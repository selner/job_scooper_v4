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
require_once(__ROOT__.'/scooper_common/APICallWrapperClass.php');


const C__SEARCH_RESULTS_TYPE_HTML__ = 1;
const C__SEARCH_RESULTS_TYPE_XML__ = 4;
const C__SEARCH_RESULTS_TYPE_HTML_FILE__= 10;

abstract class ClassJobsSitePlugin extends ClassJobsSitePluginCommon
{
    protected $flagValidClassConstruction = false;
    protected $siteName = 'NAME-NOT-SET';
    protected $arrLatestJobs = null;
    protected $arrSearchesToReturn = null;
    protected $nJobListingsPerPage = 20;
    protected $flagAutoMarkListings = true; // All the called classes do it for us already

    function __construct($bitFlags = null, $strOutputDirectory = null)
    {
        if($bitFlags == null && $strOutputDirectory == null)
        {
            $this->flagValidClassConstruction = false;
        }
        else
        {
            $this->_bitFlags = $bitFlags;
            $this->setOutputFolder($strOutputDirectory);
           $this->flagValidClassConstruction = true;
        }
    }

    function __destruct()
    {
        __debug__printLine("Closing ".$this->siteName." instance of class " . get_class($this), C__DISPLAY_ITEM_START__);

        //
        // Write out the interim data to file if we're debugging
        //
        if($GLOBALS['OPTS']['DEBUG'] == true)
        {
            if($this->arrLatestJobs != null)
            {
                $strOutPathWithName = $this->getOutputFileFullPath($this->siteName . "_");
                __debug__printLine("Writing ". $this->siteName." " .count($this->arrLatestJobs) ." job records to " . $strOutPathWithName . " for debugging (if needed).", C__DISPLAY_ITEM_START__);
                $this->writeMyJobsListToFile($strOutPathWithName, false);
            }
        }
    }

    function checkIsValid()
    {
        if(!$this->flagValidClassConstruction)
            throw new ErrorException(get_class($this) . " was not constructed with valid parameters to be executed.  Aborting.");
    }

    abstract function parseJobsListForPage($objSimpHTML); // returns an array of jobs
    abstract function parseTotalResultsCount($objSimpHTML); // returns a settings array




    function getMyJobsList() { $this->checkIsValid(); return $this->arrLatestJobs; }



