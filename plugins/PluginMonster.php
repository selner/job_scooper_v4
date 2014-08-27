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


class PluginMonster extends ClassJobsSitePlugin
{
    protected $siteName = 'Monster';
    protected $siteBaseURL = 'http://jobsearch.monster.com';
    protected $strBaseURLFormat = "http://jobsearch.monster.com/search/***KEYWORDS***_5?where=***LOCATION***&tm=***NUMBER_DAYS***&pg=***PAGE_NUMBER***";
    protected $nJobListingsPerPage = 25;
    protected $flagSettings = null;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode-underscores-and-dashes';

    function __construct($strBaseDir = null)
    {
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS | C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES;
        parent::__construct($strBaseDir);
    }

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
            if(!isset($node->attr['class']) || strcasecmp($node->attr['class'], "even") != 0 &&
                strcasecmp($node->attr['class'], "odd") != 0)
            {
                    continue;
            }
            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;

            $subNode = $node->find("div div a");
            if(isset($subNode) && isset($subNode[0])) $item['job_title'] = $subNode[0]->plaintext;


            $subNode = $node->find(("div[class='socialContainer']"));
            if(isset($subNode) && isset($subNode[0])) $objDiv = $subNode[0]->plaintext;
            if(isset($objDiv) && isset($objDiv[0]) && isset($objDiv[0]->attr) && isset($objDiv[0]->attr['data-jobid']))     $item['job_id'] = $objDiv[0]->attr['data-jobid'];



            if($item['job_title'] == '') continue;

            $subNode = $node->find("a[class='fnt4']");
            if(isset($subNode) && isset($subNode[0])) $item['company'] = $subNode[0]->plaintext;

            $subNode = $node->find("div[class='jobLocationSingleLine']");
            if(isset($subNode) && isset($subNode[0])) $item['location'] = str_replace("Location:", "", $subNode[0]->plaintext);

            $strScrubTitle = \Scooper\strip_punctuation(html_entity_decode($item['job_title']));
            $strLoc= \Scooper\strip_punctuation(html_entity_decode($item['location']));

            $item['job_post_url'] = $this->siteBaseURL . "/" . str_replace(" ", "-", $strScrubTitle )."-".str_replace(" ", "-",$strLoc)."-".$item['job_id'].".aspx";
            $item['date_pulled'] = \Scooper\getTodayAsString();

            $subNode = $node->find("span[class='accessibilityOnly']");
            if(isset($subNode) && isset($subNode[0])) $item['job_site_date'] = $subNode[0]->plaintext;

            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}