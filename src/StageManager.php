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
namespace Jobscooper;

//

const JOBLIST_TYPE_UNFILTERED = "unfiltered";
const JOBLIST_TYPE_MARKED = "marked";
const JSON_FILENAME = "-alljobsites.json";

class StageManager extends \Jobscooper\BasePlugin\JobsSiteCommon
{
    protected $siteName = "StageManager";
    protected $classConfig = null;
    protected $logger = null;
    protected $pathAllMatchedJobs = null;
    protected $pathAllExcludedJobs = null;
    protected $pathAllJobs = null;

    function __construct()
    {
        try {
            $this->classConfig = ClassConfig::getInstance();
            $logger = $this->classConfig->getLogger();

            if ($logger)
                $this->logger = $logger;
            elseif ($GLOBALS['logger'])
                $this->logger = $GLOBALS['logger'];
            else
                $this->logger = new \Scooper\ScooperLogger($GLOBALS['USERDATA']['directories']['debug']);

            parent::__construct(null);

            $this->pathAllExcludedJobs = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['results'], "all-excluded-jobs"));
            $this->pathAllMatchedJobs = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['results'], "all-job-matches"));
            $this->pathAllJobs = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['results'], "all-jobs"));


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
            handleException($ex, null, false);
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
                    writeJobsListDataToLocalJSONFile($site, $arrSiteJobs, JOBLIST_TYPE_UNFILTERED, $dirKey = "listings-rawbysite");
                $arrSiteJobs = null;
            }

            $filelist = $this->getAllNotExcludedFilesForDir($GLOBALS['USERDATA']['directories']['listings-rawbysite-allusers']);
            foreach($filelist as $allfile)
            {
                copy(
                    join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['listings-rawbysite-allusers'], $allfile)),
                    join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['listings-rawbysite'], $allfile))
                );
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

                $cmd = getPythonPath() . " " . $PYTHONPATH . " --infile " . escapeshellarg($jfilefullpath) . " --outfile " . escapeshellarg($outjfilefullpath) ." --column job_title --index key_jobsite_siteid";
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
        return $this->getAllNotExcludedFilesForDir($directory, $matchFileName);
    }

    private function getAllNotExcludedFilesForDir($directory, $matchFileName=null)
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

        $resultfiles = array_filter($filelist, function ($var) {
            $matches = array();
            $res = substr_count_multi($var, $GLOBALS['USERDATA']['configuration_settings']['excluded_sites'], $matches);
            if (count($matches) > 0)
            {
                return false;
            }

            return true;
        });

        return $resultfiles;
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

    private function splitJobListByMarkedOrNot($jobsList)
    {
        $ret = array('interested' => array(), 'not_interested' => array());

        if (countJobRecords($jobsList) > 0) {
            $this->logger->logLine(PHP_EOL . "    ~~~~~~ Auto-marking jobs based on user settings ~~~~~~~" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
            $marker = new JobsAutoMarker();
            $marker->setJobsList($jobsList);
            $marker->markJobsList();
            $arrAllJobsMarked = $marker->getMarkedJobs();

            $ret['interested'] = array_filter($arrAllJobsMarked, "isMarked_InterestedOrBlank");
            $ret['not_interested'] = array_filter($arrAllJobsMarked, "isMarked_NotInterested");
        }

        return $ret;
    }

    public function doStage3()
    {
        
        try {
            $this->logger->logLine("Stage 3:  Aggregating results from all jobs sites ", \Scooper\C__DISPLAY_SECTION_START__);
            $this->logger->logLine("Merging matched job site results into single file: " . $this->pathAllMatchedJobs."[.json and .csv]", \Scooper\C__DISPLAY_NORMAL__);
            $jobsinterested = $this->mergeAllJobsJsonInDir($GLOBALS['USERDATA']['directories']['listings-userinterested']);
            $arrFullJobList = $this->mergeAllJobsJsonInDir($GLOBALS['USERDATA']['directories']['listings-usernotinterested']);
            if (countJobRecords($arrFullJobList) > 0)
            {
                foreach(array_keys($jobsinterested) as $key)
                {
                    if(!array_key_exists($key, $arrFullJobList))
                        $arrFullJobList[$key] = $jobsinterested[$key];
                }
            }
            else {
                $arrFullJobList = $jobsinterested;
            }

            $marker = new JobsAutoMarker();
            $marker->setJobsList($arrFullJobList);
            $marker->markJobsList();
            $arrFullJobList = $marker->getMarkedJobs();

//            foreach(array(array($this->pathAllMatchedJobs, $jobsinterested), array($this->pathAllExcludedJobs, $jobsnotinterested)) as $jobset) {
//                $data = array('key' => null, 'listtype' => JOBLIST_TYPE_MARKED, 'jobs_count' => countJobRecords($jobset[1]), 'jobslist' => $jobset[1], 'search' => null);
//                writeJSON($data, $jobset[0].".json");
//                $PYTHONPATH = realpath(__DIR__ . "/../python/pyJobNormalizer/jobsjson_to_csv.py");
//                $cmd = getPythonPath() . " " . $PYTHONPATH . " -i " . escapeshellarg($jobset[0].".json") . " -o " . escapeshellarg($jobset[0].".csv");
//                $this->logger->logLine(PHP_EOL . "    ~~~~~~ Running command: " . $cmd . "  ~~~~~~~" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
//                doExec($cmd);
//            }
//
            $data = array('key' => null, 'listtype' => JOBLIST_TYPE_MARKED, 'jobs_count' => countJobRecords($arrFullJobList), 'jobslist' => $arrFullJobList, 'search' => null);
            writeJSON($data, $this->pathAllJobs.".json");
            $PYTHONPATH = realpath(__DIR__ . "/../python/pyJobNormalizer/jobsjson_to_csv.py");
            $cmd = getPythonPath() . " " . $PYTHONPATH . " -i " . escapeshellarg($this->pathAllJobs.".json") . " -o " . escapeshellarg($this->pathAllJobs.".csv");
            $this->logger->logLine(PHP_EOL . "    ~~~~~~ Running command: " . $cmd . "  ~~~~~~~" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
            doExec($cmd);


            $arrJobsInterestedJobs = array_filter($arrFullJobList, "isMarked_InterestedOrBlank");
            if(countJobRecords($arrJobsInterestedJobs) > 0)
                writeJobsListDataToFile($this->pathAllMatchedJobs.".json", $fileKey = null, $arrJobsInterestedJobs, JOBLIST_TYPE_MARKED,  $dirKey = "listings-userinterested");

            $arrJobsNotInterestedJobs = array_filter($arrFullJobList, "isMarked_NotInterested");
            if(countJobRecords($arrJobsNotInterestedJobs) > 0)
                writeJobsListDataToFile($this->pathAllExcludedJobs.".json", $fileKey = null, $arrJobsNotInterestedJobs, JOBLIST_TYPE_MARKED,  $dirKey = "listings-usernotinterested");

//            $this->logger->logLine("Merging excluded job site results into single file: " . $this->pathAllExcludedJobs, \Scooper\C__DISPLAY_NORMAL__);
//            $data = array('key' => null, 'listtype' => JOBLIST_TYPE_MARKED, 'jobs_count' => countJobRecords($jobsnotinterested ), 'jobslist' => $jobsnotinterested , 'search' => null);
//            writeJSON($data, $this->pathAllExcludedJobs);
//
//            $PYTHONPATH = realpath(__DIR__ . "/../python/pyJobNormalizer/jobsjson_to_csv.py");
//            $csvPath = join(DIRECTORY_SEPARATOR, array(dirname($this->pathAllMatchedJobs), (basename($this->pathAllMatchedJobs) . ".csv")))
//            $cmd = getPythonPath() . " " . $PYTHONPATH . " --infile " . escapeshellarg($this->pathAllMatchedJobs) . " --outfile " . escapeshellarg($csvPath) ." --column job_title --index key_jobsite_siteid";
//            $this->logger->logLine(PHP_EOL . "    ~~~~~~ Running command: " . $cmd ."  ~~~~~~~" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
//            doExec($cmd);
//
//
            $this->logger->logLine("End of stage 3.", \Scooper\C__DISPLAY_NORMAL__);


        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
    }

    public function doStage4()
    {
        try {

            $this->logger->logLine("Stage 4: Notifying User", \Scooper\C__DISPLAY_SECTION_START__);

//            if ((countJobRecords($arrMatchedJobs)) == 0) {
//                $this->logger->logLine("No jobs were loaded for notification. Skipping Stage 4.", \Scooper\C__DISPLAY_WARNING__);
//                return;
//            }

            $notifier = new ClassJobsNotifier($this->pathAllMatchedJobs, $this->pathAllExcludedJobs, $GLOBALS['USERDATA']['directories']['results']);
            $notifier->processNotifications();
        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
    }
}