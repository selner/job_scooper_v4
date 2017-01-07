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
require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/include/SeleniumSession.php');
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');
header('Content-Type: text/html');

const VALUE_NOT_SUPPORTED = -1;
const BASE_URL_TAG_LOCATION = "***LOCATION***";
const BASE_URL_TAG_KEYWORDS = "***KEYWORDS***";

abstract class ClassBaseJobsSitePlugin extends ClassJobsSiteCommon
{

    function __construct($strOutputDirectory = null, $attributes = null)
    {
        parent::__construct($strOutputDirectory);

        if(array_key_exists("JOBSITE_PLUGINS", $GLOBALS) && (array_key_exists(strtolower($this->siteName), $GLOBALS['JOBSITE_PLUGINS'])))
        {
        $plugin = $GLOBALS['JOBSITE_PLUGINS'][strtolower($this->siteName)];
            if(array_key_exists("other_settings", $plugin))
            {
                $keys = array_keys($plugin['other_settings']);
                foreach($keys as $attrib_name)
                {
                    $this->$attrib_name = $plugin['other_settings'][$attrib_name];
                }
            }
        }

        if(is_array($this->additionalFlags))
        {
            foreach($this->additionalFlags as $flag)
            {
                // If the flag is already set, don't try to set it again or it will
                // actually unset that flag incorrectly
                if(!$this->isBitFlagSet($flag))
                {
                    $this->_flags_ = $this->_flags_ | $flag;
                }
            }
        }
    }


    //************************************************************************
    //
    //
    //
    //  Adding search parameters & downloading new job functions
    //
    //
    //
    //************************************************************************

    public function addSearches($arrSearches)
    {

        if(!is_array($arrSearches[0])) { $arrSearches[] = $arrSearches; }

        foreach($arrSearches as $searchDetails)
        {
            $this->_addSearch_($searchDetails);

        }
    }

    public function getMyJobsList()
    {
        $retAllSearchResults = array();
        if(isset($this->arrSearchesToReturn) && is_array($this->arrSearchesToReturn))
        {
            foreach($this->arrSearchesToReturn as $searchDetails)
            {

                $tmpSearchJobs = $this->_getResultsForSearch_($searchDetails);
//                $this->markJobsList_withAutoItems($tmpSearchJobs);
                addJobsToJobsList($retAllSearchResults, $tmpSearchJobs);
            }
        }
        return $retAllSearchResults;
    }

    public function getUpdatedJobsForAllSearches()
    {
        $strIncludeKey = 'include_'.strtolower($this->siteName);

        if(isset($GLOBALS['OPTS'][$strIncludeKey]) && $GLOBALS['OPTS'][$strIncludeKey] == 0)
        {
            $GLOBALS['logger']->logLine($this->siteName . ": excluded for run. Skipping '" . count($this->arrSearchesToReturn). "' site search(es).", \Scooper\C__DISPLAY_ITEM_START__);
            return array();
        }

        if(count($this->arrSearchesToReturn) == 0)
        {
            $GLOBALS['logger']->logLine($this->siteName . ": no searches set. Skipping...", \Scooper\C__DISPLAY_ITEM_START__);
            return array();
        }

        $this->_collapseSearchesIfPossible_();


        foreach($this->arrSearchesToReturn as $search)
        {
            // assert this search is actually for the job site supported by this plugin
            assert(strcasecmp(strtolower($search['site_name']), strtolower($this->siteName)) == 0);

            $this->_getJobsForSearchByType_($search);
        }


        return $this->getMyJobsList();
    }

    function getName() {
        $name = strtolower($this->siteName);
        if(is_null($name) || strlen($name) == 0)
        {
            $name = str_replace("plugin", "", get_class($this));
        }
        return $name;
    }

    //************************************************************************
    //
    //
    //
    //  Protected and Private Class Members
    //
    //
    //
    //************************************************************************

    protected $nJobListingsPerPage = 20;
    protected $additionalFlags = [];
    protected $secsPageTimeout = null;
    protected $pluginResultsType;

    protected $strKeywordDelimiter = null;
    protected $additionalLoadDelaySeconds = 0;
    protected $_flags_ = null;

//    function takeNextPageAction($driver, $nextPageNum) {   throw new \BadMethodCallException(sprintf("Not implemented method called on class \"%s \".", __CLASS__)); return null;}

    protected function getCombinedKeywordString($arrKeywordSet)
    {
        $arrKeywords = array();

        if(!is_array($arrKeywordSet))
        {
            $arrKeywords[] =$arrKeywordSet[0];
        }
        else
        {
            $arrKeywords = $arrKeywordSet;
        }

        $strRetCombinedKeywords = VALUE_NOT_SUPPORTED;
        if($this->isBitFlagSet(C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS))
        {
            $arrKeywords = array_mapk(function ($k, $v) { return "\"{$v}\""; }, $arrKeywords);
        }


        if(($this->isBitFlagSet(C__JOB_KEYWORD_MULTIPLE_TERMS_SUPPORTED)) && count($arrKeywords) > 1)
        {
            if($this->strKeywordDelimiter == null)
            {
                throw new ErrorException($this->siteName . " supports multiple keyword terms, but has not set the \$strKeywordDelimiter value in " .get_class($this). ". Aborting search because cannot create the URL.");
            }

            $strRetCombinedKeywords = implode((" " . $this->strKeywordDelimiter . " "), $arrKeywords);

        }
        else
        {
            $strRetCombinedKeywords = array_shift($arrKeywords);
        }

        return $strRetCombinedKeywords;
    }

    protected function parseJobsListForPage($objSimpHTML) {   throw new \BadMethodCallException(sprintf("Not implemented method called on class \"%s \".", __CLASS__)); }

