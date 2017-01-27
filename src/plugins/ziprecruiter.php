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
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');


class PluginZipRecruiter extends ClassHTMLJobSitePlugin
{
    protected $siteName = 'ZipRecruiter';
    protected $siteBaseURL = 'https://jobs.ziprecruiter.com';
    protected $nJobListingsPerPage = 20;
    protected $strBaseURLFormat = "https://www.ziprecruiter.com/candidate/search?search=***KEYWORDS***&include_near_duplicates=1&location=***LOCATION***&radius=25&page=***PAGE_NUMBER***&days=***NUMBER_DAYS***";
    protected $additionalFlags = [C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS];
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $regex_link_job_id = '/^.*\/clk\/(.*)/i';

    protected $arrListingTagSetup = array(
        'tag_listings_noresults' => array(array('tag' => 'section', 'attribute'=>'class', 'attribute_value' => 'no-results'), array('tag' => 'h2'), 'return_attribute' => 'plaintext', 'return_value_callback' => "PluginZipRecruiter::isNoResults"),
        'tag_listings_count' => array('tag' => 'h1', 'attribute'=>'class', 'attribute_value' => 'headline', 'index'=> 0, 'return_attribute' => 'plaintext', 'return_value_regex' => '/\s*(\d+).*/')
    );

    function isNoResults($var)
    {
        if(stristr($var, "No jobs") != "")
            return 0;

        return null;
    }

    /**
     * If the site does not have a URL parameter for number of days
     * then set the plugin flag to C__JOB_DAYS_VALUE_NOTAPPLICABLE__
     * in the SitePlugins.php file and just comment out this function.
     *
     * getDaysURLValue returns the value that is used to replace
     * the ***DAYS*** token in the search URL for the number of
     * days requested.
     *
     * @param $days
     * @return int|string
     */
    function getDaysURLValue($days = null)
    {
        $ret = "1";

        if($days != null)
        {
            switch($days)
            {
                case ($days>5 && $days<=10):
                    $ret = "10";
                    break;

                case ($days>1 && $days<=5):
                    $ret = "5";
                    break;


                case $days<=1:
                default:
                    $ret = "1";
                    break;

            }
        }

        return $ret;
    }

    /**
    /**
     * parseJobsListForPage
     *
     * This does the heavy lifting of parsing each job record from the
     * page's HTML it was passed.
     * *
     * @param $objSimpHTML
     * @return array|null
     */
    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('div[id="job_list"] article');


        foreach($nodesJobs as $node)
        {
            //
            // get a new record with all columns set to null
            //
            $item = $this->getEmptyJobListingRecord();

            $item['job_id'] = $node->attr['id'];

            $titleNode = $node->find("span[class='just_job_title']");
            if(isset($titleNode) && isset($titleNode[0]))
            {
                $item['job_title'] = $titleNode[0]->plaintext;
            }

            // If we couldn't parse the job title, it's not really a job
            // listing so just continue to the next one
            //
            if($item['job_title'] == '') continue;

            $titleLink = $node->find("h2[class='job_title'] a");
            if(isset($titleLink) && isset($titleLink[0]))
            {
                $item['job_post_url'] = $titleLink[0]->href;
            }

//
//            // remove "remaining15" or similar if it exists
//            $strExternalJobID = preg_replace('/remaining\d{1,3}/i', "", $strExternalJobID);
//
//            // remove "_cpc" from the ID if it still exists
//            $strExternalJobID = preg_replace('/_cpc/i', "", $strExternalJobID );
//

            $companyNode = $node->find("span[class='name']");
            if(isset($companyNode) && isset($companyNode[0]))
            {
                $item['company'] = $companyNode[0]->plaintext;
            }

            $locNode = $node->find("span[class='location']");
            if(isset($locNode) && isset($locNode[0]))
            {
                $item['location'] = $locNode[0]->plaintext;
            }

            $empNode = $node->find("span[itemProp='employmentType']");
            if(isset($empNode) && isset($empNode[0]))
            {
                $item['employment_type'] = $empNode[0]->plaintext;
            }

            $jobDetailsNode = $node->find("span[class='new']");
            if(isset($jobDetailsNode) && isset($jobDetailsNode[0]))
            {
                $item['job_site_date'] = getTodayAsString();
            }

            $ret[] = $this->normalizeJobItem($item);
        }

        return $ret;
    }

}