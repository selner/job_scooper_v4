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




class PluginOuterwall extends ClassJobsSitePlugin
{
    protected $siteName = 'Outerwall';
    protected $siteBaseURL = 'http://outerwall.jobs';
    protected $strBaseURLFormat = "http://outerwall.jobs/***LOCATION***/usa/jobs/";
    protected $flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_NO_KEYWORDS;
    protected $typeLocationSearchNeeded = 'location-state';

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('li[class="direct_joblisting"]');


        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;
            $item['job_post_url'] = $this->siteBaseURL . $node->find("h4 a")[0]->href;
            $item['job_title'] = $node->find("h4 a span")[0]->plaintext;
            $item['job_id'] = explode("/", $item['job_post_url'])[3];
            if($item['job_title'] == '') continue;


            $item['location'] =  explode("/", $item['job_post_url'])[1];
            $item['company'] = $this->siteName;
            $item['date_pulled'] = \Scooper\getTodayAsString();

//            var_dump($item);
            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}

?>
