<?php
/**
 * Copyright 2014-18 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
namespace JobScooper\StageProcessor;

use JobScooper\DataAccess\Base\GeoLocation;use JobScooper\DataAccess\JobSiteManager;
use JobScooper\DataAccess\JobPostingQuery;
use Exception;
use JobScooper\DataAccess\GeoLocationManager;
use JobScooper\DataAccess\LocationLookup;use JobScooper\DataAccess\LocationLookupQuery;use JobScooper\DataAccess\Map\JobPostingTableMap;use JobScooper\Utils\PythonRunner;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;

/**
 * Class JobsAutoMarker
 * @package JobScooper\StageProcessor
 */
class DataNormalizer
{

    /**
     * DataNormalizer constructor.
     *
     * @param array $userFacts
     * @throws \Exception
     */
    public function __construct()
    {

    }

    /**
     *
     * @throws \Exception
     */
    public function normalizeJobs($onlyNew=true)
    {
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        try {
            $this->_findAndMarkRecentDuplicatePostings();
        } catch (Exception $ex) {
            LogError($ex->getMessage(), null, $ex);
        }

    }


	   
	/**
	 * @param $query
	 * @param $callback
	 *
	 * @throws \Exception
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	function doBatchCallbackForQuery($query, $callback)
	{
	    $chunkResults = null;
	    $continueLoop = true;
        $con = \Propel\Runtime\Propel::getWriteConnection("default");

	    $nResults = 0;
	    $queryClass = get_class($query);
		$nCurrentPage = 1;
	    $jobsPager = $query->paginate($page = $nCurrentPage, $maxPerPage = 500, $con);
		
	    while ((null !== $jobsPager && !$jobsPager->isEmpty()) || $continueLoop === true) {
            $nSetResults = $nResults + $jobsPager->count() - 1;
            $jobsPageData = $jobsPager->getResults();
            LogMessage("Processing query results #{$nResults} - {$nSetResults} of {$jobsPager->getNbResults() } total results via callback {$queryClass}...");
            $nResults = $nResults + $nSetResults;
            call_user_func($callback, $jobsPageData);
            $nCurrentPage += 1;
            
            if (!$jobsPager->isEmpty()) {
            
			    $jobsPager = $query->paginate($page = $nCurrentPage, $maxPerPage = 200, $con);
            } else {
                $continueLoop = false;
            }
        }
        
    }
	   
    /**
     * @throws \Exception
     */
    private function _findAndMarkRecentDuplicatePostings()
    {
        startLogSection('Finding new duplicate company / job title pairs in the past 7 days to mark as dupe...');
        try {
            $daysBack = 7;
            $sinceWhen = date_add(new \DateTime(), date_interval_create_from_date_string("{$daysBack} days ago"));
            $included_sites = JobSiteManager::getJobSiteKeysIncludedInRun();

            LogMessage("Querying for all job postings created in the last {$daysBack} days");
            $dupeQuery = JobPostingQuery::create()
                ->filterByFirstSeenAt(array('min' => $sinceWhen));

            if (!empty($included_sites)) {
                $dupeQuery->filterByJobSiteKey($included_sites, Criteria::IN);
            }
			$dupeQuery->orderByKeyCompanyAndTitle();
            
            $this->doBatchCallbackForQuery($dupeQuery, array($this, '_markDuplicatePostings'));

            
        } catch (\Exception $ex) {
            handleException($ex, null, false);
        } finally {
            endLogSection('Finished processing job posting duplicates.');
        }
    }

    /**
     * @throws \Exception
     */
    private function _markDuplicatePostings($queryResults)
    {

    	if (is_empty_value($queryResults)) {
    		return;
        }
        
        try {
	        $recentJobPostings = $queryResults->toArray("JobPostingId");
			unset($dupeQuery);
	
	        $itemKeysToExport = array('JobPostingId', 'Title', 'Company', 'JobSite', 'KeyCompanyAndTitle', 'GeoLocationId', 'FirstSeenAt', 'DuplicatesJobPostingId');
			$postsToExport = array_child_columns($recentJobPostings, $itemKeysToExport, "JobPostingId");
			unset($recentJobPostings);
	  
			if(is_empty_value($postsToExport)) {
		        LogWarning('JobNormalizer: No jobs found to check for duplicates.');
		        return;
	        }
	
	        $cntJobs = countAssociativeArrayValues($postsToExport);
	
	        $jsonObj = array(
	            'job_postings' => $postsToExport,
	            'count' => count($postsToExport)
	        );
	
	        $outfile = generateOutputFileName('dedupe', 'json', true, 'debug');
	        $resultsfile = generateOutputFileName('deduped_jobs_results', 'json', true, 'debug');
	        LogMessage("Exporting {$cntJobs} job postings to {$outfile} for deduplication...");
	        writeJson($jsonObj, $outfile);
	
	        unset($postsToExport, $jsonObj);
	
	        startLogSection('Calling python to dedupe new job postings...');
			$runFile = 'pyJobNormalizer/mark_duplicates.py';
			$params = [
				'-i' => $outfile,
				'-o' => $resultsfile
			];
			
			$results = PythonRunner::execScript($runFile, $params);
			
	        endLogSection('Python command call finished.');
	        
	        if (!is_file($resultsfile)) {
	            throw new Exception("Job posting deduplication failed.  No dedupe results file was found to load into the database at {$resultsfile}");
	        }
	
	        LogMessage("Loading list of duplicate job postings from {$resultsfile}...");
	        $jobsToMarkDupe = loadJSON($resultsfile);
	
	        $cntJobsToMark = countAssociativeArrayValues($jobsToMarkDupe['duplicate_job_postings']);
	
	        LogMessage("Marking {$cntJobsToMark} jobs as duplicate in the database...");
	
	        $totalMarked = 0;
	        $con = Propel::getWriteConnection('default');
	
	        foreach ($jobsToMarkDupe['duplicate_job_postings'] as $key=>$job) {
	            $jobRecord =  \JobScooper\DataAccess\JobPostingQuery::create()
	                ->filterByPrimaryKey($key)
	                ->findOneOrCreate();
	            assert($jobRecord->isNew() === false);
	            $jobRecord->setDuplicatesJobPostingId($job['isDuplicateOf']);
	            $jobRecord->save($con);
	            ++$totalMarked;
	            if ($totalMarked % 100 === 0) {
	                $con->commit();
	                // fetch a new connection
	                $con = Propel::getWriteConnection('default');
	                LogMessage("... marked {$totalMarked} duplicate job postings...");
	            }
	            unset($jobRecord);
	        }
	
	        LogMessage("Marked {$totalMarked} job listings as duplicate of an earlier job posting with the same company and job title.");
	    } catch (\Exception $ex) {
	        handleException($ex, null, false);
	    } finally {
	        endLogSection('Finished processing job posting duplicates.');
	    }
    }


}