    protected function getLocationURLValue($searchDetails, $locSettingSets = null)
    {
        $strReturnLocation = VALUE_NOT_SUPPORTED;

        if($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED))
        {
            throw new ErrorException($this->siteName . " does not support the ***LOCATION*** replacement value in a base URL.  Please review and change your base URL format to remove the location value.  Aborting all searches for ". $this->siteName, \Scooper\C__DISPLAY_ERROR__);
        }

        // Did the user specify an override at the search level in the INI?
        if($searchDetails != null && isset($searchDetails['location_user_specified_override']) && strlen($searchDetails['location_user_specified_override']) > 0)
        {
            $strReturnLocation = $searchDetails['location_user_specified_override'];
        }
        else
        {
            // No override, so let's see if the search settings have defined one for us
            $locTypeNeeded = $this->getLocationSettingType();
            if($locTypeNeeded == null || $locTypeNeeded == "")
            {
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Plugin for '" . $searchDetails['site_name'] ."' did not have the required location type of " . $locTypeNeeded ." set.   Skipping search '". $searchDetails['name'] . "' with settings '" . $locSettingSets['name'] ."'.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                return $strReturnLocation;
            }

            if(isset($locSettingSets) && count($locSettingSets) > 0 && isset($locSettingSets[$locTypeNeeded]))
            {
                $strReturnLocation = $locSettingSets[$locTypeNeeded];
            }

            if($strReturnLocation == null || $strReturnLocation == VALUE_NOT_SUPPORTED)
            {
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Plugin for '" . $searchDetails['site_name'] ."' did not have the required location type of " . $locTypeNeeded ." set.   Skipping search '". $searchDetails['name'] . "' with settings '" . $locSettingSets['name'] ."'.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                return $strReturnLocation;
            }
        }

        if(!isValueURLEncoded($strReturnLocation))
        {
            $strReturnLocation = urlencode($strReturnLocation);
        }

