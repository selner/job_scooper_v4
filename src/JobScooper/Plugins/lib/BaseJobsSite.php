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

namespace JobScooper\Plugins\lib;



use JobScooper\DataAccess\UserSearchRun;
use JobScooper\Plugins\Interfaces\IJobSitePlugin;
use JobScooper\Manager\SeleniumManager;

const VALUE_NOT_SUPPORTED = -1;
const BASE_URL_TAG_LOCATION = "***LOCATION***";
const BASE_URL_TAG_KEYWORDS = "***KEYWORDS***";
use Exception;
use Psr\Log\LogLevel;

abstract class BaseJobsSite implements IJobSitePlugin
{
    protected $arrSearchesToReturn = null;
    protected $strBaseURLFormat = null;
    protected $siteBaseURL = null;
    protected $typeLocationSearchNeeded = null;
    protected $siteName = null;
    private $userObject = null;

    function __construct()
    {
        if(is_null($this->siteName) || strlen($this->siteName) == 0) {
            $classname = get_class($this);
            if (preg_match('/^Plugin(\w+)/', $classname, $matches) > 0) {
                $this->siteName = $matches[1];
            }
        }

       if (array_key_exists("JOBSITE_PLUGINS", $GLOBALS) && (array_key_exists(strtolower($this->siteName), $GLOBALS['JOBSITE_PLUGINS']))) {
            $plugin = $GLOBALS['JOBSITE_PLUGINS'][strtolower($this->siteName)];
            if (array_key_exists("other_settings", $plugin) && is_array($plugin['other_settings'])) {
                $keys = array_keys($plugin['other_settings']);
                foreach ($keys as $attrib_name) {
                    $this->$attrib_name = $plugin['other_settings'][$attrib_name];
                }
            }
        }

        $this->userObject = $GLOBALS['USERDATA']['configuration_settings']['user_details'];


        if (stristr($this->strBaseURLFormat, "***KEYWORDS***") == false)
            $this->additionalFlags[] = C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED;

        if (stristr($this->strBaseURLFormat, "***LOCATION***") == false)
            $this->additionalFlags[] = C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED;

        if (stristr($this->strBaseURLFormat, "***NUMBER_DAYS***") == false)
            $this->additionalFlags[] = C__JOB_DAYS_VALUE_NOTAPPLICABLE__;

        if (is_array($this->additionalFlags)) {
            foreach ($this->additionalFlags as $flag) {
                // If the flag is already set, don't try to set it again or it will
                // actually unset that flag incorrectly
                if (!$this->isBitFlagSet($flag)) {
                    $this->_flags_ = $this->_flags_ | $flag;
                }
            }
        }

        if ($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
            $this->nMaxJobsToReturn = $this->nMaxJobsToReturn * 3;
            $objJobSite = $this->getJobSiteObject();
        }

        if(!is_null($this->selectorMoreListings) && strlen($this->selectorMoreListings) > 0)
            $this->selectorMoreListings = preg_replace("/\\\?[\"']/", "'", $this->selectorMoreListings);

        if(substr($this->siteBaseURL, strlen($this->siteBaseURL)-1, strlen($this->siteBaseURL)) === "/")
            $this->siteBaseURL = substr($this->siteBaseURL, 0, strlen($this->siteBaseURL) - 1);
        print $this->siteBaseURL;

    }

    private function getJobSiteObject()
    {
        return $GLOBALS['JOBSITE_PLUGINS'][strtolower($this->siteName)]['jobsite_db_object'];
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
        $ret = isBitFlagSet($this->_flags_, $flagToCheck);
        if ($ret == $flagToCheck) {
            return true;
        }
        return false;
    }

    public function addSearches($arrSearches)
    {
        if ($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
            $firstKey = array_keys($arrSearches)[0];
            $arrSearches = array($firstKey => $arrSearches[$firstKey]);

            $objJobSite = $this->getJobSiteObject();
            if(!is_null($objJobSite))
            {
                if ($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED))
                    $objJobSite->setResultsFilterType("all-by-location");
                else
                    $objJobSite->setResultsFilterType("all-only");
                $objJobSite->save();
            }

        }

