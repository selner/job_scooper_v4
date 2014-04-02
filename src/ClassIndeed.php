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
require_once dirname(__FILE__) . '/../include/scooter_utils_common.php';


class ClassIndeed extends ClassSiteExportBase
{
    protected $siteName = 'Indeed';


    function getJobs($strAlternateLocalHTMLFile = null)
    {
        $strSearch = 'http://www.indeed.com/jobs?q=title%3A%28%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22Chief+Technology+Officer%22%29&l=Seattle%2C+WA&sort=date&limit=50&fromage=1&start=';

        $arrJobs = $this->__getJobsFromSearch__($strSearch, 'Exec Keywords in Seattle, WA', $strAlternateLocalHTMLFile);

        $strOutFile = $this->getOutputFileFullPath();
        $this->writeJobsToCSV($strOutFile , $arrJobs );

        return $strOutFile ;


    }


    private function __getJobsFromSearch__($strBaseURL, $category,  $strAlternateLocalHTMLFile = null)
    {
        $arrAllJobs = array();
        $nItemCount = 1;

        $objSimpleHTML = $this->getSimpleObjFromPathOrURL($strAlternateLocalHTMLFile, $strBaseURL);

        $nItemChunkSize = 50;


        // # of items to parse
        $pageDiv= $objSimpleHTML->find('div[id="searchCount"]');
        $pageDiv = $pageDiv[0];
        $pageText = $pageDiv->plaintext;
        $arrItemItems = explode(" ", trim($pageText));
        $maxItem = $arrItemItems[5] / $nItemChunkSize;
        if($maxItem < 1)  $maxItem = 1;

        while ($nItemCount <= $maxItem)
        {
            $objSimpleHTML = null;
            $strURL = $strBaseURL.$nItemCount;
            __debug__printLine("Querying " . $this->siteName ." jobs: ".$strURL, C__DISPLAY_ITEM_START__);

            if(!$objSimpleHTML) $objSimpleHTML = parent::getSimpleObjFromPathOrURL($strAlternateLocalHTMLFile, $strURL);
            if(!$objSimpleHTML) throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$strAlternateLocalHTMLFile.') or '.$strURL);

            $arrNewJobs = $this->_scrapeItemsFromHTML_($objSimpleHTML, $category);

            $arrAllJobs = array_merge($arrAllJobs, $arrNewJobs);
            __debug__printLine("Querying " . $this->siteName ." jobs: ".$strURL, C__DISPLAY_ITEM_START__);

            $nItemCount += $nItemChunkSize;

            // clean up memory
            $objSimpleHTML->clear();
            unset($objSimpleHTML);

        }
        $this->arr = array_copy($arrAllJobs);

        return $arrAllJobs;
    }

    private function _scrapeItemsFromHTML_($objSimpleHTML, $category)
    {
        $ret = null;


        $nodesJobs = $objSimpleHTML->find('div[class="row"]');


        foreach($nodesJobs as $node)
        {
            $item = parent::getEmptyItemsArray();
            $item['job_id'] = $node->attr['id'];



            $jobInfoNode = $node->firstChild()->firstChild();
            $item['job_title'] = $jobInfoNode->attr['title'];
            if($item['job_title'] == '') continue;

            $item['job_post_url'] = 'http://www.indeed.com' . $jobInfoNode->href;
            $item['company'] = trim($node->find("span[class='company'] span")[0]->plaintext);
            $item['location'] =trim( $node->find("span[class='location'] span")[0]->plaintext);
            $item['date_pulled'] = parent::getPostDateString();
            $item['job_site_date'] = $node->find("span[class='date']")[0]->plaintext;

            if($this->is_IncludeBrief())
            {
                $item['brief_description'] = $node->find("span[class='summary']")[0]->plaintext;
            }
            $item[ 'script_search_key'] = $category;

            // calculate the original source
            $item['job_site'] = $this->siteName;
            $origSiteNode = $node->find("span[class='sdn']");
            if($origSiteNode && $origSiteNode[0])
            {
                $item['original_source'] = trim($origSiteNode[0]->plaintext);
            }
            if($this->is_IncludeActualURL())
            {
                $item['job_source_url'] = parent::getActualPostURL($item['job_post_url']);
            }

            $ret[] = $item;
//            $ret[ $item['job_site']."-".$item['job_id']] = $item;


        }

        return $ret;
    }

}