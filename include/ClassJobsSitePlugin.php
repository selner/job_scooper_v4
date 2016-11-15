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
    protected $flagSettings = null;
    protected $secsPageTimeout = null;

    protected $strBaseURLFormat = null;
    protected $typeLocationSearchNeeded = null;
    protected $locationValue = null;
    protected $strKeywordDelimiter = null;
    protected $strTitleOnlySearchKeywordFormat = null;
    protected $classToCheckExists = null;
    protected $cookieNamesToSaveAndResend = Array();
    protected $additionalLoadDelaySeconds = 0;




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

    private function _getResultsForSearch_($searchDetails)
    {
        $tmpSearchJobs = $this->_getCachedJobsForSearch_($searchDetails);
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
            $this->writeJobsListToFile($strOutPathWithName, $arrAllSearchResults, true, false, $this->siteName, "CSV");
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
    function getName() { return $this->siteName; }
    function getLocationSettingType() { return $this->typeLocationSearchNeeded; }
    private function _setLocationValue_($locVal) { $this->locationValue = $locVal; }
    function getLocationValue() { return $this->locationValue; }



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
        return $this->writeJobsListToFile($strOutFilePath, $this->getMyJobsList(), true, false, $this->siteName, "CSV");
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
                    $this->_finalizeSearch_($searchCollapsedDetails);
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

        if(!$this->_isValueURLEncoded_($strRetCombinedKeywords))
        {
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

        if ($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || $this->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED))
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

        $this->_setKeywordStringsForSearch_($searchDetails);
        $this->_setLocationValueForSearch_($searchDetails);
        $this->_setStartingUrlForSearch_($searchDetails);

    }



    private function _getJobsForSearchByType_($searchDetails, $nAttemptNumber = 0)
    {
        $GLOBALS['logger']->logSectionHeader(("Starting data pull for " . $this->siteName . "[". $searchDetails['name']), \Scooper\C__SECTION_BEGIN__, \Scooper\C__NAPPTOPLEVEL__);
        $strStartingURL = $searchDetails['search_start_url'];
        $this->_logMemoryUsage_();

        try {

            // get the url for the first page/items in the results
            if($this->_checkInvalidURL_($searchDetails, $strStartingURL) == VALUE_NOT_SUPPORTED) return;

            // get all the results for all pages if we have them cached already
            $arrPageJobsList = $this->_getCachedJobsForSearch_($searchDetails);
            if(isset($arrPageJobsList))
            {
                $GLOBALS['logger']->logLine("Using cached " . $this->siteName . "[".$searchDetails['name']."]" .": " . countJobRecords($arrPageJobsList). " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
            }
            else
            {
                $GLOBALS['logger']->logLine(("No cached results found.  Starting data pull for " . $this->siteName . "[". $searchDetails['name']), \Scooper\C__DISPLAY_ITEM_RESULT__);

                if($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_XML__))
                {
                    $arrPageJobsList = $this->_getMyJobsForSearchFromXML_($searchDetails, $strStartingURL);
                }
                elseif($this->isBitFlagSet(C__JOB_SEARCH_RESULTS_TYPE_WEBPAGE__))
                {
                    $arrPageJobsList = $this->_getMyJobsForSearchFromWebpage_($searchDetails, $strStartingURL);
                }
                else
                {
                    throw new ErrorException("Class ". get_class($this) . " does not have a valid setting for parser.  Cannot continue.");
                }
                $this->_setCachedJobsForSearch_($searchDetails, $arrPageJobsList);
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


                $strError = "Failed to download jobs from " . $this->siteName ." jobs for search '".$searchDetails['name']. "[URL=".$strStartingURL. "].  ".$ex->getMessage();
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
        finally
        {
            $this->_logMemoryUsage_();
            $GLOBALS['logger']->logSectionHeader(("Finished data pull for " . $this->siteName . "[". $searchDetails['name']), \Scooper\C__SECTION_END__, \Scooper\C__NAPPTOPLEVEL__);
        }
    }

    private function _getCacheKey($strURLFirstPage)
    {
        return urlencode(\Scooper\getTodayAsString() . $strURLFirstPage);
    }

    private function _getCache()
    {
        return new JG_Cache2($dir = $GLOBALS['OPTS']['cache_path'], $subdir = $this->siteName);
    }

    private function _getCachedJobsForSearch_($searchSettings)
    {
        $cache = $this->_getCache();
        $key = $this->_getCacheKey($searchSettings['search_start_url']);

        $data = $cache->get($key);
        if ($data === FALSE)
        {
            // No cached data file found; return null;
            return null;
        }
        else
        {
            return $data;
        }

    }

    private function _setCachedJobsForSearch_($searchSettings, $dataJobs)
    {
        $key = $this->_getCacheKey( $searchSettings['search_start_url']);

        $cache = $this->_getCache();
        $data = $cache->set($key, $dataJobs);
        if ($data === FALSE)
        {
            $GLOBALS['logger']->logLine("Failed to cache results for search " . $searchSettings['name'] . " and key [" . $key . ".]", \Scooper\C__DISPLAY_ERROR__);
            return FALSE;
        }
        else
        {
            $GLOBALS['logger']->logLine("Search " . $searchSettings['name'] . " listings cached to disk with key [" . $key . ".]", \Scooper\C__DISPLAY_NORMAL__);
            return $dataJobs;
        }
    }

    protected function getCachedJobResultsForSearch($searchDetails)
    {
        $dataCached = $this->_getCachedJobsForSearch_($searchDetails);
        if($dataCached != null && is_array($dataCached))
            return $dataCached;
        else
            return array();

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
        }

        return $ret;

    }


    function getIDFromLink($regex_link_job_id, $url)
    {
        if(isset($regex_link_job_id))
        {
            $fMatchedID = preg_match($regex_link_job_id, $url, $idMatches);
            if($fMatchedID && count($idMatches) >= 1)
            {
                return $idMatches[count($idMatches)-1];
            }
        }
        return "";
    }


    private function _getMyJobsForSearchFromXML_($searchDetails, $strStartingURL)
    {

        ini_set("user_agent",C__STR_USER_AGENT__);
        ini_set("max_execution_time", 0);
        ini_set("memory_limit", "10000M");
        $arrSearchReturnedJobs = null;;

        $nItemCount = 1;
        $nPageCount = 1;

        $GLOBALS['logger']->logLine("Downloading count of " . $this->siteName ." jobs for search '".$searchDetails['key']. "': ".$strStartingURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $class = new \Scooper\ScooperDataAPIWrapper();
        $class->setVerbose(isVerbose());
        $ret = $class->cURL($strStartingURL, null, 'GET', 'text/xml; charset=UTF-8');
        $xmlResult = simplexml_load_string($ret['output']);

        if(!$xmlResult) throw new ErrorException("Error:  unable to get SimpleXML object for ".$strStartingURL);
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

            $GLOBALS['logger']->logLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strStartingURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

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
                $driver = RemoteWebDriver::createBySessionID($GLOBALS['selenium_sessionid']);
            }
            else
            {
                $host = 'http://localhost:4444/wd/hub'; // this is the default
                $capabilities = DesiredCapabilities::safari();
                $capabilities->setCapability("nativeEvents", true);
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


    private function _getMyJobsForSearchFromWebpage_($searchDetails, $strStartURL)
    {

        $nItemCount = 1;
        $nPageCount = 1;
        $arrSearchReturnedJobs = null;
        $driver = null;
        $objSimpleHTML = null;

        $GLOBALS['logger']->logLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['key']. "': ".$strStartURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        if($this->isBitFlagSet(C__JOB_USE_SELENIUM))
        {
            try
            {
                $driver = $this->_getFullHTMLForDynamicWebpage_($strStartURL, $this->classToCheckExists);
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
            $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strStartURL, $this->secsPageTimeout );
        }
        if(!$objSimpleHTML) { throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strStartURL); }

        $totalPagesCount = 1;
        $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__  ; // placeholder because we don't know how many are on the page
        if(!$this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__))
        {
            $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML);
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

            $GLOBALS['logger']->logLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strStartURL, \Scooper\C__DISPLAY_ITEM_START__);

            while ($nPageCount <= $totalPagesCount )
            {


                $arrPageJobsList = null;

                if($this->isBitFlagSet(C__JOB_USE_SELENIUM))
                {
                    try
                    {
                        if($driver == null)
                            $driver = $this->_getFullHTMLForDynamicWebpage_($strStartURL, $this->classToCheckExists);

                        if($this->isBitFlagSet( C__JOB_INFSCROLL_DOWNFULLPAGE))
                        {
                            while($nPageCount <= $totalPagesCount)
                            {
                                if(isDebug() && isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("... getting infinite results page #".$nPageCount." of " .$totalPagesCount, \Scooper\C__DISPLAY_NORMAL__); }
                                $this->getNextInfiniteScrollSet($driver);
                                $this->_logMemoryUsage_();

                                if($nPageCount <= $totalPagesCount)
                                    $nPageCount = $nPageCount + 1;
                            }

                        }
                    } catch (Exception $ex) {
                        $strMsg = "Failed to scroll down through full set of results due to error: ".$ex->getMessage();

                        $GLOBALS['logger']->logLine($strMsg, \Scooper\C__DISPLAY_ERROR__);
                        throw new ErrorException($strMsg);
                    }

                    try
                    {

                        // BUGBUG -- Checking these two HTML values to make sure they still match
                        $html = $driver->getPageSource();
                        $objSimpleHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);
                    } catch (Exception $ex) {
                        $strMsg = "Failed to get dynamic HTML via Selenium due to error:  ".$ex->getMessage();

                        $GLOBALS['logger']->logLine($strMsg, \Scooper\C__DISPLAY_ERROR__);
                        throw new ErrorException($strMsg);
                    }
                }
                $strURL = $strStartURL;
                if(!isset($objSimpleHTML))
                {
                    $strURL = $this->_getURLfromBase_($searchDetails, $nPageCount, $nItemCount);
                    if($this->_checkInvalidURL_($searchDetails, $strURL) == VALUE_NOT_SUPPORTED)
                        return null;

                    $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL, $this->secsPageTimeout);
                }
                if(!$objSimpleHTML) throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL);

                $GLOBALS['logger']->logLine("Getting jobs from ". $strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);
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

    }



