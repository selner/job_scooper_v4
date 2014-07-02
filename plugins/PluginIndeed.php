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
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');



class PluginIndeed extends ClassJobsSitePlugin
{
    protected $siteName = 'Indeed';
    protected $nJobListingsPerPage = 50;
    protected $siteBaseURL = 'http://www.Indeed.com';
    protected $strBaseURLFormat = "http://www.indeed.com/jobs?q=title%3A%28***KEYWORDS***%29&l=***LOCATION***&sort=date&limit=50&fromage=***NUMBER_DAYS***&start=***ITEM_NUMBER***";



    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 1) { return 0; }

        return $nItem;
    }

    function getDaysURLValue($nDays)
    {
        $ret = 1;
        switch($nDays)
        {
            case $nDays > 3 && $nDays <= 7:
                $ret = 7;
                break;

            case $nDays > 1 && $nDays <= 3:
                $ret = 3;
                break;

            default:
                // BUGBUG: Yesterday was giving me headaches, so switched "24 hours" to really mean last 3 days for Indeed
                $ret = 3;
                break;
        }
       return $ret;

    }

    function parseJobsListForPage($objSimpHTML)
    { return $this->_scrapeItemsFromHTML_($objSimpHTML); }


    function parseTotalResultsCount($objSimpHTML)
    {
        // # of items to parse
        $pageDiv= $objSimpHTML->find('div[id="searchCount"]');
        $pageDiv = $pageDiv[0];
        $pageText = $pageDiv->plaintext;
        $arrItemItems = explode(" ", trim($pageText));
        return $arrItemItems[5];
    }


    private function _scrapeItemsFromHTML_($objSimpleHTML)
    {
        $ret = null;


        $nodesJobs = $objSimpleHTML->find('div[class="row"]');


        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;



            $jobInfoNode = $node->firstChild()->firstChild();
            $item['job_title'] = $jobInfoNode->attr['title'];
            if($item['job_title'] == '') continue;

            $item['job_post_url'] = 'http://www.indeed.com' . $jobInfoNode->href;

            $arrURLParts = explode("jk=",  $item['job_post_url']);
            $item['job_id'] = \Scooper\strScrub($arrURLParts[1]);


            $item['company'] = trim($node->find("span[class='company'] span")[0]->plaintext);
            $item['location'] =trim( $node->find("span[class='location'] span")[0]->plaintext);
            $item['date_pulled'] = \Scooper\getTodayAsString();
            $item['job_site_date'] = $node->find("span[class='date']")[0]->plaintext;


            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}