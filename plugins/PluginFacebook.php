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




class PluginFacebook extends ClassJobsSitePlugin
{
    protected $siteName = 'Facebook';
    protected $siteBaseURL = 'https://www.facebook.com/careers/';
    protected $nJobListingsPerPage = 10;
    protected $strBaseURLFormat = "https://www.facebook.com/careers/locations/***LOCATION***";
    protected $flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS;
    protected $typeLocationSearchNeeded = 'location-city';

    function setLocationValue($locVal) { $this->locationValue = strtolower($locVal); }


    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('ul[class="careersList"]');

        foreach($nodesJobs as $node)
        {

            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;
            $item['company'] = $this->siteName;
            $item['job_title'] = $node->find("li span a")[0]->plaintext;
            $item['job_post_url'] = $node->find("li span a")[0]->href;
            $item['job_id'] = explode("req=", $item['job_post_url'])[1];
            if($item['job_title'] == '') continue;

            $item['job_site_category'] = $node->parent()->find("h3")[0]->plaintext;

            $item['location'] = $this->getLocationValue();
            $item['date_pulled'] = \Scooper\getTodayAsString();

//            var_dump($item);
            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}

?>
