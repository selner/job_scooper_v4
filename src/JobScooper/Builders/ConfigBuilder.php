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
namespace JobScooper\Builders;



use JobScooper\DataAccess\UserKeywordSet;
use JobScooper\DataAccess\UserKeywordSetQuery;
use JobScooper\DataAccess\UserQuery;
use JobScooper\DataAccess\User;
use JobScooper\Manager\LocationManager;
use JobScooper\Manager\LoggingManager;
use const JobScooper\Plugins\Classes\VALUE_NOT_SUPPORTED;
use Propel\Runtime\Exception\InvalidArgumentException;
use \SplFileInfo;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PHLAK\Config\Config;
use  \Propel\Runtime\Propel;


$GLOBALS['CACHES'] = array('LOCATION_MANAGER' =>null, 'GEOCODER_ENABLED' => true);


class ConfigBuilder
{
	private $_rootOutputDirInfo = null;

    public function __construct($iniFile = null)
    {
	    $envDirOut = getenv('JOBSCOOPER_OUTPUT');
	    if(!empty($envDirOut))
		    setConfigurationSetting("output_directories.root", $envDirOut);

	    $Config = new Config($iniFile,true,"imports");
	    setConfigurationSetting("config_file_settings", $Config->getAll());
    }

    protected $nNumDaysToSearch = -1;
    public $arrFileDetails = array('output' => null, 'output_subfolder' => null, 'config_ini' => null, 'user_input_files_details' => null);
    protected $allConfigFileSettings = null;

