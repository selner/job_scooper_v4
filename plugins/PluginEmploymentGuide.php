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




class PluginEmploymentGuide extends ClassJobsSitePlugin
{
    protected $siteName = 'EmploymentGuide';
    protected $siteBaseURL = 'http://seattle.employmentguide.com/';



    function getDaysURLValue($nDays)
    {
        $ret = 1;
        switch($nDays)
        {
            case $nDays > 3 && $nDays <= 7:
                $ret = 7;
                break;

            case $nDays > 1 && $nDays <= 3:
                $ret = 3;
                break;

            default:
                $ret = 1;
                break;
        }
        return $ret;

    }




    function parseTotalResultsCount($objSimpHTML)
    {
        $resultsSection= $objSimpHTML->find("div[class='resultsTalley']");  // "1 - 10 of 10 Job Results"
        $totalItemsText = $resultsSection[0]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = trim($arrItemItems[4]);
        $strTotalItemsCount = str_replace(",", "", $strTotalItemsCount);

        return $strTotalItemsCount;
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('a[class="jobLink"]');


        foreach($nodesJobs as $node)
        {
            /*            if(strcasecmp($node->attr['class'], "gradeA even") != 0 &&
                            strcasecmp($node->attr['class'], "gradeA odd") != 0)
                        {
                            continue;
                        }
            */
            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;
            $item['job_post_url']  = $node->href;
            $item['job_id'] = explode("JobID=", $item['job_post_url'])[1];

            $item['job_title'] = $node->find("div[class='jobInfo'] h2")[0]->plaintext;
            //          if($item['job_title'] == '') continue;


            $item['company'] = $node->find("div[class='jobInfo'] span[class='companyName']")[0]->plaintext;
            $item['location'] = $node->find("div[class='jobInfo'] span[class='location'] span")[0]->plaintext;


            $item['job_site_category'] = $node->find("td[class='column2'] div")[0]->plaintext;

            $item['date_pulled'] = getTodayAsString();

            $item['job_site_date'] = $node->find("div[class='datePosted']")[0]->plaintext . '-'.$node->find("div[class='datePosted'] span[class='dateNum']")[0]->plaintext;;


            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}


?>
