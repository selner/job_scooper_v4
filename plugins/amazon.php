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


/****************************************************************************************************************/
/***                                                                                                         ****/
/***                     Jobs Scooper Plugin:  Amazon.jobs                                                   ****/
/***                                                                                                         ****/
/****************************************************************************************************************/


/*****
 *
 * To get the right URL for Amazon Jobs search, fill out the parameters on
 * http://www.amazon.jobs/advancedjobsearch and then submit the form.  The URL of the
 * resulting page (e.g. "http://www.amazon.jobs/results?jobCategoryIds[]=83&jobCategoryIds[]=68&locationIds[]=226")
 * is the value you should set in the INI file to get the right filtered results.
 *
 */

class PluginAmazon extends \JobScooper\Plugins\lib\AjaxHtmlSimplePlugin
{
    protected $siteName = 'Amazon';
    protected $nJobListingsPerPage = 100;
    protected $siteBaseURL = 'http://www.amazon.jobs';
    protected $strBaseURLFormat = "https://www.amazon.jobs/en/search?base_query=***KEYWORDS***&loc_query=***LOCATION***&result_limit=100&sort=recent&cache";
    protected $paginationType = C__PAGINATION_INFSCROLLPAGE_VIALOADMORE;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode-comma-country';
    protected $nMaxJobsToReturn = 2000; // Amazon maxes out at 2000 jobs in the list
    protected $additionalLoadDelaySeconds = 1;
    protected $countryCodes = array("US", "GB");

    protected $selectorMoreListings = ".load-more";



    protected $arrListingTagSetup = array(
        'TotalPostCount' =>  array('selector' => 'div.job-count-info', 'return_value_regex' => '/.*?of\s(\d+)/'),
        'JobPostItem' => array('selector' => 'div.job-tile'),
        'Title' =>  array('selector' => 'h2.job-title'),
        'Url' =>  array('selector' => 'a.job-link', 'return_attribute' => 'href'),
        'JobSitePostId' =>  array('selector' => 'div.job', 'return_attribute' => 'data-job-id'),
        'Location' =>  array('selector' => 'div.location-and-id', 'return_value_regex' => '/(.*?)\|/', 'return_value_callback' => "cleanupLocationValue"),
        'PostedAt' =>  array('selector' => 'div.posting-date', 'return_value_regex' => '/Posted at (.*)/')
    );


    static function cleanupLocationValue($var)
    {
        $ret = "";
        $parts = preg_split("/,\s?/", $var);
        $revparts = array_reverse($parts);
        $ret = join(", ", $revparts);
        return $ret;
    }



}
