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




class PluginCraigslist  extends ClassJobsSitePlugin
{
    protected $siteName = 'Craigslist';
    protected $nJobListingsPerPage = 100;
    protected $siteBaseURL = 'http://seattle.craigslist.org/';
    protected $strBaseURLFormat = "http://***LOCATION***.craigslist.org/search/jjj?s=***ITEM_NUMBER***&catAbb=jjj&query=***KEYWORDS***&srchType=T";
    protected $flagSettings = null;
    protected $typeLocationSearchNeeded = 'location-city';
    protected $strKeywordDelimiter = "|";

    protected function _getLocationValueFromSettings_($settingsSet)
    {
        return parent::_getLocationValueFromSettings_($settingsSet, true);
    }

    function __construct($strBaseDir = null)
    {
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_MULTIPLE_KEYWORDS;
        parent::__construct($strBaseDir);
    }

    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 0) { return 0; }

        return $nItem - 1;
    }

    function getDaysURLValue($nDays)
    {
        if($nDays > 1)
        {
            $GLOBALS['logger']->logLine($this->siteName ." jobs can only be pulled for, at most, 1 day.  Ignoring number of days value and just pulling current listings.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
        }
        return VALUE_NOT_SUPPORTED;

    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $pageDiv= $objSimpHTML->find('span[class="pagenum"]');
        $pageDiv = $pageDiv[0];
        $pageText = $pageDiv->plaintext;
        $arrItemItems = explode(" ", trim($pageText));
        return $arrItemItems[4];
    }


     function parseJobsListForPage($objSimpleHTML)
    {
        $ret = null;
        $resultsSection= $objSimpleHTML->find('div[class="content"]');
        $resultsSection= $resultsSection[0];

        $nodesJobs = $resultsSection->find('p[class="row"]');
        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();

            $jobTitleLink = $node->find("span[class='pl'] a");
            $item['job_title'] = $jobTitleLink[0]->plaintext;
            if($item['job_title'] == '') continue;

            $item['job_post_url'] = $this->siteBaseURL.$jobTitleLink[0]->href;
            $item['date_pulled'] = \Scooper\getTodayAsString();

            $item['job_site'] = "Craigslist";
            $item['job_id'] = $node->attr['data-pid'];
            $item['job_site_date'] = $node->find("span[class='date']")[0]->plaintext;
            $item['location'] = str_replace("pic", "", $node->find("span[class='pnr']")[0]->plaintext);
            $item['job_site_category'] = $node->find("a[class='gc']")[0]->plaintext;


            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

} 