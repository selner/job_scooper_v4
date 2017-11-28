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



use JobScooper\Manager\LocationManager;
use JobScooper\Manager\LoggingManager;
use const JobScooper\Plugins\Classes\VALUE_NOT_SUPPORTED;
use JobScooper\Utils\SimpleCSV;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use JobScooper\DataAccess\UserSearchRun;
use  \Propel\Runtime\Propel;
use \JobScooper\Utils\Pharse;


$GLOBALS['CACHES'] = array();
$GLOBALS['CACHES']['LOCATION_MANAGER'] = null;
$GLOBALS['CACHES']['GEOCODER_ENABLED'] = true;



class ConfigBuilder
{
    protected $nNumDaysToSearch = -1;
    public $arrFileDetails = array('output' => null, 'output_subfolder' => null, 'config_ini' => null, 'user_input_files_details' => null);
    protected $arrEmailAddresses = null;
    protected $configSettings = array('searches' => null, 'keyword_sets' => null, 'number_days' => VALUE_NOT_SUPPORTED, 'included_sites' => array(), 'excluded_sites' => array());
    protected $arrEmail_PHPMailer_SMTPSetup = null;
    protected $allConfigFileSettings = null;

    function getSMTPSettings()
    {
        if (isset($this->arrEmail_PHPMailer_SMTPSetup)) {
            return $this->arrEmail_PHPMailer_SMTPSetup;
        } else return null;
    }

    function getInputFilesByType($strInputDataType)
    {
        $ret = $this->__getInputFilesByValue__('data_type', $strInputDataType);

        return $ret;
    }

    function getFileDetails($file_key_name, $strSubDataType = null)
    {
        if ($file_key_name == 'user_input_files_details') {
            return $this->getInputFilesByType($strSubDataType);
        } else {
            return $this->arrFileDetails[$file_key_name];
        }
    }

    /*
     * returns a record if it found a match; null otherwise.
     */
    function getEmailsByType($strType)
    {
        $retArr = null;

        if ($this->arrEmailAddresses) {
            foreach ($this->arrEmailAddresses as $email) {
                if (strcasecmp($email['type'], $strType) == 0) {
                    $emailRecord = $email;
                    $retArr[$email['address']] = $emailRecord;
                }

            }
        }
        return $retArr;
    }

    function getEmailRecords($strEmailKind, $addressType)
    {
        $retArr = null;

        $retEmails = array_filter($this->allConfigFileSettings['emails'], function ($var) use ($strEmailKind, $addressType) {
            return (strcasecmp($var['emailkind'], $strEmailKind) == 0 && strcasecmp($var['type'], $addressType) == 0);
        });
        return array_unique($retEmails);
    }


