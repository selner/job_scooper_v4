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
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');
header('Content-Type: text/html');

const VALUE_NOT_SUPPORTED = -1;
const BASE_URL_TAG_LOCATION = "***LOCATION***";
const BASE_URL_TAG_KEYWORDS = "***KEYWORDS***";

abstract class ClassJobsSitePlugin extends ClassJobsSiteCommon
{
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
    protected $nJobListingsPerPage = 20;
    protected $additionalFlags = [];
    protected $secsPageTimeout = null;

    protected $strKeywordDelimiter = null;
    protected $strTitleOnlySearchKeywordFormat = null;
    protected $classToCheckExists = null;
    protected $cookieNamesToSaveAndResend = Array();
    protected $additionalLoadDelaySeconds = 0;

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

        if($this->additionalFlags)
        {
            foreach($this->additionalFlags as $flag)
            {
                $this->flagSettings = $this->flagSettings | $flag;
            }
        }
    }




    function parseTotalResultsCount($objSimpHTML) {   throw new \BadMethodCallException(sprintf("Not implemented method called on class \"%s \".", __CLASS__)); return null;}
    function parseJobsListForPage($objSimpHTML) {   throw new \BadMethodCallException(sprintf("Not implemented method called on class \"%s \".", __CLASS__)); return null;}
    function getNextPage($driver, $nextPageNum) {   throw new \BadMethodCallException(sprintf("Not implemented method called on class \"%s \".", __CLASS__)); return null;}


    function addSearches($arrSearches)
    {

        if(!is_array($arrSearches[0])) { $arrSearches[] = $arrSearches; }

        foreach($arrSearches as $searchDetails)
        {
            $this->_addSearch_($searchDetails);

        }
    }

    private function _getResultsForSearch_($searchDetails)
    {
        $tmpSearchJobs = $this->_getJobsfromFileStoreForSearch_($searchDetails);
        if(isset($tmpSearchJobs) && is_array($tmpSearchJobs))
            return $tmpSearchJobs;
        else
            return array();
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

    function __destruct()
    {

        //
        // Write out the interim data to file if we're debugging
        //
        if($this->is_OutputInterimFiles() == true && isset($this->detailsMyFileOut) && is_array($this->detailsMyFileOut))
        {
            $arrAllSearchResults = $this->getMyJobsList();
            $strOutPathWithName = $this->getOutputFileFullPath($this->siteName . "-");
            if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Writing ". $this->siteName." " .count($arrAllSearchResults) ." job records to " . $strOutPathWithName . " for debugging (if needed).", \Scooper\C__DISPLAY_ITEM_START__); }
            $this->writeJobsListToFile($strOutPathWithName, $arrAllSearchResults, true, $this->siteName, "CSV");
        }
    }


    function getUpdatedJobsForAllSearches()
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


    private function _parseJobsListForPageBase_($objSimpHTML) {
        $retJobs = $this->parseJobsListForPage($objSimpHTML);
        $retJobs = $this->normalizeJobList($retJobs);

        return $retJobs;

    } // returns an array of jobs


    //************************************************************************
    //
    //
    //
    //  Utility Functions
    //
    //
    //
    //************************************************************************
    function getName() {
        $name = strtolower($this->siteName);
        if(is_null($name) || strlen($name) == 0)
        {
            $name = str_replace("plugin", "", get_class($this));
        }
        return $name;
    }



    /**
     * Write this class instance's list of jobs to an output CSV file.  Always rights
     * the full unfiltered list.
     *
     *
     * @param  string $strOutFilePath The file to output the jobs list to
     * @return string $strOutFilePath The file the jobs was written to or null if failed.
     */
    function writeMyJobsListToFile($strOutFilePath = null)
    {
        return $this->writeJobsListToFile($strOutFilePath, $this->getMyJobsList(), true, $this->siteName, "CSV");
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
    //  Keyword Search Related Functions
    //
    //
    //
    //************************************************************************
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

            $searchDetails['keywords_string_for_url'] = $this->getCombinedKeywordStringForURL($searchDetails['keywords_array']);
        }

        // Lastly, check if we support keywords in the URL at all for this
        // plugin.  If not, remove any keywords_string_for_url value we'd set
        // and set it to "not supported"
        if($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
        {
            $searchDetails['keywords_string_for_url'] = VALUE_NOT_SUPPORTED;
        }
    }




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

        if($this->isBitFlagSet(C__JOB_KEYWORD_MULTIPLE_TERMS_SUPPORTED) && $this->strTitleOnlySearchKeywordFormat != null && strlen($this->strTitleOnlySearchKeywordFormat) > 0)
        {
            $strRetCombinedKeywords = sprintf($this->strTitleOnlySearchKeywordFormat, $strRetCombinedKeywords);
        }

        return $strRetCombinedKeywords;
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



    function _setStartingUrlForSearch_(&$searchDetails)
    {

        $searchStartURL = $this->_getURLfromBase_($searchDetails, 1, 1);
        $searchDetails['search_start_url'] = $searchStartURL;
        $GLOBALS['logger']->logLine("Setting start URL for " . $this->siteName . "[". $searchDetails['name'] . " to: " . PHP_EOL. $searchDetails['search_start_url'] , \Scooper\C__DISPLAY_ITEM_DETAIL__);

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

        assert($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || ($searchDetails['location_search_value'] !== VALUE_NOT_SUPPORTED && strlen($searchDetails['location_search_value']) > 0));

        $this->_setKeywordStringsForSearch_($searchDetails);
        $this->_setStartingUrlForSearch_($searchDetails);

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
                $GLOBALS['logger']->logLine("Using cached " . $this->siteName . "[".$searchDetails['name']."]" .": " . countJobRecords($arrPageJobsList). " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
            }
            else
            {
                $GLOBALS['logger']->logLine(("No previously retrieved & cached results found.  Starting data pull for " . $this->siteName . "[". $searchDetails['name']), \Scooper\C__DISPLAY_ITEM_RESULT__);

                if($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_XML__))
                {
                    $arrPageJobsList = $this->_getMyJobsForSearchFromXML_($searchDetails);
                }
                elseif($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__))
                {
                    $arrPageJobsList = $this->_getMyJobsForSearchFromJobsAPI_($searchDetails);
                }
                elseif($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_WEBPAGE__))
                {
                    $arrPageJobsList = $this->_getMyJobsForSearchFromWebpage_($searchDetails);
                }
                else
                {
                    throw new ErrorException("Class ". get_class($this) . " does not have a valid setting for parser.  Cannot continue.");
                }

                $this->_setJobsToFileStoreForSearch_($searchDetails, $arrPageJobsList);
            }
            $GLOBALS['logger']->logSectionHeader(("Finished data pull for " . $this->siteName . "[". $searchDetails['name']), \Scooper\C__SECTION_END__, \Scooper\C__NAPPTOPLEVEL__);
        } catch (Exception $ex) {

            //
            // BUGBUG:  This is a workaround to prevent errors from showing up
            // when no results are returned for a particular search for EmploymentGuide plugin only
            // See https://github.com/selner/jobs_scooper/issues/23 for more details on
            // this particular underlying problem
            //
            $strErr = $ex->getMessage();
            if((isset($GLOBALS['JOBSITE_PLUGINS']['employmentguide']) && (strcasecmp($this->siteName, $GLOBALS['JOBSITE_PLUGINS']['employmentguide']['name']) == 0)||
                 (isset($GLOBALS['JOBSITE_PLUGINS']['careerbuilder']) && strcasecmp($this->siteName, $GLOBALS['JOBSITE_PLUGINS']['careerbuilder']['name']) == 0) ||
                (isset($GLOBALS['JOBSITE_PLUGINS']['ziprecruiter']) && strcasecmp($this->siteName, $GLOBALS['JOBSITE_PLUGINS']['ziprecruiter']['name']) == 0)) &&
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


                $strError = "Failed to download jobs from " . $this->siteName ." jobs for search '".$searchDetails['name']. "[URL=".$searchDetails['search_start_url'] . "].  ".$ex->getMessage();
                $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                throw new Exception($strError);
            }
        }
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
                $item['job_site'] = $this->siteName;
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

                $item = $this->normalizeItem($item);
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

    private function _getFileStoreKeyForSearch($searchSettings, $prefix="")
    {
        if(stripos($searchSettings['key'], $this->siteName) === false)
        {
            $prefix = $prefix .$this->siteName;
        }

        $key = $prefix . \Scooper\strip_punctuation($this->getDaysURLValue().$searchSettings['key']);

        return $key;
    }

    private function _setJobsToFileStoreForSearch_($searchSettings, $dataJobs)
    {
        $key = $this->_getFileStoreKeyForSearch( $searchSettings, "");
        return writeJobsListDataToLocalJSONFile($key, $dataJobs, JOBLIST_TYPE_UNFILTERED, $stageNumber=null, $searchDetails=$searchSettings);
    }


    private function _getJobsfromFileStoreForSearch_($searchSettings)
    {
        $key = $this->_getFileStoreKeyForSearch( $searchSettings, "");
        return readJobsListFromLocalJsonFile($key);

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
                if (isset($mditem->type) && strcasecmp(parse_url($mditem->type[0], PHP_URL_PATH), "/JobPosting") == 0) {

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
                    $item['company'] = $this->siteName;

//                    if(isset($this->regex_link_job_id))
//                    {
//                        $item['job_id'] = $this->getIDFromLink($this->regex_link_job_id, $item['job_post_url']);
//                    }
//                    else
//                    {
//                        $item['job_id'] = $item['job_site'] . "_" . preg_replace('/[\s\W]+/', '', $item['job_post_url']);
//                    }


                    $ret[] = $this->normalizeItem($item);
                }
            }
        }

        return $ret;

    }


    private function _getMyJobsForSearchFromXML_($searchDetails)
    {

        ini_set("user_agent",C__STR_USER_AGENT__);
        ini_set("max_execution_time", 0);
        ini_set("memory_limit", "10000M");
        $arrSearchReturnedJobs = null;;

        $nItemCount = 1;
        $nPageCount = 1;

        $GLOBALS['logger']->logLine("Downloading count of " . $this->siteName ." jobs for search '".$searchDetails['key']. "': ".$searchDetails['search_start_url'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $class = new \Scooper\ScooperDataAPIWrapper();
        $class->setVerbose(isVerbose());
        $ret = $class->cURL($searchDetails['search_start_url'], null, 'GET', 'text/xml; charset=UTF-8');
        $xmlResult = simplexml_load_string($ret['output']);

        if(!$xmlResult) throw new ErrorException("Error:  unable to get SimpleXML object for ".$searchDetails['search_start_url']);
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
            return array();
        }
        else
        {

            $GLOBALS['logger']->logLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$searchDetails['search_start_url'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

            while ($nPageCount <= $totalPagesCount )
            {
                $arrPageJobsList = null;

                $strURL = $this->_getURLfromBase_($searchDetails, $nPageCount, $nItemCount);
                if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED)
                    return null;;

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
        }
        $GLOBALS['logger']->logLine($this->siteName . "[".$searchDetails['name']."]" .": " . $nItemCount . " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
        return $arrSearchReturnedJobs;
    }

    protected function getNextInfiniteScrollSet($driver)
    {
        // Neat trick written up by http://softwaretestutorials.blogspot.in/2016/09/how-to-perform-page-scrolling-with.html.
        $driver->executeScript("window.scrollBy(500,5000);");

        sleep(5);

    }
    private function _getFullHTMLForDynamicWebpage_($url, $elementClass, $countRetry = 0)
    {
        $driver = null;
        try
        {
            if(array_key_exists('selenium_sessionid', $GLOBALS) && isset($GLOBALS['selenium_sessionid']) && $GLOBALS['selenium_sessionid'] != -1)
            {
                $driver = RemoteWebDriver::createBySessionID($GLOBALS['selenium_sessionid'], $GLOBALS['USERDATA']['selenium']['host_location'] . "/wd/hub");
            }
            else
            {


//                use \Facebook\WebDriver\Remote\WebDriverCapabilityType;
//                use \Facebook\WebDriver\Remote\RemoteWebDriver;
//                use \Facebook\WebDriver\WebDriverDimension;
//
//                $host = '127.0.0.1:8910';
//                $capabilities = array(
//                    WebDriverCapabilityType::BROWSER_NAME => 'phantomjs',
//                    'phantomjs.page.settings.userAgent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:25.0) Gecko/20100101 Firefox/25.0',
//                );
//                $driver = RemoteWebDriver::create($host, $capabilities, 5000);
//
//                $window = new WebDriverDimension(1024, 768);
//                $driver->manage()->window()->setSize($window);
//
//                $driver->get('https://www.google.ru/');
//
//                $driver->takeScreenshot('/tmp/screen.png');
//                $driver->quit();


                $capabilities = DesiredCapabilities::phantomjs();
                if(PHP_OS == "Darwin")
                    $capabilities = DesiredCapabilities::safari();

                $capabilities->setCapability("nativeEvents", true);
                $capabilities->setCapability("setThrowExceptionOnScriptError", false);

                $host = $GLOBALS['USERDATA']['selenium']['host_location'] . '/wd/hub';
                $driver = RemoteWebDriver::create($host, $desired_capabilities = $capabilities, 5000);
                $GLOBALS['selenium_sessionid'] = $driver->getSessionID();
            }
            $driver->get($url);

//            // wait at most 10 seconds until at least one result is shown
//            if($elementClass)
//            {
//                $driver->wait(10+$this->additionalLoadDelaySeconds)->until(
//                    WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
//                        WebDriverBy::className($elementClass)
//                    )
//                );
//            }
//            else
//            {
//                sleep(2+$this->additionalLoadDelaySeconds);
//            }
            sleep(2+$this->additionalLoadDelaySeconds);

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
        return $driver;
    }


    private function _getMyJobsForSearchFromWebpage_($searchDetails)
    {

        $nItemCount = 1;
        $nPageCount = 1;
        $arrSearchReturnedJobs = null;
        $driver = null;
        $objSimpleHTML = null;

        $GLOBALS['logger']->logLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['key']. "': ".$searchDetails['search_start_url'], \Scooper\C__DISPLAY_ITEM_DETAIL__);

        try
        {
            if($this->isBitFlagSet(C__JOB_USE_SELENIUM))
            {
                try
                {
                    $driver = $this->_getFullHTMLForDynamicWebpage_($searchDetails['search_start_url'], $this->classToCheckExists);
                    $html = $driver->getPageSource();
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

            $totalPagesCount = 1;
            $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__  ; // placeholder because we don't know how many are on the page
            if(!$this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__))
            {
                $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML->root);
                $nTotalResults  = intval(str_replace(",", "", $strTotalResults));
                $nTotalListings = $nTotalResults;
                if($nTotalResults == 0)
                {
                    $totalPagesCount = 0;
                }
                elseif($nTotalResults != C__TOTAL_ITEMS_UNKNOWN__)
                {
                    $totalPagesCount = \Scooper\intceil($nTotalListings  / $this->nJobListingsPerPage); // round up always
                    if($totalPagesCount < 1)  $totalPagesCount = 1;
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
                            if($driver == null)
                            {
                                $driver = $this->_getFullHTMLForDynamicWebpage_($strURL, $this->classToCheckExists);
                            }
                            if($this->isBitFlagSet( C__JOB_INFSCROLL_DOWNFULLPAGE))
                            {
                                while($nPageCount <= $totalPagesCount)
                                {
                                    if(isDebug() && isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("... getting infinite results page #".$nPageCount." of " .$totalPagesCount, \Scooper\C__DISPLAY_NORMAL__); }
                                    $this->getNextInfiniteScrollSet($driver);
                                    $strURL = $driver->getCurrentURL();
                                    $nPageCount = $nPageCount + 1;
                                }
                                $html = $driver->getPageSource();
                                $objSimpleHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);
                            }
                            else if(!$this->isBitFlagSet( C__JOB_INFSCROLL_DOWNFULLPAGE))
                            {
                                if($nPageCount <= $totalPagesCount && $nPageCount > 1)
                                {
                                    $retDriver = $this->getNextPage($driver, $nPageCount);
                                    if(!is_null($retDriver))
                                        $driver = $retDriver;
                                }

                                // BUGBUG -- Checking these two HTML values to make sure they still match
                                $strURL = $driver->getCurrentURL();
                                $html = $driver->getPageSource();
                                $objSimpleHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);
                            }
                    } catch (Exception $ex) {
                            $strMsg = "Failed to get dynamic HTML via Selenium due to error:  ".$ex->getMessage();

                            $GLOBALS['logger']->logLine($strMsg, \Scooper\C__DISPLAY_ERROR__);
                            throw new ErrorException($strMsg);
                        }
                    }
                    else
                    {
                        $strURL = $this->_getURLfromBase_($searchDetails, $nPageCount, $nItemCount);
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
                            $arrPageJobsList = $this->getJobsFromMicroData($objSimpleHTML);
                        }
                        if(!$arrPageJobsList)
                        {
                            $arrPageJobsList = $this->_parseJobsListForPageBase_($objSimpleHTML);
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
                        throw $ex;
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



    //************************************************************************
    //
    //
    //
    //  Search URL Functions
    //
    //
    //
    //************************************************************************


    private function _checkInvalidURL_($details, $strURL)
    {
        if($strURL == null) throw new ErrorException("Skipping " . $this->siteName ." search '".$details['name']. "' because a valid URL could not be set.");
        return $strURL;
        // if($strURL == VALUE_NOT_SUPPORTED) $GLOBALS['logger']->logLine("Skipping " . $this->siteName ." search '".$details['name']. "' because a valid URL could not be set.");
    }



    protected function getDaysURLValue($days = null) { $days = \Scooper\get_PharseOptionValue('number_days'); return ($days == null || $days == "") ? 1 : $days; } // default is to return the raw number
    protected function getItemURLValue($nItem) { return ($nItem == null || $nItem == "") ? 0 : $nItem; } // default is to return the raw number
    protected function getPageURLValue($nPage) { return ($nPage == null || $nPage == "") ? "" : $nPage; } // default is to return the raw number
    protected function getKeywordURLValue($searchDetails) {
        if(!$this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
        {
            assert($searchDetails['keywords_string_for_url'] != VALUE_NOT_SUPPORTED);
            return $searchDetails['keywords_string_for_url'];
        }
        return "";
    }

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

    protected function _getURLfromBase_($searchDetails, $nPage = null, $nItem = null)
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


}