    function initialize()
    {
	    $debug = getConfigurationSetting("command_line_args.debug");
	    setConfigurationSetting("debug", $debug);

        LogDebug("Setting up application... ", \C__DISPLAY_SECTION_START__);

        $now = new \DateTime();
        setConfigurationSetting('app_run_id', $now->format('Ymd_His_') .__APP_VERSION__);


	    $file_name = getConfigurationSetting("command_line_args.configfile");
        $this->arrFileDetails['config_ini'] = new SplFileInfo($file_name);

	    $rootOutputPath = getConfigurationSetting("output_directories.root");
	    $rootOutputDir = parsePathDetailsFromString($rootOutputPath, C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
	    if($rootOutputDir->isDir() !== true)
	    {
		    $outputpath = sprintf("%s%s%s", $this->arrFileDetails['config_ini']->getPathname(), DIRECTORY_SEPARATOR, "output");
		    $rootOutputDir = parsePathDetailsFromString($outputpath, C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
		    setConfigurationSetting("output_directories.root", $rootOutputDir->getPathname());
	    }


	    //        $name = str_replace(DIRECTORY_SEPARATOR, "", $this->arrFileDetails['config_ini']->getPathname());
//        $name = substr($name, max([strlen($name) - 31 - strlen(".ini"), 0]), 31);

        // Now setup all the output folders
        $this->__setupOutputFolders__();

        $strOutfileArrString = getArrayValuesAsString(getConfigurationSetting("output_directories"));
        LogLine("Output folders configured: " . $strOutfileArrString, \C__DISPLAY_ITEM_DETAIL__);


        LogLine("Loaded configuration details from " . $this->arrFileDetails['config_ini']->getPathname(), \C__DISPLAY_ITEM_DETAIL__);

	    LogDebug("Configuring specific settings for this run... ", \C__DISPLAY_SECTION_START__);
        $this->_setupRunFromConfig_();

        setConfigurationSetting('number_days', 1);

        LogLine("Completed configuration load.", \C__DISPLAY_SUMMARY__);

    }

    private function __setupOutputFolders__()
    {
    	$arrOututDirs = array();

	    $outputDirectory = getConfigurationSetting("output_directories.root");
	    if (empty($outputDirectory)) {
		    throw new \ErrorException("Required value for the output folder {$outputDirectory} was not specified. Exiting.");
	    }

        $globalDirs = ["debug", "logs"];
        foreach ($globalDirs as $d) {
            $path = join(DIRECTORY_SEPARATOR, array($outputDirectory, getTodayAsString("-"), $d));
            $details = parsePathDetailsFromString($path, \C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
	        $arrOututDirs[$d] = realpath($details->getPathname());
        }

        $userWorkingDirs = ["notifications"];
        foreach ($userWorkingDirs as $d) {
            $prefix = $GLOBALS['user_unique_key'];
            $path = join(DIRECTORY_SEPARATOR, array($outputDirectory, getTodayAsString("-"), $d, $prefix));
            $details = parsePathDetailsFromString($path, \C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
	        $arrOututDirs[$d] = realpath($details->getPathname());
        }

		setConfigurationSetting('output_directories', $arrOututDirs);

	    if (!isset($GLOBALS['logger']))
		    $GLOBALS['logger'] = new LoggingManager(getOutputDirectory('logs'));
        $GLOBALS['logger']->addFileHandlers(getOutputDirectory('logs'));
    }

    private function _setupRunFromConfig_()
    {
	    $this->_setupPropelForRun();

	    $srchmgr = new SearchBuilder();

	    $config = getConfigurationSetting("config_file_settings");
	    $inputFolderPath = array_get_element(strval("input.folder"), $config);
	    if (!empty($inputFolderPath))
		    $this->arrFileDetails['input_folder'] = parsePathDetailsFromString($inputFolderPath, C__FILEPATH_DIRECTORY_MUST_EXIST);
	    else
		    $this->arrFileDetails['input_folder'] = parsePathDetailsFromString(getConfigurationSetting("configfile"));


	    //
	    // First load the user email information.  We set this first because it is used
	    // to send error email if something goes wrong anywhere further along our run
	    //
	    $this->_parseUsers();


	    //
	    // Validate each of the inputfiles that the user passed
	    // and configure all searches
	    //
	    $verifiedInputs = array();
	    $inputfiles = array_get_element(strval("inputfiles"), $config);
	    if (!empty($inputfiles) && is_array($inputfiles)) {
		    foreach ($inputfiles as $key => $iniInputFileItem)
		    {
			    $tempFileDetails = null;
			    $filepath = !empty($iniInputFileItem['path']) ? $iniInputFileItem['path'] : $iniInputFileItem['filename'];
			    $fileinfo = new \SplFileInfo($filepath);
		        if($fileinfo->getRealPath() !== false)
			        $tempFileDetails = parsePathDetailsFromString($fileinfo->getRealPath(), C__FILEPATH_FILE_MUST_EXIST);

		        if(empty($tempFileDetails) || $tempFileDetails->isFile() !== true) {
				    $filesearch = glob($this->arrFileDetails['input_folder']->getPath() . DIRECTORY_SEPARATOR . "*" . DIRECTORY_SEPARATOR . $fileinfo->getFilename());
				    if (!empty($filesearch)) {
				    	$firstmatch = array_shift($filesearch);
					    $tempFileDetails = parsePathDetailsFromString($firstmatch, C__FILEPATH_FILE_MUST_EXIST);
				    }
			    }

			    if(empty($tempFileDetails) || $tempFileDetails->isFile() !== true) {
				    throw new \Exception("Specified input file '" . $filepath . "' was not found.  Aborting.");
			    }

			    setConfigurationSetting("user_data_files.".$iniInputFileItem['type'].".".$key, $tempFileDetails->getPathname());

		    }
	    }

        LogLine("Loaded all configuration settings from " . $this->arrFileDetails['config_ini']->getPathname(), \C__DISPLAY_SUMMARY__);

	    //
	    // Load the global search data that will be used to create
	    // and configure all searches
	    //
	    $this->_parseGlobalSearchParameters();

	    $this->_instantiateLocationManager();
	    $this->_parseSeleniumParameters();

        //
        // Load Plugin Specific settings from the config
        //
        $this->_parsePluginSettings();

        $this->_parseSearchLocations();

	    if(count(JobSitePluginBuilder::getIncludedJobSites()) == 0)
        {
            LogError("No job site plugins could be loaded for the given search geographic locations.  Aborting.");
            return;
        }

        $this->_parseKeywordSetsFromConfig_();


	    $srchmgr->initializeSearches();

        LogDebug("All INI files loaded. Finalizing configuration for run...", \C__DISPLAY_SECTION_START__);

    }

    private function _instantiateLocationManager()
    {
        $cache = LocationManager::getLocationManager();
        if(empty($cache)) {
            LocationManager::create();
            $cache = LocationManager::getLocationManager();
        }

        return $cache;
    }




    private function _setupPropelForRun()
    {
	    $cfgDatabase = $this->_getSetting("propel.database.connections");
	    if(empty($cfgDatabase))
	    	throw new InvalidArgumentException("No Propel database connection definitions were found in the config files.  You must define at least one connection's settings under propel.database.connections.");
	    foreach ($cfgDatabase as $key => $setting)
	    {
		    $serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
		    $serviceContainer->checkVersion('2.0.0-dev');
		    $serviceContainer->setAdapterClass($key, $setting['adapter']);
		    $manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
		    $manager->setConfiguration(array (
			    'dsn' => $setting['dsn'],
			    'user' => $setting['user'],
			    'password' => $setting['password'],
			    'classname' => '\\Propel\\Runtime\\Connection\\ConnectionWrapper',
			    'model_paths' =>
				    array (
					    0 => 'src',
					    1 => 'vendor',
				    ),
		    ));
		    $manager->setName($key);
		    $serviceContainer->setConnectionManager($key, $manager);
		    $serviceContainer->setDefaultDatasource($key);
	    }


	    LogDebug("Configuring Propel global options and logging...", C__DISPLAY_ITEM_DETAIL__);
        $defaultLogger = $GLOBALS['logger'];
        if(is_null($defaultLogger)) {
            $pathLog = getOutputDirectory('logs') . '/propel-' .getTodayAsString("-").'.log';
            LogLine("Could not find global logger object so configuring propel logging separately at {$pathLog}", C__DISPLAY_WARNING__);
            $defaultLogger = new Logger('defaultLogger');
            $defaultLogger->pushHandler(new StreamHandler($pathLog, Logger::DEBUG));
            $defaultLogger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
        }

        $serviceContainer = Propel::getServiceContainer();
        $serviceContainer->setLogger('defaultLogger', $defaultLogger);
        if(isDebug()) {
            $con = Propel::getWriteConnection(\JobScooper\DataAccess\Map\JobPostingTableMap::DATABASE_NAME);
            $con->useDebug(true);
            LogLine("Enabled debug logging for Propel.", C__DISPLAY_ITEM_DETAIL__);
        }
    }

	private function _getSetting($keyPath)
	{
		if(is_array($keyPath))
		{
			$ret = array();
			foreach ($keyPath as $key) {
				$ret[$key] = $this->_getSetting($key);
			}
			return $ret;
		}

		return getConfigurationSetting("config_file_settings." . $keyPath);
	}

    private function _parsePluginSettings()
    {
        LogLine("Loading plugin setup information from config file...", \C__DISPLAY_ITEM_START__);

        setConfigurationSetting("plugin_specific_settings", $this->_getSetting("plugin_specific_settings"));
    }

    private function _parseGlobalSearchParameters()
    {
        LogLine("Loading global search settings from config file...", \C__DISPLAY_ITEM_START__);

	    $gsoset = $this->_getSetting('global_search_options');
		if(!empty($gsoset))
		{
            // This must happen first so that the search locations can be geocoded
            if (array_key_exists('google_maps_api_key', $gsoset)) {
                setConfigurationSetting('google_maps_api_key', $gsoset['google_maps_api_key']);
            }

            $allJobSitesByKey = JobSitePluginBuilder::getAllJobSites(false);
            foreach ($gsoset as $gsoKey => $gso)
            {
                if(!empty($gso))
                {
                    switch (strtoupper($gsoKey))
                    {
                        case 'EXCLUDED_JOBSITES':
                            if (is_string($gso)) {
                                $gso = preg_split("/\s*,\s*/", $gso);
	                            $gso = array_combine(array_values($gso), $gso);
                            }
                            if (!is_array($gso)) {
                                $gso = array($gso => $gso);
                            }

                            $excludedSiteList = array_intersect_key($allJobSitesByKey, $gso);
                            JobSitePluginBuilder::setSitesAsExcluded($setExcluded=$excludedSiteList);
                            break;

                        default:
                            setConfigurationSetting($gsoKey, $gso);
                            break;
                    }
               }
            }
        }
    }

    private function _parseSeleniumParameters()
    {
        LogDebug("Loading Selenium settings from config file...", \C__DISPLAY_ITEM_START__);
        $settings = $this->_getSetting("selenium");


	    $settings['autostart'] = filter_var($settings['autostart'], FILTER_VALIDATE_BOOLEAN);

        if (!array_key_exists('server', $settings)) {
            throw new \ErrorException("Configuration missing for [selenium] [server] in the config INI files.");
        }
        elseif (strcasecmp("localhost", $settings['server']) === 0)
        {
            throw new \ErrorException("Invalid server value for [selenium] [server] in the config INI files. You must use named hosts, not localhost.");
        }

        if (!array_key_exists('port', $settings))
	        $settings['port'] = "80";

	    $settings['host_location'] = 'http://' . $settings['server'] . ":" . $settings['port'];

	    setConfigurationSetting("selenium", $settings);

    }

    private function _parseSearchLocations()
    {

    	$searchLocs = $this->_getSetting("search_locations.location");

    	if(!empty($searchLocs) && is_array($searchLocs)) {
		    foreach ($searchLocs as $location_string) {

			    if (!$location_string) throw new \ErrorException("Invalid configuration: search location value was empty.");

			    $locmgr = $this->_instantiateLocationManager();
			    $location = $locmgr->getAddress($location_string);
			    if (!empty($location)) {
				    setConfigurationSetting('search_locations.' . $location->getGeoLocationId(), $location);
				    if (!empty($location->getCountryCode()))
					    setConfigurationSetting('country_codes.' . $location->getCountryCode(), $location->getCountryCode());
			    }
		    }
	    }
    }

    private function _parseKeywordSetsFromConfig_()
    {
        LogLine("Loading search keywords from config...", \C__DISPLAY_ITEM_START__);

        $verifiedSets = array();
        $iniSets = $this->_getSetting("search_keyword_set");
        if (!empty($iniSets)) {
            foreach ($iniSets as $key => $ini_keyword_set) {
	            $setKey = 'ks' . (count($verifiedSets['keyword_sets']) + 1) . "_" . $key;
	            $ini_keyword_set['key'] = $setKey;
	            $kwdset = $this->_getUserKeywordSet_($ini_keyword_set);
	            if(!empty($kwdset))
		            $verifiedSets[$kwdset->getUserKeywordSetKey()] = $kwdset;
	            else
	            	throw new \Exception("Unable to create a user keyword set for the values defined in the config file (" . var_dump($ini_keyword_set, true));
            }
	        LogDebug("Added keyword sets: " . join(", ", array_keys($verifiedSets)) , \C__DISPLAY_ITEM_DETAIL__);
			setConfigurationSetting('user_keyword_sets', $verifiedSets);
        }
    }


    private function _parseUsers()
    {
    	LogLine("Configuring users and alerts...");

        setConfigurationSetting('alerts.configuration.smtp', $this->_getSetting("alerts.configuration.smtp"));

        $alertsUsers = $this->_getSetting(array("alerts.errors.to", "alerts.errors.from", "alerts.results.from", "alerts.results.to"));
        if(empty($alertsUsers))
        	return;

	    $configUsers = array_unique_multidimensional($alertsUsers);

        foreach($configUsers as $alertkind => $cfgusr)
        {
        	if(empty($cfgusr))
		        throw new \ErrorException("Missing user settings for " . $alertkind .  ". Aborting.");

	        $user = UserQuery::create()
                ->filterByEmailAddress($cfgusr['email'])
                ->findOneOrCreate();

            $user->setEmailAddress($cfgusr['email']);
	        if(!empty($cfgusr['display_name']))
                $user->setName($cfgusr['display_name']);
	        else
		        $user->setName(preg_replace("/(@.*)/", "", $cfgusr['email']));

            $user->save();

            setConfigurationSetting($alertkind, $user);
            if(strcasecmp($alertkind, "alerts.results.to") == 0)
	            $user->setCurrentUser($user);

        }

	    $curuser = User::getCurrentUser();
        if (empty($curuser))
            throw new \ErrorException("No email address or user has been set in the configuration files for this run.  Aborting.");
    }


	/**
	 * @param array $iniKeywordSetup
	 *
	 * @return \JobScooper\DataAccess\UserKeywordSet|null
	 */
	private function _getUserKeywordSet_($iniKeywordSetup)
    {
	    // If keywords are in a string, split them out into an array instead before continuing
	    if(!empty($iniKeywordSetup) && array_key_exists("keywords", $iniKeywordSetup))
	    {
	    	$user = User::getCurrentUser();

	    	$keyword_list = $iniKeywordSetup['keywords'];
		    if(is_string($keyword_list))
			    $keyword_list = preg_split("/\s*,\s*/", $keyword_list);

		    $final_keywd_list = array();
		    foreach($keyword_list as $kwd)
		    {
			    $scrubbedKwd = strScrub($kwd, ADVANCED_TEXT_CLEANUP);
			    $final_keywd_list[$scrubbedKwd] = $scrubbedKwd;
		    }

		    $kwdset = UserKeywordSetQuery::create()
		        ->filterByUserFromUKS($user)
		        ->filterByKeywords($final_keywd_list)
			    ->findOneOrCreate();

		    $kwdset->setSearchKeyFromConfig(!empty($iniKeywordSetup['key'] ? $iniKeywordSetup['key'] : cleanupSlugPart(join(" ", $final_keywd_list))));
		    $kwdset->setKeywords($final_keywd_list);
		    $kwdset->setUserFromUKS($user);
		    $kwdset->save();
			return $kwdset;
        }

        return null;
    }




} 