    function initialize()
    {
        $GLOBALS['USERDATA'] = array();
        $GLOBALS['USERDATA']['configuration_settings'] = array();

        // Do a quick & dirty arg parse so that we can pull out the debug fact right away
        //
        global $argv;

        # now do the actual option parsing
        # cheaply parse the args into $key => $val
        $arg_string = trim(implode($argv, ' '));
        $arg_string = preg_replace(array('/\s--/', '/\s-/'), ' ||||', $arg_string);
        $args       = explode('||||', $arg_string);
        unset($args[0]);
        $argPairs = array();
        foreach($args as $arg)
        {
            $pair = preg_split("/[\s=]/", $arg);
            $argPairs[strtolower($pair[0])] = (empty($pair[1]) ? 1 : $pair[1]);
        }

        if(array_key_exists("debug", $argPairs))
            $GLOBALS['USERDATA']['configuration_settings']["debug"] = filter_var($argPairs['debug'], FILTER_VALIDATE_BOOLEAN);



        $envDirOut = getenv('JOBSCOOPER_OUTPUT');
        if(is_null($envDirOut) || strlen($envDirOut) == 0)
            $envDirOut = sys_get_temp_dir();
        $outputDirectoryDetails = getFilePathDetailsFromString($envDirOut, C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);

        $rootdir = realpath(dirname(dirname(dirname(dirname(__FILE__)))));

        __initializeArgs__($rootdir);

        $cmdLineOpts = Pharse::options($GLOBALS['OPTS_SETTINGS']);

        LogLine("Initializing application using the passed command line switches: " . getArrayValuesAsString($cmdLineOpts));

        # After you've configured Pharse, run it like so:
        $GLOBALS['USERDATA']['OPTS'] = $cmdLineOpts;
        LogDebug("Setting up application... ", \C__DISPLAY_SECTION_START__);

        $GLOBALS['USERDATA']['companies_regex_to_filter'] = null;

        $now = new \DateTime();
        $GLOBALS['USERDATA']['configuration_settings']['app_run_id'] = __APP_VERSION__ . "-".$now->format('YmdHi');

        // and to make sure our notes get updated on active jobs
        // that we'd seen previously
        // Now go see what we got back for each of the sites
        //
        foreach ($GLOBALS['JOBSITE_PLUGINS'] as $site) {
            assert(isset($site['name']));
            $GLOBALS['JOBSITE_PLUGINS'][$site['name']]['include_in_run'] = is_OptionIncludedSite($site['name']);
        }
        $includedsites = array_filter($GLOBALS['JOBSITE_PLUGINS'], function ($k) {
            return $k['include_in_run'];
        });
        $excludedsites = array_filter($GLOBALS['JOBSITE_PLUGINS'], function ($k) {
            return !($k['include_in_run']);
        });

        $keys = array();
        foreach (array_keys($includedsites) as $k)
            $keys[] = strtolower($k);

        $GLOBALS['USERDATA']['configuration_settings']['included_sites'] = array_combine($keys, array_column($includedsites, 'name'));
        $keys = array();
        foreach (array_keys($excludedsites) as $k)
            $keys[] = strtolower($k);

        $GLOBALS['USERDATA']['configuration_settings']['excluded_sites'] = array_combine($keys, array_column($excludedsites, 'name'));
        $GLOBALS['USERDATA']['configuration_settings']['country_codes'] = array();

        
        
        // Override any INI file setting with the command line output file path & name
        // the user specificed (if they did)
        $cmdlineOutDir = get_FileDetails_fromPharseOption("output", false);
        if ($cmdlineOutDir['has_directory']) {
            $outputDirectoryDetails = $cmdlineOutDir;
        }

        if ($GLOBALS['USERDATA']['OPTS']['use_config_ini_given']) {
            $this->arrFileDetails['config_ini'] = set_FileDetails_fromPharseSetting("use_config_ini", 'config_file_details', true);
            $name = str_replace(DIRECTORY_SEPARATOR, "", $this->arrFileDetails['config_ini']['directory']);
            $name = substr($name, max([strlen($name) - 31 - strlen(".ini"), 0]), 31);
            $GLOBALS['USERDATA']['user_unique_key'] = md5($name);
        } else {
            $GLOBALS['USERDATA']['user_unique_key'] = uniqid("unknown");
        }

        // Now setup all the output folders
        $this->__setupOutputFolders__($outputDirectoryDetails['directory']);

        $strOutfileArrString = getArrayValuesAsString($GLOBALS['USERDATA']['directories']);
        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Output folders configured: " . $strOutfileArrString, \C__DISPLAY_ITEM_DETAIL__);

        if ($GLOBALS['USERDATA']['OPTS']['use_config_ini_given']) {
            if (!isset($GLOBALS['logger'])) $GLOBALS['logger'] = new LoggingManager($this->arrFileDetails['config_ini']['directory']);
            LogLine("Logging file for run being written to: " . $this->arrFileDetails['config_ini']['directory'], \C__DISPLAY_ITEM_DETAIL__);

            LogLine("Loading configuration file details from " . $this->arrFileDetails['config_ini']['full_file_path'], \C__DISPLAY_ITEM_DETAIL__);

            $this->_LoadAndMergeAllConfigFilesRecursive($this->arrFileDetails['config_ini']['full_file_path']);

            if (isset($this->arrFileDetails['config_ini'])) {
                if (!is_file($this->arrFileDetails['config_ini']['full_file_path'])) {
                    $altFileDetails = null;
                    $altFileDetails = parseFilePath($this->arrFileDetails['config_ini']['full_file_path']);
                    if (!is_dir($altFileDetails['config_ini']['directory'])) {
                        if (is_dir(is_file($altFileDetails['config_ini']['full_file_path']))) {
                            parseFilePath($this->arrFileDetails['config_ini']['directory'] . "/" . $altFileDetails['config_ini']['filename']);
                        }
                    }

                }
            }

            $this->_setupRunFromConfig_($this->allConfigFileSettings);

        }


        LogDebug("Configuring specific settings for this run... ", \C__DISPLAY_SECTION_START__);

        $GLOBALS['USERDATA']['configuration_settings']['number_days'] = get_PharseOptionValue('number_days');
        if ($GLOBALS['USERDATA']['configuration_settings']['number_days'] === false) {
            $GLOBALS['USERDATA']['configuration_settings']['number_days'] = 1;
        }
        LogDebug($GLOBALS['USERDATA']['configuration_settings']['number_days'] . " days configured for run. ", \C__DISPLAY_ITEM_DETAIL__);

        LogLine("Completed configuration load.", \C__DISPLAY_SUMMARY__);

    }

