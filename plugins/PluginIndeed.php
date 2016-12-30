<?php
/**
 * Copyright 2014-16 Bryan Selner
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
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');


class PluginIndeed extends ClassBaseServerHTMLJobSitePlugin
{
    protected $siteName = 'Indeed';
    protected $nJobListingsPerPage = 50;
    protected $siteBaseURL = 'http://www.Indeed.com';
    protected $strBaseURLFormat = "http://www.indeed.com/jobs?as_cmp=&jt=all&st=&salary=&radius=50&&fromage=***NUMBER_DAYS***&limit=50&sort=date&psf=advsrch&start=50&pp=***ITEM_NUMBER***&filter=0";
    protected $flagSettings = null;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $strKeywordDelimiter = "OR";


    function __construct($strBaseDir = null)
    {
        // Note:  C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS intentioanlly not set although Indeed supports it.  However, their support is too explicit of a search a will weed out
        //        too many potentia hits to be worth it.
        parent::__construct($strBaseDir);
    }

    protected function _getBaseURLFormat_($searchDetails = null)
    {
        $strURL = parent::_getBaseURLFormat_($searchDetails);
        if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_BE_IN_TITLE) || \Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_EQUAL_TITLE))
        {
            $strURL = $strURL . "&as_ttl=***KEYWORDS***&l=***LOCATION***";
        }
        else
        {
            $strURL = $strURL . "&q=***KEYWORDS***&l=***LOCATION***";
        }

        return $strURL;
    }

    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 1) { return 0; }

        return $nItem;
    }

    function getDaysURLValue($nDays = null)
    {

        switch($nDays)
        {
            case $nDays > 15:
                $ret = "any";
                break;

            case $nDays > 7 && $nDays <= 15:
                $ret = 15;
                break;

            case $nDays > 3 && $nDays <= 7:
                $ret = 7;
                break;

            case $nDays > 1 && $nDays <= 3:
                $ret = 3;
                break;

            case $nDays = 1:
                $ret = 1;
                break;

            default:
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
            $GLOBALS['logger']->logLine("Unable to find count of listrings for search on " . $this->siteName, \Scooper\C__DISPLAY_WARNING__);
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


            // BUGBUG:  does not get ID from this valid URL  http://www.indeed.com/cmp/IEH-Laboratories/jobs/PT-Accounting-Intern-79cb387002268752?r=1&fccid=e6c6c781cbff7bd3
            $item['job_id'] = preg_replace(REXPR_MATCH_URL_DOMAIN, "", $item['job_post_url']);
            $arrURLParts = explode("jk=",  $item['job_id']);
            if(isset($arrURLParts) && is_array($arrURLParts) && count($arrURLParts) >=2)
            {
                $item['job_id'] = \Scooper\strScrub($arrURLParts[1]);
            }
            else
            {
                $item['job_id'] = preg_replace('/\/(company|cmp)\//', "", $item['job_id']);
                $item['job_id'] = preg_replace('/\?.*$/', "", $item['job_id']);
                $item['job_id'] = \Scooper\strScrub(str_replace("/jobs/", "", $item['job_id']));
            }

            $subNode = $node->find("span[class='company'] span");
            if(isset($subNode) && isset($subNode[0])) $item['company'] = $subNode[0]->plaintext;

            $subNode = $node->find("span[class='location'] span");
            if(isset($subNode) && isset($subNode[0])) $item['location'] = $subNode[0]->plaintext;

            $subNode = $node->find("span[class='date']");
            if(isset($subNode) && isset($subNode[0])) $item['job_site_date'] = $subNode[0]->plaintext;

            $item['date_pulled'] = getTodayAsString();


            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}