        foreach ($arrSearches as $searchDetails) {
            $this->_addSearch_($searchDetails);
        }
    }

    public function getMyJobsList()
    {
        return getAllUserMatchesNotNotified($GLOBALS['USERDATA']['configuration_settings']['app_run_id'], $this->siteName);
    }

    public function getUpdatedJobsForAllSearches()
    {
        $strIncludeKey = 'include_' . strtolower($this->siteName);
        $boolSearchSuccess = null;


        if (isset($GLOBALS['USERDATA']['OPTS'][$strIncludeKey]) && $GLOBALS['USERDATA']['OPTS'][$strIncludeKey] == 0) {
            LogLine($this->siteName . ": excluded for run. Skipping '" . count($this->arrSearchesToReturn) . "' site search(es).", \C__DISPLAY_ITEM_DETAIL__);
            return array();
        }

        if (count($this->arrSearchesToReturn) == 0) {
            LogLine($this->siteName . ": no searches set. Skipping...", \C__DISPLAY_ITEM_DETAIL__);
            return array();
        }

        try
        {
            /*
                Check to see if we should pull new job listings now.  If we ran too recently, this will skip the run
            */
            foreach ($this->arrSearchesToReturn as $search)
            {
                if($search->shouldRunNow())
                {
                    $this->currentSearchBeingRun = $search;

                    $search->setLastRunAt(time());
                    $search->save();

                    try {
                        // assert this search is actually for the job site supported by this plugin
                        assert(strcasecmp($search->getJobSiteKey(), cleanupSlugPart($this->siteName)) == 0);

                        if ($this->isBitFlagSet(C__JOB_USE_SELENIUM) && is_null($this->selenium)) {
                            try
                            {
                                $this->selenium = new SeleniumManager();
                            } catch (Exception $ex) {
                                handleException($ex, "Unable to start Selenium to get jobs for plugin '" . $this->siteName . "'", true);
                            }
                        }

                        $this->_updateJobsDataForSearch_($search);
                        $this->_addJobMatchesToUser($search);
                        $this->_setSearchResult_($search, true);
                    } catch (Exception $ex) {
                        $this->_setSearchResult_($search, false, new Exception("Unable to download jobs: " .strval($ex)));
                        throw $ex;
                    } finally {
                        $this->currentSearchBeingRun = null;
                    }
                }
                else
                {
                    LogLine("Skipping {$search->getUserSearchRunKey()} jobs download since it just ran recently.", \C__DISPLAY_ITEM_DETAIL__);
                        $this->_setSearchResult_($search, null);
                        $search->save();
                }
            }

            /*
             *  If this plugin is not user-filterable (aka no keywords filter), then any jobs from it can be applied
             *  to all users.  If that is the case, update user matches to assets any jobs that were loaded previously
             *  but the user is currently missing from their potential job matches.
             */
            $objJobSite = $this->getJobSiteObject();
            $pluginResultsType = $objJobSite->getResultsFilterType();
            if ((strcasecmp($pluginResultsType, "all-only") == 0) || (strcasecmp($pluginResultsType, "all-by-location") == 0))
            {
                try
                {
                    LogLine("Checking for missing " . $this->getName() . " jobs for user " . $this->userObject->getUserSlug() . ".", \C__DISPLAY_ITEM_DETAIL__);
                    $dataExistingUserJobMatchIds = \JobScooper\DataAccess\UserJobMatchQuery::create()
                        ->select("JobPostingId")
                        ->filterByUserSlug($this->userObject->getUserSlug())
                        ->useJobPostingQuery()
                            ->filterByJobSite($objJobSite->getJobSiteKey())
                        ->endUse()
                        ->find()
                        ->getData();

                    $queryAllJobsFromJobSite = \JobScooper\DataAccess\JobPostingQuery::create()
                        ->filterByJobSite($objJobSite->getJobSiteKey())
                        ->select("JobPostingId")
                        ->find()
                        ->getData();

                    $jobIdsToAddToUser = array_diff($queryAllJobsFromJobSite, $dataExistingUserJobMatchIds);

                    if(!is_null($jobIdsToAddToUser) && count($jobIdsToAddToUser) > 0) {
                        LogLine("Found " . count($jobIdsToAddToUser) . " " . $this->getName() . " jobs not yet assigned to user " . $this->userObject->getUserSlug() . ".", \C__DISPLAY_ITEM_DETAIL__);
                        $this->_addJobMatchIdsToUser($jobIdsToAddToUser);
                        LogLine("Successfully added " . count($jobIdsToAddToUser) . " " . $this->getName() . " jobs to user " . $this->userObject->getUserSlug() . ".", \C__DISPLAY_ITEM_DETAIL__);
                    }
                    else
                    {
                        LogLine("User " . $this->userObject->getUserSlug() . " had no missing previously loaded listings from ". $this->getName() . ".", \C__DISPLAY_ITEM_DETAIL__);
                    }
                } catch (Exception $ex) {
                    handleException($ex);
                }
            }

        } catch (Exception $ex) {
            throw $ex;
        } finally {
            try
            {
                if(!is_null($this->selenium)) {
                    $this->selenium->done();
                }
            } catch (Exception $ex) {
                LogLine("Unable to shutdown Selenium server successfully while closing down downloads for {$this->siteName}: " . $ex->getMessage(), C__DISPLAY_WARNING__);
            }
            finally
            {
                $this->selenium = null;
            }

            $this->currentSearchBeingRun = null;
        }


        return $this->getMyJobsList();
    }

    function getName()
    {
        $name = strtolower($this->siteName);
        if (is_null($name) || strlen($name) == 0) {
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
    protected $selenium = null;
    protected $nextPageScript = null;
    protected $selectorMoreListings = null;
    protected $nMaxJobsToReturn = C_JOB_MAX_RESULTS_PER_SEARCH;
    protected $currentSearchBeingRun = null;
    protected $arrSearchReturnedJobs = array();

    protected $detailsMyFileOut= "";
    protected $regex_link_job_id = null;
    protected $prevCookies = "";
    protected $prevURL = null;

    protected $strKeywordDelimiter = null;
    protected $additionalLoadDelaySeconds = 0;
    protected $_flags_ = null;
    protected $pluginResultsType = C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__;

    protected $countryCodes = array("US");

    function getLocationSettingType()
    {
        return $this->typeLocationSearchNeeded;
    }

    function getSupportedCountryCodes()
    {
        return $this->countryCodes;
    }

    protected function getActiveWebdriver()
    {
        if (!is_null($this->selenium)) {
            return $this->selenium->get_driver();
        } else
            throw new Exception("Error:  active webdriver for Selenium not found as expected.");
    }

    protected function getCombinedKeywordString($arrKeywordSet)
    {
        $arrKeywords = array();

        if (!is_array($arrKeywordSet)) {
            $arrKeywords[] = $arrKeywordSet[0];
        } else {
            $arrKeywords = $arrKeywordSet;
        }

        if ($this->isBitFlagSet(C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS)) {
            $arrKeywords = array_mapk(function ($k, $v) {
                return "\"{$v}\"";
            }, $arrKeywords);
        }

        $strRetCombinedKeywords = array_shift($arrKeywords);

        return $strRetCombinedKeywords;
    }

    function parseJobsListForPage($objSimpHTML)
    {
        throw new \BadMethodCallException(sprintf("Not implemented method  " . __METHOD__ . " called on class \"%s \".", __CLASS__));
    }

    protected function getLocationURLValue($searchDetails)
    {
        $strReturnLocation = VALUE_NOT_SUPPORTED;

        if ($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED)) {
            throw new \ErrorException($this->siteName . " does not support the ***LOCATION*** replacement value in a base URL.  Please review and change your base URL format to remove the location value.  Aborting all searches for " . $this->siteName, \C__DISPLAY_ERROR__);
        }

        // Did the user specify an override at the search level in the INI?
        if ($searchDetails != null && !is_null($searchDetails->getSearchParameter('location_user_specified_override')) && strlen($searchDetails->getSearchParameter('location_user_specified_override')) > 0) {
            $strReturnLocation = $searchDetails->getSearchParameter('location_user_specified_override');
        }
        elseif ($searchDetails != null && !is_null($searchDetails->getSearchParameter('location_search_value')) && strlen($searchDetails->getSearchParameter('location_search_value')) > 0)
        {
            $strReturnLocation = $searchDetails->getSearchParameter('location_search_value');
        }
        else
        {
            // No override, so let's see if the search settings have defined one for us
            $locTypeNeeded = $this->getLocationSettingType();
            if ($locTypeNeeded == null || $locTypeNeeded == "") {
                LogLine("Plugin for '" . $searchDetails->getJobSiteKey() . "' did not have the required location type of " . $locTypeNeeded . " set.   Skipping search '" . $searchDetails->getUserSearchRunKey() . ".", \C__DISPLAY_ITEM_DETAIL__);
                return $strReturnLocation;
            }

            if ($strReturnLocation == null || $strReturnLocation == VALUE_NOT_SUPPORTED) {
                LogLine("Plugin for '" . $searchDetails->getJobSiteKey() . "' did not have the required location type of " . $locTypeNeeded . " set.   Skipping search '" . $searchDetails->getUserSearchRunKey() . ".", \C__DISPLAY_ITEM_DETAIL__);
                return $strReturnLocation;
            }
        }

        if (!isValueURLEncoded($strReturnLocation)) {
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

        if (!$this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) && $nSubtermMatches > 0) {
            $strLocationValue = $searchDetails->getSearchParameter('location_search_value');
            if ($strLocationValue == VALUE_NOT_SUPPORTED) {
                LogLine("Failed to run search:  search is missing the required location type of " . $this->getLocationSettingType() . " set.  Skipping search '" . $searchDetails->getUserSearchRunKey() . ".", \C__DISPLAY_ITEM_DETAIL__);
                $strURL = VALUE_NOT_SUPPORTED;
            }
            else
            {
                $strURL = str_ireplace(BASE_URL_TAG_LOCATION, $this->getLocationURLValue($searchDetails), $strURL);
            }
        }

        if ($strURL == null) {
            throw new \ErrorException("Location value is required for " . $this->siteName . ", but was not set for the search '" . $searchDetails->getUserSearchRunKey() . "'." . " Aborting all searches for " . $this->siteName, \C__DISPLAY_ERROR__);
        }

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
    function getIDFromLink($regex_link_job_id, $url)
    {
        if (isset($regex_link_job_id)) {
            $fMatchedID = preg_match($regex_link_job_id, $url, $idMatches);
            if ($fMatchedID && count($idMatches) >= 1) {
                return $idMatches[count($idMatches) - 1];
            }
        }
        return "";
    }


    protected function _getBaseURLFormat_($searchDetails = null, $nPage = null, $nItem = null)
    {
        $strBaseURL = VALUE_NOT_SUPPORTED;

        if (!is_null($searchDetails->getSearchParameter('base_url_format'))) {
            $strBaseURL = $searchDetails->getSearchParameter('base_url_format');
        } elseif (!is_null($this->strBaseURLFormat) && strlen($this->strBaseURLFormat) > 0) {
            $strBaseURL = $this->strBaseURLFormat;
            $searchDetails->setSearchParameter('base_url_format', $strBaseURL);
        } elseif (!is_null($this->siteBaseURL) && strlen($this->siteBaseURL) > 0) {
            $strBaseURL = $this->siteBaseURL;
            $searchDetails->setSearchParameter('base_url_format', $strBaseURL);
        } else {
            throw new \ErrorException("Could not find base URL format for " . $this->siteName . ".  Aborting all searches for " . $this->siteName, \C__DISPLAY_ERROR__);
        }
        return $strBaseURL;
    }

    protected function getDaysURLValue($nDays = null)
    {
        return ($nDays == null || $nDays == "") ? 1 : $nDays;
    }

    protected function getPageURLValue($nPage)
    {
        return ($nPage == null || $nPage == "") ? "" : $nPage;
    }

    protected function getItemURLValue($nItem) {

        if($this->isBitFlagSet(C__JOB_ITEMCOUNT_STARTSATZERO__) && $nItem > 0)
        {
            $nItem = $nItem - 1;
        }

        return ($nItem == null || $nItem == "") ? 0 : $nItem;
    }

    function parseTotalResultsCount($objSimpHTML)
    {
        throw new \BadMethodCallException(sprintf("Not implemented method " . __METHOD__ . " called on class \"%s \".", __CLASS__));
    }


    protected function moveDownOnePageInBrowser()
    {

        // Neat trick written up by http://softwaretestutorials.blogspot.in/2016/09/how-to-perform-page-scrolling-with.html.
        $driver = $this->getActiveWebdriver();

        $driver->executeScript("window.scrollTo(0,document.body.scrollHeight);");

        sleep($this->additionalLoadDelaySeconds + 1);

    }


    protected function getKeywordURLValue($searchDetails)
    {
        if (!$this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
            assert($searchDetails->getSearchParameter('keywords_string_for_url') != VALUE_NOT_SUPPORTED);
            return $searchDetails->getSearchParameter('keywords_string_for_url');
        }
        return "";
    }

    protected function goToEndOfResultsSetViaPageDown($nTotalItems = null)
    {
        $this->moveDownOnePageInBrowser();
        $secs = $this->additionalLoadDelaySeconds * 1000;
        if ($secs <= 0)
            $secs = 1000;

        $js = "
            localStorage.setItem('startTime', Date.now());
            localStorage.setItem('prevHeight', 0);
            scroll = setTimeout(gotoPageBottom, 250);
            function getRunTime()
            {
                var startTime = localStorage.getItem('startTime');
                var endTime = Date.now();
                runtime = Math.floor((endTime-startTime)/(1000));
                return runtime;
            }

            function gotoPageBottom() 
            {
                runtime = getRunTime();
                prevHeight = localStorage.getItem('prevHeight');
                
                window.scrollTo(0,document.body.scrollHeight);
                if(prevHeight == null || (prevHeight < document.body.scrollHeight && runtime <= 60))
                {
                    localStorage.setItem('prevHeight', document.body.scrollHeight);
                    setTimeout(gotoPageBottom, " . $secs . ");
                }
                else
                {
                    console.log('Load more button no longer active; done paginating the results.');
                    console.log('Script needed a minimum of ' + runtime + ' seconds to load all the results.');
                    localStorage.removeItem('startTime');
                    localStorage.removeItem('prevHeight');

                }
            }  
        ";


        if (is_null($nTotalItems)) {
            $nTotalItems = $this->nMaxJobsToReturn;
        }

        if ($nTotalItems == C__TOTAL_ITEMS_UNKNOWN__) {
            $nSleepTimeToLoad = 30 + $this->additionalLoadDelaySeconds;
        } else {
            $nSleepTimeToLoad = ($nTotalItems / $this->nJobListingsPerPage) * $this->additionalLoadDelaySeconds;
        }

        LogLine("Sleeping for " . $nSleepTimeToLoad . " seconds to allow browser to page down through all the results", \C__DISPLAY_ITEM_DETAIL__);

        $this->runJavaScriptSnippet($js, false);

        sleep($nSleepTimeToLoad > 0 ? $nSleepTimeToLoad : 2);

        $this->moveDownOnePageInBrowser();

    }

    protected function goToEndOfResultsSetViaLoadMore($nTotalItems)
    {
        $this->moveDownOnePageInBrowser();
        $secs = $this->additionalLoadDelaySeconds * 1000;
        if ($secs <= 0)
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


        if (is_null($nTotalItems)) {
            $nTotalItems = $this->nMaxJobsToReturn;
        }

        if ($nTotalItems == C__TOTAL_ITEMS_UNKNOWN__) {
            $nSleepTimeToLoad = 30 + $this->additionalLoadDelaySeconds;
        } else {
            $nSleepTimeToLoad = ($nTotalItems / $this->nJobListingsPerPage) * $this->additionalLoadDelaySeconds;
        }

        LogLine("Sleeping for " . $nSleepTimeToLoad . " seconds to allow browser to page down through all the results", \C__DISPLAY_ITEM_DETAIL__);

        $this->runJavaScriptSnippet($js, false);

        sleep($nSleepTimeToLoad > 0 ? $nSleepTimeToLoad : 2);

        $this->moveDownOnePageInBrowser();

    }


    protected function goToNextPageOfResultsViaNextButton()
    {
        $secs = $this->additionalLoadDelaySeconds * 1000;
        if ($secs <= 0)
            $secs = 1000;

        if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Clicking button [" . $this->selectorMoreListings . "] to go to the next page of results...", \C__DISPLAY_ITEM_DETAIL__);

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


    private function _addSearch_($searchDetails)
    {
        assert($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || ($searchDetails->getSearchParameter('location_search_value') !== VALUE_NOT_SUPPORTED && strlen($searchDetails->getSearchParameter('location_search_value')) > 0));

        if ($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
            // null out any generalized keyword set values we previously had
            $searchDetails->setSearchParameter('keywords_array', null);
            $searchDetails->setSearchParameter('keywords_array_tokenized_array', null);
            $searchDetails->setSearchParameter('keywords_string_for_url', null);
        } else {
            $this->_setKeywordStringsForSearch_($searchDetails);
        }

        $this->_setStartingUrlForSearch_($searchDetails);

        $searchDetails->save();

        //
        // Add the search to the list of ones to run
        //
        $this->arrSearchesToReturn[$searchDetails->getUserSearchRunKey()] = $searchDetails;
        LogLine($this->siteName . ": added search (" . $searchDetails->getUserSearchRunKey() . ")", \C__DISPLAY_ITEM_DETAIL__);

    }

    private function _setKeywordStringsForSearch_($searchDetails)
    {
        // Does this search have a set of keywords specific to it that override
        // all the general settings?
        if (is_null($searchDetails->getSearchParameter('keyword_search_override')) && strlen($searchDetails->getSearchParameter('keyword_search_override')) > 0) {
            // keyword_search_override should only ever be a string value for any given search
            assert(!is_array($searchDetails->getSearchParameter('keyword_search_override')));

            // null out any generalized keyword set values we previously had
            $searchDetails->setSearchParameter('keywords_array', null);
            $searchDetails->setSearchParameter('keywords_string_for_url', null);

            //
            // Now take the override value and setup the keywords_array
            // and URL value for that particular string
            //
            $searchDetails->setSearchParameter('keywords_array', array($searchDetails->getSearchParameter('keyword_search_override')));
        }

        if (!is_null($searchDetails->getSearchParameter('keywords_array')))
        {
            $searchDetails->setSearchParameter('keywords_string_for_url', $this->_getCombinedKeywordStringForURL_($searchDetails->getSearchParameter('keywords_array')));
            $searchDetails->setSearchParameter('keywords_array', array());
        }

        // Lastly, check if we support keywords in the URL at all for this
        // plugin.  If not, remove any keywords_string_for_url value we'd set
        // and set it to "not supported"
        if ($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
            $searchDetails->setSearchParameter('keywords_string_for_url', VALUE_NOT_SUPPORTED);
        }
    }

    private function _getCombinedKeywordStringForURL_($arrKeywordSet)
    {
        $arrKeywords = array();

        if (!is_array($arrKeywordSet)) {
            $arrKeywords[] = $arrKeywordSet[0];
        } else {
            $arrKeywords = $arrKeywordSet;
        }

        $strRetCombinedKeywords = $this->getCombinedKeywordString($arrKeywords);

        if (!isValueURLEncoded($strRetCombinedKeywords)) {
            if ($this->isBitFlagSet(C__JOB_KEYWORD_PARAMETER_SPACES_RAW_ENCODE))
                $strRetCombinedKeywords = rawurlencode($strRetCombinedKeywords);
            else
                $strRetCombinedKeywords = urlencode($strRetCombinedKeywords);

        }

        if ($this->isBitFlagSet(C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES)) {
            $strRetCombinedKeywords = str_replace("%22", "-", $strRetCombinedKeywords);
            $strRetCombinedKeywords = str_replace("+", "-", $strRetCombinedKeywords);
        }

        return $strRetCombinedKeywords;
    }

    private function _setStartingUrlForSearch_($searchDetails)
    {

        $searchStartURL = $this->getPageURLfromBaseFmt($searchDetails, 1, 1);
        if (is_null($searchStartURL) || strlen($searchStartURL) == 0)
            $searchStartURL = $this->siteBaseURL;

        $searchDetails->setSearchParameter('search_start_url', $searchStartURL);
        LogLine("Setting start URL for " . $this->siteName . "[" . $searchDetails->getUserSearchRunKey() . "] to: " . PHP_EOL . $searchDetails->getSearchParameter('search_start_url'), \C__DISPLAY_ITEM_DETAIL__);

    }


    function setCompanyToSiteName($var)
    {
        return $this->siteName;
    }

    function combineTextAllNodes($var)
    {
        return combineTextAllNodes($var);
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

    private function _updateJobsDataForSearch_($searchDetails)
    {
        $ex = null;

        try {

            // get the url for the first page/items in the results
            if ($this->_checkInvalidURL_($searchDetails, $searchDetails->getSearchParameter('search_start_url')) == VALUE_NOT_SUPPORTED) return;

            LogLine(("Starting data pull for " . $this->siteName . "[" . $searchDetails->getUserSearchRunKey() . "]"), \C__DISPLAY_ITEM_RESULT__);

            if ($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__) {
                $this->_getMyJobsForSearchFromJobsAPI_($searchDetails);
            } elseif ($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__) {
                $this->_getMyJobsForSearchFromWebpage_($searchDetails);
            } elseif ($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_CLIENTSIDE_WEBPAGE__) {
                $this->_getMyJobsForSearchFromWebpage_($searchDetails);
            } else {
                throw new \ErrorException("Class " . get_class($this) . " does not have a valid setting for parser.  Cannot continue.");
            }

            // Let's do another check to make sure we got any listings at all for those that weren't
            // filtered by keyword.  If we returned zero jobs for any given city and no keyword filter
            // then we are likely broken somehow unexpectedly.   Make sure to error so that we note
            // it in the results & error notifications so that a developer can take a look.
            if ($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED) && !$this->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED) && countJobRecords($this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()]) == 0) {
                $strError = "The search " . $searchDetails->getUserSearchRunKey() . " on " . $this->siteName . " downloaded 0 jobs yet we did not have any keyword filter is use.  Logging as a potential error since we should have had something returned. [URL=" . $searchDetails->getSearchParameter('search_start_url') . "].  ";
                handleException(new Exception($strError), null, true);
            }

        } catch (Exception $ex) {

            //
            // BUGBUG:  This is a workaround to prevent errors from showing up
            // when no results are returned for a particular search for EmploymentGuide plugin only
            // See https://github.com/selner/jobs_scooper/issues/23 for more details on
            // this particular underlying problem
            //
            $jobsitekey = $this->getJobSiteObject()->getJobSiteKey();
            if (in_array($jobsitekey, array('employmentguide', 'careerbuilder', 'ziprecruiter')) &&
                (substr_count($ex->getMessage(), "HTTP error #404") > 0)
            ) {
                $strError = $this->siteName . " plugin returned a 404 page for the search.  This is not an error; it means zero results found.";
                LogLine($strError, \C__DISPLAY_ITEM_DETAIL__);

                $this->_setSearchResult_($searchDetails, $success = true);

            } else {
                //
                // Not the known issue case, so log the error and re-throw the exception
                // if we should have thrown one
                //
                $strError = "Failed to download jobs from " . $this->siteName . " jobs for search '" . $searchDetails->getUserSearchRunKey() . "[URL=" . $searchDetails->getSearchParameter('search_start_url') . "]. Exception Details: ";
                $this->_setSearchResult_($searchDetails, false, new Exception($strError . strval($ex)));
                handleException($ex, $strError, false);
            }
        } finally {
            $GLOBALS['logger']->logSectionHeader(("Finished data pull for " . $this->siteName . "[" . $searchDetails->getUserSearchRunKey() . "]"), \C__NAPPTOPLEVEL__, \C__SECTION_END__);
        }

        if (!is_null($ex)) {
            throw $ex;
        }

    }

    private function _checkInvalidURL_($details, $strURL)
    {
        if ($strURL == null) throw new \ErrorException("Skipping " . $this->siteName . " search '" . $details->getUserSearchKey() . "' because a valid URL could not be set.");
        return $strURL;
    }

    private function _setSearchResult_($searchDetails, $success = null, $except = null, $runWasSkipped=false)
    {
        if(!($searchDetails instanceof UserSearchRun))
            $searchDetails = $this->currentSearchBeingRun;

        if (!is_null($runWasSkipped) && is_bool($runWasSkipped) && $runWasSkipped === true)
        {
            $searchDetails->setRunResultCode("skipped");
        }
        elseif (!is_null($success) && is_bool($success)) {
            if($success === true)
                $searchDetails->setRunSucceeded();
            else {
                $searchDetails->failRunWithException($except);
            }
        }
        $searchDetails->save();
    }

    function saveDomToFile($htmlNode, $filepath)
    {

        $strHTML = strval($htmlNode);

        $htmlTmp = \SimpleHTMLHelper::str_get_html($strHTML);
        $htmlTmp->save($filepath);

        return $strHTML;

    }

    function getSimpleObjFromPathOrURL($filePath = "", $strURL = "", $optTimeout = null, $referrer = null, $cookies = null)
    {
        try {
            $objSimpleHTML = null;

            if (isDebug() == true) {

                $GLOBALS['logger']->logLine("URL        = " . $strURL, \C__DISPLAY_NORMAL__);
                $GLOBALS['logger']->logLine("Referrer   = " . $referrer, \C__DISPLAY_NORMAL__);
                $GLOBALS['logger']->logLine("Cookies    = " . $cookies, \C__DISPLAY_NORMAL__);
            }

            if (!$objSimpleHTML && ($filePath && strlen($filePath) > 0)) {
                $GLOBALS['logger']->logLine("Loading ALTERNATE results from " . $filePath, \C__DISPLAY_ITEM_START__);
                $objSimpleHTML = null;
                $GLOBALS['logger']->logLine("Loading HTML from " . $filePath, \C__DISPLAY_ITEM_DETAIL__);

                if (!file_exists($filePath) && !is_file($filePath)) return $objSimpleHTML;
                $fp = fopen($filePath, 'r');
                if (!$fp) return $objSimpleHTML;

                $strHTML = fread($fp, JOBS_SCOOPER_MAX_FILE_SIZE);
                $objSimpleHTML = \SimpleHTMLHelper::str_get_html($strHTML);
                fclose($fp);
            }


            if (!$objSimpleHTML && $strURL && strlen($strURL) > 0) {
                $class = new \CurlWrapper();
                if (isVerbose()) $class->setVerbose(true);

                $retObj = $class->cURL($strURL, $json = null, $action = 'GET', $content_type = null, $pagenum = null, $onbehalf = null, $fileUpload = null, $secsTimeout = $optTimeout, $cookies = $cookies, $referrer = $referrer);
                if (!is_null($retObj) && array_key_exists("output", $retObj) && strlen($retObj['output']) > 0) {
                    $objSimpleHTML = \SimpleHTMLHelper::str_get_html($retObj['output']);
                    $this->prevCookies = $retObj['cookies'];
                    $this->prevURL = $strURL;
                } else {
                    $options = array('http' => array('timeout' => 30, 'user_agent' => C__STR_USER_AGENT__));
                    $context = stream_context_create($options);
                    $objSimpleHTML = \SimpleHTMLHelper::file_get_html($strURL, false, $context);
                }
            }
            if(!$objSimpleHTML)
            {
                throw new \Exception("Unable to get SimpleHTMLDom object from " . strlen($filePath) > 0 ? $filePath : $strURL);
            }

            return $objSimpleHTML;
        }
        catch (Exception $ex)
        {
            handleException($ex, null, true);
        }

    }


    protected function _getMyJobsForSearchFromJobsAPI_($searchDetails)
    {
        $nItemCount = 0;

        LogLine("Downloading count of " . $this->siteName . " jobs for search '" . $searchDetails->getUserSearchRunKey() . "'", \C__DISPLAY_ITEM_DETAIL__);

        $pageNumber = 1;
        $noMoreJobs = false;
        while ($noMoreJobs != true) {
            $arrPageJobsList = [];
            $apiJobs = $this->getSearchJobsFromAPI($searchDetails);
            if (is_null($apiJobs)) {
                LogLine("Warning: " . $this->siteName . "[" . $searchDetails->getUserSearchRunKey() . "] returned zero jobs from the API." . PHP_EOL, \C__DISPLAY_WARNING__);
                return null;
            }

            foreach ($apiJobs as $job) {
                $item = getEmptyJobListingRecord();
                $item['Title'] = $job->name;
                $item['JobSitePostId'] = $job->sourceId;
                if ($item['JobSitePostId'] == null)
                    $item['JobSitePostId'] = $job->url;

                if (strlen(trim($item['Title'])) == 0 || strlen(trim($item['JobSitePostId'])) == 0) {
                    continue;
                }
                $item['LocationFromSource'] = $job->location;
                $item['Company'] = $job->company;
                if ($job->datePosted != null)
                    $item['PostedAt'] = $job->datePosted->format('Y-m-d');
                $item['Url'] = $job->url;

                $strCurrentJobIndex = cleanupSlugPart($this->siteName) . cleanupSlugPart($item['JobSitePostId']);
                $arrPageJobsList[$strCurrentJobIndex] = $item;
                $nItemCount += 1;
            }
            $this->saveSearchReturnedJobs($arrPageJobsList, $searchDetails);
            if (count($arrPageJobsList) < $this->nJobListingsPerPage) {
                $noMoreJobs = true;
            }
            $pageNumber++;
        }

        LogLine($this->siteName . "[" . $searchDetails->getUserSearchRunKey() . "]" . ": " . $nItemCount . " jobs found." . PHP_EOL, \C__DISPLAY_ITEM_RESULT__);

    }


    protected function runJavaScriptSnippet($jscript = "", $wrap_in_func = true)
    {
        $driver = $this->getActiveWebdriver();

        if ($wrap_in_func === true) {
            $jscript = "function call_from_php() { " . $jscript . " }; call_from_php();";
        }

        LogLine("Executing JavaScript in browser:  " . $jscript, \C__DISPLAY_ITEM_DETAIL__);

        $ret = $driver->executeScript($jscript);

        sleep(5);

        return $ret;
    }
    
    function cleanupJobItemFields($arrItem)
    {
        if(is_null($arrItem['JobSite']) || strlen($arrItem['JobSite']) == 0)
            $arrItem['JobSite'] = $this->siteName;

        $arrItem['JobSite'] = cleanupSlugPart($arrItem['JobSite']);

        $arrItem ['Url'] = trim($arrItem['Url']); // DO NOT LOWER, BREAKS URLS

        if (!is_null($arrItem['Url']) || strlen($arrItem['Url']) > 0) {
            $arrMatches = array();
            $matchedHTTP = preg_match(REXPR_MATCH_URL_DOMAIN, $arrItem['Url'], $arrMatches);
            if (!$matchedHTTP) {
                $sep = "";
                if (substr($arrItem['Url'], 0, 1) != "/")
                    $sep = "/";
                $arrItem['Url'] = $this->siteBaseURL . $sep . $arrItem['Url'];
            }
        } else {
            $arrItem['Url'] = "[UNKNOWN]";
        }

        if (is_null($arrItem['JobSitePostId']) || strlen($arrItem['JobSitePostId']) <= 0)
            $arrItem['JobSitePostId'] = $arrItem['Url'];

        $arrItem['JobSitePostId'] = preg_replace(REXPR_MATCH_URL_DOMAIN, "", $arrItem['JobSitePostId']);
        $arrItem ['JobSitePostId'] = strScrub($arrItem['JobSitePostId'], FOR_LOOKUP_VALUE_MATCHING);
        if (is_null($arrItem['JobSitePostId']) || strlen($arrItem['JobSitePostId']) == 0) {
            if (isset($this->regex_link_job_id)) {
                $arrItem['JobSitePostId'] = $this->getIDFromLink($this->regex_link_job_id, $arrItem['Url']);
            }
        }

        return $arrItem;

    }

    function saveJob($arrItem)
    {
        $arrJob = $this->cleanupJobItemFields($arrItem);
        $job = updateOrCreateJobPosting($arrJob);

        return $job;
    }

    function saveSearchReturnedJobs($arrJobList, $searchDetails)
    {
        $arrJobsBySitePostId = array_column($arrJobList, null, 'JobSitePostId');
        if (!array_key_exists($searchDetails->getUserSearchRunKey(), $this->arrSearchReturnedJobs))
            $this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()] = array();

        foreach (array_keys($arrJobsBySitePostId) as $JobSitePostId) {
            $job = $this->saveJob($arrJobsBySitePostId[$JobSitePostId]);
            $this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()][$job->getJobPostingId()] = $job;
        }
    }

    private function _addJobMatchIdsToUser($arrJobIds)
    {
        foreach ($arrJobIds as $jobId) {
            $newMatch = \JobScooper\DataAccess\UserJobMatchQuery::create()
                ->filterByUserSlug($this->userObject->getUserSlug())
                ->filterByJobPostingId($jobId)
                ->findOneOrCreate();

            $newMatch->setUserSlug($this->userObject->getUserSlug());
            $newMatch->setAppRunId($GLOBALS['USERDATA']['configuration_settings']['app_run_id']);
            $newMatch->save();
        }
    }

    private function _addJobMatchesToUser($searchDetails)
    {
        if(array_key_exists($searchDetails->getUserSearchRunKey(), $this->arrSearchReturnedJobs) && !is_null($this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()]) && is_array($this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()]))
        $this->_addJobMatchIdsToUser(array_keys($this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()]));
    }

    //
