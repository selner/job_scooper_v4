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


class PluginIndeed extends ClassJobsSitePlugin
{
    protected $siteName = 'Indeed';
    protected $nJobListingsPerPage = 50;
    protected $siteBaseURL = 'http://www.Indeed.com';
    protected $strBaseURLFormat = "http://www.indeed.com/jobs?q=***KEYWORDS***&l=***LOCATION***&sort=date&limit=50&fromage=***NUMBER_DAYS***&start=***ITEM_NUMBER***";
    protected $flagSettings = null;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $strKeywordDelimiter = "OR";
    protected $strTitleOnlySearchKeywordFormat = "title:%s";


    function __construct($strBaseDir = null)
    {
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_MULTIPLE_KEYWORDS | C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS;
        parent::__construct($strBaseDir);
    }

    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 1) { return 0; }

        return $nItem;
    }

    function getDaysURLValue($nDays)
    {

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
        $nodeHelper = new CSimpleHTMLHelper($objSimpHTML);

        $pageText = $nodeHelper->getText("div[id='searchCount']", 0, false);
        $arrItemItems = explode(" ", trim($pageText));
        if(!isset($arrItemItems) || !is_array($arrItemItems) || !(count($arrItemItems) >=6))
        {
            $GLOBALS['logger']->logLine("Unable to find count of listings for search on " . $this->siteName, \Scooper\C__DISPLAY_WARNING__);
            return 0;
        }
        else
        {
            return $arrItemItems[5];
        }
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
            if(isset($jobInfoNode) && isset($jobInfoNode->attr['title'])) $item['job_title'] = $jobInfoNode->attr['title'];
            if($item['job_title'] == '') continue;

            $item['job_post_url'] = 'http://www.indeed.com' . $jobInfoNode->href;

            $arrURLParts = explode("jk=",  $item['job_post_url']);
            if(isset($arrURLParts) && is_array($arrURLParts) && count($arrURLParts) >=2)
            {
                $item['job_id'] = \Scooper\strScrub($arrURLParts[1]);
            }


            $subNode = $node->find("span[class='company'] span");
            if(isset($subNode) && isset($subNode[0])) $item['company'] = $subNode[0]->plaintext;

            $subNode = $node->find("span[class='location'] span");
            if(isset($subNode) && isset($subNode[0])) $item['location'] = $subNode[0]->plaintext;

            $subNode = $node->find("span[class='date']");
            if(isset($subNode) && isset($subNode[0])) $item['job_site_date'] = $subNode[0]->plaintext;

            $item['date_pulled'] = \Scooper\getTodayAsString();


            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}