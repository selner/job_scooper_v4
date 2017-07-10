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

class PluginDotJobs extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'dotjobs';
    // BUGBUG:  hard coded to be washington state
    protected $siteBaseURL = 'http://www.my.jobs';
    protected $nJobListingsPerPage = 20;
    protected $additionalFlags = [C__JOB_PAGECOUNT_NOTAPPLICABLE__, C__JOB_CLIENTSIDE_INFSCROLLPAGE_VIALOADMORE, C__JOB_DAYS_VALUE_NOTAPPLICABLE__ ];

    protected $strBaseURLFormat = "http://www.my.jobs/jobs/?location=***LOCATION***&q=***KEYWORDS***";
    protected $typeLocationSearchNeeded = 'location-city-comma-state';
    protected $additionalLoadDelaySeconds = 4;
    protected $nMaxJobsToReturn = 9000;
    protected $selectorMoreListings = "#button_moreJobs";


    protected function goToEndOfResultsSetViaLoadMore()
    {

        $js = "
            document.getElementById(\"direct_moreLessLinks_listingDiv\").setAttribute(\"data-num-items\", 50);
        ";

        $this->runJavaScriptSnippet($js, false);

        parent::goToEndOfResultsSetViaLoadMore();
    }

    protected $arrListingTagSetup = array(
        'tag_listings_count' => array('tag' => 'h3', 'attribute'=>'class', 'attribute_value' => 'direct_highlightedText', 'return_attribute' => 'plaintext', 'return_value_regex' =>  '/.*?([,\d]+)\s*jobs/i'),
        'tag_listings_section' => array(array('tag' => 'ul', 'attribute'=>'class', 'attribute_value' => 'default_jobListing'), array('tag' => 'li')),
        'tag_title' =>  array(array('tag' => 'h4'), array('tag' => 'a'), array('tag' => 'span'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'h4'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_company' =>  array(array('tag' => 'div'), array('tag' => 'span'),array('tag' => 'b'), 'return_attribute' => 'plaintext'),
        'tag_location' =>  array(array('tag' => 'div'), array('tag' => 'span', 'attribute'=>'class', 'attribute_value' => 'hiringPlace'), array('tag' => 'span'), array('tag' => 'span'), 'index' => 0, 'return_attribute' => 'plaintext'),
        'tag_job_id' =>  array(array('tag' => 'h4'), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>  '/\/([\w\d]+)\/job.*/i'),
        'tag_load_more' =>  array('tag' => 'a', 'attribute' => 'id', 'attribute_value' =>'button_moreJobs')
    );


}
