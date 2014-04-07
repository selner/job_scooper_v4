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


require_once dirname(__FILE__) . '/../include/ClassJobsSite.php';

/*  TODO -- IN PROGRESS
class ClassJobsSiteGeneric extends ClassJobsSite
{
    protected $siteName = 'Generic';

    private $arrSitesSupported = array(
      'Porch' => array('site_name' => "Porch", "base_url" => "http://about.porch.com/careers/", 'get_pagecount_callback' => '_getNumPorchPages', 'scrape_jobs_list_callback' => '_getJobsFromPorchPage'),

    );

    private $_siteThisInstance_ = null;

    function setSite($strName)
    {
        $this->_siteThisInstance_ = $this->arrSitesSupported[$strName];
    }

    function getMyJobs($nDays = -1, $fIncludeFilteredJobsInResults = true)
    {


        //        Keywords: title:("vice president" or VP or director or CTO or CPO or director or "chief product officer" or "product management" or "general manager" or "Chief Technology Officer")
        //  Location: Seattle Washington (25 miles)
        //  Filters: Last 24 hours, Full time


        if($nDays > 1)
        {
            $strSearch = "http://www.simplyhired.com/search?t=%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22product+management%22+or+%22general+manager%22+or+%22Chief+Technology+Officer%22&lc=Seattle&ls=WA&fdb=".$nDays."&ws=50&sb=dd&pn=";
            // __debug__printLine("Getting " . $nDays . " days of postings from " . $this->siteName ." jobs: ".$strURL, C__DISPLAY_ITEM_START__);
        }
        else
        {
            $strDays = $nDays < 1 ? "24 hours" : $nDays;
            $strSearch = 'http://www.simplyhired.com/search?t=%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22product+management%22+or+%22general+manager%22+or+%22Chief+Technology+Officer%22&lc=Seattle&ls=WA&fdb=1&ws=50&sb=dd&pn=';
        }

        $this->__getMyJobsFromSearch__($strSearch, 'Exec Keywords in Seattle, WA', $strAlternateLocalHTMLFile);

    }


    private function __handleCallback__($callback, &$val)
    {

        if ($callback && is_callable($callback))
        {
            call_user_func_array($callback, array(&$val));
        }
    }


    private function __getMyJobsFromSearch__($strBaseURL, $category, $strAlternateLocalHTMLFile = null)
    {
        $arrAllJobs = array();

        $arrRetPageCounts = $this->__handleCallback__($this->_siteThisInstance_['get_pagecount_callback'], $this->_siteThisInstance_);

        $nPageCount = 1;
        $maxPages = $arrRetPageCounts['number_pages'];
        $nItemChunkSize = $arrRetPageCounts['item_chunksize'];
        $totalItems = $arrRetPageCounts['total_items'];

        $objSimpleHTML = $this->getSimpleObjFromPathOrURL($strAlternateLocalHTMLFile, $strBaseURL);
        if(!$objSimpleHTML) throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$strAlternateLocalHTMLFile.') or '.$strBaseURL);

        $maxItem = intval($totalItems / $nItemChunkSize);
        if($maxItem < 1)  $maxItem = 1;

        __debug__printLine("Downloading " . $maxItem . " pages of ".$totalItems  . " jobs from " . $this->siteName , C__DISPLAY_ITEM_START__);


        while ($nPageCount <= $maxItem)
        {
            $objSimpleHTML = null;
            $strURL = $strBaseURL.$nPageCount;
            __debug__printLine("Querying " . $this->siteName ." jobs: ".$strURL, C__DISPLAY_ITEM_START__);

            if(!$objSimpleHTML) $objSimpleHTML = parent::getSimpleObjFromPathOrURL($strAlternateLocalHTMLFile, $strURL);
            if(!$objSimpleHTML) throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$strAlternateLocalHTMLFile.') or '.$strURL);

            $arrNewJobs = $this->__handleCallback__($this->_siteThisInstance_['scrape_jobs_list_callback'], $objSimpleHTML);

            if(!is_array($arrNewJobs))
            {
                // we likely hit a page where jobs started to be hidden.
                // Go ahead and bail on the loop here
                __debug__printLine("Not getting results back from SimplyHired starting on page " . $nPageCount.".  They likely have hidden the remaining " . $maxItem - $nPageCount. " pages worth. ", C__DISPLAY_ITEM_START__);
                $nPageCount = $maxItem;
            }
            else
            {
                $arrAllJobs = array_merge($arrAllJobs, $arrNewJobs);

                $nItemCount += $nItemChunkSize;
            }

            // clean up memory
            $objSimpleHTML->clear();
            unset($objSimpleHTML);
            $nPageCount++;

        }

        $this->arrLatestJobs = array_copy($arrAllJobs);
    }

    static function _getJobsFromPorchPage($objSimpleHTML)
    {
        $ret = null;


        $resultsSection= $objSimpleHTML->find('div[id="job-listings"]');
        $resultsSection= $resultsSection[0];

        $nodesJobs = $resultsSection->find('div[class="cell"]');

//        var_dump('found ' . count($nodesJobs) . ' nodes');

        foreach($nodesJobs as $node)
        {
            $item = parent::getEmptyItemsArray();
            $item['job_id'] = $node->attr['id'];

            $item['job_title'] = $node->find("h4")[0]->plaintext;
            if($item['job_title'] == '') continue;

            $item['job_post_url'] = 'http://about.porch.com/careers/' . $node->find("a")[0]->href;
            $item['job_source_url'] = $item['job_post_url'];
            $item['company']= 'Porch'
            $item['location'] =trim( $node->find("span[class='location']")[0]->plaintext);
            $item['date_pulled'] = $this->getTodayAsString();

            $ret[] = $item;
        }

        return $ret;
    }

    static function _getNumPorchPages($objSimpleHTML)
    {
        $arrAllJobs = array();
        $nPageCount = 1;
        $nItemChunkSize = 50;

        $objSimpleHTML = $this->getSimpleObjFromPathOrURL($strAlternateLocalHTMLFile, $strBaseURL);
        if(!$objSimpleHTML) throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$strAlternateLocalHTMLFile.') or '.$strBaseURL);

        // # of items to parse
        $pageDiv= $objSimpleHTML->find('span[class="search_title"]');
        $pageDiv = $pageDiv[0];
        $pageText = $pageDiv->plaintext;
        $arrItemItems = explode(" ", trim($pageText));
        $totalItems = $arrItemItems[4];
        $totalItems  = intval(str_replace(",", "", $totalItems));
        $maxItem = intval($totalItems / $nItemChunkSize);
        if($maxItem < 1)  $maxItem = 1;


        return array("total_items"=>-1, "number_pages"=>1, "max_pages"=>1,  "item_chunksize"=>1);
    }

}

*/