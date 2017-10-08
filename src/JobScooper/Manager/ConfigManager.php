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



use const JobScooper\Plugins\lib\VALUE_NOT_SUPPORTED;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use JobScooper\DataAccess\UserSearchRun;
use  \Propel\Runtime\Propel;


class ConfigManager
{
    protected $nNumDaysToSearch = -1;
    public $arrFileDetails = array('output' => null, 'output_subfolder' => null, 'config_ini' => null, 'user_input_files_details' => null);
    protected $arrEmailAddresses = null;
    protected $configSettings = array('searches' => null, 'keyword_sets' => null, 'location_sets' => null, 'number_days' => VALUE_NOT_SUPPORTED, 'included_sites' => array(), 'excluded_sites' => array());
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
        return $retEmails;
    }


    function initialize()
    {
        # increase memory consumed to fit larger job searches
        print "Starting memory is " . getPhpMemoryUsage() . PHP_EOL;

        ini_set('memory_limit', '1024M');
        ini_set("auto_detect_line_endings", true);
        $envDirOut = getenv('JOBSCOOPER_OUTPUT');
        if(is_null($envDirOut) || strlen($envDirOut) == 0)
            $envDirOut = sys_get_temp_dir();
        $outputDirectoryDetails = getFilePathDetailsFromString($envDirOut, C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);

        $GLOBALS['USERDATA'] = array();
        $rootdir = realpath(dirname(dirname(dirname(dirname(__FILE__)))));

        __initializeArgs__($rootdir);

        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Setting up application... ", \C__DISPLAY_SECTION_START__);
        # After you've configured Pharse, run it like so:
        $GLOBALS['OPTS'] = \Pharse::options($GLOBALS['OPTS_SETTINGS']);

        $GLOBALS['USERDATA']['companies_regex_to_filter'] = null;
        $GLOBALS['USERDATA']['configuration_settings'] = array();

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
        $GLOBALS['USERDATA']['configuration_settings']['searches'] = array();
        $GLOBALS['USERDATA']['configuration_settings']['country_codes'] = array();

        
        
        // Override any INI file setting with the command line output file path & name
        // the user specificed (if they did)
        $cmdlineOutDir = get_FileDetails_fromPharseOption("output", false);
        if ($cmdlineOutDir['has_directory']) {
            $outputDirectoryDetails = $cmdlineOutDir;
        }

        if ($GLOBALS['OPTS']['use_config_ini_given']) {
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

        if ($GLOBALS['OPTS']['use_config_ini_given']) {
            if (!isset($GLOBALS['logger'])) $GLOBALS['logger'] = new \LoggingManager($this->arrFileDetails['config_ini']['directory']);
            if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Log file for run being written to: " . $this->arrFileDetails['config_ini']['directory'], \C__DISPLAY_ITEM_DETAIL__);

            if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading configuration file details from " . $this->arrFileDetails['config_ini']['full_file_path'], \C__DISPLAY_ITEM_DETAIL__);

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


        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Configuring specific settings for this run... ", \C__DISPLAY_SECTION_START__);

        $GLOBALS['USERDATA']['configuration_settings']['number_days'] = get_PharseOptionValue('number_days');
        if ($GLOBALS['USERDATA']['configuration_settings']['number_days'] === false) {
            $GLOBALS['USERDATA']['configuration_settings']['number_days'] = 1;
        }
        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine($GLOBALS['USERDATA']['configuration_settings']['number_days'] . " days configured for run. ", \C__DISPLAY_ITEM_DETAIL__);

//        $this->_setPreviouslyReviewedJobsInputFiles__setPreviouslyReviewedJobsInputFiles_();

        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Completed configuration load.", \C__DISPLAY_SUMMARY__);

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

        $GLOBALS['logger']->addFileHandler(getOutputDirectory('logs'));
    }


    private function _LoadAndMergeAllConfigFilesRecursive($fileConfigToLoad)
    {
        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading INI file: " . $fileConfigToLoad, \C__DISPLAY_SECTION_START__);

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

        LogLine("Loaded all configuration settings" . isDebug() ? ": " . PHP_EOL . var_export($this->allConfigFileSettings, true) : "", \C__DISPLAY_SUMMARY__);


        $this->_setupPropelForRun();

        $this->_parseEmailSetupFromINI_($config);

        $this->_parseSeleniumParametersFromConfig_($config);

        //
        // Load Plugin Specific settings from the config
        //
        $this->_parsePluginSettingsFromConfig_($config);

        $this->_parseKeywordSetsFromConfig_($config);

        $this->_parseLocationSetsFromConfig_($config);


        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("All INI files loaded. Finalizing configuration for run...", \C__DISPLAY_SECTION_START__);

        //
        // Create searches needed to run all the keyword sets
        //
        $this->_addSearchesForKeywordSets_();

        //
        // Finally create the instances of user search runs
        // that we will use during the run
        //
        $this->_createSearchInstancesForRun();

        //
        // Load the exclusion filter and other user data from files
        //
        $this->_loadTitlesTokensToFilter_();

        $this->_loadCompanyRegexesToFilter_();
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
        LogLine("Configuring Propel global options and logging...", C__DISPLAY_ITEM_DETAIL__);
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
        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading Selenium settings from config file...", \C__DISPLAY_ITEM_START__);
        if (isset($config['selenium']) && is_array($config['selenium'])) {
            foreach (array_keys($config['selenium']) as $k)
                $GLOBALS['USERDATA']['selenium'][$k] = trim($config['selenium'][$k]);
        }

//        if (!((array_key_exists('autostart', $GLOBALS['USERDATA']['selenium']) === true && array_key_exists('port', $GLOBALS['USERDATA']['selenium']) === true ) || array_key_exists('start_command', $GLOBALS['USERDATA']['selenium']) === true ))
//            throw new \Exception("Required parameters for Selenium are missing; app cannot start.  You must set either 'autostart' and 'port' or 'start_command' in your configuration files.");

        $GLOBALS['USERDATA']['selenium']['autostart'] = intceil($GLOBALS['USERDATA']['selenium']['autostart']);
//
//        if(! array_key_exists('start_command', $GLOBALS['USERDATA']['selenium']) === true ) {
//            if ($GLOBALS['USERDATA']['selenium']['autostart'] == 1 && !(array_key_exists('jar', $GLOBALS['USERDATA']['selenium']) === true && array_key_exists('postfix_switches', $GLOBALS['USERDATA']['selenium']) === true))
//                throw new \Exception("Required parameters to autostart Selenium are missing; you must set both 'jar' and 'postfix_switches' in your configuration files.");
//        }

        if (!(array_key_exists('server', $GLOBALS['USERDATA']['selenium']) === true))
            $GLOBALS['USERDATA']['selenium']['server'] = "localhost";


        $GLOBALS['USERDATA']['selenium']['host_location'] = 'http://' . $GLOBALS['USERDATA']['selenium']['server'] . ":" . $GLOBALS['USERDATA']['selenium']['port'];

    }
    private function _configureSearchLocation_($location_string)
    {
        if (!$location_string) throw new \ErrorException("Invalid configuration: search location value was empty.");
        if (!array_key_exists('search_locations', $GLOBALS['USERDATA']['configuration_settings']))
            $GLOBALS['USERDATA']['configuration_settings']['search_locations'] = array();

        $loclookup = findOrCreateLocationLookupFromName($location_string);


        if(!is_null($loclookup))
        {
            $loc = $loclookup->getLocation();
            $GLOBALS['USERDATA']['configuration_settings']['search_locations'][$loc->getLocationKey()] = array(
                'location_raw_source_value' => $location_string,
                'location_name_key' => $loclookup->getSlug(),
                'location_name_lookup' => $loclookup);
        }

    }


    private function _parseLocationSetsFromConfig_($config)
    {
        if (!$config) throw new \ErrorException("Invalid configuration.  Cannot load user's searches.");

        if (!array_key_exists('location_sets', $GLOBALS['USERDATA']['configuration_settings']))
            $GLOBALS['USERDATA']['configuration_settings']['location_sets'] = array();

        if (isset($config['search_location_setting_set'])) {
            if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading search locations from config file...", \C__DISPLAY_ITEM_START__);
            //
            // Check if this is a single search setting or if it's a set of search settings
            //
            $strSettingsName = null;
            if ($config['search_location_setting_set'] && is_array($config['search_location_setting_set'])) {
                foreach ($config['search_location_setting_set'] as $iniSettings) {
                    if (count($iniSettings) > 1) {
                        $arrNewLocationSet = array();
                        $strSetName = 'LocationSet' . (count($GLOBALS['USERDATA']['configuration_settings']['location_sets']) + 1);
                        if (isset($iniSettings['name'])) {
                            if (is_array($iniSettings['name'])) {
                                throw new \Exception("Error: Invalid location set data loaded from configs.  Did you inadvertently assets the same location set [" . $iniSettings['name'][0] . "] twice?");
                            }
                            if (strlen($iniSettings['name']) > 0) {
                                $strSetName = $iniSettings['name'];
                            }
                        } elseif (isset($iniSettings['key']) && strlen($iniSettings['key']) > 0) {
                            $strSetName = $iniSettings['key'];

                        }
                        $strSetName = strScrub($strSetName, FOR_LOOKUP_VALUE_MATCHING);

                        $arrNewLocationSet['key'] = $strSetName;

                        foreach (array_keys($iniSettings) as $loctype) {
                            if (isset($iniSettings[$loctype])) {
                                $arrNewLocationSet[$loctype] = strScrub($iniSettings[$loctype], REMOVE_EXTRA_WHITESPACE);
                            }
                        }

                        $computedLocValues = array();
                        if (array_key_exists("city", $arrNewLocationSet) && array_key_exists("state", $arrNewLocationSet) && array_key_exists("statecode", $arrNewLocationSet) && array_key_exists("country", $arrNewLocationSet) && array_key_exists("countrycode", $arrNewLocationSet)) {
                            $computedLocValues = array(
                                'location-city' => $arrNewLocationSet['city'],
                                'location-city-comma-statecode' => $arrNewLocationSet['city'] . ", " . $arrNewLocationSet['statecode'],
                                'location-city-comma-nospace-statecode' => $arrNewLocationSet['city'] . "," . $arrNewLocationSet['statecode'],
                                'location-city-dash-statecode' => $arrNewLocationSet['city'] . "-" . $arrNewLocationSet['statecode'],
                                'location-city-comma-statecode-underscores-and-dashes' => $arrNewLocationSet['city'] . "__2c-" . $arrNewLocationSet['statecode'],
                                'location-city-comma-state' => $arrNewLocationSet['city'] . ", " . $arrNewLocationSet['state'],
                                'location-city-comma-state-country' => $arrNewLocationSet['city'] . ", " . $arrNewLocationSet['state'] . " " . $arrNewLocationSet['country'],
                                'location-city-comma-state-comma-country' => $arrNewLocationSet['city'] . ", " . $arrNewLocationSet['state'] . ", " . $arrNewLocationSet['country'],
                                'location-city-comma-state-comma-countrycode' => $arrNewLocationSet['city'] . ", " . $arrNewLocationSet['state'] . ", " . $arrNewLocationSet['countrycode'],
                                'location-city-comma-statecode-comma-countrycode' => $arrNewLocationSet['city'] . ", " . $arrNewLocationSet['statecode'] . ", " . $arrNewLocationSet['countrycode'],
                                'location-city-comma-statecode-comma-country' => $arrNewLocationSet['city'] . ", " . $arrNewLocationSet['statecode'] . ", " . $arrNewLocationSet['country'],
                                'location-city-comma-countrycode' => $arrNewLocationSet['city'] . ", " . $arrNewLocationSet['countrycode'],
                                'location-city-comma-country' => $arrNewLocationSet['city'] . ", " . $arrNewLocationSet['country'],
                                'location-city-state-country-no-commas' => $arrNewLocationSet['city'] . " " . $arrNewLocationSet['state'] . " " . $arrNewLocationSet['country'],
                                'location-city-country-no-commas' => $arrNewLocationSet['city'] . " " . $arrNewLocationSet['country'],
                                'location-state' => $arrNewLocationSet['state'],
                                'location-statecode' => $arrNewLocationSet['statecode'],
                                'location-countrycode' => $arrNewLocationSet['countrycode']
                            );

                        }

                        $arrNewLocationSet = array_merge($arrNewLocationSet, $computedLocValues);

                        $strSettingStrings = getArrayValuesAsString($arrNewLocationSet);
                        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added location search settings '" . $strSetName . ": " . $strSettingStrings, \C__DISPLAY_ITEM_DETAIL__);

                        $GLOBALS['USERDATA']['configuration_settings']['location_sets'][$strSetName] = $arrNewLocationSet;

                    }
                }
            }

            if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded " . count($GLOBALS['USERDATA']['configuration_settings']['location_sets']) . " location sets. ", \C__DISPLAY_ITEM_RESULT__);
        }
    }

    private function _parseKeywordSetsFromConfig_($config)
    {
        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading keyword set from config file...", \C__DISPLAY_ITEM_START__);
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

                if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added keyword set '" . $GLOBALS['USERDATA']['configuration_settings']['keyword_sets'][$strSetKey]['name'] . "' with keywords = " . getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['keyword_sets'][$strSetKey]['keywords_array']) , \C__DISPLAY_ITEM_DETAIL__);

            }

        }
    }




    private function _addSearchesForKeywordSets_()
    {
        $arrSearchesPreLocation = array();
        //
        // explode any keyword sets we loaded into separate searches
        //
        // If the keyword settings scope is all sites, then create a search for every possible site
        // so that it runs with the keywords settings if it was included_<site> = true
        //
        if (isset($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) && count($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) > 0) {
            if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Adding new searches for user's keyword sets ", \C__DISPLAY_ITEM_START__);

            foreach ($GLOBALS['USERDATA']['configuration_settings']['keyword_sets'] as $keywordSet) {
                $arrSkippedPlugins = null;
                if (isset($GLOBALS['USERDATA']['configuration_settings']['included_sites']) && count($GLOBALS['USERDATA']['configuration_settings']['included_sites']) > 0)
                {
                    foreach ($GLOBALS['USERDATA']['configuration_settings']['included_sites'] as $siteToSearch)
                    {
                        $plugin = getPluginObjectForJobSite($siteToSearch);
                        $searchKey = cleanupSlugPart($keywordSet['key']);
                        if ($plugin->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
                            $searchKey = "alljobs";
                        }
                        $newSearch = findOrCreateUserSearchRun($searchKey, $siteToSearch);

                        $newSearch->setSearchParameter('keywords_array', $keywordSet['keywords_array']);
                        $newSearch->setSearchParameter('keywords_array_tokenized', $keywordSet['keywords_array_tokenized']);

                        if ($plugin->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED)) {
                            $arrSkippedPlugins[] = $siteToSearch;
                            continue;
                        }
                        elseif($plugin->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
                            $newSearch->save();
                            $arrSearchesPreLocation[$newSearch->getUserSearchRunKey()] = $newSearch;
                        }
                        else // if not, we need to add search for each keyword using that word as a single value in a keyword set
                        {
                            $arrKeys = array_keys($keywordSet['keywords_array']);
                            foreach ($arrKeys as $kwdkey) {
                                $thisSearchKey = $searchKey . "-" . strScrub($kwdkey, FOR_LOOKUP_VALUE_MATCHING);
                                $thisSearch = findOrCreateUserSearchRun($thisSearchKey, $newSearch->getJobSiteKey(), $newSearch->getLocationKey(), $newSearch);

                                $thisSearch->setSearchParameter('keywords_array', array($keywordSet['keywords_array'][$kwdkey]));
                                $thisSearch->setSearchParameter('keywords_array_tokenized', $keywordSet['keywords_array_tokenized'][$kwdkey]);
                                $thisSearch->save();
                                $arrSearchesPreLocation[$thisSearch->getUserSearchRunKey()] = $thisSearch;
                            }
                            $newSearch->delete();
                        }
                    }
                } else {
                    if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("No searches were set for keyword set " . $keywordSet['name'], \C__DISPLAY_ITEM_DETAIL__);
                }

                if (count($arrSkippedPlugins) > 0)
                    if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Keyword set " . $keywordSet['name'] . " did not generate searches for " . count($arrSkippedPlugins) . " plugins because they do not support keyword search: " . getArrayValuesAsString($arrSkippedPlugins, ", ", null, false) . ".", \C__DISPLAY_ITEM_DETAIL__);
            }

            //
            // Full set of searches loaded (location-agnostic).  We've now
            // got the full set of searches, so update the set with the
            // primary location data we have in the config.
            //

            if(count($arrSearchesPreLocation) > 0)
            {
                $arrLocations = getConfigurationSettings('search_locations');
                if(isset($arrLocations) && is_array($arrLocations) && count($arrLocations) >= 1)
                {
                    foreach ($arrSearchesPreLocation as $search)
                    {
                        $plugin = getPluginObjectForJobSite($search->getJobSiteKey());

                        if ($plugin->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || $plugin->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED))
                        {
                            // this search doesn't support specifying locations so we shouldn't clone it for a second location set
                            $GLOBALS['USERDATA']['configuration_settings']['searches'][$search->getUserSearchRunKey()] = $search;
                            continue;
                        }

                        foreach ( $arrLocations as $searchlocation)
                        {
                            $loc = $searchlocation['location_name_lookup']->getLocation();

                            if (!is_null($loc->getCountryCode() ))
                                $GLOBALS['USERDATA']['configuration_settings']['country_codes'][$loc->getCountryCode()] = $loc->getCountryCode();

                            $plugin = getPluginObjectForJobSite($search->getJobSiteKey());
                            $pluginCountries = $plugin->getSupportedCountryCodes();
                            if (!is_null($pluginCountries))
                            {
                                $matchedCountries = array_intersect($pluginCountries, $GLOBALS['USERDATA']['configuration_settings']['country_codes']);
                                if ($matchedCountries === false || count($matchedCountries) == 0)
                                {
                                    LogLine("Skipping search " . $search->getUserSearchRunKey() . " because it does not support any of the country codes required (" . getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['country_codes']));
                                    continue;
                                }
                            }

                            $searchForLoc = $this->_getSearchForSpecificLocationName_($search, $searchlocation);

                            if(!is_null($searchForLoc))
                                $GLOBALS['USERDATA']['configuration_settings']['searches'][$searchForLoc->getUserSearchRunKey()] = $searchForLoc;
                        }
                        $search->delete();
                    }
                }
            }
            else
                $GLOBALS['USERDATA']['configuration_settings']['searches'] = $arrSearchesPreLocation;

