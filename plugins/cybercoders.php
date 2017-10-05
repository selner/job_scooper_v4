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
require_once dirname(dirname(__FILE__))."/bootstrap.php";

class PluginCyberCoders extends \Jobscooper\Plugins\Base\AjaxHtmlSimplePlugin
{
    protected $siteName = 'CyberCoders';
    protected $siteBaseURL = "https://www.cybercoders.com";
    protected $strBaseURLFormat = "https://www.cybercoders.com/search/?page=***PAGE_NUMBER***&searchterms=***KEYWORDS***&searchlocation=***LOCATION***&newsearch=true&originalsearch=true&sorttype=date";

    protected $paginationType = C__PAGINATION_PAGE_VIA_URL;
//    protected $nJobListingsPerPage = 40;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';


    protected $arrListingTagSetup = array(
        'tag_listings_count' =>  array('tag' => 'span', 'attribute' => 'id', 'attribute_value' =>'total-result-count', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?(\d+).*?/'),
        'tag_listings_section' => array('selector' => '.job-details-container'),
        'tag_title' =>  array(array('selector' => 'div.job-title'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('selector' => 'div.job-title'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_job_id' =>  array(array('selector' => 'div.job-title'), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>'/.*?(\d+)$/'),
        'tag_employment_type' =>  array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'wage'), array('tag' => 'span')),
        'tag_location' =>  array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'location'),
        'tag_job_posting_date' =>  array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'posted')
    );

}