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

use Exception;
use const JobScooper\DataAccess\LIST_SEPARATOR_TOKEN;
use JobScooper\DataAccess\LocationLookup;
use JobScooper\DataAccess\Map\UserJobMatchTableMap;
use JobScooper\DataAccess\User;
use JobScooper\DataAccess\UserJobMatch;
use JobScooper\DataAccess\UserJobMatchQuery;
use JobScooper\Utils\PythonRunner;
use JobScooper\Utils\Settings;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;

/**
 * Class JobsAutoMarker
 * @package JobScooper\StageProcessor
 */
class JobsAutoMarker
{

    /**
     * @var LocationLookup
     */
    protected $_locmgr = null;
    protected $title_negative_keyword_tokens = null;
    protected $companies_regex_to_filter = null;

    /**
     * @var User
     */
    private $_markingUserFacts = null;


    /**
     * JobsAutoMarker constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->_locmgr = LocationLookup::getInstance();
    }


    /**
     *
     * @param array $userFacts
     * @throws \Exception
     */
    public function markJobMatches($usersForRun)
    {
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        foreach($usersForRun as $userFacts) {
            $this->_markingUserFacts = $userFacts;

            try {
                $this->_callPythonCmd(
                    $filename = "cmd_set_out_of_area.py",
                    $descr = "{$userFacts['UserSlug']}:  Marking in/out of search areas...",
                    $extraParams = array( '--user' => $userFacts['UserSlug'])
                );
            }
            catch (Exception $ex)
            {
                handleException($ex);
            }

            //
            // Get all the postings that are in the table but not yet automarked and automark them
            // in batches so we don't max out RAM
            //
            try {
                startLogSection("{$userFacts['UserSlug']}:  Marking title matches / exclusions...");
                doCallbackForAllMatches(
                    array($this, 'markJobsListSubset_KwdsComps'),
                    [UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_NOT_YET_MARKED, Criteria::EQUAL],
                    null,
                    null,
                    $userFacts
                );
            } catch (PropelException $ex) {
                handleException($ex, null, true);
            } catch (\PDOException $ex) {
                handleException($ex, null, true);
            } catch (Exception $ex) {
                handleException($ex, null, true);
            }
            finally {
                endLogSection("{$userFacts['UserSlug']}: Finished title match / exclusion marking.");
            }
        }

        try {
            $this->_callPythonCmd(
                $filename = "cmd_exclude_duplicate_matches.py",
                $descr = "excluding duplicate job posts"
            );
        }
        catch (Exception $ex)
        {
            handleException($ex);
        }

        try {
            $this->_callPythonCmd(
                $filename = "cmd_mark_skipsend.py",
                $descr = "marking job matches for user notification"
            );
        }
        catch (Exception $ex)
        {
            handleException($ex);
        }

    }

    /**
     * @param UserJobMatch[] $results
     *
     * @throws \Exception
     */
    public function markJobsListSubset_KwdsComps($results)
    {
        try {
            $this->_markJobsList_SetAutoExcludedCompaniesFromRegex_($results);
        } catch (Exception $ex) {
            handleException($ex, "Failed to mark job postings from excluded companies: %s", true);
        }

        try {
            $this->_markJobsList_KeywordMatches_($results);
        } catch (Exception $ex) {
            handleException($ex, "Failed to mark job postings matches to user keywords: %s", true);
        }

        //
        // Done Automarking this set of Job Matches, so set them to the Marked state
        //
        try {
            $arrIdsToSetMarked = array_keys($results->toKeyIndex('UserJobMatchId'));
            $rowsSetAsMarked = UserJobMatchQuery::create()
                ->filterByUserJobMatchId($arrIdsToSetMarked, Criteria::IN)
                ->update(array("UserNotificationState" =>
                    UserJobMatchQuery::convertNotificationStateEnumToInt(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_MARKED))
                );
            LogMessage("... set {$rowsSetAsMarked} user job matches to the 'marked' user notification state.");
        } catch (Exception $ex) {
            handleException($ex, "Failed to move job postings to 'marked' state: %s", true);
        }
    }

    /**
     * @param $filename
     * @param $descr
     * @param array $extraParams
     * @throws Exception
     */
    private function _callPythonCmd($filename, $descr, $extraParams=array())
    {
        startLogSection($descr);

        try {
            $runFile = "pyJobNormalizer" . DIRECTORY_SEPARATOR . "{$filename}";
            $params = array_merge(array( '--dsn' => Settings::get_db_dsn()), $extraParams);

            $resultcode = PythonRunner::execScript($runFile, $params);

        } catch (Exception $ex) {
            handleException($ex, "ERROR:  Failed {$descr}");
        } finally {
            endLogSection($descr);
        }
    }

