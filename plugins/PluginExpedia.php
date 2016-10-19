<?php

    /**
     * Copyright 2014-15 Bryan Selner
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
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');


//.// http://expediajobs.findly.com/candidate/job_search/advanced/results?job_type=5517&state=2336&country=5492&sort=date


class PluginExpedia extends ClassJobsSitePlugin
{
    protected $siteName = 'Expedia';
    protected $siteBaseURL = 'https://expedia.wd5.myworkdayjobs.com/search/jobs/';
    protected $strBaseURLFormat = 'https://expedia.wd5.myworkdayjobs.com/search/jobs/';
    protected $nJobListingsPerPage = 100;

    function __construct($strBaseDir = null)
    {
        parent::__construct($strBaseDir);
        $this->flagSettings =  C__JOB_BASETYPE_WEBPAGE_FLAGS |C__JOB_SETTINGS_URL_VALUE_REQUIRED | C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED | C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED | C__JOB_USE_SELENIUM | C__JOB_INFSCROLL_DOWNFULLPAGE;
    }


    function getDaysURLValue($days = null) {
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


    function parseTotalResultsCount($objSimpHTML)
    {
        $resultsSection= $objSimpHTML->find("span[class='GF34SVYCIUH'] span");
        $totalItemsText = $resultsSection[0]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = $arrItemItems[0];

        return str_replace(",", "", $strTotalItemsCount);
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $parent = $objSimpHTML->find('div[class="GF34SVYCOUH.GF34SVYCAUH"]');

        $nodesJobs= $parent[0]->find('li');
        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();
            $item['company'] = 'Expedia';
            $item['job_site'] = $item['company'];

            $titleLink = $node->find("h3 a")[0];
            $item['job_title'] = $titleLink->plaintext;
            $item['job_post_url'] = $titleLink->href;
            if($item['job_title'] == '') continue;

            $item['job_id'] = str_replace(array("(", ")"), "", $node->find("h3 small")[0]->plaintext);

            $item['job_post_url'] = $this->siteBaseURL . $node->find("a")[0]->href;
            $item['date_pulled'] = \Scooper\getTodayAsString();
            $item['location'] = preg_replace("/(\s{2,})/", " ", $node->find("p[class='search-result-item-company-name']")[0]->plaintext, -1);
//            $item['brief'] = $node->find("p[class='search-result-item-description']")[0]->plaintext;
//           $item['brief'] = str_ireplace(array("Position Description ", "position overview", "PositionSummary"), "", $item['brief']);

            $item['job_site_date'] = str_replace("date posted: ","", $node->find("span[class='search-result-item-post-date']")[0]->plaintext);
            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}