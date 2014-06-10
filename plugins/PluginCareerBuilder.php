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




class PluginCareerBuilder extends ClassJobsSitePlugin
{
    protected $siteName = 'CareerBuilder';
    protected $siteBaseURL = 'http://www.careerbuilder.com/';


    function getDaysURLValue($days) {
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

        $resultsSection= $objSimpHTML->find("div[id='pnlJobResultsCount']");
        $totalItemsText = $resultsSection[0]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = $arrItemItems[4];

        return str_replace(",", "", $strTotalItemsCount);
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;



        $nodesJobs= $objSimpHTML->find('table[id="JL_D"] tr');

        foreach($nodesJobs as $node)
        {

            if(strcasecmp($node->attr['class'], "jl_even_row prefRow") != 0 &&
                strcasecmp($node->attr['class'], "jl_odd_row prefRow") != 0)
            {
                continue;
            }

            $item = parent::getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;

            $titleLink = $node->find("a[class='jt']");


            $item['job_title'] = $titleLink[0]->plaintext;
            $item['job_post_url'] = $titleLink[0]->href;
            if($item['job_title'] == '') continue;

            $arrURLParts = explode("&amp;", $item['job_post_url']);
            foreach ($arrURLParts as $param)
            {
                $arrParamParts = explode("=" ,$param);
                if($arrParamParts && count($arrParamParts) > 1 && $arrParamParts[0] == "job_did")
                {
                    $item['job_id'] = $arrParamParts[1];
                    break;
                }
            }


            $item['company'] = $node->find("a[class='prefCompany']")[0]->plaintext;
            $item['location'] = strScrub($node->find("div[class='jl_col4_div']")[0]->plaintext);

            $item['date_pulled'] = getTodayAsString();

            if($this->is_IncludeBrief() == true)
            {
                $item['brief_description'] = $node->find("td[class='jl_col2'] div")[0]->plaintext;
            }
            //            $item['job_site_category'] = $node->find("span[class='listing-tag']")[0]->plaintext;

            $item['job_site_date'] = strScrub($node->find("TD[class='jl_rslt_posted_cell'] span")[0]->plaintext,DEFAULT_SCRUB );
            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}