//        $arrReturnedJobIds = array_keys($this->arrSearchReturnedJobs);
//
//            $arrJobsBySitePostId = array_column($arrJobList, null, "JobSitePostId");
//            if(!array_key_exists($searchDetails->getUserSearchRunKey(), $this->arrSearchReturnedJobs))
//                $this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()] = array();
//
//            foreach (array_keys($arrJobsBySitePostId) as $JobSitePostId) {
//                $job = $this->saveJob($arrJobsBySitePostId[$JobSitePostId]);
//                $this->arrSearchReturnedJobs[$job->getJobPostingId()] = $job->getJobPostingId();
//            }
//
//            $newMatch = \JobScooper\DataAccess\UserJobMatchQuery::create()
//                ->filterByUserSlug($this->userObject->getUserSlug())
//                ->filterByJobPostingId($arrReturnedJobIds)
//                ->findOneOrCreate();
//
////            $newMatch->setJobPostingId($job->getJobPostingId());
//            $newMatch->setUserSlug($this->userObject->getUserSlug());
//            $newMatch->setAppRunId($GLOBALS['USERDATA']['configuration_settings']['app_run_id']);
//            $newMatch->save();
////            $this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()][$job->getKeySiteAndPostID()] = $job->getJobPostingId();

//        }
//    }


    function saveJobList($arrJobs)
    {
        $addedJobIds = array();
        foreach ($arrJobs as $job) {
            $savedJob = $this->saveJob($job);
            $addedJobIds[] = $savedJob->getJobPostingId();
        }

        return $addedJobIds;
    }

    protected function getJobsDbIds($arrJobs)
    {
        $arrIds = array_column($arrJobs, 'JobSitePostId', 'JobSitePostId');
        $queryData = \JobScooper\DataAccess\JobPostingQuery::create()
            ->select(array("JobPostingId", "JobSitePostId", "JobSite", "KeySiteAndPostID"))
            ->filterByJobSite($this->siteName)
            ->filterByJobSitePostId(array_values($arrIds))
            ->find();
        $jobResults = $queryData->toArray();

        return $jobResults;
    }


    protected function getSimpleHtmlDomFromSeleniumPage()
    {
        $objSimpleHTML = null;
        try {
            $html = $this->getActiveWebdriver()->getPageSource();
            $objSimpleHTML = \SimpleHTMLHelper::str_get_html($html);
        } catch (Exception $ex) {
            $strError = "Failed to get dynamic HTML via Selenium due to error:  " . $ex->getMessage();
            handleException(new Exception($strError), null, true);
        }
        return $objSimpleHTML;
    }


    private function _getMyJobsForSearchFromWebpage_($searchDetails)
    {
        try {
            $nItemCount = 1;
            $nPageCount = 1;
            $objSimpleHTML = null;

            LogLine("Getting count of " . $this->siteName . " jobs for search '" . $searchDetails->getUserSearchRunKey() . "': " . $searchDetails->getSearchParameter('search_start_url'), \C__DISPLAY_ITEM_DETAIL__);

            if ($this->isBitFlagSet(C__JOB_USE_SELENIUM)) {
                try {
                    if (is_null($this->selenium)) {
                        $this->selenium = new SeleniumManager($this->additionalLoadDelaySeconds);
                    }

                    if (method_exists($this, "doFirstPageLoad") && $nPageCount == 1)
                        $html = $this->doFirstPageLoad($searchDetails);
                    else
                        $html = $this->selenium->getPageHTML($searchDetails->getSearchParameter('search_start_url'));
                    $objSimpleHTML = \SimpleHTMLHelper::str_get_html($html);
                } catch (Exception $ex) {
                    $strError = "Failed to get dynamic HTML via Selenium due to error:  " . $ex->getMessage();
                    handleException(new Exception($strError), null, true);
                }
            } else {
                $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $searchDetails->getSearchParameter('search_start_url'), $this->secsPageTimeout, $referrer = $this->prevURL, $cookies = $this->prevCookies);
            }
            if (!$objSimpleHTML) {
                throw new \ErrorException("Error:  unable to get SimpleHTML object for " . $searchDetails->getSearchParameter('search_start_url'));
            }

            $totalPagesCount = C__TOTAL_ITEMS_UNKNOWN__;
            $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__; // placeholder because we don't know how many are on the page
            if ($this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) && $this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__)) {
                switch ($this->paginationType) {

                    case C__PAGINATION_INFSCROLLPAGE_NOCONTROL:
                    case C__PAGINATION_INFSCROLLPAGE_PAGEDOWN:
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
            }

            if (!$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) || !$this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__)) {
                $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML);
                $nTotalListings = intval(str_replace(",", "", $strTotalResults));
                if ($nTotalListings == 0) {
                    $totalPagesCount = 0;
                } elseif ($nTotalListings != C__TOTAL_ITEMS_UNKNOWN__) {
                    if ($nTotalListings > $this->nMaxJobsToReturn) {
                        LogLine("Search '" . $searchDetails->getUserSearchRunKey() . "' returned more results than allowed.  Only retrieving the first " . $this->nMaxJobsToReturn . " of  " . $nTotalListings . " job listings.", \C__DISPLAY_WARNING__);
                        $nTotalListings = $this->nMaxJobsToReturn;
                    }
                    $totalPagesCount = intceil($nTotalListings / $this->nJobListingsPerPage); // round up always
                    if ($totalPagesCount < 1) $totalPagesCount = 1;
                }
            }


            //
            // If this is just a test run to verify everything is functioning and all plugins are returning data,
            // then only bring back the first page and/or first 10 or so results to verify.  We don't need to bring
            // back hundreds of results to test things are running successfully.
            //
            if (isTestRun()) {
                $maxListings = $this->nJobListingsPerPage * 2;
                if ($nTotalListings > $maxListings) {
                    $nTotalListings = $maxListings;
                    $totalPagesCount = 2;
                }
            }


            if ($nTotalListings <= 0) {
                LogLine("No new job listings were found on " . $this->siteName . " for search '" . $searchDetails->getUserSearchRunKey() . "'.", \C__DISPLAY_ITEM_START__);
                return array();
            } else {
                $nJobsFound = 0;

                LogLine("Querying " . $this->siteName . " for " . $totalPagesCount . " pages with " . ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__ ? "an unknown number of" : $nTotalListings) . " jobs:  " . $searchDetails->getSearchParameter('search_start_url'), \C__DISPLAY_ITEM_START__);

                $strURL = $searchDetails->getSearchParameter('search_start_url');
                while ($nPageCount <= $totalPagesCount) {

                    $arrPageJobsList = null;

                    //
                    // First, if this is an infinite page or a single page of listings, we
                    // need to make the calls to load the full results set into the page HTML
                    // We do this only for certain pagination types (INFSCROLLPAGE)
                    //
                    if ($this->isBitFlagSet(C__JOB_USE_SELENIUM)) {
                        try {
                            switch (strtoupper($this->paginationType)) {

                                case C__PAGINATION_NONE:
                                    $totalPagesCount = 1;
                                    $this->selenium->loadPage($strURL);
                                    break;

                                case C__PAGINATION_INFSCROLLPAGE_PAGEDOWN:
                                    $this->selenium->loadPage($strURL);
                                    //
                                    // If we dont know how many pages to go down,
                                    // call the method to go down to the very end so we see the whole page
                                    // and whole results set
                                    //
                                    $this->goToEndOfResultsSetViaPageDown($nTotalListings);
                                    $totalPagesCount = 1;
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
                                        if (isDebug() == true) {
                                            LogLine("... getting infinite results page #" . $nPageCount . " of " . $totalPagesCount, \C__DISPLAY_NORMAL__);
                                        }
                                        $this->moveDownOnePageInBrowser();
                                        $nPageCount = $nPageCount + 1;
                                    }
                                    $totalPagesCount = $nPageCount;
                                    break;

                                case C__PAGINATION_INFSCROLLPAGE_VIA_JS:
                                    if (is_null($this->nextPageScript)) {
                                        handleException(new Exception("Plugin " . $this->siteName . " is missing nextPageScript settings for the defined pagination type."), "", true);

                                    }
                                    $this->selenium->loadPage($strURL);

                                    if ($nPageCount > 1 && $nPageCount <= $totalPagesCount) {
                                        $this->runJavaScriptSnippet($this->nextPageScript, true);
                                        sleep($this->additionalLoadDelaySeconds + 1);
                                    }
                                    break;
                            }

                            $strURL = $this->selenium->driver->getCurrentURL();
                            $html = $this->selenium->driver->getPageSource();
                            $objSimpleHTML = \SimpleHTMLHelper::str_get_html($html);

                        } catch (Exception $ex) {
                            handleException($ex, "Failed to get dynamic HTML via Selenium due to error:  %s", true);
                        }
                    } else {
                        $strURL = $this->getPageURLfromBaseFmt($searchDetails, $nPageCount, $nItemCount);
                        if ($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED)
                            return null;

                        $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL, $this->secsPageTimeout, $referrer = $this->prevURL, $cookies = $this->prevCookies);
                    }
                    if (!$objSimpleHTML) {
                        throw new \ErrorException("Error:  unable to get SimpleHTML object for " . $strURL);
                    }

                    LogLine("Getting jobs page # " . $nPageCount . " of " . $totalPagesCount . " from " . $strURL . ".  Total listings loaded:  " . ($nItemCount == 1 ? 0 : $nItemCount) . "/" . $nTotalListings . ".", \C__DISPLAY_ITEM_DETAIL__);
                    try {

                        $arrPageJobsList = $this->parseJobsListForPage($objSimpleHTML);
                        if (!is_array($arrPageJobsList)) {
                            // we likely hit a page where jobs started to be hidden.
                            // Go ahead and bail on the loop here
                            $strWarnHiddenListings = "Could not get all job results back from " . $this->siteName . " for this search starting on page " . $nPageCount . ".";
                            if ($nPageCount < $totalPagesCount)
                                $strWarnHiddenListings .= "  They likely have hidden the remaining " . ($totalPagesCount - $nPageCount) . " pages worth. ";

                            LogLine($strWarnHiddenListings, \C__DISPLAY_ITEM_START__);
                            $nPageCount = $totalPagesCount;
                        }

                        if (is_array($arrPageJobsList)) {
                            $this->saveSearchReturnedJobs($arrPageJobsList, $searchDetails);
                            $nJobsFound = count($this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()]);

                            if ($nItemCount == 1) {
                                $nItemCount = 0;
                            }
                            $nItemCount += ($nJobsFound < $this->nJobListingsPerPage) ? $nJobsFound : $this->nJobListingsPerPage;

                            // If we don't know the total number of listings we will get, we can guess that we've got them all
                            // if we did not get the max number of job listings from the last page.  Basically, if we couldn't
                            // fill up a page with our search, then they must not be that many listings avaialble.
                            //
                            if ($totalPagesCount > 1 && $nTotalListings == C__TOTAL_ITEMS_UNKNOWN__ && countAssociativeArrayValues($arrPageJobsList) < $this->nJobListingsPerPage) {
                                $totalPagesCount = $nPageCount;
                                $nTotalListings = countAssociativeArrayValues($this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()]);
                            }

                            LogLine("Loaded " . countAssociativeArrayValues($this->arrSearchReturnedJobs[$searchDetails->getUserSearchRunKey()]) . " of " . $nTotalListings . " job listings from " . $this->siteName, \C__DISPLAY_NORMAL__);
                        }
                    } catch (Exception $ex) {
                        handleException($ex, ($this->siteName . " error: %s"), true);
                    }

                    //
                    // Look check for plugin errors that are not caught.  If we have looped through one page of results,
                    // we should either have returned at least 1 listing of the total count OR if we have retrieved fewer
                    // listings than are expected on a page, then we should our page count should be the same as the last page.
                    //
                    // If either is not true, then we're likely in an error condition and about to go a bit wacky, possibly in a major loop.
                    // Throw an error for this search instead and move on.
                    //
                    $err = null;
                    $marginOfErrorAllowed = .05;
                    if ($nTotalListings > 0 && $nItemCount == 0) // We got zero listings but should have found some
                        $err = "Retrieved 0 of the expected " . $nTotalListings . " listings for " . $this->siteName . " (search = " . $searchDetails->getUserSearchRunKey() . ")";
                    elseif ($nItemCount < $this->nJobListingsPerPage && $nPageCount < $totalPagesCount)
                        $err = "Retrieved only " . $nItemCount . " of the " . $this->nJobListingsPerPage . " job listings on page " . $nPageCount . " for " . $this->siteName . " (search = " . $searchDetails->getUserSearchRunKey() . ")";
                    elseif ($nJobsFound < $nTotalListings * (1 - $marginOfErrorAllowed) && $nPageCount == $totalPagesCount && !$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__))
                        $err = "Retrieved only " . $nJobsFound . " of the " . $nTotalListings . " listings that we expected for " . $this->siteName . " (search = " . $searchDetails->getUserSearchRunKey() . ")";
                    elseif ($nJobsFound > $nTotalListings * (1 + $marginOfErrorAllowed) && $nPageCount == $totalPagesCount && !$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__)) {
                        $warnMsg = "Warning:  Downloaded " . ($nJobsFound - $nTotalListings) . " jobs more than the " . $nTotalListings . " expected for " . $this->siteName . " (search = " . $searchDetails->getUserSearchRunKey() . ")";
                        LogLine($warnMsg, \C__DISPLAY_WARNING__);
                    }

                    if (!is_null($err)) {
                        if ($this->isBitFlagSet(C__JOB_IGNORE_MISMATCHED_JOB_COUNTS) || $this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) === true) {
                            LogLine("Warning: " . $err, \C__DISPLAY_WARNING__);
                        } else {
                            $err = "Error: " . $err . "  Aborting job site plugin to prevent further errors.";
                            LogLine($err, \C__DISPLAY_ERROR__);
                            handleException(new Exception($err), null, true);
                        }
                    }

                    $nPageCount++;

                    //
                    // OK, we're done loading the results set from that page.  Now we need to
                    // move the browser session to the next page of results. (Unless we were on
                    // an infinite scroll page, if we were, then there isn't another page to load.)
                    //
                    if ($this->isBitFlagSet(C__JOB_USE_SELENIUM)) {
                        try {
                            switch (strtoupper($this->paginationType)) {
                                case C__PAGINATION_PAGE_VIA_URL:
                                    $strURL = $this->getPageURLfromBaseFmt($searchDetails, $nPageCount, $nItemCount);
                                    if ($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED)
                                        return null;
                                    $this->selenium->loadPage($strURL);
                                    break;

                                case C__PAGINATION_PAGE_VIA_NEXTBUTTON:
                                    if (is_null($this->selectorMoreListings)) {
                                        throw(new Exception("Plugin " . $this->siteName . " is missing selectorMoreListings setting for the defined pagination type."));

                                    }
                                    $this->selenium->loadPage($strURL);

                                    if ($nPageCount > 1 && ($totalPagesCount == C__TOTAL_ITEMS_UNKNOWN__ || $nPageCount <= $totalPagesCount)) {
                                        $ret = $this->goToNextPageOfResultsViaNextButton();
                                        if ($ret == false)
                                            $totalPagesCount = $nPageCount;
                                    }
                                    break;

                                case C__PAGINATION_PAGE_VIA_CALLBACK:
                                    if (!method_exists($this, 'takeNextPageAction')) {
                                        handleException(new Exception("Plugin " . $this->siteName . " is missing takeNextPageAction method definiton required for its pagination type."), "", true);
                                    }

                                    if ($nPageCount > 1 && $nPageCount <= $totalPagesCount) {
                                        //
                                        // if we got a driver instance back, then we got a new page
                                        // otherwise we're out of results so end the loop here.
                                        //
                                        try {
                                            $this->takeNextPageAction($this->getItemURLValue($nItemCount), $this->getPageURLValue($nPageCount));
                                            sleep($this->additionalLoadDelaySeconds + 2);
                                        } catch (Exception $ex) {
                                            handleException($ex, ("Failed to take nextPageAction on page " . $nPageCount . ".  Error:  %s"), true);
                                        }
                                    }
                                    break;

                            }

                        } catch (Exception $ex) {
                            handleException($ex, "Failed to get dynamic HTML via Selenium due to error:  %s", true);
                        }
                    }
                }

            }

            LogLine($this->siteName . "[" . $searchDetails->getUserSearchRunKey() . "]" . ": " . $nJobsFound . " jobs found." . PHP_EOL, \C__DISPLAY_ITEM_RESULT__);

        } catch (Exception $ex) {
            $this->_setSearchResult_($searchDetails, false, $ex);
            handleException($ex, null, true);
        } finally {
            // clean up memory
            if (!is_null($objSimpleHTML) && is_object($objSimpleHTML)) {
                $objSimpleHTML->clear();
                unset($objSimpleHTML);
            }
        }

        return null;
    }

    protected function getSearchJobsFromAPI($searchDetails)
    {
        throw new \BadMethodCallException(sprintf("Not implemented method " . __METHOD__ . " called on class \"%s \".", __CLASS__));
    }
}



