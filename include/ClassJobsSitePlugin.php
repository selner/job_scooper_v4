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
require_once dirname(__FILE__) . '/Options.php';
require_once dirname(__FILE__) . '/ClassJobsSitePluginCommon.php';

const C__JOB_PAGECOUNT_NOTAPPLICABLE__ = -1;
const C__JOB_ITEMCOUNT_UNKNOWN__ = 11111;

//
// Jobs List Filter Functions
//
function isMarked_AutoDupe($var)
{
    if(substr_count($var['interested'], "No (Likely Duplicate") > 0) return true;

    return false;
}

function isMarked_Auto($var)
{
    $GLOBALS['CNT'][$var['interested']] +=1;
    if(substr_count($var['interested'], C__STR_TAG_AUTOMARKEDJOB__) > 0)
    {
        return true;
    };

    return false;
}

function isMarked_NotInterested($var)
{
    if(substr_count($var['interested'], "No ") > 0) return true;
    return false;
}

function isMarked_InterestedOrBlank($var)
{
    if((substr_count($var['interested'], "No ") > 0) || isMarked_AutoDupe($var) == true) return false;
    return true;
}

function isJobFilterable($var)
{
    $filterYes = false;

    if(isMarked_Auto($var) == true) $filterYes = true;
    if(isMarked_NotInterested($var) == true) $filterYes = true;

    return $filterYes;
}

function includeJobInFilteredList($var)
{
    return !(isJobFilterable($var));
}



abstract class ClassJobsSitePlugin extends ClassJobsSitePluginCommon
{
    protected $siteName = 'NAME-NOT-SET';
    protected $arrLatestJobs = null;
    protected $arrSearchesToReturn = null;
    protected $nJobListingsPerPage = 20;
    protected $flagAutoMarkListings = true; // All the called classes do it for us already

    function __construct($bitFlags = null, $strOutputDirectory = null)
    {
        $this->_bitFlags = $bitFlags;
        $this->setOutputFolder($strOutputDirectory);
    }

    function __destruct()
    {
        __debug__printLine("Closing ".$this->siteName." class instance.", C__DISPLAY_ITEM_START__);

        //
        // Write out the interim data to file if we're debugging
        //
        if($GLOBALS['DEBUG'] == true)
        {

            if($this->arrLatestJobs != null)
            {
                $strDebugFileName = $this->getMyOutputFileFullPath("debug");
                __debug__printLine("Writing ". $this->siteName." " .count($this->arrLatestJobs) ." job records to " . $strDebugFileName . " for debugging (if needed).", C__DISPLAY_ITEM_START__);
                $this->writeMyJobsListToFile($strDebugFileName, false);
            }
        }
    }


    abstract function parseJobsListForPage($objSimpHTML); // returns an array of jobs
    abstract function parseTotalResultsCount($objSimpHTML); // returns a settings array


    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function getMyJobsList() { return $this->arrLatestJobs; }


    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function loadMyJobsListFromCSVs($arrFilesToLoad)
    {
        $this->arrLatestJobs = $this->loadJobsListFromCSVs($arrFilesToLoad);
    }


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
        __debug__printLine("Downloading new ". $this->siteName ." jobs...", C__DISPLAY_ITEM_START__);

        //
        // Call the child classes getJobs function to update the object's array of job listings
        // and output the results to a single CSV
        //
        $this->getJobsForAllSearches($nDays);