//
//            //
//            // Full set of searches loaded (location-agnostic).  We've now
//            // got the full set of searches, so update the set with the
//            // primary location data we have in the config.
//            //
//
//            if(count($arrSearchesPreLocation) > 0)
//            {
//                $arrLocations = getConfigurationSettings('location_sets');
//                if(isset($arrLocations) && is_array($arrLocations) && count($arrLocations) >= 1) {
//                    foreach ($arrSearchesPreLocation as $search) {
//                        $plugin = getPluginObjectForJobSite($search->getJobSiteKey());
//                        if ($plugin->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || $plugin->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED)) {
//                            // this search doesn't support specifying locations so we shouldn't clone it for a second location set
//                            $GLOBALS['USERDATA']['configuration_settings']['searches'][$search->getUserSearchRunKey()] = $search;
//                            continue;
//                        }
//
//                        foreach ( $arrLocations as $locset)
//                        {
//                            if (array_key_exists("location-countrycode", $locset) )
//                                $GLOBALS['USERDATA']['configuration_settings']['country_codes'][$locset['country_code']] = $locset['location-countrycode'];
//
//                            $plugin = getPluginObjectForJobSite($search->getJobSiteKey());
//                            $pluginCountries = $plugin->getSupportedCountryCodes();
//                            if (!is_null($pluginCountries)) {
//                                $matchedCountries = array_intersect($pluginCountries, $GLOBALS['USERDATA']['configuration_settings']['country_codes']);
//                                if ($matchedCountries === false || count($matchedCountries) == 0) {
//                                    LogLine("Skipping search " . $search->getUserSearchRunKey() . " because it does not support any of the country codes required (" . getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['country_codes']));
//                                    continue;
//                                }
//                            }
//
//                            $searchForLoc = $this->_getSearchForLocation_($search, $locset);
//
//                            $GLOBALS['USERDATA']['configuration_settings']['searches'][$searchForLoc->getUserSearchRunKey()] = $searchForLoc;
//                            $searchForLoc = null;
//                        }
//                        $search->delete();
//                    }
//                }
//            }
//            else
//                $GLOBALS['USERDATA']['configuration_settings']['searches'] = $arrSearchesPreLocation;

        }
    }
    private function _getSearchForSpecificLocationName_($search, $arrSearchLocation)
    {
        $locNameLookup = $arrSearchLocation['location_name_lookup'];
        if ($search->isSearchIncludedInRun() !== true) {
            // this site was excluded for this run, so continue.
            return $search;
        }

        if (!is_null($search->getSearchParameter('location_user_specified_override'))) {
            // this search already has a location from the user, so we just do nothing else
            return $search;
        }

        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Initializing new search for " . $search->getSearchKey() . " with location " . $arrSearchLocation['location_name_key'] . "...", \C__DISPLAY_NORMAL__);


        $plugin = getPluginObjectForJobSite($search->getJobSiteKey());

        $locTypeNeeded = $plugin->getLocationSettingType();
        if (!is_null($locTypeNeeded)) {

            $newSearch = findOrCreateUserSearchRun($search->getSearchKey(), $search->getJobSiteKey(), $arrSearchLocation['location_name_key'], $search);

            $location = $locNameLookup->getLocation();
            $newSearch->setLocation($location);

            $formatted_search_term = $location->formatLocationByLocationType($locTypeNeeded);
            $newSearch->setSearchParameter('location_search_value', $formatted_search_term);
            if (is_null($formatted_search_term) || strlen($formatted_search_term) == 0)
            {
                LogLine(sprintf("Requested location type setting of '%s' for %s was not found for search location %s.", $locTypeNeeded, $search->getJobSiteKey(), $arrSearchLocation['location_name_key']), C__DISPLAY_WARNING__);
                return null;
            }

            if (!isValueURLEncoded($newSearch->getSearchParameter('location_search_value'))) {
                $newSearch->setSearchParameter('location_search_value', urlencode($newSearch->getSearchParameter('location_search_value')));
            }

            if ($plugin->isBitFlagSet(C__JOB_LOCATION_REQUIRES_LOWERCASE)) {
                $newSearch->setSearchParameter('location_search_value', strtolower($newSearch->getSearchParameter('location_search_value')));
            }

            $newSearch->save();

            // BUGBUG:  2nd search returns Seattle as search string value when it should be blank
            return $newSearch;
        }

        return $search;
    }


    private function _createSearchInstancesForRun()
    {
        //
        // let's start with the searches specified with the details in the the config.ini
        //
        $arrSearchConfigSettings = getConfigurationSettings('searches');
        LogLine(" Creating search instances for this run from " . strval(count($arrSearchConfigSettings)) . " search config settings.", \C__DISPLAY_ITEM_DETAIL__);
        $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'] = array();

        if (!is_null($arrSearchConfigSettings) && is_array($arrSearchConfigSettings) && count($arrSearchConfigSettings) > 0)
        {
            //
            // Remove any sites that were excluded in this run from the searches list
            //
            foreach (array_keys($arrSearchConfigSettings) as $z) {
                $curSearchSettings = $arrSearchConfigSettings[$z];
                $jobsitekey = cleanupSlugPart($curSearchSettings->getJobSiteKey());

                if($curSearchSettings->getJobSiteObject()->isSearchIncludedInRun())
                {
                    if(!array_key_exists($jobsitekey, $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN']))
                        $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'][$jobsitekey] = array();

                    $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'][$jobsitekey][$curSearchSettings->getUserSearchRunKey()] = $curSearchSettings;
                }
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
            foreach($config['emails'] as $emailItem)
            {
                $tempEmail = $this->__getEmptyEmailRecord__();
                if (isset($emailItem['emailkind'])) {
                    $tempEmail['emailkind'] = $emailItem['emailkind'];
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
                $settingsEmail['email_addresses'][] = $tempEmail;
            }
        }

        $GLOBALS['USERDATA']['configuration_settings']['email'] = array_copy($settingsEmail);

        $userDetails = array();
        $userDetails['ConfigFilePath'] = $this->arrFileDetails['config_ini']['full_file_path'];
        $emails = $this->getEmailRecords("results", "to");
        foreach($emails as $email)
        {
            $userDetails['Name'] = $email['name'];
            $userDetails['EmailAddress'] = $email['address'];
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


//    private function _setPreviouslyReviewedJobsInputFiles_()
//    {
//        $arrFileInput = $this->getInputFilesByType("previously_reviewed_file");
//        if(is_array($arrFileInput) && $arrFileInput != null)
//        {
//            foreach($arrFileInput as $fileDetails)
//            {
//                if($fileDetails)
//                {
//                    $GLOBALS['userdata']['previous_files_details'][$fileDetails['file_name_base']] = array_copy($fileDetails);
//                }
//            }
//        }
//    }


    private function _scrubRegexSearchString($pattern)
    {
        $delim = '~';
        if(strpos($pattern, $delim) != false)
        {
            $delim = '|';
        }

        $rx = $delim.preg_quote(trim($pattern), $delim).$delim.'i';
        try
        {
            $testMatch = preg_match($rx, "empty");
        }
        catch (\Exception $ex)
        {
            $GLOBALS['logger']->logLine($ex->getMessage(), \C__DISPLAY_ERROR__);
            if(isDebug() == true) { throw $ex; }
        }
        return $rx;
    }




    private function _loadTitlesTokensToFilter_()
    {
        $arrFileInput = $this->getInputFilesByType("negative_title_keywords");

        $GLOBALS['USERDATA']['title_negative_keyword_tokens'] = array();

        if(isset($GLOBALS['USERDATA']['title_negative_keyword_tokens']) && count($GLOBALS['USERDATA']['title_negative_keyword_tokens']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            $GLOBALS['logger']->logLine("Using previously loaded " . countAssociativeArrayValues($GLOBALS['USERDATA']['title_negative_keyword_tokens']) . " tokenized title strings to exclude." , \C__DISPLAY_ITEM_DETAIL__);
            return;
        }

        if(!is_array($arrFileInput))
        {
            // No files were found, so bail
            $GLOBALS['logger']->logLine("No input files were found with title token strings to exclude." , \C__DISPLAY_ITEM_DETAIL__);
            return;
        }

        $arrNegKwds = array();

        foreach($arrFileInput as $fileItem)
        {
            $fileDetail = $fileItem['details'];
            if(isset($fileDetail) && $fileDetail ['full_file_path'] != '')
            {
                if(file_exists($fileDetail ['full_file_path'] ) && is_file($fileDetail['full_file_path'] ))
                {
                    $arrRecs = loadCSV($fileDetail ['full_file_path']);
                    foreach($arrRecs as $arrRec)
                    {
                        if(array_key_exists('negative_keywords', $arrRec)) {
                            $kwd = strtolower($arrRec['negative_keywords']);
                            $arrNegKwds[$kwd] = $kwd;
                        }
                    }
//                    $file = fopen($fileDetail ['full_file_path'],"r");
//                    $headers = fgetcsv($file);
//                    while (($rowData = fgetcsv($file, null, ",", "\"")) !== false) {
//                        $arrRec = array_combine($headers, $rowData);
//                        $arrRec['negative_keywords'] = strtolower($arrRec['negative_keywords']);
//                        $arrNegKwds[$arrRec["negative_keywords"]] = $arrRec;
//                    }
//
//                    fclose($file);

                }
            }
        }
        $GLOBALS['USERDATA']['title_negative_keywords'] = array_unique($arrNegKwds, SORT_REGULAR);

        $arrTitlesTemp = tokenizeSingleDimensionArray($GLOBALS['USERDATA']['title_negative_keywords'], 'userNegKwds', 'negative_keywords', 'negative_keywords');

        if(count($arrTitlesTemp) <= 0)
        {
            $GLOBALS['logger']->logLine("Warning: No title negative keywords were found in the input source files " . getArrayValuesAsString($arrFileInput) . " to be filtered from job listings." , \C__DISPLAY_WARNING__);
        }
        else
        {
            //
            // Add each title we found in the file to our list in this class, setting the key for
            // each record to be equal to the job title so we can do a fast lookup later
            //
            foreach($arrTitlesTemp as $titleRecord)
            {
                $tokens = explode("|", $titleRecord['tokenized']);
                $GLOBALS['USERDATA']['title_negative_keyword_tokens'][] = $tokens;
            }
            
            $inputfiles = array_column($this->getInputFilesByType("negative_title_keywords"), 'full_file_path');
            $GLOBALS['logger']->logLine("Loaded " . countAssociativeArrayValues($GLOBALS['USERDATA']['title_negative_keyword_tokens']) . " tokens to use for filtering titles from '" . getArrayValuesAsString($inputfiles) . "'." , \C__DISPLAY_WARNING__);

        }


    }



    /**
     * Initializes the global list of titles we will automatically mark
     * as "not interested" in the final results set.
     */
    function _loadCompanyRegexesToFilter_()
    {
        if(isset($GLOBALS['USERDATA']['companies_regex_to_filter']) && count($GLOBALS['USERDATA']['companies_regex_to_filter']) > 0)
        {
            // We've already loaded the companies; go ahead and return right away
            $GLOBALS['logger']->logLine("Using previously loaded " . count($GLOBALS['USERDATA']['companies_regex_to_filter']) . " regexed company strings to exclude." , \C__DISPLAY_ITEM_DETAIL__);
            return;
        }
        $arrFileInput = $this->getInputFilesByType("regex_filter_companies");

        $GLOBALS['USERDATA']['companies_regex_to_filter'] = array();

        if(isset($GLOBALS['USERDATA']['companies_regex_to_filter']) && count($GLOBALS['USERDATA']['companies_regex_to_filter']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            $GLOBALS['logger']->logLine("Using previously loaded " . count($GLOBALS['USERDATA']['companies_regex_to_filter']) . " regexed title strings to exclude." , \C__DISPLAY_ITEM_DETAIL__);
            return;
        }
        $fCompaniesLoaded = false;

        if(!isset($arrFileInput) ||  !is_array($arrFileInput)) { return; }


        foreach($arrFileInput as $fileItem)
        {
            if(isset($fileItem['details']))
            {
                $fileDetail = $fileItem['details'];

                if(isset($fileDetail ['full_file_path'])&& $fileDetail ['full_file_path'] != '')
                {
                    if(file_exists($fileDetail ['full_file_path'] ) && is_file($fileDetail ['full_file_path'] ))
                    {
                        $GLOBALS['logger']->logLine("Loading job Company regexes to filter from ".$fileDetail ['full_file_path']."." , \C__DISPLAY_ITEM_DETAIL__);
                        $classCSVFile = new \SimpleCSV($fileDetail ['full_file_path'] , 'r');
                        $arrCompaniesTemp = $classCSVFile->readAllRecords(true,array('match_regex'));
                        $arrCompaniesTemp = $arrCompaniesTemp['data_rows'];
                        $GLOBALS['logger']->logLine(count($arrCompaniesTemp) . " companies found in the source file that will be automatically filtered from job listings." , \C__DISPLAY_ITEM_DETAIL__);

                        //
                        // Add each Company we found in the file to our list in this class, setting the key for
                        // each record to be equal to the job Company so we can do a fast lookup later
                        //
                        if(count($arrCompaniesTemp) > 0)
                        {
                            foreach($arrCompaniesTemp as $CompanyRecord)
                            {
                                $arrRXInput = explode("|", strtolower($CompanyRecord['match_regex']));

                                foreach($arrRXInput as $rxItem)
                                {
                                    try
                                    {
                                        $rx = $this->_scrubRegexSearchString($rxItem);
                                        $GLOBALS['USERDATA']['companies_regex_to_filter'][] = $rx;

                                    }
                                    catch (\Exception $ex)
                                    {
                                        $strError = "Regex test failed on company regex pattern " . $rxItem .".  Skipping.  Error: '".$ex->getMessage();
                                        $GLOBALS['logger']->logLine($strError, \C__DISPLAY_ERROR__);
                                        if(isDebug() == true) { throw new \ErrorException( $strError); }
                                    }
                                }
                            }
                            $fCompaniesLoaded = true;
                        }
                    }
                }
            }
        }

        $inputfiles = array_column($arrFileInput, 'full_file_path');

        if($fCompaniesLoaded == false)
        {
            if(count($arrFileInput) == 0)
                $GLOBALS['logger']->logLine("No file specified for companies regexes to exclude from '" . getArrayValuesAsString($inputfiles) . "'.  Final list will not be filtered." , \C__DISPLAY_WARNING__);
            else
                $GLOBALS['logger']->logLine("Could not load regex list for companies to exclude from '" . getArrayValuesAsString($inputfiles) . "'.  Final list will not be filtered." , \C__DISPLAY_WARNING__);
        }
        else
        {
            $GLOBALS['logger']->logLine("Loaded " . count($GLOBALS['USERDATA']['companies_regex_to_filter']). " regexes to use for filtering companies from " . getArrayValuesAsString($inputfiles)  , \C__DISPLAY_NORMAL__);

        }
    }
//
//    function parseJobsListForPage($objSimpHTML)
//    {
//        throw new \ErrorException("parseJobsListForPage not supported for class" . get_class($this));
//    }
//    function parseTotalResultsCount($objSimpHTML)
//    {
//        throw new \ErrorException("parseTotalResultsCount not supported for class " . get_class($this));
//    }


} 