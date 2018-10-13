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
use JobScooper\StageProcessor\DataNormalizer;
use JobScooper\StageProcessor\JobsAutoMarker;
use JobScooper\StageProcessor\NotifierDevAlerts;
use JobScooper\StageProcessor\NotifierJobAlerts;
use JobScooper\Utils\ConfigInitializer;
use JobScooper\Utils\DBRecordRemover;
use JobScooper\Utils\Settings;
use Propel\Runtime\Exception\PropelException;

const JOBLIST_TYPE_UNFILTERED = 'unfiltered';
const JOBLIST_TYPE_MARKED = 'marked';
const JSON_FILENAME = '-alljobsites.json';

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

    protected $JobSiteName = 'StageManager';
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
        $runStartTime = Settings::getValue('app_run_start_datetime');
        if (empty($runStartTime)) {
	        Settings::setValue('app_run_start_datetime', new \DateTime());
        }

        if (empty($this->classConfig)) {
            $this->classConfig = new ConfigInitializer();
            $this->classConfig->initialize();
        }

    }

    /**
     * @throws \Exception
     */
    public function runAll()
    {
        try {
            $this->_initConfig();

            $cmds = Settings::getValue('command_line_args');
            if (array_key_exists('recap', $cmds) && !empty($cmds['recap'])) {
                $this->doWeeklyRecaps();
            } elseif (array_key_exists('delete', $cmds) && !empty($cmds['delete'])) {
                $this->removeUserData();
            } elseif (array_key_exists('stages', $cmds) && !empty($cmds['stages'])) {
                $arrRunStages = Settings::getValue('command_line_args.stages');

                foreach ($arrRunStages as $stage) {
                    $stageFunc = "doStage{$stage}";
                    try {
                        $this->$stageFunc();
                    }
					catch (\Exception $ex) {
                        throw new \Exception("Error:  failed to call method \$this->{$stageFunc}() for {$stage} from option --StageProcessor " . implode(",", $arrRunStages) . ".  Error: {$ex}");
                    }
                }
            } else {
                /*
                 * If no stage was specifically requested, we default to running stages 1 - 4
                 */
                $this->doStage1();
                $this->doStage2();
                $this->doStage3();
                $this->doStage4();
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
     * @throws \Exception
     */
    public function doStage1()
    {
        $this->_initConfig();

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // OK, now we have our list of searches & sites we are going to actually run
        // Let's go get the jobs for those searches
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $jobsiteKeys = JobSiteManager::getJobSiteKeysIncludedInRun();
        $sitePlugin = null;
		$siteRuns = null;
		$user = null;
        $usersForRun = Settings::getValue('users_for_run');

        startLogSection("Stage 1:  Downloading new jobs for " . \count($jobsiteKeys) . ' job sites and' . \count($usersForRun) . ' users.');

        if (is_empty_value($jobsiteKeys)) {
            throw new \InvalidArgumentException('No job sites were set to be run.  Aborting');
        }

        if(is_empty_value($usersForRun)) {
            throw new \InvalidArgumentException('No user information was set to be run.  Aborting.');
        }

        foreach($jobsiteKeys as $jobsiteKey)
        {
            startLogSection("Downloading jobs from {$jobsiteKey} for " . \count($usersForRun) . ' users.');

            try {
                if(!is_empty_value($usersForRun)) {
                    foreach($usersForRun as $userFacts) {

                        startLogSection("Downloading jobs:  {$jobsiteKey}  / {$userFacts['UserSlug']}  ");

                        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        //
                        // Run each plugin, set the searches to run and then download the jobs from that jobsite
                        //
                        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        $user = User::getUserObjById($userFacts['UserId']);
                        $siteRuns = $user->getUserSearchSiteRunsForJobSite($jobsiteKey);
                        if (is_empty_value($siteRuns)) {
                            LogMessage("No site runs generated for {$userFacts['UserSlug']} for {$jobsiteKey}");
                        }
                        else
                        {
                            $countTotalSiteSearches = \count($siteRuns);
                            $plugin = null;
                            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                            //
                            // Add the user's searches to the plugin
                            //
                            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                            startLogSection("Initializing {$countTotalSiteSearches} search(es) for {$jobsiteKey}  / {$userFacts['UserSlug']} ...");
                            try {
                                $sitePlugin = $this->_getJobSitePluginInstance($jobsiteKey);
                                $sitePlugin->setSearches($siteRuns);
                            } catch (\Exception $classError) {
                                handleException($classError, "Unable to add searches to {$jobsiteKey} plugin: %s", $raise = true);
                            } finally {
                                endLogSection(" {$jobsiteKey}  / {$userFacts['UserSlug']}  searches initialized.");
                            }

                            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                            //
                            // Download all the job listings for all the users searches for this plugin
                            //
                            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                            try {
                                startLogSection("Getting latest jobs for {$countTotalSiteSearches} search(es) for {$jobsiteKey}  / {$userFacts['UserSlug']} ...");
                                $sitePlugin->downloadLatestJobsForAllSearches();
                            } catch (\Exception $classError) {
                                handleException($classError, "{$jobsiteKey} failed to get latest job postings for user {$userFacts['UserSlug']}: %s", $raise = true);
                            } finally {
                                endLogSection("Finished getting latest jobs:  {$jobsiteKey}  / {$userFacts['UserSlug']}  ");
                            }
                        }

                        endLogSection("Done downloading jobs from {$jobsiteKey} for {$userFacts['UserSlug']}");
                    }
                }
            } catch (PropelException $pex) {
                if (!is_empty_value($usersForRun)) {
                    $remainUsers = join(", ", $usersForRun);
                    LogError("Skipping searches for {$remainUsers} users due to plugin failure to download listings.");
                }
            handleException($pex, null, false);
            } catch (\Exception $ex) {
                if (!is_empty_value($usersForRun)) {
                    $remainUsers = join(", ", $usersForRun);
                    LogError("Skipping searches for {$remainUsers} users due to plugin failure to download listings.");
                }
                handleException($ex, null, false);
            } finally {
                $sitePlugin = null;
                // make sure to clean up the references to each of the UserSiteSearchRun objects
                // so we dont leave a DB connection open
                if(!is_empty_value($siteRuns)) {
                    foreach(array_keys($siteRuns) as $k) {
                        $siteRuns[$k] = null;
                        unset($siteRuns[$k]);
                    }
                }
                $user = null;
                $siteRuns = null;
            }
            endLogSection("Done downloading jobs for {$jobsiteKey}");
        }

        endLogSection("End Stage 1: Finished downloading new jobs.");

    }

    /**
     * @throws \Exception
     */
    public function doStage2()
    {
        $this->_initConfig();

        try {
            startLogSection('Stage 2:  Normalizing job posting details for recent posts...');
            $normer = new DataNormalizer();
            $normer->normalizeJobs();
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } finally {
        	$normer = null;
            endLogSection('End of stage 2 (job posting normalization).');
        }
    }

    /**
     * @throws \Exception
     */
    public function doStage3()
    {
        $this->_initConfig();

        try {
            startLogSection("Stage 3:  Auto-marking new potential job matches...");
            $marker = new JobsAutoMarker();

            $usersForRun = Settings::getValue('users_for_run');
            if(is_empty_value($usersForRun)) {
            	throw new \InvalidArgumentException('No user information was set to be run.  Aborting.');
            }

            $marker->markJobMatches($usersForRun);

        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } finally {
        	$marker = null;
        	$usersForRun = null;
            endLogSection("Stage 3: Completed auto-marking job matches.");
        }
    }

    /**
     * @throws \Exception
     */
    public function doStage4()
    {
        $this->_initConfig();
 
        try {
            $usersForRun = Settings::getValue('users_for_run');
            if(is_empty_value($usersForRun)) {
            	throw new \InvalidArgumentException('No user information was set to be run.  Aborting.');
            }
            
            foreach($usersForRun as $userFacts) {
	            startLogSection("Stage 4: Notifying User '{$userFacts['UserSlug']}'");
	            $notifier = new NotifierJobAlerts();
	            
                $notifier->processRunResultsNotifications($userFacts);
	            endLogSection("End of stage 4 (notifying {$userFacts['UserSlug']})");
            }
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } finally {
        	$notifier = null;
        	$usersForRun = null;
            endLogSection('End of stage 4: user notifications');
        }
    }


    /**
     * @param array[] $userFacts
     *
     * @throws \Exception
     */
    public function doWeeklyRecaps()
    {
        $this->_initConfig();

        startLogSection('do Weekly Recaps: Sending Weekly Recaps to Users');
        try {
            /*
             * Run specific stages requested via command-line
             */
            $usersForRun = Settings::getValue('users_for_run');
            if(is_empty_value($usersForRun)) {
            	throw new \InvalidArgumentException('No user information was set to be run.  Aborting.');
            }

            if (!empty($usersForRun)) {
                foreach ($usersForRun as $userFacts) {
                    startLogSection("Recap begun for '{$userFacts['UserSlug']}'");
                    $notify = new NotifierJobAlerts();

		            // BUGBBUG
		            throw new \Exception('Not yet converted to use array users!');

                    $notify->processWeekRecapNotifications($userFacts);
                    endLogSection("Recap done for {$userFacts['UserSlug']}'");
                }
            }
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } finally {
        	$usersForRun = null;
        	$notify = null;
            endLogSection('End of do Weekly Recaps command');
        }
    }
    /**
     * @param array[] $users
     *
     * @throws \Exception
     */
    public function removeUserData()
    {
        $this->_initConfig();

        startLogSection('BEGIN: removeUserData from database command');
        if (!isDebug()) {
            throw new \Exception('Removing user data is only allowed if the developer is running in debug mode.  Please set --debug flag when running. Aborting.');
        }

        try {
            /*
             * Run specific stages requested via command-line
             */
            $usersForRun = Settings::getValue('users_for_run');
            if(is_empty_value($usersForRun)) {
            	throw new \InvalidArgumentException('No user information was set to be run.  Aborting.');
            }

            if (!empty($usersForRun)) {
                foreach ($usersForRun as $user) {
		            // BUGBBUG
		            throw new \Exception('Not yet converted to use array users!');

                    $remover = DBRecordRemover::removeUsers($usersForRun);
                }
            }
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        } finally {
            endLogSection('END:  removeUserData from database');
            $usersForRun = null;
        }
    }

    /**
     * @throws \Exception
     */
    public function doFinalStage()
    {
//        $this->_initConfig();
//        try {
//            startLogSection("Processing any developer alerts for plugin errors...");
//            $devNotifier = new NotifierDevAlerts();
//            $devNotifier->processPluginErrorAlert();
//        } catch (\Exception $ex) {
//            handleException($ex, null, true);
//        } finally {
//            endLogSection("End of dev alerts for plugin errors");
//        }
//
        $runStartTime = Settings::getValue('app_run_start_datetime');

        if (!empty($runStartTime)) {
            $runtime = $runStartTime->diff(new \DateTime());

            logMessage("JobScooper runtime was " . $runtime->format("%h hours, %i minutes and %s seconds (%h:%i:%s)."));
        }
    }
    
   /**
     * @param string $siteKey
     * @throws \Exception
     * @return \JobScooper\SitePlugins\IJobSitePlugin
     */
    private function _getJobSitePluginInstance($siteKey)
    {
    	$ret = null;
        $site = JobSiteManager::getJobSiteByKey($siteKey);
        if(null !== $site) {
        	$ret = $site->getPlugin();
        }

		$site = null;
        return $ret;
    }
}
