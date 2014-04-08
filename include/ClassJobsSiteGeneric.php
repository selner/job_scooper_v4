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


require_once dirname(__FILE__) . '/ClassJobsSite.php';



abstract class ClassJobsSiteGeneric extends ClassJobsSite
{
    protected $siteName = 'UNKNOWN';
    protected $arrSearchesToReturn = null;
    protected $nJobListingsPerPage = 20;

    abstract function parseJobsListForPage($objSimpHTML); // returns an array of jobs
    abstract function parseTotalResultsCount($objSimpHTML); // returns a settings array


    function __construct()
    {
        parent::__construct(null, $this->getMyBitFlags());
        $this->_addUserOptionFlag_();
        $this->_addToSitesList_();
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


    function getMyJobs($strAlternateLocalHTMLFile = null, $fIncludeFilteredJobsInResults = true)
    {
        $this->getJobsForAllSearches($nDays, $fIncludeFilteredJobsInResults);
    }


    function getJobsForAllSearches($nDays = -1, $fIncludeFilteredJobsInResults = true)
    {

        foreach($this->arrSearchesToReturn as $search)
        {
            $class = null;
            $nLastCount = count($this->arrLatestJobs);
            __debug__printLine("Running search " . $search['search_name'] . " against site ". $search['site_name'], C__DISPLAY_ITEM_DETAIL__);

            switch(strtolower($search['site_name']))
            {
                case 'glassdoor':
                    $class = new ClassGlassdoor();
                    break;

                case 'indeed':
                    $class = new ClassIndeed();
                    break;

                case 'simplyhired':
                    $class = new ClassSimplyHired();
                    break;

                case 'porch':
                    $class = new ClassPorchJobs();
                    break;


                case 'craigslist':
                    $class = new ClassCraigslist();
                    break;

                default:
                    throw new ErrorException("ERROR: Search '" . $search['search_name'] . " listed an unknown job site value.  Cannot continue.  Exiting.");
                    break;

            }

            $class->getMyJobsForSearch($search, $nDays, $fIncludeFilteredJobsInResults);
            $this->_addJobsToList_($class->getMyJobsList());
            // var_dump($this->siteName . " count loop end -- adding ".count($class->getMyJobsList()) . " to the previous " . $nLastCount ." so allJobs now = " . count($this->arrLatestJobs));
        }

    }

    function getMyJobsForSearch($search, $nDays = -1, $fIncludeFilteredJobsInResults = true)
    {
        $nItemCount = 1;
        $nPageCount = 1;

        $strURL = $this->_getURLfromBase_($search, $nDays, $nPageCount, $nItemCount);
        __debug__printLine("Getting count of " . $this->siteName ." jobs for search '".$search['search_name']. "': ".$strURL, C__DISPLAY_ITEM_DETAIL__);
        $objSimpleHTML = $this->getSimpleObjFromPathOrURL(null, $strURL );
        if(!$objSimpleHTML) throw new ErrorException("Error:  unable to get SimpleHTML object for ".$strURL);

        $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML);
        $strTotalResults  = intval(str_replace(",", "", $strTotalResults));
        $nTotalListings = intval($strTotalResults);
        $totalPagesCount = intceil($nTotalListings  / $this->nJobListingsPerPage); // round up always
        if($totalPagesCount < 1)  $totalPagesCount = 1;


        if($nTotalListings <= 0)
        {
            __debug__printLine("No new job listings were found on " . $this->siteName . " for search '" . $search['search_name']."'.", C__DISPLAY_ITEM_START__);
            return;
        }

        __debug__printLine("Downloading " . $totalPagesCount . " pages with ".$nTotalListings. " total jobs  " . $this->siteName . " for search '" . $search['search_name']."'.", C__DISPLAY_ITEM_START__);

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
                $this->_addJobsToList_($arrPageJobsList);
                $nItemCount += $this->nJobListingsPerPage;
            }

            // clean up memory
            $objSimpleHTML->clear();
            unset($objSimpleHTML);
            $nPageCount++;

        }

        __debug__printLine("Total of " . $nItemCount . " jobs were downloaded for " . $this->siteName . " search " . $search['search_name'] . " over " . $totalPagesCount . " pages.", C__DISPLAY_ITEM_START__);

    }


    private function _addUserOptionFlag_()
    {

        $arrUserOptions = $GLOBALS['OPTS_SETTINGS'];

        $arrUserOptions['include_'.strtolower($this->siteName)] = array(
                'description'   => 'Include ' .strtolower($this->siteName) . ' in the results list.' ,
                'default'       => 0,
                'type'          => Pharse::PHARSE_INTEGER,
                'required'      => false,
                'short'      => strtolower($this->siteName)
            );

        $GLOBALS['OPTS_SETTINGS'] = $arrUserOptions;
    }

   private function _addToSitesList_()
   {
       $arrSupportedSites = $GLOBALS['SITES_SUPPORTED'];

       $arrSupportedSites[$this->siteName] = array('site_name' => $this->siteName, 'include_in_run' => false, 'working_subfolder' => 'working_folder');

       $GLOBALS['SITES_SUPPORTED'] = $arrSupportedSites;

   }



}

