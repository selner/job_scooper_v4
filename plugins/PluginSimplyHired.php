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


if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');



class PluginSimplyHired extends ClassJobsSitePlugin
{
    protected $siteBaseURL = 'http://www.simplyhired.com';
    protected $siteName = 'SimplyHired';
    protected $nJobListingsPerPage = 50;
    protected $flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_MULTIPLE_KEYWORDS;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $strKeywordDelimiter = "or";
    protected $strBaseURLFormat = "http://www.simplyhired.com/search?q=***KEYWORDS***&l=***LOCATION***&fdb=***NUMBER_DAYS***&&ws=50&sb=dd&pn=***PAGE_NUMBER***";
    protected $strTitleOnlySearchKeywordFormat = "title:(%s)";

    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 1) { return 0; }

        return $nItem;
    }

    function getDaysURLValue($nDays)
    {
        $ret = 1;
        if($nDays > 1)
        {
            $ret = $nDays;
        }

        return $ret;
   }

    function parseJobsListForPage($objSimpHTML)
    { return $this->_scrapeItemsFromHTML_($objSimpHTML); }


    function parseTotalResultsCount($objSimpHTML)
    {
        // # of items to parse
        $pageDiv= $objSimpHTML->find('span[class="search_title"]');
        $pageDiv = $pageDiv[0];
        $pageText = $pageDiv->plaintext;
        $arrItemItems = explode(" ", trim($pageText));

        return $arrItemItems[4];
    }


    private function _scrapeItemsFromHTML_($objSimpleHTML)
    {
        $ret = null;


        $resultsSection= $objSimpleHTML->find('div[class="results"]');
        $resultsSection= $resultsSection[0];

        $nodesJobs = $resultsSection->find('ul[id="jobs"] li[class="result"]');


        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();
            $item['job_id'] = $node->attr['id'];

            $subNode = $node->find("a[class='title']");
            if(isset($subNode) && isset($subNode[0])) $item['job_title'] = $subNode[0]->plaintext;
            $item['job_post_url'] = 'http://www.simplyhired.com' . $subNode[0]->href;
            if($item['job_title'] == '') continue;


            $strURLAfterJobKey = str_replace("http://www.simplyhired.com/a/job-details/view/jobkey-", "", $item['job_post_url']);
            $arrURLRemainingParts = explode("/",  $strURLAfterJobKey);
            $item['job_id'] = str_replace(".", "", $arrURLRemainingParts[0]);
            $item['job_id'] = \Scooper\strScrub($item['job_id'], REPLACE_SPACES_WITH_HYPHENS);

            // TODO[BUGBUG] the h4 for company name can sometimes be missing.  the value is incorrectly set if so.
            $subNode = $node->find("h4[class='company']");
            if(isset($subNode) && isset($subNode[0])) $tempCompany = $subNode[0]->plaintext;
            if(isset($tempCompany) && strlen($tempCompany) > 0)
            {
                $item['company']= trim($tempCompany);
            }
            else
            {
                $subNode = $node->find("div[class='source']");
                if(isset($subNode) && isset($subNode[0])) $tempCompany = $subNode[0]->plaintext;
                if(isset($tempCompany) && strlen($tempCompany) > 0)
                {
                    $arrTempCompany = explode(" from ", $tempCompany);
                    if(count($arrTempCompany) > 1)
                    {
                        $item['company']= trim($arrTempCompany[1]);
                    }
                }
            }
            $subNode = $node->find("span[class='location']");
            if(isset($subNode) && isset($subNode[0])) $item['location'] = $subNode[0]->plaintext;

            $item['date_pulled'] = \Scooper\getTodayAsString();

            $subNode = $node->find("span[class='ago']");
            if(isset($subNode) && isset($subNode[0])) $item['job_site_date'] = $subNode[0]->plaintext;

            $item['job_site'] = $this->siteName;

            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}


?>
