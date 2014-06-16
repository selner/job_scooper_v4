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




class PluginLinkUp extends ClassJobsSitePlugin
{
    protected $siteName = 'LinkUp';
    protected $siteBaseURL = 'http://www.linkup.com';


    function getDaysURLValue($days) {
        $ret = "1d";

        if($days != null)
        {
            switch($days)
            {
                case ($days>3 && $days<=7):
                    $ret = "7d";
                    break;

                case ($days>=3 && $days<7):
                    $ret = "3d";
                    break;


                case $days<=1:
                default:
                    $ret = "1d";
                    break;

            }
        }

        return $ret;

    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $resultsSection= $objSimpHTML->find("div[id='search-showing']");
        $totalItemsText = $resultsSection[0]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = $arrItemItems[4];

        return str_replace(",", "", $strTotalItemsCount);
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('div[class="listing"]');


        foreach($nodesJobs as $node)
        {
            $item = parent::getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;

            $titleLink = $node->find("a[class='listing-title']")[0];

            $item['job_title'] = $titleLink->firstChild()->plaintext;
            $item['job_post_url'] = $titleLink->href;
            if($item['job_title'] == '') continue;

            $item['company'] = $node->find("span[class='listing-company']")[0]->plaintext;


            $item['job_id'] = $node->attr['data-hash'];
            $item['location'] = trim($node->find("span[class='listing-location'] span")[0]->plaintext) . "-" .
                    trim($node->find("span[class='listing-location'] span")[1]->plaintext);

            $item['date_pulled'] = getTodayAsString();


            $item['job_site_category'] = $node->find("span[class='listing-tag']")[0]->plaintext;
            $item['job_site_date'] = $node->find("span[class='listing-date']")[0]->plaintext;
            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}