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




class PluginEmploymentGuide extends ClassJobsSitePlugin
{
    protected $siteName = 'EmploymentGuide';
    protected $siteBaseURL = 'http://seattle.employmentguide.com/';
    protected $flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS;
    protected $typeLocationSearchNeeded = 'location-city';

    protected $strBaseURLFormat = "http://***LOCATION***.employmentguide.com/searchresults.php?page=***PAGE_NUMBER***&q=***KEYWORDS***&l=***LOCATION***&radius=20&sort=date&posted_after=***NUMBER_DAYS***";


    function getDaysURLValue($nDays = null)
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

            $item['job_post_url']  = $node->attr['href'];

            $fMatchedID = preg_match('/jobid[=-](\w+)/i', $item['job_post_url'], $idMatches);
            if($fMatchedID && count($idMatches) > 1)
            {
                $item['job_id'] = $idMatches[1];
            }


            $subNode = $node->find("div[class='jobInfo'] h2");
            if(isset($subNode) && isset($subNode[0]))  $item['job_title'] = $subNode[0]->plaintext;
            if($item['job_title'] == '') continue;

            $subNode = $node->find("div[class='jobInfo'] span[class='companyName']");
            if(isset($subNode) && isset($subNode[0]))  $item['company'] = $subNode[0]->plaintext;

            $subNode = $node->find("div[class='jobInfo'] span[class='location'] span");
            if(isset($subNode) && isset($subNode[0])) $item['location'] = $subNode[0]->plaintext;

            $subNode = $node->find("td[class='column2'] div");
            if(isset($subNode) && isset($subNode[0])) $item['job_site_category'] = $subNode[0]->plaintext;


            $item['date_pulled'] = \Scooper\getTodayAsString();

            $subNode = $node->find("div[class='datePosted']");
            if(isset($subNode) && isset($subNode[0])) $item['job_site_date'] = $subNode[0]->plaintext;

            $subNode = $node->find("div[class='datePosted'] span[class='dateNum']");
            if(isset($subNode) && isset($subNode[0])) $item['job_site_date'] .= '-'.$subNode[0]->plaintext;;


            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}


?>