//    function markJobsList_NonTitleMatches(&$arrToMark, $searchDetails)
//    {
//        if(count($arrToMark) == 0) return;
//
//        $GLOBALS['logger']->logLine("Excluding Jobs that are not Title Matches for search " . $searchDetails['name'], \Scooper\C__DISPLAY_ITEM_START__);
//
//        //
//        // check the search flag to see if this is needed
//        //
//        if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_BE_IN_TITLE) || \Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_EQUAL_TITLE))
//        {
//            //
//            // verify we didn't get here when the keyword can be anywhere in the search
//            //
//            assert(!\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_ANYWHERE));
//
//            // get an array of the search keywords
//            //
//            if(!$searchDetails['keyword_set'] == null && is_array($searchDetails['keyword_set']) && count($searchDetails['keyword_set']) > 0)
//            {
//
//                $kwdTokens = tokenizeKeywords($searchDetails['keyword_set']);
//
//                // Keywords entered on a per search basis as an override
//                // are allowed to be set to the exact URL encoded value
//                // the site expects in the search URL.  However, if this type
//                // of value is used as the keyword_search_override, no
//                // title-matching methods other than "match-type=any" are supported.
//                //
//                // Since we only get to this point if a non-"any" match-type was set
//                // log the fact that we cannot apply the match type for the search
//                // and return the unchanged jobs list
//                //
//                if($this->_isValueURLEncoded_($searchDetails['keyword_set'][0]))
//                {
//                    $strMatchTypeName = $this->_getKeywordMatchStringFromFlag_($searchDetails['user_setting_flags']);
//                    $GLOBALS['logger']->logLine("Cannot apply keyword_match_type=" . $strMatchTypeName . " when keywords are set to exact URL-encoded strings.  Using match-type='any' for search '" .  $searchDetails['name'] ."' instead.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
//                    return;
//                }
//
//                //
//                // check array of jobs against keywords; mark any needed
//                //
//                foreach($arrToMark as $job)
//                {
//                    $strTitleMatchScrubbed = \Scooper\strScrub($job['job_title'], FOR_LOOKUP_VALUE_MATCHING);
//                    $strInterestedValue = "";
//
//                    // We're going to check keywords for strict matches,
//                    // but we should skip it if we're exact matching and we have multiple keywords, since
//                    // that's not a possible match case.
//                    if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_EQUAL_TITLE) && count($searchDetails['keyword_set']) >= 1)
//                    {
//
//                        if(strcasecmp($strTitleMatchScrubbed, $searchDetails['keyword_set'][0]) != 0)
//                        {
//                            $strInterestedValue = C__STR_TAG_NOT_EXACT_TITLE_MATCH__ . C__STR_TAG_AUTOMARKEDJOB__;
//                        }
//                    }
//                    else if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_BE_IN_TITLE) && count($searchDetails['keyword_set']) >= 1)
//                    {
//
//                        // Check the different keywords against the job title.  If we got a
//                        // match, leave that job record alone and move on.
//                        //
////                        $arrScrubbedKeywords = $this->_getScrubbedKeywordSet_($searchDetails['keyword_set']);
//                        $arrScrubbedSubterms = array();
//                        foreach($searchDetails['keyword_set'] as $keywordTerm)
//                        {
//                            $arrKeywordSubterms = explode(" ", $keywordTerm);
//                            $newSubTerms = $this->_getScrubbedKeywordSet_($arrKeywordSubterms);
//                            foreach($newSubTerms as $newTerm)
//                                $arrScrubbedSubterms[] = $newTerm;
//                        }
//                        $arrScrubbedSubterms = array_unique($arrScrubbedSubterms);
//
//                        $nSubtermMatches = \Scooper\substr_count_array($strTitleMatchScrubbed, $arrScrubbedSubterms);
//
//                        // If we found a match for any subterm in the list, then
//                        // this was a true match and we should leave it as interested = blank
//                        if($nSubtermMatches <= 0 && $nSubtermMatches != count($arrScrubbedSubterms))
//                            $strInterestedValue =  C__STR_TAG_NOT_A_KEYWORD_TITLE_MATCH__. C__STR_TAG_AUTOMARKEDJOB__;
//                        else
//                            $strInterestedValue = null;
//
//                    }
//                    if(isset($strInterestedValue))
//                        $arrJobsFound[$job['key_jobsite_siteid']]['interested'] = $strInterestedValue;
//                }
//            }
//            else
//            {
//                $GLOBALS['logger']->logLine($searchDetails['key'] . " incorrectly set a keyword match type, but has no possible keywords.  Ignoring keyword_match_type request and returning all jobs.", \Scooper\C__DISPLAY_ERROR__);
//            }
//        }
////        if(count($arrJobs_AutoUpdatable) > 0)
////        {
////            try
////            {
////                $arrTitles = \Scooper\array_copy($arrJobs_AutoUpdatable);
//////                $titles = array_column($arrJobs_AutoUpdatable, "job_title", "job_title");
//////                foreach($titles as $k_rec => $v_rec)
//////                    $arrTitles[] = array('job_title' => $v_rec);
//////
////                //                foreach($titles as $k_rec => $v_rec)
//////                    $arrTitles[] = array('job_title' => $v_rec);
////                $tokenOutputFile = __DIR__. "/tempJobTitleTokens.csv";
//////                $classCSVFile = new \Scooper\ScooperSimpleCSV($tokenOutputFile , 'w');
//////                $classCSVFile->writeArrayToCSVFile($arrTitles, array('job_title'), array("job_title"));
//////                $classCSVFile = null;
////
////
////                $classCSVFile = new \Scooper\ScooperSimpleCSV($tokenOutputFile, 'w');
////                $classCSVFile->writeArrayToCSVFile($arrTitles, array_keys(array_shift($arrTitles)), "key_jobsite_siteid");
////                $arrTitlesTokened = callTokenizer($tokenOutputFile, null, "job_title");
////
////                foreach($arrTitlesTokened as $job)
////                {
////                    $arrMatches = array();
////                    $arrMatchErrors = array();
////                    $tokenizedTitle = join(" ", explode("|", $job['tokenized']));
////
//////                    $success = preg_match_multiple($GLOBALS['DATA']['title_tokens_to_filter'], $tokenizedTitle, $arrMatches , null, $arrMatchErrors );
////
//////                    $success = \Scooper\substr_count_array($tokenizedTitle, array$GLOBALS['DATA']['title_tokens_to_filter']);
//////
////                    $matched = substr_count_multi($tokenizedTitle, $GLOBALS['DATA']['title_tokens_to_filter'], $arrMatches );
////
////                    if(count($arrMatches) > 0)
////                    {
////                        $strTitleREMatches = getArrayValuesAsString(array_values($arrMatches), "|", "", false );
////                        $strJobIndex = getArrayKeyValueForJob($job);
////
////                        $arrToMark[$strJobIndex]['interested'] = 'No (Title Excluded Via RegEx)' . C__STR_TAG_AUTOMARKEDJOB__;
////                        if(strlen($arrToMark[$strJobIndex]['notes']) > 0) { $arrToMark[$strJobIndex]['notes'] = $arrToMark[$strJobIndex]['notes'] . " "; }
////                        $arrToMark[$strJobIndex]['notes'] = "Title matched exclusion regex [". $strTitleREMatches  ."]". C__STR_TAG_AUTOMARKEDJOB__;
////                        $arrToMark[$strJobIndex]['date_last_updated'] = \Scooper\getTodayAsString();
////                    }
////
////                }
////            }
////            catch (Exception $ex)
////            {
////                $GLOBALS['logger']->logLine('ERROR:  Failed to verify titles against regex strings due to error: '. $ex->getMessage(), \Scooper\C__DISPLAY_ERROR__);
////                if(isDebug()) { throw $ex; }
////            }
////        }
//        $strTotalRowsText = "/".count($arrToMark);
////        $nAutoExcludedTitleRegex = count(array_filter($arrToMark, "isInterested_TitleExcludedViaRegex"));
//
////        $GLOBALS['logger']->logLine("Marked titles regex(".$nAutoExcludedTitleRegex . $strTotalRowsText .") , skipped: " . $nJobsSkipped . $strTotalRowsText .", untouched: ". (count($arrToMark) - $nJobsSkipped - $nAutoExcludedTitleRegex) . $strTotalRowsText .")" , \Scooper\C__DISPLAY_ITEM_RESULT__);
//    }

    /*
     * _autoMarkUpdatedSearchJobs_
     *
     * Is passed the jobs returned for any given search, marks them for
     * search-specific settings, such as strict title matching, and adds them
     * to the plug-in's internal jobs list.
     *
     * @param array $arrAdd list of jobs to add to this plugins internal tracking list
     * @param array $searchDetails details for the search that the jobs belong to
     *
     */
    private function _autoMarkUpdatedSearchJobs_(&$arrJobsFound, $searchDetails)
    {
        if(!is_array($arrJobsFound)) return;


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
                    return;
                }

                //
                // check array of jobs against keywords; mark any needed
                //
                foreach($arrJobsFound as $job)
                {
                    $strTitleMatchScrubbed = \Scooper\strScrub($job['job_title'], FOR_LOOKUP_VALUE_MATCHING);
                    $strInterestedValue = "";

                    // We're going to check keywords for strict matches,
                    // but we should skip it if we're exact matching and we have multiple keywords, since
                    // that's not a possible match case.
                    if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_EQUAL_TITLE) && count($searchDetails['keyword_set']) >= 1)
                    {

                        if(strcasecmp($strTitleMatchScrubbed, $searchDetails['keyword_set'][0]) != 0)
                        {
                            $strInterestedValue = C__STR_TAG_NOT_EXACT_TITLE_MATCH__ . C__STR_TAG_AUTOMARKEDJOB__;
                        }
                    }
                    else if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_BE_IN_TITLE) && count($searchDetails['keyword_set']) >= 1)
                    {

                        // Check the different keywords against the job title.  If we got a
                        // match, leave that job record alone and move on.
                        //
//                        $arrScrubbedKeywords = $this->_getScrubbedKeywordSet_($searchDetails['keyword_set']);
                        $arrScrubbedSubterms = array();
                        foreach($searchDetails['keyword_set'] as $keywordTerm)
                        {
                            $arrKeywordSubterms = explode(" ", $keywordTerm);
                            $newSubTerms = $this->_getScrubbedKeywordSet_($arrKeywordSubterms);
                            foreach($newSubTerms as $newTerm)
                                $arrScrubbedSubterms[] = $newTerm;
                        }
                        $arrScrubbedSubterms = array_unique($arrScrubbedSubterms);

                        $nSubtermMatches = \Scooper\substr_count_array($strTitleMatchScrubbed, $arrScrubbedSubterms);

                        // If we found a match for any subterm in the list, then
                        // this was a true match and we should leave it as interested = blank
                        if($nSubtermMatches <= 0 && $nSubtermMatches != count($arrScrubbedSubterms))
                            $strInterestedValue =  C__STR_TAG_NOT_A_KEYWORD_TITLE_MATCH__. C__STR_TAG_AUTOMARKEDJOB__;
                        else
                            $strInterestedValue = null;

                    }
                    if(isset($strInterestedValue))
                        $arrJobsFound[$job['key_jobsite_siteid']]['interested'] = $strInterestedValue;
                }
            }
            else
            {
                $GLOBALS['logger']->logLine($searchDetails['key'] . " incorrectly set a keyword match type, but has no possible keywords.  Ignoring keyword_match_type request and returning all jobs.", \Scooper\C__DISPLAY_ERROR__);
            }
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
        $this->_setLocationValue_($strReturnLocation);
        $strReturnLocation = $this->getLocationValue();
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


        $strURL = str_ireplace("***NUMBER_DAYS***", $this->getDaysURLValue($GLOBALS['OPTS']['number_days']), $strURL );
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



