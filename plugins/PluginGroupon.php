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




class PluginGroupon extends ClassJobsSitePlugin
{
    protected $siteName = 'Groupon';
    protected $siteBaseURL = 'https://jobs.groupon.com';
    protected $strBaseURLFormat = "https://jobs.groupon.com/careers/***LOCATION***";
    protected $flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS;
    protected $typeLocationSearchNeeded = 'location-city-comma-state-country-no-commas';




    function parseTotalResultsCount($objSimpHTML)
    {


        $resultsSection= $objSimpHTML->find("div[class='jobvite-search-results'] p");
        $strTotalItemsCount  = $resultsSection[0]->plaintext;
        $strTotalItemsCount = \Scooper\strScrub($strTotalItemsCount);

        $arrItemItems = explode(" ", trim($strTotalItemsCount));
        return $arrItemItems[4];

    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find("div[class='jobvite-search-results'] table tr");

        $nCounter = -1;

        foreach($nodesJobs as $node)
        {
            $nCounter += 1;
            if($nCounter < 2)
            {
                continue;
            }

            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;

            $titleNode = $node->firstChild();
            $locNode = $node->firstChild()->nextSibling();


            $item['job_title'] = $titleNode->plaintext;
            $item['job_post_url'] = $titleNode->firstChild()->href;
            if($item['job_title'] == '') continue;


            $item['location'] = $locNode->plaintext;


            $item['company'] = $this->siteName;
            $item['date_pulled'] = \Scooper\getTodayAsString();


            $arrURLParts = explode("/",  $item['job_post_url']);
            $strURLJobPart = $arrURLParts[count($arrURLParts)-2];
            $arrJobIDParts = explode("-", $strURLJobPart);
            $nIDPart = count($arrJobIDParts)-1;
            $item['job_id'] = $arrJobIDParts[$nIDPart];

            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}