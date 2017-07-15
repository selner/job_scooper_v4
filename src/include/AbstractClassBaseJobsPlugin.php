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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/include/SeleniumSession.php');
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');
header('Content-Type: text/html');

const VALUE_NOT_SUPPORTED = -1;
const BASE_URL_TAG_LOCATION = "***LOCATION***";
const BASE_URL_TAG_KEYWORDS = "***KEYWORDS***";

abstract class AbstractClassBaseJobsPlugin extends ClassJobsSiteCommon
{

    function __construct($strOutputDirectory = null, $attributes = null)
    {
        parent::__construct($strOutputDirectory);

        if(array_key_exists("JOBSITE_PLUGINS", $GLOBALS) && (array_key_exists(strtolower($this->siteName), $GLOBALS['JOBSITE_PLUGINS'])))
        {
            $plugin = $GLOBALS['JOBSITE_PLUGINS'][strtolower($this->siteName)];
            if(array_key_exists("other_settings", $plugin) && is_array($plugin['other_settings']))
            {
                $keys = array_keys($plugin['other_settings']);
                foreach($keys as $attrib_name)
                {
                    $this->$attrib_name = $plugin['other_settings'][$attrib_name];
                }
            }
        }


        if(stristr($this->strBaseURLFormat, "***KEYWORDS***") == false)
            $this->additionalFlags[] = C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED;

        if(stristr($this->strBaseURLFormat, "***LOCATION***") == false)
            $this->additionalFlags[] = C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED;

        if(stristr($this->strBaseURLFormat, "***NUMBER_DAYS***") == false)
            $this->additionalFlags[] = C__JOB_DAYS_VALUE_NOTAPPLICABLE__;

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

        if($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
            $this->nMaxJobsToReturn = $this->nMaxJobsToReturn * 3;
        }

        if($this->paginationType == C__PAGINATION_INFSCROLLPAGE_NOCONTROL) {
            $this->nJobListingsPerPage = $this->nMaxJobsToReturn;
            
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

    public function isBitFlagSet($flagToCheck)
    {
        $ret = \Scooper\isBitFlagSet($this->_flags_, $flagToCheck);
        if($ret == $flagToCheck) { return true; }
        return false;
    }

    public function isSearchCached($searchDetails)
    {
        return ($searchDetails['is_cached'] == true);
    }

    public function addSearches(&$arrSearches)
    {

        if (!is_array($arrSearches[0])) {
            $arrSearches[] = $arrSearches;
        }

        if($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
            $soleSearch = $arrSearches[0];
            $arrSearches = array();
            $arrSearches[] = $soleSearch;
        }

        foreach ($arrSearches as $searchDetails) {
            $this->_addSearch_($searchDetails);
        }
    }

    public function getMyJobsList($returnFailedSearchResults = false)
    {
        $retAllSearchResults = array();
        if(isset($this->arrSearchesToReturn) && is_array($this->arrSearchesToReturn))
        {
            foreach($this->arrSearchesToReturn as $searchDetails)
            {

                $tmpSearchJobs = $this->_getResultsForSearch_($searchDetails, $returnFailedSearchResults);
//                $this->markJobsList_withAutoItems($tmpSearchJobs);
                addJobsToJobsList($retAllSearchResults, $tmpSearchJobs);
            }
        }
        return $retAllSearchResults;
    }

    public function getUpdatedJobsForAllSearches($returnFailedSearchResults = true)
    {
        $strIncludeKey = 'include_' . strtolower($this->siteName);

        if(isset($GLOBALS['OPTS'][$strIncludeKey]) && $GLOBALS['OPTS'][$strIncludeKey] == 0)
        {
            $GLOBALS['logger']->logLine($this->siteName . ": excluded for run. Skipping '" . count($this->arrSearchesToReturn) . "' site search(es).", \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return array();
        }

        if(count($this->arrSearchesToReturn) == 0)
        {
            $GLOBALS['logger']->logLine($this->siteName . ": no searches set. Skipping...", \Scooper\C__DISPLAY_ITEM_DETAIL__);
            return array();
        }

        foreach($this->arrSearchesToReturn as $search)
        {
            $this->currentSearchBeingRun = $search;

            try
            {
                // assert this search is actually for the job site supported by this plugin
                assert(strcasecmp(strtolower($search['site_name']), strtolower($this->siteName)) == 0);
                $GLOBALS['logger']->logSectionHeader(("Starting data pull for " . $this->siteName . "[" . $search['key']) . "]", \Scooper\C__NAPPTOPLEVEL__, \Scooper\C__SECTION_BEGIN__);

                if ($this->isSearchCached($search) == true) {
                    $GLOBALS['logger']->logLine("Jobs data for '" . $search['key'] . " has already been cached.  Skipping jobs download.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    continue;
                }

                if ($this->isBitFlagSet(C__JOB_USE_SELENIUM)) {
                    try
                    {
                        if ($GLOBALS['USERDATA']['selenium']['autostart'] == True) {
                            SeleniumSession::startSeleniumServer();
                        }
                        $this->selenium = new SeleniumSession();
                    } catch (Exception $ex) {
                        handleException($ex, "Unable to start Selenium to get jobs for plugin '" . $this->siteName ."'", true);
                    }
                }

                $this->_updateJobsDataForSearch_($search);
            }
            catch (Exception $ex)
            {
                throw $ex;
            }
            finally
            {
                $this->currentSearchBeingRun = null;
            }
        }


        return $this->getMyJobsList($returnFailedSearchResults);
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
    protected $additionalFlags = array();
    protected $paginationType = null;
    protected $secsPageTimeout = null;
    protected $pluginResultsType;
    protected $selenium = null;
    protected $nextPageScript = null;
    protected $selectorMoreListings = null;
    protected $nMaxJobsToReturn = C_JOB_MAX_RESULTS_PER_SEARCH;
    protected $currentSearchBeingRun = null;

    protected $strKeywordDelimiter = null;
    protected $additionalLoadDelaySeconds = 0;
    protected $_flags_ = null;

    protected function getActiveWebdriver()
    {
        if (!is_null($this->selenium))
        {
            return $this->selenium->get_driver();
        }
        else
            throw new Exception("Error:  active webdriver for Selenium not found as expected.");
    }

    protected function _exportObjectToJSON_()
    {
        //
        // Note:  does not export the job listings attached to the plugin
        //        instance.
        //

        if(!is_null($this->arrSearchesToReturn) && count($this->arrSearchesToReturn) > 0)
        {
            $filenm = exportToDebugJSON(\Scooper\object_to_array($this), ($this->siteName . "-plugin-state-data"));
            if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("JSON state data for plugin '" . $this->siteName . "' written to " . $filenm . ".", \Scooper\C__DISPLAY_ITEM_DETAIL__);

            return $filenm;
        }
    }

    protected function getCombinedKeywordString($arrKeywordSet)
    {
        $arrKeywords = array();

        if(!is_array($arrKeywordSet))
        {
            $arrKeywords[] = $arrKeywordSet[0];
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

        $strRetCombinedKeywords = array_shift($arrKeywords);

        return $strRetCombinedKeywords;
    }

    protected function parseJobsListForPage($objSimpHTML) {   throw new \BadMethodCallException(sprintf("Not implemented method  " . __METHOD__ . " called on class \"%s \".", __CLASS__)); }

    protected function getLocationURLValue($searchDetails, $locSettingSets = null)
    {
        $strReturnLocation = VALUE_NOT_SUPPORTED;

        if($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED))
        {
            throw new ErrorException($this->siteName . " does not support the ***LOCATION*** replacement value in a base URL.  Please review and change your base URL format to remove the location value.  Aborting all searches for " . $this->siteName, \Scooper\C__DISPLAY_ERROR__);
        }

        // Did the user specify an override at the search level in the INI?
        if ($searchDetails != null && isset($searchDetails['location_user_specified_override']) && strlen($searchDetails['location_user_specified_override']) > 0)
        {
            $strReturnLocation = $searchDetails['location_user_specified_override'];
        }
        else
        {
            // No override, so let's see if the search settings have defined one for us
            $locTypeNeeded = $this->getLocationSettingType();
            if($locTypeNeeded == null || $locTypeNeeded == "")
            {
                if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Plugin for '" . $searchDetails['site_name'] . "' did not have the required location type of " . $locTypeNeeded . " set.   Skipping search '" . $searchDetails['key'] . "' with settings '" . $locSettingSets['key'] . "'.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                return $strReturnLocation;
            }
            
            if(isset($locSettingSets) && count($locSettingSets) > 0 && isset($locSettingSets[$locTypeNeeded]))
            {
                $strReturnLocation = $locSettingSets[$locTypeNeeded];
            }

            if($strReturnLocation == null || $strReturnLocation == VALUE_NOT_SUPPORTED)
            {
                if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Plugin for '" . $searchDetails['site_name'] . "' did not have the required location type of " . $locTypeNeeded . " set.   Skipping search '" . $searchDetails['key'] . "' with settings '" . $locSettingSets['key'] . "'.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
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
        $strURL = $this->_getBaseURLFormat_($searchDetails, $nPage, $nItem);


        $strURL = str_ireplace("***NUMBER_DAYS***", $this->getDaysURLValue($GLOBALS['USERDATA']['configuration_settings']['number_days']), $strURL);
        $strURL = str_ireplace("***PAGE_NUMBER***", $this->getPageURLValue($nPage), $strURL);
        $strURL = str_ireplace("***ITEM_NUMBER***", $this->getItemURLValue($nItem), $strURL);
        $strURL = str_ireplace(BASE_URL_TAG_KEYWORDS, $this->getKeywordURLValue($searchDetails), $strURL);


        $nSubtermMatches = substr_count($strURL, BASE_URL_TAG_LOCATION);

        if(!$this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) && $nSubtermMatches > 0)
        {
            $strLocationValue = $searchDetails['location_search_value'];
            if($strLocationValue == VALUE_NOT_SUPPORTED)
            {
                if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Failed to run search:  search is missing the required location type of " . $this->getLocationSettingType() . " set.  Skipping search '" . $searchDetails['key'] . ".", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                $strURL = VALUE_NOT_SUPPORTED;
            }
            else
            {
                $strURL = str_ireplace(BASE_URL_TAG_LOCATION, $strLocationValue, $strURL);
            }
        }

        if($strURL == null) { throw new ErrorException("Location value is required for " . $this->siteName . ", but was not set for the search '" . $searchDetails['key'] ."'.". " Aborting all searches for ". $this->siteName, \Scooper\C__DISPLAY_ERROR__); }

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

    protected function _getBaseURLFormat_($searchDetails = null, $nPage = null, $nItem = null)
    {
        $strBaseURL = VALUE_NOT_SUPPORTED;

        if($searchDetails != null && isset($searchDetails['base_url_format']))
        {
            $strBaseURL = $searchDetails['base_url_format'];
        }
        elseif(!is_null($this->strBaseURLFormat) && strlen($this->strBaseURLFormat) > 0)
        {
            $strBaseURL = $searchDetails['base_url_format'] = $this->strBaseURLFormat;
        }
        elseif(!is_null($this->siteBaseURL) && strlen($this->siteBaseURL) > 0)
        {
            $strBaseURL = $searchDetails['base_url_format'] = $this->siteBaseURL;
        }
        else
        {
            throw new ErrorException("Could not find base URL format for " . $this->siteName . ".  Aborting all searches for " . $this->siteName, \Scooper\C__DISPLAY_ERROR__);
        }
        return $strBaseURL;
    }

    protected function getDaysURLValue($nDays = null) { return ($nDays == null || $nDays == "") ? 1 : $nDays; }

    protected function getPageURLValue($nPage) { return ($nPage == null || $nPage == "") ? "" : $nPage; }

    protected function getItemURLValue($nItem) { return ($nItem == null || $nItem == "") ? 0 : $nItem; }

    protected function parseTotalResultsCount($objSimpHTML) {   throw new \BadMethodCallException(sprintf("Not implemented method " . __METHOD__ . " called on class \"%s \".", __CLASS__)); }


    protected function moveDownOnePageInBrowser()
    {

        // Neat trick written up by http://softwaretestutorials.blogspot.in/2016/09/how-to-perform-page-scrolling-with.html.
        $driver = $this->getActiveWebdriver();

        $driver->executeScript("window.scrollTo(0,document.body.scrollHeight);");

        sleep($this->additionalLoadDelaySeconds + 1);

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
        if (isDebug() == true) {
            $this->_exportObjectToJSON_();
        }

    }


    protected function goToEndOfResultsSetViaLoadMore($nTotalItems = null)
    {
        $this->moveDownOnePageInBrowser();
        $secs = $this->additionalLoadDelaySeconds * 1000;
        if($secs <= 0)
            $secs = 1000;

        $js = "
            scroll = setTimeout(doLoadMore, 250);
            function getRunTime()
            {
                var startTime = localStorage.getItem(\"startTime\");
                var endTime = Date.now();
                runtime = Math.floor((endTime-startTime)/(1000));
                return (runtime + ' seconds');
            }

            function doLoadMore() 
            {
                var startTime = localStorage.getItem(\"startTime\");
                if(startTime == null) 
                {
                    localStorage.setItem(\"startTime\", Date.now());
                    localStorage.setItem(\"pageNum\", 1);
                }

                window.scrollTo(0,document.body.scrollHeight);
                console.log('paged-down-before-click');

                var loadmore = document.querySelector(\"" . $this->selectorMoreListings . "\");
                if(loadmore != null && !typeof(loadmore.click) !== \"function\" && loadmore.length >= 1) {
                    loadmore = loadmore[0];
                } 
    
                runtime = getRunTime();
                if(loadmore != null && loadmore.style.display === \"\") 
                { 
                    var pageNum = parseInt(localStorage.getItem(\"pageNum\"));
                    if (pageNum != null)
                    {   
                        console.log('Results for page # ' + pageNum + ' loaded.  Time spent so far:  ' + runtime + ' Going to next page...');
                        localStorage.setItem(\"pageNum\", pageNum + 1);
                    }
                    loadmore.click();  
                    console.log(\"Clicked load more control...\");
                        
                    scroll = setTimeout(doLoadMore, " . $secs . ");
                    window.scrollTo(0,document.body.scrollHeight);
                    console.log('paged-down-after-click');
                }
                else
                {
                    console.log('Load more button no longer active; done paginating the results.');
                    console.log('Script needed a minimum of ' + runtime + ' seconds to load all the results.');
                    localStorage.removeItem(\"startTime\");

                }
            }  
        ";


        if(is_null($nTotalItems))
        {
            $nTotalItems = $this->nMaxJobsToReturn;
        }

        if($nTotalItems == C__TOTAL_ITEMS_UNKNOWN__)
        {
            $nSleepTimeToLoad = 30 + $this->additionalLoadDelaySeconds;
        }
        else {
            $nSleepTimeToLoad = ($nTotalItems / $this->nJobListingsPerPage) * $this->additionalLoadDelaySeconds;
        }

        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Sleeping for " . $nSleepTimeToLoad . " seconds to allow browser to page down through all the results", \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $this->runJavaScriptSnippet($js, false);

        sleep($nSleepTimeToLoad > 0 ? $nSleepTimeToLoad : 2);

        $this->moveDownOnePageInBrowser();

    }


    protected function goToNextPageOfResultsViaNextButton()
    {
        $secs = $this->additionalLoadDelaySeconds * 1000;
        if($secs <= 0)
            $secs = 1000;

        $objSimplHtml = $this->getSimpleHtmlDomFromSeleniumPage();

        $node = $objSimplHtml->find($this->selectorMoreListings);
        if($node == null || count($node) == 0)
        {
            return false;
        }
        else
        {
            if(stristr($node[0]->attr["style"], "display: none") !== false) {
                return false;
            }
        }

        $js = "
            scroll = setTimeout(doNextPage, " . $secs . ");
            function doNextPage() 
            {
                var loadnext = document.querySelector(\"" . $this->selectorMoreListings . "\");
                if(loadnext != null && !typeof(loadnext .click) !== \"function\" && loadnext.length >= 1) {
                    loadnext = loadnext[0];
                } 
    
                if(loadnext != null && loadnext.style.display === \"\") 
                { 
                    loadnext.click();  
                    console.log(\"Clicked load next results control " . $this->selectorMoreListings . "...\");
                }
            }  
        ";

        $this->runJavaScriptSnippet($js, false);

        sleep($this->additionalLoadDelaySeconds > 0 ? $this->additionalLoadDelaySeconds : 2);

        return true;
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


    private function _addSearch_(&$searchDetails)
    {

        if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_ANYWHERE) && $this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
        {
            $strErr = "Skipping " . $searchDetails['key'] . " search on " . $this->siteName . ".  The plugin only can return all results and therefore cannot be matched with the requested keyword string [Search requested keyword match=anywhere].  ";
            $GLOBALS['logger']->logLine($strErr, \Scooper\C__DISPLAY_ITEM_DETAIL__);
        }
        else
        {

            if ($searchDetails['key'] == "") {
                $searchDetails['key'] = \Scooper\strScrub($searchDetails['site_name'], FOR_LOOKUP_VALUE_MATCHING) . "-" . \Scooper\strScrub($searchDetails['key'], FOR_LOOKUP_VALUE_MATCHING);
            }

            assert($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || ($searchDetails['location_search_value'] !== VALUE_NOT_SUPPORTED && strlen($searchDetails['location_search_value']) > 0));

            if($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
                // null out any generalized keyword set values we previously had
                $searchDetails['keywords_array'] = null;
                $searchDetails['keywords_array_tokenized'] = null;
                $searchDetails['keywords_string_for_url'] = null;
            }
            else
            {
                $this->_setKeywordStringsForSearch_($searchDetails);
            }

            $this->_setStartingUrlForSearch_($searchDetails);


            // Check the cached data to see if we already have jobs saved for this search & timeframe.
            // if so, mark the search as cached
            $arrSearchJobList = $this->_getJobsfromFileStoreForSearch_($searchSettings = $searchDetails, $returnFailedSearches = false);
            if((is_null($arrSearchJobList) || !is_array($arrSearchJobList)) == false)
            {
                // we have previously cached good search results for this search timeframe
                $searchDetails['is_cached'] = true;
            }

            // add a global record for the search so we can report errors
            $GLOBALS['USERDATA']['search_results'][$searchDetails['key']] = \Scooper\array_copy($searchDetails);

            //
            // Add the search to the list of ones to run
            //
            $this->arrSearchesToReturn[] = $searchDetails;
            $GLOBALS['logger']->logLine($this->siteName . ": added search (" . $searchDetails['key'] . ")", \Scooper\C__DISPLAY_ITEM_DETAIL__);
        }

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
            $arrKeywords[] = $arrKeywordSet[0];
        }
        else
        {
            $arrKeywords = $arrKeywordSet;
        }

        $strRetCombinedKeywords = $this->getCombinedKeywordString($arrKeywords);

        if(!isValueURLEncoded($strRetCombinedKeywords))
        {
            if ($this->isBitFlagSet(C__JOB_KEYWORD_PARAMETER_SPACES_RAW_ENCODE))
                $strRetCombinedKeywords = rawurlencode($strRetCombinedKeywords);
            else
                $strRetCombinedKeywords = urlencode($strRetCombinedKeywords);

        }

        if($this->isBitFlagSet(C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES))
        {
            $strRetCombinedKeywords = str_replace("%22", "-", $strRetCombinedKeywords);
            $strRetCombinedKeywords = str_replace("+", "-", $strRetCombinedKeywords);
        }

        return $strRetCombinedKeywords;
    }

    private function _setStartingUrlForSearch_(&$searchDetails)
    {

        $searchStartURL = $this->getPageURLfromBaseFmt($searchDetails, 1, 1);
        if(is_null($searchStartURL) || strlen($searchStartURL) == 0)
            $searchStartURL = $this->siteBaseURL;

        $searchDetails['search_start_url'] = $searchStartURL;
        $GLOBALS['logger']->logLine("Setting start URL for " . $this->siteName . "[" . $searchDetails['key'] . "] to: " . PHP_EOL . $searchDetails['search_start_url'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

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


    private function _getFileStoreKeyForSearch($searchSettings, $prefix = "")
    {
        if (stripos($searchSettings['key'], $this->siteName) === false) {
            $prefix = $prefix . $this->siteName;
        }

        $key = $prefix;

        if(!$this->isBitFlagSet(C__JOB_DAYS_VALUE_NOTAPPLICABLE__))
            $key = $key . \Scooper\strip_punctuation($GLOBALS['USERDATA']['configuration_settings']['number_days'] );

        $key = $key . $searchSettings['key'];
        return $key;
    }

    private function _getDirKey_()
    {
        if($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
            return "listings-rawbysite-allusers";
        else
            return "listings-raw";

    }
        

    private function _getJobsfromFileStoreForSearch_(&$searchSettings, $returnFailedSearches = true)
    {
        $retJobs = null;

        $key = $this->_getFileStoreKeyForSearch($searchSettings, "");

        $data = readJobsListDataFromLocalJsonFile($key, $returnFailedSearches, $dirKey = $this->_getDirKey_());
        if (!is_null($data) && is_array($data)) {
            if (array_key_exists("jobslist", $data) && !is_null($data['jobslist']) && is_array($data['jobslist'])) {
                $retJobs = array_filter($data['jobslist'], "isIncludedJobSite");
            }
            if (array_key_exists("search", $data) && !is_null($data['search']) && is_array($data['search'])) {
                if (!is_null($data['search']))
                {
                    $searchSettings = \Scooper\array_copy($data['search']);
                }
            }
        }

        return $retJobs;

    }

    private function _setJobsToFileStoreForSearch_($searchSettings, $dataJobs)
    {
        $key = $this->_getFileStoreKeyForSearch($searchSettings, "");
        return writeJobsListDataToLocalJSONFile($key, $dataJobs, JOBLIST_TYPE_UNFILTERED, $dirKey = $this->_getDirKey_(), $searchDetails = $searchSettings);
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

    private function _getResultsForSearch_($searchDetails, $returnFailedSearchResults = false)
    {
        $tmpSearchJobs = $this->_getJobsfromFileStoreForSearch_($searchDetails, $returnFailedSearchResults);
        if (isset($tmpSearchJobs) && is_array($tmpSearchJobs))
            return $tmpSearchJobs;
        else
            return array();
    }


    private function _updateJobsDataForSearch_($searchDetails)
    {
        $GLOBALS['logger']->logSectionHeader(("Starting data pull for " . $this->siteName . "[" . $searchDetails['key']) . "]", \Scooper\C__NAPPTOPLEVEL__, \Scooper\C__SECTION_BEGIN__);
        $this->_logMemoryUsage_();
        $arrSearchJobList = null;
        $retLastEx = null;

        try {

            // get the url for the first page/items in the results
            if ($this->_checkInvalidURL_($searchDetails, $searchDetails['search_start_url']) == VALUE_NOT_SUPPORTED) return;

            // get all the results for all pages if we have them cached already
            $arrSearchJobList = $this->_getJobsfromFileStoreForSearch_($searchDetails, false);
            if (isset($arrSearchJobList)) {
                $this->_setSearchSuccessResult_($searchDetails, $success = true, $details = 'Using cached results for ' . countAssociativeArrayValues($arrSearchJobList) . ' matching, unfiltered jobs.', $arrSearchJobList);
                $GLOBALS['logger']->logLine("Using cached " . $this->siteName . "[" . $searchDetails['key'] . "]" . ": " . countJobRecords($arrSearchJobList) . " jobs found." . PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);

            } else {
                $GLOBALS['logger']->logLine(("Starting data pull for " . $this->siteName . "[" . $searchDetails['key'] . "] (no cache file found.)"), \Scooper\C__DISPLAY_ITEM_RESULT__);

                if ($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__) {
                    $arrSearchJobList = $this->_getMyJobsForSearchFromJobsAPI_($searchDetails);
                } elseif ($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__) {
                    $arrSearchJobList = $this->_getMyJobsForSearchFromWebpage_($searchDetails);
                } elseif ($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_CLIENTSIDE_WEBPAGE__) {
                    $arrSearchJobList = $this->_getMyJobsForSearchFromWebpage_($searchDetails);
                } else {
                    throw new ErrorException("Class " . get_class($this) . " does not have a valid setting for parser.  Cannot continue.");
                }

                $this->_setSearchSuccessResult_($searchDetails, $success = true, $details = 'Search found ' . countAssociativeArrayValues($arrSearchJobList) . ' matching, unfiltered jobs.', $arrSearchJobList);

                $this->_setJobsToFileStoreForSearch_($searchDetails, $arrSearchJobList);

            }

            // Let's do another check to make sure we got any listings at all for those that weren't
            // filtered by keyword.  If we returned zero jobs for any given city and no keyword filter
            // then we are likely broken somehow unexpectedly.   Make sure to error so that we note
            // it in the results & error notifications so that a developer can take a look.
            if($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED) && !$this->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED) && countJobRecords($arrSearchJobList) == 0)
            {
                $strError = "The search " . $searchDetails['key'] . " on " . $this->siteName . " downloaded 0 jobs yet we did not have any keyword filter is use.  Logging as a potential error since we should have had something returned. [URL=" . $searchDetails['search_start_url'] . "].  ";
                handleException(new Exception($strError), null, true);
            }

        } catch (Exception $ex) {

            //
            // BUGBUG:  This is a workaround to prevent errors from showing up
            // when no results are returned for a particular search for EmploymentGuide plugin only
            // See https://github.com/selner/jobs_scooper/issues/23 for more details on
            // this particular underlying problem
            //
            if ((isset($GLOBALS['JOBSITE_PLUGINS']['employmentguide']) && (strcasecmp($this->siteName, $GLOBALS['JOBSITE_PLUGINS']['employmentguide']['name']) == 0) ||
                    (isset($GLOBALS['JOBSITE_PLUGINS']['careerbuilder']) && strcasecmp($this->siteName, $GLOBALS['JOBSITE_PLUGINS']['careerbuilder']['name']) == 0) ||
                    (isset($GLOBALS['JOBSITE_PLUGINS']['ziprecruiter']) && strcasecmp($this->siteName, $GLOBALS['JOBSITE_PLUGINS']['ziprecruiter']['name']) == 0)) &&
                (substr_count($ex->getMessage(), "HTTP error #404") > 0))
            {
                $strError = $this->siteName . " plugin returned a 404 page for the search.  This is not an error; it means zero results found.";
                $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ITEM_DETAIL__);

                $this->_setSearchSuccessResult_($searchDetails, $success = true, $details = 'Search found no matching, unfiltered jobs.', array());

            }
            else
            {
                //
                // Not the known issue case, so log the error and re-throw the exception
                // if we should have thrown one
                //
                $strError = "Failed to download jobs from " . $this->siteName . " jobs for search '" . $searchDetails['key'] . "[URL=" . $searchDetails['search_start_url'] . "].  " . $ex->getMessage() . PHP_EOL . "Exception Details: " . $ex;
                $this->_setSearchResultError_($searchDetails, $strError, $ex, $arrSearchJobList, null);
                $this->_setJobsToFileStoreForSearch_($searchDetails, $arrSearchJobList);
                $retLastEx = new Exception($strError);
                handleException($retLastEx, null, false);
            }
        }
        finally
        {
            $GLOBALS['logger']->logSectionHeader(("Finished data pull for " . $this->siteName . "[" . $searchDetails['key'] . "]"), \Scooper\C__NAPPTOPLEVEL__, \Scooper\C__SECTION_END__);
        }




        if (!is_null($retLastEx))
        {
            throw $retLastEx;
        }

    }

    private function _checkInvalidURL_($details, $strURL)
    {
        if ($strURL == null) throw new ErrorException("Skipping " . $this->siteName . " search '" . $details['key'] . "' because a valid URL could not be set.");
        return $strURL;
        // if($strURL == VALUE_NOT_SUPPORTED) $GLOBALS['logger']->logLine("Skipping " . $this->siteName ." search '".$details['key']. "' because a valid URL could not be set.");
    }


    private function _setSearchResultError_(&$searchDetails, $err = "UNKNOWN Error.", $exception = null, $arrSearchedJobs = array(), $objSimpleHTMLResults = null)
    {
        $arrErrorFiles = array();
        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Setting error for search '" . $searchDetails['key'] . "' with error '" . $err . "'.", \Scooper\C__DISPLAY_ERROR__);

        if (!array_key_exists($searchDetails['key'], $GLOBALS['USERDATA']['search_results'])){
            throw new Exception("Error - Cannot Set Search Result for key " . $searchDetails['key'] . ".  Key does not exist in search results array.");
        }

        $this->_writeDebugFiles_($searchDetails, "ERROR", $arrSearchedJobs, $objSimpleHTMLResults);

        $this->_setSearchResult_($searchDetails, $success = false, $details = $err, $exception=$exception, $files = $arrErrorFiles);
    }




    protected function _writeDebugFiles_(&$searchDetails, $keyName = "UNKNOWN", $arrSearchedJobs = null, $objSimpleHTMLResults = null)
    {
        if(isDebug() === true)
        {
            if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Writing debug files for plugin " . $this->siteName ."'s search". $searchDetails['key'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

            $debugHTMLVarFile = $GLOBALS['USERDATA']['directories']['debug'] . "/" . getDefaultJobsOutputFileName($strFilePrefix = "_debug_htmlvar_". "-". $keyName, $strBase = $searchDetails['key'] , $strExt = "html", $delim = "-");
            $debugHTMLSelenFile = $GLOBALS['USERDATA']['directories']['debug'] . "/" . getDefaultJobsOutputFileName($strFilePrefix = "_debug_htmlselen_". "-". $keyName, $strBase = $searchDetails['key'] , $strExt = "html", $delim = "-");
            $debugSSFile = $GLOBALS['USERDATA']['directories']['debug'] . "/" . getDefaultJobsOutputFileName($strFilePrefix = "_debug_htmlselen_". "-". $keyName, $strBase = $searchDetails['key'] , $strExt = "png", $delim = "-");
            $debugCSVFile = substr($debugHTMLVarFile, 0, strlen($debugHTMLVarFile) - 4) . ".csv";

            if (!is_null($objSimpleHTMLResults)) {
                saveDomToFile($objSimpleHTMLResults, $debugHTMLVarFile);
                $arrErrorFiles[$debugHTMLVarFile] = $debugHTMLVarFile;
                if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Wrote page HTML variable out to " . $debugHTMLVarFile, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            }

            if($this->selenium != null)
            {
                $driver = $this->selenium->get_driver();
                $html = $driver->getPageSource();
                file_put_contents($debugHTMLSelenFile, $html);
                $arrErrorFiles[$debugHTMLSelenFile] = $debugHTMLSelenFile;
                if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Wrote page HTML from Selenium out to " . $debugHTMLSelenFile, \Scooper\C__DISPLAY_ITEM_DETAIL__);

                $driver->takeScreenshot($debugSSFile);
                $arrErrorFiles[$debugSSFile] = $debugSSFile;
                if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Saved screenshot from Selenium out to " . $debugSSFile, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            }

            if (!is_null($arrSearchedJobs) && is_array($arrSearchedJobs) && countJobRecords($arrSearchedJobs) > 0) {
                $this->writeJobsListToFile($debugCSVFile, $arrSearchedJobs);
                $arrErrorFiles[$debugCSVFile] = $debugCSVFile;
                if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Wrote results CSV data to " . $debugCSVFile, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            }
        }
    }



    private function _setSearchSuccessResult_(&$searchDetails, $success = null, $details = "UNKNOWN RESULT.", $arrSearchedJobs = null, $objSimpleHTMLResults = null)
    {
        if(isDebug() === true)
            $this->_writeDebugFiles_($searchDetails, "SUCCESS", $arrSearchedJobs, $objSimpleHTMLResults);
        $this->_setSearchResult_($searchDetails, $success, $details, null, $files = array());
    }


    private function _setSearchResult_(&$searchDetails, $success = null, $details = "UNKNOWN RESULT.", $exception = null, $files = array())
    {
        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Setting result value for search '" . $searchDetails['key'] . "' equal to " . ($success == 1 ? "true" : "false"). " with details '" . $details . "'.", \Scooper\C__DISPLAY_ITEM_DETAIL__);

        if (!array_key_exists($searchDetails['key'], $GLOBALS['USERDATA']['search_results']))
            throw new Exception("Error - Cannot Set Search Result for key " . $searchDetails['key'] . ".  Key does not exist in search results array.");
        $errFiles = $searchDetails['search_run_result']['error_files'];
        if(is_null($errFiles)) $errFiles = array();
        if(count($files) > 0) {
            foreach($files as $f)
                $errFiles[$f] = $f;
        }

        $line= null;
        $code = null;
        $msg = null;
        $file = null;

        if(!is_null($exception))
        {
            $line = $exception->getLine();
            $code = $exception->getCode();
            $msg = $exception->getMessage();
            $file = $exception->getFile();
        }
        $searchDetails['search_run_result'] = array(
            'success' => $success,
            'error_datetime' => new DateTime(),
            'error_details' => $details,
            'exception_code' => $code,
            'exception_message' => $msg,
            'exception_line' => $line,
            'exception_file' => $file,
            'error_files' => $errFiles
        );
        $GLOBALS['USERDATA']['search_results'][$searchDetails['key']]['search_run_result'] = $searchDetails['search_run_result'];
    }

    protected function _getMyJobsForSearchFromJobsAPI_(&$searchDetails)
    {
        $nItemCount = 0;

        $arrSearchReturnedJobs = [];
        $GLOBALS['logger']->logLine("Downloading count of " . $this->siteName . " jobs for search '" . $searchDetails['key'] . "'", \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $pageNumber = 1;
        $noMoreJobs = false;
        while($noMoreJobs != true)
        {
            $arrPageJobsList = [];
            $apiJobs = $this->getSearchJobsFromAPI($searchDetails, $pageNumber);
            if(is_null($apiJobs))
            {
                $GLOBALS['logger']->logLine("Warning: " . $this->siteName . "[" . $searchDetails['key'] . "] returned zero jobs from the API." . PHP_EOL, \Scooper\C__DISPLAY_WARNING__);
                return null;
            }

            foreach($apiJobs as $job)
            {
                $item = $this->getEmptyJobListingRecord();
                $item['job_title'] = $job->name;
                $item['job_id'] = $job->sourceId;
                if ($item['job_id'] == null)
                    $item['job_id'] = $job->url;

                if(strlen(trim($item['job_title'])) == 0 || strlen(trim($item['job_id'])) == 0)
                {
                    continue;
                }
                $item['location'] = $job->location;
                $item['company'] = $job->company;
                if ($job->datePosted != null)
                    $item['job_site_date'] = $job->datePosted->format('Y-m-d');
                $item['job_post_url'] = $job->url;

                $item = $this->normalizeJobItem($item);
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

        $GLOBALS['logger']->logLine($this->siteName . "[" . $searchDetails['key'] . "]" . ": " . $nItemCount . " jobs found." . PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
        return $arrSearchReturnedJobs;
    }


    protected function runJavaScriptSnippet($jscript= "", $wrap_in_func = true)
    {
        $driver = $this->getActiveWebdriver();

        if ($wrap_in_func === true)
        {
            $jscript = "function call_from_php() { " . $jscript . " }; call_from_php();";
        }

        $GLOBALS['logger']->logLine("Executing JavaScript in browser:  ". $jscript, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $ret = $driver->executeScript($jscript);

        sleep(5);

        return $ret;
    }

    protected function getSimpleHtmlDomFromSeleniumPage()
    {
        $objSimpleHTML = null;
        try
        {
            $html = $this->getActiveWebdriver()->getPageSource();
            $objSimpleHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);
        } catch (Exception $ex) {
            $strError = "Failed to get dynamic HTML via Selenium due to error:  " . $ex->getMessage();
            handleException(new Exception($strError), null, true);
        }
        return $objSimpleHTML;
    }

    private function _getMyJobsForSearchFromWebpage_(&$searchDetails)
    {

        $nItemCount = 1;
        $nPageCount = 1;
        $arrSearchReturnedJobs = null;
        $objSimpleHTML = null;

        $GLOBALS['logger']->logLine("Getting count of " . $this->siteName . " jobs for search '" . $searchDetails['key'] . "': " . $searchDetails['search_start_url'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

        try
        {
            if($this->isBitFlagSet(C__JOB_USE_SELENIUM))
            {
                try
                {
                    if(is_null($this->selenium))
                    {
                        $this->selenium = new SeleniumSession($this->additionalLoadDelaySeconds);
                    }
                    $html = $this->selenium->getPageHTML($searchDetails['search_start_url']);
                    $objSimpleHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);
                } catch (Exception $ex) {
                    $strError = "Failed to get dynamic HTML via Selenium due to error:  " . $ex->getMessage();
                    handleException(new Exception($strError), null, true);
                }
            }
            else
            {
                $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $searchDetails['search_start_url'], $this->secsPageTimeout, $referrer = $this->prevURL, $cookies = $this->prevCookies);
            }
            if(!$objSimpleHTML) { throw new ErrorException("Error:  unable to get SimpleHTML object for ".$searchDetails['search_start_url']); }

            $totalPagesCount = C__TOTAL_ITEMS_UNKNOWN__;
            $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__; // placeholder because we don't know how many are on the page
            if($this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) && $this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__))
            {
                switch($this->paginationType) {

                    case C__PAGINATION_INFSCROLLPAGE_NOCONTROL:
                    case C__PAGINATION_INFSCROLLPAGE_VIALOADMORE:
                    case C__PAGINATION_PAGE_VIA_NEXTBUTTON:
                    case C__PAGINATION_INFSCROLLPAGE_VIA_JS:
                    case C__PAGINATION_PAGE_VIA_CALLBACK:
                        $totalPagesCount = C__TOTAL_ITEMS_UNKNOWN__;
                        $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__;
                        break;

                    default:
                        // if we can't get a number of pages AND we can't get a number of items,
                        // we must assume there is, at most, only one page of results.
                        $totalPagesCount = 1;
                        $nTotalListings = $this->nJobListingsPerPage;
                        break;
                }
            } elseif(!$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) || !$this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__)) {

                //
                // If we are in debug mode, save the HTML we got back for the listing count page to disk so it is
                // easy for a developer to review it
                //
                if(isDebug() == true && !is_null($objSimpleHTML) && !is_null($objSimpleHTML->root))
                {
                    $this->_writeDebugFiles_($searchDetails, "parseTotalResultsCount", null, $objSimpleHTML->root);
                }

                $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML->root);
                $nTotalListings = intval(str_replace(",", "", $strTotalResults));
                if($nTotalListings == 0)
                {
                    $totalPagesCount = 0;
                }
                elseif($nTotalListings != C__TOTAL_ITEMS_UNKNOWN__)
                {
                    if ($nTotalListings > $this->nMaxJobsToReturn) {
                        $GLOBALS['logger']->logLine("Search '" . $searchDetails['key'] . "' returned more results than allowed.  Only retrieving the first " . $this->nMaxJobsToReturn . " of  " . $nTotalListings . " job listings.", \Scooper\C__DISPLAY_WARNING__);
                        $nTotalListings = $this->nMaxJobsToReturn;
                    }
                    $totalPagesCount = \Scooper\intceil($nTotalListings / $this->nJobListingsPerPage); // round up always
                    if ($totalPagesCount < 1) $totalPagesCount = 1;
                }
            }


            //
            // If this is just a test run to verify everything is functioning and all plugins are returning data,
            // then only bring back the first page and/or first 10 or so results to verify.  We don't need to bring
            // back hundreds of results to test things are running successfully.
            //
            if (isTestRun())
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
                $GLOBALS['logger']->logLine("No new job listings were found on " . $this->siteName . " for search '" . $searchDetails['key'] . "'.", \Scooper\C__DISPLAY_ITEM_START__);
                return array();
            }
            else
            {
                $nJobsFound = 0;
                $prevPageURL = "";
                
                $GLOBALS['logger']->logLine("Querying " . $this->siteName . " for " . $totalPagesCount . " pages with " . ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__ ? "an unknown number of" : $nTotalListings) . " jobs:  " . $searchDetails['search_start_url'], \Scooper\C__DISPLAY_ITEM_START__);

                $strURL = $searchDetails['search_start_url'];
                while ($nPageCount <= $totalPagesCount )
                {

                    $arrPageJobsList = null;

                    if($this->isBitFlagSet(C__JOB_USE_SELENIUM))
                    {
                        try
                        {   
                            switch(strtoupper($this->paginationType))
                            {

                                case C__PAGINATION_NONE:
                                    $totalPagesCount = 1;
                                    $this->selenium->loadPage($strURL);
                                    break;

                                case C__PAGINATION_PAGE_VIA_URL:
                                    $strURL = $this->getPageURLfromBaseFmt($searchDetails, $nPageCount, $nItemCount);
                                    if ($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED)
                                        return null;
                                    $this->selenium->loadPage($strURL);
                                    break;

                                case C__PAGINATION_INFSCROLLPAGE_VIALOADMORE:
                                    $this->selenium->loadPage($strURL);
                                    //
                                    // If we dont know how many pages to go down,
                                    // call the method to go down to the very end so we see the whole page
                                    // and whole results set
                                    //
                                    $this->goToEndOfResultsSetViaLoadMore($nTotalListings);
                                    $totalPagesCount = 1;
                                    break;

                                case C__PAGINATION_INFSCROLLPAGE_NOCONTROL:
                                    $this->selenium->loadPage($strURL);
                                    //
                                    // if we know how many pages to do do, call the page down method
                                    // until we get to the right number of pages
                                    //
                                    while ($nPageCount <= $totalPagesCount) {
                                        if (isDebug() == true && isset($GLOBALS['logger'])) {
                                            $GLOBALS['logger']->logLine("... getting infinite results page #" . $nPageCount . " of " . $totalPagesCount, \Scooper\C__DISPLAY_NORMAL__);
                                        }
                                        $this->moveDownOnePageInBrowser();
                                        $nPageCount = $nPageCount + 1;
                                    }
                                    $totalPagesCount = $nPageCount;
                                    break;

                                case C__PAGINATION_INFSCROLLPAGE_VIA_JS:    
                                    if(is_null($this->nextPageScript))
                                    {
                                        handleException(new Exception("Plugin " . $this->siteName . " is missing nextPageScript settings for the defined pagination type."), "", true);
    
                                    }
                                    $this->selenium->loadPage($strURL);
    
                                    if( $nPageCount > 1 && $nPageCount <= $totalPagesCount) {
                                        $this->runJavaScriptSnippet($this->nextPageScript, true);
                                        sleep($this->additionalLoadDelaySeconds + 1);
                                    }
                                break;
                                
                                case C__PAGINATION_PAGE_VIA_NEXTBUTTON:
                                    if(is_null($this->selectorMoreListings))
                                    {
                                        throw(new Exception("Plugin " . $this->siteName . " is missing selectorMoreListings setting for the defined pagination type."));
    
                                    }
                                    $this->selenium->loadPage($strURL);
    
                                    if( $nPageCount > 1 && ($totalPagesCount == C__TOTAL_ITEMS_UNKNOWN__ || $nPageCount <= $totalPagesCount)) {
                                        $ret = $this->goToNextPageOfResultsViaNextButton();
                                        if($ret == false)
                                            $totalPagesCount = $nPageCount;
                                    }
                                    break;
                                
                                case C__PAGINATION_PAGE_VIA_CALLBACK:
                                    if (!method_exists($this, 'takeNextPageAction')) {
                                        handleException(new Exception("Plugin " . $this->siteName . " is missing takeNextPageAction method definiton required for its pagination type."), "", true);
                                    }
    
                                    if($nPageCount > 1 && $nPageCount <= $totalPagesCount) {
                                        //
                                        // if we got a driver instance back, then we got a new page
                                        // otherwise we're out of results so end the loop here.
                                        //
                                        try {
                                            $this->takeNextPageAction($this->selenium->driver);
                                        } catch (Exception $ex) {
                                            handleException($ex, ("Failed to take nextPageAction on page " . $nPageCount . ".  Error:  %s"), true);
                                        }
                                    }
                                    break;
    
                                default:
                                handleException(null, "No pagination method defined for plugin " . $this->siteName, false);
                                    break;
                            }

                            $strURL = $this->selenium->driver->getCurrentURL();
                            $html = $this->selenium->driver->getPageSource();
                            $objSimpleHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);
                            //
                            // If we are in debug mode, save the HTML we got back for the listing count page to disk so it is
                            // easy for a develooper to review it
                            //
                            if(isDebug() && !is_null($objSimpleHTML) && !is_null($objSimpleHTML->root))
                            {
                                $this->_writeDebugFiles_($searchDetails, "page" . $nPageCount . "-loaded", null, $objSimpleHTML->root);
                            }


                        } catch (Exception $ex) {
                            handleException($ex, "Failed to get dynamic HTML via Selenium due to error:  %s", true);
                        }
                    }
                    else
                    {
                        $strURL = $this->getPageURLfromBaseFmt($searchDetails, $nPageCount, $nItemCount);
                        if ($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED)
                            return null;

                        $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL, $this->secsPageTimeout, $referrer = $this->prevURL, $cookies = $this->prevCookies);
                    }
                    if (!$objSimpleHTML) throw new ErrorException("Error:  unable to get SimpleHTML object for " . $strURL);

                    $GLOBALS['logger']->logLine("Getting jobs page # " . $nPageCount . " of " . $totalPagesCount ." from " . $strURL .".  Total listings loaded:  " . ($nItemCount == 1 ? 0 : $nItemCount) . "/" . $nTotalListings . ".", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    try
                    {

                        $arrPageJobsList = $this->parseJobsListForPage($objSimpleHTML);
                        if(!is_array($arrPageJobsList) )
                        {
                            // we likely hit a page where jobs started to be hidden.
                            // Go ahead and bail on the loop here
                            $strWarnHiddenListings = "Could not get all job results back from " . $this->siteName . " for this search starting on page " . $nPageCount . ".";
                            if ($nPageCount < $totalPagesCount)
                                $strWarnHiddenListings .= "  They likely have hidden the remaining " . ($totalPagesCount - $nPageCount) . " pages worth. ";

                            $GLOBALS['logger']->logLine($strWarnHiddenListings, \Scooper\C__DISPLAY_ITEM_START__);
                            $nPageCount = $totalPagesCount;
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

                            $GLOBALS['logger']->logLine("Loaded " . countAssociativeArrayValues($arrSearchReturnedJobs) . " of " . $nTotalListings . " job listings from " . $this->siteName, \Scooper\C__DISPLAY_NORMAL__);
                        }
                    } catch (Exception $ex) {
                        handleException($ex, ($this->siteName . " error: %s"), true);
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
                    $marginOfErrorAllowed = .05;
                    if ($nTotalListings > 0 && $nItemCount == 0) // We got zero listings but should have found some
                        $err = "Retrieved 0 of the expected " . $nTotalListings . " listings for " . $this->siteName . " (search = " . $searchDetails['key'] . ")";
                    elseif ($nItemCount < $this->nJobListingsPerPage && $nPageCount < $totalPagesCount)
                        $err = "Retrieved only " . $nItemCount . " of the " . $this->nJobListingsPerPage . " job listings on page " . $nPageCount . " for " . $this->siteName . " (search = " . $searchDetails['key'] . ")";
                    elseif ($nJobsFound < $nTotalListings * (1-$marginOfErrorAllowed) && $nPageCount == $totalPagesCount && !$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__))
                        $err = "Retrieved only " . $nJobsFound . " of the " . $nTotalListings . " listings that we expected for " . $this->siteName . " (search = " . $searchDetails['key'] . ")";
                    elseif ($nJobsFound > $nTotalListings * (1+$marginOfErrorAllowed) && $nPageCount == $totalPagesCount && !$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__)) {
                        $warnMsg = "Warning:  Downloaded " . ($nJobsFound - $nTotalListings) . " jobs more than the " . $nTotalListings . " expected for " . $this->siteName . " (search = " . $searchDetails['key'] . ")";
                        $GLOBALS['logger']->logLine($warnMsg, \Scooper\C__DISPLAY_WARNING__);
                    }

                    if (!is_null($err)) {
                        if ($this->isBitFlagSet(C__JOB_IGNORE_MISMATCHED_JOB_COUNTS) || $this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) === true)
                        {
                            $GLOBALS['logger']->logLine("Warning: " . $err, \Scooper\C__DISPLAY_WARNING__);
                        }
                        else {
                            $err = "Error: " . $err . "  Aborting job site plugin to prevent further errors.";
                            $GLOBALS['logger']->logLine($err, \Scooper\C__DISPLAY_ERROR__);
                            handleException(new Exception($err), null, true);
                        }
                    }

                    $nPageCount++;
                }

            }

            $GLOBALS['logger']->logLine($this->siteName . "[" . $searchDetails['key'] . "]" . ": " . $nJobsFound . " jobs found." . PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
            return $arrSearchReturnedJobs;

        } catch (Exception $ex) {
            $this->_setSearchResultError_($searchDetails, "Error: " . $ex->getMessage(), $ex, $arrSearchReturnedJobs, $objSimpleHTML);
            $this->_setJobsToFileStoreForSearch_($searchDetails, $arrSearchReturnedJobs);
            handleException($ex, null, true);
        }
        finally
        {
            // clean up memory
            if (!is_null($objSimpleHTML))
            {
                $objSimpleHTML->clear();
                unset($objSimpleHTML);
            }
        }
    }

    function setCompanyToSiteName($var)
    {
        return $this->siteName;
    }


    protected function getSearchJobsFromAPI($searchDetails) {   throw new \BadMethodCallException(sprintf("Not implemented method " . __METHOD__ . " called on class \"%s \".", __CLASS__)); }

    protected function normalizeJobItemWithoutJobID($arrItem)
    {

        $arrItem ['job_site_date'] = \Scooper\strScrub($arrItem['job_site_date'], REMOVE_EXTRA_WHITESPACE | LOWERCASE | HTML_DECODE );
        $dateVal = strtotime($arrItem ['job_site_date'], $now = time());
        if(!($dateVal === false))
        {
            $arrItem['job_site_date'] = date('Y-m-d', $dateVal);
        }


        $arrItem['job_id'] = \Scooper\strScrub($arrItem['company'], FOR_LOOKUP_VALUE_MATCHING) . \Scooper\strScrub($arrItem['job_title'], FOR_LOOKUP_VALUE_MATCHING). \Scooper\strScrub($arrItem['job_site_date'], FOR_LOOKUP_VALUE_MATCHING);

        return parent::normalizeJobItem($arrItem);

    }
}
