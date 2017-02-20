<?php
/**
 * Copyright 2014-17 Bryan Selner
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
//    protected $strBaseURLFormat = "https://www.indeed.com/jobs?q=***KEYWORDS***&l=***LOCATION***&radius=50&sort=date&limit=50&fromage=***NUMBER_DAYS***&filter=0***ITEM_NUMBER***";
//    protected $strBaseURLFormat = "https://www.indeed.com/jobs?as_and=***KEYWORDS***&as_phr=&as_any=&as_not=&as_ttl=&as_cmp=&jt=all&st=&salary=&radius=50&l=***LOCATION***&fromage=***NUMBER_DAYS***&limit=50&sort=date&psf=advsrch&start=***ITEM_NUMBER***";
    protected $strBaseURLFormat = "https://www.indeed.com/jobs?q=***KEYWORDS***&l=***LOCATION***&radius=50&sort=date&limit=50&fromage=***NUMBER_DAYS***&filter=0=***ITEM_NUMBER***";

    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $strKeywordDelimiter = "OR";
    protected $additionalFlags = [C__JOB_IGNORE_MISMATCHED_JOB_COUNTS];


    function __construct($strBaseDir = null)
    {
        // Note:  C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS intentioanlly not set although Indeed supports it.  However, their support is too explicit of a search a will weed out
        //        too many potentia hits to be worth it.
        parent::__construct($strBaseDir);
    }

//    protected function _getBaseURLFormat_($searchDetails = null)
//    {
//        $strURL = parent::_getBaseURLFormat_($searchDetails);
//        if(\Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_BE_IN_TITLE) || \Scooper\isBitFlagSet($searchDetails['user_setting_flags'], C__USER_KEYWORD_MUST_EQUAL_TITLE))
//        {
//            $strURL = $strURL . "&as_ttl=***KEYWORDS***&l=***LOCATION***";
//        }
//        else
//        {
//            $strURL = $strURL . "&q=***KEYWORDS***&l=***LOCATION***";
//        }
//
//        return $strURL;
//    }

    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 1) { return ""; }

/*        return "&start=" . $nItem. "&pp=";*/
        return "&start=" . $nItem;
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
                $ret = "any";
                break;
        }
       return $ret;

    }
//
//    function parseJobsListForPage($objSimpHTML)
//    { return $this->_scrapeItemsFromHTML_($objSimpHTML); }


    function parseTotalResultsCount($objSimpHTML)
    {
        $nodeHelper = new CSimpleHTMLHelper($objSimpHTML);

        $pageText = $nodeHelper->getText("div[id='searchCount']", 0, false);
        $fMatchedID = preg_match('/.*?of\s*(\d+).*?/', $pageText, $idMatches);
        if($fMatchedID && count($idMatches) >= 1)
        {
            return $idMatches[1];
        }
    }


    protected function parseJobsListForPage($objSimpleHTML)
    {
        $ret = null;
        $cntNode = $objSimpleHTML->find("div[id='searchCount']");
        if(isset($cntNode) && count($cntNode) >= 1)
        {
            $GLOBALS['logger']->logLine("Processing records: " . $cntNode[0]->plaintext);
        }

        $nodesJobs = $objSimpleHTML->find('td[id=\'resultsCol\'] div[data-tn-component=\'organicJob\']');
        foreach($nodesJobs as $node)
        {

            if(!array_key_exists('itemtype', $node->attr))
            {
                $GLOBALS['logger']->logLine("Skipping job node without itemtype attribute; likely a sponsored and therefore not an organic search result.", \Scooper\C__DISPLAY_MOMENTARY_INTERUPPT__);
                continue;
            }
            assert($node->attr['itemtype'] == "http://schema.org/JobPosting");

            $item = $this->getEmptyJobListingRecord();

            if(isset($node) && isset($node->attr['data-jk']))
                $item['job_id'] = $node->attr['data-jk'];

            $subNodes = $node->find("a[data-tn-element='jobTitle']");
            if(isset($subNodes) && array_key_exists('title', $subNodes[0]->attr)) {
                $item['job_title'] = $subNodes[0]->attr['title'];
                $item['job_post_url'] = $subNodes[0]->attr['href'];
            }
//
//            if(is_null($item['job_id']) || empty($item['job_id'])) {
//                $id = $this->getIDFromLink('\/jobs\/.{1,}-(\w+).*', $item['job_post_url']);
//                if($id !== false && !is_null($id))
//                    $item['job_id'] = $id;
//            }




            $coNode = $node->find("span[itemprop='hiringOrganization']");
            if(isset($coNode) && count($coNode) >= 1)
            {
                $item['company'] = combineTextAllChildren($coNode[0]);
            }

            $locNode= $node->find("span[itemprop='addressLocality']");
            if(isset($locNode) && count($locNode) >= 1)
            {
                $item['location'] = $locNode[0]->plaintext;
            }
            $dateNode = $node->find("span[class='date']");
            if(isset($dateNode ) && count($dateNode ) >= 1)
            {
                $item['job_site_date'] = $dateNode[0]->plaintext;
                if(strcasecmp(trim($item['job_site_date']), "Just posted") == 0)
                    $item['job_site_date'] = getTodayAsString();
            }

            if($item['job_title'] == '') continue;
            $ret[] = $this->normalizeJobItem($item);

        }

        return $ret;
    }

}