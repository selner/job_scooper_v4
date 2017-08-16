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


use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ClassConfig extends AbstractClassBaseJobsPlugin
{
    protected $nNumDaysToSearch = -1;
    public $arrFileDetails = array('output' => null, 'output_subfolder' => null, 'config_ini' => null, 'user_input_files_details' => null);
    protected $arrEmailAddresses = null;
    protected $configSettings = array('searches' => null, 'keyword_sets' => null, 'location_sets' => null, 'number_days'=>VALUE_NOT_SUPPORTED, 'included_sites' => array(), 'excluded_sites' => array());
    protected $arrEmail_PHPMailer_SMTPSetup = null;
    protected $allConfigFileSettings = null;

    function getSearchConfiguration($strSubkey = null)
    {
        if(isset($strSubkey) && (isset($GLOBALS['USERDATA']['configuration_settings'][$strSubkey]) || $GLOBALS['USERDATA']['configuration_settings'][$strSubkey] == null))
            $ret = $GLOBALS['USERDATA']['configuration_settings'][$strSubkey];
        else
            $ret = $GLOBALS['USERDATA']['configuration_settings'];

        return $ret;
    }

    function getSMTPSettings() { if(isset($this->arrEmail_PHPMailer_SMTPSetup)) { return $this->arrEmail_PHPMailer_SMTPSetup; } else return null; }

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
        print "Starting memory is ". getPhpMemoryUsage() .PHP_EOL;

        ini_set('memory_limit','1024M');
        ini_set("auto_detect_line_endings", true);

        $GLOBALS['USERDATA'] = array();
        __initializeArgs__();

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Setting up application... ", \Scooper\C__DISPLAY_SECTION_START__);
        # After you've configured Pharse, run it like so:
        $GLOBALS['OPTS'] = Pharse::options($GLOBALS['OPTS_SETTINGS']);

        $GLOBALS['USERDATA']['companies_regex_to_filter'] = null;
        $GLOBALS['USERDATA']['configuration_settings'] = array();

        $now = new DateTime();
        $GLOBALS['USERDATA']['configuration_settings']['app_run_id'] = __APP_VERSION__ . $now->format('Y-m-d-H-i-s');

        // and to make sure our notes get updated on active jobs
        // that we'd seen previously
        // Now go see what we got back for each of the sites
        //
        foreach($GLOBALS['JOBSITE_PLUGINS']  as $site)
        {
            assert(isset($site['name']));
            $GLOBALS['JOBSITE_PLUGINS'][$site['name']]['include_in_run'] = is_OptionIncludedSite($site['name']);
        }
        $includedsites = array_filter($GLOBALS['JOBSITE_PLUGINS'], function($k) {
            return $k['include_in_run'];
        });
        $excludedsites = array_filter($GLOBALS['JOBSITE_PLUGINS'], function($k) {
            return !($k['include_in_run']);
        });
        $keys = array();
        foreach(array_keys($includedsites) as $k)
            $keys[] = strtolower($k);

        $GLOBALS['USERDATA']['configuration_settings']['included_sites'] = array_combine($keys, array_column($includedsites, 'name'));
        $keys = array();
        foreach(array_keys($excludedsites) as $k)
            $keys[] = strtolower($k);

        $GLOBALS['USERDATA']['configuration_settings']['excluded_sites'] = array_combine($keys, array_column($excludedsites, 'name'));
        $GLOBALS['USERDATA']['configuration_settings']['searches'] = array();

        // Override any INI file setting with the command line output file path & name
        // the user specificed (if they did)
        $userOutfileDetails = \Scooper\get_FileDetails_fromPharseOption("output", false);
        if(!$userOutfileDetails['has_directory'])
        {
            throw new ErrorException("Required value for the output folder was not specified. Exiting.");
        }

        if($GLOBALS['OPTS']['use_config_ini_given'])
        {
            $this->arrFileDetails['config_ini'] = \Scooper\set_FileDetails_fromPharseSetting("use_config_ini", 'config_file_details', true);
            $name = str_replace(DIRECTORY_SEPARATOR, "", $this->arrFileDetails['config_ini']['directory']);
            $name = substr($name, max([strlen($name)-31-strlen(".ini"), 0]), 31);
            $GLOBALS['USERDATA']['user_unique_key'] = md5($name);
        }
        else
        {
            $GLOBALS['USERDATA']['user_unique_key'] = uniqid("unknown");
        }

        // Now setup all the output folders
        $this->__setupOutputFolders__($userOutfileDetails['directory']);

        if(!isset($GLOBALS['logger'])) $GLOBALS['logger'] = new \Scooper\ScooperLogger($GLOBALS['USERDATA']['directories']['debug'] );
        $defaultLogger = new Logger('defaultLogger');
        $defaultLogger->pushHandler(new StreamHandler($GLOBALS['USERDATA']['directories']['debug'] . '/propel.log', Logger::DEBUG));
        $defaultLogger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));

        $serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
        $serviceContainer->setLogger('defaultLogger', $defaultLogger);

        $strOutfileArrString = getArrayValuesAsString( $GLOBALS['USERDATA']['directories']);
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Output folders configured: " . $strOutfileArrString, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        if($GLOBALS['OPTS']['use_config_ini_given'])
        {
            if(!isset($GLOBALS['logger'])) $GLOBALS['logger'] = new \Scooper\ScooperLogger($this->arrFileDetails['config_ini']['directory'] );
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Log file for run being written to: " . $this->arrFileDetails['config_ini']['directory'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading configuration file details from " . $this->arrFileDetails['config_ini']['full_file_path'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

            $this->_LoadAndMergeAllConfigFilesRecursive($this->arrFileDetails['config_ini']['full_file_path']);

            if (isset($this->arrFileDetails['config_ini'])) {
                if(!is_file($this->arrFileDetails['config_ini']['full_file_path']))
                {
                    $altFileDetails = null;
                    $altFileDetails = Scooper\parseFilePath($this->arrFileDetails['config_ini']['full_file_path']);
                    if(!is_dir($altFileDetails['config_ini']['directory']))
                    {
                        if(is_dir(is_file($altFileDetails['config_ini']['full_file_path']))) {
                            Scooper\parseFilePath($this->arrFileDetails['config_ini']['directory'] . "/". $altFileDetails['config_ini']['filename']);
                        }
                    }

                }
            }

            $this->_setupRunFromConfig_($this->allConfigFileSettings);

        }


        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Configuring specific settings for this run... ", \Scooper\C__DISPLAY_SECTION_START__);

        $GLOBALS['USERDATA']['configuration_settings']['number_days']= \Scooper\get_PharseOptionValue('number_days');
        if($GLOBALS['USERDATA']['configuration_settings']['number_days'] === false) {
            $GLOBALS['USERDATA']['configuration_settings']['number_days'] = 1;
        }
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine($GLOBALS['USERDATA']['configuration_settings']['number_days'] . " days configured for run. ", \Scooper\C__DISPLAY_ITEM_DETAIL__);

//        $this->_setPreviouslyReviewedJobsInputFiles__setPreviouslyReviewedJobsInputFiles_();

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Completed configuration load.", \Scooper\C__DISPLAY_SUMMARY__);

    }

    function getLogger()
    {
        return $GLOBALS['logger'];
    }

    private function __setupOutputFolders__($outputDirectory)
    {
        if(! $outputDirectory)
        {
            throw new ErrorException("Required value for the output folder was not specified. Exiting.");
        }

        $workingDirs = ["debug", "listings-raw", "results", "listings-userinterested", "listings-rawbysite", "listings-tokenized", "listings-usernotinterested"];
        foreach($workingDirs as $d) {
            $prefix = $GLOBALS['USERDATA']['user_unique_key'];
            $path = join(DIRECTORY_SEPARATOR, array($outputDirectory, getTodayAsString("-"), $d, $prefix));
            $details = \Scooper\getFilePathDetailsFromString($path, \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
            $GLOBALS['USERDATA']['directories'][$d] = realpath($details['directory']);
        }

        $path = join(DIRECTORY_SEPARATOR, array($outputDirectory, getTodayAsString("-"), "listings-rawbysite", "all-users"));
        $details = \Scooper\getFilePathDetailsFromString($path, \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
        $GLOBALS['USERDATA']['directories']['listings-rawbysite-allusers'] = realpath($details['directory']);

    }



    private function _LoadAndMergeAllConfigFilesRecursive($fileConfigToLoad)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading INI file: ".$fileConfigToLoad, \Scooper\C__DISPLAY_SECTION_START__);

        $iniParser = new IniParser($fileConfigToLoad);
        $iniParser->use_array_object = false;
        $tempConfigSettings = $iniParser->parse();
        $iniParser = null;
        if(!array_key_exists($fileConfigToLoad, $this->_arrConfigFileSettings_))
        {

            $this->_arrConfigFileSettings_[$fileConfigToLoad] = \Scooper\array_copy($tempConfigSettings);

            if(isset($tempConfigSettings['settings_files']))
            {
                foreach($tempConfigSettings['settings_files'] as $nextConfigFile)
                {
                    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Recursing into child settings file ".$nextConfigFile, \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    $this->_LoadAndMergeAllConfigFilesRecursive($nextConfigFile);
                    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added child settings file ".$nextConfigFile, \Scooper\C__DISPLAY_ITEM_RESULT__);
                }
            }

            $allConfigfileSettings = [];
            foreach($this->_arrConfigFileSettings_ as $tempConfig)
            {
                $allConfigfileSettings = array_merge_recursive($allConfigfileSettings, $tempConfig);
            }

            $this->allConfigFileSettings = $allConfigfileSettings;
        }

    }


    private function _setupRunFromConfig_($config)
    {
        if(isset($config['input']) && isset($config['input']['folder']))
        {
            $this->arrFileDetails['input_folder']  = \Scooper\parseFilePath($config['input']['folder']);
        }

        if(isset($config['inputfiles']))
        {
            foreach($config['inputfiles'] as $iniInputFile)
            {
                $strFileName = "ERROR-UNKNOWN";
                if(isset($iniInputFile['path']) && strlen($iniInputFile['path']) > 0)
                    { $strFileName = $iniInputFile['path']; }
                elseif(isset($iniInputFile['filename']) && strlen($iniInputFile['filename']) > 0)
                     $strFileName = $iniInputFile['filename'];

                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Processing input file '" . $strFileName. "' with type of '". $iniInputFile['type'] . "'...", \Scooper\C__DISPLAY_NORMAL__);
                $this->__addInputFile__($iniInputFile);
            }
        }
        //
        // Load the global search data that will be used to create
        // and configure all searches
        //
        $this->_parseGlobalSearchParametersFromConfig_($config);

        if(isDebug() == true && isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded all configuration settings:  " . var_export($this->allConfigFileSettings, true), \Scooper\C__DISPLAY_SUMMARY__);


        $this->_parseEmailSetupFromINI_($config);

        $this->_parseSeleniumParametersFromConfig_($config);

        //
        // Load Plugin Specific settings from the config
        //
        $this->_parsePluginSettingsFromConfig_($config);


        $this->_parseKeywordSetsFromConfig_($config);

        $this->_parseLocationSetsFromConfig_($config);

        //
        // Load any specific searches specified by the user in the config
        //
        $this->_parseSearchesFromConfig__($config);

        // Update the searches with the keyword values
        if(isset($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) && is_array($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) && count($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) >= 1)
        {
            reset($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']);
            $primarySet = current($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']);

            $this->_updateSearchesWithKeywordSet_($primarySet);
        }

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("All INI files loaded. Finalizing configuration for run...", \Scooper\C__DISPLAY_SECTION_START__);

        //
        // Create searches needed to run all the keyword sets
        //
        $this->_addSearchesForKeywordSets_();


        //
        // Full set of searches loaded (location-agnostic).  We've now
        // got the full set of searches, so update the set with the
        // primary location data we have in the config.
        //
        if(isset($GLOBALS['USERDATA']['configuration_settings']['location_sets']) && is_array($GLOBALS['USERDATA']['configuration_settings']['location_sets']) && count($GLOBALS['USERDATA']['configuration_settings']['location_sets']) >= 1)
        {

            $this->_addLocationSetToInitialSetOfSearches_();
        }

        //
        // Clone all searches if there were 2 or more location sets
        // Add update those clones with those location values
        //
        $this->_addSearchesForAdditionalLocationSets_();



        //
        // Load the exclusion filter and other user data from files
        //
        $this->_loadTitlesTokensToFilter_();

        $this->_loadCompanyRegexesToFilter_();
    }

    private function __getEmptyEmailRecord__()
    {
        return array('emailkind'=> null, 'type'=> null, 'name'=>null, 'address' => null);
    }

    private function __addInputFile__($iniInputFileItem)
    {

        $tempFileDetails = null;
        if(isset($iniInputFileItem['path']))
        {
            $tempFileDetails = \Scooper\parseFilePath($iniInputFileItem['path'], true);

        }
        elseif(isset($iniInputFileItem['filename']))
        {
            $tempFileDetails = \Scooper\parseFilePath($this->arrFileDetails['input_folder']['directory'].$iniInputFileItem['filename'], true);
        }

        if(!is_file($tempFileDetails['full_file_path']))
        {
            throw new Exception("Specified input file '" . $tempFileDetails['full_file_path'] . "' was not found.  Aborting.");
        }


        if(isset($tempFileDetails))
        {

            $GLOBALS['USERDATA']['user_input_files_details'][]= array('details'=> $tempFileDetails, 'data_type' => $iniInputFileItem['type']);
        }
    }

    private function __getInputFilesByValue__($valKey, $val)
    {
        $ret = null;
        if(isset($GLOBALS['USERDATA']['user_input_files_details']) && (is_array($GLOBALS['USERDATA']['user_input_files_details'])  || is_array($GLOBALS['USERDATA']['user_input_files_details'])))
        {
            foreach($GLOBALS['USERDATA']['user_input_files_details'] as $fileItem)
            {
                if(strcasecmp($fileItem[$valKey], $val) == 0)
                {
                    $ret[] = $fileItem;
                }
            }
        }
        return $ret;
    }



    private function _parseSearchesFromConfig__($config)
    {
        if(!$config) throw new ErrorException("Invalid configuration.  Cannot load user's searches.");
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading searches from config file...", \Scooper\C__DISPLAY_ITEM_START__);

        if(isset($config['search']))
        {
            if(is_array($config['search']))
            {
                foreach($config['search'] as $iniSearch)
                {
                    $retSearch = $this->_parseSearchFromINI_($iniSearch);
                    if(isset($retSearch)) $GLOBALS['USERDATA']['configuration_settings']['searches'][$retSearch['key']] = $retSearch;
                }
            }
            else
            {
                $retSearch = $this->_parseSearchFromINI_($config['search']);
                if(isset($retSearch)) $GLOBALS['USERDATA']['configuration_settings']['searches'][$retSearch['key']] = $retSearch;
            }
        }
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded " . count($GLOBALS['USERDATA']['configuration_settings']['searches']) . " searches. ", \Scooper\C__DISPLAY_ITEM_RESULT__);
    }

    private function _parseSearchFromINI_($iniSearch)
    {
        $newSearch = new UserSearchRun();

        $newSearch->setKey(\Scooper\strScrub($iniSearch['key'], REMOVE_EXTRA_WHITESPACE | LOWERCASE ));
        $newSearch->setJobSite(\Scooper\strScrub($iniSearch['jobsite'], REMOVE_EXTRA_WHITESPACE | LOWERCASE ));

        $strJobSiteKey = $newSearch->getJobSite();
        if(isset($GLOBALS['USERDATA']['configuration_settings']['included_sites']) && !isset($GLOBALS['USERDATA']['configuration_settings']['included_sites'][$strJobSiteKey]))
        {
            LogLine($iniSearch['jobsite'] . "search " .$iniSearch['name'] . " was not added; " . $strJobSiteKey . " is excluded for this run.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return null;
        }
        $searchSettings = new SearchSettings();
        $searchSettings['base_url_format'] = array_key_exists('url_format', $iniSearch) ? $iniSearch['url_format'] : null;
        $searchSettings['location_user_specified_override'] = array_key_exists('location', $iniSearch) ? $iniSearch['location'] : null;

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Search loaded from config INI: " . $newSearch->getKey(), \Scooper\C__DISPLAY_ITEM_DETAIL__);

        return $newSearch;
    }


    private function _parsePluginSettingsFromConfig_($config)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading plugin setup information from config file...", \Scooper\C__DISPLAY_ITEM_START__);

        if(array_key_exists('plugin_settings', $config) == true && is_array($config['plugin_settings']) && count($config['plugin_settings']) > 0)
        {
            //
            // plugin setting config items are structured like this:
            //      [plugin_settings.usajobs]
            //      authorization_key="XxXxXxXxXxXxXxXxXxXx="
            foreach(array_keys($config['plugin_settings']) as $pluginname)
            {
                if (array_key_exists($pluginname, $GLOBALS['JOBSITE_PLUGINS']))
                {
                    foreach(array_keys($config['plugin_settings'][$pluginname]) as $settingkey) {
                        $GLOBALS['JOBSITE_PLUGINS'][$pluginname]['other_settings'][$settingkey] = $config['plugin_settings'][$pluginname][$settingkey];
                        $GLOBALS['USERDATA']['configuration_settings']['plugin_settings'][$pluginname][$settingkey] = $config['plugin_settings'][$pluginname][$settingkey];
                    }
                }
            }
        }
    }

    private function _parseGlobalSearchParametersFromConfig_($config)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading global search settings from config file...", \Scooper\C__DISPLAY_ITEM_START__);

        if (array_key_exists('global_search_options', $config))
        {
            foreach(array_keys($config['global_search_options']) as $gso)
            {
                if((strtolower($gso)== 'excluded_jobsites') && isset($config['global_search_options']['excluded_jobsites']))
                {
                    if(is_string($config['global_search_options']['excluded_jobsites'])) { $config['global_search_options']['excluded_jobsites'] = explode(",", $config['global_search_options']['excluded_jobsites']); }
                    if(!is_array($config['global_search_options']['excluded_jobsites'])) { $config['global_search_options']['excluded_jobsites'] = array($config['global_search_options']['excluded_jobsites']); }
                    foreach($config['global_search_options']['excluded_jobsites'] as $excludedSite)
                    {
                        $excludedSite = strtolower(trim($excludedSite));
                        $GLOBALS['USERDATA']['configuration_settings']['excluded_sites'][$excludedSite] = $excludedSite;
                        if(array_key_exists($excludedSite, $GLOBALS['USERDATA']['configuration_settings']['included_sites'])) 
                        {   
                            unset($GLOBALS['USERDATA']['configuration_settings']['included_sites'][$excludedSite]);
                        }
                        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Setting " . $excludedSite . " as excluded for this run.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    }
                }
                else
                {
                    $GLOBALS['USERDATA']['configuration_settings'][$gso] = $config['global_search_options'][$gso];
                    if(strtolower($gso) == 'debug' && (!array_key_exists('DEBUG', $GLOBALS['OPTS']) || $GLOBALS['USERDATA']['configuration_settings']['debug'] === false)) {
                        if (\Scooper\intceil($config['global_search_options'][$gso]) == 1)
                        {
                            $GLOBALS['USERDATA']['configuration_settings']['debug'] =  true;
                        }
                        $GLOBALS['USERDATA']['configuration_settings']['debug'] = false;
                    }
                }
            }
        }
    }
    private function _parseSeleniumParametersFromConfig_($config)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading Selenium settings from config file...", \Scooper\C__DISPLAY_ITEM_START__);
        if(isset($config['selenium']) && is_array($config['selenium']))
        {
            foreach(array_keys($config['selenium']) as $k)
                $GLOBALS['USERDATA']['selenium'][$k] = trim($config['selenium'][$k]);
        }

//        if (!((array_key_exists('autostart', $GLOBALS['USERDATA']['selenium']) === true && array_key_exists('port', $GLOBALS['USERDATA']['selenium']) === true ) || array_key_exists('start_command', $GLOBALS['USERDATA']['selenium']) === true ))
//            throw new Exception("Required parameters for Selenium are missing; app cannot start.  You must set either 'autostart' and 'port' or 'start_command' in your configuration files.");

        $GLOBALS['USERDATA']['selenium']['autostart'] = \Scooper\intceil($GLOBALS['USERDATA']['selenium']['autostart']);
//
//        if(! array_key_exists('start_command', $GLOBALS['USERDATA']['selenium']) === true ) {
//            if ($GLOBALS['USERDATA']['selenium']['autostart'] == 1 && !(array_key_exists('jar', $GLOBALS['USERDATA']['selenium']) === true && array_key_exists('postfix_switches', $GLOBALS['USERDATA']['selenium']) === true))
//                throw new Exception("Required parameters to autostart Selenium are missing; you must set both 'jar' and 'postfix_switches' in your configuration files.");
//        }

        if (!(array_key_exists('server', $GLOBALS['USERDATA']['selenium']) === true))
            $GLOBALS['USERDATA']['selenium']['server'] = "localhost";


        $GLOBALS['USERDATA']['selenium']['host_location'] = 'http://' . $GLOBALS['USERDATA']['selenium']['server'] . ":" . $GLOBALS['USERDATA']['selenium']['port'];

    }

    private function _parseLocationSetsFromConfig_($config)
    {
        if (!$config) throw new ErrorException("Invalid configuration.  Cannot load user's searches.");

        if(!array_key_exists('location_sets', $GLOBALS['USERDATA']['configuration_settings']))
            $GLOBALS['USERDATA']['configuration_settings']['location_sets'] = array();

        if(isset($config['search_location_setting_set']))
        {
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading search locations from config file...", \Scooper\C__DISPLAY_ITEM_START__);
            //
            // Check if this is a single search setting or if it's a set of search settings
            //
            $strSettingsName = null;
            if($config['search_location_setting_set'] && is_array($config['search_location_setting_set']))
            {
                foreach($config['search_location_setting_set'] as $iniSettings)
                {
                    if(count($iniSettings) > 1)
                    {
                        $arrNewLocationSet = $this->_getEmptyLocationSettingsSet_();
                        $strSetName = 'LocationSet' . (count($GLOBALS['USERDATA']['configuration_settings']['location_sets']) + 1);
                        if(isset($iniSettings['name']))
                        {
                            if(is_array($iniSettings['name']))
                            {
                                throw new Exception("Error: Invalid location set data loaded from configs.  Did you inadvertently include the same location set [" . $iniSettings['name'][0] . "] twice?");
                            }
                            if (strlen($iniSettings['name']) > 0)
                            {
                                $strSetName = $iniSettings['name'];
                            }
                        }
                        elseif(isset($iniSettings['key']) && strlen($iniSettings['key']) > 0)
                        {
                            $strSetName = $iniSettings['key'];

                        }
                        $strSetName = \Scooper\strScrub($strSetName, FOR_LOOKUP_VALUE_MATCHING);

                        $arrNewLocationSet['key'] = $strSetName;
                        $arrNewLocationSet = $this->_parseAndAddLocationSet_($arrNewLocationSet, $iniSettings);

                        $strSettingStrings = getArrayValuesAsString($arrNewLocationSet);
                        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added location search settings '" . $strSetName . ": " . $strSettingStrings, \Scooper\C__DISPLAY_ITEM_DETAIL__);

                        $GLOBALS['USERDATA']['configuration_settings']['location_sets'][$strSetName] = $arrNewLocationSet;

                    }
                }
            }

            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded " . count($GLOBALS['USERDATA']['configuration_settings']['location_sets']) . " location sets. ", \Scooper\C__DISPLAY_ITEM_RESULT__);
        }
    }

    private function _parseAndAddLocationSet_($arrLocSet, $iniSearchSetting)
    {
        foreach($GLOBALS['DATA']['location_types'] as $loctype)
        {
            if(isset($iniSearchSetting[$loctype]) && strlen($iniSearchSetting[$loctype]) > 0)
            {
                $arrLocSet[$loctype] = \Scooper\strScrub($iniSearchSetting[$loctype], REMOVE_EXTRA_WHITESPACE);
                $arrLocSet[$loctype] = $iniSearchSetting[$loctype];
            }
        }

        return $arrLocSet;
    }


    private function _parseKeywordSetsFromConfig_($config)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading keyword set from config file...", \Scooper\C__DISPLAY_ITEM_START__);
        if(!array_key_exists('keyword_sets', $GLOBALS['USERDATA']['configuration_settings']))
        {
            $GLOBALS['USERDATA']['configuration_settings']['keyword_sets'] = array();
        }
        if(isset($config['search_keyword_set']))
        {
            foreach(array_keys($config['search_keyword_set']) as $key)
            {
                $ini_keyword_set = $config['search_keyword_set'][$key];
                $ini_keyword_set['key'] = $key;

                $strSetKey = 'ks' . (count($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) + 1);
                if(isset($ini_keyword_set['name']) && strlen($ini_keyword_set['name']) > 0)
                {
                    $strSetKey = $ini_keyword_set['name'];
                }
                elseif(isset($ini_keyword_set['key']) && strlen($ini_keyword_set['key']) > 0)
                {
                    $strSetKey = $ini_keyword_set['key'];
                }

                $GLOBALS['USERDATA']['configuration_settings']['keyword_sets'][$strSetKey] = $this->_getNewKeywordSettingsSet_(strtolower($strSetKey), $ini_keyword_set);

                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added keyword set '" . $GLOBALS['USERDATA']['configuration_settings']['keyword_sets'][$strSetKey]['name'] . "' with keywords = " . getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['keyword_sets'][$strSetKey]['keywords_array']) . ((array_key_exists('keyword_match_type', $ini_keyword_set) && strlen($ini_keyword_set['keyword_match_type'] ) > 0) ? " matching " . $ini_keyword_set['keyword_match_type'] : ""), \Scooper\C__DISPLAY_ITEM_DETAIL__);

            }

        }
    }


    private function _updateSearchesWithKeywordSet_($keywordSet)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Updating user-specified searches with keyword set details", \Scooper\C__DISPLAY_ITEM_START__);
        //for($c = 0; $c < count($GLOBALS['USERDATA']['configuration_settings']['searches']); $c++)
        foreach(array_keys($GLOBALS['USERDATA']['configuration_settings']['searches']) as $c)
        {
            $GLOBALS['USERDATA']['configuration_settings']['searches'][$c]['keywords_array'] = $keywordSet['keywords_array'];
            $GLOBALS['USERDATA']['configuration_settings']['searches'][$c]['keywords_array_tokenized'] = $keywordSet['keywords_array_tokenized'];

            //
            // If this search already has any flags set on it, then do not overwrite that value for this search
            // Otherwise, set it to be the value that any keyword set we're adding has
            //
            if(!array_key_exists('user_setting_flags', $GLOBALS['USERDATA']['configuration_settings']['searches'][$c]) || $GLOBALS['USERDATA']['configuration_settings']['searches'][$c]['user_setting_flags'] == null || $GLOBALS['USERDATA']['configuration_settings']['searches'][$c]['user_setting_flags'] == 0)
            {
                $GLOBALS['USERDATA']['configuration_settings']['searches'][$c]['user_setting_flags'] = $keywordSet['keyword_match_type_flag'];
            }
        }
    }

    private function _addSearchesForKeywordSets_()
    {
        //
        // explode any keyword sets we loaded into separate searches
        //
        // If the keyword settings scope is all sites, then create a search for every possible site
        // so that it runs with the keywords settings if it was included_<site> = true
        //
        if(isset($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) && count($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) > 0)
        {
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Adding new searches for user's keyword sets ", \Scooper\C__DISPLAY_ITEM_START__);

            foreach($GLOBALS['USERDATA']['configuration_settings']['keyword_sets'] as $keywordSet)
            {
                $arrSkippedPlugins = null;
                if(isset($GLOBALS['USERDATA']['configuration_settings']['included_sites']))
                {

                    if(count($GLOBALS['USERDATA']['configuration_settings']['included_sites'])>0)
                    {
                        foreach($GLOBALS['USERDATA']['configuration_settings']['included_sites'] as $siteToSearch)
                        {

                            $newSearch = new \JobScooper\UserSearchRun();
                            $newSearch->setKey(\Scooper\strScrub($siteToSearch, FOR_LOOKUP_VALUE_MATCHING) . \Scooper\strScrub($keywordSet['key'], FOR_LOOKUP_VALUE_MATCHING));
                            $newSearch->setJobSite($siteToSearch);
                            $classPlug = $newSearch->getPlugin();

                            $searchSettings = array();
                            $searchSettings['user_setting_flags'] = $keywordSet['keyword_match_type_flag'];

                            if($classPlug->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED))
                            {
                                $arrSkippedPlugins[] = $siteToSearch;
                                continue;
                            }

                            if($classPlug->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
                            {
                                $newSearch->setKey($siteToSearch . "-alljobs");
                                $GLOBALS['USERDATA']['configuration_settings']['searches'][$newSearch->getKey()] = $newSearch;
                            }
                            else // if not, we need to add search for each keyword using that word as a single value in a keyword set
                            {
                                $arrKeys = array_keys($keywordSet['keywords_array']);
                                foreach($arrKeys as $key)
                                {
                                    $thisSearch = $newSearch->copy(true);
                                    $thisSearch->setKey($newSearch->getKey()."-".\Scooper\strScrub($key, FOR_LOOKUP_VALUE_MATCHING));
                                    $thisSearch['keywords_array'] = array($keywordSet['keywords_array'][$key]);
                                    $thisSearch['keywords_array_tokenized'] = array($keywordSet['keywords_array_tokenized'][$key]);

                                    $GLOBALS['USERDATA']['configuration_settings']['searches'][$thisSearch->getKey()] = $thisSearch;
                                }
                            }
                        }
                    }
                    else
                    {
                        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("No searches were set for keyword set " . $keywordSet['name'] , \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    }
                }
                if(count($arrSkippedPlugins) > 0)
                    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Keyword set " . $keywordSet['name'] . " did not generate searches for " . count($arrSkippedPlugins) ." plugins because they do not support keyword search: " . getArrayValuesAsString($arrSkippedPlugins, ", ", null, false). "." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
            }
        }
    }


    private function _addLocationSetToInitialSetOfSearches_()
    {
        $arrSkippedPlugins = null;
        if(count($GLOBALS['USERDATA']['configuration_settings']['searches']) <= 0)
        {
            return;
        }

        reset($GLOBALS['USERDATA']['configuration_settings']['location_sets']);
        $primaryLocationSet = current($GLOBALS['USERDATA']['configuration_settings']['location_sets']);

        assert($primaryLocationSet!=null);

        if(!isset($primaryLocationSet))
        {
            return;
        }

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Updating all searches with primary location set '" . $primaryLocationSet['key'] . "'...", \Scooper\C__DISPLAY_NORMAL__);

        //
        // The first location set will be added to all searches always.
        // This function adds a copy of all searches if there was 2 or more
        // location sets specified
        //
        //

        $arrSearches = $GLOBALS['USERDATA']['configuration_settings']['searches'];

        if(isset($primaryLocationSet))
        {
            foreach($arrSearches as $search)
            {
                $searchKey = $search->getKey();

//                $curSiteName = \Scooper\strScrub($GLOBALS['USERDATA']['configuration_settings']['searches'][$searchKey]['site_name'], FOR_LOOKUP_VALUE_MATCHING);
//                if(array_key_exists($curSiteName, $GLOBALS['USERDATA']['configuration_settings']['excluded_sites']))
//                {
//                    // this site was excluded for this location set, so continue.
//                    continue;
//                }
//
                if($search->isSearchIncludedInRun() !== true) {
                    // this site was excluded for this location set, so continue.
                    continue;
                }

//                if(array_key_exists('location_user_specified_override', $GLOBALS['USERDATA']['configuration_settings']['searches'][$searchKey]) &&
//                        isset($GLOBALS['USERDATA']['configuration_settings']['searches'][$searchKey]['location_user_specified_override']) && strlen($GLOBALS['USERDATA']['configuration_settings']['searches'][$searchKey]['location_user_specified_override'])>0)

                if(!is_null($search['location_user_specified_override']))
                {
                    // this search already has a location from the user, so we just need to set it and nothing else
                    $search['location_search_value'] = $search['location_user_specified_override'];
                    continue;
                }

                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Adding primary location set to " . $searchKey . " searches...", \Scooper\C__DISPLAY_NORMAL__);

                $classPlug = $search->getPlugin();
                if ($classPlug->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || $classPlug->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED)) {
                    // this search doesn't support specifying locations so we shouldn't clone it for a second location set
                    continue;
                }

                $locTypeNeeded = $classPlug->getLocationSettingType();
                if(!is_null($locTypeNeeded))
                {
                    if(array_key_exists($locTypeNeeded, $primaryLocationSet))
                        $search['location_search_value'] = $primaryLocationSet[$locTypeNeeded];
                    else {
                        $err = "Error:  unable to add search '" . $searchKey . "' because the required location type '" . $locTypeNeeded ."' was not found in the location set '" . $primaryLocationSet['key'] . "'. Excluding searches for " . $curSiteName .".";
                        handleException(new IndexOutOfBoundsException(sprintf("Requested location type setting of '%s' is not valid.", $locTypeNeeded)), $err, $raise=false);
                        $GLOBALS['USERDATA']['configuration_settings']['excluded_sites'][$curSiteName] = $curSiteName;

                        $arrNewSearchList = array_filter($GLOBALS['USERDATA']['configuration_settings']['searches'], function ($var) use ($curSiteName) {
                            if (strcasecmp($var->getJobSite(), $curSiteName) == 0)
                                return false;
                            return true;
                        });

                        $GLOBALS['USERDATA']['configuration_settings']['searches'] = \Scooper\array_copy($arrNewSearchList);
                        $this->_addLocationSetToInitialSetOfSearches_();
                        return;

                    }

                    if(!isValueURLEncoded($search['location_search_value']))
                    {
                        $search['location_search_value'] = urlencode($search['location_search_value']);
                    }

                    if($classPlug->isBitFlagSet(C__JOB_LOCATION_REQUIRES_LOWERCASE))
                    {
                        $search['location_search_value'] = strtolower($search[$searchKey]['location_search_value']);
                    }


                    $search->setKey($searchKey . "-loc-" . strtolower($primaryLocationSet['key']));

                    // BUGBUG:  Workaround for a single plugin, Dice, to be able to get more than one location set parameter
                    $search['location_set_key'] = $primaryLocationSet['key'];
                }


                $search->save();
            }

            $GLOBALS['USERDATA']['configuration_settings']['searches'] = $arrSearches;
        }
    }


    // TODO:  BUGBUG -- need to update for new UserSearchRun structure
    private function _addSearchesForAdditionalLocationSets_()
    {
//        throw new \Symfony\Component\Intl\Exception\NotImplementedException("BUGBUG -- need to update for new UserSearchRun structure");

//        if(count($GLOBALS['USERDATA']['configuration_settings']['searches']) <= 0)
//        {
//            return;
//        }
//
//        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Creating searches for additional location sets after the primary by cloning and updating all searches", \Scooper\C__DISPLAY_ITEM_START__);
//        $arrSkippedPlugins = null;
//
//
//        //
//        // The first location set will be added to all searches always.
//        // This function adds a copy of all searches if there was 2 or more
//        // location sets specified
//        //
//        //
//        if(isset($GLOBALS['USERDATA']['configuration_settings']['location_sets']) && is_array($GLOBALS['USERDATA']['configuration_settings']['location_sets']) && count($GLOBALS['USERDATA']['configuration_settings']['location_sets']) > 1)
//        {
//            $arrPossibleSearches_Start = $GLOBALS['USERDATA']['configuration_settings']['searches'];
//            $arrNewSearches = null;
//
//            foreach(array_keys($arrPossibleSearches_Start) as $l)
//            {
//
//                if(array_key_exists('location_user_specified_override', $arrPossibleSearches_Start[$l]) && isset($arrPossibleSearches_Start[$l]['location_user_specified_override']) && strlen($arrPossibleSearches_Start[$l]['location_user_specified_override'])>0)
//                {
//                    // this search already has a location from the user, so we shouldn't clone it with the location set
//                    continue;
//                }
//
//
//                $classPlug = new $GLOBALS['JOBSITE_PLUGINS'][$arrPossibleSearches_Start[$l]['site_name']]['class_name'](null, null);
//                if ($classPlug->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || $classPlug->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED)) {
//                    // this search doesn't support specifying locations so we shouldn't clone it for a second location set
//                    continue;
//                }
//
//
//                $fSkippedPrimary = false;
//                foreach($GLOBALS['USERDATA']['configuration_settings']['location_sets'] as $locSet)
//                {
//                    if(!$fSkippedPrimary) // Starts at index 1 because the primary location (index 0) was already set for all searches
//                    {
//                        $fSkippedPrimary = true;
//                        continue;
//                    }
//
//                    $curSiteName = \Scooper\strScrub($GLOBALS['USERDATA']['configuration_settings']['searches'][$l]['site_name'], FOR_LOOKUP_VALUE_MATCHING);
//                    if(array_key_exists($curSiteName, $GLOBALS['USERDATA']['configuration_settings']['excluded_sites']))
//                    {
//                        // this site was excluded for this location set, so continue.
//                        continue;
//                    }
//
//                    $newSearch = $this->cloneSearchDetailsRecordExceptFor($arrPossibleSearches_Start[$l], array('location_search_value'));
//                    $locTypeNeeded = $classPlug->getLocationSettingType();
//                    if(array_key_exists($locTypeNeeded, $locSet))
//                        $strSearchLocation = $locSet[$locTypeNeeded];
//                    else
//                        throw new IndexOutOfBoundsException(sprintf("Requested location type setting of '%s' is not valid.", $locTypeNeeded));
//
//                    $strOldSearchKey = $newSearch['key'] . "-" . strtolower($locSet['key']);
//                    if(substr_count($strOldSearchKey, "-loc-") > 0) { $strOldSearchKey = explode("-loc-", $strOldSearchKey)[0]; }
//                    $newSearch['key'] = $strOldSearchKey . "-loc-" . strtolower($locSet['key']);
//                    $newSearch['location_search_value'] = $strSearchLocation;
//
//                    // BUGBUG:  Workaround for a single plugin, Dice, to be able to get more than one location set parameter
//                    $newSearch['location_set_key'] = $locSet['key'];
//
//                    $GLOBALS['USERDATA']['configuration_settings']['searches'][] = $newSearch;
//
//                    $newSearch = null;
//                }
//            }
//        }

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
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added email from config.ini: '" . getArrayValuesAsString($tempEmail), \Scooper\C__DISPLAY_ITEM_DETAIL__);
                $settingsEmail['email_addresses'][] = $tempEmail;
            }
        }

        $GLOBALS['USERDATA']['configuration_settings']['email'] = \Scooper\array_copy($settingsEmail);

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
            'source_config_file_settings' => \Scooper\array_copy($iniKeywordSetup),
            'keywords_array' => null
        );

        // If keywords are in a string, split them out into an array instead before continuing
        if(isset($iniKeywordSetup['keywords']) && is_string($iniKeywordSetup['keywords']))
        {
            $tmpKeywordArray = explode(",", $iniKeywordSetup['keywords']);
            $arrKeywords = array();
            foreach($tmpKeywordArray as $kwd)
            {
                $scrubbedKwd = \Scooper\strScrub($kwd, ADVANCED_TEXT_CLEANUP);
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

    private function _getEmptyLocationSettingsSet_()
    {
        return array(
            'key' => null,
        );
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
//                    $GLOBALS['userdata']['previous_files_details'][$fileDetails['file_name_base']] = \Scooper\array_copy($fileDetails);
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
        catch (Exception $ex)
        {
            $GLOBALS['logger']->logLine($ex->getMessage(), \Scooper\C__DISPLAY_ERROR__);
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
            $GLOBALS['logger']->logLine("Using previously loaded " . countAssociativeArrayValues($GLOBALS['USERDATA']['title_negative_keyword_tokens']) . " tokenized title strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return;
        }

        if(!is_array($arrFileInput))
        {
            // No files were found, so bail
            $GLOBALS['logger']->logLine("No input files were found with title token strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
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
            $GLOBALS['logger']->logLine("Warning: No title negative keywords were found in the input source files " . getArrayValuesAsString($arrFileInput) . " to be filtered from job listings." , \Scooper\C__DISPLAY_WARNING__);
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
            $GLOBALS['logger']->logLine("Loaded " . countAssociativeArrayValues($GLOBALS['USERDATA']['title_negative_keyword_tokens']) . " tokens to use for filtering titles from '" . getArrayValuesAsString($inputfiles) . "'." , \Scooper\C__DISPLAY_WARNING__);

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
            $GLOBALS['logger']->logLine("Using previously loaded " . count($GLOBALS['USERDATA']['companies_regex_to_filter']) . " regexed company strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return;
        }
        $arrFileInput = $this->getInputFilesByType("regex_filter_companies");

        $GLOBALS['USERDATA']['companies_regex_to_filter'] = array();

        if(isset($GLOBALS['USERDATA']['companies_regex_to_filter']) && count($GLOBALS['USERDATA']['companies_regex_to_filter']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            $GLOBALS['logger']->logLine("Using previously loaded " . count($GLOBALS['USERDATA']['companies_regex_to_filter']) . " regexed title strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
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
                        $GLOBALS['logger']->logLine("Loading job Company regexes to filter from ".$fileDetail ['full_file_path']."." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
                        $classCSVFile = new \Scooper\ScooperSimpleCSV($fileDetail ['full_file_path'] , 'r');
                        $arrCompaniesTemp = $classCSVFile->readAllRecords(true,array('match_regex'));
                        $arrCompaniesTemp = $arrCompaniesTemp['data_rows'];
                        $GLOBALS['logger']->logLine(count($arrCompaniesTemp) . " companies found in the source file that will be automatically filtered from job listings." , \Scooper\C__DISPLAY_ITEM_DETAIL__);

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
                                    catch (Exception $ex)
                                    {
                                        $strError = "Regex test failed on company regex pattern " . $rxItem .".  Skipping.  Error: '".$ex->getMessage();
                                        $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                                        if(isDebug() == true) { throw new ErrorException( $strError); }
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
                $GLOBALS['logger']->logLine("No file specified for companies regexes to exclude from '" . getArrayValuesAsString($inputfiles) . "'.  Final list will not be filtered." , \Scooper\C__DISPLAY_WARNING__);
            else
                $GLOBALS['logger']->logLine("Could not load regex list for companies to exclude from '" . getArrayValuesAsString($inputfiles) . "'.  Final list will not be filtered." , \Scooper\C__DISPLAY_WARNING__);
        }
        else
        {
            $GLOBALS['logger']->logLine("Loaded " . count($GLOBALS['USERDATA']['companies_regex_to_filter']). " regexes to use for filtering companies from " . getArrayValuesAsString($inputfiles)  , \Scooper\C__DISPLAY_NORMAL__);

        }
    }
//
//    function parseJobsListForPage($objSimpHTML)
//    {
//        throw new ErrorException("parseJobsListForPage not supported for class" . get_class($this));
//    }
//    function parseTotalResultsCount($objSimpHTML)
//    {
//        throw new ErrorException("parseTotalResultsCount not supported for class " . get_class($this));
//    }


} 