    function loadMyJobsListFromCSVs($arrFilesToLoad)
    {
        $this->checkIsValid();
        $arrAllJobsLoadedFromSrc = $this->loadJobsListFromCSVs($arrFilesToLoad);


        // These will be used at the beginning and end of
        // job processing to filter out jobs we'd previous seen
        // and to make sure our notes get updated on active jobs
        // that we'd seen previously
        //
        //
        // Set a global var with an array of all input cSV jobs marked new or not marked as excluded (aka "Yes" or "Maybe")
        //
        $GLOBALS['active_jobs_from_input_source_files'] = array_filter($arrAllJobsLoadedFromSrc, "isMarked_InterestedOrBlank");

        //
        // Set a global var with an array of all input CSV jobs that are not in the first set (aka marked Not Interested & Not Blank)
        //
        $GLOBALS['inactive_jobs_from_input_source_files'] = array_filter($arrAllJobsLoadedFromSrc, "isMarked_NotInterestedAndNotBlank");

        //
        // Initialize the run's jobs list with all the jobs we'd previously set as inactive.
        //
        $this->arrLatestJobs =  $GLOBALS['inactive_jobs_from_input_source_files'];
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
        $this->checkIsValid();
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


    function getActualPostURL($strSrcURL)
    {
        $this->checkIsValid();

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


    function is_IncludeBrief()
    {
        $this->checkIsValid();

        $val = $this->_bitFlags & C_EXCLUDE_BRIEF;
        $notVal = !($this->_bitFlags & C_EXCLUDE_BRIEF);
        // __debug__printLine('ExcludeBrief/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);
        return false;
    }


    function is_IncludeActualURL()
    {
        $this->checkIsValid();

        $val = $this->_bitFlags & C_EXCLUDE_GETTING_ACTUAL_URL;
        $notVal = !($this->_bitFlags & C_EXCLUDE_GETTING_ACTUAL_URL);
        // __debug__printLine('ExcludeActualURL/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);

        return !$notVal;
    }


    function getMyOutputFileFullPath($strFilePrefix = "")
    {
        $this->checkIsValid();
        return parent::getOutputFileFullPath($this->siteName . "_" . $strFilePrefix, "jobs", "csv");
    }

    function markMyJobsList_withAutoItems()
    {
        $this->checkIsValid();
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
        $this->checkIsValid();
        return $this->writeJobsListToFile($strOutFilePath, $this->arrLatestJobs, true, false, $this->siteName);
    }



    function _addJobsToMyJobsList_($arrAdd)
    {
        $this->checkIsValid();
        addJobsToJobsList($this->arrLatestJobs, $arrAdd);

    }


    function getJobsForAllSearches($nDays = -1)
    {
        $this->checkIsValid();

        foreach($this->arrSearchesToReturn as $search)
        {
            $strIncludeKey = 'include_'.strtolower($search['site_name']);

            if($GLOBALS['OPTS'][$strIncludeKey] == null || $GLOBALS['OPTS'][$strIncludeKey] == 0)
            {
                __debug__printLine($search['site_name'] . " excluded, so skipping its '" . $search['search_name'] . "' search.", C__DISPLAY_ITEM_START__);

                continue;
            }

            $class = null;
            $nLastCount = count($this->arrLatestJobs);
            __debug__printLine("Running ". $search['site_name'] . " search '" . $search['search_name'] ."'...", C__DISPLAY_SECTION_START__);

            $strSite = strtolower($search['site_name']);
            if(strcasecmp($strSite, $this->siteName) == 0)
            {
                $this->getMyJobsForSearch($search, $nDays);
            }
        }
    }

    public function getMyJobsForSearch($searchDetails, $nDays = -1)
    {
        $this->checkIsValid();
        switch($GLOBALS['site_plugins'][strtolower($searchDetails['site_name'])]['results_type'])
        {
            case C__SEARCH_RESULTS_TYPE_XML__:
            $this->getMyJobsForSearchFromXML($searchDetails, $nDays);
            break;

            case C__SEARCH_RESULTS_TYPE_HTML_FILE__:
            $this->getMyJobsFromHTMLFiles($searchDetails, $nDays);
            break;

            default:
            $this->getMyJobsForSearchFromWebpage($searchDetails, $nDays);
        }
    }

    function getMyJobsForSearchFromXML($searchDetails, $nDays = -1)
    {
        $this->checkIsValid();

        ini_set("user_agent",C__STR_USER_AGENT__);
        ini_set("max_execution_time", 0);
        ini_set("memory_limit", "10000M");

        $nItemCount = 1;
        $nPageCount = 1;

        try
        {
            $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount);
            __debug__printLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['search_name']. "': ".$strURL, C__DISPLAY_ITEM_DETAIL__);

            $class = new APICallWrapperClass();
            $ret = $class->cURL($strURL, null, 'GET', 'text/xml; charset=UTF-8');
            $xmlResult = simplexml_load_string($ret['output']);

            if(!$xmlResult) throw new ErrorException("Error:  unable to get SimpleXML object for ".$strURL);
            $xmlResult->registerXPathNamespace("def", "http://www.w3.org/2005/Atom");
        }
        catch (ErrorException $ex)
        {
            throw new ErrorException("Error:  unable to getMyJobsForSearchFromXML from ".$strURL. " Reason:".$ex->getMessage());
            return;
        }
        $strTotalResults = $this->parseTotalResultsCount($xmlResult);
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
            __debug__printLine("No new job listings were found on " . $this->siteName . " for search '" . $searchDetails['search_name']."'.", C__DISPLAY_ITEM_START__);
            return;
        }
        else
        {

            __debug__printLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__JOB_ITEMCOUNT_UNKNOWN__  ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strURL, C__DISPLAY_ITEM_START__);

            while ($nPageCount <= $totalPagesCount )
            {
                $arrPageJobsList = null;

                $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount);
                $class = new APICallWrapperClass();
                $ret = $class->cURL($strURL,'' , 'GET', 'application/rss+xml');

                $xmlResult = simplexml_load_string($ret['output']);
                if(!$xmlResult) throw new ErrorException("Error:  unable to get SimpleXML object for ".$strURL);

