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


class PluginCyberCoders extends \JobScooper\BasePlugin\Classes\AjaxHtmlSimplePlugin
{
    protected $JobSiteName = 'CyberCoders';
    protected $JobPostingBaseUrl = "https://www.cybercoders.com";
    protected $SearchUrlFormat = "https://www.cybercoders.com/search/?page=***PAGE_NUMBER***&searchterms=***KEYWORDS***&searchlocation=***LOCATION***&newsearch=true&originalsearch=true&sorttype=date";

    protected $PaginationType = C__PAGINATION_PAGE_VIA_URL;
//    protected $JobListingsPerPage = 40;
    protected $LocationType = 'location-city-comma-statecode';


    protected $arrListingTagSetup = array(
        'TotalPostCount' =>  array('tag' => 'span', 'attribute' => 'id', 'attribute_value' =>'total-result-count', 'return_attribute' => 'text', 'return_value_regex' => '/.*?(\d+).*?/'),
        'JobPostItem' => array('selector' => '.job-details-container'),
        'Title' =>  array(array('selector' => 'div.job-title'), array('tag' => 'a'), 'return_attribute' => 'text'),
        'Url' =>  array(array('selector' => 'div.job-title'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'JobSitePostId' =>  array(array('selector' => 'div.job-title'), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>'/.*?(\d+)$/'),
        'EmploymentType' =>  array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'wage'), array('tag' => 'span')),
        'Location' =>  array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'Location'),
        'PostedAt' =>  array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'posted')
    );

}