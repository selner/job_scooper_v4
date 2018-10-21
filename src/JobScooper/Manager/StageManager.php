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
use JobScooper\DataAccess\Map\JobSiteRecordTableMap;
use JobScooper\StageProcessor\DataNormalizer;
use JobScooper\StageProcessor\JobsAutoMarker;
use JobScooper\StageProcessor\NotifierDevAlerts;
use JobScooper\StageProcessor\NotifierJobAlerts;
use JobScooper\Utils\ConfigInitializer;
use JobScooper\Utils\PythonRunner;
use JobScooper\Utils\Settings;

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
        if (null !== $config) {
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
            if (array_key_exists('stages', $cmds) && !empty($cmds['stages'])) {
                $arrRunStages = Settings::getValue('command_line_args.stages');

                foreach ($arrRunStages as $stage) {
                    $stageFunc = "doStage{$stage}";
                    try {
                        $this->$stageFunc();
                    } catch (\Exception $ex) {
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
        // OK, let's go through each site, generate the site searches & then
        // download the jobs for those searches.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $jobsiteKeys = JobSiteManager::getJobSiteKeysIncludedInRun();
        $sitePlugin = null;
        $siteRuns = null;
        $user = null;
        $usersForRun = Settings::getValue('users_for_run');
        $didRunSearches = false;

        startLogSection("Stage 1:  Downloading new jobs for " . \count($jobsiteKeys) . ' job sites and' . \count($usersForRun) . ' users.');

        try {
            if (is_empty_value($jobsiteKeys)) {
                throw new \InvalidArgumentException('No job sites were set to be run.  Aborting');
            }

            if (is_empty_value($usersForRun)) {
                throw new \InvalidArgumentException('No user information was set to be run.  Aborting.');
            }

            foreach ($jobsiteKeys as $jobsiteKey) {

                $searchRuns = array();

                try {
                    startLogSection("Downloading new jobs from {$jobsiteKey} for " . \count($usersForRun) . ' users.');
                    $site = JobSiteManager::getJobSiteByKey($jobsiteKey);
                    assert(!is_empty_value($site));

                    if (!is_empty_value($usersForRun)) {

                        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        //
                        // Add the user's searches to the plugin
                        //
                        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                        try {
                            startLogSection("Initializing search(es) for users at {$jobsiteKey}");
                            $searchRuns = $site->generateUserSiteRuns($usersForRun);
                            $sitePlugin = $this->_getJobSitePluginInstance($jobsiteKey);
                            if (!is_empty_value($searchRuns)) {
                                $sitePlugin->setSearches($searchRuns);
                            }
                        } catch (\Exception $classError) {
                            handleException($classError, "Unable to add searches to {$jobsiteKey} plugin: %s", $raise = true);
                        } finally {
                            $totalSearches = \count($searchRuns);
                            endLogSection(" {$totalSearches} {$jobsiteKey} searches were initialized.");
                        }

                        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        //
                        // Download all the job listings for all the users searches for this plugin
                        //
                        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        try {
                            startLogSection("Getting latest jobs {$jobsiteKey} from the web for {$totalSearches} potential search(es)...");
                            if (!is_empty_value($searchRuns) && \count($searchRuns) > 0) {
                                $sitePlugin->downloadLatestJobsForAllSearches();
                            }
                            $didRunSearches = true;
                        } catch (\Exception $classError) {
                            handleException($classError, "{$jobsiteKey} failed to get latest job postings for {$jobsiteKey}: %s", $raise = true);
                            $didRunSearches = false;
                        } finally {
                            endLogSection("Finished getting latest jobs from {$jobsiteKey}  ");
                        }


                        $filterType = $site->getResultsFilterType();

                        if ((!is_empty_value($searchRuns)) &&
                            $filterType === JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE_ALL_ONLY ||
                            $filterType === JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE_ALL_ONLY) {

                            try {
                                startLogSection("Cloning new jobpostings to other users for {$jobsiteKey}");
                                foreach ($usersForRun as $userFacts) {

                                    $runFile = 'pyJobNormalizer/cmd_add_newpostings_to_user.py';
                                    $params = [
                                        '-c' => Settings::get_db_dsn(),
                                        '--userid' => $userFacts['UserId'],
                                        '--jobsite' => $jobsiteKey
                                    ];

                                    $resultcode = PythonRunner::execScript($runFile, $params);
                                }

                            } catch (\Exception $ex) {
                                handleException($ex, 'ERROR:  Failed to tag job matches as out of area for user:  %s');
                            } finally {
                                endLogSection("End cloning {$jobsiteKey} jobpostings to other users.");
                            }
                        }

                    } else {
                        LogWarning('No users have been set to be run.');
                    }

                } catch (\Exception $ex) {
                    $msg = "Skipping all other {$jobsiteKey} searches due to plugin failure: %s";
                    handleException($ex, $msg, false);
                } finally {
                    $sitePlugin = null;
                    // make sure to clean up the references to each of the UserSiteSearchRun objects
                    // so we dont leave a DB connection open
                    if (!is_empty_value($searchRuns)) {
                        $didRunSearches = true;

                        foreach (array_keys($searchRuns) as $k) {
                            $searchRuns[$k] = null;
                            unset($searchRuns[$k]);
                        }
                    }
                    $user = null;
                    $searchRuns = null;
                    endLogSection("Completed getting new job postings for {$jobsiteKey}.'");
                }
            }
        } catch (\Exception $ex) {
            $msg = "Stage 1 failed to run due to error: %s";
            handleException($ex, $msg, false);
        } finally {
            endLogSection("End Stage 1: Finished downloading new jobs.");
            if($didRunSearches === true) {
                $pluginAlerts = new NotifierDevAlerts();
                $pluginAlerts->processPluginErrorAlert();

            }
        }

    }

    /**
     * @throws \Exception
     */
    public
    function doStage2()
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
    public
    function doStage3()
    {
        $this->_initConfig();

        try {
            startLogSection("Stage 3:  Auto-marking new potential job matches...");
            $marker = new JobsAutoMarker();

            $usersForRun = Settings::getValue('users_for_run');
            if (is_empty_value($usersForRun)) {
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
    public
    function doStage4()
    {
        $this->_initConfig();

        try {
            $usersForRun = Settings::getValue('users_for_run');
            if (is_empty_value($usersForRun)) {
                throw new \InvalidArgumentException('No user information was set to be run.  Aborting.');
            }

            foreach ($usersForRun as $userFacts) {
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
     * @throws \Exception
     */
    public function doFinalStage()
    {
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
        if (null !== $site) {
            $ret = $site->getPlugin();
        }

        $site = null;
        return $ret;
    }
}
