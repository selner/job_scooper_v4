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



class PluginGlassdoor extends ClassJobsSitePlugin
{
    protected $siteName = 'Glassdoor';
    protected $siteBaseURL = 'http://www.glassdoor.com';
    protected $flagSettings = null;
    protected $typeLocationSearchNeeded = 'location-city';

    function __construct($strBaseDir = null)
    {
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS | C__JOB_BASE_URL_FORMAT_REQUIRED;
        parent::__construct($strBaseDir);
    }

    function getDaysURLValue($days) {
        $ret = 1;

        if($days != null)
        {
            switch($days)
            {
                case ($days>1 && $days<=7):
                    $ret = 7;
                    break;


                case $days<=1:
                default:
                    $ret = 1;
                    break;

            }
        }

     return $ret;

    }

    public function getPageURLValue($nPage)
    {
        if($nPage == null || $nPage <= 1)
        {
            return "";
        }
        else
        {
            return "_IP".$nPage;
        }
    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $resultsSection= $objSimpHTML->find("div[class='results cell padLt'] h1[class='noMargTop']");
        if(isset($resultsSection) && isset($resultsSection[0]))
        {
            $totalItemsText = $resultsSection[0]->plaintext;
            $arrItemItems = explode(" ", trim($totalItemsText));
            $strTotalItemsCount = $arrItemItems[0];

            return str_replace(",", "", $strTotalItemsCount);
        }
        else
        {
            throw new ErrorException("Unable to parse results count for " . $this->siteName);
        }
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret=null;

        $nodesJobs = $objSimpHTML->find('div[class="jobScopeWrapper"]');


        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();

            $nodeHelper = new CSimpleHTMLHelper($node);
            $item['job_post_url'] = $nodeHelper->getProperty("a[class='jobLink']", 0, "href", false );
            $item['job_title'] = $nodeHelper->getText("a[class='jobLink']", 1, false );
            if(strlen($item['job_title']) <= 0) continue;

            // <a href="/partner/jobListing.htm?pos=115&amp;ao=29933&amp;s=58&amp;guid=000001453fb833deb3e300823643def8&amp;src=GD_JOB_AD&amp;t=SR&amp;extid=1&amp;exst=OL&amp;ist=&amp;ast=OL&amp;vt=w&amp;cb=1396933407969&amp;jobListingId=1008408496" rel="nofollow" class="jobLink" data-ja-clk="1" data-gd-view="1" data-ev-a="B-S"><tt class="notranslate"><strong>Director, Product</strong> Management</tt></a>
            $fIDMatch = preg_match("/jobListingId=([0-9]+)/", $item['job_post_url'], $arrIDMatches);
            if($fIDMatch) { $item['job_id'] = str_replace("jobListingId=", "", $arrIDMatches[0]); }

            $item['date_pulled'] = \Scooper\getTodayAsString();
            $item['job_site'] = $this->siteName;
            $item['company'] = $nodeHelper->getText("span[class='employerName']", 0, false );
            $item['location'] = $nodeHelper->getText("span[class='location'] span span span", 0, false );

            $item['job_site_date'] = $nodeHelper->getText("div[class='logo floatLt'] div[class='minor']", 0, false );
            if(strlen($item['job_site_date']) == 0)  { $item['job_site_date'] = "n/a (sponsored ad)";}


            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}