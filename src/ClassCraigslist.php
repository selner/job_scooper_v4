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
require_once dirname(__FILE__) . '/../include/ClassJobsSiteBase.php';




class ClassCraigslist extends ClassJobsSiteBase
{
    protected $siteName = 'Craigslist';

    function getMyJobs($nDays = -1, $fIncludeFilteredJobsInResults = true)
    {
        if($nDays > 1)
        {
            __debug__printLine($this->siteName ." jobs can only be pulled for, at most, 1 day.  Ignoring number of days value and just pulling current listings.", C__DISPLAY_MOMENTARY_INTERUPPT__);

        }

        $strSearch = 'http://seattle.craigslist.org/search/jjj?catAbb=jjj&query=%22Vice%20President%22%20%7C%20%22Chief%20Technology%20Office%22%20%7C%20%22Chief%20Products%20Officer%22%20%7C%20%22CTO%22%20%7C%20%22CPO%22%20%7C%20%22VP%22%20%7C%20%22V.P.%22%20%7C%20%22Director%22%20%7C%20%20%22product%20management%22%20%7C%20%22general%20manager%22%20&srchType=T&s=';

        $this->__getMyJobsFromSearch__($strSearch, 'Exec Titles', $strAlternateLocalHTMLFile);

    }



    private function __getMyJobsFromSearch__($strBaseURL, $searchName = "", $strAlternateLocalHTMLFile = null)
    {
        $arrAllJobs = array();
        $nItemCount = 1;


        $objSimpleHTML = $this->getSimpleObjFromPathOrURL($strAlternateLocalHTMLFile, $strBaseURL);

        // # of items to parse
        $pageDiv= $objSimpleHTML->find('span[class="button pagenum"]');
        $pageDiv = $pageDiv[0];
        $pageText = $pageDiv->plaintext;
        $arrItemItems = explode(" ", trim($pageText));
        $maxItem = $arrItemItems[4];


        $objSimpleHTML->clear();
        unset($objSimpleHTML);


        $nItemChunkSize = 100;

        while ($nItemCount <= $maxItem)
        {
            $strURL = $strBaseURL.$nItemCount;
            __debug__printLine("Querying Craigslist jobs from ".$strURL, C__DISPLAY_ITEM_START__);

            if(!$objSimpleHTML)
            {
                $objSimpleHTML = $this->getSimpleObjFromPathOrURL($strAlternateLocalHTMLFile, $strBaseURL);
            }

            $arrNewJobs = $this->_scrapeItemsFromHTML_($objSimpleHTML, $searchName);

            $arrAllJobs = array_merge($arrAllJobs, $arrNewJobs);

            $nItemCount += $nItemChunkSize;

            // clean up memory
            $objSimpleHTML->clear();
            unset($objSimpleHTML);

        }

        $this->arrLatestJobs = array_copy($arrAllJobs);

    }

    private function _scrapeItemsFromHTML_($objSimpleHTML, $searchName)
    {

        $resultsSection= $objSimpleHTML->find('div[class="content"]');
        $resultsSection= $resultsSection[0];

        $nodesJobs = $resultsSection->find('p[class="row"]');
        foreach($nodesJobs as $node)
        {
            $item = parent::getEmptyItemsArray();

            $jobTitleLink = $node->find("span[class='pl'] a");
            $item['job_title'] = $jobTitleLink[0]->plaintext;
            if($item['job_title'] == '') continue;

            $item['url'] = 'http://seattle.craigslist.org/'.$jobTitleLink[0]->href;

            $item['job_id'] = $node->attr['data-pid'];
            $item['date_posted'] = $node->find("span[class='date']")[0]->plaintext;
            $item['location'] = $node->find("span[class='pnr']")[0]->plaintext;
            $item['job_site_category'] = $node->find("a[class='gc']")[0]->plaintext;
//             $item['original_source'] = $this->siteName;


            $ret[] = $item;
        }

        return $ret;
    }

} 