    /**
     * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
     * @throws \Exception
     */
    private function _markJobsList_SetAutoExcludedCompaniesFromRegex_(&$arrJobsList)
    {
        try {
            startLogSection('Marking jobs in excluded companies...');

            //
            // Load the exclusion filter and other user data from files
            //
            $this->_loadCompanyRegexesToFilter();

            $nJobsMarkedAutoExcluded = 0;
            $nJobsNotMarked = 0;

            if (null === $this->companies_regex_to_filter  || \count($arrJobsList) === 0 || \count($this->companies_regex_to_filter) === 0) {
                return;
            }

            LogMessage('Excluding Jobs by Companies Regex Matches');
            LogMessage('Checking '.count($arrJobsList) .' roles against '. \count($this->companies_regex_to_filter) .' excluded companies.');

            foreach ($arrJobsList as &$jobMatch) {
                $matched_companies = array();

                foreach ($this->companies_regex_to_filter as $rxInput) {
                    if (preg_match($rxInput, strScrub($jobMatch->getJobPostingFromUJM()->getCompany(), DEFAULT_SCRUB))) {
                        $matched_companies[] = $rxInput;
                    }
                }

                if(!is_empty_value($matched_companies) && count($matched_companies) > 0) {
                    $matches = join(LIST_SEPARATOR_TOKEN, $matched_companies);

                    $jobMatch->setBadCompanyNameKeywordMatches($matches);
                    $jobMatch->save();
                    $nJobsMarkedAutoExcluded++;
                }
                else {
                    ++$nJobsNotMarked;
                }
                unset($jobMatch);
            }

            LogMessage('Jobs marked with unwanted company name matches: '.$nJobsMarkedAutoExcluded . '/' . countAssociativeArrayValues($arrJobsList) .' marked as excluded; not marked '. $nJobsNotMarked . '/' . countAssociativeArrayValues($arrJobsList));
        } catch (Exception $ex) {
            handleException($ex, 'Error in _markJobsList_SetAutoExcludedCompaniesFromRegex_: %s', true);
        } finally {
            endLogSection('Finished marking jobs in excluded companies...');
        }
    }

