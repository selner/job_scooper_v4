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
            $this->_finalizeSearch_($searchDetails);

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

    function getJobsForAllSearches($nDays = VALUE_NOT_SUPPORTED)
    {

        $strIncludeKey = 'include_'.strtolower($this->siteName);

        if(isset($GLOBALS['OPTS'][$strIncludeKey]) && $GLOBALS['OPTS'][$strIncludeKey] == 0)
        {
            $GLOBALS['logger']->logLine($this->siteName . " excluded for run, so skipping '" . count($this->arrSearchesToReturn). "' search(es) set for that site.", \Scooper\C__DISPLAY_ITEM_START__);
        }
        else
        {

            $this->_collapseSearchesIfPossible_();


            foreach($this->arrSearchesToReturn as $search)
            {
                // assert this search is actually for the job site supported by this plugin
                assert(strcasecmp(strtolower($search['site_name']), strtolower($this->siteName)) == 0);

                $this->_getJobsForSearchByType_($search, $nDays);
            }
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

    /**
     * Main worker function for all jobs sites.
     *
     *
     * @param  integer $nDays Number of days of job listings to pull
     * @param  Array $arrInputFilesToMergeWithResults Optional list of jobs list CSV files to include in the results
     * @param  integer $fIncludeFilteredJobsInResults If true, filters out jobs flagged with "not interested" values from the results.
     * @return string If successful, the final output CSV file with the full jobs list
     */

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

//   TODO:  break the single flag setup into multiples
//    protected $flagsKeywordSettings = null;
//    protected $flagsLocationSettings = null;
//    protected $flagsPluginSetup = null;

    protected $strFilePath_HTMLFileDownloadScript = null;
    protected $strBaseURLFormat = null;
    protected $typeLocationSearchNeeded = null;
    protected $locationValue = null;
    protected $strKeywordDelimiter = null;
    protected $strTitleOnlySearchKeywordFormat = null;


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

        $this->_setKeywordStringsForSearch_($searchDetails);

        //
        // Add the search to the list of ones to run
        //
        $this->arrSearchesToReturn[] = $searchDetails;


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
            $GLOBALS['logger']->logLine($this->siteName . " does not support collapsing terms into a single search.  Continuing with " . count($this->arrSearchesToReturn) . " search(es).", \Scooper\C__DISPLAY_WARNING__);
            return;
        }

        if(count($this->arrSearchesToReturn) == 1)
        {
            $GLOBALS['logger']->logLine($this->siteName . " does not have more than one search to collapse.  Continuing with single '" . $this->arrSearchesToReturn[0]['name'] . "' search.", \Scooper\C__DISPLAY_WARNING__);
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
                    $searchCollapsedDetails['name'] = "Collapsed " . $search['name'];
                    $searchCollapsedDetails['site_name'] = $this->siteName;
                    $searchCollapsedDetails['base_url_format'] = $search['base_url_format'];
                    $searchCollapsedDetails['keyword_set'] = $search['keyword_set'];
                    $searchCollapsedDetails['user_setting_flags'] = $search['user_setting_flags'];
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
        elseif($searchDetails['location_user_specified_override'] != null && strlen($searchDetails['location_user_specified_override']) > 0)
        {
            $searchDetails['location_search_value'] = $searchDetails['location_user_specified_override'];
        }
        elseif(isset($searchDetails['location_set']) && is_array($searchDetails['location_set']) )
        {
            $locTypeNeeded = $this->getLocationSettingType();
            if($searchDetails['location_set'] != null && count($searchDetails['location_set']) > 0 && $searchDetails['location_set'][$locTypeNeeded] != null)
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



    private function _getJobsForSearchByType_($searchDetails, $nDays, $nAttemptNumber = 0)
    {

//        $strURLBase = $this->_getBaseURLFormat_($searchDetails);
//
//        if($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED))
//        {
//            $GLOBALS['logger']->logLine("Running ". $searchDetails['site_name'] . " search '" . $searchDetails['name'] ."' with no location settings and and base_url_format = " . $strURLBase . "..." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_START__);
//        }
//        elseif(substr_count($strURLBase, BASE_URL_TAG_LOCATION) < 1)
//        {
//            $GLOBALS['logger']->logLine("Running ". $searchDetails['site_name'] . " search '" . $searchDetails['name'] ."' with base_url_format = " . $strURLBase . "..." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_START__);
//        }
//        elseif($this->isBitFlagSet(C__JOB_BASE_URL_FORMAT_REQUIRED))
//        {
//            $GLOBALS['logger']->logLine("Running ". $searchDetails['site_name'] . " search '" . $searchDetails['name'] ."' with base_url_format = " . $strURLBase . "..." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_START__);
//        }

        try {
            if($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_XML__))
            {
                $this->_getMyJobsForSearchFromXML_($searchDetails, $nDays);
            }
            elseif($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_HTML_FILE__))
            {
                $this->_getMyJobsFromHTMLFiles_($searchDetails, $nDays);
            }
            elseif($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_WEBPAGE__))
            {
                $this->_getMyJobsForSearchFromWebpage_($searchDetails, $nDays);
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
            if(((strcasecmp($this->siteName, $GLOBALS['DATA']['site_plugins']['employmentguide']['name']) == 0)||
                 (strcasecmp($this->siteName, $GLOBALS['DATA']['site_plugins']['careerbuilder']['name']) == 0) ||
                (strcasecmp($this->siteName, $GLOBALS['DATA']['site_plugins']['ziprecruiter']['name']) == 0)) &&
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
                    $this->_getJobsForSearchByType_($searchDetails, $nDays, ($nAttemptNumber+1));
                }
                else
                {
                    $strError .= " Search failed twice.  Skipping search.";
                    $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                    if($GLOBALS['OPTS']['DEBUG'] == true) { throw new ErrorException( $strError); }
                }
            }
        }
    }



    private function _getMyJobsForSearchFromXML_($searchDetails, $nDays = VALUE_NOT_SUPPORTED)
    {

        ini_set("user_agent",C__STR_USER_AGENT__);
        ini_set("max_execution_time", 0);
        ini_set("memory_limit", "10000M");
        $arrSearchReturnedJobs = null;;

        $nItemCount = 1;
        $nPageCount = 1;

        $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount);
        if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED) return;

        $GLOBALS['logger']->logLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['name']. "': ".$strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $class = new \Scooper\ScooperDataAPIWrapper();
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
            $GLOBALS['logger']->logLine("No new job listings were found on " . $this->siteName . " for search '" . $searchDetails['name']."'.", \Scooper\C__DISPLAY_ITEM_START__);
            return;
        }
        else
        {

            $GLOBALS['logger']->logLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strURL, \Scooper\C__DISPLAY_ITEM_START__);

            while ($nPageCount <= $totalPagesCount )
            {
                $arrPageJobsList = null;

                $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount);
                if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED) return;

                $class = new \Scooper\ScooperDataAPIWrapper();
                $ret = $class->cURL($strURL,'' , 'GET', 'application/rss+xml');

                $xmlResult = simplexml_load_string($ret['output']);
                if(!$xmlResult) throw new ErrorException("Error:  unable to get SimpleXML object for ".$strURL);

                $arrPageJobsList = $this->parseJobsListForPage($xmlResult);


                if(!is_array($arrPageJobsList))
                {
                    // we likely hit a page where jobs started to be hidden.
                    // Go ahead and bail on the loop here
                    $strWarnHiddenListings = "Could not get all job results back from ". $this->siteName . " for this search starting on page " . $nPageCount.".";
                    if($nPageCount < $totalPagesCount)
                        $strWarnHiddenListings .= "  They likely have hidden the remaining " . ($totalPagesCount - $nPageCount) . " pages worth. ";

                    $GLOBALS['logger']->logLine($strWarnHiddenListings, \Scooper\C__DISPLAY_ITEM_START__);
                    $nPageCount = $totalPagesCount ;
                }
                else
                {
                    addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);
                    $nItemCount += $this->nJobListingsPerPage;
                }
                $nPageCount++;

            }

        }
        $this->_addSearchJobsToMyJobsList_($arrSearchReturnedJobs, $searchDetails);
        $GLOBALS['logger']->logLine($this->siteName . "[".$searchDetails['name']."]" .": " . $nItemCount . " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
    }



    private function _getMyJobsForSearchFromWebpage_($searchDetails, $nDays = VALUE_NOT_SUPPORTED)
    {

        $nItemCount = 1;
        $nPageCount = 1;
        $arrSearchReturnedJobs = null;


        $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount);
        if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED) return;

        $GLOBALS['logger']->logLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['name']. "': ".$strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL );
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

            $GLOBALS['logger']->logLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strURL, \Scooper\C__DISPLAY_ITEM_START__);

            while ($nPageCount <= $totalPagesCount )
            {
                $arrPageJobsList = null;

                if($objSimpleHTML == null)
                {
                    $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount);
                    if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED) return;


                }
                $GLOBALS['logger']->logLine("Getting jobs from ". $strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

                if(!$objSimpleHTML) $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL);
                if(!$objSimpleHTML) throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL);

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
                else
                {
                    addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);
                    $nJobsFound = countJobRecords($arrSearchReturnedJobs);
                    if($nItemCount == 1) { $nItemCount = 0; }
                    $nItemCount += ($nJobsFound < $this->nJobListingsPerPage) ? $nJobsFound : $this->nJobListingsPerPage;

                }

                // clean up memory
                $objSimpleHTML->clear();
                unset($objSimpleHTML);
                $objSimpleHTML = null;
                $nPageCount++;

            }
        }

        $this->_addSearchJobsToMyJobsList_($arrSearchReturnedJobs, $searchDetails);
        $GLOBALS['logger']->logLine($this->siteName . "[".$searchDetails['name']."]" .": " . $nJobsFound . " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    private function _getMyJobsFromHTMLFiles_($searchDetails, $nDays = VALUE_NOT_SUPPORTED)
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

        $strURL = $this->_getURLfromBase_($searchDetails, $nDays, null, null);
        if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED) return;

        $GLOBALS['logger']->logLine("Exporting HTML from " . $this->siteName ." jobs for search '".$searchDetails['name']. "' to be parsed: ".$strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

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

            $GLOBALS['logger']->logLine("Downloaded " . countJobRecords($arrPageJobsList) ." jobs from " . $strFileName, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);

            $nPageCount++;

            $strFileName = $strFileBase.$nPageCount.".html";
        }

        $GLOBALS['logger']->logLine("Downloaded " . countJobRecords($arrSearchReturnedJobs) ." total jobs for search '" . $searchDetails['name'] . "'.", \Scooper\C__DISPLAY_ITEM_RESULT__);
        $this->_addSearchJobsToMyJobsList_($arrSearchReturnedJobs, $searchDetails);
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



    protected function getDaysURLValue($days) { return ($days == null || $days == "") ? 1 : $days; } // default is to return the raw number
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
        if($searchDetails != null && $searchDetails['location_user_specified_override'] != null && strlen($searchDetails['location_user_specified_override']) > 0)
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

            if($locSettingSets != null && count($locSettingSets) > 0 && $locSettingSets[$locTypeNeeded] != null)
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

    protected function _getURLfromBase_($searchDetails, $nDays, $nPage = null, $nItem = null)
    {
        $strURL = $this->_getBaseURLFormat_($searchDetails);


        $strURL = str_ireplace("***NUMBER_DAYS***", $this->getDaysURLValue($nDays), $strURL );
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
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Failed to run search:  search is missing the required location type of " . $this->getLocationSettingType() ." set.  Skipping search '". $searchDetails['name'] . "' with settings '" . $locSingleSettingSet['name'] ."'.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
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