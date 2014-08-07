<?php
/**
 * Copyright 2014 Bryan Selner
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

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/include/SitePlugins.php');


class ClassConfig extends ClassJobsSitePlugin
{
    protected $nNumDaysToSearch = -1;
    protected $arrFileDetails = array('output' => null, 'output_subfolder' => null, 'config_ini' => null, 'user_input_files' => null);
    protected $arrEmailAddresses = null;
    protected $configSettings = array('searches' => null, 'keyword_sets' => null, 'location_sets' => null, 'number_days'=>VALUE_NOT_SUPPORTED);
    protected $arrEmail_PHPMailer_SMTPSetup = null;


    function getSearchConfiguration($strSubkey = null)
    {
        if($strSubkey != null)
            return $this->configSettings[$strSubkey];
        else
            $this->configSettings;
    }

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
    function getEmailByType($strType)
    {

        if ($this->arrEmailAddresses) {
            foreach ($this->arrEmailAddresses as $email) {
                if (strcasecmp($email['type'], $strType) == 0) {
                    $emailRecord = $email;
                    return $emailRecord;
                }

            }
        }
        return null;
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
            $fIsIncludedInRun = is_IncludeSite($site['name']);
            $GLOBALS['DATA']['site_plugins'][$site['name']]['include_in_run'] = $fIsIncludedInRun;
        }

        $GLOBALS['OPTS']['DEBUG'] = \Scooper\get_PharseOptionValue('use_debug');
        $GLOBALS['OPTS']['VERBOSE'] = $GLOBALS['OPTS']['DEBUG'];

        if($GLOBALS['OPTS']['use_config_ini_given'])
        {
//            throw new ErrorException("Config ini files not yet supported!");
            $this->arrFileDetails['config_ini'] = \Scooper\set_FileDetails_fromPharseSetting("use_config_ini", 'config_file_details', true);
            if(!isset($GLOBALS['logger'])) $GLOBALS['logger'] = new \Scooper\ScooperLogger($this->arrFileDetails['config_ini']['directory'] );
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Log file for run being written to: " . $this->arrFileDetails['config_ini']['directory'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading configuration file details from " . $this->arrFileDetails['config_ini']['full_file_path'], \Scooper\C__DISPLAY_ITEM_DETAIL__);
            $iniParser = new IniParser($this->arrFileDetails['config_ini']['full_file_path']);
            $confTemp = $iniParser->parse();
            $this->_setupRunFromConfig_($confTemp);
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
//        if($GLOBALS['OPTS']['DEBUG'] == true)
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

    private function _setupRunFromConfig_($config)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Reading configuration options from ".$this->arrFileDetails['config_ini']['full_file_path']."...", \Scooper\C__DISPLAY_ITEM_START__);
        if($config->output)
        {
            if($config->output->folder)
            {
                 $this->arrFileDetails['output'] = \Scooper\parseFilePath($config->output->folder);
            }

            if($config->output->file)
            {
                 $this->arrFileDetails['output'] = \Scooper\parseFilePath( $this->arrFileDetails['output'] . $config->output->file);
            }
        }


        if($config->input && $config->input->folder)
        {
            $this->arrFileDetails['input_folder']  = \Scooper\parseFilePath($config->input->folder);
        }

        if($config->inputfiles)
        {
            foreach($config->inputfiles as $iniInputFile)
            {
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Processing input file '" . $this->arrFileDetails['input_folder']['directory'].$iniInputFile['filename'] . "' with type of '". $iniInputFile['type'] . "'...", \Scooper\C__DISPLAY_NORMAL__);
                $this->__addInputFile__($iniInputFile);
            }
        }

        $this->_parseEmailSetupFromINI_($config);

        $this->__readSearchesFromConfig__($config);

        $this->__readKeywordSetsFromConfig__($config);

        $this->__readLocationSetsFromConfig__($config);

        $this->_expandKeywordsAndLocationsIntoSearches_();

        $this->_loadTitlesRegexesToFilter_();

        $this->_loadCompanyRegexesToFilter_();




        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Completed loading configuration from INI file:  ".var_export($GLOBALS['OPTS'], true), \Scooper\C__DISPLAY_SUMMARY__);

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
        foreach($this->arrFileDetails['user_input_files'] as $fileItem)
        {
            if(strcasecmp($fileItem[$valKey], $val) == 0)
            {
                $ret[] = $fileItem;
            }
        }

        return $ret;
    }



    private function __readSearchesFromConfig__($config)
    {
        if(!$config) throw new ErrorException("Invalid configuration.  Cannot load user's searches.");
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading searches from config file...", \Scooper\C__DISPLAY_ITEM_START__);

        if($config->search)
        {
            if(is_object($config->search))
            {
                foreach($config->search as $iniSearch)
                {
                    $this->configSettings['searches'][] = $this->_parseSearchFromINI_($iniSearch);
                }
            }
            else
            {
                $this->configSettings['searches'][] = $this->_parseSearchFromINI_($config->search);
            }
        }
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded " . count($this->configSettings['searches']) . " searches. ", \Scooper\C__DISPLAY_ITEM_RESULT__);
    }

    private function _parseSearchFromINI_($iniSearch)
    {
        $tempSearch = $this->getEmptySearchDetailsRecord();

        $tempSearch['search_key'] = \Scooper\strScrub($iniSearch['key'], REMOVE_EXTRA_WHITESPACE | LOWERCASE );
        $tempSearch['site_name'] = \Scooper\strScrub($iniSearch['jobsite'], REMOVE_EXTRA_WHITESPACE | LOWERCASE );
        $tempSearch['search_name'] = ($iniSearch['jobsite'] != null ? $iniSearch['jobsite'] . ': ' : "") . $iniSearch['name'];
        $tempSearch['base_url_format']  = $iniSearch['url_format'];
        $tempSearch['keyword_search_override']  = $iniSearch['keywords'];
        $tempSearch['location_user_specified_override']  = $iniSearch['location'];

        if($iniSearch['keyword_match_type_string'] != null && strlen($iniSearch['keyword_match_type_string'] ) > 0)
        {
            $flagType = $this->_getKeywordMatchFlagFromString_($iniSearch['keyword_match_type_string'] );
            if($flagType != null)
            {
                $tempSearch['user_flag_settings'] = $flagType;
            }
        }

// BUGBUG:  Can't call FinalizeSearch from $this here because we aren't the plugin that will process it.
//        Can only call it from a job site plugin, so we'd need to instantiate the class if we're going to do it.
//        $this->_finalizeSearch_($tempSearch);


        if($tempSearch['search_key'] == "")
        {
            $tempSearch['search_key'] = \Scooper\strScrub($tempSearch['site_name'], FOR_LOOKUP_VALUE_MATCHING) . "-" . \Scooper\strScrub($tempSearch['search_name'], FOR_LOOKUP_VALUE_MATCHING);
        }

        $strSearchAsString = getArrayValuesAsString($tempSearch);
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Search loaded from config INI: " . $strSearchAsString, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        return $tempSearch;
    }

    private function _parseLocationSettingsFromINI_($iniSearchSetting)
    {
        $tempSettings = null;

        foreach($GLOBALS['DATA']['location_types'] as $loctype)
        {
            if($iniSearchSetting[$loctype] != null && $iniSearchSetting[$loctype] != "")
            {
                $tempSettings[$loctype] = \Scooper\strScrub($iniSearchSetting[$loctype], REMOVE_EXTRA_WHITESPACE);
                $tempSettings[$loctype] = $iniSearchSetting[$loctype];
            }
        }


        return $tempSettings;
    }


    private function __readLocationSetsFromConfig__($config)
    {
        if (!$config) throw new ErrorException("Invalid configuration.  Cannot load user's searches.");



        if($config->search_location_setting_set)
        {
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading search locations from config file...", \Scooper\C__DISPLAY_ITEM_START__);
            //
            // Check if this is a single search setting or if it's a set of search settings
            //
            $strSettingsName = null;
            if (is_object($config->search_location_setting_set))
            {
                foreach($config->search_location_setting_set as $iniSettings)
                {
                    if(count($iniSettings) > 1)
                    {
                        $strSettingsName = $iniSettings['name'];
                        $this->configSettings['location_sets'][$strSettingsName] = $this->_parseLocationSettingsFromINI_($iniSettings);
                        $this->configSettings['location_sets'][$strSettingsName]['name'] = $strSettingsName;
                    }
                }
            }
            else
            {
                $strSettingsName = $config->search_location_setting_set['name'];
                $this->configSettings['location_sets'][$strSettingsName] = $this->_parseLocationSettingsFromINI_($config->search_location_setting_set);
                $this->configSettings['location_sets'][$strSettingsName]['name'] = $strSettingsName;
            }
            if($strSettingsName != null)
            {
                $strSettingStrings = getArrayValuesAsString($this->configSettings['location_sets'][$strSettingsName]);
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added location search settings: " . $strSettingStrings, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            }
        }


        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loaded " . count($this->configSettings['location_sets']) . " search setting groups. ", \Scooper\C__DISPLAY_ITEM_RESULT__);

    }


    private function __readKeywordSetsFromConfig__($config)
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Loading keyword set from config file...", \Scooper\C__DISPLAY_ITEM_START__);
        if($config->search_keyword_set && is_object($config->search_keyword_set))
        {
            foreach($config->search_keyword_set as $ini_keyword_set)
            {

                $strSetName = 'KeywordSet' . (count($this->configSettings['keyword_sets']) + 1);
                if($ini_keyword_set['name'] != null && strlen($ini_keyword_set['name']) > 0)
                {
                    $strSetName = $ini_keyword_set['name'];
                }
                elseif($ini_keyword_set['set_key'] != null && strlen($ini_keyword_set['set_key']) > 0)
                {
                    $strSetName = $ini_keyword_set['set_key'];

                }


                $this->configSettings['keyword_sets'][$strSetName] = $this->_getEmptyKeywordSettingsSet_();
                $this->configSettings['keyword_sets'][$strSetName]['set_name'] = $strSetName;
                if(!isset($ini_keyword_set['set_key'])) {
                    $this->configSettings['keyword_sets'][$strSetName]['set_key'] = strtolower($strSetName);
                }

                $fScopeAllSites = true;
                if($ini_keyword_set['settings_scope'] != null && strlen($ini_keyword_set['settings_scope'] ) > 0)
                {
                    $this->configSettings['keyword_sets'][$strSetName]['settings_scope'] = $ini_keyword_set['settings_scope'];

                    if(strcasecmp($this->configSettings['keyword_sets'][$strSetName]['settings_scope'], "all-sites") != 0)
                    {
                        $fScopeAllSites = false;
                    }
                }

                if($fScopeAllSites == true)
                {
                    // Copy all the job sites into the list of sites included to be run
                    $this->configSettings['keyword_sets'][$strSetName]['included_jobsites_array'] = array_column($GLOBALS['DATA']['site_plugins'], 'name', 'name');
                }

                if($ini_keyword_set['excluded_jobsites'] != null && count($ini_keyword_set['excluded_jobsites']) > 0)
                {
                    foreach($ini_keyword_set['excluded_jobsites'] as $excludedSite)
                    {
                        $excludedSite = strtolower($excludedSite);
                        $this->configSettings['keyword_sets'][$strSetName]['excluded_jobsites_array'][] = $excludedSite;
                        unset($this->configSettings['keyword_sets'][$strSetName]['included_jobsites_array'][$excludedSite]);
                        $excludedSite = '';
                    }
                }


                if($ini_keyword_set['keywords'] != null && count($ini_keyword_set['keywords']) > 0)
                {
                    foreach($ini_keyword_set['keywords'] as $keywordItem)
                    {
                        $this->configSettings['keyword_sets'][$strSetName]['keywords_array'][] = $keywordItem;
                    }
                }

                if($ini_keyword_set['keyword_match_type'] != null && strlen($ini_keyword_set['keyword_match_type'] ) > 0)
                {
                    $flagType = $this->_getKeywordMatchFlagFromString_($ini_keyword_set['keyword_match_type'] );
                    if($flagType != null)
                    {
                        $this->configSettings['keyword_sets'][$strSetName]['keyword_match_type_string'] = $ini_keyword_set['keyword_match_type'] ;
                        $this->configSettings['keyword_sets'][$strSetName]['keyword_match_type_flag'] = $flagType;
                    }
                }


                if($this->configSettings['keyword_sets'][$strSetName]['keywords_array'] != null && count($this->configSettings['keyword_sets'][$strSetName]['keywords_array']) > 0)
                {
                    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Added keyword set '" . $strSetName . "' with keywords = " . getArrayValuesAsString($this->configSettings['keyword_sets'][$strSetName]['keywords_array']) . (($ini_keyword_set['keyword_match_type'] != null && strlen($ini_keyword_set['keyword_match_type'] ) > 0) ? " matching " . $ini_keyword_set['keyword_match_type'] : ""), \Scooper\C__DISPLAY_ITEM_DETAIL__);
                }


            }

        }
    }
    private function _expandKeywordsAndLocationsIntoSearches_()
    {
        $arrSkippedPlugins = null;

        //
        // explode any keyword sets we loaded into separate searches
        //
        // If the keyword settings scope is all sites, then create a search for every possible site
        // so that it runs with the keywords settings if it was included_<site> = true
        //
        if(isset($this->configSettings['keyword_sets']))
        {
            foreach($this->configSettings['keyword_sets'] as $keywordSet)
            {
                foreach($keywordSet['included_jobsites_array'] as $siteToSearch)
                {
                    $classPlug = new $GLOBALS['DATA']['site_plugins'][$siteToSearch]['class_name'](null, null);

                    if(!$classPlug->isBitFlagSet(C__JOB_BASE_URL_FORMAT_REQUIRED))
                    {
                        $tempSearch = $this->getEmptySearchDetailsRecord();
                        $tempSearch['search_key'] = \Scooper\strScrub($siteToSearch, FOR_LOOKUP_VALUE_MATCHING) . '-for-keyword-set-' . \Scooper\strScrub($keywordSet['set_key'], FOR_LOOKUP_VALUE_MATCHING);
                        $tempSearch['search_name']  = $tempSearch['search_key'];
                        $tempSearch['site_name']  = $siteToSearch;
                        $tempSearch['keyword_set']  = $keywordSet['keywords_array'];
                        $tempSearch['user_setting_flags'] = $keywordSet['keyword_match_type_flag'];
                        $tempSearch['keywords_string_for_url'] = VALUE_NOT_SUPPORTED;
                        // if this plugin supports keyword parameters, then add a search for it.
                        if(!$classPlug->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
                        {
                            $tempSearch['keywords_string_for_url'] = $classPlug->getCombinedKeywordStringForURL($tempSearch['keyword_set']);
                        }
                        $classPlug->_finalizeSearch_($tempSearch);

                        $this->configSettings['searches'][] = $tempSearch;
                        $strSearchAsString = getArrayValuesAsString($tempSearch);
                        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Search loaded for keyword settings: " . $strSearchAsString, \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    }
                    else
                    {
                        $arrSkippedPlugins[] = $siteToSearch;
                    }
                }
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Skipped " . count($arrSkippedPlugins) ." plugins because they do not support keyword search: " . getArrayValuesAsString($arrSkippedPlugins, ", ", null, false). "." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
            }
        }



        if(isset($this->configSettings['location_sets']))
        {
            $arrPossibleSearches_Start = $this->configSettings['searches'];
            $arrPossibleSearches_End = null;

            for($l = 0; $l < count($arrPossibleSearches_Start) ; $l++)
            {

                if(isset($arrPossibleSearches_Start[$l]['location_user_specified_override']) && strlen($arrPossibleSearches_Start[$l]['location_user_specified_override'])>0)
                {
                    // this search already has a location from the user, so we shouldn't clone it with the location set
                    $arrPossibleSearches_End[] = $arrPossibleSearches_Start[$l];
                    continue;
                }

                $classPlug = new $GLOBALS['DATA']['site_plugins'][$arrPossibleSearches_Start[$l]['site_name']]['class_name'](null, null);
                if ($classPlug->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || $classPlug->isBitFlagSet(C__JOB_BASE_URL_FORMAT_REQUIRED)) {
                    $arrPossibleSearches_End[] = $arrPossibleSearches_Start[$l];
                    continue;
                }

                foreach($this->configSettings['location_sets'] as $locSet)
                {
                    $strSearchLocation = $classPlug->getLocationValueForLocationSetting($arrPossibleSearches_Start[$l], $locSet);
                    if($strSearchLocation != VALUE_NOT_SUPPORTED)
                    {
                        $newSearch = $arrPossibleSearches_Start[$l];
                        $newSearch['search_key'] = $arrPossibleSearches_Start[$l]['search_key'] . "-for-location-set-" . strtolower($locSet['name']);
                        $newSearch['search_name'] = $arrPossibleSearches_Start[$l]['search_name'] . "-for-location-set-" . strtolower($locSet['name']);
                        $newSearch['location_set'] = $locSet;
                        $newSearch['location_search_value'] = $strSearchLocation;
                        $this->_finalizeSearch_($tempSearch);

                        $arrPossibleSearches_End[] = $newSearch;
                    }
                }
            }
            $arrPossibleSearchesForRun = $arrPossibleSearches_End;
        }
        //
        // Add the search locations to the list of ones to run
        //
        // If the search had location keywords, it overrides the locSettingSets
        //
//        if ($searchDetails['location_user_specified_override'] != null && strlen($searchDetails['location_user_specified_override']) > 0) {
//            $locSingleSettingSet = array('name' => 'location_user_specified_override', 'search_location_value_override' => $searchDetails['location_user_specified_override']);
//            $this->configSettings['location_sets']['location_user_specified_override'] = $locSingleSettingSet;
//        } else {
//            $locTypeSupported = $this->getLocationSettingType();
//            if ($locTypeSupported != null && strlen($locTypeSupported) > 0 && $locSettingSets != null) {
//                foreach ($locSettingSets as $set) {
//                    if ($set[$locTypeSupported] != null) {
//                        $this->configSettings['location_sets'][$set['name']]['name'] = $set['name'];
//                        $this->configSettings['location_sets'][$set['name']][$locTypeSupported] = $set[$locTypeSupported];
//                    }
//                }
//            }
//        }
//   }

    }
    private function _parseEmailSetupFromINI_($config)
    {
        if($config->email )
        {
            if($config->email->smtp)
            {
                $this->arrEmail_PHPMailer_SMTPSetup = $config->email->smtp;
            }
        }

        if($config->emails )
        {
            var_dump($config->emails);
            foreach($config->emails as $emailItem)
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
            'set_key' => null,
            'set_name' => null,
            'keywords_array' => null,
            'keyword_match_type_string' => null,
            'keyword_match_type_flag' => null,
            'excluded_sites_array' => null,
            'settings_scope' => "all-searches",
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

        if($GLOBALS['DATA']['titles_regex_to_filter'] != null && count($GLOBALS['DATA']['titles_regex_to_filter']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            $GLOBALS['logger']->logLine("Using previously loaded " . count($GLOBALS['DATA']['titles_regex_to_filter']) . " regexed title strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return;
        }

        foreach($arrFileInput as $fileItem)
        {
            $fileDetail = $fileItem['details'];

            if($fileDetail != null && $fileDetail ['full_file_path'] != '')
            {
                if(file_exists($fileDetail ['full_file_path'] ) && is_file($fileDetail ['full_file_path'] ))
                {
                    $GLOBALS['logger']->logLine("Loading job title regexes to filter from ".$fileDetail ['full_file_path']."." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    $classCSVFile = new \Scooper\ScooperSimpleCSV($fileDetail ['full_file_path'] , 'r');
                    $arrTitlesTemp = $classCSVFile->readAllRecords(true);
                    $arrTitlesTemp = $arrTitlesTemp['data_rows'];
                    $GLOBALS['logger']->logLine(count($arrTitlesTemp) . " titles found in the source file " . $fileDetail['file_name'] . " that will be automatically filtered from job listings." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    if(count($arrTitlesTemp) <= 0)  continue;
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
                                if($GLOBALS['OPTS']['DEBUG'] == true) { throw new ErrorException( $strError); }
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
        if($GLOBALS['DATA']['companies_regex_to_filter'] != null && count($GLOBALS['DATA']['companies_regex_to_filter']) > 0)
        {
            // We've already loaded the companies; go ahead and return right away
            $GLOBALS['logger']->logLine("Using previously loaded " . count($GLOBALS['DATA']['companies_regex_to_filter']) . " regexed company strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return;
        }
        $arrFileInput = $this->getInputFilesByType("regex_filter_companies");

        $GLOBALS['DATA']['companies_regex_to_filter'] = array();

        if($GLOBALS['DATA']['companies_regex_to_filter'] != null && count($GLOBALS['DATA']['companies_regex_to_filter']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            $GLOBALS['logger']->logLine("Using previously loaded " . count($GLOBALS['DATA']['companies_regex_to_filter']) . " regexed title strings to exclude." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return;
        }
        $fCompaniesLoaded = false;

        foreach($arrFileInput as $fileItem)
        {
            $fileDetail = $fileItem['details'];


            if($fileDetail != null && $fileDetail ['full_file_path'] != '')
            {
                if(file_exists($fileDetail ['full_file_path'] ) && is_file($fileDetail ['full_file_path'] ))
                {
                    $GLOBALS['logger']->logLine("Loading job Company regexes to filter from ".$fileDetail ['full_file_path']."." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    $classCSVFile = new \Scooper\ScooperSimpleCSV($fileDetail ['full_file_path'] , 'r');
                    $arrCompaniesTemp = $classCSVFile->readAllRecords(true);
                    $arrCompaniesTemp = $arrCompaniesTemp['data_rows'];
                    $GLOBALS['logger']->logLine(count($arrCompaniesTemp) . " companies found in the source file that will be automatically filtered from job listings." , \Scooper\C__DISPLAY_ITEM_DETAIL__);

                    //
                    // Add each Company we found in the file to our list in this class, setting the key for
                    // each record to be equal to the job Company so we can do a fast lookup later
                    //
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