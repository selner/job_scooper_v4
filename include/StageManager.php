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

class StageManager extends ClassJobsSiteCommon
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
                $GLOBALS['logger'] = new \Scooper\ScooperLogger($GLOBALS['USERDATA']['directories']['debug']);

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
        LogLine("Closing " . $this->siteName . " instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__);



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
                    LogLine("StageManager starting stage " . $stage, \Scooper\C__DISPLAY_SECTION_START__);
                    $stageFunc = "doStage" . $stage;
                    try {
                        call_user_func(array($this, $stageFunc));
                    } catch (Exception $ex) {
                        throw new Exception("Error:  failed to call method \$this->" . $stageFunc . "() for " . $stage . " from option --stages " . join(",", $arrRunStages) . ".  Error: " . $ex);
                    }
                    finally
                    {
                        LogLine("StageManager ended stage " . $stage, \Scooper\C__DISPLAY_ITEM_RESULT__);
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
        $data = readJobsListDataFromLocalFile($path);
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

        LogLine("Stage 1: Downloading Latest Matching Jobs ", \Scooper\C__DISPLAY_ITEM_RESULT__);
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
                            LogLine($curSearch['site_name'] . " excluded, so dropping its searches from the run.", \Scooper\C__DISPLAY_ITEM_START__);
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
                    LogLine(PHP_EOL . "**************  Starting Run of " . count($arrSearchesToRun) . " Searches  **************  " . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);


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
            LogLine("Stage 2:  Tokenizing Job Titles... ", \Scooper\C__DISPLAY_SECTION_START__);
            $arrJobsList = getUserJobMatchesForAppRun();
            if(count($arrJobsList) > 0)
            {
                $injfile = "alljobmatches.json";
                $outjfile = "alljobmatches_tokenized.json";
                $jfilefullpath = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['debug'], $injfile));

                writeJobRecordsToJson($jfilefullpath, $arrJobsList);

                LogLine(PHP_EOL . "Processing " . $jfilefullpath, \Scooper\C__DISPLAY_NORMAL__);
                $outjfilefullpath = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['debug'], $outjfile));

                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                //
                // Tokenize the job listings found in the stage 2 prefix on S3
                //
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                LogLine(PHP_EOL . "    ~~~~~~ Tokenizing job titles ~~~~~~~" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
                $PYTHONPATH = realpath(__DIR__ . "/../python/pyJobNormalizer/normalizeJobListingFile.py");

                $cmd = "python " . $PYTHONPATH . " --infile " . escapeshellarg($jfilefullpath) . " --outfile " . escapeshellarg($outjfilefullpath) ." --column Title --index KeySiteAndPostID";
                LogLine(PHP_EOL . "    ~~~~~~ Running command: " . $cmd ."  ~~~~~~~" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);

                doExec($cmd);

                updateJobRecordsFromJson($outjfilefullpath);
            }

        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
        finally
        {
            LogLine("End of stage 2 (tokenizing titles).", \Scooper\C__DISPLAY_NORMAL__);
        }

    }

    public function doStage3()
    {
        
        try {
            LogLine("Stage 3:  Auto-marking all user job matches...", \Scooper\C__DISPLAY_SECTION_START__);
            $marker = new JobsAutoMarker();
            $marker->markJobs();
        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
        finally
        {
            LogLine("End of stage 3 (auto-marking).", \Scooper\C__DISPLAY_NORMAL__);
        }
    }

    public function doStage4()
    {
        try {

            LogLine("Stage 4: Notifying User", \Scooper\C__DISPLAY_SECTION_START__);

//            if ((countJobRecords($arrMatchedJobs)) == 0) {
//                LogLine("No jobs were loaded for notification. Skipping Stage 4.", \Scooper\C__DISPLAY_WARNING__);
//                return;
//            }

            $notifier = new ClassJobsNotifier($this->pathAllMatchedJobs, $this->pathAllExcludedJobs, $GLOBALS['USERDATA']['directories']['results']);
            $notifier->processNotifications();
        } catch (Exception $ex) {
            handleException($ex, null, true);
        }
    }
}