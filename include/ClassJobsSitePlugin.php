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
require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');

const VALUE_NOT_SUPPORTED = -1;
const BASE_URL_TAG_LOCATION = "***LOCATION***";
const BASE_URL_TAG_KEYWORDS = "***KEYWORDS***";

abstract class ClassJobsSitePlugin extends ClassJobsSitePluginCommon
{

    function __construct($strOutputDirectory = null)
    {
        if($strOutputDirectory != null)
        {
            $this->detailsMyFileOut = \Scooper\parseFilePath($strOutputDirectory, false);
        }

    }

    function __destruct()
    {

        //
        // Write out the interim data to file if we're debugging
        //
        if($this->is_OutputInterimFiles() == true) {
            if($this->arrLatestJobs != null)
            {
                $strOutPathWithName = $this->getOutputFileFullPath($this->siteName . "_");
                if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Writing ". $this->siteName." " .count($this->arrLatestJobs) ." job records to " . $strOutPathWithName . " for debugging (if needed).", \Scooper\C__DISPLAY_ITEM_START__); }
                $this->writeMyJobsListToFile($strOutPathWithName, false);
            }
        }

        if(isset($GLOBALS['selenium_sessionid']) && $GLOBALS['selenium_sessionid'] != -1)
        {
            $driver = RemoteWebDriver::createBySessionID($GLOBALS['selenium_sessionid']);
            $driver->quit();
            $GLOBALS['selenium_sessionid'] = -1;

            $sessions = RemoteWebDriver::getAllSessions();
            foreach($sessions as $sess)
            {
                $driver = RemoteWebDriver::createBySessionID($sess);
                $driver->quit();
            }
        }

        if($this->isBitFlagSet(C__JOB_USE_SELENIUM) && isset($GLOBALS['selenium_started']) && $GLOBALS['selenium_started'] = true)
        {
            if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Sending server shutdown call to Selenium server...", \Scooper\C__DISPLAY_ITEM_RESULT__); }
            $cmd = "curl \"http://localhost:4444/selenium-server/driver?cmd=shutDownSeleniumServer\" >/dev/null &";
            exec($cmd);
            $GLOBALS['selenium_started'] = false;
        }

    }

    function parseJobsListForPageBase($objSimpHTML) {
         $retJobs = $this->parseJobsListForPage($objSimpHTML);
        $retJobs = $this->normalizeJobList($retJobs);

        return $retJobs;

    } // returns an array of jobs

    function parseTotalResultsCount($objSimpHTML) { return VALUE_NOT_SUPPORTED; } // returns an array of jobs
    function parseJobsListForPage($objSimpHTML) { return VALUE_NOT_SUPPORTED; } // returns an array of jobs


    function addSearches($arrSearches)
    {
        if(!is_array($arrSearches[0])) { $arrSearches[] = $arrSearches; }

        foreach($arrSearches as $searchDetails)
        {
            $this->_addSearch_($searchDetails);

        }
    }

    function getLocationValueForLocationSetting($searchDetails)
    {
        $strReturnLocation = VALUE_NOT_SUPPORTED;

        if(isset($searchDetails['location_user_specified_override']) && strlen($searchDetails['location_user_specified_override']) > 0)
        {
            $strReturnLocation = $searchDetails['location_user_specified_override'];
        }
        else
        {
            $locTypeNeeded = $this->getLocationSettingType();
            if(isset($searchDetails['location_set']) && count($searchDetails['location_set']) > 0 && isset($searchDetails['location_set'][$locTypeNeeded]))
            {
                $strReturnLocation = $searchDetails['location_set'][$locTypeNeeded];
            }
        }
        if(!$this->_isValueURLEncoded_($strReturnLocation)) { $strReturnLocation = urlencode($strReturnLocation); }

        if($this->isBitFlagSet(C__JOB_LOCATION_REQUIRES_LOWERCASE))
        {
            $strReturnLocation = strtolower($strReturnLocation);
        }
        return $strReturnLocation;
    }

