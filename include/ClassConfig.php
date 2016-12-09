<?php
/**
 * Copyright 2014-16 Bryan Selner
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

if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/SitePlugins.php');


class ClassConfig extends ClassJobsSitePlugin
{
    protected $nNumDaysToSearch = -1;
    public $arrFileDetails = array('output' => null, 'output_subfolder' => null, 'config_ini' => null, 'user_input_files_details' => null);
    protected $arrEmailAddresses = null;
    protected $configSettings = array('searches' => null, 'keyword_sets' => null, 'location_sets' => null, 'number_days'=>VALUE_NOT_SUPPORTED, 'included_sites' => array(), 'excluded_sites' => array());
    protected $arrEmail_PHPMailer_SMTPSetup = null;
    protected $allConfigFileSettings = null;

    function getSearchConfiguration($strSubkey = null)
    {
        if(isset($strSubkey) && (isset($GLOBALS['USERDATA']['configurationSettings'][$strSubkey]) || $GLOBALS['USERDATA']['configurationSettings'][$strSubkey] == null))
            $ret = $GLOBALS['USERDATA']['configurationSettings'][$strSubkey];
        else
            $ret = $GLOBALS['USERDATA']['configurationSettings'];

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



    function initialize()
    {
        # increase memory consumed to fit larger job searches
        ini_set('memory_limit','500M');
        ini_set("auto_detect_line_endings", true);

        $GLOBALS['USERDATA'] = array();
        __initializeArgs__();

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Setting up application... ", \Scooper\C__DISPLAY_SECTION_START__);
        # After you've configured Pharse, run it like so:
        $GLOBALS['OPTS'] = Pharse::options($GLOBALS['OPTS_SETTINGS']);

        $GLOBALS['USERDATA']['companies_regex_to_filter'] = null;
        $GLOBALS['USERDATA']['configurationSettings'] = array();

        // and to make sure our notes get updated on active jobs
        // that we'd seen previously
        // Now go see what we got back for each of the sites
        //
        foreach($GLOBALS['JOBSITE_PLUGINS']  as $site)
        {
            assert(isset($site['name']));
            $GLOBALS['JOBSITE_PLUGINS'][$site['name']]['include_in_run'] = is_IncludeSite($site['name']);
        }
        $includedsites = array_filter($GLOBALS['JOBSITE_PLUGINS'], function($k) {
            return $k['include_in_run'];
        });
        $excludedsites = array_filter($GLOBALS['JOBSITE_PLUGINS'], function($k) {
            return !($k['include_in_run']);
        });
        $GLOBALS['USERDATA']['configurationSettings']['included_sites'] = array_combine(array_keys($includedsites), array_column($includedsites, 'name'));
        $GLOBALS['USERDATA']['configurationSettings']['excluded_sites'] = array_combine(array_keys($excludedsites), array_column($excludedsites, 'name'));
        $GLOBALS['USERDATA']['configurationSettings']['searches'] = array();

        $tmpDebug = \Scooper\get_PharseOptionValue('use_debug');
        switch($tmpDebug)
        {
            case 1:
                $GLOBALS['OPTS']['DEBUG'] = true;
                $GLOBALS['OPTS']['VERBOSE'] = false;
                break;

            case 2:
                $GLOBALS['OPTS']['DEBUG'] = true;
                $GLOBALS['OPTS']['VERBOSE'] = true;
                break;

            default:
                $GLOBALS['OPTS']['DEBUG'] = false;
                $GLOBALS['OPTS']['VERBOSE'] = false;
                break;
        }


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
        $this->__setupOutputFolders__($userOutfileDetails['directory'], $GLOBALS['USERDATA']['user_unique_key']);

        if(!isset($GLOBALS['logger'])) $GLOBALS['logger'] = new \Scooper\ScooperLogger($GLOBALS['USERDATA']['directories']['debug'] );

        $strOutfileArrString = getArrayValuesAsString( $GLOBALS['USERDATA']['directories']);
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Output folders configured: " . $strOutfileArrString, \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $GLOBALS['USERDATA']['AWS'] = array("S3" => array("bucket" => \Scooper\get_PharseOptionValue("s3bucket"), "region" => \Scooper\get_PharseOptionValue("s3region") ));

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

        $GLOBALS['USERDATA']['configurationSettings']['number_days']= \Scooper\get_PharseOptionValue('number_days');
        if($GLOBALS['USERDATA']['configurationSettings']['number_days']== false) { $GLOBALS['OPTS']['number_days'] = 1; $GLOBALS['USERDATA']['configurationSettings']['number_days'] = 1; }
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine($GLOBALS['OPTS']['number_days'] . " days configured for run. ", \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $this->_setPreviouslyReviewedJobsInputFiles_();

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Completed configuration load.", \Scooper\C__DISPLAY_SUMMARY__);

    }

    function getLogger()
    {
        return $GLOBALS['logger'];
    }

    private function __setupOutputFolders__($outputDirectory, $userKey)
    {
        if(! $outputDirectory)
        {
            throw new ErrorException("Required value for the output folder was not specified. Exiting.");
        }

        $path = join(DIRECTORY_SEPARATOR, array($outputDirectory, $userKey, "debug/"));
        $details = \Scooper\getFilePathDetailsFromString($path, \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
        $GLOBALS['USERDATA']['directories']['debug'] = realpath($details['directory']);

        $path = join(DIRECTORY_SEPARATOR, array($outputDirectory, $userKey, "staged/"));
        $details = \Scooper\getFilePathDetailsFromString($path, \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
        $GLOBALS['USERDATA']['directories']['staging'] = realpath($details['directory']);

        for($n=1; $n <= 4; $n++)
        {
            $path = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['staging'], getStageKeyPrefix($n, STAGE_FLAG_EXCLUDEPARENTPATH)));
            $details = \Scooper\getFilePathDetailsFromString($path, \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
            $GLOBALS['USERDATA']['directories']["stage".$n] = realpath($details['directory']);
        }

        $path = join(DIRECTORY_SEPARATOR, array($outputDirectory, $userKey, "results/"));
        $details = \Scooper\getFilePathDetailsFromString($path, \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
        $GLOBALS['USERDATA']['directories']['results'] = realpath($details['directory']);

    }



    private function _LoadAndMergeAllConfigFilesRecursive($fileConfigToLoad)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading INI file: ".$fileConfigToLoad, \Scooper\C__DISPLAY_SECTION_START__);

        $iniParser = new IniParser($fileConfigToLoad);
        $iniParser->use_array_object = false;
        $tempConfigSettings = $iniParser->parse();
        $iniParser = null;
        $this->_arrConfigFileSettings_[$fileConfigToLoad] = \Scooper\array_copy($tempConfigSettings);

        if(isset($tempConfigSettings['settings_files']))
        {
            $settingFiles = $tempConfigSettings['settings_files'];
            foreach($settingFiles as $nextConfigFile)
            {
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Recursing into child settings file ".$nextConfigFile, \Scooper\C__DISPLAY_ITEM_DETAIL__);
                $this->_LoadAndMergeAllConfigFilesRecursive($nextConfigFile);
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added child settings file ".$nextConfigFile, \Scooper\C__DISPLAY_ITEM_RESULT__);
            }
        }
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded config settings file " . $fileConfigToLoad . " and any descendant children config setting files.  ".var_export($GLOBALS['OPTS'], true), \Scooper\C__DISPLAY_SUMMARY__);

        $allConfigfileSettings = [];
        foreach($this->_arrConfigFileSettings_ as $tempConfig)
        {
            $allConfigfileSettings = array_merge_recursive($allConfigfileSettings, $tempConfig);
        }

        $this->allConfigFileSettings = $allConfigfileSettings;

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

        $this->_parseEmailSetupFromINI_($config);

        //
        // Load the global search data that will be used to create
        // and configure all searches
        //
        $this->_readGlobalSearchParamtersFromConfig_($config);

        //
        // Load Plugin Specific settings from the config
        //
        $this->_readPluginSettingsFromConfig_($config);


        $this->_readKeywordSetsFromConfig_($config);

        $this->_readLocationSetsFromConfig_($config);

        //
        // Load any specific searches specified by the user in the config
        //
        $this->__readSearchesFromConfig__($config);

        // Update the searches with the keyword values
        if(isset($GLOBALS['USERDATA']['configurationSettings']['keyword_sets']) && is_array($GLOBALS['USERDATA']['configurationSettings']['keyword_sets']) && count($GLOBALS['USERDATA']['configurationSettings']['keyword_sets']) >= 1)
        {
            reset($GLOBALS['USERDATA']['configurationSettings']['keyword_sets']);
            $primarySet = current($GLOBALS['USERDATA']['configurationSettings']['keyword_sets']);

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
        if(isset($GLOBALS['USERDATA']['configurationSettings']['location_sets']) && is_array($GLOBALS['USERDATA']['configurationSettings']['location_sets']) && count($GLOBALS['USERDATA']['configurationSettings']['location_sets']) >= 1)
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
        return array('type'=> null, 'name'=>null, 'address' => null);
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



    private function __readSearchesFromConfig__($config)
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
                    if(isset($retSearch)) $GLOBALS['USERDATA']['configurationSettings']['searches'][$retSearch['key']] = $retSearch;
                }
            }
            else
            {
                $retSearch = $this->_parseSearchFromINI_($config['search']);
                if(isset($retSearch)) $GLOBALS['USERDATA']['configurationSettings']['searches'][$retSearch['key']] = $retSearch;
            }
        }
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded " . count($GLOBALS['USERDATA']['configurationSettings']['searches']) . " searches. ", \Scooper\C__DISPLAY_ITEM_RESULT__);
    }

    private function _parseSearchFromINI_($iniSearch)
    {
        $tempSearch = $this->getEmptySearchDetailsRecord();

        if(isset($iniSearch['key'])) $tempSearch['key'] = \Scooper\strScrub($iniSearch['key'], REMOVE_EXTRA_WHITESPACE | LOWERCASE );
        if(isset($iniSearch['jobsite'])) $tempSearch['site_name'] = \Scooper\strScrub($iniSearch['jobsite'], REMOVE_EXTRA_WHITESPACE | LOWERCASE );
        assert(isset($iniSearch['jobsite']));
        $strJobSiteKey = \Scooper\strScrub($iniSearch['jobsite'], FOR_LOOKUP_VALUE_MATCHING | LOWERCASE );
        if(isset($GLOBALS['USERDATA']['configurationSettings']['included_sites']) && !isset($GLOBALS['USERDATA']['configurationSettings']['included_sites'][$strJobSiteKey]))
        {
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine($iniSearch['jobsite'] . "search " .$iniSearch['name'] . " was not added; " . $strJobSiteKey . " is excluded for this run.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return null;
        }
        $tempSearch['name'] = (isset($iniSearch['jobsite']) ? $iniSearch['jobsite'] . ': ' : "") . $iniSearch['name'];
        if(isset($iniSearch['url_format'])) $tempSearch['base_url_format']  = $iniSearch['url_format'];
        if(isset($iniSearch['location'])) $tempSearch['location_user_specified_override']  = $iniSearch['location'];

        if(isset($iniSearch['keyword_match_type_string']) && strlen($iniSearch['keyword_match_type_string'] ) > 0)
        {
            $flagType = $this->_getKeywordMatchFlagFromString_($iniSearch['keyword_match_type_string'] );
            if($flagType != null)
            {
                $tempSearch['user_flag_settings'] = $flagType;
            }
        }

        if(!isset($tempSearch['key']) || strlen($tempSearch['key']) <= 0)
        {
            $tempSearch['key'] = \Scooper\strScrub($tempSearch['site_name'], FOR_LOOKUP_VALUE_MATCHING) . "-" . \Scooper\strScrub($tempSearch['name'], FOR_LOOKUP_VALUE_MATCHING);
        }

        $strSearchAsString = getArrayValuesAsString($tempSearch);
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Search loaded from config INI: " . $strSearchAsString, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        return $tempSearch;
    }


    private function _readPluginSettingsFromConfig_($config)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading plugin setup information from config file...", \Scooper\C__DISPLAY_ITEM_START__);

        foreach($GLOBALS['JOBSITE_PLUGINS'] as $classPlugin)
        {
            $name = strtolower($classPlugin['class_name']) . "_settings";
            if(isset($config[$name]) && is_array($config[$name]))
            {
                $GLOBALS['JOBSITE_PLUGINS'][$classPlugin['name']]['other_settings'] = $config[$name];
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Found settings for " . $name, \Scooper\C__DISPLAY_ITEM_DETAIL__);

            }
        }

    }

    private function _readGlobalSearchParamtersFromConfig_($config)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading global search settings from config file...", \Scooper\C__DISPLAY_ITEM_START__);
        if(isset($config['global_search_options']) && is_array($config['global_search_options']) && isset($config['global_search_options']['excluded_jobsites']))
        {
            if(is_string($config['global_search_options']['excluded_jobsites'])) { $config['global_search_options']['excluded_jobsites'] = explode(",", $config['global_search_options']['excluded_jobsites']); }
            if(!is_array($config['global_search_options']['excluded_jobsites'])) { $config['global_search_options']['excluded_jobsites'] = array($config['global_search_options']['excluded_jobsites']); }
            foreach($config['global_search_options']['excluded_jobsites'] as $excludedSite)
            {
                $excludedSite = strtolower(trim($excludedSite));
                $GLOBALS['USERDATA']['configurationSettings']['excluded_sites'][$excludedSite] = $excludedSite;
                if(isset($GLOBALS['USERDATA']['configurationSettings']['included_sites'][$excludedSite])) unset($GLOBALS['USERDATA']['configurationSettings']['included_sites'][$excludedSite]);
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Setting " . $excludedSite . " as excluded for this run.", \Scooper\C__DISPLAY_ITEM_DETAIL__);

            }

        }
    }

    private function _readLocationSetsFromConfig_($config)
    {
        if (!$config) throw new ErrorException("Invalid configuration.  Cannot load user's searches.");

        if(!array_key_exists('location_sets', $GLOBALS['USERDATA']['configurationSettings']))
            $GLOBALS['USERDATA']['configurationSettings']['location_sets'] = array();

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
                        $strSetName = 'LocationSet' . (count($GLOBALS['USERDATA']['configurationSettings']['location_sets']) + 1);
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
                        $arrNewLocationSet['name'] = $strSetName ;
                        $arrNewLocationSet = $this->_parseAndAddLocationSet_($arrNewLocationSet, $iniSettings);

                        if(isset($iniSettings['excluded_jobsites']) && count($iniSettings['excluded_jobsites']) > 0)
                        {
                            foreach($iniSettings['excluded_jobsites'] as $excludedSite)
                            {
                                $excludedSite = strtolower($excludedSite);
                                $arrNewLocationSet['excluded_jobsites'][$excludedSite] = $excludedSite;
                            }
                        }
                        if(is_array($arrNewLocationSet['excluded_jobsites']))
                        {
                            $arrNewLocationSet['excluded_jobsites'] = \Scooper\my_merge_add_new_keys($arrNewLocationSet['excluded_jobsites'], $GLOBALS['USERDATA']['configurationSettings']['excluded_sites']);
                        }

                        $strSettingStrings = getArrayValuesAsString($arrNewLocationSet);
                        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added location search settings '" . $strSetName . ": " . $strSettingStrings, \Scooper\C__DISPLAY_ITEM_DETAIL__);

                        $GLOBALS['USERDATA']['configurationSettings']['location_sets'][$strSetName] = $arrNewLocationSet;

                    }
                }
            }

            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded " . count($GLOBALS['USERDATA']['configurationSettings']['location_sets']) . " location sets. ", \Scooper\C__DISPLAY_ITEM_RESULT__);
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


    private function _readKeywordSetsFromConfig_($config)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading keyword set from config file...", \Scooper\C__DISPLAY_ITEM_START__);
        if(!array_key_exists('keyword_sets', $GLOBALS['USERDATA']['configurationSettings']))
        {
            $GLOBALS['USERDATA']['configurationSettings']['keyword_sets'] = array();
        }
        if(isset($config['search_keyword_set']))
        {
            foreach(array_keys($config['search_keyword_set']) as $key)
            {
                $ini_keyword_set = $config['search_keyword_set'][$key];
                $ini_keyword_set['key'] = $key;

                $strSetKey = 'ks' . (count($GLOBALS['USERDATA']['configurationSettings']['keyword_sets']) + 1);
                if(isset($ini_keyword_set['name']) && strlen($ini_keyword_set['name']) > 0)
                {
                    $strSetKey = $ini_keyword_set['name'];
                }
                elseif(isset($ini_keyword_set['key']) && strlen($ini_keyword_set['key']) > 0)
                {
                    $strSetKey = $ini_keyword_set['key'];
                }

                $GLOBALS['USERDATA']['configurationSettings']['keyword_sets'][$strSetKey] = $this->_getNewKeywordSettingsSet_(strtolower($strSetKey), $ini_keyword_set);

                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added keyword set '" . $GLOBALS['USERDATA']['configurationSettings']['keyword_sets'][$strSetKey]['name'] . "' with keywords = " . getArrayValuesAsString($GLOBALS['USERDATA']['configurationSettings']['keyword_sets'][$strSetKey]['keywords_array']) . (($ini_keyword_set['keyword_match_type'] != null && strlen($ini_keyword_set['keyword_match_type'] ) > 0) ? " matching " . $ini_keyword_set['keyword_match_type'] : ""), \Scooper\C__DISPLAY_ITEM_DETAIL__);

            }

        }
    }


    private function _updateSearchesWithKeywordSet_($keywordSet)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Updating user-specified searches with keyword set details", \Scooper\C__DISPLAY_ITEM_START__);
        //for($c = 0; $c < count($GLOBALS['USERDATA']['configurationSettings']['searches']); $c++)
        foreach(array_keys($GLOBALS['USERDATA']['configurationSettings']['searches']) as $c)
        {
            $GLOBALS['USERDATA']['configurationSettings']['searches'][$c]['keywords_array'] = $keywordSet['keywords_array'];
            $GLOBALS['USERDATA']['configurationSettings']['searches'][$c]['keywords_array_tokenized'] = $keywordSet['keywords_array_tokenized'];

            //
            // If this search already has any flags set on it, then do not overwrite that value for this search
            // Otherwise, set it to be the value that any keyword set we're adding has
            //
            if(!array_key_exists('user_setting_flags', $GLOBALS['USERDATA']['configurationSettings']['searches'][$c]) || $GLOBALS['USERDATA']['configurationSettings']['searches'][$c]['user_setting_flags'] == null || $GLOBALS['USERDATA']['configurationSettings']['searches'][$c]['user_setting_flags'] == 0)
            {
                $GLOBALS['USERDATA']['configurationSettings']['searches'][$c]['user_setting_flags'] = $keywordSet['keyword_match_type_flag'];
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
        if(isset($GLOBALS['USERDATA']['configurationSettings']['keyword_sets']) && count($GLOBALS['USERDATA']['configurationSettings']['keyword_sets']) > 0)
        {
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Adding new searches for user's keyword sets ", \Scooper\C__DISPLAY_ITEM_START__);

            foreach($GLOBALS['USERDATA']['configurationSettings']['keyword_sets'] as $keywordSet)
            {
                $arrSkippedPlugins = null;
                if(isset($keywordSet['included_jobsites']))
                {

                    if(count($keywordSet['included_jobsites'])>0)
                    {
                        foreach($keywordSet['included_jobsites'] as $siteToSearch)
                        {
                            $classPlug = new $GLOBALS['JOBSITE_PLUGINS'][$siteToSearch]['class_name'](null, null);
                            $thisSearch = $this->getEmptySearchDetailsRecord();
                            $thisSearch ['name'] =  \Scooper\strScrub($keywordSet['key'], FOR_LOOKUP_VALUE_MATCHING);
                            $thisSearch ['key'] = \Scooper\strScrub($siteToSearch, FOR_LOOKUP_VALUE_MATCHING) . \Scooper\strScrub($keywordSet['key'], FOR_LOOKUP_VALUE_MATCHING);
                            $thisSearch ['site_name']  = $siteToSearch;
                            $thisSearch ['user_setting_flags'] = $keywordSet['keyword_match_type_flag'];

                            if($classPlug->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED))
                            {
                                $arrSkippedPlugins[] = $siteToSearch;
                                continue;
                            }


//                            $baseSearch ['keyword_set']  = $searchKywdSet['keywords_array'];
//                            $baseSearch ['keywords_array_tokenized'] = $searchKywdSet['keywords_array_tokenized'];

                            if($classPlug->isBitFlagSet(C__JOB_KEYWORD_MULTIPLE_TERMS_SUPPORTED))
                            {
                                $thisSearch['keywords_array'] = $keywordSet['keywords_array'];
                                $thisSearch['keywords_array_tokenized'] = $keywordSet['keywords_array_tokenized'];

                                $GLOBALS['USERDATA']['configurationSettings']['searches'][$thisSearch['key']] = $thisSearch;
                            }
                            else // if not, we need to add search for each keyword using that word as a single value in a keyword set
                            {
                                $arrKeys = array_keys($keywordSet['keywords_array']);
                                foreach($arrKeys as $key)
                                {
                                    $newSearch = \Scooper\array_copy($thisSearch);
                                    $newSearch['key'] = $newSearch['key']."-".\Scooper\strScrub($key, FOR_LOOKUP_VALUE_MATCHING);
                                    $newSearch['name'] = $newSearch['key'];
                                    $newSearch['keywords_array'] = array($keywordSet['keywords_array'][$key]);
                                    $newSearch['keywords_array_tokenized'] = array($keywordSet['keywords_array_tokenized'][$key]);

                                    $GLOBALS['USERDATA']['configurationSettings']['searches'][$newSearch['key']] = $newSearch;
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
        if(count($GLOBALS['USERDATA']['configurationSettings']['searches']) <= 0)
        {
            return;
        }

        reset($GLOBALS['USERDATA']['configurationSettings']['location_sets']);
        $primaryLocationSet = current($GLOBALS['USERDATA']['configurationSettings']['location_sets']);

        assert($primaryLocationSet!=null);

        if(!isset($primaryLocationSet))
        {
            return;
        }

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Updating all searches with primary location set '" . $primaryLocationSet['name'] . "...", \Scooper\C__DISPLAY_NORMAL__);

        //
        // The first location set will be added to all searches always.
        // This function adds a copy of all searches if there was 2 or more
        // location sets specified
        //
        //
        if(isset($primaryLocationSet))
        {
            foreach(array_keys($GLOBALS['USERDATA']['configurationSettings']['searches']) as $searchKey)
            {
                if(isset($GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]['location_user_specified_override']) && strlen($GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]['location_user_specified_override'])>0)
                {
                    // this search already has a location from the user, so we don't need to update it with the location set
                    continue;
                }

                $curSiteName = \Scooper\strScrub($GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]['site_name'], FOR_LOOKUP_VALUE_MATCHING);

                if(isset($primaryLocationSet['excluded_jobsites'][$curSiteName]) && strlen($primaryLocationSet['excluded_jobsites'][$curSiteName]) > 0)
                {
                    // this site was excluded for this location set, so continue.
                    // TODO[Future] Try to use a secondary location set for the search instead

                    // TODO[future] Update the search in such a way that it doesn't try to process later without a location.  Maybe a "is_valid = T/F" flag?
                    continue;
                }


                $classPlug = new $GLOBALS['JOBSITE_PLUGINS'][$GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]['site_name']]['class_name'](null, null);
                $GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]['location_set'] = $primaryLocationSet;
                $strSearchLocation = $classPlug->getLocationValueForLocationSetting($GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]);
                if($strSearchLocation != VALUE_NOT_SUPPORTED)
                {
                    $GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]['key'] = $GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]['key'] . "-loc-" . strtolower($primaryLocationSet['name']);
                    $GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]['name'] = $GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]['name'] . "-loc-" . strtolower($primaryLocationSet['name']);
                    $GLOBALS['USERDATA']['configurationSettings']['searches'][$searchKey]['location_search_value'] = $strSearchLocation;
                }
            }
        }

    }

    private function _addSearchesForAdditionalLocationSets_()
    {
        if(count($GLOBALS['USERDATA']['configurationSettings']['searches']) <= 0)
        {
            return;
        }

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Creating searches for additional location sets after the primary by cloning and updating all searches", \Scooper\C__DISPLAY_ITEM_START__);
        $arrSkippedPlugins = null;


        //
        // The first location set will be added to all searches always.
        // This function adds a copy of all searches if there was 2 or more
        // location sets specified
        //
        //
        if(isset($GLOBALS['USERDATA']['configurationSettings']['location_sets']) && is_array($GLOBALS['USERDATA']['configurationSettings']['location_sets']) && count($GLOBALS['USERDATA']['configurationSettings']['location_sets']) > 1)
        {
            $arrPossibleSearches_Start = $GLOBALS['USERDATA']['configurationSettings']['searches'];
            $arrNewSearches = null;

            for($l = 0; $l < count($arrPossibleSearches_Start) ; $l++)
            {

                if(isset($arrPossibleSearches_Start[$l]['location_user_specified_override']) && strlen($arrPossibleSearches_Start[$l]['location_user_specified_override'])>0)
                {
                    // this search already has a location from the user, so we shouldn't clone it with the location set
                    continue;
                }


                $classPlug = new $GLOBALS['JOBSITE_PLUGINS'][$arrPossibleSearches_Start[$l]['site_name']]['class_name'](null, null);
                if ($classPlug->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || $classPlug->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED)) {
                    // this search doesn't support specifying locations so we shouldn't clone it for a second location set
                    continue;
                }


                $fSkippedPrimary = false;
                foreach($GLOBALS['USERDATA']['configurationSettings']['location_sets'] as $locSet)
                {
                    if(!$fSkippedPrimary) // Starts at index 1 because the primary location (index 0) was already set for all searches
                    {
                        $fSkippedPrimary = true;
                        continue;
                    }

                    $curSiteName = \Scooper\strScrub($GLOBALS['USERDATA']['configurationSettings']['searches'][$l]['site_name'], FOR_LOOKUP_VALUE_MATCHING);
                    if(isset($locSet['excluded_jobsites'][$curSiteName]) && strlen($locSet['excluded_jobsites'][$curSiteName]) > 0)
                    {
                        // this site was excluded for this location set, so continue.
                        continue;
                    }

                    $newSearch = $this->cloneSearchDetailsRecordExceptFor($arrPossibleSearches_Start[$l], array('location_search_value', 'location_set'));
                    $newSearch['location_set'] = $locSet;
                    $strSearchLocation = $classPlug->getLocationValueForLocationSetting($newSearch);
                    if($strSearchLocation != VALUE_NOT_SUPPORTED)
                    {
                        $strOldSearchKey = $newSearch['key'] . "-" . strtolower($locSet['name']);
                        if(substr_count($strOldSearchKey, "-loc-") > 0) { $strOldSearchKey = explode("-loc-", $strOldSearchKey)[0]; }
                        $newSearch['key'] = $strOldSearchKey . "-loc-" . strtolower($locSet['name']);
                        $newSearch['name'] = $strOldSearchKey . "-loc-" . strtolower($locSet['name']);
                        $newSearch['location_search_value'] = $strSearchLocation;

                        $GLOBALS['USERDATA']['configurationSettings']['searches'][] = $newSearch;

                    }
                    $newSearch = null;
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
                $settingsEmail['email_addresses'][$tempEmail['type']][] = $tempEmail;
            }
        }

        $GLOBALS['USERDATA']['configurationSettings']['email'] = \Scooper\array_copy($settingsEmail);


    }

    private $_arrConfigFileSettings_ = [];

    private function _getNewKeywordSettingsSet_($setkey=null, $iniKeywordSetup)
    {
        $flagType = null;
        if(isset($ini_keyword_set['keyword_match_type']) && strlen($ini_keyword_set['keyword_match_type'] ) > 0)
        {
            $flagType = $this->_getKeywordMatchFlagFromString_($ini_keyword_set['keyword_match_type'] );
        }


        $set =  array(
            'key' => $setkey,
            'name' =>  $setkey,
            'source_config_file_settings' => \Scooper\array_copy($iniKeywordSetup),
            'keywords_array' => null,
            'keyword_match_type_string' => $iniKeywordSetup['keyword_match_type'],
            'keyword_match_type_flag' => $flagType,
            'excluded_jobsites' => $GLOBALS['USERDATA']['configurationSettings']['excluded_sites'],
            'included_jobsites' => $GLOBALS['USERDATA']['configurationSettings']['included_sites'],
            'settings_scope' => $iniKeywordSetup['settings_scope']
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
            'name' => null,
            'excluded_jobsites' => array(),
        );
    }


    private function _setPreviouslyReviewedJobsInputFiles_()
    {
        $arrFileInput = $this->getInputFilesByType("previously_reviewed_file");
        if(is_array($arrFileInput) && $arrFileInput != null)
        {
            foreach($arrFileInput as $file)
            {
                $details = \Scooper\getFilePathDetailsFromString($file, \Scooper\C__FILEPATH_FILE_MUST_EXIST);
                if($details)
                {
                    $GLOBALS['userdata']['previous_files_details'][$details['file_name_base']] = \Scooper\array_copy($details);
                }
            }
        }
    }


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
            if(isDebug()) { throw $ex; }
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
                    $file = fopen($fileDetail ['full_file_path'],"r");
                    $headers = fgetcsv($file);
                    while (($rowData = fgetcsv($file, null, ",", "\"")) !== false) {
                        $arrRec = array_combine($headers, $rowData);
                        $arrRec['negative_keywords'] = strtolower($arrRec['negative_keywords']);
                        $arrNegKwds[$arrRec["negative_keywords"]] = $arrRec;
                    }

                    fclose($file);

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

            $GLOBALS['logger']->logLine("Loaded " . countAssociativeArrayValues($GLOBALS['USERDATA']['title_negative_keyword_tokens']) . " tokens to use for filtering titles from '" . getArrayValuesAsString($this->getInputFilesByType("negative_title_keywords")) . "'." , \Scooper\C__DISPLAY_WARNING__);

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
                                        if(isDebug()) { throw new ErrorException( $strError); }
                                    }
                                }
                            }
                            $fCompaniesLoaded = true;
                        }
                    }
                }
            }
        }

        if($fCompaniesLoaded == false)
        {
            if(count($arrFileInput) == 0)
                $GLOBALS['logger']->logLine("No file specified for companies regexes to exclude from '" . getArrayValuesAsString($arrFileInput) . "'.  Final list will not be filtered." , \Scooper\C__DISPLAY_WARNING__);
            else
                $GLOBALS['logger']->logLine("Could not load regex list for companies to exclude from '" . getArrayValuesAsString($arrFileInput) . "'.  Final list will not be filtered." , \Scooper\C__DISPLAY_WARNING__);
        }
        else
        {
            $GLOBALS['logger']->logLine("Loaded " . count($GLOBALS['USERDATA']['companies_regex_to_filter']). " regexes to use for filtering companies from " . getArrayValuesAsString($arrFileInput)  , \Scooper\C__DISPLAY_WARNING__);

        }
    }

    function parseJobsListForPage($objSimpHTML)
    {
        throw new ErrorException("parseJobsListForPage not supported for class" . get_class($this));
    }
    function parseTotalResultsCount($objSimpHTML)
    {
        throw new ErrorException("parseTotalResultsCount not supported for class " . get_class($this));
    }


} 