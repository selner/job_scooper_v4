<?php

/**
 * Copyright 2014-16 Bryan Selner
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


class PluginDotJobs extends ClassJobsSitePlugin
{
    protected $siteName = 'dotjobs';
    protected $siteBaseURL = '';
    protected $flagSettings = C__JOB_BASETYPE_XMLRSS_FLAGS;
    protected $strBaseURLFormat = "http://washington.jobs/jobs/feed/rss?location=***LOCATION***&q=***KEYWORDS***";
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';



    function parseJobsListForPage($xmlResult)
    {
        $ret = null;

        foreach ($xmlResult->channel->item as $job)
        {

            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;
            $item['job_post_url'] = (string)$job->link;
            $item['job_title'] =  (string)explode(")", (string)$job->title)[1];
            $item['location'] =  str_replace("(", "", (string)explode(")", (string)$job->title)[0]);
            $item['job_id'] = (string)explode("/", (string)$job->guid)[3];
            if($item['job_title'] == '') continue;

            $item['job_site_date'] = (string)$job->pubDate;
//            $item['company'] = $this->siteName;
            $item['date_pulled'] = \Scooper\getTodayAsString();

            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}

?>
