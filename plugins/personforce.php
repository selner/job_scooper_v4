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

class PluginPersonForce extends \JobScooper\Plugins\Base\AjaxHtmlSimplePlugin
{
    protected $siteName = 'PersonForce';
    protected $siteBaseURL = 'http://www.personforce.com';
    protected $strBaseURLFormat = 'https://www.personforce.com/jobs/tags/***KEYWORDS***/in/***LOCATION***/p/***PAGE_NUMBER***';
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $nJobListingsPerPage = 20;
    protected $additionalLoadDelaySeconds = 5;
    protected $paginationType = C__PAGINATION_PAGE_VIA_URL;


    protected $arrListingTagSetup = array(

    'tag_listings_count' => array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value' => 'module_jobs'), array('tag' => 'div', 'attribute_value' =>'plaintext'), 'return_value_regex' => '/.*?of total (\d+).*?/'),
    'tag_listings_section' => array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'content-col-content hir left'),array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'row'),array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'row')),
    'tag_title' => array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'hir-job-title'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
    'tag_link' => array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'hir-job-title'), array('tag' => 'a'), 'return_attribute' => 'href'),
    'tag_company' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'hir-company-title', 'return_value_regex' => '/(.*?) \- .*/'),
    'tag_location' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'hir-company-title', 'return_value_regex' => '/.*? \- (.*)/'),
    'tag_next_button' => array('selector' => 'div.pagination ul li.active a'),
    'tag_job_id' =>  array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'hir-job-title'), array('tag' => 'a'), 'return_attribute' => 'href'),
    //        'regex_link_job_id' => '/.*?\/(\d+)|.*?;ad=-(.{1,})$/'
    );


    protected function normalizeJobItem($arrItem)
    {

        $arrItem ['job_site_date'] = strScrub($arrItem['job_site_date'], REMOVE_EXTRA_WHITESPACE | LOWERCASE | HTML_DECODE );
        $dateVal = strtotime($arrItem ['job_site_date'], $now = time());
        if(!($dateVal === false))
        {
            $arrItem['job_site_date'] = date('Y-m-d', $dateVal);
        }


        $arrItem['job_id'] = strScrub($arrItem['company'], FOR_LOOKUP_VALUE_MATCHING) . strScrub($arrItem['job_title'], FOR_LOOKUP_VALUE_MATCHING). strScrub($arrItem['job_site_date'], FOR_LOOKUP_VALUE_MATCHING);

        return parent::normalizeJobItem($arrItem);
    }


}