    function getLogger()
    {
        return $GLOBALS['logger'];
    }

    private function __setupOutputFolders__($outputDirectory)
    {
        if (!$outputDirectory) {
            throw new \ErrorException("Required value for the output folder was not specified. Exiting.");
        }

        $globalDirs = ["debug", "logs"];
        foreach ($globalDirs as $d) {
            $path = join(DIRECTORY_SEPARATOR, array($outputDirectory, getTodayAsString("-"), $d));
            $details = getFilePathDetailsFromString($path, \C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
            $GLOBALS['USERDATA']['directories'][$d] = realpath($details['directory']);
        }

        $userWorkingDirs = ["notifications"];
        foreach ($userWorkingDirs as $d) {
            $prefix = $GLOBALS['USERDATA']['user_unique_key'];
            $path = join(DIRECTORY_SEPARATOR, array($outputDirectory, getTodayAsString("-"), $d, $prefix));
            $details = getFilePathDetailsFromString($path, \C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
            $GLOBALS['USERDATA']['directories'][$d] = realpath($details['directory']);
        }

        $path = $outputDirectory;
        $details = getFilePathDetailsFromString($path, \C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
        $GLOBALS['USERDATA']['directories']['root'] = realpath($details['directory']);

        $GLOBALS['logger']->addFileHandlers(getOutputDirectory('logs'));
    }


    private function _LoadAndMergeAllConfigFilesRecursive($fileConfigToLoad)
    {
        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading INI file: " . $fileConfigToLoad, \C__DISPLAY_SECTION_START__);

        $GLOBALS['USERDATA']['configuration_settings']['config_files'] = array();
        $GLOBALS['USERDATA']['configuration_settings']['config_files'][] = $fileConfigToLoad;

        $iniParser = new \IniParser($fileConfigToLoad);
        $iniParser->use_array_object = false;
        $tempConfigSettings = $iniParser->parse();
        $iniParser = null;
        if (!array_key_exists($fileConfigToLoad, $this->_arrConfigFileSettings_)) {

            $this->_arrConfigFileSettings_[$fileConfigToLoad] = array_copy($tempConfigSettings);

            if (isset($tempConfigSettings['settings_files'])) {
                foreach ($tempConfigSettings['settings_files'] as $nextConfigFile) {
                    if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Recursing into child settings file " . $nextConfigFile, \C__DISPLAY_ITEM_DETAIL__);
                    $this->_LoadAndMergeAllConfigFilesRecursive($nextConfigFile);
                    if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added child settings file " . $nextConfigFile, \C__DISPLAY_ITEM_RESULT__);
                }
            }

            $allConfigfileSettings = [];
            foreach ($this->_arrConfigFileSettings_ as $tempConfig) {
                $allConfigfileSettings = array_merge_recursive($allConfigfileSettings, $tempConfig);
            }

            $this->allConfigFileSettings = $allConfigfileSettings;
        }

    }


    private function _setupRunFromConfig_($config)
    {


        if (isset($config['input']) && isset($config['input']['folder'])) {
            $this->arrFileDetails['input_folder'] = parseFilePath($config['input']['folder']);
        }

        if (isset($config['inputfiles'])) {
            foreach ($config['inputfiles'] as $iniInputFile) {
                $strFileName = "ERROR-UNKNOWN";
                if (isset($iniInputFile['path']) && strlen($iniInputFile['path']) > 0) {
                    $strFileName = $iniInputFile['path'];
                } elseif (isset($iniInputFile['filename']) && strlen($iniInputFile['filename']) > 0)
                    $strFileName = $iniInputFile['filename'];

                if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Processing input file '" . $strFileName . "' with type of '" . $iniInputFile['type'] . "'...", \C__DISPLAY_NORMAL__);
                $this->__addInputFile__($iniInputFile);
            }
        }
        //
        // Load the global search data that will be used to create
        // and configure all searches
        //
        $this->_parseGlobalSearchParametersFromConfig_($config);
        $this->_instantiateLocationManager();

        LogLine("Loaded all configuration settings" . isDebug() ? ": " . PHP_EOL . var_export($this->allConfigFileSettings, true) : "", \C__DISPLAY_SUMMARY__);


        $this->_setupPropelForRun();

        $this->_parseEmailSetupFromINI_($config);

        $this->_parseSeleniumParametersFromConfig_($config);

        //
        // Load Plugin Specific settings from the config
        //
        $this->_parsePluginSettingsFromConfig_($config);

        if(count($GLOBALS['USERDATA']['configuration_settings']['included_sites']) == 0)
        {
            LogError("No plugins could be found for the user's searches.  Aborting.");
            return;
        }

        $this->_parseKeywordSetsFromConfig_($config);


        LogDebug("All INI files loaded. Finalizing configuration for run...", \C__DISPLAY_SECTION_START__);
//
//        //
//        // Create searches needed to run all the keyword sets
//        //
//        $this->_addSearchesForKeywordSets_();
//
//        //
//        // Finally create the instances of user search runs
//        // that we will use during the run
//        //
//        $this->_createSearchInstancesForRun();

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



    private function __getEmptyEmailRecord__()
    {
        return array('emailkind' => null, 'type' => null, 'name' => null, 'address' => null);
    }

    private function __addInputFile__($iniInputFileItem)
    {

        $tempFileDetails = null;
        if (isset($iniInputFileItem['path'])) {
            $tempFileDetails = parseFilePath($iniInputFileItem['path'], true);

        } elseif (isset($iniInputFileItem['filename'])) {
            $tempFileDetails = parseFilePath($this->arrFileDetails['input_folder']['directory'] . $iniInputFileItem['filename'], true);
        }

        if (!is_file($tempFileDetails['full_file_path'])) {
            throw new \Exception("Specified input file '" . $tempFileDetails['full_file_path'] . "' was not found.  Aborting.");
        }


        if (isset($tempFileDetails)) {

            $GLOBALS['USERDATA']['user_input_files_details'][] = array('details' => $tempFileDetails, 'data_type' => $iniInputFileItem['type']);
        }
    }

    private function _setupPropelForRun()
    {
        LogDebug("Configuring Propel global options and logging...", C__DISPLAY_ITEM_DETAIL__);
        $defaultLogger = $this->getLogger();
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

    private function __getInputFilesByValue__($valKey, $val)
    {
        $ret = null;
        if (isset($GLOBALS['USERDATA']['user_input_files_details']) && (is_array($GLOBALS['USERDATA']['user_input_files_details']) || is_array($GLOBALS['USERDATA']['user_input_files_details']))) {
            foreach ($GLOBALS['USERDATA']['user_input_files_details'] as $fileItem) {
                if (strcasecmp($fileItem[$valKey], $val) == 0) {
                    $ret[] = $fileItem;
                }
            }
        }
        return $ret;
    }

    private function _parsePluginSettingsFromConfig_($config)
    {
        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading plugin setup information from config file...", \C__DISPLAY_ITEM_START__);

        if (array_key_exists('plugin_settings', $config) == true && is_array($config['plugin_settings']) && count($config['plugin_settings']) > 0) {
            //
            // plugin setting config items are structured like this:
            //      [plugin_settings.usajobs]
            //      authorization_key="XxXxXxXxXxXxXxXxXxXx="
            foreach (array_keys($config['plugin_settings']) as $pluginname) {
                if (array_key_exists($pluginname, $GLOBALS['JOBSITE_PLUGINS'])) {
                    foreach (array_keys($config['plugin_settings'][$pluginname]) as $settingkey) {
                        $GLOBALS['JOBSITE_PLUGINS'][$pluginname]['other_settings'][$settingkey] = $config['plugin_settings'][$pluginname][$settingkey];
                        $GLOBALS['USERDATA']['configuration_settings']['plugin_settings'][$pluginname][$settingkey] = $config['plugin_settings'][$pluginname][$settingkey];
                    }
                }
            }
        }
    }

    private function _parseGlobalSearchParametersFromConfig_($config)
    {
        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading global search settings from config file...", \C__DISPLAY_ITEM_START__);

        if (array_key_exists('global_search_options', $config)) {

            // This must happen first so that the search locations can be geocoded
            if (array_key_exists('google_maps_api_key', $config['global_search_options'])) {
                $GLOBALS['USERDATA']['configuration_settings']['google_maps_api_key'] = $config['global_search_options']['google_maps_api_key'];
            }

            foreach (array_keys($config['global_search_options']) as $gso)
            {
                if(!is_null($config['global_search_options'][$gso]) && isset($config['global_search_options'][$gso]))
                {
                    switch (strtoupper($gso))
                    {
                        case 'EXCLUDED_JOBSITES':
                            if (is_string($config['global_search_options']['excluded_jobsites'])) {
                                $config['global_search_options']['excluded_jobsites'] = explode(",", $config['global_search_options']['excluded_jobsites']);
                            }
                            if (!is_array($config['global_search_options']['excluded_jobsites'])) {
                                $config['global_search_options']['excluded_jobsites'] = array($config['global_search_options']['excluded_jobsites']);
                            }
                            foreach ($config['global_search_options']['excluded_jobsites'] as $excludedSite) {
                                setSiteAsExcluded($excludedSite);
                            }
                            break;

                        case 'SEARCH_LOCATION':
                            if (!is_array($config['global_search_options'][$gso]))
                                $config['global_search_options'][$gso] = array($config['global_search_options'][$gso]);
                            foreach ($config['global_search_options'][$gso] as $search_location)
                            {
                                $this->_configureSearchLocation_($search_location);
                            }
                        break;

                        default:
                            $GLOBALS['USERDATA']['configuration_settings'][$gso] = $config['global_search_options'][$gso];
                            break;
                    }
               }
            }
        }
    }

    private function _parseSeleniumParametersFromConfig_($config)
    {
        LogDebug("Loading Selenium settings from config file...", \C__DISPLAY_ITEM_START__);
        if (isset($config['selenium']) && is_array($config['selenium'])) {
            foreach (array_keys($config['selenium']) as $k)
                $GLOBALS['USERDATA']['configuration_settings']['selenium'][$k] = trim($config['selenium'][$k]);
        }

//        if (!((array_key_exists('autostart', $GLOBALS['USERDATA']['configuration_settings']['selenium']) === true && array_key_exists('port', $GLOBALS['USERDATA']['configuration_settings']['selenium']) === true ) || array_key_exists('start_command', $GLOBALS['USERDATA']['configuration_settings']['selenium']) === true ))
//            throw new \Exception("Required parameters for Selenium are missing; app cannot start.  You must set either 'autostart' and 'port' or 'start_command' in your configuration files.");
        $settings = getConfigurationSettings('selenium');

        $GLOBALS['USERDATA']['configuration_settings']['selenium']['autostart'] = filter_var($settings['autostart'], FILTER_VALIDATE_BOOLEAN);
//
//        if(! array_key_exists('start_command', $GLOBALS['USERDATA']['configuration_settings']['selenium']) === true ) {
//            if ($GLOBALS['USERDATA']['configuration_settings']['selenium']['autostart'] == 1 && !(array_key_exists('jar', $GLOBALS['USERDATA']['configuration_settings']['selenium']) === true && array_key_exists('postfix_switches', $GLOBALS['USERDATA']['configuration_settings']['selenium']) === true))
//                throw new \Exception("Required parameters to autostart Selenium are missing; you must set both 'jar' and 'postfix_switches' in your configuration files.");
//        }

        if (!(array_key_exists('server', $GLOBALS['USERDATA']['configuration_settings']['selenium']) === true)) {
            throw new \ErrorException("Configuration missing for [selenium] [server] in the config INI files.");
        }
        elseif (strcasecmp("localhost", $GLOBALS['USERDATA']['configuration_settings']['selenium']['server']) === 0)
        {
            throw new \ErrorException("Invalid server value for [selenium] [server] in the config INI files. You must use named hosts, not localhost.");
        }

        if (!(array_key_exists('port', $GLOBALS['USERDATA']['configuration_settings']['selenium']) === true))
            $GLOBALS['USERDATA']['configuration_settings']['selenium']['port'] = "80";

        $GLOBALS['USERDATA']['configuration_settings']['selenium']['host_location'] = 'http://' . $GLOBALS['USERDATA']['configuration_settings']['selenium']['server'] . ":" . $GLOBALS['USERDATA']['configuration_settings']['selenium']['port'];

    }
    private function _configureSearchLocation_($location_string)
    {
        if (!$location_string) throw new \ErrorException("Invalid configuration: search location value was empty.");
        if (!array_key_exists('search_location', $GLOBALS['USERDATA']['configuration_settings']))
            $GLOBALS['USERDATA']['configuration_settings']['search_locations'] = array();

        $locmgr = $this->_instantiateLocationManager();
        $location = $locmgr->getAddress($location_string);
        if(!empty($location))
        {
            $GLOBALS['USERDATA']['configuration_settings']['search_location'][$location->getGeoLocationId()] = array(
                'location_raw_source_value' => $location_string,
                'location_name_key' => $location->getSlug(),
                'location' => $location,
                'location_id' => $location->getGeoLocationId());

            if (!is_null($location->getCountryCode()))
                $GLOBALS['USERDATA']['configuration_settings']['country_codes'][$location->getCountryCode()] = $location->getCountryCode();
        }

    }

    private function _parseKeywordSetsFromConfig_($config)
    {
        LogLine("Loading search keywords from config...", \C__DISPLAY_ITEM_START__);
        if (!array_key_exists('keyword_sets', $GLOBALS['USERDATA']['configuration_settings'])) {
            $GLOBALS['USERDATA']['configuration_settings']['keyword_sets'] = array();
        }
        if (isset($config['search_keyword_set'])) {
            foreach (array_keys($config['search_keyword_set']) as $key) {
                $ini_keyword_set = $config['search_keyword_set'][$key];
                $ini_keyword_set['key'] = $key;

                $strSetKey = 'ks' . (count($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) + 1);
                if (isset($ini_keyword_set['name']) && strlen($ini_keyword_set['name']) > 0) {
                    $strSetKey = $ini_keyword_set['name'];
                } elseif (isset($ini_keyword_set['key']) && strlen($ini_keyword_set['key']) > 0) {
                    $strSetKey = $ini_keyword_set['key'];
                }

                $GLOBALS['USERDATA']['configuration_settings']['keyword_sets'][$strSetKey] = $this->_getNewKeywordSettingsSet_(strtolower($strSetKey), $ini_keyword_set);

                LogDebug("Added keyword set '" . $GLOBALS['USERDATA']['configuration_settings']['keyword_sets'][$strSetKey]['name'] . "' with keywords = " . getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['keyword_sets'][$strSetKey]['keywords_array']) , \C__DISPLAY_ITEM_DETAIL__);

            }

        }
    }






    private function _parseEmailSetupFromINI_($config)
    {
        $settingsEmail = array();

        if(isset($config['email'] ))
        {
            if($config['email']['smtp'])
            {
                $settingsEmail['PHPMailer_SMTPSetup'] = $config['email']['smtp'];
            }
        }

        if(isset($config['emails'] ))
        {
            foreach(array_keys($config['emails']) as $emailKey)
            {
                $emailItem = $config['emails'][$emailKey];

                $tempEmail = $this->__getEmptyEmailRecord__();
                $tempEmail['key'] = $emailKey;

                foreach(array_keys($emailItem) as $key)
                {
                    if (isset($emailItem[$key])) {
                        $tempEmail[$key] = $emailItem[$key];
                    }
                }
                if (isset($emailItem['name'])) {
                    $tempEmail['name'] = $emailItem['name'];
                }
                if (isset($emailItem['address'])) {
                    $tempEmail['address'] = $emailItem['address'];
                }
                if (isset($emailItem['type'])) {
                    $tempEmail['type'] = $emailItem['type'];
                }
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added email from config.ini: '" . getArrayValuesAsString($tempEmail), \C__DISPLAY_ITEM_DETAIL__);
                $settingsEmail['email_addresses'][$emailKey] = $tempEmail;
            }
        }

        $GLOBALS['USERDATA']['configuration_settings']['email'] = array_copy($settingsEmail);

        $userDetails = array();
        $userDetails['ConfigFilePath'] = $this->arrFileDetails['config_ini']['full_file_path'];
        $emails = $this->getEmailRecords("results", "to");
        foreach($emails as $email)
        {
            if (array_key_exists('address', $email)) {
                $userDetails['Name'] = $email['name'];
                $userDetails['EmailAddress'] = $email['address'];
                break;
            }
        }

        if (!array_key_exists('EmailAddress', $userDetails)) {
            throw new \ErrorException("No email address or user has been set in the configuration files for this run.  Aborting.");
        }

        $retUserData = updateOrCreateUser($userDetails);
        $GLOBALS['USERDATA']['configuration_settings']['user_details'] = $retUserData->copy();



    }

    private $_arrConfigFileSettings_ = [];

    private function _getNewKeywordSettingsSet_($setkey=null, $iniKeywordSetup)
    {
         $set =  array(
            'key' => $setkey,
            'name' =>  $setkey,
            'source_config_file_settings' => array_copy($iniKeywordSetup),
            'keywords_array' => null
        );

        // If keywords are in a string, split them out into an array instead before continuing
        if(isset($iniKeywordSetup['keywords']) && is_string($iniKeywordSetup['keywords']))
        {
            $tmpKeywordArray = explode(",", $iniKeywordSetup['keywords']);
            $arrKeywords = array();
            foreach($tmpKeywordArray as $kwd)
            {
                $scrubbedKwd = strScrub($kwd, ADVANCED_TEXT_CLEANUP);
                $arrKeywords[$scrubbedKwd] = $scrubbedKwd;
            }
            $set['keywords_array'] = $arrKeywords;

            if(isset($set['keywords_array']) && is_array($set['keywords_array']) && count($set['keywords_array']) > 0)
            {
                $set['keywords_array_tokenized']  = tokenizeKeywords($set['keywords_array']);
            }
        }

        return $set;
    }




} 