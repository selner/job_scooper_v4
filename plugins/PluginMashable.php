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
require_once dirname(__FILE__) . '/../include/ClassJobsSitePlugin.php';



class PluginMashable extends ClassJobsSitePlugin
{
    protected $siteName = 'Mashable';
    protected $siteBaseURL = 'http://jobs.mashable.com';
    protected $nJobListingsPerPage = 50;


    function getDaysURLValue($days) {
        $ret = "%5BNOW-1DAYS+TO+NOW%5D";

        if($days != null)
        {
            switch($days)
            {
                case ($days>7):
                    $ret = "";
                    break;

                case ($days>1 && $days<=7):
                    $ret = "%5BNOW-7DAYS+TO+NOW%5D";
                    break;


                case $days<=1:
                default:
                    $ret = "%5BNOW-1DAYS+TO+NOW%5D";
                    break;

            }
        }

        return $ret;

    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $resultsSection= $objSimpHTML->find("span[id='retCountNumber']");
        $strTotalItemsCount  = $resultsSection[0]->plaintext;

        return str_replace(",", "", $strTotalItemsCount);
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('div[class="aiResultsWrapper"]');


        foreach($nodesJobs as $node)
        {
            $item = parent::getEmptyItemsArray();
            $item['job_site'] = $this->siteName;

            $titleLink = $node->find("div[class='aiResultTitle'] h3 a")[0];

            $item['job_title'] = $titleLink->plaintext;
            $item['job_post_url'] = $siteBaseURL . $titleLink->href;
            if($item['job_title'] == '') continue;

            $item['company'] = $node->find("li[class='aiResultsCompanyName']")[0]->plaintext;
            $item['location'] = trim($node->find("span[class='aiResultsLocationSpan']")[0]->plaintext);
            $item['job_site_category'] = $node->find("div[class='aiDescriptionPod'] ul li[class='searchResultsCategoryDisplay']")[0]->plaintext;
            $item['date_pulled'] = $this->getTodayAsString();


            $idClass = $node->find("div[class='aiResultsMainDiv']")[0];
            $idText = $idClass->attr['id'];

            $item['job_id'] = str_replace("aiResultsMainDiv", "", $idText);


            if($this->is_IncludeBrief() == true)
            {
                $item['brief_description'] = $node->find("div[class='aiResultsDescriptionNoAdvert']")[0]->plaintext;
            }

            $item['job_site_date'] = $node->find("div[class='aiDescriptionPod'] ul li")[2]->plaintext;

            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}