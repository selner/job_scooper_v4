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
    protected $nJobListingsPerPage = 20;
    private $flagSettings = null;
    protected $strFilePath_HTMLFileDownloadScript = null;


    function __construct($strOutputDirectory = null)
    {
        if($strOutputDirectory != null)
        {
            $this->detailsMyFileOut = parseFilePath($strOutputDirectory, false);
        }

        $this->flagSettings = $GLOBALS['DATA']['site_plugins'][strtolower($this->siteName)]['flags'];

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

    function parseJobsListForPage($objSimpHTML) { return null; } // returns an array of jobs
    function parseTotalResultsCount($objSimpHTML) { return null; } // returns an array of jobs



    function getMyJobsList() { return $this->arrLatestJobs; }




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

        $this->markMyJobsList_withAutoItems();
    }


    function getActualPostURL($strSrcURL)
    {

        $retURL = null;

        $classAPI = new ClassScooperAPIWrapper();
        __debug__printLine("Getting source URL for ". $strSrcURL , C__DISPLAY_ITEM_START__);

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
        try
        {
            if($this->flagSettings & C__SEARCH_RESULTS_TYPE_XML__)
            {
                $this->getMyJobsForSearchFromXML($searchDetails, $nDays);
            }
            elseif($this->flagSettings & C__SEARCH_RESULTS_TYPE_HTML_FILE__)
            {
                $this->getMyJobsFromHTMLFiles($searchDetails, $nDays);
            }
            elseif($this->flagSettings & C__SEARCH_RESULTS_TYPE_WEBPAGE__)
            {
                $this->getMyJobsForSearchFromWebpage($searchDetails, $nDays);
            }
            else
            {
                throw new ErrorException("Class ". get_class($this) . " does not have a valid setting for parser.  Cannot continue.");
            }

        } catch (ErrorException $ex) {
            $strError = "Failed to download jobs from " . $this->siteName ." jobs for search '".$searchDetails['search_name']. "[URL=".$searchDetails['base_url_format']. "].   Reason:  ".$ex->getMessage();
            __debug__printLine($strError, C__DISPLAY_ERROR__);
            throw new ErrorException($strError);
        }
     }

    function getMyJobsForSearchFromXML($searchDetails, $nDays = -1)
    {

        ini_set("user_agent",C__STR_USER_AGENT__);
        ini_set("max_execution_time", 0);
        ini_set("memory_limit", "10000M");

        $nItemCount = 1;
        $nPageCount = 1;

        $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount);

            __debug__printLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['search_name']. "': ".$strURL, C__DISPLAY_ITEM_DETAIL__);

            $class = new ClassScooperAPIWrapper();
            $ret = $class->cURL($strURL, null, 'GET', 'text/xml; charset=UTF-8');
            $xmlResult = simplexml_load_string($ret['output']);

            if(!$xmlResult) throw new ErrorException("Error:  unable to get SimpleXML object for ".$strURL);
            $xmlResult->registerXPathNamespace("def", "http://www.w3.org/2005/Atom");

            if($this->flagSettings & C__JOB_PAGECOUNT_NOTAPPLICABLE__)
            {
                $totalPagesCount = 1;
                $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__  ; // placeholder because we don't know how many are on the page
            }
            else
            {
                $strTotalResults = $this->parseTotalResultsCount($xmlResult);
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

                __debug__printLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strURL, C__DISPLAY_ITEM_START__);

                while ($nPageCount <= $totalPagesCount )
                {
                    $arrPageJobsList = null;

                    $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount);
                    $class = new ClassScooperAPIWrapper();
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



    function getMyJobsForSearchFromWebpage($searchDetails, $nDays = -1)
    {

        $nItemCount = 1;
        $nPageCount = 1;


        $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount);
        __debug__printLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['search_name']. "': ".$strURL, C__DISPLAY_ITEM_DETAIL__);

        $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL );
        if(!$objSimpleHTML) { throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL); }

        if($this->flagSettings & C__JOB_PAGECOUNT_NOTAPPLICABLE__)
        {
            $totalPagesCount = 1;
            $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__  ; // placeholder because we don't know how many are on the page
        }
        else
        {
            $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML);
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

            __debug__printLine("Querying " . $this->siteName ." for " . $totalPagesCount . " pages with ". ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__   ? "an unknown number of" : $nTotalListings) . " jobs:  ".$strURL, C__DISPLAY_ITEM_START__);

            while ($nPageCount <= $totalPagesCount )
            {
                $arrPageJobsList = null;

                if($objSimpleHTML == null)
                {
                    $strURL = $this->_getURLfromBase_($searchDetails, $nDays, $nPageCount, $nItemCount);
                }
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
                $objSimpleHTML = null;
                $nPageCount++;

            }
        }

        __debug__printLine(PHP_EOL.$this->siteName . "[".$searchDetails['search_name']."]" .": " . $nItemCount . " jobs found." .PHP_EOL, C__DISPLAY_ITEM_RESULT__);

    }

    protected function getMyJobsFromHTMLFiles($searchDetails, $nDays = -1)
    {

        if($this->strFilePath_HTMLFileDownloadScript == null || strlen($this->strFilePath_HTMLFileDownloadScript) == 0)
        {
            throw new ErrorException("Cannot download client-side jobs HTML for " . $this->siteName . " because the " . get_class($this) . " plugin does not have an Applescript configured to call.");

        }


        $nPageCount = 1;

        $strFileKey = strtolower($this->siteName.'-'.$searchDetails['search_key']);
        $strFileBase = $this->detailsMyFileOut['directory'].$strFileKey. "-jobs-page-";

        $strURL = $this->_getURLfromBase_($searchDetails, $nDays);
        __debug__printLine("Getting count of " . $this->siteName ." jobs for search '".$searchDetails['search_name']. "': ".$strURL, C__DISPLAY_ITEM_DETAIL__);


        $strCmdToRun = "osascript " . __ROOT__ . "/plugins/".$this->strFilePath_HTMLFileDownloadScript . " " . escapeshellarg($this->detailsMyFileOut["directory"])  . " ".escapeshellarg($searchDetails["site_name"])." " . escapeshellarg($strFileKey)   . " '"  . $strURL . "'";
        __debug__printLine("Command = " . $strCmdToRun, C__DISPLAY_ITEM_DETAIL__);
        $result = my_exec($strCmdToRun);
        if($result['stdout'] == -1)
        {
            $strError = "AppleScript did not successfully download the jobs.  Log = ".$result['stderr'];
            __debug__printLine($strError, C__DISPLAY_ERROR__);
            throw new ErrorException($strError);
        }


        $strFileName = $strFileBase.".html";
        __debug__printLine("Parsing downloaded HTML files: '" . $strFileBase."*.html'", C__DISPLAY_ITEM_DETAIL__);
        while (file_exists($strFileName) && is_file($strFileName))
        {
            __debug__printLine("Parsing results HTML from '" . $strFileName ."'", C__DISPLAY_ITEM_DETAIL__);
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


    protected function _getURLfromBase_($searchDetails, $nDays, $nPage = null, $nItem = null)
    {
        $strURL = $searchDetails['base_url_format'];
        $strURL = str_ireplace("***NUMBER_DAYS***", $this->getDaysURLValue($nDays), $strURL );
        $strURL = str_ireplace("***PAGE_NUMBER***", $this->getPageURLValue($nPage), $strURL );
        $strURL = str_ireplace("***ITEM_NUMBER***", $this->getItemURLValue($nItem), $strURL );
        return $strURL;
    }

    function isJobListingMine($var)
    {
        if(substr_count($var['job_site'], strtolower($this->siteName)) > 0)
        {
            return true;
        }

        return false;
    }
}



?>
