<?php
/**
 * Copyright 2014-18 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the 'License'); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
namespace JobScooper\Utils;

use JobScooper\DataAccess\UserQuery;
use JobScooper\DataAccess\GeoLocationManager;
use JobScooper\DataAccess\JobSiteManager;
use Propel\Common\Config\ConfigurationManager;
use Propel\Runtime\Exception\InvalidArgumentException;
use \SplFileInfo;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use \Propel\Runtime\Propel;

$GLOBALS['CACHES'] = array('LOCATION_MANAGER' =>null, 'GEOCODER_ENABLED' => true);


/**
 * Class ConfigInitializer
 * @package JobScooper\Utils
 */
class ConfigInitializer
{
    private $_rootOutputDirInfo = null;

    /**
     * ConfigInitializer constructor.
     *
     * @param null $iniFile
     *
     */
    public function __construct($iniFile = null)
    {
        if (null === $iniFile) {
            $iniFile = Settings::getValue('command_line_args.config');
        }

        if (is_empty_value($iniFile)) {
            throw new \InvalidArgumentException('Missing user configuration settings file definition.  You must specify the configuration file on the command line.  Aborting.');
        }

        $envDirOut = getenv('JOBSCOOPER_OUTPUT');
        if (!is_empty_value($envDirOut)) {
            Settings::setValue('output_directories.root', $envDirOut);
        }

        $configData = Settings::loadConfig($iniFile);
        Settings::setValue('config_file_settings', $configData);
        $outdir = Settings::getValue('config_file_settings.output_directory');
        if (is_empty_value(Settings::getValue('output_directories.root')) && !is_empty_value($outdir)) {
            Settings::setValue('output_directories.root', $outdir);
        }
    }

    protected $nNumDaysToSearch = -1;
    public $arrConfigFileDetails = array('output' => null, 'output_subfolder' => null, 'config_ini' => null);
    protected $allConfigFileSettings = null;

