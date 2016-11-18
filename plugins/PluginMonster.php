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
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');


class PluginMonster extends ClassJobsSitePlugin
{
    protected $siteName = 'Monster';
    protected $siteBaseURL = 'http://www.monster.com';
    protected $strBaseURLFormat = "https://www.monster.com/jobs/search/?q=***KEYWORDS***&sort=dt.rv.di&where=***LOCATION***&tm=***NUMBER_DAYS***&pg=***PAGE_NUMBER***";
    protected $nJobListingsPerPage = 35;
    protected $flagSettings = null;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode-underscores-and-dashes';
    protected $regex_link_job_id = '/\.com\/([^\/]+\/)?([^\.]+)/i';
    function __construct($strBaseDir = null)
    {
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS | C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES | C__JOB_PREFER_MICRODATA;
        parent::__construct($strBaseDir);
    }

    function getDaysURLValue($days = null) {
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

        $tags = get_meta_tags('http://www.example.com/');

        $resultsSection= $objSimpHTML->find("h2[class='page-title']");
        $totalItemsText = $resultsSection[0]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = $arrItemItems[0];

        return str_replace(",", "", $strTotalItemsCount);
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('article[class="js_result_row"]');


        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;

            $subNode = $node->find("div.JobTitle");
            if(isset($subNode) && isset($subNode[0])) $item['job_title'] = $subNode[0]->plaintext;


            $subNode = $node->find(("div.JobTitle h2 a"));
            if(isset($subNode) && isset($subNode[0]) && isset($subNode[0]->attr) && isset($subNode[0]->attr['data-m_impr_j_postingid']))     $item['job_id'] = $subNode[0]->attr['data-m_impr_j_postingid'];



            if($item['job_title'] == '') continue;

            $subNode = $node->find("div[class='company']");
            if(isset($subNode) && isset($subNode[0])) $item['company'] = $subNode[0]->plaintext;

            $subNode = $node->find("div[class='location']");
            if(isset($subNode) && isset($subNode[0])) $item['location'] = str_ireplace("Location:", "", $subNode[0]->plaintext);

            $subNode = $node->find("meta[itemprop='url']");
            if(isset($subNode) && isset($subNode[0])) $item['location'] = str_ireplace("Location:", "", $subNode[0]->plaintext);
            if(isset($objDiv) && isset($objDiv[0]) && isset($objDiv[0]->attr) && isset($objDiv[0]->attr['data-m_impr_j_postingid']))     $item['job_id'] = $objDiv[0]->attr['data-m_impr_j_postingid'];

            $strScrubTitle = \Scooper\strip_punctuation(html_entity_decode($item['job_title']));
            $strLoc= \Scooper\strip_punctuation(html_entity_decode($item['location']));

            $item['job_post_url'] = $this->siteBaseURL . "/" . str_ireplace(" ", "-", $strScrubTitle )."-".str_ireplace(" ", "-",$strLoc)."-".$item['job_id'].".aspx";
            $item['date_pulled'] = \Scooper\getTodayAsString();

            $subNode = $node->find("span[class='accessibilityOnly']");
            if(isset($subNode) && isset($subNode[0])) $item['job_site_date'] = $subNode[0]->plaintext;

            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}