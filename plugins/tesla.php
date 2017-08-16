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

class PluginTesla extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'Tesla';

    // BUGBUG:  Hard coded to region = North America (#4)
    protected $childSiteURLBase = 'https://www.tesla.com/careers/search#/filter/?region=4';
    protected $childSiteListingPage = 'https://www.tesla.com/careers/search#/filter/?region=4';
    protected $paginationType = C__PAGINATION_NONE;
    protected $nMaxJobsToReturn = C_JOB_MAX_RESULTS_PER_SEARCH;
    protected $nJobListingsPerPage = C_JOB_MAX_RESULTS_PER_SEARCH;

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('selector' => "table tr.table-row" ),
        'tag_title' => array('selector' => 'th.listing-title a', 'return_attribute' => 'plaintext'),
        'tag_link' => array('selector' => 'th.listing-title a', 'return_attribute' => 'href'),
        'tag_company' =>  array('return_value_callback' => 'setCompanyToSiteName'),
        'tag_job_id' => array('selector' => 'th.listing-title a', 'return_attribute' => 'href', 'return_value_regex' =>  '/.*?-(\d+)/i'),
        'tag_department' => array('selector' => 'td.listing-department'),
        'tag_location' => array('selector' => 'td.listing-location')
    );

}


