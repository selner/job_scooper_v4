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
        'tag_title' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a'), 'return_attribute' => 'href'),
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
        'tag_link' =>  array(array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_company' => array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'entry-company'),
        'regex_link_job_id' => '/j\/.*?\/([^\/]+)/i'
    );

}


class PluginRobertHalf extends ClassBaseSimpleJobSitePlugin
{
    protected $siteName = 'RobertHalf';
    protected $strBaseURLFormat = 'https://www.roberthalf.com/technology/jobs/***KEYWORDS***/***LOCATION***?pageSize=1500';
    protected $additionalFlags = [C__JOB_USE_SELENIUM, C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES ];
    protected $typeLocationSearchNeeded = 'location-city';
    protected $nJobListingsPerPage = 1500;


    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('tag' => 'tr', 'attribute' => 'class', 'attribute_value' =>'job-search-page'),
        'tag_title' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_location' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'city')),
        'tag_job_posting_date' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'postDate')),
        'regex_link_job_id' => '/\/technology\/job\/([^?]+)/i'
    );


    function parseTotalResultsCount($objSimpHTML)
    {
        $nTotalResults = C__TOTAL_ITEMS_UNKNOWN__;

        //
        // Find the HTML node that holds the result count
        $spanCounts = $objSimpHTML->find("div[class='job-search-result-counter pg-1 first']");
        if($spanCounts != null && is_array($spanCounts) && isset($spanCounts[0]))
        {
            $counts = explode(" of ", $spanCounts[0]->plaintext);
            $nTotalResults = \Scooper\intceil($counts[1]);
        }


        return $nTotalResults;

    }




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





class PluginAltasource extends BaseForceComClass
{
    protected $siteName = 'Altasource';
    protected $siteBaseURL = "http://altasourcegroup.force.com/";
    protected $nJobListingsPerPage = 30;
    protected $strBaseURLFormat = "http://altasourcegroup.force.com/careers";
}

class PluginSalesforce extends BaseForceComClass
{
    protected $siteName = 'Salesforce';
    protected $siteBaseURL = "https://careers.secure.force.com/";
    protected $strBaseURLFormat = "https://careers.secure.force.com/jobs";

    // Alternate job site that could be used instead:   http://salesforce.careermount.com/candidate/job_search/quick/results?location=seattle&keyword=developer&sort_dir=desc&sort_field=post_date&relevance=false
}

class BaseForceComClass extends ClassBaseSimpleJobSitePlugin
{
    protected $additionalFlags = [ C__JOB_USE_SELENIUM, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED ];
    protected $additionalLoadDelaySeconds = 3;
    protected $nJobListingsPerPage = 50;
    protected $nextPageScript = "A4J.AJAX.Submit('j_id0:j_id1:atsForm',event,{'similarityGroupingId':'j_id0:j_id1:atsForm:j_id123','containerId':'j_id0:j_id1:atsForm:j_id77','parameters':{'j_id0:j_id1:atsForm:j_id123':'j_id0:j_id1:atsForm:j_id123'} ,'status':'j_id0:j_id1:atsForm:ats_pagination_status'} );return false;";


    function parseTotalResultsCount($objSimpHTML)
    {
        $nTotalResults = C__TOTAL_ITEMS_UNKNOWN__;

        //
        // Find the HTML node that holds the result count
        $spanCounts = $objSimpHTML->find("div[id='atsSearchResultsText']");
        if($spanCounts != null && is_array($spanCounts) && isset($spanCounts[0]))
        {
            $counts = explode("&nbsp", $spanCounts[0]->plaintext);
            $nTotalResults = \Scooper\intceil($counts[0]);
        }


        return $nTotalResults;

    }

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array(array('tag' => 'table', 'attribute' => 'id', 'attribute_value' => "j_id0:j_id1:atsForm:atsSearchResultsTable"), array('tag' => 'tbody'),array('tag' => 'tr')),
        'tag_title' =>  array(array('tag' => 'td', 'index' => 0), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'td', 'index' => 0), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_location' =>  array(array('tag' => 'td', 'index' => 2), array('tag' => 'span')),
#        'tag_company' => array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'jobCard__details__company'), array('tag' => 'a')),
        'regex_link_job_id' => '/.*?jobId=([^&]+)/i'
    );

    public function getNextPage($driver, $nextPageNum)
    {
        $driver->executeScript("function callNextPage() { " . $this->nextPageScript ." } ; callNextPage();");
        sleep(2);
        return $driver;

    }

}



?>