    function getJobsForAllSearches()
    {

        $strIncludeKey = 'include_'.strtolower($this->siteName);

        if(isset($GLOBALS['OPTS'][$strIncludeKey]) && $GLOBALS['OPTS'][$strIncludeKey] == 0)
        {
            $GLOBALS['logger']->logLine($this->siteName . ": excluded for run. Skipping '" . count($this->arrSearchesToReturn). "' site search(es).", \Scooper\C__DISPLAY_ITEM_START__);
            return;
        }

        if(count($this->arrSearchesToReturn) == 0)
        {
            $GLOBALS['logger']->logLine($this->siteName . ": no searches set. Skipping...", \Scooper\C__DISPLAY_ITEM_START__);
            return;
        }

        $this->_collapseSearchesIfPossible_();


        foreach($this->arrSearchesToReturn as $search)
        {
            // assert this search is actually for the job site supported by this plugin
            assert(strcasecmp(strtolower($search['site_name']), strtolower($this->siteName)) == 0);

            $this->_getJobsForSearchByType_($search);
        }
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
    function getName() { return $this->siteName; }
    function getMyJobsList() { return $this->arrLatestJobs; }
    function getLocationSettingType() { return $this->typeLocationSearchNeeded; }
    function setLocationValue($locVal) { $this->locationValue = $locVal; }
    function getLocationValue() { return $this->locationValue; }

    function getMyOutputFileFullPath($strFilePrefix = "")
    {
        return parent::getOutputFileFullPath($this->siteName . "_" . $strFilePrefix, "jobs", "csv");
    }

    function markMyJobsList_withAutoItems()
    {
        $this->markJobsList_withAutoItems($this->arrLatestJobs, $this->siteName);
    }




    /**
     * Write this class instance's list of jobs to an output CSV file.  Always rights
     * the full unfiltered list.
     *
     *
     * @param  string $strOutFilePath The file to output the jobs list to
     * @param  Array $arrMyRecordsToInclude An array of optional job records to combine into the file output CSV
     * @return string $strOutFilePath The file the jobs was written to or null if failed.
     */
    function writeMyJobsListToFile($strOutFilePath = null)
    {
        return $this->writeJobsListToFile($strOutFilePath, $this->arrLatestJobs, true, false, $this->siteName, "CSV");
    }



    function isBitFlagSet($flagToCheck)
    {
        $ret = \Scooper\isBitFlagSet($this->flagSettings, $flagToCheck);
        if($ret == $flagToCheck) { return true; }
        return false;
    }



    function getMySearches()
    {
        return $this->arrSearchesToReturn;
    }

    function isJobListingMine($var)
    {
        if(substr_count($var['job_site'], strtolower($this->siteName)) > 0)
        {
            return true;
        }

        return false;
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


    protected $siteName = 'NAME-NOT-SET';
    protected $arrLatestJobs = null;
    protected $arrSearchesToReturn = null;
    protected $nJobListingsPerPage = 20;
    protected $flagSettings = null;
    protected $secsPageTimeout = null;

    protected $strFilePath_HTMLFileDownloadScript = null;
    protected $strBaseURLFormat = null;
    protected $typeLocationSearchNeeded = null;
    protected $locationValue = null;
    protected $strKeywordDelimiter = null;
    protected $strTitleOnlySearchKeywordFormat = null;
    protected $classToCheckExists = null;
    protected $cookieNamesToSaveAndResend = Array();

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
            $GLOBALS['logger']->logLine($this->siteName . ": added search (" . $searchDetails['name'] . ")", \Scooper\C__DISPLAY_ITEM_DETAIL__);
        }

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
            if(strlen($curSearch['base_url_format']) > 0 || strlen($curSearch['keyword_search_override']) > 0 || strlen($curSearch['location_user_specified_override']) > 0)
            {
                $arrCollapsedSearches[] = $curSearch;
            }
            elseif($curSearch['location_set'] != $arrSearchesLeftToCollapse[0]['location_set'])
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
            if(strlen($search['keyword_search_override']) > 0 || strlen($search['location_user_specified_override']) > 0)
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
                    $this->_finalizeSearch_($tempSearch);
                }
                else
                {
                    // Verify the user settings for keyword match type are the same.  If they are,
                    // we can combine this search into the collapsed one.
                    //
                    if(\Scooper\isBitFlagSet($searchCollapsedDetails['user_setting_flags'], $search['user_setting_flags']))
                    {
                        $searchCollapsedDetails['name'] .= " and " . $search['name'];
                        $searchCollapsedDetails['keyword_set'] = \Scooper\my_merge_add_new_keys(array_values($searchCollapsedDetails['keyword_set']), array_values($search['keyword_set']));

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
    //  Keyword Search Related Functions
    //
    //
    //
    //************************************************************************
    private function _setKeywordStringsForSearch_(&$searchDetails)
    {

//        'keyword_search_override' => null,
//            'keywords_string_for_url' => null,
//            'keyword_set' => null,
//            'user_setting_flags' => null,


        // Does this search have a set of keywords specific to it that override
        // all the general settings?
        if(isset($searchDetails['keyword_search_override']) && strlen($searchDetails['keyword_search_override']) > 0)
        {
            // keyword_search_override should only ever be a string value for any given search
            assert(!is_array($searchDetails['keyword_search_override']));

            // null out any generalized keyword set values we previously had
            $searchDetails['keyword_set'] = null;
            $searchDetails['keywords_string_for_url'] = null;

            //
            // Now take the override value and setup the keyword_set
            // and URL value for that particular string
            //
            $searchDetails['keyword_set'] = array($searchDetails['keyword_search_override']);
        }

        if(isset($searchDetails['keyword_set']))
        {
            assert(is_array($searchDetails['keyword_set']));

            $searchDetails['keywords_string_for_url'] = $this->getCombinedKeywordStringForURL($searchDetails['keyword_set']);
        }

        // Lastly, check if we support keywords in the URL at all for this
        // plugin.  If not, remove any keywords_string_for_url value we'd set
        // and set it to "not supported"
        if($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
        {
            $searchDetails['keywords_string_for_url'] = VALUE_NOT_SUPPORTED;
        }
    }




    protected function getCombinedKeywordStringForURL($arrKeywordSet)
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

        if(($this->isBitFlagSet(C__JOB_KEYWORD_MULTIPLE_TERMS_SUPPORTED)) && count($arrKeywords) > 1)
        {
            if($this->strKeywordDelimiter == null)
            {
                throw new ErrorException($this->siteName . " supports multiple keyword terms, but has not set the \$strKeywordDelimiter value in " .get_class($this). ". Aborting search because cannot create the URL.");
            }

            foreach($arrKeywords as $kywd)
            {
                $newKywd = $kywd;
                if($this->isBitFlagSet(C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS))
                {
                    $newKywd = '"' . $newKywd .'"';
                }

                if($strRetCombinedKeywords == VALUE_NOT_SUPPORTED)
                {
                    $strRetCombinedKeywords = $newKywd;
                }
                else
                {
                    $strRetCombinedKeywords .= " " . $this->strKeywordDelimiter . " " . $newKywd;
                }
            }
        }
        else
        {
            $strRetCombinedKeywords = $arrKeywords[0];
            if($this->isBitFlagSet(C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS))
            {
                $strRetCombinedKeywords = '"' . $strRetCombinedKeywords .'"';
            }
        }

        if($this->isBitFlagSet(C__JOB_KEYWORD_MULTIPLE_TERMS_SUPPORTED) && $this->strTitleOnlySearchKeywordFormat != null && strlen($this->strTitleOnlySearchKeywordFormat) > 0)
        {
            $strRetCombinedKeywords = sprintf($this->strTitleOnlySearchKeywordFormat, $strRetCombinedKeywords);
        }

        if(!$this->_isValueURLEncoded_($strRetCombinedKeywords)) { $strRetCombinedKeywords = urlencode($strRetCombinedKeywords); }

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



    private function _getScrubbedKeywordSet_($arrKeywordSet)
    {
        $arrReturnKeywords = array();

        foreach($arrKeywordSet as $term)
        {
            $strAddTerm = \Scooper\strScrub($term, FOR_LOOKUP_VALUE_MATCHING);

            if(strlen($strAddTerm) > 0) $arrReturnKeywords[] = $strAddTerm;
        }

        return $arrReturnKeywords;
    }






    //************************************************************************
    //
    //
    //
    //  Location Search Related Functions
    //
    //
    //
    //************************************************************************

    private function _setLocationValueForSearch_(&$searchDetails)
    {
        $searchDetails['location_search_value'] = VALUE_NOT_SUPPORTED;

        if ($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || $this->isBitFlagSet(C__JOB_BASE_URL_FORMAT_REQUIRED))
        {
            $searchDetails['location_search_value'] = VALUE_NOT_SUPPORTED;
        }
        elseif(isset($searchDetails['location_user_specified_override']) && strlen($searchDetails['location_user_specified_override']) > 0)
        {
            $searchDetails['location_search_value'] = $searchDetails['location_user_specified_override'];
        }
        elseif(isset($searchDetails['location_set']) && is_array($searchDetails['location_set']) )
        {
            $locTypeNeeded = $this->getLocationSettingType();
            if(isset($searchDetails['location_set']) && count($searchDetails['location_set']) > 0 && isset($searchDetails['location_set'][$locTypeNeeded]))
            {
                $searchDetails['location_search_value'] = $searchDetails['location_set'][$locTypeNeeded];
            }
        }

        if(!$this->_isValueURLEncoded_($searchDetails['location_search_value'])) { $searchDetails['location_search_value'] = urlencode($searchDetails['location_search_value']); }

        if($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED))
        {
            $searchDetails['location_search_value']= strtolower($searchDetails['location_search_value']);
        }
    }




    //************************************************************************
    //
    //
    //
    //  Job Download Functions
    //
    //
    //
    //************************************************************************


    //************************************************************************
    //
    //
    //
    //  Keyword Search Related Functions
    //
    //
    //
    //************************************************************************
    protected function _finalizeSearch_(&$searchDetails)
    {

        if ($searchDetails['key'] == "") {
            $searchDetails['key'] = \Scooper\strScrub($searchDetails['site_name'], FOR_LOOKUP_VALUE_MATCHING) . "-" . \Scooper\strScrub($searchDetails['name'], FOR_LOOKUP_VALUE_MATCHING);
        }

        $this->_setKeywordStringsForSearch_($searchDetails);
        $this->_setLocationValueForSearch_($searchDetails);

    }



    private function _getJobsForSearchByType_($searchDetails, $nAttemptNumber = 0)
    {
        $GLOBALS['logger']->logLine($this->siteName . ": getting jobs for search(" . $searchDetails['name'] .")", \Scooper\C__DISPLAY_ITEM_START__);

        try {
            if($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_XML__))
            {
                $this->_getMyJobsForSearchFromXML_($searchDetails);
            }
            elseif($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_HTML_FILE__))
            {
                $this->_getMyJobsFromHTMLFiles_($searchDetails);
            }
            elseif($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_WEBPAGE__))
            {
                $this->_getMyJobsForSearchFromWebpage_($searchDetails);
            }
            else
            {
                throw new ErrorException("Class ". get_class($this) . " does not have a valid setting for parser.  Cannot continue.");
            }

        } catch (Exception $ex) {

            //
            // BUGBUG:  This is a workaround to prevent errors from showing up
            // when no results are returned for a particular search for EmploymentGuide plugin only
            // See https://github.com/selner/jobs_scooper/issues/23 for more details on
            // this particular underlying problem
            //
            $strErr = $ex->getMessage();
            if((isset($GLOBALS['DATA']['site_plugins']['employmentguide']) && (strcasecmp($this->siteName, $GLOBALS['DATA']['site_plugins']['employmentguide']['name']) == 0)||
                 (isset($GLOBALS['DATA']['site_plugins']['careerbuilder']) && strcasecmp($this->siteName, $GLOBALS['DATA']['site_plugins']['careerbuilder']['name']) == 0) ||
                (isset($GLOBALS['DATA']['site_plugins']['ziprecruiter']) && strcasecmp($this->siteName, $GLOBALS['DATA']['site_plugins']['ziprecruiter']['name']) == 0)) &&
                (substr_count($strErr, "HTTP error #404") > 0))
            {
                $strError = $this->siteName . " plugin returned a 404 page for the search.  This is not an error; it means zero results found." ;
                $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            }
            else
            {
                //
                // Not the known issue case, so log the error and re-throw the exception
                // if we should have thrown one
                //


                $strError = "Failed to download jobs from " . $this->siteName ." jobs for search '".$searchDetails['name']. "[URL=".$searchDetails['base_url_format']. "].  ".$ex->getMessage();
                //
                // Sometimes the site just returns a timeout on the request.  if this is the first attempt,
                // delay a bit then try it once more before failing.
                //
                if($nAttemptNumber < 1)
                {
                    $strError .= " Retrying search...";
                    $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_WARNING__);
                    // delay for 15 seconds
                    sleep(15);

                    // retry the request
                    $this->_getJobsForSearchByType_($searchDetails, ($nAttemptNumber+1));
                }
                else
                {
                    $strError .= " Search failed twice.  Skipping search.";
                    $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                    if(isDebug()) { throw new ErrorException( $strError); }
                }
            }
        }
    }

    private function _getJobsDataForCachedURL_($strURL)
    {
        $ret = null;

        $plugin = $GLOBALS['DATA']['site_plugins'][strtolower($this->siteName)];
        if(isset($plugin['cached_jobs']) && isset($plugin['cached_jobs'][$strURL]) &&
            strcasecmp($strURL, $plugin['cached_jobs'][$strURL]['url']) == 0)
        {
            return $plugin['cached_jobs'][$strURL]['object'];
        }

        return null;
    }

    private function _cacheJobsForURL($strURL, $dataJobs, $nJobCount = null)
    {
        $arrJobData = array();
        $arrJobData['object'] = \Scooper\array_copy($dataJobs);
        $arrJobData['url'] = $strURL;

        if(isset($dataJobs) && isset($strURL))
        {
            $GLOBALS['DATA']['site_plugins'][strtolower($this->siteName)]['cached_jobs'][$strURL] = $arrJobData;
        }
    }

    private function getJobsFromMicroData($objSimpleHTML)
    {
        $config  = array('html' => (string)$objSimpleHTML);
        $obj = new linclark\MicrodataPHP\MicrodataPhp($config);
        $micro = $obj->obj();
        $ret = null;

        if($micro && $micro->items && count($micro->items) > 0)
        {
            foreach($micro->items as $mditem)
            {
                if (isset($mditem->type) && strcasecmp($mditem->type[0], "https://schema.org/JobPosting") == 0) {

                    $item = $this->getEmptyJobListingRecord();

                    $item['job_title'] = $mditem->properties["title"][0];
                    if(isset($mditem->properties["url"]))
                        $item['job_post_url'] = $mditem->properties["url"][0];
                    elseif(isset($mditem->properties["mainEntityOfPage"]))
                        $item['job_post_url'] = $mditem->properties["mainEntityOfPage"][0];
                    if (isset($mditem->properties['hiringOrganization']))
                        $item['company'] = $mditem->properties['hiringOrganization'][0]->properties['name'][0];
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

                    if (isset($mditem->properties['datePosted']))
                        $item['job_site_date'] = $mditem->properties['datePosted'][0];
                    //                    $item['industry'] = $mditem->properties["industry"][0];
                    //                    $item['employmentType'] = $mditem->properties["employmentType"][0];
                    //                    $item['brief'] = $mditem->properties["description"][0];

                    $item['job_site'] = $this->siteName;

                    if(isset($this->regex_link_job_id))
                    {
                        $item['job_id'] = $this->getIDFromLink($this->regex_link_job_id, $item['job_post_url']);

                    }
                    else
                    {
                        $item['job_id'] = $item['job_site'] . "_" . preg_replace('/[\s\W]+/', '', $item['job_post_url']);
                    }


                    $ret[] = $this->normalizeItem($item);
                }
            }

            return $ret;
        }

    }


    function getIDFromLink($regex_link_job_id, $url)
    {
        if(isset($regex_link_job_id))
        {
            $fMatchedID = preg_match($regex_link_job_id, $url, $idMatches);
            if($fMatchedID && count($idMatches) > 1)
            {
                return $idMatches[count($idMatches)-1];
            }
        }
        return "";
    }