    /**
     * @param string $basefile
     * @param \JobScooper\DataAccess\UserJobMatch [] $arrJobList
     *
     * @return null|string
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    private function _exportJobMatchesToJson($basefile, &$collJobList)
    {
		try {
			$arrJobItems = collectionToArray($collJobList, ['UserJobMatchId', 'JobPostingId', 'Title', 'GoodJobTitleKeywordMatches', 'BadJobTitleKeywordMatches'] );
            $arrJobItems = array_column($arrJobItems, null, 'UserJobMatchId');
            
            $jobItems = null;
            
	        $searchKeywords = array();
	        $keywords = $this->_markingUserFacts['SearchKeywords'];
	        if (is_empty_value($keywords)) {
	            return null;
	        }
	
	        $neg_kwds = $this->_loadUserNegativeTitleKeywords();
	
	        $jsonObj = array(
	            'user' => $this->_markingUserFacts,
	            'job_matches' => $arrJobItems,
	            'search_keywords' => $searchKeywords,
	            'negative_title_keywords' => $neg_kwds
	        );
	
	        $outfile = generateOutputFileName($basefile, 'json', true, 'debug');
	        writeJson($jsonObj, $outfile);

	    } catch (\Exception $ex) {
	        handleException($ex, null, false);
	    } finally {
	        $arrJobItems = null;
	        $jsonObj = null;
	        $jobItems = null;

	    }
        
        return $outfile;
    }

    /**
     * Reads JSON encoded file with an array of UserJobMatch/JobPosting combo records named "jobs"
     * and updates the database with the values for each record
     *
     * @param String $datafile The input json file to load
     * @param UserJobMatch[] &$collJobsList the job list to update from json
     *
     * @throws \Exception
     */
    private function _updateUserJobMatchesFromJson($datafile, &$collJobsList)
    {
        if (!is_file($datafile)) {
            throw new Exception("Unable to locate JSON file {$datafile} to load.");
        }

        try {
            LogMessage("Loading json file '{$datafile}'...");
            $data = loadJson($datafile);
            $retUJMIds = array();

            if (empty($data) || !array_key_exists('job_matches', $data)) {
                throw new Exception("Unable to load data from {$datafile}.  No records found.");
            }

            $arrMatchRecs = $data['job_matches'];
            LogMessage("Loaded " . \count($arrMatchRecs) . " user job matches from JSON file {$datafile} for updating in the DB...");

            //
            // Update any of the passed job records we had in the loaded data set
            //
            if (!empty($arrMatchRecs) && is_array($arrMatchRecs)) {
                if (!is_empty_value($collJobsList))
                {
                	$jobsToUpdate = array_intersect_key($collJobsList->toKeyIndex('UserJobMatchId'), $arrMatchRecs);
                    foreach ($jobsToUpdate as $jobId => $jobRecord) {
                        $jobRecord->setGoodJobTitleKeywordMatches($arrMatchRecs[$jobId]['GoodJobTitleKeywordMatches']);
                        $jobRecord->setBadJobTitleKeywordMatches($arrMatchRecs[$jobId]['BadJobTitleKeywordMatches']);

                        $jobRecord->save();
                        $retUJMIds[] = $jobId;
                        unset($jobRecord);
                        unset($arrMatchRecs[$jobId]);
                    }
                    unset($jobsToUpdate);

                }
            }

            //
            // if there were any other user job matches in the loaded JSON
            // file, we need to pull each one from the DB if exists and update
            // or insert if missing
            //
            if (!empty($arrMatchRecs))
            {
            	$jobsToUpdate = array_diff_key($collJobsList->toKeyIndex('UserJobMatchId'), $arrMatchRecs);
				if(!is_empty_value($jobsToUpdate))
				{
	                $con = Propel::getWriteConnection('default');
	
	                $chunks = array_chunk(array_keys($arrMatchRecs), 50);
	                foreach ($chunks as $idchunk) {
	                    $dbRecsById = UserJobMatchQuery::create()
	                        ->filterByUserJobMatchId($idchunk, Criteria::IN)
	                        ->find()
	                        ->toKeyIndex('UserJobMatchId');
	                    foreach ($idchunk as $id) {
	                        if (array_key_exists($id, $dbRecsById)) {
	                            $dbMatch = $dbRecsById[$id];
	                        } else {
	                            $dbMatch = UserJobMatchQuery::create()
	                                ->filterByUserJobMatchId($id)
	                                ->findOneOrCreate();
	                            $dbMatch->setUserJobMatchId($id);
	                        }
                            $dbMatch->setGoodJobTitleKeywordMatches($arrMatchRecs[$id]['GoodJobTitleKeywordMatches']);
                            $dbMatch->setBadJobTitleKeywordMatches($arrMatchRecs[$id]['BadJobTitleKeywordMatches']);

	                        $dbMatch->save();
	                        $retUJMIds[] = $id;
	                        unset($dbMatch);
	                    }
	
	                    // commit the last 50 to the database and then fetch
	                    // a clean connection so we don't trip up the database with long connection times
	                    $con->commit();
	                    // fetch a new connection
	                    $con = Propel::getWriteConnection('default');

                        unset($dbRecsById);
                    }
	            }
			}
            unset($jobsToUpdate);
            unset($arrMatchRecs);
            $totalMarked =  \count($retUJMIds);
            LogMessage("... updated {$totalMarked} user job matches loaded from json file '{$datafile}.");
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param \JobScooper\DataAccess\UserJobMatch[] $collJobsList
     * @throws \Exception
     */
    private function _markJobsList_KeywordMatches_(&$collJobsList)
    {
        if(is_empty_value($collJobsList)) {
            LogWarning('Automarker: No jobs found to match against user search keywords.');
            return;
        }

        startLogSection('Matching user\'s keywords against ' .  \count($collJobsList) . ' job titles...');

        try {
            $basefile = 'mark_titlematches';

            LogMessage('Exporting ' . \count($collJobsList) . " user job matches to JSON file '{$basefile}_src.json' for matching...");
            $sourcefile = $this->_exportJobMatchesToJson("{$basefile}_src", $collJobsList);
            $resultsfile = generateOutputFileName("{$basefile}_results", 'json', true, 'debug');

            $this->_callPythonCmd(
                $filename = "cmd_match_titles_to_keywords.py",
                $descr = "matching job titles for user",
                $extraParams = [
                    '-i' => $sourcefile,
                    '-o' => $resultsfile
                ]
            );
            LogMessage('Updating database with new match results...');
            $this->_updateUserJobMatchesFromJson($resultsfile, $collJobsList);

        } catch (Exception $ex) {
            handleException($ex, 'ERROR:  Failed to verify titles against keywords due to error: %s');
        } finally {
            endLogSection('Finished: search keyword/job title matching.');
        }
    }

    /**
     * @throws \Exception
     */
    private function _loadUserNegativeTitleKeywords()
    {
        assert(null !== $this->_markingUserFacts);

        if(is_empty_value($this->_markingUserFacts['UserId'])) {
            throw new \InvalidArgumentException('Unable to automark jobs:  UserId not found.');
        }
        $user = User::getUserObjById($this->_markingUserFacts['UserId']);
        if(null === $user) {
            throw new \InvalidArgumentException("Unable to mark jobs:  user ID {$this->_markingUserFacts['UserId']} not found.");
        }
        
        $inputfiles = $user->getInputFiles('negative_title_keywords');
        $user = null;

        if (!\is_array($inputfiles)) {
            // No files were found, so bail
            LogDebug('No input files were found with title token strings to exclude.');

            return array();
        }

        $arrNegKwds = array();

        foreach ($inputfiles as $fileItem) {
            $arrRecs = loadCSV($fileItem);
            foreach ($arrRecs as $arrRec) {
                if (array_key_exists('negative_keywords', $arrRec)) {
                    $kwd = trim(strtolower($arrRec['negative_keywords']));
                    if (!empty($kwd)) {
                        $arrNegKwds[$kwd] = $kwd;
                    }
                }
            }
        }
        return array_unique($arrNegKwds, SORT_REGULAR);
    }

    /**
     * @param $pattern
     *
     * @return string
     * @throws \Exception
     */
    private function _scrubRegexSearchString($pattern)
    {
        $delim = '~';
        if (strpos($pattern, $delim) != false) {
            $delim = '|';
        }

        $rx = $delim.preg_quote(trim($pattern), $delim).$delim.'i';
        try {
            preg_match($rx, 'empty');
        } catch (\Exception $ex) {
            LogError($ex->getMessage());
            if (isDebug() === true) {
                throw $ex;
            }
        }
        return $rx;
    }



    /**
     * Initializes the global list of titles we will automatically mark
     * as 'not interested' in the final results set.
     * @throws \Exception
     */
    public function _loadCompanyRegexesToFilter()
    {
        if (!is_empty_value($this->companies_regex_to_filter)) {
            // We've already loaded the companies; go ahead and return right away
            LogDebug('Using previously loaded ' .  \count($this->companies_regex_to_filter) . ' regexed company strings to exclude.');
            return;
        }

        if(is_empty_value($this->_markingUserFacts['UserId'])) {
            throw new \InvalidArgumentException('Unable to automark jobs:  UserId not found.');
        }
        $user = User::getUserObjById($this->_markingUserFacts['UserId']);
        if(null === $user) {
            throw new \InvalidArgumentException("Unable to mark jobs:  user ID {$this->_markingUserFacts['UserId']} not found.");
        }
        
        $inputfiles = $user->getInputFiles('regex_filter_companies');
		$user = null;
        if (is_empty_value($inputfiles) ||  !\is_array($inputfiles)) {
            return;
        }

        $regexList = array();
        foreach ($inputfiles as $fileItem) {
            LogDebug("Loading job Company regexes to filter from { $fileItem }.");
            $loadedCompaniesRegex = loadCSV($fileItem, 'match_regex');
            if (!empty($loadedCompaniesRegex)) {
                //	        $classCSVFile = new SimpleCSV($fileItem, 'r');
                //	        $loadedCompaniesRegex= $classCSVFile->readAllRecords(true, array('match_regex'));
                $regexList = array_merge($regexList, array_column($loadedCompaniesRegex, 'match_regex'));
                LogDebug( \count($loadedCompaniesRegex) . " companies found in the file {$fileItem} that will be automatically filtered from job listings.");
            }
        }
        $regexList = array_unique($regexList);

        //
        // Add each Company we found in the file to our list in this class, setting the key for
        // each record to be equal to the job Company so we can do a fast lookup later
        //
        if (!empty($regexList) && \is_array($regexList)) {
            foreach ($regexList as $rxItem) {
                try {
                    $rx = $this->_scrubRegexSearchString($rxItem);
                    $this->companies_regex_to_filter[] = $rx;
                } catch (\Exception $ex) {
                    $strError = "Regex test failed on company regex pattern {$rxItem}.  Skipping.  Error: '{$ex->getMessage()}'";
                    LogError($strError);
	                    if (isDebug() === true) {
                        throw new \ErrorException($strError);
                    }
                }
            }
        }

        if (count($inputfiles) == 0) {
            LogDebug("No file specified for companies regexes to exclude from '" . getArrayValuesAsString($inputfiles) . "'.  Final list will not be filtered.");
        } elseif (empty($this->companies_regex_to_filter)) {
            LogDebug("Could not load regex list for companies to exclude from '" . getArrayValuesAsString($inputfiles) . "'.  Final list will not be filtered.");
        }

        LogMessage('Loaded ' . \count($this->companies_regex_to_filter). ' regexes to use for filtering companies from ' . getArrayValuesAsString($inputfiles));
    }
}
