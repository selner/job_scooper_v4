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




class PluginCraigslist  extends ClassJobsSitePlugin
{
    protected $siteName = 'Craigslist';
    protected $nJobListingsPerPage = 100;
    protected $siteBaseURL = 'http://seattle.craigslist.org/';
    protected $strBaseURLFormat = "http://***LOCATION***.craigslist.org/search/jjj?s=***ITEM_NUMBER***&catAbb=jjj&query=***KEYWORDS***&srchType=T";
    protected $flagSettings = null;
    protected $typeLocationSearchNeeded = 'location-city';
    protected $strKeywordDelimiter = "|";

    function __construct($strBaseDir = null)
    {
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_MULTIPLE_KEYWORDS | C__JOB_LOCATION_REQUIRES_LOWERCASE | C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS;
        parent::__construct($strBaseDir);
    }

    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 0) { return 0; }

        return $nItem - 1;
    }

    function getDaysURLValue($days = null)
    {
        return VALUE_NOT_SUPPORTED;
    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $nodeHelper = new CSimpleHTMLHelper($objSimpHTML);

        $pageText = $nodeHelper->getText("span[class='pagenum']", 0, false);
        $arrItemItems = explode(" ", trim($pageText));
        if(!isset($arrItemItems) || !is_array($arrItemItems) || !(count($arrItemItems) >=5))
        {
            $GLOBALS['logger']->logLine("Unable to find count of listings for search on " . $this->siteName, \Scooper\C__DISPLAY_WARNING__);
            return 0;
        }
        else
        {
            return $arrItemItems[4];
        }
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