private function _getMyJobsForSearchFromXML_($searchDetails)
    {

        ini_set("user_agent",C__STR_USER_AGENT__);
        ini_set("max_execution_time", 0);
        ini_set("memory_limit", "10000M");
        $arrSearchReturnedJobs = null;;

        $nItemCount = 1;
        $nPageCount = 1;

        $strURL = $this->_getURLfromBase_($searchDetails, $nPageCount, $nItemCount);
        if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED) return;
        $GLOBALS['logger']->logLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['key']. "': ".$strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);


        $arrPageJobsList = $this->_getJobsDataForCachedURL_($strURL);
        if(isset($arrPageJobsList))
        {
            $this->_addSearchJobsToMyJobsList_($arrPageJobsList['object'], $searchDetails);
            $GLOBALS['logger']->logLine("Using cached " . $this->siteName . "[".$searchDetails['name']."]" .": " . countJobRecords($arrPageJobsList['object']). " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
            return;
        }

        $class = new \Scooper\ScooperDataAPIWrapper();
        $class->setVerbose(isVerbose());
        $ret = $class->cURL($strURL, null, 'GET', 'text/xml; charset=UTF-8');
        $xmlResult = simplexml_load_string($ret['output']);

        if(!$xmlResult) throw new ErrorException("Error:  unable to get SimpleXML object for ".$strURL);
        $xmlResult->registerXPathNamespace("def", "http://www.w3.org/2005/Atom");

        if($this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__))
        {
            $totalPagesCount = 1;
            $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__  ; // placeholder because we don't know how many are on the page
        }
        else
        {
            $strTotalResults = $this->parseTotalResultsCount($xmlResult);
            $strTotalResults  = intval(str_replace(",", "", $strTotalResults));
            $nTotalListings = intval($strTotalResults);
            $totalPagesCount = \Scooper\intceil($nTotalListings  / $this->nJobListingsPerPage); // round up always
            if($totalPagesCount < 1)  $totalPagesCount = 1;
        }

        if($nTotalListings <= 0)
        {
            $GLOBALS['logger']->logLine("No new job listings were found on " . $this->siteName . " for search '" . $searchDetails['name']."'.", \Scooper\C__DISPLAY_ITEM_RESULT__);
            $this->_cacheJobsForURL($strURL, null, 0);
            return;
        }
        else
        {

            $GLOBALS['logger']->logLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

            while ($nPageCount <= $totalPagesCount )
            {
                $arrPageJobsList = null;

                $strURL = $this->_getURLfromBase_($searchDetails, $nPageCount, $nItemCount);
                if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED) return;

                if(!($nPageCount == 1 && isset($xmlResult)))
                {
                    $class = new \Scooper\ScooperDataAPIWrapper();
                    $class->setVerbose(isVerbose());
                    $ret = $class->cURL($strURL,'' , 'GET', 'application/rss+xml');

                    $xmlResult = simplexml_load_string($ret['output']);
                    if(!$xmlResult) throw new ErrorException("Error:  unable to get SimpleXML object for ".$strURL);
                }
                $arrPageJobsList = $this->parseJobsListForPage($xmlResult);
                if(!is_array($arrPageJobsList) && $nPageCount > 1)
                {
                    // we likely hit a page where jobs started to be hidden.
                    // Go ahead and bail on the loop here
                    $strWarnHiddenListings = "Could not get all job results back from ". $this->siteName . " for this search starting on page " . $nPageCount.".";
                    if($nPageCount < $totalPagesCount)
                        $strWarnHiddenListings .= "  They likely have hidden the remaining " . ($totalPagesCount - $nPageCount) . " pages worth. ";

                    $GLOBALS['logger']->logLine($strWarnHiddenListings, \Scooper\C__DISPLAY_ITEM_START__);
                    $nPageCount = $totalPagesCount;
                    continue;
                }

                addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);
                $nItemCount += $this->nJobListingsPerPage;
                $nPageCount++;
            }

            $this->_cacheJobsForURL($strURL, $arrPageJobsList, countJobRecords($arrPageJobsList));

        }
        $this->_addSearchJobsToMyJobsList_($arrSearchReturnedJobs, $searchDetails);
        $GLOBALS['logger']->logLine($this->siteName . "[".$searchDetails['name']."]" .": " . $nItemCount . " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
    }


    private function _getFullHTMLForDynamicWebpage_($url, $elementClass, $countRetry = 0)
    {

        try
        {

            if(!isset($GLOBALS['selenium_started']) || $GLOBALS['selenium_started'] == false)
            {
                $strCmdToRun = "java -jar \"" . __ROOT__ . "/lib/selenium-server-standalone-3.0.0-beta4.jar\"  >/dev/null &";
                $result = exec($strCmdToRun);
                $GLOBALS['selenium_started'] = true;
                sleep(5);
            }

            if(isset($GLOBALS['selenium_sessionid']) && $GLOBALS['selenium_sessionid'] != -1)
            {
                $driver = RemoteWebDriver::createBySessionID($GLOBALS['selenium_sessionid']);
//                if(isset($GLOBALS['selenium_cookies']))
//                {
//                    $cookies2 = $driver->manage()->getCookies();
//                    foreach($GLOBALS['selenium_cookies'] as $cookie)
//                    {
//                        $driver->get($this->$url);
//
//                        $driver = $driver->manage()->addCookie(array("name" => $cookie['name'], "value" => $cookie['value'], "path" =>  $cookie['path'], "domain" => $cookie['domain'], "expiry" => $cookie['expiry'], "secure" => $cookie['secure']));
//                    }
//                }
            }
            else
            {
                $host = 'http://localhost:4444/wd/hub'; // this is the default
                $capabilities = DesiredCapabilities::safari();
                $driver = RemoteWebDriver::create($host, $desired_capabilities = $capabilities, 5000);
                $GLOBALS['selenium_sessionid'] = $driver->getSessionID();
            }
            $driver->get($url);

            // wait at most 10 seconds until at least one result is shown
            if($elementClass)
            {
                $driver->wait(10)->until(
                    WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
                        WebDriverBy::className($elementClass)
                    )
                );
            }

            else
            {
                $driver->wait(10);
            }

            $GLOBALS['selenium_cookies'] = $driver->manage()->getCookies();
            $pagehtml = $driver->getPageSource();

            // $driver->close();

            return $pagehtml;
        } catch (Exception $ex) {
            $strMsg = "Failed to get dynamic HTML via Selenium due to error:  ".$ex->getMessage();

            if($countRetry < 3)
            {
                $GLOBALS['logger']->logLine($strMsg, \Scooper\C__DISPLAY_WARNING__);
                return $this->_getFullHTMLForDynamicWebpage_($url, $elementClass, ($countRetry+1));
            }

            $GLOBALS['logger']->logLine($strMsg, \Scooper\C__DISPLAY_ERROR__);
            throw new ErrorException($strMsg);
        }
        finally
        {
//            if($this->isBitFlagSet(C__JOB_USE_SELENIUM) && isset($GLOBALS['selenium_started']) && $GLOBALS['selenium_started'] = true)
//            {
//                if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Sending server shutdown call to Selenium server...", \Scooper\C__DISPLAY_ITEM_RESULT__); }
//                $cmd = "curl \"http://localhost:4444/selenium-server/driver?cmd=shutDownSeleniumServer\" >/dev/null &";
//                exec($cmd);
//                $GLOBALS['selenium_started'] = false;
//            }
        }
    }

    private function _getMyJobsForSearchFromWebpage_($searchDetails)
    {

        $nItemCount = 1;
        $nPageCount = 1;
        $arrSearchReturnedJobs = null;


        $strURL = $this->_getURLfromBase_($searchDetails, $nPageCount, $nItemCount);
        if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED) return;

        $arrPageJobsList = $this->_getJobsDataForCachedURL_($strURL);
        if(isset($arrPageJobsList))
        {
            $this->_addSearchJobsToMyJobsList_($arrPageJobsList['object'], $searchDetails);
            $GLOBALS['logger']->logLine("Using cached " . $this->siteName . "[".$searchDetails['name']."]" .": " . countJobRecords($arrPageJobsList['object']). " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
            return;
        }

        $GLOBALS['logger']->logLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['key']. "': ".$strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        if($this->isBitFlagSet(C__JOB_USE_SELENIUM))
        {
            $html = $this->_getFullHTMLForDynamicWebpage_($strURL, $this->classToCheckExists);
            $objSimpleHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);
        }
        else
        {
            $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL, $this->secsPageTimeout );
        }
        if(!$objSimpleHTML) { throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL); }

        if($this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__))
        {
            $totalPagesCount = 1;
            $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__  ; // placeholder because we don't know how many are on the page
        }
        else
        {
            $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML);
            assert($strTotalResults != VALUE_NOT_SUPPORTED);
            $strTotalResults  = intval(str_replace(",", "", $strTotalResults));
            $nTotalListings = intval($strTotalResults);
            $totalPagesCount = \Scooper\intceil($nTotalListings  / $this->nJobListingsPerPage); // round up always
            if($totalPagesCount < 1)  $totalPagesCount = 1;
        }

        if($nTotalListings <= 0)
        {
            $GLOBALS['logger']->logLine("No new job listings were found on " . $this->siteName . " for search '" . $searchDetails['name']."'.", \Scooper\C__DISPLAY_ITEM_START__);
            return;
        }
        else
        {
            $nJobsFound = 0;

            $GLOBALS['logger']->logLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strURL, \Scooper\C__DISPLAY_ITEM_START__);

            while ($nPageCount <= $totalPagesCount )
            {


                $arrPageJobsList = null;

                if($this->isBitFlagSet(C__JOB_USE_SELENIUM))
                {
                    $html = $this->_getFullHTMLForDynamicWebpage_($strURL, $this->classToCheckExists);
                    $objSimpleHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);

                }

                if(!($nPageCount == 1 && isset($objSimpleHTML)))
                {
                    $strURL = $this->_getURLfromBase_($searchDetails, $nPageCount, $nItemCount);
                    if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED) return;

                    $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL, $this->secsPageTimeout);
                }
                $GLOBALS['logger']->logLine("Getting jobs from ". $strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);
                if(!$objSimpleHTML) throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL);
                try
                {

                    if($this->isBitFlagSet(C__JOB_PREFER_MICRODATA))
                    {
                        $arrPageJobsList = $this->getJobsFromMicroData($objSimpleHTML);
                    }
                    if(!$arrPageJobsList)
                    {
                        $arrPageJobsList = $this->parseJobsListForPageBase($objSimpleHTML);
                        if(!is_array($arrPageJobsList))
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
                        addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);
                        $nJobsFound = countJobRecords($arrSearchReturnedJobs);
                        if($nItemCount == 1) { $nItemCount = 0; }
                        $nItemCount += ($nJobsFound < $this->nJobListingsPerPage) ? $nJobsFound : $this->nJobListingsPerPage;
                    }
                } catch (Exception $ex) {
                    $GLOBALS['logger']->logLine($this->siteName . " error: " . $ex, \Scooper\C__DISPLAY_ERROR__);

                }

                // clean up memory
                $objSimpleHTML->clear();
                unset($objSimpleHTML);
                $objSimpleHTML = null;
                $nPageCount++;
            }
            $this->_cacheJobsForURL($strURL, $arrSearchReturnedJobs, countJobRecords($arrSearchReturnedJobs));
        }

        $this->_addSearchJobsToMyJobsList_($arrSearchReturnedJobs, $searchDetails);
        $GLOBALS['logger']->logLine($this->siteName . "[".$searchDetails['name']."]" .": " . $nJobsFound . " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    /**
     * @param $searchDetails
     * @throws ErrorException
     */
    private function _getMyJobsFromHTMLFiles_($searchDetails)
    {
        $arrSearchReturnedJobs = null;

        if($this->strFilePath_HTMLFileDownloadScript == null || strlen($this->strFilePath_HTMLFileDownloadScript) == 0)
        {
            throw new ErrorException("Cannot download client-side jobs HTML for " . $this->siteName . " because the " . get_class($this) . " plugin does not have an Applescript configured to call.");
        }

        $GLOBALS['logger']->logLine("Starting search " . $searchDetails['name'] . " jobs download through AppleScript.", \Scooper\C__DISPLAY_ITEM_START__);

        $nPageCount = 0;

        $strFileKey = strtolower($this->siteName.'-'.$searchDetails['key']);
        $strFileBase = $this->detailsMyFileOut['directory'].$strFileKey. "-jobs-page-";

        $strURL = $this->_getURLfromBase_($searchDetails, null, null);
        if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED) return;

        $arrPageJobsList = $this->_getJobsDataForCachedURL_($strURL);
        if(isset($arrPageJobsList))
        {
            $this->_addSearchJobsToMyJobsList_($arrPageJobsList['object'], $searchDetails);
            $GLOBALS['logger']->logLine("Using cached " . $this->siteName . "[".$searchDetails['name']."]" .": " . countJobRecords($arrPageJobsList['object']). " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
            return;
        }


        $strCmdToRun = "osascript " . __ROOT__ . "/plugins/".$this->strFilePath_HTMLFileDownloadScript . " " . escapeshellarg($this->detailsMyFileOut["directory"])  . " ".escapeshellarg($searchDetails["site_name"])." " . escapeshellarg($strFileKey)   . " '"  . $strURL . "'";
        $GLOBALS['logger']->logLine("Command = " . $strCmdToRun, \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $result = \Scooper\my_exec($strCmdToRun);
        if(\Scooper\intceil($result['stdout']) == VALUE_NOT_SUPPORTED)
        {
            $strError = "AppleScript did not successfully download the jobs.  Log = ".$result['stderr'];
            $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
            throw new ErrorException($strError);
        }
        else
        {
            // log the resulting output from the script to the job_scooper log
            $GLOBALS['logger']->logLine($result['stderr'], \Scooper\C__DISPLAY_ITEM_DETAIL__);
        }

        $strFileName = $strFileBase.".html";
        $GLOBALS['logger']->logLine("Parsing downloaded HTML files: '" . $strFileBase."*.html'", \Scooper\C__DISPLAY_ITEM_DETAIL__);
        while (file_exists($strFileName) && is_file($strFileName))
        {
            $GLOBALS['logger']->logLine("Parsing results HTML from '" . $strFileName ."'", \Scooper\C__DISPLAY_ITEM_DETAIL__);
            $objSimpleHTML = $this->getSimpleHTMLObjForFileContents($strFileName);
            if(!$objSimpleHTML)
            {
                throw new ErrorException('Error:  unable to get SimpleHTMLDom object from file('.$strFileName.').');
            }

            $arrPageJobsList = $this->parseJobsListForPage($objSimpleHTML);

            addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);

            $objSimpleHTML->clear();
            unset($objSimpleHTML);
            $objSimpleHTML = null;

            //
            // If the user has not asked us to keep interim files around
            // after we're done processing, then delete the interim HTML file
            //
            if($this->is_OutputInterimFiles() != true) {
                unlink($strFileName);
            }

            $nPageCount++;
            $strFileName = $strFileBase.$nPageCount.".html";
        }

        if(isset($arrPageJobsList))
        {
            $GLOBALS['logger']->logLine("Downloaded " . countJobRecords($arrPageJobsList) ." jobs from " . $strFileName, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            $this->_cacheJobsForURL($strURL, $arrPageJobsList, countJobRecords($arrPageJobsList));
            $this->_addSearchJobsToMyJobsList_($arrSearchReturnedJobs, $searchDetails);
        }
        else
        {
            $this->_cacheJobsForURL($strURL, array(), 0);
            $GLOBALS['logger']->logLine("0 jobs found for " . $strFileName, \Scooper\C__DISPLAY_ITEM_DETAIL__);
        }

    }

    /*
     * _addSearchJobsToMyJobsList_
     *
     * Is passed the jobs returned for any given search, marks them for
     * search-specific settings, such as strict title matching, and adds them
     * to the plug-in's internal jobs list.
     *
     * @param array $arrAdd list of jobs to add to this plugins internal tracking list
     * @param array $searchDetails details for the search that the jobs belong to
     *
     */
    private function _addSearchJobsToMyJobsList_($arrAdd, $searchDetails)
    {
        $arrAddJobsForSearch = $arrAdd;

        if(!is_array($arrAddJobsForSearch)) return;


        //
        // check the search flag to see if this is needed
        //
        if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_BE_IN_TITLE) || \Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_EQUAL_TITLE))
        {
            //
            // verify we didn't get here when the keyword can be anywhere in the search
            //
            assert(!\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_ANYWHERE));

            // get an array of the search keywords
            //
            if(!$searchDetails['keyword_set'] == null && is_array($searchDetails['keyword_set']) && count($searchDetails['keyword_set']) > 0)
            {

                // Keywords entered on a per search basis as an override
                // are allowed to be set to the exact URL encoded value
                // the site expects in the search URL.  However, if this type
                // of value is used as the keyword_search_override, no
                // title-matching methods other than "match-type=any" are supported.
                //
                // Since we only get to this point if a non-"any" match-type was set
                // log the fact that we cannot apply the match type for the search
                // and return the unchanged jobs list
                //
                if($this->_isValueURLEncoded_($searchDetails['keyword_set'][0]))
                {
                    $strMatchTypeName = $this->_getKeywordMatchStringFromFlag_($searchDetails['user_setting_flags']);
                    $GLOBALS['logger']->logLine("Cannot apply keyword_match_type=" . $strMatchTypeName . " when keywords are set to exact URL-encoded strings.  Using match-type='any' for search '" .  $searchDetails['name'] ."' instead.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    addJobsToJobsList($this->arrLatestJobs, $arrAdd);
                }

                // We're going to check keywords for strict matches,
                // but we should skip it if we're exact matching and we have multiple keywords, since
                // that's not a possible match case.
                if(!(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_EQUAL_TITLE)) && count($searchDetails['keyword_set']) >= 1)
                {
                    //
                    // check array of jobs against keywords; mark any needed
                    //
                    foreach($arrAddJobsForSearch as $job)
                    {
                        $strTitleMatchScrubbed = \Scooper\strScrub($job['job_title'], FOR_LOOKUP_VALUE_MATCHING);

                        if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_EQUAL_TITLE))
                        {
                            if(count($searchDetails['keyword_set']) != 1)
                            {
                                // TODO log error.
                                print("TODO log error" . PHP_EOL);
                            }
                            elseif(strcasecmp($strTitleMatchScrubbed, $searchDetails['keyword_set'][0]) != 0)
                            {
                                $arrAddJobsForSearch[$job['key_jobsite_siteid']]['interested'] = C__STR_TAG_NOT_EXACT_TITLE_MATCH__ . C__STR_TAG_AUTOMARKEDJOB__;
                            }
                        }
                        else
                        {
                            // Check the different keywords against the job title.  If we got a
                            // match, leave that job record alone and move on.
                            //
                            $arrScrubbedKeywords = $this->_getScrubbedKeywordSet_($searchDetails['keyword_set']);
                            $nCount = \Scooper\substr_count_array($strTitleMatchScrubbed, $arrScrubbedKeywords);
                            if($nCount <= 0)
                            {
                                //
                                // At this point, we assume we're not going to have a match for any of the keywords
                                // but there is one more case to check.  Set a var to the default answer and then
                                // check the multiple terms per keyword case to be sure.

                                $strInterestedValue = C__STR_TAG_NOT_STRICT_TITLE_MATCH__ . C__STR_TAG_AUTOMARKEDJOB__;
                                //
                                // If we had no matches against all the terms, break up any of the keywords
                                // that are multiple words to see if all of the words are present in the title
                                // (just not in the same order.)  If they are not, then mark it as not a match.
                                //
                                foreach($searchDetails['keyword_set'] as $keywordTerm)
                                {
                                    $arrKeywordSubterms = explode(" ", $keywordTerm);
                                    if(count($arrKeywordSubterms) > 1)
                                    {
                                        $arrScrubbedSubterms = $this->_getScrubbedKeywordSet_($arrKeywordSubterms);
                                        $nSubtermMatches = \Scooper\substr_count_array($strTitleMatchScrubbed, $arrScrubbedSubterms);

                                        // If we found a match for every subterm in the list, then
                                        // this was a true match and we should leave it as interested = blank
                                        if($nSubtermMatches == count($arrScrubbedSubterms))
                                        {
                                            $strInterestedValue = "";
                                        }
                                    }
                                }
                                $arrAddJobsForSearch[$job['key_jobsite_siteid']]['interested'] = $strInterestedValue;
                            }
                        }
                    }
                }
            }
            else
            {
                $GLOBALS['logger']->logLine($searchDetails['key'] . " incorrectly set a keyword match type, but has no possible keywords.  Ignoring keyword_match_type request and returning all jobs.", \Scooper\C__DISPLAY_ERROR__);
            }
        }

        //
        // add the jobs to my list, now marked if necessary
        //
        addJobsToJobsList($this->arrLatestJobs, $arrAddJobsForSearch);

    }

    //************************************************************************
    //
    //
    //
    //  Search URL Functions
    //
    //
    //
    //************************************************************************

    private function _isValueURLEncoded_($str)
    {
        if(strlen($str) <= 0) return 0;
        return (\Scooper\substr_count_array($str, array("%22", "&", "=", "+", "-", "%7C", "%3C" )) >0 );

    }

    private function _checkInvalidURL_($details, $strURL)
    {
        if($strURL == null) throw new ErrorException("Skipping " . $this->siteName ." search '".$details['name']. "' because a valid URL could not be set.");
        return $strURL;
        // if($strURL == VALUE_NOT_SUPPORTED) $GLOBALS['logger']->logLine("Skipping " . $this->siteName ." search '".$details['name']. "' because a valid URL could not be set.");
    }



    protected function getDaysURLValue($days = null) { $days = \Scooper\get_PharseOptionValue('number_days'); return ($days == null || $days == "") ? 1 : $days; } // default is to return the raw number
    protected function getItemURLValue($nItem) { return ($nItem == null || $nItem == "") ? 0 : $nItem; } // default is to return the raw number
    protected function getPageURLValue($nPage) { return ($nPage == null || $nPage == "") ? "" : $nPage; } // default is to return the raw number

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

        if(!$this->_isValueURLEncoded_($strReturnLocation))
        {
            $strReturnLocation = urlencode($strReturnLocation);
        }
        $this->setLocationValue($strReturnLocation);
        $strReturnLocation = $this->getLocationValue();
        return $strReturnLocation;
    }

    private function _getBaseURLFormat_($searchDetails = null)
    {
        $strBaseURL = VALUE_NOT_SUPPORTED;

        if($searchDetails != null && isset($searchDetails['base_url_format']))
        {
            $strBaseURL = $searchDetails['base_url_format'];

        }
        elseif(isset($this->strBaseURLFormat))
        {
            $strBaseURL = $this->strBaseURLFormat;
        }
        else
        {
            throw new ErrorException("Could not find base URL format for " . $this->siteName . ".  Aborting all searches for ". $this->siteName, \Scooper\C__DISPLAY_ERROR__);
        }
        return $strBaseURL;
    }

    protected function _getURLfromBase_($searchDetails, $nPage = null, $nItem = null)
    {
        $strURL = $this->_getBaseURLFormat_($searchDetails);


        $strURL = str_ireplace("***NUMBER_DAYS***", $this->getDaysURLValue(), $strURL );
        $strURL = str_ireplace("***PAGE_NUMBER***", $this->getPageURLValue($nPage), $strURL );
        $strURL = str_ireplace("***ITEM_NUMBER***", $this->getItemURLValue($nItem), $strURL );
        if(!$this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
        {
            assert($searchDetails['keywords_string_for_url'] != VALUE_NOT_SUPPORTED);
            $strURL = str_ireplace(BASE_URL_TAG_KEYWORDS, $searchDetails['keywords_string_for_url'], $strURL );
        }


        $nSubtermMatches = substr_count($strURL, BASE_URL_TAG_LOCATION);

        if(!$this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) && $nSubtermMatches > 0)
        {
            $strLocationValue = $this->getLocationValueForLocationSetting($searchDetails);
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


}



