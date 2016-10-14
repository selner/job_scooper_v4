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
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');


// TODO:  Make abstract class to power sites like http://www.careerbuilder.com/jobs/greenbay,wisconsin/category/engineering/?channel=en&siteid=gagbp037&sc_cmp1=JS_Sub_Loc_EN&lr=cbga_gbp
// just have to add the following terms per site &siteid=gagbp037&lr=cbga_gbp

class PluginCareerBuilder extends ClassJobsSitePlugin
{
    protected $siteName = 'CareerBuilder';
    protected $siteBaseURL = "http://www.careerbuilder.com/jobs--***KEYWORDS***-in-***LOCATION***?radius=30&siteid=cbnsv&pay=0&emp=JTFT&page_number=***PAGE_NUMBER***&posted=***NUMBER_DAYS***";
    protected $flagSettings = null;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';


    function __construct($strDir = null)
    {
        parent::__construct($strDir);
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS | C__JOB_SETTINGS_URL_VALUE_REQUIRED | C__JOB_USE_SELENIUM;

    }

    function getDaysURLValue($days = null) {
        $ret = "yesterday";

        if($days != null)
        {
            switch($days)
            {
                case ($days>7):
                    $ret = "30";
                    break;

                case ($days>3 && $days<=7):
                    $ret = "7";
                    break;

                case ($days>=3 && $days<7):
                    $ret = "3";
                    break;


                case $days<=1:
                default:
                    $ret = "";
                    break;

            }
        }

        return $ret;

    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $this->nJobListingsPerPage = 25;

        $resultsSection= $objSimpHTML->find("div[class='count']");
        $totalItemsText = $resultsSection[0]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = $arrItemItems[0];

        $strTotalItemsCount = str_replace("(", "", $strTotalItemsCount);
        $strTotalItemsCount = str_replace(")", "", $strTotalItemsCount);

        return $strTotalItemsCount;
   }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;



        $nodesJobs= $objSimpHTML->find('div[class="job-row"]');

        foreach($nodesJobs as $node)
        {
//            if(isset($node->attr) && isset($node->attr['class']) && strcasecmp($node->attr['class'], "jl_even_row prefRow") != 0 &&
//                strcasecmp($node->attr['class'], "jl_odd_row prefRow") != 0)
//            {
//                continue;
//            }
            $item = $this->getEmptyJobListingRecord();

            $titleLink = $node->find("h2 a");
            $item['job_title'] = $titleLink[0]->plaintext;
            $item['job_post_url'] = $titleLink[0]->attr["href"];
            $item['job_id'] = $titleLink[0]->attr["data-job-did"];
            $item['job_site'] = $this->siteName;

            if($item['job_title'] == '') continue;

            $nodeEmploymentInfo = $node->find("div[class=row job-information]");


            $subNode = $nodeEmploymentInfo[0]->find("div[class=columns end large-2 medium-3 small-12] h4");
            if($subNode && count($subNode)>=1)
                $item['location'] = trim($subNode[0]->plaintext);

            $subNode = $nodeEmploymentInfo[0]->find("div[class=columns large-2 medium-3 small-12] h4");
            if($subNode && count($subNode)>=1)
                $item['company'] = trim($subNode[0]->plaintext);

            $item['date_pulled'] = \Scooper\getTodayAsString();

            $subNode = $node->find("div[class='show-for-medium-up'] em");
            if(isset($subNode) && isset($subNode[0]))
                $item['job_site_date'] = trim($subNode[0]->plaintext);

            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}