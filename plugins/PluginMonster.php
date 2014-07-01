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




class PluginMonster extends ClassJobsSitePlugin
{
    protected $siteName = 'Monster';
    protected $siteBaseURL = 'http://jobsearch.monster.com';


    function getDaysURLValue($days) {
        $ret = "yesterday";

        if($days != null)
        {
            switch($days)
            {
                case ($days>3 && $days<=7):
                    $ret = "Last-7-Days";
                    break;

                case ($days>=3 && $days<7):
                    $ret = "Last-3-Days";
                    break;


                case $days<=1:
                default:
                    $ret = "yesterday";
                    break;

            }
        }

        return $ret;

    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $resultsSection= $objSimpHTML->find("div[id='resultsCountHeader']");
        $totalItemsText = $resultsSection[0]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = $arrItemItems[0];

        return str_replace(",", "", $strTotalItemsCount);
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('table[class="listingsTable"] tr');


        foreach($nodesJobs as $node)
        {
            if(strcasecmp($node->attr['class'], "even") != 0 &&
                strcasecmp($node->attr['class'], "odd") != 0)
            {
                    continue;
            }
            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;

            $titleLink = $node->find("div div a")[0];


            $objDiv = $node->find("div[class='socialContainer']");
            $item['job_id'] = $objDiv[0]->attr['data-jobid'];

            $item['job_title'] = $titleLink->plaintext;

            $testLink = $objSimpHTML->find("a[id='ctl00_ctl00_ctl00_body_body_wacCenterStage_ctl03_rptResults_ctl00_linkJobTitle']")[0];
            $testLink = $objSimpHTML->find("a[id='".$titleLink->attr['id']."']");

            if($item['job_title'] == '') continue;

            $item['company'] = $node->find("a[class='fnt4']")[0]->plaintext;
            $item['location'] = \Scooper\strScrub(str_replace("Location:", "", $node->find("div[class='jobLocationSingleLine']")[0]->plaintext));

            $strScrubTitle = \Scooper\strip_punctuation(html_entity_decode($item['job_title']));
            $strLoc= \Scooper\strip_punctuation(html_entity_decode($item['location']));

            $item['job_post_url'] = $this->siteBaseURL . "/" . str_replace(" ", "-", $strScrubTitle )."-".str_replace(" ", "-",$strLoc)."-".$item['job_id'].".aspx";
            $item['date_pulled'] = \Scooper\getTodayAsString();

            $item['job_site_date'] = $node->find("span[class='accessibilityOnly']")[0]->plaintext;
            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}