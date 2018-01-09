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
use JobScooper\StageProcessor\NotifierDevAlerts;
use JobScooper\StageProcessor\NotifierJobAlerts;
use JobScooper\Builders\ConfigBuilder;


const JOBLIST_TYPE_UNFILTERED = "unfiltered";
const JOBLIST_TYPE_MARKED = "marked";
const JSON_FILENAME = "-alljobsites.json";

/**
 * Class StageManager
 * @package JobScooper\Manager
 */
class StageManager
{
    protected $JobSiteName = "StageManager";
    protected $classConfig = null;

	/**
	 * @throws \Exception
	 */
	public function runAll()
    {
        try {
	        $this->classConfig = new ConfigBuilder();

	        $this->classConfig->initialize();


	        $arrRunStages = getConfigurationSetting("command_line_args.stages");
            if (!empty($arrRunStages)) {

	            foreach ($arrRunStages as $stage) {
                    $stageFunc = "doStage" . $stage;
                    try {
                        call_user_func(array($this, $stageFunc));
                    } catch (\Exception $ex) {
                        throw new \Exception("Error:  failed to call method \$this->" . $stageFunc . "() for " . $stage . " from option --StageProcessor " . join(",", $arrRunStages) . ".  Error: " . $ex);
                    }
                }
            } else {

                $this->doStage1();
                $this->doStage2();
                $this->doStage3();
            }
        } catch (\Exception $ex) {
	        handleException($ex, null, true);
		}
        finally
        {
        	try
	        {
	        	$this->doFinalStage();
	        } catch (\Exception $ex) {
			    handleException($ex, null, true);
		    }
        }
    }

	/**
	 * @throws \Exception
	 */
	public function doStage1()
    {

        startLogSection("Stage 1: Downloading Latest Matching Jobs ");

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
            LogMessage(PHP_EOL . "**************  Starting Run of Searches for " . count($arrSearchesToRunBySite) . " Job Sites **************  " . PHP_EOL);

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
                    startLogSection("Setting up " . count($searches) . " search(es) for ". $jobsiteKey . "...");
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
                        endLogSection(" added ". $jobsiteKey . " searches.");
                    }

                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    //
                    // Download all the job listings for all the users searches for this plugin
                    //
                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    try
                    {
                        startLogSection("Downloading updated jobs on " . count($searches) . " search(es) for ". $jobsiteKey . "...");
                        $site->downloadLatestJobsForAllSearches();
                    }
                    catch (\Exception $classError)
                    {
                        handleException($classError, $jobsiteKey . " failed to download job postings: %s", $raise = false);
                    }
                    finally
                    {
                        endLogSection("Job downloads have ended for ". $jobsiteKey . ".");
                    }
                }
            } catch (\Exception $ex) {
                handleException($ex, null, false);
            }

        } else {
            LogWarning("No searches have been set to be run.");
        }

        endLogSection("Stage 1");
    }

	/**
	 * @throws \Exception
	 */
	public function doStage2()
    {
        
        try {
	        startLogSection("Stage 2:  Auto-marking all user job matches...");
            $marker = new JobsAutoMarker();
            $marker->markJobs();
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        }
        finally
        {
            endLogSection("End of stage 2 (auto-marking)");
        }
    }

	/**
	 * @throws \Exception
	 */
	public function doStage3()
    {
        try {
            startLogSection("Stage 3: Notifying User");
            $notifier = new NotifierJobAlerts();
            $notifier->processNotifications();
        } catch (\Exception $ex) {
	        handleException($ex, null, true);
		}
        finally
        {
        	endLogSection("End of stage 3 (notifying user)");
        }
    }

	/**
	 * @throws \Exception
	 */
	public function doFinalStage()
    {
	    try {
		    startLogSection("Final Stage: Developer Alerts + Cleanup");
		    $devNotifier = new NotifierDevAlerts();
		    $devNotifier->processPluginErrors();
	    } catch (\Exception $ex) {
		    handleException($ex, null, true);
	    }
	    finally
	    {
		    endLogSection("End of final stage (dev alerts + cleanup)");
	    }

    }
}