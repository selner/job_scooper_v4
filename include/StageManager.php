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
require_once dirname(dirname(__FILE__))."/bootstrap.php";

const JOBLIST_TYPE_UNFILTERED = "unfiltered";
const JOBLIST_TYPE_MARKED = "marked";
const JSON_FILENAME = "-alljobsites.json";

class StageManager
{
    protected $siteName = "StageManager";
    protected $classConfig = null;
    protected $pathAllMatchedJobs = null;
    protected $pathAllExcludedJobs = null;
    protected $pathAllJobs = null;

    function __construct()
    {
        try {
            $this->classConfig = new ClassConfig();
            $this->classConfig->initialize();

            if (!$GLOBALS['logger'])
                $GLOBALS['logger'] = new ScooperLogger($GLOBALS['USERDATA']['directories']['debug']);

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
        LogLine("Closing " . $this->siteName . " instance of class " . get_class($this), \C__DISPLAY_ITEM_START__);



    }

    function _cleanUpBeforeExiting()
    {
        $err = new ErrorManager();
        $err->processAndAlertErrors();


    }


    public function runAll()
    {
        try {
            $arrRunStages = explode(",", get_PharseOptionValue("stages"));
            if (is_array($arrRunStages) && count($arrRunStages) >= 1 && strlen($arrRunStages[0]) > 0) {
                foreach ($arrRunStages as $stage) {
                    LogLine("StageManager starting stage " . $stage, \C__DISPLAY_SECTION_START__);
                    $stageFunc = "doStage" . $stage;
                    try {
                        call_user_func(array($this, $stageFunc));
                    } catch (Exception $ex) {
                        throw new Exception("Error:  failed to call method \$this->" . $stageFunc . "() for " . $stage . " from option --stages " . join(",", $arrRunStages) . ".  Error: " . $ex);
                    }
                    finally
                    {
                        LogLine("StageManager ended stage " . $stage, \C__DISPLAY_ITEM_RESULT__);
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

    public function insertJobsFromJSON($path)
    {
        $data = loadJSON($path);
        $jobs = $data['jobslist'];

        foreach($jobs as $job) {
            $newJob = new \JobScooper\JobPosting();
            $newJob->fromArray($job);
            $newJob->save();
            LogLine("Saved " . $job['job_post_id'] . " to database.");
        }

        LogLine("Stored " . countJobRecords($jobs) . " to database from file '".$path."''.");
    }


    public function doStage1()
    {

        LogLine("Stage 1: Downloading Latest Matching Jobs ", \C__DISPLAY_ITEM_RESULT__);

        //
        // let's start with the searches specified with the details in the the config.ini
        //
        $arrSearchesToRunBySite = $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'];


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // OK, now we have our list of searches & sites we are going to actually run
        // Let's go get the jobs for those searches
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if ($arrSearchesToRunBySite != null) {
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //
            // Download all the job listings for all the users searches
            //
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            LogLine(PHP_EOL . "**************  Starting Run of Searches for " . count($arrSearchesToRunBySite) . " Job Sites **************  " . PHP_EOL, \C__DISPLAY_NORMAL__);

            try {

                //
                // the Multisite class handles the heavy lifting for us by executing all
                // the searches in the list and returning us the combined set of new jobs
                // (with the exception of Amazon for historical reasons)
                //
                $classMulti = new ClassMultiSiteSearch();
                $classMulti->updateJobsForAllJobSites($arrSearchesToRunBySite);
            } catch (Exception $ex) {
                handleException($ex, null, false);
            }

        } else {
            throw new ErrorException("No searches have been set to be run.");
        }

    }


    public function doStage2()
    {
        try {
            LogLine("Stage 2:  Tokenizing Job Titles... ", \C__DISPLAY_SECTION_START__);
            $arrJobsList = getAllUserMatchesNotNotified();
            if(count($arrJobsList) > 0)
            {
                $injfile = "alljobmatches.json";
                $outjfile = "alljobmatches_tokenized.json";
                $jfilefullpath = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['debug'], $injfile));

                writeJobRecordsToJson($jfilefullpath, $arrJobsList);

                LogLine(PHP_EOL . "Processing " . $jfilefullpath, \C__DISPLAY_NORMAL__);
                $outjfilefullpath = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['debug'], $outjfile));

                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                //
                // Tokenize the job listings found in the stage 2 prefix on S3
                //
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                LogLine(PHP_EOL . "    ~~~~~~ Tokenizing job titles ~~~~~~~" . PHP_EOL, \C__DISPLAY_NORMAL__);
                $PYTHONPATH = realpath(__ROOT__. "/python/pyJobNormalizer/normalizeJobListingFile.py");

                $cmd = "python " . $PYTHONPATH . " --infile " . escapeshellarg($jfilefullpath) . " --outfile " . escapeshellarg($outjfilefullpath) ." --column Title --index KeySiteAndPostID";
                LogLine(PHP_EOL . "    ~~~~~~ Running command: " . $cmd ."  ~~~~~~~" . PHP_EOL, \C__DISPLAY_NORMAL__);

                doExec($cmd);

                updateJobRecordsFromJson($outjfilefullpath);
            }

        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
        finally
        {
            LogLine("End of stage 2 (tokenizing titles).", \C__DISPLAY_NORMAL__);
        }

    }

    public function doStage3()
    {
        
        try {
            LogLine("Stage 3:  Auto-marking all user job matches...", \C__DISPLAY_SECTION_START__);
            $marker = new JobsAutoMarker();
            $marker->markJobs();
        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
        finally
        {
            LogLine("End of stage 3 (auto-marking).", \C__DISPLAY_NORMAL__);
        }
    }

    public function doStage4()
    {
        try {

            LogLine("Stage 4: Notifying User", \C__DISPLAY_SECTION_START__);

//            if ((countJobRecords($arrMatchedJobs)) == 0) {
//                LogLine("No jobs were loaded for notification. Skipping Stage 4.", \C__DISPLAY_WARNING__);
//                return;
//            }

            $notifier = new ClassJobsNotifier();
            $notifier->processNotifications();
        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
    }
}