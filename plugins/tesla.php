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


class PluginTesla extends \JobScooper\Plugins\lib\AjaxHtmlSimplePlugin
{
    protected $siteName = 'Tesla';
    protected $siteBaseURL = 'http://www.tesla.com/careers';
    // BUGBUG:  Hard coded to region = North America (#4)
    protected $strBaseURLFormat = 'https://www.tesla.com/careers/search#/filter/?region=4';
    protected $countryCodes = array("US", "UK");

    protected $paginationType = C__PAGINATION_NONE;
    protected $nMaxJobsToReturn = C_JOB_MAX_RESULTS_PER_SEARCH;
    protected $nJobListingsPerPage = C_JOB_MAX_RESULTS_PER_SEARCH;

    protected $arrListingTagSetup = array(
        'JobPostItem' => array('selector' => "table tr.table-row" ),
        'Title' => array('selector' => 'th.listing-title a', 'return_attribute' => 'plaintext'),
        'Url' => array('selector' => 'th.listing-title a', 'return_attribute' => 'href'),
        'Company' =>  array('return_value_callback' => 'setCompanyToSiteName'),
        'JobSitePostId' => array('selector' => 'th.listing-title a', 'return_attribute' => 'href', 'return_value_regex' =>  '/.*?-(\d+)/i'),
        'Department' => array('selector' => 'td.listing-department'),
        'LocationFromSource' => array('selector' => 'td.listing-location')
    );

}


