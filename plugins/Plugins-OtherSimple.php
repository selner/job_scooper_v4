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
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');



class PluginTesla extends ClassBaseSimplePlugin
{
    protected $siteName = 'Tesla';
    protected $childSiteURLBase = 'https://www.tesla.com';
    protected $childSiteListingPage = 'https://www.tesla.com/careers/search#';
    protected $additionalFlags = [C__JOB_USE_SELENIUM];

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('tag' => 'tr', 'attribute' => 'class', 'attribute_value' =>'table-row'),
        'tag_title' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a')),
        'tag_link' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a')),
        'tag_department' => array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'listing-department'),
        'tag_location' => array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'listing-location'),
        'regex_link_job_id' => '/job\/([^\/]+)/i'
    );

}



class PluginSmashingMagazine extends ClassBaseSimplePlugin
{
    protected $siteName = 'SmashingMagazine';
    protected $childSiteURLBase = 'http://jobs.smashingmagazine.com/';
    protected $childSiteListingPage = 'http://jobs.smashingmagazine.com/fulltime';
    protected $additionalFlags = [C__JOB_PAGECOUNT_NOTAPPLICABLE__,  C__JOB_ITEMCOUNT_NOTAPPLICABLE__, C__JOB_BASETYPE_WEBPAGE_FLAGS_RETURN_ALL_JOBS_ON_SINGLE_PAGE_NO_LOCATION ];

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array(array('tag' => 'ul', 'attribute' => 'class', 'attribute_value' =>'entry-list compact'), array('tag' => 'li')),
        'tag_title' => array('tag' => 'h2'),
        'tag_link' =>  array('tag' => 'a'),
        'tag_company' => array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'entry-company'),
        'regex_link_job_id' => '/j\/.*?\/([^\/]+)/i'
    );

}


?>