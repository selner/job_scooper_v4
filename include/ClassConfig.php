<?php
/**
 * Copyright 2014-15 Bryan Selner
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
    protected $arrFileDetails = array('output' => null, 'output_subfolder' => null, 'config_ini' => null, 'user_input_files' => null);
    protected $arrEmailAddresses = null;
    protected $configSettings = array('searches' => null, 'keyword_sets' => null, 'location_sets' => null, 'number_days'=>VALUE_NOT_SUPPORTED, 'included_sites' => array(), 'excluded_sites' => array(), 'excluded_plugin_types' => array());
    protected $arrEmail_PHPMailer_SMTPSetup = null;


    function getSearchConfiguration($strSubkey = null)
    {
        if(isset($strSubkey) && (isset($this->configSettings[$strSubkey]) || $this->configSettings[$strSubkey] == null))
            $ret = $this->configSettings[$strSubkey];
        else
            $ret = $this->configSettings;

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
        if ($file_key_name == 'user_input_files') {
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
        __initializeArgs__();

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Setting up application... ", \Scooper\C__DISPLAY_SECTION_START__);
        # After you've configured Pharse, run it like so:
        $GLOBALS['OPTS'] = Pharse::options($GLOBALS['OPTS_SETTINGS']);

        $GLOBALS['DATA']['titles_to_filter'] = null;
        $GLOBALS['DATA']['titles_regex_to_filter'] = null;
        $GLOBALS['DATA']['companies_regex_to_filter'] = null;

        // and to make sure our notes get updated on active jobs
        // that we'd seen previously
        // Now go see what we got back for each of the sites
        //
        foreach($GLOBALS['DATA']['site_plugins']  as $site)
        {
            assert(isset($site['name']));
            $fIsIncludedInRun = is_IncludeSite($site['name']);
            if(isset($GLOBALS['DATA']['site_plugins'][$site['name']]))
            {
                if(isset($GLOBALS['DATA']['site_plugins'][$site['name']]['include_in_run']))
                   $GLOBALS['DATA']['site_plugins'][$site['name']]['include_in_run'] = $fIsIncludedInRun;
            }
            // Initialize the config settings for the site list using the options set by the user
            // on the command line
            if($fIsIncludedInRun)
            {
                $this->configSettings['included_sites'][$site['name']] = $site['name'];
            }
            else
            {
                $this->configSettings['excluded_sites'][$site['name']] = $site['name'];
            }
        }

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

        if($GLOBALS['OPTS']['use_config_ini_given'])
        {
//            throw new ErrorException("Config ini files not yet supported!");
            $this->arrFileDetails['config_ini'] = \Scooper\set_FileDetails_fromPharseSetting("use_config_ini", 'config_file_details', true);
            if(!isset($GLOBALS['logger'])) $GLOBALS['logger'] = new \Scooper\ScooperLogger($this->arrFileDetails['config_ini']['directory'] );
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Log file for run being written to: " . $this->arrFileDetails['config_ini']['directory'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading configuration file details from " . $this->arrFileDetails['config_ini']['full_file_path'], \Scooper\C__DISPLAY_ITEM_DETAIL__);
            if($this->arrFileDetails['config_ini']['full_file_path'] )

            $iniParser = new IniParser($this->arrFileDetails['config_ini']['full_file_path']);
            $iniParser->use_array_object = false;
            $confTemp = $iniParser->parse();
            $iniParser = null;
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
            $this->setupRunFromAllConfigsRecursive($this->arrFileDetails['config_ini']['full_file_path'], $confTemp);
        }


        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Configuring specific settings for this run... ", \Scooper\C__DISPLAY_SECTION_START__);

        $this->configSettings['number_days']= \Scooper\get_PharseOptionValue('number_days');
        if($this->configSettings['number_days']== false) { $GLOBALS['OPTS']['number_days'] = 1; $this->configSettings['number_days'] = 1; }
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine($GLOBALS['OPTS']['number_days'] . " days configured for run. ", \Scooper\C__DISPLAY_ITEM_DETAIL__);


        // Override any INI file setting with the command line output file path & name
        // the user specificed (if they did)
        $userOutfileDetails = \Scooper\get_FileDetails_fromPharseOption("output_file", false);
        if(!isset($GLOBALS['logger'])) $GLOBALS['logger'] = new \Scooper\ScooperLogger($userOutfileDetails['directory'] );
        if($userOutfileDetails['has_directory'])
        {
             $this->arrFileDetails['output'] = $userOutfileDetails;
        }

        // Now setup all the output folders
        $this->__setupOutputFolders__();

        $strOutfileArrString = getArrayValuesAsString( $this->arrFileDetails['output']);
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Output file configured: " . $strOutfileArrString, \Scooper\C__DISPLAY_ITEM_DETAIL__);


        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Completed configuration load.", \Scooper\C__DISPLAY_SUMMARY__);

    }


    function __destruct()
    {
//        if(isDebug())
//             if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }
    }

    function getMyOutputFileFullPath($strFilePrefix = "")
    {
        return parent::getOutputFileFullPath($this->siteName . "_" . $strFilePrefix, "jobs", "csv");
    }

    private function __setupOutputFolders__()
    {
        if(! $this->arrFileDetails['output']['has_directory'])
        {
            throw new ErrorException("Required value for the output folder was not specified. Exiting.");
        }

        if(! $this->arrFileDetails['output']['has_file'])
        {
            $strDefaultFileName = getDefaultJobsOutputFileName("", "jobs", "csv");

             $this->arrFileDetails['output'] = \Scooper\parseFilePath( $this->arrFileDetails['output']['directory'] .  $strDefaultFileName);
        }
        $this->arrFileDetails['output_subfolder'] = $this->createOutputSubFolder( $this->arrFileDetails['output']);
    }



    private function setupRunFromAllConfigsRecursive($configFilePath, $config, $fRecursed = false)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading INI file: ".$configFilePath, \Scooper\C__DISPLAY_SECTION_START__);

        if(isset($config['settings_files']))
        {
            $settingFiles = $config['settings_files'];
            foreach($settingFiles as $nextConfigFile)
            {
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Child INI found: ".$nextConfigFile, \Scooper\C__DISPLAY_ITEM_DETAIL__);

                $iniParser = new IniParser($nextConfigFile);
                $iniParser->use_array_object = false;
                $nextConfig = $iniParser->parse();
                $iniParser = null;
                $this->setupRunFromAllConfigsRecursive($nextConfigFile, $nextConfig, true);
            }
        }

        $this->_setupRunFromConfig_($config, $fRecursed);
    }

    private function _setupRunFromConfig_($config, $fRecursed = false)
    {
        if(isset($config['output']))
        {
            if(isset($config['output']['folder']))
            {
                 $this->arrFileDetails['output'] = \Scooper\parseFilePath($config['output']['folder']);
            }

            if(isset($config['output']['file']))
            {
                 $this->arrFileDetails['output'] = \Scooper\parseFilePath( $this->arrFileDetails['output'] . $config['output']['file']);
            }
        }


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
        //
        //



        //
        // Load the global search data that will be used to create
        // and configure all searches
        //

        $this->_readGlobalSearchParamtersFromConfig_($config);


        $this->_readKeywordSetsFromConfig_($config);

        $this->_readLocationSetsFromConfig_($config);

        //
        // Load any specific searches specified by the user in the config
        //
        $this->__readSearchesFromConfig__($config);

        // Update the searches with the keyword values
        if(isset($this->configSettings['keyword_sets']) && is_array($this->configSettings['keyword_sets']) && count($this->configSettings['keyword_sets']) >= 1)
        {
            reset($this->configSettings['keyword_sets']);
            $primarySet = current($this->configSettings['keyword_sets']);

            $this->_updateSearchesWithKeywordSet_($primarySet);
        }

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded INI file:  ".var_export($GLOBALS['OPTS'], true), \Scooper\C__DISPLAY_SUMMARY__);

        // Only run this section after processing the parent/last one
        if($fRecursed == false)
        {
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
            if(isset($this->configSettings['location_sets']) && is_array($this->configSettings['location_sets']) && count($this->configSettings['location_sets']) >= 1)
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
            $this->_loadTitlesRegexesToFilter_();

            $this->_loadCompanyRegexesToFilter_();
        }



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

        if(isset($tempFileDetails))
        {
            $this->arrFileDetails['user_input_files'][]= array('details'=> $tempFileDetails, 'data_type' => $iniInputFileItem['type']);
        }
    }

    private function __getInputFilesByValue__($valKey, $val)
    {
        $ret = null;
        if(isset($this->arrFileDetails['user_input_files']) && (is_array($this->arrFileDetails['user_input_files'])  || is_array($this->arrFileDetails['user_input_files'])))
        {
            foreach($this->arrFileDetails['user_input_files'] as $fileItem)
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
                    if(isset($retSearch)) $this->configSettings['searches'][] = $retSearch;
                }
            }
            else
            {
                $retSearch = $this->_parseSearchFromINI_($config['search']);
                if(isset($retSearch)) $this->configSettings['searches'][] = $retSearch;
            }
        }
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded " . count($this->configSettings['searches']) . " searches. ", \Scooper\C__DISPLAY_ITEM_RESULT__);
    }

    private function _parseSearchFromINI_($iniSearch)
    {
        $tempSearch = $this->getEmptySearchDetailsRecord();

        if(isset($iniSearch['key'])) $tempSearch['key'] = \Scooper\strScrub($iniSearch['key'], REMOVE_EXTRA_WHITESPACE | LOWERCASE );
        if(isset($iniSearch['jobsite'])) $tempSearch['site_name'] = \Scooper\strScrub($iniSearch['jobsite'], REMOVE_EXTRA_WHITESPACE | LOWERCASE );
        assert(isset($iniSearch['jobsite']));
        $strJobSiteKey = \Scooper\strScrub($iniSearch['jobsite'], FOR_LOOKUP_VALUE_MATCHING | LOWERCASE );
        if(isset($this->configSettings['included_sites']) && !isset($this->configSettings['included_sites'][$strJobSiteKey]))
        {
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine($iniSearch['jobsite'] . "search " .$iniSearch['name'] . " was not added; " . $strJobSiteKey . " is excluded for this run.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return null;
        }
        $tempSearch['name'] = (isset($iniSearch['jobsite']) ? $iniSearch['jobsite'] . ': ' : "") . $iniSearch['name'];
        if(isset($iniSearch['url_format'])) $tempSearch['base_url_format']  = $iniSearch['url_format'];
        if(isset($iniSearch['keywords'])) $tempSearch['keyword_search_override']  = $iniSearch['keywords'];
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


    private function _readGlobalSearchParamtersFromConfig_($config)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading global search settings from config file...", \Scooper\C__DISPLAY_ITEM_START__);
        if(isset($config['global_search_options']) && is_array($config['global_search_options']) && isset($config['global_search_options']['excluded_jobsites']))
        {
            if(!is_array($config['global_search_options']['excluded_jobsites'])) { $config['global_search_options']['excluded_jobsites'] = array($config['global_search_options']['excluded_jobsites']); }
            foreach($config['global_search_options']['excluded_jobsites'] as $excludedSite)
            {
                $excludedSite = strtolower($excludedSite);
                $this->configSettings['excluded_sites'][$excludedSite] = $excludedSite;
                if(isset($this->configSettings['included_sites'][$excludedSite])) unset($this->configSettings['included_sites'][$excludedSite]);
                $excludedSite = '';
            }

            if(isset($config['global_search_options']['excluded_plugin_types']))
            {
                foreach($config['global_search_options']['excluded_plugin_types'] as $excludedPlugins)
                {
                    $excludedPlugins = strtolower($excludedPlugins);
                    $this->configSettings['excluded_plugin_types'][$excludedPlugins] = $excludedPlugins;
                }

                if(isset($this->configSettings['excluded_plugin_types']) && count($this->configSettings['excluded_plugin_types']) > 0)
                {
                    foreach($this->configSettings['excluded_plugin_types'] as $type)
                    {
                        $flagCheck=null;
                        foreach($GLOBALS['DATA']['site_plugins'] as $plugin)
                        {
                            $class = new $plugin['class_name'];
                            switch ($type)
                            {
                                case "web":
                                    $flagCheck = C__JOB_SEARCH_RESULTS_TYPE_WEBPAGE__;
                                    break;

                                case "xml":
                                    $flagCheck = C__JOB_SEARCH_RESULTS_TYPE_XML__;
                                    break;
                            }

                            if(isset($flagCheck) && $class->isBitFlagSet($flagCheck))
                            {
                                $this->configSettings['excluded_sites'][$plugin['name']] = $plugin['name'];
                                if(isset($this->configSettings['included_sites'][$plugin['name']])) unset($this->configSettings['included_sites'][$plugin['name']]);

                            }

                        }
                    }
                }

            }
        }
    }

    private function _readLocationSetsFromConfig_($config)
    {
        if (!$config) throw new ErrorException("Invalid configuration.  Cannot load user's searches.");



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
                        $strSetName = 'LocationSet' . (count($this->configSettings['location_sets']) + 1);
                        if(isset($iniSettings['name']) && strlen($iniSettings['name']) > 0)
                        {
                            $strSetName = $iniSettings['name'];
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
                                $arrNewLocationSet['excluded_jobsites_array'][$excludedSite] = $excludedSite;
                                $excludedSite = '';
                            }
                        }
                        if(is_array($arrNewLocationSet['excluded_jobsites_array']))
                        {
                            $arrNewLocationSet['excluded_jobsites_array'] = \Scooper\my_merge_add_new_keys($arrNewLocationSet['excluded_jobsites_array'], $this->configSettings['excluded_sites']);
                        }

                        $strSettingStrings = getArrayValuesAsString($arrNewLocationSet);
                        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added location search settings '" . $strSetName . ": " . $strSettingStrings, \Scooper\C__DISPLAY_ITEM_DETAIL__);

                        $this->configSettings['location_sets'][$strSetName] = $arrNewLocationSet;

                    }
                }
            }

            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded " . count($this->configSettings['location_sets']) . " location sets. ", \Scooper\C__DISPLAY_ITEM_RESULT__);
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
        if(isset($config['search_keyword_set']))
        {
            foreach($config['search_keyword_set'] as $ini_keyword_set)
            {

                $strSetKey = 'KeywordSet' . (count($this->configSettings['keyword_sets']) + 1);
                if(isset($ini_keyword_set['name']) && strlen($ini_keyword_set['name']) > 0)
                {
                    $strSetKey = $ini_keyword_set['name'];
                }
                elseif(isset($ini_keyword_set['key']) && strlen($ini_keyword_set['key']) > 0)
                {
                    $strSetKey = $ini_keyword_set['key'];

                }


                $this->configSettings['keyword_sets'][$strSetKey] = $this->_getEmptyKeywordSettingsSet_();
                $this->configSettings['keyword_sets'][$strSetKey]['name'] = $strSetKey;
                if(!isset($ini_keyword_set['key'])) {
                    $this->configSettings['keyword_sets'][$strSetKey]['key'] = strtolower($strSetKey);
                }

                $fScopeAllSites = true;
                if(isset($ini_keyword_set['settings_scope']) && strlen($ini_keyword_set['settings_scope'] ) > 0)
                {
                    $this->configSettings['keyword_sets'][$strSetKey]['settings_scope'] = $ini_keyword_set['settings_scope'];

                    if(strcasecmp($this->configSettings['keyword_sets'][$strSetKey]['settings_scope'], "all-sites") != 0)
                    {
                        $fScopeAllSites = false;
                    }
                }

                if($fScopeAllSites == true)
                {
                    // Copy all the job sites into the list of sites included to be run
                    $this->configSettings['keyword_sets'][$strSetKey]['included_jobsites_array'] = $this->configSettings['included_sites'];
                }

                if(isset($ini_keyword_set['excluded_jobsites']) && count($ini_keyword_set['excluded_jobsites']) > 0)
                {
                    foreach($ini_keyword_set['excluded_jobsites'] as $excludedSite)
                    {
                        $excludedSite = strtolower($excludedSite);
                        $this->configSettings['keyword_sets'][$strSetKey]['excluded_jobsites_array'][$excludedSite] = $excludedSite;
                        unset($this->configSettings['keyword_sets'][$strSetKey]['included_jobsites_array'][$excludedSite]);
                        $excludedSite = '';
                    }
                    $this->configSettings['keyword_sets'][$strSetKey]['excluded_jobsites_array'] = \Scooper\my_merge_add_new_keys($this->configSettings['keyword_sets'][$strSetKey]['excluded_jobsites_array'], $this->configSettings['excluded_sites']);
                }


                // If keywords are in a string, split them out into an array instead before continuing
                if(isset($ini_keyword_set['keywords']) && is_string($ini_keyword_set['keywords']))
                {
                    $ini_keyword_set['keywords'] = explode(",", $ini_keyword_set['keywords']);
                }


                if(isset($ini_keyword_set['keywords']) && is_array($ini_keyword_set['keywords']) && count($ini_keyword_set['keywords']) > 0)
                {
                    foreach($ini_keyword_set['keywords'] as $keywordItem)
                    {
                        $this->configSettings['keyword_sets'][$strSetKey]['keywords_array'][] = \Scooper\strScrub($keywordItem, ADVANCED_TEXT_CLEANUP);
                    }
                }

                if(isset($ini_keyword_set['keyword_match_type']) && strlen($ini_keyword_set['keyword_match_type'] ) > 0)
                {
                    $flagType = $this->_getKeywordMatchFlagFromString_($ini_keyword_set['keyword_match_type'] );
                    if($flagType != null)
                    {
                        $this->configSettings['keyword_sets'][$strSetKey]['keyword_match_type_string'] = $ini_keyword_set['keyword_match_type'] ;
                        $this->configSettings['keyword_sets'][$strSetKey]['keyword_match_type_flag'] = $flagType;
                    }
                }


                if(isset($this->configSettings['keyword_sets'][$strSetKey]['keywords_array']) && count($this->configSettings['keyword_sets'][$strSetKey]['keywords_array']) > 0)
                {
                    if(strcasecmp('Keyword',$strSetKey) <= 0)
                    {
                        $this->configSettings['keyword_sets'][$strSetKey]['name'] = "KeywordSet-" . \Scooper\strScrub($this->configSettings['keyword_sets'][$strSetKey]['keywords_array'][0], FOR_LOOKUP_VALUE_MATCHING);
                    }

                    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added keyword set '" . $this->configSettings['keyword_sets'][$strSetKey]['name'] . "' with keywords = " . getArrayValuesAsString($this->configSettings['keyword_sets'][$strSetKey]['keywords_array']) . (($ini_keyword_set['keyword_match_type'] != null && strlen($ini_keyword_set['keyword_match_type'] ) > 0) ? " matching " . $ini_keyword_set['keyword_match_type'] : ""), \Scooper\C__DISPLAY_ITEM_DETAIL__);
                }


            }

        }
    }



    private function _updateSearchesWithKeywordSet_($keywordSet)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Updating user-specified searches with keyword set details", \Scooper\C__DISPLAY_ITEM_START__);
        for($c = 0; $c < count($this->configSettings['searches']); $c++)
        {
            $this->configSettings['searches'][$c]['keyword_set']  = $keywordSet['keywords_array'];
            //
            // If this search already has any flags set on it, then do not overwrite that value for this search
            // Otherwise, set it to be the value that any keyword set we're adding has
            //
            if($this->configSettings['searches'][$c]['user_setting_flags'] == null || $this->configSettings['searches'][$c]['user_setting_flags'] == 0)
            {
                $this->configSettings['searches'][$c]['user_setting_flags'] = $keywordSet['keyword_match_type_flag'];
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
        if(isset($this->configSettings['keyword_sets']) && count($this->configSettings['keyword_sets']) > 0)
        {
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Adding new searches for user's keyword sets ", \Scooper\C__DISPLAY_ITEM_START__);

            foreach($this->configSettings['keyword_sets'] as $keywordSet)
            {
                $arrSkippedPlugins = null;
                if(isset($keywordSet['included_jobsites_array']))
                {

                    if(count($keywordSet['included_jobsites_array'])>0)
                    {
                        foreach($keywordSet['included_jobsites_array'] as $siteToSearch)
                        {
                            $classPlug = new $GLOBALS['DATA']['site_plugins'][$siteToSearch]['class_name'](null, null);

                            if($classPlug->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED))
                            {
                                $arrSkippedPlugins[] = $siteToSearch;
                                continue;
                            }

                            // If the class supports multiple keywords per search, then we can
                            // use a single search with the full set.
                            $nameKywdSet = $keywordSet['key'];
                            $arrKywdSetsForUniqSearches = array();
                            if($classPlug->isBitFlagSet(C__JOB_KEYWORD_MULTIPLE_TERMS_SUPPORTED) || $classPlug->isBitFlagSet(C__JOB_ALWAYS_ADD_FULL_KEYWORDS_SET))
                            {
                                $arrKywdSetsForUniqSearches[$keywordSet['key']] = array('key' => $keywordSet['key'], 'keywords_array' => $keywordSet['keywords_array']);
                            }
                            else // if not, we need to add search for each keyword using that word as a single value in a keyword set
                            {
                                $arrSetForEachTerm = array_chunk ( $keywordSet['keywords_array'], 1);
                                foreach($arrSetForEachTerm as $set)
                                {
                                    $nameSet = $nameKywdSet."-".\Scooper\strScrub($set[0], FOR_LOOKUP_VALUE_MATCHING);
                                    $arrKywdSetsForUniqSearches[$nameSet] = array('key' => $nameSet, 'keywords_array' => array($set[0]));
                                }

                            }

                            foreach($arrKywdSetsForUniqSearches as $searchKywdSet)
                            {
                                $tempSearch = $this->getEmptySearchDetailsRecord();
                                $tempSearch['key'] = \Scooper\strScrub($siteToSearch, FOR_LOOKUP_VALUE_MATCHING) . '-' . \Scooper\strScrub($searchKywdSet['key'], FOR_LOOKUP_VALUE_MATCHING);
                                $tempSearch['name'] =  $tempSearch['key'] . '-' . \Scooper\strScrub($keywordSet['name'], FOR_LOOKUP_VALUE_MATCHING);
                                $tempSearch['site_name']  = $siteToSearch;
                                $tempSearch['keyword_set']  = $searchKywdSet['keywords_array'];
                                $tempSearch['user_setting_flags'] = $keywordSet['keyword_match_type_flag'];

                                $this->configSettings['searches'][] = $tempSearch;
                                $strSearchAsString = getArrayValuesAsString($tempSearch);
                                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Search added: " . $strSearchAsString, \Scooper\C__DISPLAY_ITEM_DETAIL__);
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
        if(count($this->configSettings['searches']) <= 0)
        {
            return;
        }

        reset($this->configSettings['location_sets']);
        $primaryLocationSet = current($this->configSettings['location_sets']);

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
            for($l = 0; $l < count($this->configSettings['searches']) ; $l++)
            {

                if(isset($this->configSettings['searches'][$l]['location_user_specified_override']) && strlen($this->configSettings['searches'][$l]['location_user_specified_override'])>0)
                {
                    // this search already has a location from the user, so we don't need to update it with the location set
                    continue;
                }

                $curSiteName = \Scooper\strScrub($this->configSettings['searches'][$l]['site_name'], FOR_LOOKUP_VALUE_MATCHING);

                if(isset($primaryLocationSet['excluded_jobsites_array'][$curSiteName]) && strlen($primaryLocationSet['excluded_jobsites_array'][$curSiteName]) > 0)
                {
                    // this site was excluded for this location set, so continue.
                    // TODO[Future] Try to use a secondary location set for the search instead

                    // TODO[future] Update the search in such a way that it doesn't try to process later without a location.  Maybe a "is_valid = T/F" flag?
                    continue;
                }


                $classPlug = new $GLOBALS['DATA']['site_plugins'][$this->configSettings['searches'][$l]['site_name']]['class_name'](null, null);
                $this->configSettings['searches'][$l]['location_set'] = $primaryLocationSet;
                $strSearchLocation = $classPlug->getLocationValueForLocationSetting($this->configSettings['searches'][$l]);
                if($strSearchLocation != VALUE_NOT_SUPPORTED)
                {
                    $this->configSettings['searches'][$l]['key'] = $this->configSettings['searches'][$l]['key'] . "-loc-" . strtolower($primaryLocationSet['name']);
                    $this->configSettings['searches'][$l]['name'] = $this->configSettings['searches'][$l]['name'] . "-loc-" . strtolower($primaryLocationSet['name']);
                    $this->configSettings['searches'][$l]['location_search_value'] = $strSearchLocation;
                }
            }
        }

    }

    private function _addSearchesForAdditionalLocationSets_()
    {
        if(count($this->configSettings['searches']) <= 0)
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
        if(isset($this->configSettings['location_sets']) && is_array($this->configSettings['location_sets']) && count($this->configSettings['location_sets']) > 1)
        {
            $arrPossibleSearches_Start = $this->configSettings['searches'];
            $arrNewSearches = null;

            for($l = 0; $l < count($arrPossibleSearches_Start) ; $l++)
            {

                if(isset($arrPossibleSearches_Start[$l]['location_user_specified_override']) && strlen($arrPossibleSearches_Start[$l]['location_user_specified_override'])>0)
                {
                    // this search already has a location from the user, so we shouldn't clone it with the location set
                    continue;
                }


                $classPlug = new $GLOBALS['DATA']['site_plugins'][$arrPossibleSearches_Start[$l]['site_name']]['class_name'](null, null);
                if ($classPlug->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || $classPlug->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED)) {
                    // this search doesn't support specifying locations so we shouldn't clone it for a second location set
                    continue;
                }


                $fSkippedPrimary = false;
                foreach($this->configSettings['location_sets'] as $locSet)
                {
                    if(!$fSkippedPrimary) // Starts at index 1 because the primary location (index 0) was already set for all searches
                    {
                        $fSkippedPrimary = true;
                        continue;
                    }

                    $curSiteName = \Scooper\strScrub($this->configSettings['searches'][$l]['site_name'], FOR_LOOKUP_VALUE_MATCHING);
                    if(isset($locSet['excluded_jobsites_array'][$curSiteName]) && strlen($locSet['excluded_jobsites_array'][$curSiteName]) > 0)
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

                        $this->configSettings['searches'][] = $newSearch;

                    }
                    $newSearch = null;
                }
            }
        }

    }

    private function _parseEmailSetupFromINI_($config)
    {
        if(isset($config['email'] ))
        {
            if($config['email']['smtp'])
            {
                $this->arrEmail_PHPMailer_SMTPSetup = $config['email']['smtp'];
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
                $this->arrEmailAddresses[] = $tempEmail;
            }
        }


    }

    private function _getEmptyKeywordSettingsSet_()
    {
        return array(
            'key' => null,
            'name' => null,
            'keywords_array' => null,
            'keyword_match_type_string' => null,
            'keyword_match_type_flag' => null,
            'excluded_jobsites_array' => array(),
            'settings_scope' => "all-searches",
        );
    }

    private function _getEmptyLocationSettingsSet_()
    {
        return array(
            'key' => null,
            'name' => null,
            'excluded_jobsites_array' => array(),
        );
    }


    private function createOutputSubFolder($fileDetails)
    {
        // Append the file name base to the directory as a new subdirectory for output
        $fullNewDirectory = $fileDetails['directory'] . $fileDetails['file_name_base'];
        $detailsSubdir = \Scooper\getFilePathDetailsFromString($fullNewDirectory, \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Created folder for results output: " . $detailsSubdir['directory'], \Scooper\C__DISPLAY_ITEM_RESULT__);
        $detailsSubdir['file_name_base'] =  $fileDetails['file_name_base'];
        $detailsSubdir['file_extension'] =  $fileDetails['file_extension'];

        // return the new file & path details
        return $detailsSubdir;
    }





    private function _loadTitlesRegexesToFilter_()
    {
        $arrFileInput = $this->getInputFilesByType("regex_filter_titles");
        $fTitlesLoaded = false;

        $GLOBALS['DATA']['titles_regex_to_filter'] = array();
        $nDebugCounter = 0;

        if(isset($GLOBALS['DATA']['titles_regex_to_filter']) && count($GLOBALS['DATA']['titles_regex_to_filter']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            $GLOBALS['logger']->logLine("Using previously loaded " . count($GLOBALS['DATA']['titles_regex_to_filter']) . " regexed title strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return;
        }

        if(!is_array($arrFileInput))
        {
            // No files were found, so bail
            $GLOBALS['logger']->logLine("No input files were found with regex title strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return;
        }

        foreach($arrFileInput as $fileItem)
        {
            $fileDetail = $fileItem['details'];

            if(isset($fileDetail) && $fileDetail ['full_file_path'] != '')
            {
                if(file_exists($fileDetail ['full_file_path'] ) && is_file($fileDetail ['full_file_path'] ))
                {
                    $GLOBALS['logger']->logLine("Loading job title regexes to filter from ".$fileDetail ['full_file_path']."." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    $classCSVFile = new \Scooper\ScooperSimpleCSV($fileDetail ['full_file_path'] , 'r');
                    $arrTitlesTemp = $classCSVFile->readAllRecords(true, array("match_regex"));
                    if(!isset($arrTitlesTemp['data_rows']) || count($arrTitlesTemp) <= 0)
                    {
                        $GLOBALS['logger']->logLine("Warning: No titles were found in the source file " . $fileDetail['file_name'] . " that will be automatically filtered from job listings." , \Scooper\C__DISPLAY_WARNING__);
                        continue;
                    }

                    $arrTitlesTemp = $arrTitlesTemp['data_rows'];
                    //
                    // Add each title we found in the file to our list in this class, setting the key for
                    // each record to be equal to the job title so we can do a fast lookup later
                    //
                    foreach($arrTitlesTemp as $titleRecord)
                    {
                        $arrRXInput = explode("|", strtolower($titleRecord['match_regex']));
                        foreach($arrRXInput as $rxItem)
                        {
                            $rx = '/'.preg_quote($rxItem).'/i';
                            //                        $GLOBALS['logger']->logLine("Testing regex record " .$nDebugCounter . " with value of " . $rx , \Scooper\C__DISPLAY_ITEM_DETAIL__);
                            try
                            {
                                $testMatch = preg_match($rx, "empty");

                            }
                            catch (Exception $ex)
                            {
                                $strError = "Regex test failed on # " . $nDebugCounter . ", value " . $rxItem .".  Skipping.  Error: '".$ex->getMessage();
                                $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                                if(isDebug()) { throw new ErrorException( $strError); }
                            }
                            $GLOBALS['DATA']['titles_regex_to_filter'][] = $rx;
                        }
                        $nDebugCounter = $nDebugCounter + 1;
                    }
                    $fTitlesLoaded = true;
                }

            }
        }

        if($fTitlesLoaded == false)
        {
            if(count($arrFileInput) ==0)
                $GLOBALS['logger']->logLine("No file specified for title regexes to exclude.'.  Final list will not be filtered." , \Scooper\C__DISPLAY_WARNING__);
            else
                $GLOBALS['logger']->logLine("Could not load regex list for titles to exclude from '" . getArrayValuesAsString($arrFileInput) . "'.  Final list will not be filtered." , \Scooper\C__DISPLAY_WARNING__);
        }
        else
        {
            $GLOBALS['logger']->logLine("Loaded " . countAssociativeArrayValues($GLOBALS['DATA']['titles_regex_to_filter']) . " regexes to use for filtering titles from '" . getArrayValuesAsString($this->getInputFilesByType("regex_filter_titles")) . "'." , \Scooper\C__DISPLAY_WARNING__);

        }

    }



    /**
     * Initializes the global list of titles we will automatically mark
     * as "not interested" in the final results set.
     */
    function _loadCompanyRegexesToFilter_()
    {
        if(isset($GLOBALS['DATA']['companies_regex_to_filter']) && count($GLOBALS['DATA']['companies_regex_to_filter']) > 0)
        {
            // We've already loaded the companies; go ahead and return right away
            $GLOBALS['logger']->logLine("Using previously loaded " . count($GLOBALS['DATA']['companies_regex_to_filter']) . " regexed company strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return;
        }
        $arrFileInput = $this->getInputFilesByType("regex_filter_companies");

        $GLOBALS['DATA']['companies_regex_to_filter'] = array();

        if(isset($GLOBALS['DATA']['companies_regex_to_filter']) && count($GLOBALS['DATA']['companies_regex_to_filter']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            $GLOBALS['logger']->logLine("Using previously loaded " . count($GLOBALS['DATA']['companies_regex_to_filter']) . " regexed title strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
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
                                    $rx = '/'.$rxItem.'/';

                                    $GLOBALS['DATA']['companies_regex_to_filter'][] = $rx;
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
            $GLOBALS['logger']->logLine("Loaded " . count($GLOBALS['DATA']['companies_regex_to_filter']). " regexes to use for filtering companies from " . getArrayValuesAsString($arrFileInput)  , \Scooper\C__DISPLAY_WARNING__);

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