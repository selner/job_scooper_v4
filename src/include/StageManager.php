<?php
/**
 * Copyright 2014-17 Bryan Selner
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

if (!strlen(__ROOT__) > 0) {
    define('__ROOT__', dirname(dirname(__FILE__)));
}
require_once(__ROOT__ . '/include/SitePlugins.php');
require_once(__ROOT__ . '/include/ClassMultiSiteSearch.php');
require_once(__ROOT__ . '/include/ErrorManager.php');
require_once(__ROOT__ . '/include/ClassJobsNotifier.php');
require_once(__ROOT__ . '/include/JobsAutoMarker.php');

const JOBLIST_TYPE_UNFILTERED = "unfiltered";
const JOBLIST_TYPE_MARKED = "marked";
const JSON_FILENAME = "-alljobsites.json";

class StageManager extends ClassJobsSiteCommon
{
    protected $siteName = "StageManager";
    protected $classConfig = null;
    protected $logger = null;

    function __construct()
    {
        try {
            $this->classConfig = new ClassConfig();
            $this->classConfig->initialize();
            $logger = $this->classConfig->getLogger();

            if ($logger)
                $this->logger = $logger;
            elseif ($GLOBALS['logger'])
                $this->logger = $GLOBALS['logger'];
            else
                $this->logger = new \Scooper\ScooperLogger($GLOBALS['USERDATA']['directories']['debug']);

            parent::__construct(null);

        } catch (Exception $ex) {
//            handleException($ex, null, true);
            print $ex;
        }

    }

    function __destruct()
    {
        if (isset($this->logger)) $this->logger->logLine("Closing " . $this->siteName . " instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__);



    }

    function _cleanUpBeforeExiting()
    {
        $err = new ErrorManager();
        $err->processAndAlertErrors();


    }


    public function runAll()
    {
        try {
            $arrRunStages = explode(",", \Scooper\get_PharseOptionValue("stages"));
            if (is_array($arrRunStages) && count($arrRunStages) >= 1 && strlen($arrRunStages[0]) > 0) {
                foreach ($arrRunStages as $stage) {
                    if (isset($this->logger)) $this->logger->logLine("StageManager starting stage " . $stage, \Scooper\C__DISPLAY_SECTION_START__);
                    $stageFunc = "doStage" . $stage;
                    try {
                        call_user_func(array($this, $stageFunc));
                    } catch (Exception $ex) {
                        throw new Exception("Error:  failed to call method \$this->" . $stageFunc . "() for " . $stage . " from option --stages " . join(",", $arrRunStages) . ".  Error: " . $ex);
                    }
                    finally
                    {
                        if (isset($this->logger)) $this->logger->logLine("StageManager ended stage " . $stage, \Scooper\C__DISPLAY_ITEM_RESULT__);
                    }
                }
            } else {
                $this->doStage1();
                $this->doStage2();
                $this->doStage3();
                $this->doStage4();
            }
        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
        finally
        {
            $this->_cleanUpBeforeExiting();

        }
    }

    public function doStage1()
    {

        if (isset($this->logger)) $this->logger->logLine("Stage 1: Downloading Latest Matching Jobs ", \Scooper\C__DISPLAY_ITEM_RESULT__);
        try {

            //
            // let's start with the searches specified with the details in the the config.ini
            //
            $arrSearchesToRun = $this->classConfig->getSearchConfiguration('searches');

            if (isset($arrSearchesToRun)) {
                if (count($arrSearchesToRun) > 0) {

                    //
                    // Remove any sites that were excluded in this run from the searches list
                    //
                    foreach (array_keys($arrSearchesToRun) as $z) {
                        $curSearch = $arrSearchesToRun[$z];

                        $strIncludeKey = 'include_' . $curSearch['site_name'];

                        $valInclude = \Scooper\get_PharseOptionValue($strIncludeKey);

                        if (!isset($valInclude) || $valInclude == 0) {
                            $this->logger->logLine($curSearch['site_name'] . " excluded, so dropping its searches from the run.", \Scooper\C__DISPLAY_ITEM_START__);
                            unset($arrSearchesToRun[$z]);
                        }
                    }
                }

                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                //
                // OK, now we have our list of searches & sites we are going to actually run
                // Let's go get the jobs for those searches
                //
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                if ($arrSearchesToRun != null) {
                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    //
                    // Download all the job listings for all the users searches
                    //
                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    $this->logger->logLine(PHP_EOL . "**************  Starting Run of " . count($arrSearchesToRun) . " Searches  **************  " . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);


                    //
                    // the Multisite class handles the heavy lifting for us by executing all
                    // the searches in the list and returning us the combined set of new jobs
                    // (with the exception of Amazon for historical reasons)
                    //
                    $classMulti = new ClassMultiSiteSearch($GLOBALS['USERDATA']['directories']['listings-raw']);
                    $classMulti->addMultipleSearches($arrSearchesToRun, null);
                    $arrUpdatedJobs = $classMulti->updateJobsForAllPlugins();

                } else {
                    throw new ErrorException("No searches have been set to be run.");
                }
            }
        } catch (Exception $ex) {
            handleException($ex, null, true);
        }

    }

    public function doStage2()
    {
        try {
            if (isset($this->logger)) $this->logger->logLine("Stage 2:  Tokenizing Jobs ", \Scooper\C__DISPLAY_SECTION_START__);
            $marker = new JobsAutoMarker();

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //
            // Load the jobs list we need to process in this stage
            //
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            foreach($GLOBALS['USERDATA']['configuration_settings']['included_sites'] as $site)
            {
                $arrSiteJobs = $this->mergeAllJobsJsonInDir($GLOBALS['USERDATA']['directories']['listings-raw'], $site);
                if(countAssociativeArrayValues($arrSiteJobs) > 0)
                    writeJobsListDataToLocalJSONFile($site, $arrSiteJobs, JOBLIST_TYPE_UNFILTERED, $stageNumber = null, $dirKey = "listings-rawbysite");
                $arrSiteJobs = null;
            }
            $filelist = $this->getAllIncludedFilesForDir($GLOBALS['USERDATA']['directories']['listings-rawbysite']);
            $jsonfiles = array_filter($filelist, function ($var) {
                if(strtolower(pathinfo($var, PATHINFO_EXTENSION)) == "json" && substr($var, 0, strlen("tokenized_")) != "tokenized_")
                    return true;
                return false;
                });

            foreach($jsonfiles as $jfile)
            {
                $this->logger->logLine(PHP_EOL . "Processing " . $jfile , \Scooper\C__DISPLAY_NORMAL__);
                $outjfile = "tokenized_" .$jfile;
                $jfilefullpath = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['listings-rawbysite'], $jfile));
                $outjfilefullpath = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['listings-tokenized'], $outjfile));

                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                //
                // Tokenize the job listings found in the stage 2 prefix on S3
                //
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                $this->logger->logLine(PHP_EOL . "    ~~~~~~ Tokenizing job titles ~~~~~~~" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
                $PYTHONPATH = realpath(__DIR__ . "/../python/pyJobNormalizer/normalizeJobListingFile.py");

                $cmd = "python " . $PYTHONPATH . " --infile " . escapeshellarg($jfilefullpath) . " --outfile " . escapeshellarg($outjfilefullpath) ." --column job_title --index key_jobsite_siteid";
                $this->logger->logLine(PHP_EOL . "    ~~~~~~ Running command: " . $cmd ."  ~~~~~~~" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);

                doExec($cmd);

                $arrJobs = readJobsListFromLocalJsonFile(pathinfo($outjfilefullpath, PATHINFO_BASENAME), $returnFailedSearches = true, $dirKey = "listings-tokenized");
                if(countAssociativeArrayValues($arrJobs) > 0)
                {
                    $this->logger->logLine(PHP_EOL . "    ~~~~~~ Auto-marking jobs based on user settings ~~~~~~~" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
                    $marker->setJobsList($arrJobs);
                    $marker->markJobsList();
                    $arrMarkedJobs = $marker->getMarkedJobs();


                    $arrJobsInterestedJobs = array_filter($arrMarkedJobs, "isMarked_InterestedOrBlank");
                    $arrJobsNotInterested = array_filter($arrMarkedJobs, "isMarked_NotInterested");

                    if(countAssociativeArrayValues($arrJobsInterestedJobs) > 0)
                        writeJobsListDataToLocalJSONFile($jfile, $arrJobsInterestedJobs, JOBLIST_TYPE_MARKED,  $dirKey = "listings-userinterested");

                    if(countAssociativeArrayValues($arrJobsNotInterested) > 0)
                        writeJobsListDataToLocalJSONFile($jfile, $arrJobsNotInterested, JOBLIST_TYPE_MARKED, $dirKey = "listings-usernotinterested");
                }
            }
        } catch (Exception $ex) {
            handleException($ex, null, true);
        }

    }

    private function getAllIncludedFilesForDir($directory, $matchFileName=null)
    {
        $filelist = array_diff(scandir($directory), array(".", ".."));
        if(!is_null($matchFileName))
        {
            $filelist = array_filter($filelist, function ($var) use ($matchFileName) {
                if(stristr($var, $matchFileName) != false)
                    return true;
                return false;
            });
        }

        $includedfiles = array_filter($filelist, function ($var) {
            $matches = array();
            $res = substr_count_multi($var, $GLOBALS['USERDATA']['configuration_settings']['included_sites'], $matches);
            if (count($matches) > 0)
            {
                return true;
            }

            return false;
        });
        return $includedfiles;
    }

    private function mergeAllJobsJsonInDir($directory, $matchFileName = null)
    {
        $filelist = $this->getAllIncludedFilesForDir($directory, $matchFileName);


        $jsMerged = array();
        foreach($filelist as $file)
        {
            if(strtolower(pathinfo($file, PATHINFO_EXTENSION)) == "json") {
                $filepath = join(DIRECTORY_SEPARATOR, array($directory, $file));
                $jsobj = loadJSON($filepath);
                if (is_array($jsobj['jobslist']) && countAssociativeArrayValues($jsobj['jobslist']) > 0)
                {
                    foreach(array_keys($jsobj['jobslist']) as $key)
                    {
                        if(!array_key_exists($key, $jsMerged))
                            $jsMerged[$key] = $jsobj['jobslist'][$key];
                    }
               }
            }
        }

        return $jsMerged;
    }

    public function doStage3()
    {
        
        try {

            $this->logger->logLine("Stage 3:  Merging job site results into single set", \Scooper\C__DISPLAY_SECTION_START__);
            $resultsJSON = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['results'], "all-results.json"));
            $jobsinterested = $this->mergeAllJobsJsonInDir($GLOBALS['USERDATA']['directories']['listings-userinterested']);
            $jobsnotinterested= $this->mergeAllJobsJsonInDir($GLOBALS['USERDATA']['directories']['listings-usernotinterested']);

            $arrMarkedJobs = array_merge_recursive($jobsnotinterested, $jobsinterested);

            $data = array('key' => null, 'listtype' => JOBLIST_TYPE_MARKED, 'jobs_count' => countJobRecords($arrMarkedJobs), 'jobslist' => $arrMarkedJobs, 'search' => null);
            writeJSON($data, $resultsJSON);

            $this->logger->logLine(PHP_EOL . "**************  Updating jobs list for known filters ***************" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);

        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
    }

    public function doStage4()
    {
        try {

            $this->logger->logLine("Stage 4: Notifying User", \Scooper\C__DISPLAY_SECTION_START__);

            $jobData = readJobsListDataFromLocalFile(join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['results'], "all-results.json")));
            $arrMarkedJobs = $jobData['jobslist'];

            if ((countJobRecords($arrMarkedJobs)) == 0) {
                $this->logger->logLine("No jobs were loaded for notification. Skipping Stage 4.", \Scooper\C__DISPLAY_WARNING__);
                return;
            }

            $notifier = new ClassJobsNotifier($arrMarkedJobs, $GLOBALS['USERDATA']['directories']['results']);
            $notifier->processNotifications();
        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
    }
}