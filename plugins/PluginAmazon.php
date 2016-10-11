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

/****************************************************************************************************************/
/***                                                                                                         ****/
/***                     Jobs Scooper Plugin:  Amazon.jobs                                                   ****/
/***                                                                                                         ****/
/****************************************************************************************************************/



/*****
 *
 * To get the right URL for Amazon Jobs search, fill out the parameters on
 * http://www.amazon.jobs/advancedjobsearch and then submit the form.  The URL of the
 * resulting page (e.g. "http://www.amazon.jobs/results?jobCategoryIds[]=83&jobCategoryIds[]=68&locationIds[]=226")
 * is the value you should set in the INI file to get the right filtered results.
 *
 */

class PluginAmazon extends ClassJobsSitePlugin
{
    protected $siteName = 'Amazon';
    protected $nJobListingsPerPage = 100;
    protected $siteBaseURL = 'http://www.amazon.jobs';
    protected $strBaseURLFormat = "https://www.amazon.jobs/en/search?base_query=***KEYWORDS***&loc_query=***LOCATION***&job_count=100&result_limit=100&sort=recent&cache";
    protected $flagSettings = null;
    protected $typeLocationSearchNeeded = 'location-city';
    protected $classToCheckExists = "job-title";

    function __construct($strBaseDir = null)
    {
        parent::__construct($strBaseDir);
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS | C__JOB_USE_SELENIUM;
    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $subnode = $objSimpHTML->find("div[id=search-paging] div[class=container] div[class=row] div");
        $parts = explode(" ", $subnode[count($subnode)-1]->plaintext);
        return $parts[2];

    }


    function parseJobsListForPage($objSimpHTML)
    {
        $ret = array();
        $nodesjobs= $objSimpHTML->find('div[class=jobs col-xs-12] a');

        foreach($nodesjobs as $node)
        {
            $strTeamName = "";
            $strTeamCat = "";

            $item = $this->getEmptyJobListingRecord();

            $item['job_post_url'] = $node->href;
            $item['job_id'] = str_replace("/en/jobs/", "", $item['job_post_url']);

            $subNode = $node->find("h2[class=job-title]");
            $item['job_title'] = $subNode[0]->plaintext;

            $item['company'] = 'Amazon';
            $item['job_site'] = 'Amazon';
            $item['date_pulled'] = \Scooper\getTodayAsString();
            $subNode = $node->find("div[class=location-and-id] span]");
            $item['location'] = explode("|", $subNode[0]->plaintext)[0];

            $subNode = $node->find("h2[class=posting-date]");
            $item['job_site_date'] = $subNode[0]->plaintext;


            $ret[] = $this->normalizeItem($item);


        }
        return $ret;
    }


}
?>
