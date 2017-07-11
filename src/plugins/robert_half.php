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



class PluginRobertHalf extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'RobertHalf';
    protected $siteBaseURL = "https://www.roberthalf.com";
    #    protected $strBaseURLFormat = 'https://www.roberthalf.com/technology/jobs/***KEYWORDS***/***LOCATION***?pageSize=1500';
    protected $strBaseURLFormat = 'https://www.roberthalf.com/technology/jobs/all-jobs/***LOCATION***?pageSize=1500';
    protected $additionalFlags = [C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES];
    protected $typeLocationSearchNeeded = 'location-city';
    protected $nJobListingsPerPage = 1500;


    protected $arrListingTagSetup = array(
        'tag_listings_count' => array('selector' => 'div.job-search-result-counter', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?of (\d+).*?/'),
        'tag_listings_section' => array(array('tag' => 'table', 'attribute'=>'class', 'attribute_value' => 'job-search-results'), array('tag' => 'tr', 'attribute' => 'class', 'attribute_value' =>'job-search-page')),
        'tag_title' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_company' =>  array('return_value_callback' => 'setCompanyToSiteName'),
        'tag_job_id' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>  '/\/technology\/job\/([^?]+)/i'),
        'tag_location' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'city')),
        'tag_job_posting_date' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'postDate')),
        'tag_next_button' => array('selector' => 'li[data-pg=\"next\"] a')
    );

}