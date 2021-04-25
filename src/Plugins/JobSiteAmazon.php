<?php
/**
 * Copyright 2014-17 Bryan Selner
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
namespace Jobscooper\Plugins;

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

class JobSiteAmazon extends \Jobscooper\BasePlugin\ClientSideHTMLJobSitePlugin
{
    protected $siteName = 'Amazon';
    protected $nJobListingsPerPage = 100;
    protected $siteBaseURL = 'http://www.amazon.jobs';
    protected $strBaseURLFormat = "https://www.amazon.jobs/en/search?base_query=***KEYWORDS***&loc_query=***LOCATION***&result_limit=100&sort=recent&cache";
    protected $paginationType = C__PAGINATION_INFSCROLLPAGE_VIALOADMORE;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode-comma-country';
    protected $nMaxJobsToReturn = 2000; // Amazon maxes out at 2000 jobs in the list
    protected $additionalLoadDelaySeconds = 1;

    protected $selectorMoreListings = ".load-more";

    function parseTotalResultsCount($objSimpHTML)
    {
        $subnode = $objSimpHTML->find("div[id=search-paging] div[class=container] div[class=row] div");
        if(isset($subnode) && is_array($subnode) && count($subnode) >= 1)
        {
            $resultsText = $subnode[count($subnode)-1]->plaintext;
            $countParts = explode(" of ", trim($resultsText));
            $countTotalParts = explode(" ", $countParts[1]);
            return $countTotalParts[0];
        }
        return 0;

    }


    function parseJobsListForPage($objSimpHTML)
    {
        $ret = array();
        $nodesjobs= $objSimpHTML->find('div[class=jobs col-xs-12] a');

        foreach($nodesjobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();

            $item['job_id'] = str_ireplace("/en/jobs/", "", $node->href);
            $item['job_post_url'] = $this->siteBaseURL . $node->href;

            $subNode = $node->find("h2[class=job-title]");
            $item['job_title'] = $subNode[0]->plaintext;

            $item['company'] = 'Amazon';
            $item['date_pulled'] = getTodayAsString();
            $subNode = $node->find("div[class=location-and-id] span]");
            $item['location'] = explode("|", $subNode[0]->plaintext)[0];

            $subNode = $node->find("h2[class=posting-date]");
            $item['job_site_date'] = trim(str_ireplace(array("Posted ", "on"), "", $subNode[0]->plaintext));
            $dateVal = date_create_from_format("F d, Y", $item['job_site_date']);
            if(isset($dateVal))
                $item['job_site_date'] = $dateVal->format('m/d/y');

            $ret[] = $this->normalizeJobItem($item);
        }
        return $ret;
    }

}
?>
