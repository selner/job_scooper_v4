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
namespace JobScooper\Manager;

use JobScooper\DataAccess\JobSiteManager;
use JobScooper\DataAccess\User;
use JobScooper\StageProcessor\JobsAutoMarker;
use JobScooper\StageProcessor\NotifierDevAlerts;
use JobScooper\StageProcessor\NotifierJobAlerts;
use JobScooper\Utils\ConfigInitializer;
use JobScooper\Utils\DBRecordRemover;
use JobScooper\Utils\Settings;

const JOBLIST_TYPE_UNFILTERED = "unfiltered";
const JOBLIST_TYPE_MARKED = "marked";
const JSON_FILENAME = "-alljobsites.json";

/**
 * Class StageManager
 * @package JobScooper\Manager
 */
class StageManager
{
	public function __construct($config = null)
	{
		if(null !== $config) {
			$this->classConfig = $config;
		}
	}

    protected $JobSiteName = "StageManager";
    private $classConfig = null;

    /**
     * @var \DateTime
     */
    protected $runStartTime = null;

    /**
     * @throws \Exception
     */
    private function _initConfig()
    {
        if (empty($this->classConfig)) {
            $this->classConfig = new ConfigInitializer();
            $this->classConfig->initialize();
        }

        $runStartTime = getConfigurationSetting('app_run_start_datetime');
        if (empty($runStartTime)) {
	        Settings::setValue('app_run_start_datetime', new \DateTime());
        }
    }

    /**
     * @throws \Exception
     */
    public function runAll()
    {
        try {
            $this->_initConfig();

            /*
             * Run specific stages requested via command-line
             */
            $usersForRun = getConfigurationSetting("users_for_run");
            if(is_empty_value($usersForRun)) {
            	throw new \InvalidArgumentException("No user information was set to be run.  Aborting.");
            }

            $cmds = getConfigurationSetting("command_line_args");
            if (array_key_exists("recap", $cmds) && !empty($cmds['recap'])) {
                $this->doWeeklyRecaps($usersForRun);
            } elseif (array_key_exists("delete", $cmds) && !empty($cmds['delete'])) {
                $this->removeUserData($usersForRun);
            } elseif (array_key_exists("stages", $cmds) && !empty($cmds['stages'])) {
                $arrRunStages = getConfigurationSetting("command_line_args.stages");

                foreach ($usersForRun as $user) {
                    foreach ($arrRunStages as $stage) {
                        $stageFunc = "doStage{$stage}";
                        try {
                            $this->$stageFunc($user);
                        } catch (\Exception $ex) {
                            throw new \Exception("Error:  failed to call method \$this->{$stageFunc}() for {$stage} from option --StageProcessor " . implode(",", $arrRunStages) . ".  Error: {$ex}");
                        }
                    }
                }
            } else {
                /*
                 * If no stage was specifically requested, we default to running stages 1 - 3
                 */
                foreach ($usersForRun as $user) {
                    $this->doStage1($user);
                    $this->doStage2($user);
                    $this->doStage3($user);
                }
            }
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } finally {
            try {
                $this->doFinalStage();
            } catch (\Exception $ex) {
                handleException($ex, null, true);
            }
        }
    }

    /**
     * @param array $userFacts
     * @throws \Exception
     */
    public function doStage1(array $userFacts)
    {
        $this->_initConfig();

        startLogSection("Stage 1: Downloading Latest Matching Jobs for User {$userFacts['UserSlug']}");

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // OK, now we have our list of searches & sites we are going to actually run
        // Let's go get the jobs for those searches
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $jobsiteKeys = JobSiteManager::getIncludedJobSiteKeys();
        $sitePlugin = null;

        if (!is_empty_value($jobsiteKeys)) {
            LogMessage(PHP_EOL . "**************  Starting Run for user={$userFacts['UserSlug']} for " . count($jobsiteKeys) . " potential job sites **************  " . PHP_EOL);

	        foreach($jobsiteKeys as $jobsiteKey)
	        {

	            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	            //
	            // Run each plugin, set the searches to run and then download the jobs from that jobsite
	            //
	            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	            try {
	            	$user = User::getUserObjById($userFacts['UserId']);
		            $siteRuns = $user->getUserSearchSiteRunsForJobSite($jobsiteKey);
		            if(!is_empty_value($siteRuns)) {
	                    $countTotalSiteSearches = count($siteRuns);
	                    $plugin = null;
	                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	                    //
	                    // Add the user's searches to the plugin
	                    //
	                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	                    startLogSection("Initializing {$countTotalSiteSearches} search(es) for {$jobsiteKey}...");
	                    try {
	                    	$sitePlugin = JobSiteManager::getJobSitePluginByKey($jobsiteKey);
	                        $sitePlugin->setSearches($siteRuns);
	                    } catch (\Exception $classError) {
	                        handleException($classError, "Unable to add searches to {$jobsiteKey} plugin: %s", $raise = false);
	                    } finally {
							// make sure to clean up the references to each of the UserSiteSearchRun objects
							// so we dont leave a DB connection open
	                        foreach(array_keys($siteRuns) as $k) {
								$siteRuns[$k] = null;
								unset($siteRuns[$k]);
	                        }
	                        $siteRuns = null;
	                        endLogSection(" {$jobsiteKey} searches initialized.");
	                    }

	                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	                    //
	                    // Download all the job listings for all the users searches for this plugin
	                    //
	                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	                    try {
	                        startLogSection("Downloading jobs for {$countTotalSiteSearches} search(es) for {$jobsiteKey}...");
	                        $sitePlugin->downloadLatestJobsForAllSearches();
	                    } catch (\Exception $classError) {
	                        handleException($classError, "{$jobsiteKey} failed to download job postings: %s", $raise = false);
	                    } finally {
	                        endLogSection("Job downloads have ended for {$jobsiteKey}.");
	                    }
		            }
	            } catch (\Exception $ex) {
	                handleException($ex, null, false);
	            } finally {
					$user = null;
	            }
            }
        } else {
            LogWarning('No searches have been set to be run.');
        }

        endLogSection("Stage 1 for {$userFacts['UserSlug']}");
    }

