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

use JobScooper\DataAccess\JobSiteManager;
use JobScooper\DataAccess\JobPostingQuery;
use Exception;
use JobScooper\DataAccess\Map\UserJobMatchTableMap;
use JobScooper\DataAccess\User;
use JobScooper\DataAccess\UserJobMatch;
use JobScooper\DataAccess\UserJobMatchQuery;
use JobScooper\DataAccess\GeoLocationManager;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;

/**
 * Class JobsAutoMarker
 * @package JobScooper\StageProcessor
 */
class JobsAutoMarker
{

    /**
     * @var GeoLocationManager
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
     * @param array $userFacts
     * @throws \Exception
     */
    public function __construct(array $userFacts)
    {
        $this->_markingUserFacts = $userFacts;
        $this->_locmgr = GeoLocationManager::getLocationManager();
    }

    /**
     *
     * @throws \Exception
     */
    public function markJobs()
    {

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        LogMessage(PHP_EOL . '**************  Updating jobs list for known filters ***************' . PHP_EOL);

        try {
            // Dupes aren't affected by the user's matches so do that marking first
            //
            $this->_findAndMarkRecentDuplicatePostings();
        } catch (Exception $ex) {
            LogError($ex->getMessage(), null, $ex);
        }

        try {

            // Get all the postings that are in the table but not marked as ready-to-send
            //
            doCallbackForAllMatches(
                array($this, '_markJobsListSubset_'),
                [UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_NOT_YET_MARKED, Criteria::EQUAL],
                null,
                null,
                $this->_markingUserFacts
            );
        } catch (Exception $ex) {
            LogError($ex->getMessage(), null, $ex);
            throw $ex;
        }
    }