        if($this->flagAutoMarkListings == true)
        {
            $this->markMyJobsList_withAutoItems();
        }
    }



    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */

    function getActualPostURL($strSrcURL)
    {
        $retURL = null;

        $classAPI = new APICallWrapperClass();
        __debug__printLine("Getting source URL for ". $strSrcURL , C__DISPLAY_ITEM_START__);

        try
        {
            $curlObj = $classAPI->cURL($strSrcURL);
            if($curlObj && !$curl_object['error_number'] && $curl_object['error_number'] == 0 )
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

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function is_IncludeBrief()
    {
        $val = $this->_bitFlags & C_EXCLUDE_BRIEF;
        $notVal = !($this->_bitFlags & C_EXCLUDE_BRIEF);
        // __debug__printLine('ExcludeBrief/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);
        return false;
    }

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function is_IncludeActualURL()
    {
        $val = $this->_bitFlags & C_EXCLUDE_GETTING_ACTUAL_URL;
        $notVal = !($this->_bitFlags & C_EXCLUDE_GETTING_ACTUAL_URL);
        // __debug__printLine('ExcludeActualURL/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);

        return !$notVal;
    }

/*    function getOutputFileFullPath($strFilePrefix = "", $strBase = "jobs", $strExtension = "csv")
    {
        return parent::getOutputFileFullPath($this->siteName . "_" . $strFilePrefix, $strBase, $strExtension);
    }
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
        return $this->writeJobsListToFile($strOutFilePath, $this->arrLatestJobs, true, false, $this->siteName);
    }


    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function _addJobsToMyJobsList_($arrAdd)
    {
        addJobsToJobsList($this->arrLatestJobs, $arrAdd);

    }

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function getJobsForAllSearches($nDays = -1)
    {
        foreach($this->arrSearchesToReturn as $search)
        {
            $strIncludeKey = 'include_'.strtolower($search['site_name']);

            if($GLOBALS['OPTS'][$strIncludeKey] == null || $GLOBALS['OPTS'][$strIncludeKey] == 0)
            {
                __debug__printLine($search['site_name'] . " excluded, so skipping its '" . $search['search_name'] . "' search.", C__DISPLAY_WARNING__);

                continue;
            }

            $class = null;
            $nLastCount = count($this->arrLatestJobs);
            __debug__printLine("Running ". $search['site_name'] . " search '" . $search['search_name'], C__DISPLAY_ITEM_START__);

            $strSite = strtolower($search['site_name']);
            if(strcasecmp($strSite, $this->siteName) == 0)
            {
                $this->getMyJobsForSearch($search, $nDays);
            }
        }
    }
    function getMyJobsForSearch($search, $nDays = -1)
    {
        $nItemCount = 1;
        $nPageCount = 1;

        $strURL = $this->_getURLfromBase_($search, $nDays, $nPageCount, $nItemCount);
        __debug__printLine("Getting count of " . $this->siteName ." jobs for search '".$search['search_name']. "': ".$strURL, C__DISPLAY_ITEM_DETAIL__);
        $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL );
        if(!$objSimpleHTML) throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL);

        $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML);
        if($strTotalResults == C__JOB_PAGECOUNT_NOTAPPLICABLE__)
        {
            $totalPagesCount = 1;
            $nTotalListings = C__JOB_ITEMCOUNT_UNKNOWN__ ; // placeholder because we don't know how many are on the page
        }
        else
        {
            $strTotalResults  = intval(str_replace(",", "", $strTotalResults));
            $nTotalListings = intval($strTotalResults);
            $totalPagesCount = intceil($nTotalListings  / $this->nJobListingsPerPage); // round up always
            if($totalPagesCount < 1)  $totalPagesCount = 1;
        }

        if($nTotalListings <= 0)
        {
            __debug__printLine("No new job listings were found on " . $this->siteName . " for search '" . $search['search_name']."'.", C__DISPLAY_ITEM_START__);
            return;
        }

        __debug__printLine("Downloading " . $totalPagesCount . " pages with ". ($nTotalListings == C__JOB_ITEMCOUNT_UNKNOWN__  ? "a not yet know number of" : $nTotalListings). " total jobs  " . $this->siteName . " for search '" . $search['search_name']."'.", C__DISPLAY_ITEM_START__);

        while ($nPageCount <= $totalPagesCount )
        {
            $arrPageJobsList = null;

            $objSimpleHTML = null;
            $strURL = $this->_getURLfromBase_($search, $nDays, $nPageCount, $nItemCount);
            __debug__printLine("Querying " . $this->siteName ." jobs: ".$strURL, C__DISPLAY_ITEM_START__);

            if(!$objSimpleHTML) $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL);
            if(!$objSimpleHTML) throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL);

            $arrPageJobsList = $this->parseJobsListForPage($objSimpleHTML);


            if(!is_array($arrPageJobsList))
            {
                // we likely hit a page where jobs started to be hidden.
                // Go ahead and bail on the loop here
                __debug__printLine("Not getting results back from ". $this->siteName . " starting on page " . $nPageCount.".  They likely have hidden the remaining " . $maxItem - $nPageCount. " pages worth. ", C__DISPLAY_ITEM_START__);
                $nPageCount = $totalPagesCount ;
            }
            else
            {
                $this->_addJobsToMyJobsList_($arrPageJobsList);
                $nItemCount += $this->nJobListingsPerPage;
            }

            // clean up memory
            $objSimpleHTML->clear();
            unset($objSimpleHTML);
            $nPageCount++;

        }

        __debug__printLine("Total of " . $nItemCount . " jobs were downloaded for " . $this->siteName . " search " . $search['search_name'] . " over " . $totalPagesCount . " pages.", C__DISPLAY_ITEM_START__);

    }

    function getDaysURLValue($days) { return ($days == null || $days == "") ? 1 : $days; } // default is to return the raw number
    function getItemURLValue($nItem) { return ($nItem == null || $nItem == "") ? 0 : $nItem; } // default is to return the raw number
    function getPageURLValue($nPage) { return ($nPage == null || $nPage == "") ? 0 : $nPage; } // default is to return the raw number

    function addSearchURL($site, $name, $fmtURL)
    {
        $this->addSearches(array('site_name' => $site, 'search_name' => $name, 'base_url_format' =>$fmtURL));

    }

    function addSearches($arrSearches)
    {
        foreach($arrSearches as $search)
        {
            $this->arrSearchesToReturn[] = $search;
        }
    }


    private function _getURLfromBase_($search, $nDays, $nPage, $nItem = null)
    {
        $strURL = $search['base_url_format'];
        $strURL = str_ireplace("***NUMBER_DAYS***", $this->getDaysURLValue($nDays), $strURL );
        $strURL = str_ireplace("***PAGE_NUMBER***", $nPage, $strURL );
        $strURL = str_ireplace("***ITEM_NUMBER***", $this->getItemURLValue($nItem), $strURL );
        return $strURL;
    }






}
