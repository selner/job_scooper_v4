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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');




class PluginGroupon extends ClassBaseClientSideHTMLJobSitePlugin
{
    protected $siteName = 'Groupon';
    protected $siteBaseURL = 'https://jobs.groupon.com';
    protected $strBaseURLFormat = "https://jobs.groupon.com/locations/***LOCATION***";
    protected $additionalFlags = [C__JOB_SETTINGS_GET_ALL_JOBS_UNFILTERED, C__JOB_CLIENTSIDE_INFSCROLLPAGE_NOCONTROL, C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_PAGECOUNT_NOTAPPLICABLE__, C__JOB_ITEMCOUNT_NOTAPPLICABLE__];
    protected $typeLocationSearchNeeded = 'location-city';


    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find("a[class='ng-binding']");

        $nCounter = -1;

        foreach($nodesJobs as $node)
        {
            $nCounter += 1;
            if($nCounter < 2)
            {
                continue;
            }

            $item = $this->getEmptyJobListingRecord();

            $item['job_title'] = $node->plaintext;
            $item['job_post_url'] = $node->href;
            $item['date_pulled'] = getTodayAsString();
            $item['company'] = $this->siteName;
            $item['job_id'] = $this->getIDFromLink('/\/jobs\/([^\/]+)/i', $item['job_post_url']);
            if($item['job_title'] == '') continue;
            $ret[] = $this->normalizeJobItem($item);

        }

        return $ret;
    }

}