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

//
//class PluginGovernmentJobs extends ClassBaseSimpleJobSitePlugin
//{
//    protected $siteName = 'GovernmentJobs';
//    protected $siteBaseURL = "https://www.governmentjobs.com";
//    protected $strBaseURLFormat = 'https://www.governmentjobs.com/jobs?keyword=***KEYWORDS***&location=***LOCATION***&page=***PAGE_NUMBER***&sort=date&isDescendingSort=True&distance=50';
//    protected $additionalFlags = [C__JOB_USE_SELENIUM, C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES, C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_PAGECOUNT_NOTAPPLICABLE__];
//    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
//    protected $nJobListingsPerPage = 10;
//    protected $additionalLoadDelaySeconds = 2;
//
//    protected $arrListingTagSetup = array(
////        'tag_pages_count' => array('selector' => '#iCIMS_Paginator', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?\s+(\d+)\s*$/'),
//        'tag_listings_section' => array(array('tag' => 'ul', 'attribute'=>'class', 'attribute_value' => 'unstyled job-listing-container'), array('tag' => 'li', 'attribute' => 'class', 'attribute_value' =>'job-item')),
//        'tag_title' =>  array(array('tag' => 'h3'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
//        'tag_link' =>  array(array('tag' => 'h3'), array('tag' => 'a'), 'return_attribute' => 'href'),
//        'tag_job_id' =>  array(array('tag' => 'h3'), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>  '/\/jobs\/(\d+).*/i'),
//        'tag_location' =>  array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'primaryInfo job-location'),
//        'tag_employment_type' =>  array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'primaryInfo'),
//        'tag_job_posting_date' =>  array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'termInfo'), array('tag' => 'span')),
//        'tag_next_button' =>  array(array('tag' => 'li', 'attribute' => 'class', 'attribute_value' =>'PagedList-skipToNext next-page-link'), array('tag' => 'a'))
//    );
//
//}
//
//


