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



class PluginFacebook extends \JobScooper\Plugins\lib\AjaxHtmlSimplePlugin
{
    protected $siteName = 'Facebook';
    protected $siteBaseURL = 'https://www.facebook.com/careers/';
    protected $strBaseURLFormat = "https://www.facebook.com/careers/search/?q=&location=***LOCATION***";
    protected $typeLocationSearchNeeded = 'location-city';
    protected $additionalFlags = [C__JOB_LOCATION_REQUIRES_LOWERCASE];
    protected $nJobListingsPerPage = C__TOTAL_ITEMS_UNKNOWN__;
    protected $paginationType = C__PAGINATION_NONE;
    
    protected $arrListingTagSetup = array(
        'tag_listings_count' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'_1dc4', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?(\d+).*?/'),
        'tag_listings_section' => array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value' => '_3k6i')),
        'tag_title' => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => '_5144', 'return_attribute' => 'plaintext'),
        'tag_company' =>  array('return_value_callback' => 'setCompanyToSiteName'),
        'tag_link' => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => '_5144', 'return_attribute' => 'href'),
        'tag_job_id' => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => '_5144', 'return_attribute' => 'href', 'return_value_regex' => '/\/careers\/jobs\/([^\/]+)/'),
        'tag_location' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'_3k6m')
    );

}