    /**
     * @param array $userFacts
     * @throws \Exception
     */
    public function doStage2(array $userFacts)
    {
        $this->_initConfig();

        try {
            startLogSection("Stage 2:  Auto-marking all user job matches for user {$userFacts['UserSlug']}...");
            $marker = new JobsAutoMarker($userFacts);
            $marker->markJobs();
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } finally {
            endLogSection("End of stage 2 (auto-marking) for user {$userFacts['UserSlug']}.");
        }
    }

    /**
     * @param array $userFacts
     *
     * @throws \Exception
     */
    public function doStage3(array $userFacts)
    {
        $this->_initConfig();
        try {
            startLogSection("Stage 3: Notifying User '{$userFacts['UserSlug']}'");
            $notifier = new NotifierJobAlerts();


            $notifier->processRunResultsNotifications($userFacts);
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } catch (\PhpOffice\PhpSpreadsheet\Style\Exception $ex) {
            handleException(null, $ex->getMessage(), true);
        } finally {
            endLogSection("End of stage 3 (notifying user)");
        }
    }


    /**
     * @param array[] $userFacts
     *
     * @throws \Exception
     */
    public function doWeeklyRecaps($users)
    {
        $this->_initConfig();

        startLogSection("do Weekly Recaps: Sending Weekly Recaps to Users");
        try {
            if (!empty($users)) {
                foreach ($users as $userFacts) {
                    startLogSection("Recap begun for '{$userFacts['UserSlug']}'");
                    $notify = new NotifierJobAlerts();

		            // BUGBBUG
		            throw new \Exception("Not yet converted to use array users!");

                    $notify->processWeekRecapNotifications($userFacts);
                    endLogSection("Recap done for {$userFacts['UserSlug']}'");
                }
            }
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } catch (\PhpOffice\PhpSpreadsheet\Style\Exception $ex) {
            handleException(null, $ex->getMessage(), true);
        } finally {
            endLogSection("End of do Weekly Recaps command");
        }
    }
    /**
     * @param array[] $users
     *
     * @throws \Exception
     */
    public function removeUserData(array $users)
    {
        $this->_initConfig();

        startLogSection("BEGIN: removeUserData from database command");
        if (!isDebug()) {
            throw new \Exception("Removing user data is only allowed if the developer is running in debug mode.  Please set --debug flag when running. Aborting.");
        }

        try {
            if (!empty($users)) {
                foreach ($users as $user) {
		            // BUGBBUG
		            throw new \Exception("Not yet converted to use array users!");

                    $remover = DBRecordRemover::removeUsers($users);
                }
            }
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } catch (\PhpOffice\PhpSpreadsheet\Style\Exception $ex) {
            handleException($ex, $ex->getMessage(), true);
        } finally {
            endLogSection("END:  removeUserData from database");
        }
    }

    /**
     * @throws \Exception
     */
    public function doFinalStage()
    {
        $this->_initConfig();
        try {
            startLogSection("Processing any developer alerts for plugin errors...");
            $devNotifier = new NotifierDevAlerts();
            $devNotifier->processPluginErrorAlert();
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } finally {
            endLogSection("End of dev alerts for plugin errors");
        }

        $runStartTime = getConfigurationSetting('app_run_start_datetime');

        if (!empty($runStartTime)) {
            $runtime = $runStartTime->diff(new \DateTime());

            logMessage("JobScooper runtime was " . $runtime->format("%h hours, %i minutes and %s seconds (%h:%i:%s)."));
        }
    }
}