    /**
     * @throws \ErrorException
     * @throws \Exception
     */
    public function initialize()
    {
        Settings::moveValue('command_line_args.debug', 'debug');
        
        startLogSection('Setting up configuration... ');

        $now = new \DateTime();
        Settings::setValue('app_run_id', $now->format('Ymd_His_') .__APP_VERSION__);
        
        $file_name = Settings::getValue('command_line_args.configfile');
        $this->arrConfigFileDetails = new SplFileInfo($file_name);

        $rootOutputPath = Settings::getValue('output_directories.root');
        if (empty($rootOutputPath)) {
            throw new \Exception('Missing JOBSCOOPER_OUPUT environment variable value.');
        }
        $rootOutputDir = parsePathDetailsFromString($rootOutputPath, C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
        if ($rootOutputDir->isDir() !== true) {
            $outputpath = sprintf('%s%s%s', $this->arrConfigFileDetails->getPathname(), DIRECTORY_SEPARATOR, 'output');
            $rootOutputDir = parsePathDetailsFromString($outputpath, C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
            Settings::setValue('output_directories.root', $rootOutputDir->getPathname());
        }

        $this->setupPropelForRun();

        // Now setup all the output folders
        $this->setupOutputFolders();

        $strOutfileArrString = getArrayValuesAsString(Settings::getValue('output_directories'));
        LogMessage('Output folders configured: ' . $strOutfileArrString);


        endLogSection('Loaded configuration details from ' . $this->arrConfigFileDetails->getPathname());

        startLogSection('Configuring specific settings for this run... ');
        $this->setupRunFromConfig();

        Settings::setValue('number_days', 1);

        endLogSection('Finished setting up run.');
    }

    /**
     * @throws \ErrorException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    private function setupOutputFolders()
    {
        $arrOututDirs = Settings::getValue('output_directories');
        $outputDirectory = $arrOututDirs['root'];
        if (empty($outputDirectory)) {
            throw new \ErrorException("Required value for the output folder {$outputDirectory} was not specified. Exiting.");
        }

        $globalDirs = ['debug', 'logs', 'caches'];
        foreach ($globalDirs as $d) {
            $path = join(DIRECTORY_SEPARATOR, array($outputDirectory, getTodayAsString('-'), $d));
            $details = parsePathDetailsFromString($path, \C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
            $arrOututDirs[$d] = realpath($details->getPathname());
        }

        Settings::setValue('output_directories', $arrOututDirs);

        Settings::setValue('logging', $this->getSetting('logging'));

        if (!isset($GLOBALS['logger'])) {
            $GLOBALS['logger'] = new \JobScooper\Manager\LoggingManager(C__APPNAME__);
        }

        $GLOBALS['logger']->addFileHandlers(getOutputDirectory('logs'));
        $this->setupPropelLogging();
    }

    /**
     * @throws \ErrorException
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    private function setupRunFromConfig()
    {
        //
        // Load the global search data that will be used to create
        // and configure all searches
        //
        $this->parseGlobalSearchParameters();

        //
        // First load the user email information.  We set this first because it is used
        // to send error email if something goes wrong anywhere further along our run
        //
        $this->parseUserConfigs();
        $this->parseAlertReceipients();

        LogMessage('Loaded all configuration settings from ' . $this->arrConfigFileDetails->getPathname());

        // Note:  this must happen before any of the job site plugins are instantiated
        $this->parsePluginSettings();

        // initialize the plugin bulder class so that all the plugins get loaded
        //
        new JobSiteManager();

        $this->instantiateLocationManager();
        $this->parseSeleniumParameters();

        if (count(JobSiteManager::getIncludedJobSiteKeys()) == 0) {
            LogError('No job site plugins could be loaded for the given search geographic locations.  Aborting.');
            return;
        }
    }

    /**
     * @return \JobScooper\DataAccess\GeoLocationManager
     * @throws \Exception
     */
    private function instantiateLocationManager()
    {
        $cache = GeoLocationManager::getLocationManager();
        if (null === $cache) {
            GeoLocationManager::create();
            $cache = GeoLocationManager::getLocationManager();
        }

        return $cache;
    }


    /**
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    private function setupPropelForRun()
    {
        $cfgDBConns = null;
        $cfgSettingsFile = $this->getSetting('propel.configuration_file');
        if (!empty($cfgSettingsFile)) {
            LogMessage('Loading Propel configuration file: ' . $cfgSettingsFile);
            $propelCfg = new ConfigurationManager($cfgSettingsFile);
            $cfgDBConns = $propelCfg->getConfigProperty('database.connections');
            if (!empty($cfgDBConns)) {
                LogMessage('Using Propel Connection Settings from Propel config: ' . getArrayDebugOutput($cfgDBConns));
            }
        }

        if (empty($cfgDBConns)) {
            $cfgDBConns = $this->getSetting('propel.database.connections');
            if (!empty($cfgDBConns)) {
                LogMessage('Using Propel Connection Settings from Jobscooper Config: ' . getArrayDebugOutput($cfgDBConns));
            }
        }

        if (empty($cfgDBConns)) {
            throw new InvalidArgumentException('No Propel database connection definitions were found in the config files.  You must define at least one connection\'s settings under propel.database.connections.');
        } elseif (count($cfgDBConns) > 1) {
            LogWarning('More than one database connection was defined for Propel.  Using \'default\' if exists; otherwise using first connection found.');
        }

        $dbConnSettings = null;
        foreach ($cfgDBConns as $connKey => $setting) {
            if (strtoupper($connKey) === 'DEFAULT') {
                $dbConnSettings = $setting;
            }
        }
        if (empty($dbConnSettings)) {
            $dbConnSettings = array_shift($cfgDBConns);
        }

        assert(!empty($dbConnSettings));


        if (stristr($dbConnSettings['dsn'], 'charset') !== true) {
            $dbConnSettings['dsn'] .= ';charset=utf8mb4';
        }

        $serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
        $serviceContainer->checkVersion('2.0.0-dev');
        $serviceContainer->setAdapterClass($connKey, $dbConnSettings['adapter']);
        $manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
        $manager->setConfiguration(array(
            'dsn'         => $dbConnSettings['dsn'],
            'user'        => $dbConnSettings['user'],
            'password'    => $dbConnSettings['password'],
            'classname'   => \Propel\Runtime\Connection\ConnectionWrapper::class,
            'attributes'  => array(
                'ATTR_TIMEOUT' => 360
            ),
            'model_paths' =>
                array(
                    0 => 'src',
                    1 => 'vendor',
                ),
        ));
        $manager->setName($connKey);
        $serviceContainer->setConnectionManager($connKey, $manager);
        $serviceContainer->setDefaultDatasource($connKey);
        \Propel\Runtime\Propel::setServiceContainer($serviceContainer);
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     */
    private function setupPropelLogging()
    {
        LogDebug('Configuring Propel logging...');
        $defaultLogger = $GLOBALS['logger'];
        if (null === $defaultLogger) {
            $pathLog = getOutputDirectory('logs') . '/propel-' .getTodayAsString('-').'.log';
            LogWarning('Could not find global logger object so configuring propel logging separately at {$pathLog}.');
            $defaultLogger = new Logger('defaultLogger');
            $defaultLogger->pushHandler(new StreamHandler($pathLog, Logger::DEBUG));
            $defaultLogger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
        }

        $serviceContainer = Propel::getServiceContainer();
        $serviceContainer->setLogger('defaultLogger', $defaultLogger);
        if (isDebug()) {
            $con = Propel::getWriteConnection(\JobScooper\DataAccess\Map\JobPostingTableMap::DATABASE_NAME);
            $con->useDebug(true);
            LogMessage('Enabled debug logging for Propel.');
        }
    }

    /**
     * @param $keyPath
     *
     * @return array|mixed
     */
    private function getSetting($keyPath)
    {
        if (is_array($keyPath)) {
            $ret = array();
            foreach ($keyPath as $key) {
                $ret[$key] = $this->getSetting($key);
            }
            return $ret;
        }

        return Settings::getValue('config_file_settings.' . $keyPath);
    }

    /**
     *
     */
    private function parsePluginSettings()
    {
        LogMessage('Loading plugin setup information from config file...');

        Settings::setValue('plugin_settings', $this->getSetting('plugin_settings'));
    }

    /**
     *
     * @throws \Exception
     */
    private function parseGlobalSearchParameters()
    {
        LogMessage('Loading global search settings from config file...');

        $gsoset = $this->getSetting('global_search_options');
        if (!is_empty_value($gsoset)) {
            // This must happen first so that the search locations can be geocoded
            if (array_key_exists('google_maps_api_key', $gsoset)) {
                Settings::setValue('google_maps_api_key', $gsoset['google_maps_api_key']);
            }

            foreach ($gsoset as $gsoKey => $gso) {
                if (!empty($gso)) {
                    switch (strtoupper($gsoKey)) {
                        case 'EXCLUDED_JOBSITES':
                            if (is_string($gso)) {
                                $gso = preg_split('/\s*,\s*/', $gso);
                                $gso = array_combine(array_values($gso), $gso);
                            }
                            if (!is_array($gso)) {
                                $gso = array($gso => $gso);
                            }
                            Settings::setValue('config_excluded_sites', $gso);
                            break;

                        default:
                            Settings::setValue($gsoKey, $gso);
                            break;
                    }
                }
            }
        }
    }

    /**
     * @throws \ErrorException
     */
    private function parseSeleniumParameters()
    {
        LogDebug('Loading Selenium settings from config file...');
        $settings = $this->getSetting('selenium');

        if (!array_key_exists('server', $settings)) {
            throw new \ErrorException('Configuration missing for [selenium] [server] in the config INI files.');
        } elseif (strcasecmp('localhost', $settings['server']) === 0) {
            throw new \ErrorException('Invalid server value for [selenium] [server] in the config INI files. You must use named hosts, not localhost.');
        }

        if (!array_key_exists('port', $settings)) {
            $settings['port'] = '80';
        }

        $settings['host_location'] = 'http://' . $settings['server'] . ':' . $settings['port'];

        Settings::setValue('selenium', $settings);
    }

    /**
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    private function parseUserConfigs()
    {
        LogMessage('Creating or updating users based on config file settings...');

        Settings::setValue('alerts.configuration.smtp', $this->getSetting('alerts.configuration.smtp'));

        $userList = array();

        //
        // Configure the primary user for the config file and set it
        //
        $currentUser = null;
        $cmd_line_user_to_run = Settings::getValue('command_line_args.user');

        $config_users = $this->getSetting('users');
        $user_recs = array();
        if (is_empty_value($config_users)) {
            throw new \Exception('No users found in configuration settings.  Aborting.');
        } else {

        	//
        	// if the user specified a single user to run on the commamnd line, then ignore any
        	// user data loaded from the config for this run besides that specific user.
        	//
        	if(!is_empty_value($cmd_line_user_to_run)) {
        		if(array_key_exists($cmd_line_user_to_run, $config_users)) {
        			$config_users = [$cmd_line_user_to_run => $config_users[$cmd_line_user_to_run]];
        		}
        	}
            foreach ($config_users as $key_user => $config_user) {
                $updatedUser = UserQuery::findOrCreateUserByUserSlug(cleanupSlugPart($key_user), $config_user, $overwriteFacts = true);
                if (null === $updatedUser) {
                    throw new \Exception('Failed to create or update user based on config section users.{$key_user}.');
                }
                $user_recs[$key_user] = $updatedUser->toArray();
                $updatedUser = null;
            }
        }

        Settings::setValue('users_for_run', $user_recs);


        // First try to pull the user from the database by that userslug value.  Use that user
        // if we find one.  This allows a dev to override the local config file data if needed
        if (!empty($cmd_line_user_to_run)) {
            $currentUser = UserQuery::getUserByUserSlug($cmd_line_user_to_run);
            if(null !== $currentUser)
            	$currentUser = $currentUser->toArray();
        }

        // if we didn't match a user, look for one as the key name in a config file section under [users.*]
        if (empty($currentUser) && array_key_exists($cmd_line_user_to_run, $user_recs)) {
            $currentUser = $user_recs[$cmd_line_user_to_run];
        }

        // if we specified a single user to run, reduce the set of users for run to just that single instance
        if (!empty($currentUser)) {
            $user_recs = array($cmd_line_user_to_run => $currentUser);
            Settings::setValue('users_for_run', $user_recs);

            LogMessage("Limiting users run to single, specified user: {$cmd_line_user_to_run}");
        } elseif (!empty($cmd_line_user_to_run)) {
            throw new \Exception("Unable to find user matching {$cmd_line_user_to_run} that was specified for the run.");
        }

        if (empty($user_recs)) {
            throw new \Exception('No email address or user has been found to send results notifications.  Aborting.');
        }
    }

    /**
     * @throws \Exception
     */
    private function parseAlertReceipients()
    {
        LogMessage('Configuring contacts for alerts...');

        Settings::setValue('alerts.configuration.smtp', $this->getSetting('alerts.configuration.smtp'));

        $keysAlertsTypes = array('alerts.errors.to', 'alerts.errors.from', 'alerts.results.from');
        foreach ($keysAlertsTypes as $alertKey) {
            $arrOtherUserFacts = $this->getSetting($alertKey);
            if (empty($arrOtherUserFacts)) {
                continue;
            }

            $nextUser = array();
            $otherUser = UserQuery::findUserByEmailAddress($arrOtherUserFacts['email'], $arrOtherUserFacts, false);
            if (null !== $otherUser) {
                $nextUser = $otherUser->toArray();
                $nextUser['User'] = $otherUser;
                $otherUser = null;
            } else {
                $nextUser = $arrOtherUserFacts;
                $nextUser['User'] = null;
            }
            Settings::setValue($alertKey, $nextUser);
        }
    }
}
