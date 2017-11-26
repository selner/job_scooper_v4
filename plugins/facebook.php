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
    protected $JobSiteName = 'Facebook';
    protected $JobPostingBaseUrl = 'https://www.facebook.com/careers/';
    protected $SearchUrlFormat = "https://www.facebook.com/careers/search/?q=&location=***LOCATION***";
    protected $LocationType = 'location-city';
    protected $additionalBitFlags = [C__JOB_LOCATION_REQUIRES_LOWERCASE];
    protected $JobListingsPerPage = C__TOTAL_ITEMS_UNKNOWN__;
    protected $PaginationType = C__PAGINATION_NONE;
    
    protected $arrListingTagSetup = array(
        'TotalPostCount' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'_1dc4', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?(\d+).*?/'),
        'JobPostItem' => array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value' => '_3k6i')),
        'Title' => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => '_5144', 'return_attribute' => 'plaintext'),
        'Url' => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => '_5144', 'return_attribute' => 'href'),
        'JobSitePostId' => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => '_5144', 'return_attribute' => 'href', 'return_value_regex' => '/\/careers\/jobs\/([^\/]+)/'),
        'Location' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'_3k6m')
    );

}
