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
require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');


abstract class ClassJobsSitePlugin extends ClassJobsSitePluginCommon
{
    protected $siteName = 'NAME-NOT-SET';
    protected $arrLatestJobs = null;
    protected $arrSearchesToReturn = null;
    protected $arrSearchLocationSetsToRun = null;
    protected $nJobListingsPerPage = 20;
    protected $flagSettings = null;
    protected $strFilePath_HTMLFileDownloadScript = null;
    protected $strBaseURLFormat = null;
    protected $typeLocationSearchNeeded = null;
    protected $locationValue = null;
    protected $strKeywordDelimiter = null;

    function __construct($strOutputDirectory = null)
    {
        if($strOutputDirectory != null)
        {
            $this->detailsMyFileOut = \Scooper\parseFilePath($strOutputDirectory, false);
        }

//        if($GLOBALS['DATA']['site_plugins'][strtolower($this->siteName)]['flags'] > 0)
//        {
//            $this->flagSettings = $GLOBALS['DATA']['site_plugins'][strtolower($this->siteName)]['flags'];
//        }
//        else
//        {
//            print('class ' . get_class($this) . ' pre-set the flags'.PHP_EOL);
//        }

    }

    function __destruct()
    {

        //
        // Write out the interim data to file if we're debugging
        //
        if($GLOBALS['OPTS']['DEBUG'] == true)
        {
            if($this->arrLatestJobs != null)
            {
                $strOutPathWithName = $this->getOutputFileFullPath($this->siteName . "_");
                $GLOBALS['logger']->logLine("Writing ". $this->siteName." " .count($this->arrLatestJobs) ." job records to " . $strOutPathWithName . " for debugging (if needed).", \Scooper\C__DISPLAY_ITEM_START__);
                $this->writeMyJobsListToFile($strOutPathWithName, false);
            }
        }
        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }
    }

    function parseJobsListForPage($objSimpHTML) { return null; } // returns an array of jobs
    function parseTotalResultsCount($objSimpHTML) { return null; } // returns an array of jobs

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
    function downloadAllUpdatedJobs($nDays = -1)
    {
        $retFilePath = '';

        // Now go download and output the latest jobs from this site
        $GLOBALS['logger']->logLine("Downloading new ". $this->siteName ." jobs...", \Scooper\C__DISPLAY_ITEM_START__);

        //
        // Call the child classes getJobs function to update the object's array of job listings
        // and output the results to a single CSV
        //
        $this->getJobsForAllSearches($nDays);

        $this->markMyJobsList_withAutoItems();
    }


    function getActualPostURL($strSrcURL)
    {

        $retURL = null;

        $classAPI = new \Scooper\ScooperDataAPIWrapper();
        $GLOBALS['logger']->logLine("Getting source URL for ". $strSrcURL , \Scooper\C__DISPLAY_ITEM_START__);

        try
        {
            $curlObj = $classAPI->cURL($strSrcURL);
            if($curlObj && !$curlObj['error_number'] && $curlObj['error_number'] == 0 )
            {
                $retURL  =  $curlObj['actual_site_url'];
            }
        }
        catch(ErrorException $err)
        {
            // do nothing
        }
        return $retURL;
    }





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
        return $this->writeJobsListToFile($strOutFilePath, $this->arrLatestJobs, true, false, $this->siteName);
    }



    function _addJobsToMyJobsList_($arrAdd)
    {
        addJobsToJobsList($this->arrLatestJobs, $arrAdd);

    }


    function getJobsForAllSearches($nDays = -1)
    {

        foreach($this->arrSearchesToReturn as $search)
        {
            $strIncludeKey = 'include_'.strtolower($search['site_name']);

            if($GLOBALS['OPTS'][$strIncludeKey] == null || $GLOBALS['OPTS'][$strIncludeKey] == 0)
            {
                $GLOBALS['logger']->logLine($search['site_name'] . " excluded, so skipping its '" . $search['search_name'] . "' search.", \Scooper\C__DISPLAY_ITEM_START__);

                continue;
            }

            $class = null;
            $nLastCount = count($this->arrLatestJobs);

            $strSite = strtolower($search['site_name']);
            if(strcasecmp($strSite, strtolower($this->siteName)) == 0)
            {
                $this->getMyJobsForSearch($search, $nDays);
            }
        }
    }

    protected function getMyJobsForSearch($searchDetails, $nDays = -1)
    {
        /*
        $keywordsSettings = $this->arrSearchKeywordSetsToRun;
        if($keywordsSettings == null || count($keywordsSettings) == 0 || $this->_isBitFlagSet_(C__JOB_KEYWORD_PARAMETER_NOT_SUPPORTED))
        {
            $GLOBALS['logger']->logLine("Running ". $searchDetails['site_name'] . " search '" . $searchDetails['search_name'] ."' with no keyword settings...", \Scooper\C__DISPLAY_SECTION_START__);
            $this->_runLocationSetForKeywordsSet_($searchDetails, $nDays, null);
        }
        elseif($searchDetails['keywords'] != null && strlen($searchDetails['keywords']) == 0)
        */
        $this->_runSearchesForEachLocationSetting($searchDetails, $nDays);
    }


    protected function getJobsForSearchByType($searchDetails, $nDays, $locSingleSettingSet = null)
    {
        try {
            if($this->_isBitFlagSet_(C__JOB_SEARCH_RESULTS_TYPE_XML__))
            {
                $this->getMyJobsForSearchFromXML($searchDetails, $nDays, $locSingleSettingSet);
            }
            elseif($this->_isBitFlagSet_(C__JOB_SEARCH_RESULTS_TYPE_HTML_FILE__))
            {
                $this->getMyJobsFromHTMLFiles($searchDetails, $nDays, $locSingleSettingSet);
            }
            elseif($this->_isBitFlagSet_(C__JOB_SEARCH_RESULTS_TYPE_WEBPAGE__))
            {
                $this->getMyJobsForSearchFromWebpage($searchDetails, $nDays, $locSingleSettingSet);
            }
            else
            {
                throw new ErrorException("Class ". get_class($this) . " does not have a valid setting for parser.  Cannot continue.");
            }

        } catch (ErrorException $ex) {
            $strError = "Failed to download jobs from " . $this->siteName ." jobs for search '".$searchDetails['search_name']. "[URL=".$searchDetails['base_url_format']. "].  ".$ex->getMessage();
            $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
            if($GLOBALS['OPTS']['DEBUG'] == true) { throw new ErrorException( $strError); }
        }
    }


    protected function _runSearchesForEachLocationSetting($searchDetails, $nDays)
    {
        if($this->_isBitFlagSet_(C__JOB_LOCATION_PARAMETER_NOT_SUPPORTED))
        {
            $GLOBALS['logger']->logLine("Running ". $searchDetails['site_name'] . " search '" . $searchDetails['search_name'] ."' with no location settings..." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_START__);
            $this->getJobsForSearchByType($searchDetails, $nDays, null);
        }
        else
        {
            // Did the user specify an override at the search level in the INI?
            if($searchDetails != null && $searchDetails['location_keyword'] != null && strlen($searchDetails['location_keyword']) > 0)
            {
                $locSingleSettingSet = array('name' => 'location_keyword', 'search_location_override' => $searchDetails['location_keyword']);
                $this->getJobsForSearchByType($searchDetails, $nDays, $locSingleSettingSet);
            }
            elseif(($this->arrSearchLocationSetsToRun == null || count($this->arrSearchLocationSetsToRun) == 0) == true)
            {
                $GLOBALS['logger']->logLine("Skipping ". $searchDetails['site_name'] . " search '" . $searchDetails['search_name'] ."' because there was no location set...", \Scooper\C__DISPLAY_ITEM_RESULT__);
            }
            else
            {
                foreach($this->arrSearchLocationSetsToRun as $locSingleSettingSet)
                {
                    $GLOBALS['logger']->logLine("Running ". $searchDetails['site_name'] . " search '" . $searchDetails['search_name'] ."' for location settings set '" . $locSingleSettingSet['name'] . "'...", \Scooper\C__DISPLAY_ITEM_START__);

                    $this->getJobsForSearchByType($searchDetails, $nDays, $locSingleSettingSet);
                    $GLOBALS['logger']->logLine("Completed ". $searchDetails['site_name'] . " search '" . $searchDetails['search_name'] ."' for location settings set '" . $locSingleSettingSet['name'] . "'...", \Scooper\C__DISPLAY_ITEM_RESULT__);
                }
            }
        }
    }

    private function _checkInvalidURL_($details, $strURL)
    {
        if($strURL == null) throw new ErrorException("Skipping " . $this->siteName ." search '".$details['search_name']. "' because a valid URL could not be set.");
        return $strURL;
        // if($strURL == -1) $GLOBALS['logger']->logLine("Skipping " . $this->siteName ." search '".$details['search_name']. "' because a valid URL could not be set.");
    }

    protected function getMyJobsForSearchFromXML($searchDetails, $nDays = -1, $locSingleSettingSet=null)
    {

        ini_set("user_agent",C__STR_USER_AGENT__);
        ini_set("max_execution_time", 0);
        ini_set("memory_limit", "10000M");

        $nItemCount = 1;
        $nPageCount = 1;

            $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount, $locSingleSettingSet);
            if($this->_checkInvalidURL_($searchDetails, $strURL) == -1) return;

            $GLOBALS['logger']->logLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['search_name']. "': ".$strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

            $class = new \Scooper\ScooperDataAPIWrapper();
            $ret = $class->cURL($strURL, null, 'GET', 'text/xml; charset=UTF-8');
            $xmlResult = simplexml_load_string($ret['output']);

            if(!$xmlResult) throw new ErrorException("Error:  unable to get SimpleXML object for ".$strURL);
            $xmlResult->registerXPathNamespace("def", "http://www.w3.org/2005/Atom");

            if($this->_isBitFlagSet_(C__JOB_PAGECOUNT_NOTAPPLICABLE__))
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
                $GLOBALS['logger']->logLine("No new job listings were found on " . $this->siteName . " for search '" . $searchDetails['search_name']."'.", \Scooper\C__DISPLAY_ITEM_START__);
                return;
            }
            else
            {

                $GLOBALS['logger']->logLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strURL, \Scooper\C__DISPLAY_ITEM_START__);

                while ($nPageCount <= $totalPagesCount )
                {
                    $arrPageJobsList = null;

                    $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount, $locSingleSettingSet);
                    if($this->_checkInvalidURL_($searchDetails, $strURL) == -1) return;

                    $class = new \Scooper\ScooperDataAPIWrapper();
                    $ret = $class->cURL($strURL,'' , 'GET', 'application/rss+xml');

                    $xmlResult = simplexml_load_string($ret['output']);
                    if(!$xmlResult) throw new ErrorException("Error:  unable to get SimpleXML object for ".$strURL);

                    $arrPageJobsList = $this->parseJobsListForPage($xmlResult);


                    if(!is_array($arrPageJobsList))
                    {
                        // we likely hit a page where jobs started to be hidden.
                        // Go ahead and bail on the loop here
                        $GLOBALS['logger']->logLine("Not getting results back from ". $this->siteName . " starting on page " . $nPageCount.".  They likely have hidden the remaining " . $maxItem - $nPageCount. " pages worth. ", \Scooper\C__DISPLAY_ITEM_START__);
                        $nPageCount = $totalPagesCount ;
                    }
                    else
                    {
                        $this->_addJobsToMyJobsList_($arrPageJobsList);
                        $nItemCount += $this->nJobListingsPerPage;
                    }
                $nPageCount++;

            }

        }
        $GLOBALS['logger']->logLine($this->siteName . "[".$searchDetails['search_name']."]" .": " . $nItemCount . " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
    }



    protected function getMyJobsForSearchFromWebpage($searchDetails, $nDays = -1, $locSingleSettingSet=null)
    {

        $nItemCount = 1;
        $nPageCount = 1;


        $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount, $locSingleSettingSet);
        if($this->_checkInvalidURL_($searchDetails, $strURL) == -1) return;

        $GLOBALS['logger']->logLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['search_name']. "': ".$strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL );
        if(!$objSimpleHTML) { throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL); }

        if($this->_isBitFlagSet_(C__JOB_PAGECOUNT_NOTAPPLICABLE__))
        {
            $totalPagesCount = 1;
            $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__  ; // placeholder because we don't know how many are on the page
        }
        else
        {
            $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML);
            $strTotalResults  = intval(str_replace(",", "", $strTotalResults));
            $nTotalListings = intval($strTotalResults);
            $totalPagesCount = \Scooper\intceil($nTotalListings  / $this->nJobListingsPerPage); // round up always
            if($totalPagesCount < 1)  $totalPagesCount = 1;
        }

        if($nTotalListings <= 0)
        {
            $GLOBALS['logger']->logLine("No new job listings were found on " . $this->siteName . " for search '" . $searchDetails['search_name']."'.", \Scooper\C__DISPLAY_ITEM_START__);
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
                    $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount, $locSingleSettingSet);
                    if($this->_checkInvalidURL_($searchDetails, $strURL) == -1) return;


                }
                $GLOBALS['logger']->logLine("Getting jobs from ". $strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

                if(!$objSimpleHTML) $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL);
                if(!$objSimpleHTML) throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL);

                $arrPageJobsList = $this->parseJobsListForPage($objSimpleHTML);


                if(!is_array($arrPageJobsList))
                {
                    // we likely hit a page where jobs started to be hidden.
                    // Go ahead and bail on the loop here
                    $GLOBALS['logger']->logLine("Not getting results back from ". $this->siteName . " starting on page " . $nPageCount.".  They likely have hidden the remaining " . $maxItem - $nPageCount. " pages worth. ", \Scooper\C__DISPLAY_ITEM_START__);
                    $nPageCount = $totalPagesCount;
                }
                else
                {
                    $this->_addJobsToMyJobsList_($arrPageJobsList);
                    $nItemCount += $this->nJobListingsPerPage;
                }

                // clean up memory
                $objSimpleHTML->clear();
                unset($objSimpleHTML);
                $objSimpleHTML = null;
                $nPageCount++;

            }
        }

        $GLOBALS['logger']->logLine($this->siteName . "[".$searchDetails['search_name']."]" .": " . $nItemCount . " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    protected function getMyJobsFromHTMLFiles($searchDetails, $nDays = -1, $locSingleSettingSet=null)
    {

        if($this->strFilePath_HTMLFileDownloadScript == null || strlen($this->strFilePath_HTMLFileDownloadScript) == 0)
        {
            throw new ErrorException("Cannot download client-side jobs HTML for " . $this->siteName . " because the " . get_class($this) . " plugin does not have an Applescript configured to call.");

        }


        $nPageCount = 0;

        $strFileKey = strtolower($this->siteName.'-'.$searchDetails['search_key']);
        $strFileBase = $this->detailsMyFileOut['directory'].$strFileKey. "-jobs-page-";

        $strURL = $this->_getURLfromBase_($searchDetails, $nDays, null, null, $locSingleSettingSet);
        if($this->_checkInvalidURL_($searchDetails, $strURL) == -1) return;

        $GLOBALS['logger']->logLine("Exporting HTML from " . $this->siteName ." jobs for search '".$searchDetails['search_name']. "' to be parsed: ".$strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $strCmdToRun = "osascript " . __ROOT__ . "/plugins/".$this->strFilePath_HTMLFileDownloadScript . " " . escapeshellarg($this->detailsMyFileOut["directory"])  . " ".escapeshellarg($searchDetails["site_name"])." " . escapeshellarg($strFileKey)   . " '"  . $strURL . "'";
        $GLOBALS['logger']->logLine("Command = " . $strCmdToRun, \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $result = \Scooper\my_exec($strCmdToRun);
        if(\Scooper\intceil($result['stdout']) == -1)
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

            $arrNewJobs = $this->parseJobsListForPage($objSimpleHTML);

            $objSimpleHTML->clear();
            unset($objSimpleHTML);
            $objSimpleHTML = null;

            $this->_addJobsToMyJobsList_($arrNewJobs);

            $nPageCount++;

            $strFileName = $strFileBase.$nPageCount.".html";
        }



    }


    function getDaysURLValue($days) { return ($days == null || $days == "") ? 1 : $days; } // default is to return the raw number
    function getItemURLValue($nItem) { return ($nItem == null || $nItem == "") ? 0 : $nItem; } // default is to return the raw number
    function getPageURLValue($nPage) { return ($nPage == null || $nPage == "") ? "" : $nPage; } // default is to return the raw number
    function getKeywordURLValue($strKeywords)
    {
        if($this->_isBitFlagSet_(C__JOB_LOCATION_PARAMETER_NOT_SUPPORTED))
        {
            return -1;
        }
        return $strKeywords;
    }


    function getLocationURLValue($searchDetails, $locSettingSets = null)
    {
        $strReturnLocation = -1;

        if($this->_isBitFlagSet_(C__JOB_LOCATION_PARAMETER_NOT_SUPPORTED))
        {
            throw new ErrorException($this->siteName . " does not support the ***LOCATION*** replacement value in a base URL.  Please review and change your base URL format to remove the location value.  Aborting all searches for ". $this->siteName, \Scooper\C__DISPLAY_ERROR__);
        }

        // Did the user specify an override at the search level in the INI?
        if($searchDetails != null && $searchDetails['location_keyword'] != null && strlen($searchDetails['location_keyword']) > 0)
        {
            $strReturnLocation = $searchDetails['location_keyword'];
        }
        else
        {
            // No override, so let's see if the search settings have defined one for us
            $locTypeNeeded = $this->getLocationSettingType();
            if($locTypeNeeded == null || $locTypeNeeded == "")
            {
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Plugin for '" . $searchDetails['site_name'] ."' did not have the required location type of " . $locTypeNeeded ." set.   Skipping search '". $searchDetails['search_name'] . "' with settings '" . $locSettingSets['name'] ."'.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                return $strReturnLocation;
            }

            if($locSettingSets != null && count($locSettingSets) > 0 && $locSettingSets[$locTypeNeeded] != null)
            {
                $strReturnLocation = $locSettingSets[$locTypeNeeded];
            }

            if($strReturnLocation == null || $strReturnLocation == -1)
            {
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Plugin for '" . $searchDetails['site_name'] ."' did not have the required location type of " . $locTypeNeeded ." set.   Skipping search '". $searchDetails['search_name'] . "' with settings '" . $locSettingSets['name'] ."'.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
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

    function isJobListingMine($var)
    {
        if(substr_count($var['job_site'], strtolower($this->siteName)) > 0)
        {
            return true;
        }

        return false;
    }

    function getMySearches()
    {
        return $this->arrSearchesToReturn;
    }

    function addSearches($arrSearches, $locSettingSets = null, $keywordSet = null)
    {

        if(!is_array($arrSearches[0])) { $arrSearches[] = $arrSearches; }

        if($this->_isBitFlagSet_(C__JOB_KEYWORD_PARAMETER_NOT_SUPPORTED))
        {
            $this->addSearch($arrSearches[0], $locSettingSets);
        }
        else
        {

            $strCombinedKeywords = $this->getCombinedKeywordValueForSearches($keywordSet);
            foreach($arrSearches as $searchDetails)
            {
                if($searchDetails['keywords'] != null && strlen($searchDetails['keywords'])>0)
                {
                    $this->addSearch($searchDetails, $locSettingSets);
                }
                elseif($strCombinedKeywords == -1)
                {
                    $this->addSearch($searchDetails, $locSettingSets);
                }
                else
                {
                    $newCombinedSearch = $searchDetails;
                    $newCombinedSearch['keywords'] = $strCombinedKeywords;
                    $this->addSearch($newCombinedSearch, $locSettingSets);
                }
            }
        }
    }


    protected function addSearch($searchDetails, $locSettingSets = null)
    {
        //
        // Add the search to the list of ones to run
        //
        $this->arrSearchesToReturn[] = $searchDetails;

        $this->addSearchLocations($searchDetails, $locSettingSets);

    }

    protected function addSearchLocations($searchDetails, $locSettingSets = null)
    {
        //
        // Add the search locations to the list of ones to run
        //
        // If the search had location keywords, it overrides the locSettingSets
        //
        if($searchDetails['location_keyword'] != null && strlen($searchDetails['location_keyword']) > 0)
        {
            $locSingleSettingSet = array('name' => 'search_location_override', 'search_location_value_override' => $searchDetails['location_keyword']);
            $this->arrSearchLocationSetsToRun['search_location_override'] = $locSingleSettingSet;
        }
        else
        {
            $locTypeSupported = $this->getLocationSettingType();
            if($locTypeSupported != null && strlen($locTypeSupported) > 0 && $locSettingSets != null)
            {
                foreach($locSettingSets as $set)
                {
                    if($set[$locTypeSupported] != null)
                    {
                        $this->arrSearchLocationSetsToRun[$set['name']]['name'] = $set['name'];
                        $this->arrSearchLocationSetsToRun[$set['name']][$locTypeSupported] = $set[$locTypeSupported];
                    }
                }
            }
        }
    }


    protected function _getLocationValueFromSettings_($settingsSet, $fLowerCase = false)
    {
        $strReturnLocation = -1;

        if($settingsSet['search_location_override'] != null && strlen($settingsSet['search_location_override']) > 0)
        {
            $strReturnLocation = $settingsSet['search_location_override'];
        }
        else
        {

            $locTypeNeeded = $this->getLocationSettingType();
            if($settingsSet != null && count($settingsSet) > 0 && $settingsSet[$locTypeNeeded] != null)
            {
                $strReturnLocation = $settingsSet[$locTypeNeeded];
            }
        }
        if(!$this->_isValueURLEncoded_($strReturnLocation)) { $strReturnLocation = urlencode($strReturnLocation); }

        if($fLowerCase == true)
        {
            $strReturnLocation = strtolower($strReturnLocation);
        }
        return $strReturnLocation;
    }

    private function _isValueURLEncoded_($str)
    {
        if(strlen($str) <= 0) return 0;
        return (\Scooper\substr_count_array($str, array("%22", "&", "=", "+", "-", "%7C", "%3C" )) >0 );

    }

    protected function getCombinedKeywordValueForSearches($arrSearchKeywords)
    {
        $arrKeywords = array();

        if(count($arrSearchKeywords) == 1)
        {
            $arrKeywords[] = $arrSearchKeywords[0];
        }
        else
        {
            $arrKeywords = array_combine($arrKeywords, $arrSearchKeywords);
        }

        $strReturnKeywords = -1;

        if(!($this->_isBitFlagSet_(C__JOB_KEYWORD_MULTIPLE_TERMS_ALLOWED)) && count($arrKeywords) > 1)
        {
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine($this->siteName . " can only support a single keyword per search.  Skipping search with multiple keywords =" . var_export($arrKeywords, true), \Scooper\C__DISPLAY_ITEM_DETAIL__);

        }
        else
        {
            if(count($arrKeywords) == 1)
            {
                $strReturnKeywords = $arrKeywords[0];
            }
            else
            {
                if($this->strKeywordDelimiter == null)
                {
                    throw new ErrorException($this->siteName . " supports multiple keyword terms, but has not set the \$strKeywordDelimiter value in " .get_class($this). " Aborting search beacyse cannot create the URL.");
                }

                foreach($arrKeywords as $kywd)
                {
                    $strReturnKeywords = (strlen($strReturnKeywords) > 0 ? ' '. $this->strKeywordDelimiter .' ' : '') . '"' . $strReturnKeywords . '"';
                }
            }
            if(!$this->_isValueURLEncoded_($strReturnKeywords)) { $strReturnKeywords = urlencode($strReturnKeywords); }
            if($this->_isBitFlagSet_(C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES))
            {
                $strReturnKeywords = str_replace("%22", "-", $strReturnKeywords);
            }
        }
        return $strReturnKeywords;
    }

    protected function _getURLfromBase_($searchDetails, $nDays, $nPage = null, $nItem = null, $locSingleSettingSet=null)
    {
        $strURL = null;

        if(isset($searchDetails['base_url_format']))
        {
            $strURL = $searchDetails['base_url_format'];

        }
        elseif(isset($this->strBaseURLFormat))
        {
            $strURL = $this->strBaseURLFormat;
        }
        else
        {
            throw new ErrorException("Could not find base URL format for " . $this->siteName . ".  Aborting all searches for ". $this->siteName, \Scooper\C__DISPLAY_ERROR__);
        }

        $strURL = str_ireplace("***NUMBER_DAYS***", $this->getDaysURLValue($nDays), $strURL );
        $strURL = str_ireplace("***PAGE_NUMBER***", $this->getPageURLValue($nPage), $strURL );
        $strURL = str_ireplace("***ITEM_NUMBER***", $this->getItemURLValue($nItem), $strURL );
        if(!$this->_isBitFlagSet_(C__JOB_KEYWORD_PARAMETER_NOT_SUPPORTED))
        {
            $keywordValue = $this->getKeywordURLValue($searchDetails['keywords']);
            if($keywordValue == null)
            {
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Search '" . $searchDetails['search_name'] ."' did not include a required keyword string value.  Skipping search...", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                $strURL = -1;
            }
            else
            {
                $strURL = str_ireplace("***KEYWORDS***", $keywordValue, $strURL );
            }
        }

        if(!$this->_isBitFlagSet_(C__JOB_LOCATION_PARAMETER_NOT_SUPPORTED))
        {
            $strLocationValue = $this->_getLocationValueFromSettings_($locSingleSettingSet);
            if($strLocationValue == null)
            {
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Search settings '" . $locSingleSettingSet['name'] ."' did not have the required location type of " . $this->getLocationSettingType() ." set.  Skipping search '". $searchDetails['search_name'] . "' with settings '" . $locSingleSettingSet['name'] ."'.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                $strURL = -1;
            }
            $strURL = str_ireplace("***LOCATION***", $strLocationValue, $strURL);
        }

        if($strURL == null) { throw new ErrorException("Location value is required for " . $this->siteName . ", but was not set for the search '" . $searchDetails['name'] ."'.". " Aborting all searches for ". $this->siteName, \Scooper\C__DISPLAY_ERROR__); }

        return $strURL;
    }

    private function _isBitFlagSet_($flagToCheck)
    {
        $ret = ($this->flagSettings & $flagToCheck);
        if($ret == $flagToCheck) { return true; }
        return false;
    }

}



?>