                $arrPageJobsList = $this->parseJobsListForPage($xmlResult);


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
                $nPageCount++;

            }

        }
        __debug__printLine(PHP_EOL.$this->siteName . "[".$searchDetails['search_name']."]" .": " . $nItemCount . " jobs found." .PHP_EOL, C__DISPLAY_ITEM_RESULT__);

    }

    function getMyJobsForSearchFromWebpage($search, $nDays = -1)
    {
        $this->checkIsValid();

        $nItemCount = 1;
        $nPageCount = 1;

        try
        {
            $strURL = $this->_getURLfromBase_($search, $nDays, $nPageCount, $nItemCount);
            __debug__printLine("Getting count of " . $this->siteName ." jobs for search '".$search['search_name']. "': ".$strURL, C__DISPLAY_ITEM_DETAIL__);

            $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL );
            if(!$objSimpleHTML) throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL);
        }
        catch (ErrorException $ex)
        {
            throw new ErrorException("Error:  unable to getMyJobsForSearch from ".$strURL. " Reason:".$ex->getMessage());
            return;
        }
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
        else
        {

            __debug__printLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__JOB_ITEMCOUNT_UNKNOWN__  ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strURL, C__DISPLAY_ITEM_START__);

            while ($nPageCount <= $totalPagesCount )
            {
                $arrPageJobsList = null;

                $objSimpleHTML = null;
                $strURL = $this->_getURLfromBase_($search, $nDays, $nPageCount, $nItemCount);

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
        }

        __debug__printLine(PHP_EOL.$this->siteName . "[".$search['search_name']."]" .": " . $nItemCount . " jobs found." .PHP_EOL, C__DISPLAY_ITEM_RESULT__);

    }

    protected function getMyJobsFromHTMLFiles($strCompanyName, $searchDetails = null)
    {
        $this->checkIsValid();

        $nItemCount = 1;
        $dataFolder = $this->strOutputFolder ;

        if($searchDetails)
        {
            $strFileBase = strtolower($strCompanyName).'-'.$searchDetails['search_name'] . "-jobs-page-";
        }
        else
        {
            $strFileBase = strtolower($strCompanyName). "-jobs-page-";
        }

        $strFileName = $dataFolder . $strFileBase.$nItemCount.".html";
        if(!is_file($strFileName)) // try the current folder instead
        {
            $dataFolder = "./";
            $strFileName = $dataFolder . $strFileBase.$nItemCount.".html";
        }

        if(!is_file($strFileName)) // last try the debugging data folder
        {
            $dataFolder = C_STR_DATAFOLDER;
            $strFileName = $dataFolder . $strFileBase.$nItemCount.".html";
        }

        while (file_exists($strFileName) && is_file($strFileName))
        {
            $objSimpleHTML = $this->getSimpleHTMLObjForFileContents($strFileName);
            if(!$objSimpleHTML)
            {
                throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$filePath.') or '.$strURL);
            }

            $arrNewJobs = $this->parseJobsListForPage($objSimpleHTML);

            $objSimpleHTML->clear();
            unset($objSimpleHTML);

            $this->_addJobsToMyJobsList_($arrNewJobs);

            $nItemCount++;

            $strFileName = $dataFolder . $strFileBase.$nItemCount.".html";

        }


    }
    /**  NOT YET TESTED AND INTEGRATED
    //
    // Parses a relative date string such as "5 hrs ago" or "22 days ago"
    // and returns a date string representing the actual date (i.e. "2014-05-15")
    // the relative string represents.
    function getDateFromRelativeDateString($strDaysPast, $fReturnNullForFailure = false)
    {
    $nRetNumber = null;
    $strRetUnit = null;
    $nRetDays = null;

    //
    // First, let's break the string into it's words
    //
    $arrDateStringWords = explode(" ", $strDaysPast);

    if(count($arrDateStringWords) <= 1) return null; // we don't know enough to parse the value

    // Let's see if the first item is numeric
    if(is_string($arrDateStringWords[0]) && is_numeric($arrDateStringWords[0]))
    {
    $nRetNumber = floatval($arrDateStringWords[0]);

    switch ($arrDateStringWords[1])
    {
    case "hrs":
    case "hr":
    case "hours":
    case "hour":
    $strRetUnit = "hours";
    $nRetDays = intceil($nRetNumber / 24);  // divide to get number of days and then round up
    break;

    case "d":
    case "days":
    case "day":
    $strRetUnit = "hours";
    $nRetDays = intceil($nRetNumber);  // divide to get number of days
    break;

    default:
    return null;  // we don't know what this is so return null
    break;
    }
    }

    if($strRetUnit != null && $strRetUnit != "")
    {
    $now = new DateTime();
    $retDate = $now->sub(new DateInterval('P'.$nRetDays.'D')); // P1D means a period of 1 day
    return $retDate->format('Y-m-d');
    }

    //
    // If we were told to return null on failure, return null.
    //
    if($fReturnNullForFailure == true)
    {
    return null;
    }

    //
    // Return the input string if we weren't told to return null on failure
    //
    return $strDaysPast;

    }
     **/

    function getDaysURLValue($days) { return ($days == null || $days == "") ? 1 : $days; } // default is to return the raw number
    function getItemURLValue($nItem) { return ($nItem == null || $nItem == "") ? 0 : $nItem; } // default is to return the raw number
    function getPageURLValue($nPage) { return ($nPage == null || $nPage == "") ? 0 : $nPage; } // default is to return the raw number

    function addSearchURL($site, $name, $fmtURL)
    {
        $this->addSearches(array('site_name' => $site, 'search_name' => $name, 'base_url_format' =>$fmtURL));

    }

    function getMySearches()
    {
        return $this->arrSearchesToReturn;
    }

    function addSearches($arrSearches)
    {
        if(!is_array($arrSearches[0])) { $arrSearches[] = $arrSearches; }
        foreach($arrSearches as $searchDetails)
        {
            $this->addSearch($searchDetails);
        }
    }

    function addSearch($arrSearch)
    {
            $this->arrSearchesToReturn[] = $arrSearch;
    }


    protected function _getURLfromBase_($search, $nDays, $nPage, $nItem = null)
    {
        $strURL = $search['base_url_format'];
        $strURL = str_ireplace("***NUMBER_DAYS***", $this->getDaysURLValue($nDays), $strURL );
        $strURL = str_ireplace("***PAGE_NUMBER***", $nPage, $strURL );
        $strURL = str_ireplace("***ITEM_NUMBER***", $this->getItemURLValue($nItem), $strURL );
        return $strURL;
    }






}

?>