        return $strReturnLocation;
    }


    protected function getPageURLfromBaseFmt($searchDetails, $nPage = null, $nItem = null)
    {
        $strURL = $this->_getBaseURLFormat_($searchDetails);


        $strURL = str_ireplace("***NUMBER_DAYS***", $this->getDaysURLValue($GLOBALS['USERDATA']['configuration_settings']['number_days']), $strURL );
        $strURL = str_ireplace("***PAGE_NUMBER***", $this->getPageURLValue($nPage), $strURL );
        $strURL = str_ireplace("***ITEM_NUMBER***", $this->getItemURLValue($nItem), $strURL );
        $strURL = str_ireplace(BASE_URL_TAG_KEYWORDS, $this->getKeywordURLValue($searchDetails), $strURL );


        $nSubtermMatches = substr_count($strURL, BASE_URL_TAG_LOCATION);

        if(!$this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) && $nSubtermMatches > 0)
        {
            $strLocationValue = $searchDetails['location_search_value'];
            if($strLocationValue == VALUE_NOT_SUPPORTED)
            {
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Failed to run search:  search is missing the required location type of " . $this->getLocationSettingType() ." set.  Skipping search '". $searchDetails['name'] . ".", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                $strURL = VALUE_NOT_SUPPORTED;
            }
            else
            {
                $strURL = str_ireplace(BASE_URL_TAG_LOCATION, $strLocationValue, $strURL);
            }
        }

        if($strURL == null) { throw new ErrorException("Location value is required for " . $this->siteName . ", but was not set for the search '" . $searchDetails['name'] ."'.". " Aborting all searches for ". $this->siteName, \Scooper\C__DISPLAY_ERROR__); }

        return $strURL;
    }

    //************************************************************************
    //
    //
    //
    //  Utility Functions
    //
    //
    //
    //************************************************************************

    protected function _getBaseURLFormat_($searchDetails = null)
    {
        $strBaseURL = VALUE_NOT_SUPPORTED;

        if($searchDetails != null && isset($searchDetails['base_url_format']))
        {
            $strBaseURL = $searchDetails['base_url_format'];
        }
        elseif(isset($this->strBaseURLFormat))
        {
            $strBaseURL = $searchDetails['base_url_format'] = $this->strBaseURLFormat;
        }
        else
        {
            throw new ErrorException("Could not find base URL format for " . $this->siteName . ".  Aborting all searches for ". $this->siteName, \Scooper\C__DISPLAY_ERROR__);
        }
        return $strBaseURL;
    }

    protected function getDaysURLValue($nDays = null) { return ($nDays == null || $nDays == "") ? 1 : $nDays; }

    protected function getPageURLValue($nPage) { return ($nPage == null || $nPage == "") ? "" : $nPage; }

    protected function getItemURLValue($nItem) { return ($nItem == null || $nItem == "") ? 0 : $nItem; }

    protected function parseTotalResultsCount($objSimpHTML) {   throw new \BadMethodCallException(sprintf("Not implemented method called on class \"%s \".", __CLASS__)); }

    protected function getNextInfiniteScrollSet($driver)
    {
        // Neat trick written up by http://softwaretestutorials.blogspot.in/2016/09/how-to-perform-page-scrolling-with.html.
        $driver->executeScript("window.scrollBy(500,5000);");

        sleep(5);

    }


    protected function getKeywordURLValue($searchDetails) {
        if(!$this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
        {
            assert($searchDetails['keywords_string_for_url'] != VALUE_NOT_SUPPORTED);
            return $searchDetails['keywords_string_for_url'];
        }
        return "";
    }

    function __destruct()
    {

        //
        // Write out the interim data to file if we're debugging
        //
        if(isDebug() === true && isset($this->detailsMyFileOut) && is_array($this->detailsMyFileOut))
        {
            $arrAllSearchResults = $this->getMyJobsList();
            $strOutPathWithName = $this->getOutputFileFullPath($this->siteName . "-");
            if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Writing ". $this->siteName." " .count($arrAllSearchResults) ." job records to " . $strOutPathWithName . " for debugging (if needed).", \Scooper\C__DISPLAY_ITEM_START__); }
            $this->writeJobsListToFile($strOutPathWithName, $arrAllSearchResults, true, $this->siteName, "CSV");
        }
    }


    //************************************************************************
    //
    //
    //
    //  Functions for Adding Searches to Plugin Instance
    //
    //
    //
    //************************************************************************


    private function _addSearch_($searchDetails)
    {

        if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_ANYWHERE) && $this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
        {
            $strErr = "Skipping " . $searchDetails['key'] . " search on " . $this->siteName . ".  The plugin only can return all results and therefore cannot be matched with the requested keyword string [Search requested keyword match=anywhere].  ";
            $GLOBALS['logger']->logLine($strErr, \Scooper\C__DISPLAY_ITEM_DETAIL__);
        }
        else
        {

            $this->_finalizeSearch_($searchDetails);

            //
            // Add the search to the list of ones to run
            //
            $this->arrSearchesToReturn[] = $searchDetails;
            $GLOBALS['logger']->logLine($this->siteName . ": added search (" . $searchDetails['key'] . ")", \Scooper\C__DISPLAY_ITEM_DETAIL__);
        }

    }

    private function _finalizeSearch_(&$searchDetails)
    {

        if ($searchDetails['key'] == "") {
            $searchDetails['key'] = \Scooper\strScrub($searchDetails['site_name'], FOR_LOOKUP_VALUE_MATCHING) . "-" . \Scooper\strScrub($searchDetails['name'], FOR_LOOKUP_VALUE_MATCHING);
        }

        assert($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || ($searchDetails['location_search_value'] !== VALUE_NOT_SUPPORTED && strlen($searchDetails['location_search_value']) > 0));

        $this->_setKeywordStringsForSearch_($searchDetails);
        $this->_setStartingUrlForSearch_($searchDetails);

        // add a global record for the search so we can report errors
        $GLOBALS['USERDATA']['search_results'][$searchDetails['key']] = \Scooper\array_copy($searchDetails);

    }

    private function _setKeywordStringsForSearch_(&$searchDetails)
    {
        // Does this search have a set of keywords specific to it that override
        // all the general settings?
        if(isset($searchDetails['keyword_search_override']) && strlen($searchDetails['keyword_search_override']) > 0)
        {
            // keyword_search_override should only ever be a string value for any given search
            assert(!is_array($searchDetails['keyword_search_override']));

            // null out any generalized keyword set values we previously had
            $searchDetails['keywords_array'] = null;
            $searchDetails['keywords_string_for_url'] = null;

            //
            // Now take the override value and setup the keywords_array
            // and URL value for that particular string
            //
            $searchDetails['keywords_array'] = array($searchDetails['keyword_search_override']);
        }

        if(isset($searchDetails['keywords_array']))
        {
            assert(is_array($searchDetails['keywords_array']));

            $searchDetails['keywords_string_for_url'] = $this->_getCombinedKeywordStringForURL_($searchDetails['keywords_array']);
        }

        // Lastly, check if we support keywords in the URL at all for this
        // plugin.  If not, remove any keywords_string_for_url value we'd set
        // and set it to "not supported"
        if($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
        {
            $searchDetails['keywords_string_for_url'] = VALUE_NOT_SUPPORTED;
        }
    }

    private function _getCombinedKeywordStringForURL_($arrKeywordSet)
    {
        $arrKeywords = array();

        if(!is_array($arrKeywordSet))
        {
            $arrKeywords[] =$arrKeywordSet[0];
        }
        else
        {
            $arrKeywords = $arrKeywordSet;
        }

        $strRetCombinedKeywords = $this->getCombinedKeywordString($arrKeywords);

        if(!isValueURLEncoded($strRetCombinedKeywords))
        {
            if($this->isBitFlagSet(C__JOB_KEYWORD_PARAMETER_SPACES_RAW_ENCODE))
                $strRetCombinedKeywords = rawurlencode($strRetCombinedKeywords);
            else
                $strRetCombinedKeywords = urlencode($strRetCombinedKeywords);

        }

        if($this->isBitFlagSet(C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES))
        {
            $strRetCombinedKeywords = str_replace("%22", "-", $strRetCombinedKeywords);
            $strRetCombinedKeywords = str_replace("+", "-", $strRetCombinedKeywords);
        }

        if($this->isBitFlagSet(C__JOB_KEYWORD_SUPPORTS_PLUS_PREFIX))
        {
            $strRetCombinedKeywords = "%2B" . $strRetCombinedKeywords;
        }

        return $strRetCombinedKeywords;
    }

    private function _setStartingUrlForSearch_(&$searchDetails)
    {

        $searchStartURL = $this->getPageURLfromBaseFmt($searchDetails, 1, 1);
        $searchDetails['search_start_url'] = $searchStartURL;
        $GLOBALS['logger']->logLine("Setting start URL for " . $this->siteName . "[". $searchDetails['name'] . " to: " . PHP_EOL. $searchDetails['search_start_url'] , \Scooper\C__DISPLAY_ITEM_DETAIL__);

    }

    private function _collapseSearchesIfPossible_()
    {
        if(count($this->arrSearchesToReturn) == 1)
        {
            // $GLOBALS['logger']->logLine($this->siteName . " does not have more than one search to collapse.  Continuing with single '" . $this->arrSearchesToReturn[0]['name'] . "' search.", \Scooper\C__DISPLAY_WARNING__);
            return;
        }

        $arrCollapsedSearches = array();

        assert($this->arrSearchesToReturn != null);

        // If the plugin does not support multiple terms or if we don't have a valid delimiter to collapse
        // the terms with, we can't collapse, so just leave the searches as they were and return
        if(!$this->isBitFlagSet(C__JOB_KEYWORD_MULTIPLE_TERMS_SUPPORTED) || $this->strKeywordDelimiter == null || strlen($this->strKeywordDelimiter) <= 0)
        {
            $GLOBALS['logger']->logLine($this->siteName . " does not support collapsing terms into a single search.  Continuing with " . count($this->arrSearchesToReturn) . " search(es).", \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return;
        }

        if(count($this->arrSearchesToReturn) == 1)
        {
            $GLOBALS['logger']->logLine($this->siteName . " does not have more than one search to collapse.  Continuing with single '" . $this->arrSearchesToReturn[0]['name'] . "' search.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return;
        }


        $searchCollapsedDetails = null;

        $arrSearchesLeftToCollapse = $this->arrSearchesToReturn;

        while(count($arrSearchesLeftToCollapse) > 1)
        {
            $curSearch = array_pop($arrSearchesLeftToCollapse);

            // if this search has any of the search-level overrides on it
            // then we don't bother trying to collapse it
            //
            if(strlen($curSearch['base_url_format']) > 0 || strlen($curSearch['keyword_search_override']) > 0 || strlen($curSearch['location_search_value']) > 0)
            {
                $arrCollapsedSearches[] = $curSearch;
            }
            elseif($curSearch['location_search_value'] != $arrSearchesLeftToCollapse[0]['location_search_value'])
            {
                $arrCollapsedSearches[] = $curSearch;
            }
        }

        $arrCollapsedSearches[] = array_pop($arrSearchesLeftToCollapse);

        foreach($this->arrSearchesToReturn as $search)
        {
            //
            // if this search has an override value for keyword or location, don't bother to collapse it
            //
            if(strlen($search['keyword_search_override']) > 0 || strlen($search['location_search_value']) > 0)
            {
                $arrCollapsedSearches[] = $search;
            }
            else
            {
                // Otherwise, if we haven't gotten details together yet for any collapsed searches,
                // let's start a unified one now
                if($searchCollapsedDetails == null)
                {
                    $searchCollapsedDetails = $this->cloneSearchDetailsRecordExceptFor($search, array());
                    $searchCollapsedDetails['key'] = $this->siteName . "-collapsed-search";
                    $searchCollapsedDetails['name'] = "collapsed-" . $search['name'];
                    $this->_setKeywordStringsForSearch_($searchCollapsedDetails);
                    $this->_finalizeSearch_($searchCollapsedDetails);
                }
                else
                {
                    // Verify the user settings for keyword match type are the same.  If they are,
                    // we can combine this search into the collapsed one.
                    //
                    if(\Scooper\isBitFlagSet($searchCollapsedDetails['user_setting_flags'], $search['user_setting_flags']) === true)
                    {
                        $searchCollapsedDetails['name'] .= " and " . $search['name'];
                        $searchCollapsedDetails['keywords_array'] = \Scooper\my_merge_add_new_keys(array_values($searchCollapsedDetails['keywords_array']), array_values($search['keywords_array']));

                        $this->_setKeywordStringsForSearch_($searchCollapsedDetails);
                    }
                    else // not the same, so can't combine them.  Just add this search as separate then
                    {
                        $arrCollapsedSearches[] = $search;
                    }
                }
            }

        }
        if($searchCollapsedDetails != null)
        {
            $arrCollapsedSearches[] = $searchCollapsedDetails;
        }

        //
        // set the internal list of searches to be the newly collapsed set
        //
        $this->arrSearchesToReturn = $arrCollapsedSearches;
        $GLOBALS['logger']->logLine($this->siteName . " has collapsed into " . count($arrCollapsedSearches). " search(es).", \Scooper\C__DISPLAY_ITEM_DETAIL__);


        //
        //BUGBUG Hack Fix for https://github.com/selner/jobs_scooper/issues/69
        //
        $tempArrListofSearches = array();
        foreach($arrCollapsedSearches as $search)
        {
            $tempArrListofSearches[] = $this->cloneSearchDetailsRecordExceptFor($search, array('key', 'name'));
        }
        $arrUniqSearches = array_unique_multidimensional($tempArrListofSearches);
        if(count($arrUniqSearches) != count($arrCollapsedSearches))
        {
            $this->arrSearchesToReturn = $arrUniqSearches;
            $GLOBALS['logger']->logLine($this->siteName . " had an incorrect duplicate search, so re-collapsed into " . count($arrUniqSearches). " search(es).", \Scooper\C__DISPLAY_WARNING__);
        }

    }

    //************************************************************************
    //
    //
    //
    //  Caching / File Storage
    //
    //
    //
    //************************************************************************


    private function _getJobsfromFileStoreForSearch_($searchSettings)
    {
        $key = $this->_getFileStoreKeyForSearch( $searchSettings, "");
        return readJobsListFromLocalJsonFile($key);

    }

    private function _getFileStoreKeyForSearch($searchSettings, $prefix="")
    {
        if(stripos($searchSettings['key'], $this->siteName) === false)
        {
            $prefix = $prefix .$this->siteName;
        }

        $key = $prefix . \Scooper\strip_punctuation($GLOBALS['USERDATA']['configuration_settings']['number_days'].$searchSettings['key']);

        return $key;
    }

    private function _setJobsToFileStoreForSearch_($searchSettings, $dataJobs)
    {
        $key = $this->_getFileStoreKeyForSearch( $searchSettings, "");
        return writeJobsListDataToLocalJSONFile($key, $dataJobs, JOBLIST_TYPE_UNFILTERED, $stageNumber=null, $searchDetails=$searchSettings);
    }

    //************************************************************************
    //
    //
    //
    //  Job listing download methods
    //
    //
    //
    //************************************************************************

    private function _getResultsForSearch_($searchDetails)
    {
        $tmpSearchJobs = $this->_getJobsfromFileStoreForSearch_($searchDetails);
        if(isset($tmpSearchJobs) && is_array($tmpSearchJobs))
            return $tmpSearchJobs;
        else
            return array();
    }


    private function _getJobsForSearchByType_($searchDetails)
    {
        $GLOBALS['logger']->logSectionHeader(("Starting data pull for " . $this->siteName . "[". $searchDetails['name']) ."]", \Scooper\C__SECTION_BEGIN__, \Scooper\C__NAPPTOPLEVEL__);
        $this->_logMemoryUsage_();
        $arrPageJobsList = null;

        try {

            // get the url for the first page/items in the results
            if($this->_checkInvalidURL_($searchDetails, $searchDetails['search_start_url']) == VALUE_NOT_SUPPORTED) return;

            // get all the results for all pages if we have them cached already
            $arrPageJobsList = $this->_getJobsfromFileStoreForSearch_($searchDetails);
            if(isset($arrPageJobsList))
            {
                $this->_setSearchResult_($searchDetails, $success = true, $details = 'Using cached results for ' . countAssociativeArrayValues($arrPageJobsList) . ' matching, unfiltered jobs.');
                $GLOBALS['logger']->logLine("Using cached " . $this->siteName . "[".$searchDetails['name']."]" .": " . countJobRecords($arrPageJobsList). " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);

            }
            else
            {
                $GLOBALS['logger']->logLine(("Starting data pull for " . $this->siteName . "[". $searchDetails['name'] . "] (no cache file found.)"), \Scooper\C__DISPLAY_ITEM_RESULT__);

                if($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__)
                {
                    $arrPageJobsList = $this->_getMyJobsForSearchFromJobsAPI_($searchDetails);
                }
                elseif($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__)
                {
                    $arrPageJobsList = $this->_getMyJobsForSearchFromWebpage_($searchDetails);
                }
                elseif($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_CLIENTSIDE_WEBPAGE__)
                {
                    $arrPageJobsList = $this->_getMyJobsForSearchFromWebpage_($searchDetails);
                }
                else
                {
                    throw new ErrorException("Class ". get_class($this) . " does not have a valid setting for parser.  Cannot continue.");
                }

                $this->_setJobsToFileStoreForSearch_($searchDetails, $arrPageJobsList);

                $this->_setSearchResult_($searchDetails, $success = true, $details = 'Search found ' . countAssociativeArrayValues($arrPageJobsList) . ' matching, unfiltered jobs.');

            }


            $GLOBALS['logger']->logSectionHeader(("Finished data pull for " . $this->siteName . "[". $searchDetails['name']), \Scooper\C__SECTION_END__, \Scooper\C__NAPPTOPLEVEL__);
        } catch (Exception $ex) {

            //
            // BUGBUG:  This is a workaround to prevent errors from showing up
            // when no results are returned for a particular search for EmploymentGuide plugin only
            // See https://github.com/selner/jobs_scooper/issues/23 for more details on
            // this particular underlying problem
            //
            if((isset($GLOBALS['JOBSITE_PLUGINS']['employmentguide']) && (strcasecmp($this->siteName, $GLOBALS['JOBSITE_PLUGINS']['employmentguide']['name']) == 0)||
                 (isset($GLOBALS['JOBSITE_PLUGINS']['careerbuilder']) && strcasecmp($this->siteName, $GLOBALS['JOBSITE_PLUGINS']['careerbuilder']['name']) == 0) ||
                (isset($GLOBALS['JOBSITE_PLUGINS']['ziprecruiter']) && strcasecmp($this->siteName, $GLOBALS['JOBSITE_PLUGINS']['ziprecruiter']['name']) == 0)) &&
                (substr_count($ex->getMessage(), "HTTP error #404") > 0))
            {
                $strError = $this->siteName . " plugin returned a 404 page for the search.  This is not an error; it means zero results found." ;
                $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ITEM_DETAIL__);

                $this->_setSearchResult_($searchDetails, $success = true, $details = 'Search found no matching, unfiltered jobs.');

            }
            else
            {
                //
                // Not the known issue case, so log the error and re-throw the exception
                // if we should have thrown one
                //
                $strError = "Failed to download jobs from " . $this->siteName ." jobs for search '".$searchDetails['name']. "[URL=".$searchDetails['search_start_url'] . "].  ".$ex->getMessage();
                $this->_setSearchResult_($searchDetails, $success = false, $details = $strError);
                $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                throw new Exception($strError);
            }
        }
    }

    private function _checkInvalidURL_($details, $strURL)
    {
        if($strURL == null) throw new ErrorException("Skipping " . $this->siteName ." search '".$details['name']. "' because a valid URL could not be set.");
        return $strURL;
        // if($strURL == VALUE_NOT_SUPPORTED) $GLOBALS['logger']->logLine("Skipping " . $this->siteName ." search '".$details['name']. "' because a valid URL could not be set.");
    }

    private function _setSearchResult_(&$searchDetails, $success = null, $details = "UNKNOWN RESULT.")
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Setting result value for search '" . $searchDetails['key'] ."' equal to " . $success ." with details '" . $details ."'.", \Scooper\C__DISPLAY_ITEM_DETAIL__);

        if(!array_key_exists($searchDetails['key'], $GLOBALS['USERDATA']['search_results']))
            throw new Exception("Error - Cannot Set Search Result for key " . $searchDetails['key'] . ".  Key does not exist in search results array.");

        $searchDetails['search_run_result'] = array(
            'success' => $success,
            'details'=> $details
        );
        $GLOBALS['USERDATA']['search_results'][$searchDetails['key']]['search_run_result'] = $searchDetails['search_run_result'];
    }

    protected function _getMyJobsForSearchFromJobsAPI_($searchDetails)
    {
        $nItemCount = 0;

        $arrSearchReturnedJobs = [];
        $GLOBALS['logger']->logLine("Downloading count of " . $this->siteName ." jobs for search '".$searchDetails['key']. "'", \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $pageNumber = 1;
        $noMoreJobs = false;
        while($noMoreJobs != true)
        {
            $arrPageJobsList = [];
            $apiJobs = $this->getSearchJobsFromAPI($searchDetails, $pageNumber);

            foreach($apiJobs as $job)
            {
                $item = $this->getEmptyJobListingRecord();
                $item['job_title'] = $job->name;
                $item['job_id'] = $job->sourceId;
                if($item['job_id'] == null)
                    $item['job_id'] = $job->url;

                if(strlen(trim($item['job_title'])) == 0 || strlen(trim($item['job_id'])) == 0)
                {
                    continue;
                }
                $item['location'] = $job->location;
                $item['company'] = $job->company;
                if($job->datePosted != null)
                    $item['job_site_date'] = $job->datePosted->format('D, M d');
                $item['job_post_url'] = $job->url;

                $strCurrentJobIndex = getArrayKeyValueForJob($item);
                $arrPageJobsList[$strCurrentJobIndex] = $item;
                $nItemCount += 1;
            }
            if(count($arrPageJobsList) < $this->nJobListingsPerPage)
            {
                addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);
                $noMoreJobs = true;
            }
            else
            {
                addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);
            }
            $pageNumber++;
        }

        $GLOBALS['logger']->logLine($this->siteName . "[".$searchDetails['name']."]" .": " . $nItemCount . " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
        return $arrSearchReturnedJobs;
    }

    private function _getMyJobsForSearchFromWebpage_($searchDetails)
    {

        $nItemCount = 1;
        $nPageCount = 1;
        $arrSearchReturnedJobs = null;
        $selen = null;
        $objSimpleHTML = null;

        $GLOBALS['logger']->logLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['key']. "': ".$searchDetails['search_start_url'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

        try
        {
            if($this->isBitFlagSet(C__JOB_USE_SELENIUM))
            {
                try
                {
                    $selen = new SeleniumSession($this->additionalLoadDelaySeconds);
                    $html = $selen->getPageHTML($searchDetails['search_start_url']);
                    $objSimpleHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);
                } catch (Exception $ex) {
                    $strMsg = "Failed to get dynamic HTML via Selenium due to error:  ".$ex->getMessage();

                    $GLOBALS['logger']->logLine($strMsg, \Scooper\C__DISPLAY_ERROR__);
                    throw new ErrorException($strMsg);
                }
            }
            else
            {
                $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $searchDetails['search_start_url'], $this->secsPageTimeout );
            }
            if(!$objSimpleHTML) { throw new ErrorException("Error:  unable to get SimpleHTML object for ".$searchDetails['search_start_url']); }

            $totalPagesCount = C__TOTAL_ITEMS_UNKNOWN__;
            $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__  ; // placeholder because we don't know how many are on the page
            if($this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) && $this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__) )
            {
                // if we can't get a number of pages AND we can't get a number of items,
                // we must assume there is, at most, only one page of results.
                $totalPagesCount = 1;
                $nTotalListings = $this->nJobListingsPerPage;

            } elseif(!$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) || !$this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__))
            {
                $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML->root);
                $nTotalListings  = intval(str_replace(",", "", $strTotalResults));
                if($nTotalListings == 0)
                {
                    $totalPagesCount = 0;
                }
                elseif($this->isBitFlagSet(C__JOB_SINGLEPAGE_RESULTS))
                {
                    $totalPagesCount = 1;
                }
                elseif($nTotalListings != C__TOTAL_ITEMS_UNKNOWN__)
                {
                    if($nTotalListings > C_JOB_MAX_RESULTS_PER_SEARCH ) {
                        $GLOBALS['logger']->logLine("Search '" . $searchDetails['key'] . "' returned more results than allowed.  Only retrieving the first " . C_JOB_MAX_RESULTS_PER_SEARCH . " of  " . $nTotalListings . " job listings.", \Scooper\C__DISPLAY_WARNING__);
                        $nTotalListings = C_JOB_MAX_RESULTS_PER_SEARCH;
                    }
                        $totalPagesCount = \Scooper\intceil($nTotalListings  / $this->nJobListingsPerPage); // round up always
                    if($totalPagesCount < 1)  $totalPagesCount = 1;
                }
            }


            //
            // If this is just a test run to verify everything is functioning and all plugins are returning data,
            // then only bring back the first page and/or first 10 or so results to verify.  We don't need to bring
            // back hundreds of results to test things are running successfully.
            //
            if (isTestRun() && !$this->isBitFlagSet(C__JOB_SINGLEPAGE_RESULTS))
            {
                $maxListings = $this->nJobListingsPerPage * 2;
                if($nTotalListings > $maxListings)
                {
                    $nTotalListings = $maxListings;
                    $totalPagesCount = 2;
                }
            }


            if($nTotalListings <= 0)
            {
                $GLOBALS['logger']->logLine("No new job listings were found on " . $this->siteName . " for search '" . $searchDetails['name']."'.", \Scooper\C__DISPLAY_ITEM_START__);
                return array();
            }
            else
            {
                $nJobsFound = 0;

                $GLOBALS['logger']->logLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$searchDetails['search_start_url'], \Scooper\C__DISPLAY_ITEM_START__);

                $strURL = $searchDetails['search_start_url'];
                while ($nPageCount <= $totalPagesCount )
                {

                    $arrPageJobsList = null;

                    if($this->isBitFlagSet(C__JOB_USE_SELENIUM))
                    {
                        try
                        {
                            if($this->isBitFlagSet( C__JOB_PAGE_VIA_URL) || $this->isBitFlagSet( C__JOB_SINGLEPAGE_RESULTS))
                            {
                                $strURL = $this->getPageURLfromBaseFmt($searchDetails, $nPageCount, $nItemCount);
                                if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED)
                                    return null;
                                $selen->loadPage($strURL);
                            }
                            elseif($this->isBitFlagSet( C__JOB_CLIENTSIDE_INFSCROLLPAGE))
                            {
                                $selen->loadPage($strURL);
                                while($nPageCount <= $totalPagesCount)
                                {
                                    if(isDebug() && isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("... getting infinite results page #".$nPageCount." of " .$totalPagesCount, \Scooper\C__DISPLAY_NORMAL__); }
                                    $this->getNextInfiniteScrollSet($selen->driver);
                                    $nPageCount = $nPageCount + 1;
                                }
                            }
                            elseif(!$this->isBitFlagSet( C__JOB_CLIENTSIDE_INFSCROLLPAGE)) {
                                if (method_exists($this, 'takeNextPageAction') && $nPageCount > 1 && $nPageCount <= $totalPagesCount) {
                                    //
                                    // if we got a driver instance back, then we got a new page
                                    // otherwise we're out of results so end the loop here.
                                    //
                                    try {
                                        $this->takeNextPageAction($selen->driver);
                                    } catch (Exception $ex) {
                                        $strMsg = "Failed to take nextPageAction on page " . $nPageCount . ".  Error:  ".$ex;

                                        $GLOBALS['logger']->logLine($strMsg, \Scooper\C__DISPLAY_ERROR__);
                                        throw new ErrorException($strMsg);
                                    }
                                }
                            }

                            $strURL = $selen->driver->getCurrentURL();
                            $html = $selen->driver->getPageSource();
                            $objSimpleHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);

                    } catch (Exception $ex) {
                            $strMsg = "Failed to get dynamic HTML via Selenium due to error:  ".$ex->getMessage();

                            $GLOBALS['logger']->logLine($strMsg, \Scooper\C__DISPLAY_ERROR__);
                            throw new ErrorException($strMsg);
                        }
                    }
                    else
                    {
                        $strURL = $this->getPageURLfromBaseFmt($searchDetails, $nPageCount, $nItemCount);
                        if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED)
                            return null;

                        $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL, $this->secsPageTimeout);
                    }
                    if(!$objSimpleHTML) throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL);

                    $GLOBALS['logger']->logLine("Getting page # ". $nPageCount . " of jobs from ". $strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    try
                    {

                        if($this->isBitFlagSet(C__JOB_PREFER_MICRODATA))
                        {
                            $arrPageJobsList = $this->_getJobsFromMicroData_($objSimpleHTML);
                        }
                        else
                        {
                            $arrPageJobsList = $this->parseJobsListForPage($objSimpleHTML);
                            if(!is_array($arrPageJobsList) )
                            {
                                // we likely hit a page where jobs started to be hidden.
                                // Go ahead and bail on the loop here
                                $strWarnHiddenListings = "Could not get all job results back from ". $this->siteName . " for this search starting on page " . $nPageCount.".";
                                if($nPageCount < $totalPagesCount)
                                    $strWarnHiddenListings .= "  They likely have hidden the remaining " . ($totalPagesCount - $nPageCount) . " pages worth. ";

                                $GLOBALS['logger']->logLine($strWarnHiddenListings, \Scooper\C__DISPLAY_ITEM_START__);
                                $nPageCount = $totalPagesCount;
                            }

                        }

                        if(is_array($arrPageJobsList))
                        {
                            $this->normalizeJobList($arrPageJobsList);

                            addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);
                            $nJobsFound = countJobRecords($arrSearchReturnedJobs);
                            if($nItemCount == 1) { $nItemCount = 0; }
                            $nItemCount += ($nJobsFound < $this->nJobListingsPerPage) ? $nJobsFound : $this->nJobListingsPerPage;

                            // If we don't know the total number of listings we will get, we can guess that we've got them all
                            // if we did not get the max number of job listings from the last page.  Basically, if we couldn't
                            // fill up a page with our search, then they must not be that many listings avaialble.
                            //
                            if ($totalPagesCount > 1 && $nTotalListings == C__TOTAL_ITEMS_UNKNOWN__ && countAssociativeArrayValues($arrPageJobsList) < $this->nJobListingsPerPage) {
                                $totalPagesCount = $nPageCount;
                                $nTotalListings = countAssociativeArrayValues($arrSearchReturnedJobs);
                            }

                            $GLOBALS['logger']->logLine("Loaded " . countAssociativeArrayValues($arrSearchReturnedJobs). " of " . $nTotalListings . " job listings from " . $this->siteName, \Scooper\C__DISPLAY_NORMAL__ );
                        }
                    } catch (Exception $ex) {
                        $GLOBALS['logger']->logLine($this->siteName . " error: " . $ex, \Scooper\C__DISPLAY_ERROR__);
                        throw $ex;
                    }

                    //
                    // Look check for plugin errors that are not caught.  If we have looped through one page of results,
                    // we should either have returned at least 1 listing of the total count OR if we have retrieved fewer
                    // listings than are expected on a page, then we should our page count should be the same as the last page.
                    //
                    // If either is not true, then we're likely in an error condtion and about to go a bit wacky, possibly in a major loop.
                    // Throw an error for this search instead and move on.
                    //
                    $err = null;
                    if ($nTotalListings > 0 && $nItemCount == 0) // We got zero listings but should have found some
                        $err = "Retrieved 0 of the expected " . $nTotalListings . " listings for " . $this->siteName. " (search = " . $searchDetails['key'] . ")";
                    elseif ($nItemCount < $this->nJobListingsPerPage && $nPageCount < $totalPagesCount)
                        $err = "Retrieved only ". $nItemCount . " of the " . $this->nJobListingsPerPage ." job listings on page " . $nPageCount . " for " . $this->siteName . " (search = " . $searchDetails['key'] . ")";
                    elseif ($nJobsFound < $nTotalListings && $nPageCount == $totalPagesCount && !$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__))
                        $err = "Retrieved only " . $nJobsFound . " of the " . $nTotalListings . " listings that we expected for " . $this->siteName. " (search = " . $searchDetails['key'] . ")";
                    elseif ($nJobsFound > $nTotalListings && $nPageCount == $totalPagesCount) {
                        $warnMsg = "Warning:  Downloaded " . ($nJobsFound - $nTotalListings) . " jobs more than the " . $nTotalListings . " expected for " . $this->siteName . " (search = " . $searchDetails['key'] . ")";
                        $GLOBALS['logger']->logLine($warnMsg, \Scooper\C__DISPLAY_WARNING__);
                    }

                    if(!is_null($err)) {
                        if ($this->isBitFlagSet(C__JOB_IGNORE_MISMATCHED_JOB_COUNTS) || $this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) === true)
                        {
                            $GLOBALS['logger']->logLine("Warning: " . $err, \Scooper\C__DISPLAY_WARNING__);
                        }
                        else {
                            $errHTMLFile = $GLOBALS['USERDATA']['directories']['stage1'] . "/" . getDefaultJobsOutputFileName($strFilePrefix = "error", $strBase = $searchDetails['key'], $strExt = "html", $delim = "-");
                            $objSimpleHTML->save($errHTMLFile);

                            $err = "Error: " . $err . "  Aborting job site plugin to prevent further errors.  HTML for page that generated the error saved to " . $errHTMLFile;

                            $this->_setSearchResult_($searchDetails, $success = false, $details = $err);
                            $this->_setJobsToFileStoreForSearch_($searchDetails, $arrSearchReturnedJobs);

                            $GLOBALS['logger']->logLine($err, \Scooper\C__DISPLAY_ERROR__);

                            throw new Exception($err);
                        }
                    }

                    // clean up memory
                    $objSimpleHTML->clear();
                    unset($objSimpleHTML);
                    $objSimpleHTML = null;
                    $nPageCount++;


                }

            }

            $GLOBALS['logger']->logLine($this->siteName . "[".$searchDetails['name']."]" .": " . $nJobsFound . " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
            return $arrSearchReturnedJobs;

        } catch (Exception $ex) {
            $GLOBALS['logger']->logLine($this->siteName . " error: " . $ex, \Scooper\C__DISPLAY_ERROR__);
            throw $ex;
        }
    }

    private function _getJobsFromMicroData_($objSimpleHTML)
    {
        $config  = array('html' => (string)$objSimpleHTML);
        $obj = new linclark\MicrodataPHP\MicrodataPhp($config);
        $micro = $obj->obj();
        $ret = null;

        if($micro && $micro->items && count($micro->items) > 0)
        {
            foreach($micro->items as $mditem)
            {
                if (isset($mditem->type) && strcasecmp(parse_url($mditem->type[0], PHP_URL_PATH), "/JobPosting") == 0) {

                    $item = $this->getEmptyJobListingRecord();

                    $item['job_title'] = $mditem->properties["title"][0];
                    if(isset($mditem->properties["url"]))
                        $item['job_post_url'] = $mditem->properties["url"][0];
                    elseif(isset($mditem->properties["mainEntityOfPage"]))
                        $item['job_post_url'] = $mditem->properties["mainEntityOfPage"][0];

                    if (isset($mditem->properties['hiringOrganization']))
                        $item['company'] = $mditem->properties['hiringOrganization'][0]->properties['name'][0];

                    if (isset($mditem->properties['datePosted']))
                        $item['job_site_date'] = $mditem->properties['datePosted'][0];


                    if (isset($mditem->properties['jobLocation']) && is_array($mditem->properties['jobLocation']))
                    {
                        if (is_array($mditem->properties['jobLocation']))
                        {
                            if (isset($mditem->properties['jobLocation'][0]->properties['address']) && is_array($mditem->properties['jobLocation'][0]->properties['address']))
                            {
                                $city = "";
                                $region = "";
                                $zip = "";

                                if (isset($mditem->properties['jobLocation'][0]->properties['address'][0]->properties['addressLocality']))
                                    $city = $mditem->properties['jobLocation'][0]->properties['address'][0]->properties['addressLocality'][0];
                                if (isset($mditem->properties['jobLocation'][0]->properties['address'][0]->properties['addressRegion']))
                                    $region = $mditem->properties['jobLocation'][0]->properties['address'][0]->properties['addressRegion'][0];
                                if (isset($mditem->properties['jobLocation'][0]->properties['address'][0]->properties['postalCode']))
                                    $zip = $mditem->properties['jobLocation'][0]->properties['address'][0]->properties['postalCode'][0];
                                $item["location"] = $city . " " . $region . " " & $zip;
                            }

                        }
                        else
                        {
                            $item["location"] = $mditem->properties['jobLocation'][0];
                        }
                    }
                    $item['company'] = $this->siteName;
                }
            }
        }

        return $ret;
    }

    protected function getSearchJobsFromAPI($searchDetails) {   throw new \BadMethodCallException(sprintf("Not implemented method called on class \"%s \".", __CLASS__)); }

}