    /**
     * @param UserJobMatch[] $results
     *
     * @throws \Exception
     */
    public function _markJobsListSubset_($results)
    {
        $errs = array();

        LogMessage('Clearing old auto-marked facts from jobs we are re-marking...');
        try {
            foreach ($results as $jobMatch) {
                $jobMatch->clearUserMatchState();
            }
        } catch (Exception $ex) {
            LogError($ex->getMessage(), null, $ex);
            $errs[] = $ex;
        }

        try {
            $this->_markJobsList_SetOutOfArea_($results);
        } catch (Exception $ex) {
            LogError($ex->getMessage(), null, $ex);
            $errs[] = $ex;
        }

        try {
            $this->_markJobsList_SetAutoExcludedCompaniesFromRegex_($results);
        } catch (Exception $ex) {
            LogError($ex->getMessage(), null, $ex);
            $errs[] = $ex;
        }

        try {
            $this->_markJobsList_KeywordMatches_($results);
        } catch (Exception $ex) {
            LogError($ex->getMessage(), null, $ex);
            $errs[] = $ex;
        }


        if (!empty($errs)) {
            $err_to_return = '';
            foreach ($errs as $ex) {
                if (!empty($err_to_return)) {
                    $err_to_return .= PHP_EOL . PHP_EOL;
                }
                $err_to_return .= getArrayDebugOutput(object_to_array($ex));
            }

            $last = array_pop($ex);
            throw new Exception("AutoMarking Errors Occurred:  {$err_to_return}", $last->getCode(), $last);
        }


        //
        // Since we did not require each update in the previous calls to call save()
        // for each UserJobMatch and take the perf hit that would generate, it is
        // probable that some of the rows are inconsistent between their facts for
        // match exclusion and the IsExcluded fact.  So let's grab them all fresh
        // from the DB with the updated data and re-save each of them, which will
        // cause IsExcluded to get updated and back in sync for each record.
        //
        // We'll use this same Save call to store that the record has been automarked
        // and is ready for sending.
        //
        LogMessage('Auto-marking complete. Setting marked jobs to \'ready-to-send\'...');
        try {
            $con = Propel::getWriteConnection('default');
            $totalMarkedReady = 0;
            foreach ($results as $jobMatch) {
                $jobMatch->setUserNotificationState(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_MARKED_READY_TO_SEND);
                $jobMatch->save($con);
                $totalMarkedReady = $totalMarkedReady + 1;
                if ($totalMarkedReady % 100 === 0) {
                    $con->commit();

                    // fetch a new connection
                    $con = Propel::getWriteConnection('default');
                    $nStartMarked = $totalMarkedReady - 100;
                    LogMessage("... user job matches {$nStartMarked} - {$totalMarkedReady} marked 'ready-to-send.'");
                }
            }

            $con->commit();
        } catch (Exception $ex) {
            LogError($ex->getMessage(), null, $ex);
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
            $itemKeysToExport = array('JobPostingId', 'Title', 'Company', 'JobSite', 'KeyCompanyAndTitle', 'GeoLocationId', 'FirstSeenAt', 'DuplicatesJobPostingId');

            LogMessage("Querying for all job postings created in the last {$daysBack} days");
            $dupeQuery = JobPostingQuery::create()
                ->filterByFirstSeenAt(array('min' => $sinceWhen));

            if (!empty($included_sites)) {
                $dupeQuery->filterByJobSiteKey($included_sites, Criteria::IN);
            }

            $recentJobPostings = $dupeQuery->find();

            //			$outfile = generateOutputFileName('dedupe', 'csv', true, 'debug');
            //			LogMessage("Writing results to CSV {$outfile}");
            //			file_put_contents($outfile, $recentJobPostings->toCSV(false, false));
//

            $arrRecentJobs = array();
            LogMessage('Reducing full resultset data to just the columns needed for deduplication...');
            foreach ($recentJobPostings->toKeyIndex('JobPostingId') as $id => $job) {
                $arrRecentJobs[$id] = $job->toFlatArrayForCSV(false, $itemKeysToExport);
            }
            $cntJobs = countAssociativeArrayValues($arrRecentJobs);

            $jsonObj = array(
                'user' => $this->_markingUserFacts,
                'job_postings' => $arrRecentJobs
            );

            $outfile = generateOutputFileName('dedupe', 'json', true, 'debug');
            $resultsfile = generateOutputFileName('deduped_jobs_results', 'json', true, 'debug');
            LogMessage("Exporting {$cntJobs} job postings to {$outfile} for deduplication...");
            writeJson($jsonObj, $outfile);

            unset($arrRecentJobs, $jsonObj);

            try {
                startLogSection('Calling python to dedupe new job postings...');
                $PYTHONPATH = realpath(__ROOT__ . '/python/pyJobNormalizer/mark_duplicates.py');
                $cmd = 'python ' . $PYTHONPATH . ' -i ' . escapeshellarg($outfile) . ' -o ' . escapeshellarg($resultsfile);

                $venvDir = __ROOT__ . '/python/.venv/bin';
                if(is_dir($venvDir)) {
                    $cmd = preg_replace("/python /", "source {$venvDir}/activate; python ", $cmd);
                }

                LogMessage(PHP_EOL . "    ~~~~~~ Running command: {$cmd}  ~~~~~~~" . PHP_EOL);
                doExec($cmd);
            } catch (Exception $ex) {
                throw $ex;
            } finally {
                endLogSection('Python command call finished.');
            }
            
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
            }

            LogMessage("Marked {$totalMarked} job listings as duplicate of an earlier job posting with the same company and job title.");
        } catch (\Exception $ex) {
            handleException($ex, null, false);
        } finally {
            endLogSection('Finished processing job posting duplicates.');
        }
    }

    /**
     * @return bool
     */
    private function _isGeoSpatialWorking()
    {
        $sqlType = \Propel\Runtime\Propel::getServiceContainer()->getAdapterClass();
        switch ($sqlType) {
            case 'mysql':
                return true;
                break;

            default:
            return false;
                break;

            case 'sqlite':
                try {
                    $ret = loadSqlite3MathExtensions();
                    if ($ret) {
                        LogMessage('Successfully loaded the necessary math functions for SQLite to do geospatial filtering.');
                    }
                    return $ret;
                } catch (\Exception $ex) {
                    LogWarning('Failed to load the necessary math functions for SQLite to do geospatial filtering.  Falling back to county-level instead.');
                }
                break;
        }

        return false;
    }

    /**
     * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
     * @throws \Exception
     */
    private function _markJobsList_SetOutOfArea_(&$arrJobsList)
    {
        if (count($arrJobsList) == 0) {
            return;
        }

        LogMessage('Marking Out of Area Jobs');

        if ($this->_isGeoSpatialWorking()) {
            $this->_markJobsList_OutOfArea_Geospatial($arrJobsList);
        } else {
            $this->_markJobsList_OutOfArea_CountyFiltered($arrJobsList);
        }
    }

    /**
     * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
     * @throws \Exception
     */
    private function _markJobsList_OutOfArea_CountyFiltered(&$arrJobsList)
    {
        try {
            startLogSection('Automarker: marking jobs as out of area using counties...');

            $userObj = User::getUserObjById($this->_markingUserFacts['UserId']);
            $searchLocations = $userObj->getSearchGeoLocations();


            $arrIncludeCounties = array();

            /* Find all locations that are within 50 miles of any of our search locations */

            LogMessage('Auto-marking postings not in same counties as the search locations...');
            foreach ($searchLocations as $searchloc) {
                if (null !== $searchloc) {
                    $arrIncludeCounties[] = $searchloc->getCounty() . '~' .$searchloc->getRegion();
                }
            }

            LogMessage('Finding job postings not in the following counties & states: ' . getArrayValuesAsString($arrIncludeCounties) . ' ...');
            $arrJobsOutOfArea = array_filter($arrJobsList, function (UserJobMatch $v) use ($arrIncludeCounties) {
                $posting = $v->getJobPostingFromUJM();
                $locId = $posting->getGeoLocationId();
                if (null === $locId) {
                    return false;
                }  // if we don't have a location, assume nearby

                $location = $posting->getGeoLocationFromJP();
                $county = $location->getCounty();
                $state = $location->getRegion();
                if (null !== $county && null !== $state) {
                    $match = $county . '~' . $state;
                    if (!in_array($match, $arrIncludeCounties)) {
                        return true;
                    }
                }
                return false;
            });

            LogMessage('Marking user job matches as out of area for ' . count($arrJobsOutOfArea) . ' matches ...');

            foreach ($arrJobsOutOfArea as &$jobOutofArea) {
                $jobOutofArea->setOutOfUserArea(true);
                $jobOutofArea->save();
            }
            $nJobsMarkedAutoExcluded = count($arrJobsOutOfArea);
            $nJobsNotMarked = count($arrJobsList) - $nJobsMarkedAutoExcluded;


            LogMessage('Jobs excluded as out of area: marked '. $nJobsMarkedAutoExcluded . '/' . countAssociativeArrayValues($arrJobsList) .';  not marked ' . $nJobsNotMarked . ' / ' . countAssociativeArrayValues($arrJobsList));
        } catch (Exception $ex) {
            handleException($ex, 'Error in _markJobsList_OutOfArea_CountyFiltered: %s', true);
        } finally {
            endLogSection('Out of area job marking by county finished.');
            $user = null;
            $arrJobsOutOfArea = null;
            $searchLocations = null;
        }
    }

    /**
     * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
     * @throws \Exception
     */
    private function _markJobsList_OutOfArea_Geospatial(&$arrJobsList)
    {
        try {
            startLogSection('Automarker: marking jobs as out of area using geospatial data...');
            if(is_empty_value($this->_markingUserFacts['UserId'])) {
            	throw new \InvalidArgumentException('Unable to automark jobs:  UserId not found.');
            }
            $user = User::getUserObjById($this->_markingUserFacts['UserId']);
            if(null === $user) {
            	throw new \InvalidArgumentException("Unable to mark jobs:  user ID {$this->_markingUserFacts['UserId']} not found.");
            }
            
            
            $arrNearbyGeoLocIds = array();

            $searchLocations = $user->getSearchGeoLocations();
            $user = null;
            if(is_empty_value($searchLocations)) {
            	LogWarning("No search locations were found for user ID {$this->_markingUserFacts['UserId']}.");
            	return;
            }
            

            /* Find all locations that are within 50 miles of any of our search locations */

            LogMessage('Getting locationIDs within 50 miles of search locations...');
            foreach ($searchLocations as $searchloc) {
                if (null !== $searchloc) {
                    $arrNearbyGeoLocIds = array_merge(array_values($arrNearbyGeoLocIds), array_values(getGeoLocationsNearby($searchloc)));
                }
				$searchloc = null;
            }
			$searchLocations = null;
            
            $arrJobListFacts = convert_propel_objects_to_arrays($arrJobsList, 'UserJobMatchId');
            $arrJobListIds = array_keys($arrJobListFacts);
            $arrJobListFacts = null;
			
            LogMessage('Marking job postings in the ' . count($arrNearbyGeoLocIds) . ' nearby GeoLocations...');
            $arrInAreaJobs = array_filter($arrJobsList, function (UserJobMatch &$var) use ($arrNearbyGeoLocIds) {
                if (null !== $var->getJobPostingFromUJM()) {
                    $geoId = $var->getJobPostingFromUJM()->getGeoLocationId();
                    if (!is_empty_value($geoId) && !is_empty_value(array_intersect($arrNearbyGeoLocIds, array($geoId)))) {
                        return true;
                    }
                }
                return false;
            });

            $arrJobIdsInArea = array_keys($arrInAreaJobs);
            $arrInAreaJobs = null;
            $arrJobIdsOutOfArea = array_diff($arrJobListIds, $arrJobIdsInArea);

            foreach (array_chunk($arrJobIdsInArea, 50) as $chunk) {
                $con = Propel::getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
                UserJobMatchQuery::create()
                    ->filterByUserJobMatchId($chunk)
                    ->update(array('OutOfUserArea' => false), $con);
            }

            LogMessage('Marking job postings outside ' . count($arrNearbyGeoLocIds) . ' matching areas ...');
            if (!empty($arrJobIdsOutOfArea)) {
                foreach (array_chunk($arrJobIdsOutOfArea, 50) as $chunk) {
                    $con = Propel::getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
                    UserJobMatchQuery::create()
                        ->filterByUserJobMatchId($chunk)
                        ->update(array('OutOfUserArea' => true), $con);
                }
            }

            $nJobsMarkedAutoExcluded = count($arrJobIdsOutOfArea);
            $nJobsNotMarked = count($arrJobIdsInArea);
            $nJobsTotal = count($arrJobListIds);

            LogMessage("Jobs excluded as out of area:  marked out of area {$nJobsMarkedAutoExcluded}/{$nJobsTotal}; marked in area = {$nJobsNotMarked}/{$nJobsTotal}");
        } catch (Exception $ex) {
            handleException($ex, 'Error in _markJobsList_OutOfArea_Geospatial: %s', true);
        } finally {
            endLogSection('Out of area job marking geospatially finished.');
        }
    }

    /**
     * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
     * @throws \Exception
     */
    private function _markJobsList_SetAutoExcludedCompaniesFromRegex_(&$arrJobsList)
    {
        try {
            startLogSection('Automarker: marking company names as excluded based on user input files...');

            //
            // Load the exclusion filter and other user data from files
            //
            $this->_loadCompanyRegexesToFilter();

            $nJobsMarkedAutoExcluded = 0;
            $nJobsNotMarked = 0;

            if (null === $this->companies_regex_to_filter  || count($arrJobsList) === 0 || count($this->companies_regex_to_filter) === 0) {
                return;
            }

            LogMessage('Excluding Jobs by Companies Regex Matches');
            LogMessage('Checking '.count($arrJobsList) .' roles against '. count($this->companies_regex_to_filter) .' excluded companies.');

            foreach ($arrJobsList as &$jobMatch) {
                $matched_exclusion = false;
                foreach ($this->companies_regex_to_filter as $rxInput) {
                    if (preg_match($rxInput, strScrub($jobMatch->getJobPostingFromUJM()->getCompany(), DEFAULT_SCRUB))) {
                        $jobMatch->setMatchedNegativeCompanyKeywords(array($rxInput));
                        $jobMatch->save();
                        $nJobsMarkedAutoExcluded++;
                        $matched_exclusion = true;
                        break;
                    }
                }
				unset($jobMatch);

                if ($matched_exclusion !== true) {
                    ++$nJobsNotMarked;
                }
            }

            LogMessage('Jobs marked with excluded companies: '.$nJobsMarkedAutoExcluded . '/' . countAssociativeArrayValues($arrJobsList) .' marked as excluded; not marked '. $nJobsNotMarked . '/' . countAssociativeArrayValues($arrJobsList));
        } catch (Exception $ex) {
            handleException($ex, 'Error in SetAutoExcludedCompaniesFromRegex: %s', true);
        } finally {
            endLogSection('Company exclusion by name finished.');
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
    private function _exportJobMatchesToJson($basefile='automarker', $arrJobList)
    {
        $jobMatchKeys = array();
        $arrJobItems = array();
        if ($arrJobList) {
            $item = array_shift($arrJobList);
            $jobMatchKeys = array_keys($item->toArray());
            $jobMatchKeys[] = 'Title';
            array_unshift($arrJobList, $item);
        }
        foreach ($arrJobList as $job) {
            $arrJobItems[$job->getUserJobMatchId()] = $job->toFlatArrayForCSV($jobMatchKeys);
        }

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

        return $outfile;
    }

    private function _updateKeywordMatchForSingleJob(UserJobMatch $job, $arrMatchData)
    {
        $arrJobMatchFacts = array_subset_keys($arrMatchData, array(
            'UserJobMatchId',
            'MatchedNegativeTitleKeywords',
            'MatchedUserKeywords'
        ));
        if (!empty($arrJobMatchFacts['MatchedUserKeywords'])) {
            if (is_string($arrJobMatchFacts['MatchedNegativeTitleKeywords'])) {
                $split = preg_split("/\|/", $arrJobMatchFacts['MatchedUserKeywords'], -1, PREG_SPLIT_NO_EMPTY);
                if (!empty($split)) {
                    $arrJobMatchFacts['MatchedUserKeywords'] = $split;
                }
            }
        }
        if (!empty($arrJobMatchFacts['MatchedNegativeTitleKeywords'])) {
            if (is_string($arrJobMatchFacts['MatchedNegativeTitleKeywords'])) {
                $split = preg_split("/\|/", $arrJobMatchFacts['MatchedNegativeTitleKeywords'], -1, PREG_SPLIT_NO_EMPTY);
                if (!empty($split)) {
                    $arrJobMatchFacts['MatchedNegativeTitleKeywords'] = $split;
                }
            }
        }
        $job->fromArray($arrJobMatchFacts);
    }

    /**
     * Reads JSON encoded file with an array of UserJobMatch/JobPosting combo records named "jobs"
     * and updates the database with the values for each record
     *
     * @param String $datafile The input json file to load
     * @param UserJobMatch[] &$arrJobsList the job list to update from json
     *
     * @throws \Exception
     */
    private function _updateUserJobMatchesFromJson($datafile, &$arrJobsList)
    {
        if (!is_file($datafile)) {
            throw new Exception("Unable to locate JSON file {$datafile} to load.");
        }

        try {
            LogMessage("Loading json file '{$datafile}'...");
            $data = loadJSON($datafile);
            $retUJMIds = array();

            if (empty($data) || !array_key_exists('job_matches', $data)) {
                throw new Exception("Unable to load data from {$datafile}.  No records found.");
            }

            $arrMatchRecs = $data['job_matches'];
            LogMessage("Loaded " . count($arrMatchRecs) . " user job matches from JSON file {$datafile} for updating in the DB...");

            //
            // Update any of the passed job records we had in the loaded data set
            //
            if (!empty($arrMatchRecs) && is_array($arrMatchRecs)) {
                $arrUserJobMatchIds = array_keys($arrMatchRecs);

                if (!empty($arrJobsList)) {
                    $jobIdsToUpdate = array_intersect($arrUserJobMatchIds, array_keys($arrJobsList));
                    foreach ($jobIdsToUpdate as $jobid) {
                        $this->_updateKeywordMatchForSingleJob($arrJobsList[$jobid], $arrMatchRecs[$jobid]);
                        unset($arrMatchRecs[$jobid]);
                        $retUJMIds[] = $jobid;
                    }
                }
            }

            //
            // if there were any other user job matches in the loaded JSON
            // file, we need to pull each one from the DB if exists and update
            // or insert if missing
            //
            if (!empty($arrMatchRecs)) {
                $con = Propel::getWriteConnection('default');
                $dbRecsById = array();
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
                        $this->_updateKeywordMatchForSingleJob($dbMatch, $arrMatchRecs[$id]);
                        $dbMatch->save();
                        $retUJMIds[] = $id;
                    }

                    // commit the last 50 to the database and then fetch
                    // a clean connection so we don't trip up the database with long connection times
                    $con->commit();
                    // fetch a new connection
                    $con = Propel::getWriteConnection('default');
                }
            }

            $totalMarked = count($retUJMIds);

            LogMessage("... updated {$totalMarked} user job matches loaded from json file '{$datafile}.");
        } catch (Exception $ex) {
            throw $ex;
        }
    }



    /**
     * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
     * @throws \Exception
     */
    private function _markJobsList_KeywordMatches_(&$arrJobsList)
    {
        startLogSection('Automarker: Starting matching of ' . count($arrJobsList) . ' job role titles against user search keywords ...');

        try {
            $basefile = 'mark_titlematches';

            LogMessage('Exporting ' . count($arrJobsList) . " user job matches to JSON file '{$basefile}_src.json' for matching...");
            $sourcefile = $this->_exportJobMatchesToJson("{$basefile}_src", $arrJobsList);
            $resultsfile = generateOutputFileName("{$basefile}_results", 'json', true, 'debug');

            try {
                startLogSection('Calling python to do work of job title matching.');
                $PYTHONPATH = realpath(__ROOT__ . '/python/pyJobNormalizer/matchTitlesToKeywords.py');
                $cmd = 'python ' . $PYTHONPATH . ' -i ' . escapeshellarg($sourcefile) . ' -o ' . escapeshellarg($resultsfile);

                #					$cmd = 'source ' . realpath(__ROOT__) . '/python/pyJobNormalizer/venv/bin/activate; ' . $cmd;

                LogMessage(PHP_EOL . '    ~~~~~~ Running command: ' . $cmd . '  ~~~~~~~' . PHP_EOL);
                doExec($cmd);

                LogMessage('Updating database with new match results...');
                $this->_updateUserJobMatchesFromJson($resultsfile, $arrJobsList);
            } catch (Exception $ex) {
                throw $ex;
            } finally {
                endLogSection('Python command call finished.');
            }
        } catch (Exception $ex) {
            handleException($ex, 'ERROR:  Failed to verify titles against keywords due to error: %s');
        } finally {
            endLogSection('Job role title matching finished.');
        }
    }

    /**
     * @throws \Exception
     */
    private function _loadUserNegativeTitleKeywords()
    {
        assert(!empty($this->_markingUserFacts));
        if(is_empty_value($this->_markingUserFacts['UserId'])) {
            throw new \InvalidArgumentException('Unable to automark jobs:  UserId not found.');
        }
        $user = User::getUserObjById($this->_markingUserFacts['UserId']);
        if(null === $user) {
            throw new \InvalidArgumentException("Unable to mark jobs:  user ID {$this->_markingUserFacts['UserId']} not found.");
        }
        
        $inputfiles = $user->getInputFiles('negative_title_keywords');
        $user = null;

        if (!is_array($inputfiles)) {
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
        $negKwdForTokens = array_unique($arrNegKwds, SORT_REGULAR);

        return $negKwdForTokens;
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
            $testMatch = preg_match($rx, 'empty');
        } catch (\Exception $ex) {
            LogError($ex->getMessage());
            if (isDebug() == true) {
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
            LogDebug('Using previously loaded ' . count($this->companies_regex_to_filter) . ' regexed company strings to exclude.');
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
        if (is_empty_value($inputfiles) ||  !is_array($inputfiles)) {
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
                LogDebug(count($loadedCompaniesRegex) . " companies found in the file {$fileItem} that will be automatically filtered from job listings.");
            }
        }
        $regexList = array_unique($regexList);

        //
        // Add each Company we found in the file to our list in this class, setting the key for
        // each record to be equal to the job Company so we can do a fast lookup later
        //
        if (!empty($regexList) && is_array($regexList)) {
            foreach ($regexList as $rxItem) {
                try {
                    $rx = $this->_scrubRegexSearchString($rxItem);
                    $this->companies_regex_to_filter[] = $rx;
                } catch (\Exception $ex) {
                    $strError = "Regex test failed on company regex pattern " . $rxItem .".  Skipping.  Error: '".$ex->getMessage();
                    LogError($strError);
                    if (isDebug() == true) {
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

        LogMessage('Loaded ' . count($this->companies_regex_to_filter). ' regexes to use for filtering companies from ' . getArrayValuesAsString($inputfiles));
    }
}
