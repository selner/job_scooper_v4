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
namespace JobScooper\Manager;

use JobScooper\Builders\JobSitePluginBuilder;
use JobScooper\StageProcessor\JobsAutoMarker;
use JobScooper\StageProcessor\NotifierJobAlerts;
use JobScooper\Builders\ConfigBuilder;
use JobScooper\Builders\SearchBuilder;
use JobScooper\Utils\DocOptions;


const JOBLIST_TYPE_UNFILTERED = "unfiltered";
const JOBLIST_TYPE_MARKED = "marked";
const JSON_FILENAME = "-alljobsites.json";

class StageManager
{
    protected $JobSiteName = "StageManager";
    protected $classConfig = null;

    function __construct()
    {
        try {
            $this->classConfig = new ConfigBuilder();

            $this->classConfig->initialize();

            if (!$GLOBALS['logger'])
                $GLOBALS['logger'] = new LoggingManager(C__APPNAME__);


        } catch (\Exception $ex) {
            LogLine("Unable to start Stage Manager: " . $ex->getMessage());
            throw $ex;
        }

    }

    function __destruct()
    {
        LogLine("Closing StageManager instance.", \C__DISPLAY_ITEM_START__);
    }


    public function runAll()
    {
        try {
	        $arrRunStages = getConfigurationSetting("command_line_args.stages");
            if (!empty($arrRunStages)) {

	            foreach ($arrRunStages as $stage) {
                    LogLine("StageManager starting stage " . $stage, \C__DISPLAY_SECTION_START__);
                    $stageFunc = "doStage" . $stage;
                    try {
                        call_user_func(array($this, $stageFunc));
                    } catch (\Exception $ex) {
                        throw new \Exception("Error:  failed to call method \$this->" . $stageFunc . "() for " . $stage . " from option --StageProcessor " . join(",", $arrRunStages) . ".  Error: " . $ex);
                    }
                    finally
                    {
                        LogLine("StageManager ended stage " . $stage, \C__DISPLAY_SECTION_END__);
                    }
                }
            } else {

                $this->doStage1();
                $this->doStage2();
                $this->doStage3();
                $this->doStage4();
            }
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        }
    }

    public function insertJobsFromJSON($path)
    {
        $data = loadJSON($path);
        $jobs = $data['jobslist'];

        foreach($jobs as $job) {
            $newJob = new \JobScooper\DataAccess\JobPosting();
            $newJob->fromArray($job);
            $newJob->save();
            LogLine("Saved " . $job->getJobPostingId() . " to database.");
        }

        LogLine("Stored " . countJobRecords($jobs) . " to database from file '".$path."''.");
    }


    public function doStage1()
    {

        LogLine("Stage 1: Downloading Latest Matching Jobs ", \C__DISPLAY_ITEM_RESULT__);

        //
        // let's start with the searches specified with the details in the the config.ini
        //
        $arrSearchesToRunBySite = getConfigurationSetting("user_search_site_runs");

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // OK, now we have our list of searches & sites we are going to actually run
        // Let's go get the jobs for those searches
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if ($arrSearchesToRunBySite != null)
        {
            LogLine(PHP_EOL . "**************  Starting Run of Searches for " . count($arrSearchesToRunBySite) . " Job Sites **************  " . PHP_EOL, \C__DISPLAY_NORMAL__);

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //
            // Run each plugin, set the searches to run and then download the jobs from that jobsite
            //
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            try {
                $jobsites = JobSitePluginBuilder::getIncludedJobSites();
                foreach($arrSearchesToRunBySite as $jobsiteKey => $searches)
                {
                    $plugin = null;
                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    //
                    // Add the user's searches to the plugin
                    //
                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    LogLine("Setting up " . count($searches) . " search(es) for ". $jobsiteKey . "...", \C__DISPLAY_SECTION_START__);
                    try
                    {
                        $site = $jobsites[$jobsiteKey];
                        $site->addSearches($searches);
                    }
                    catch (\Exception $classError)
                    {
                        handleException($classError, "Unable to add searches to {$jobsiteKey} plugin: %s", $raise = false);
                    }
                    finally
                    {
                        LogLine("Search(es) added ". $jobsiteKey . ".", \C__DISPLAY_SECTION_END__);
                    }

                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    //
                    // Download all the job listings for all the users searches for this plugin
                    //
                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    try
                    {
                        LogLine("Downloading updated jobs on " . count($searches) . " search(es) for ". $jobsiteKey . "...", \C__DISPLAY_SECTION_START__);
                        $site->getUpdatedJobsForAllSearches();
                    }
                    catch (\Exception $classError)
                    {
                        handleException($classError, $jobsiteKey . " failed to download job postings: %s", $raise = false);
                    }
                    finally
                    {
                        LogLine("Job downloads have ended for ". $jobsiteKey . ".", \C__DISPLAY_SECTION_END__);
                    }
                }
            } catch (\Exception $ex) {
                handleException($ex, null, false);
            }

        } else {
            LogLine("No searches have been set to be run.", C__DISPLAY_WARNING__);
        }

    }


    public function doStage2()
    {
        try {
            LogLine("Stage 2:  Tokenizing Job Titles... ", \C__DISPLAY_SECTION_START__);
            $arrJobsList = getAllMatchesForUserNotification();

            if(is_null($arrJobsList) || count($arrJobsList) <= 0) {
                LogLine("No new jobs found to tokenize", C__DISPLAY_WARNING__);
            }
            else
            {
                $jfilefullpath = generateOutputFileName("alljobmatches", "json");
                $outjfilefullpath = generateOutputFileName("alljobmatches_tokenized", "json");
                writeJobRecordsToJson($jfilefullpath, $arrJobsList);

                LogLine(PHP_EOL . "Processing " . $jfilefullpath, \C__DISPLAY_NORMAL__);

                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                //
                // Tokenize the job listings found in the stage 2 prefix on S3
                //
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                LogLine(PHP_EOL . "    ~~~~~~ Tokenizing job titles ~~~~~~~" . PHP_EOL, \C__DISPLAY_NORMAL__);
                $PYTHONPATH = realpath(__ROOT__. "/python/pyJobNormalizer/normalizeJobListingFile.py");

                $cmd = "python " . $PYTHONPATH . " --infile " . escapeshellarg($jfilefullpath) . " --outfile " . escapeshellarg($outjfilefullpath) ." --column Title --index KeySiteAndPostId";
                LogLine(PHP_EOL . "    ~~~~~~ Running command: " . $cmd ."  ~~~~~~~" . PHP_EOL, \C__DISPLAY_NORMAL__);

                doExec($cmd);

                updateJobRecordsFromJson($outjfilefullpath);
            }

        } catch (\Exception $ex) {
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
        } catch (\Exception $ex) {
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

            $notifier = new NotifierJobAlerts();
            $notifier->processNotifications();
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        }
    }
}