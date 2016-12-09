<?php

/**
 * Copyright 2014-16 Bryan Selner
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



class PluginTesla extends ClassSimpleFullPageJobSitePlugin
{
    protected $siteName = 'Tesla';
    protected $childSiteURLBase = 'https://www.tesla.com/careers/search#';
    protected $childSiteListingPage = 'https://www.tesla.com/careers/search#';
    protected $additionalFlags = [C__JOB_USE_SELENIUM, C__JOB_BASETYPE_WEBPAGE_FLAGS_RETURN_ALL_JOBS_ON_SINGLE_PAGE_NO_LOCATION];

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('tag' => 'tr', 'attribute' => 'class', 'attribute_value' =>'table-row'),
        'tag_title' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a')),
        'tag_link' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a')),
        'tag_department' => array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'listing-department'),
        'tag_location' => array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'listing-location'),
        'regex_link_job_id' => '/job\/([^\/]+)/i'
    );

}



class PluginSmashingMagazine extends ClassSimpleFullPageJobSitePlugin
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




class PluginBetalist extends ClassBaseSimpleJobSitePlugin
{
    protected $siteName = 'Betalist';
    protected $siteBaseURL = "https://betalist.com";
    protected $nJobListingsPerPage = 500;
    protected $strBaseURLFormat = "https://betalist.com/jobs?q=***KEYWORDS***&hPP=500&p=***PAGE_NUMBER***&dFR%5Bcommitment%5D%5B0%5D=Full-Time&dFR%5Bcommitment%5D%5B1%5D=Part-Time&dFR%5Bcommitment%5D%5B2%5D=Contractor&dFR%5Bcommitment%5D%5B3%5D=Internship&is_v=1";
    protected $additionalFlags = [ C__JOB_USE_SELENIUM, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED ];  // TODO:  Add Lat/Long support for BetaList location search
    protected $additionalLoadDelaySeconds = 6;

    function parseTotalResultsCount($objSimpHTML)
    {
        $nTotalResults = C__TOTAL_ITEMS_UNKNOWN__;
        $TEXT_NOJOBS = "No jobs found";

        $spanNoCount = $objSimpHTML->find("span[class='ais-hits ais-hits__empty']");
        if($spanNoCount != null && is_array($spanNoCount) && isset($spanNoCount[0]))
        {
            if(substr($spanNoCount[0]->plaintext, 0, strlen($TEXT_NOJOBS)) == $TEXT_NOJOBS)
            {
                return 0;
            }

        }

        //
        // Find the HTML node that holds the result count
        $spanCounts = $objSimpHTML->find("span[class='ais-refinement-list--count']");
        if($spanCounts != null && is_array($spanCounts) && isset($spanCounts[0]))
        {
            foreach($spanCounts as $spanCount)
            {
                $strVal = $spanCount->plaintext;
                $nVal = intval(str_replace(",", "", $strVal));
                if($nTotalResults == C__TOTAL_ITEMS_UNKNOWN__)
                    $nTotalResults = $nVal;
                else
                    $nTotalResults += $nVal;
            }
        }


        return $nTotalResults;

    }


    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'jobCard'),
        'tag_title' =>  array('tag' => 'a', 'attribute' => 'class', 'attribute_value' => 'jobCard__details__title'),
        'tag_link' =>  array('tag' => 'a', 'attribute' => 'class', 'attribute_value' => 'jobCard__details__title'),
        'tag_company' => array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'jobCard__details__company'), array('tag' => 'a')),
        'tag_location' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'jobCard__details__location'),
        'regex_link_job_id' => '/jobs\/([^\/]+)/i'
    );

}



?>