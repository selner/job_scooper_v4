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

class PluginOuterwall extends ClassHTMLJobSitePlugin
{
    protected $siteName = 'Outerwall';
    protected $siteBaseURL = 'http://outerwall.jobs';
    protected $strBaseURLFormat = "http://outerwall.jobs/***LOCATION***/usa/jobs/";
    protected $typeLocationSearchNeeded = 'location-state';

    protected $arrListingTagSetup = array(

        'tag_listings_count' => array('tag' => 'h3', 'attribute' => 'class', 'attribute_value' =>'direct_highlightedText', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?(\d+).*?/'),
        'tag_listings_section' => array(array('tag' => 'ul', 'attribute'=>'class', 'attribute_value' => 'default_jobListing'), array('tag' => 'li')),
        'tag_title' => array(array('tag' => 'h4'), array('tag' => 'a'), array('tag' => 'span'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'h4'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_location' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'direct_joblocation'),
        'regex_link_job_id' => '/\/[j\/]{0,2}(.*)/i'
    );

}


class PluginTesla extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'Tesla';
    protected $childSiteURLBase = 'https://www.tesla.com/careers/search#';
    protected $childSiteListingPage = 'https://www.tesla.com/careers/search#';
    protected $additionalFlags = [ C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_PAGECOUNT_NOTAPPLICABLE__, C__JOB_ITEMCOUNT_NOTAPPLICABLE__];

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array(array('tag' => 'table', 'attribute' => 'class', 'attribute_value' =>'table'), array('tag' => 'tbody', 'attribute' => 'class', 'attribute_value' =>'table-body'), array('tag' => 'tr', 'attribute' => 'class', 'attribute_value' =>'table-row')),
        'tag_title' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_job_id' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>  '/.*?-(\d+)/i'),
        'tag_department' => array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'listing-department'),
        'tag_location' => array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'listing-location')
    );

}



class PluginSmashingMagazine extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'SmashingMagazine';
    protected $childSiteURLBase = 'http://jobs.smashingmagazine.com';
    protected $childSiteListingPage = 'http://jobs.smashingmagazine.com';
    protected $additionalFlags = [C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_PAGECOUNT_NOTAPPLICABLE__, C__JOB_ITEMCOUNT_NOTAPPLICABLE__];

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array(array('tag' => 'ul', 'attribute' => 'class', 'attribute_value' =>'entry-list compact'), array('tag' => 'li')),
        'tag_title' => array('tag' => 'h2'),
        'tag_link' =>  array(array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_job_id' =>  array(array('tag' => 'article'), 'return_attribute' => 'id'),
        'tag_company' => array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'entry-company'),
    );

}

class PluginPersonForce extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'PersonForce';
    protected $siteBaseURL = 'http://www.personforce.com';
    protected $strBaseURLFormat = 'https://www.personforce.com/jobs/tags/***KEYWORDS***/in/***LOCATION***/p/***PAGE_NUMBER***';
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $nJobListingsPerPage = 20;
    protected $additionalLoadDelaySeconds = 5;

    function __construct($strOutputDirectory = null)
    {
        $this->additionalFlags[] = C__JOB_PAGE_VIA_URL;
        parent::__construct($strOutputDirectory);
    }


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

}


class PluginRobertHalf extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'RobertHalf';
    protected $siteBaseURL = "https://www.roberthalf.com";
    protected $strBaseURLFormat = 'https://www.roberthalf.com/technology/jobs/***KEYWORDS***/***LOCATION***?pageSize=1500';
    protected $additionalFlags = [C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES, C__JOB_DAYS_VALUE_NOTAPPLICABLE__  ];
    protected $typeLocationSearchNeeded = 'location-city';
    protected $nJobListingsPerPage = 1500;


    protected $arrListingTagSetup = array(
        'tag_listings_count' => array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value' => 'job-search-result-counter pg-1 first'), 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?of (\d+).*?/'),
        'tag_listings_section' => array(array('tag' => 'table', 'attribute'=>'class', 'attribute_value' => 'job-search-results'), array('tag' => 'tr', 'attribute' => 'class', 'attribute_value' =>'job-search-page')),
        'tag_title' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_job_id' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>  '/\/technology\/job\/([^?]+)/i'),
        'tag_location' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'city')),
        'tag_job_posting_date' =>  array(array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'JobTitle'), array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'postDate')),
    );

}
//
//class PluginGovernmentJobs extends ClassBaseSimpleJobSitePlugin
//{
//    protected $siteName = 'GovernmentJobs';
//    protected $siteBaseURL = "https://www.governmentjobs.com";
//    protected $strBaseURLFormat = 'https://www.governmentjobs.com/jobs?keyword=***KEYWORDS***&location=***LOCATION***&page=***PAGE_NUMBER***&sort=date&isDescendingSort=True&distance=50';
//    protected $additionalFlags = [C__JOB_USE_SELENIUM, C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES, C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_PAGECOUNT_NOTAPPLICABLE__];
//    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
//    protected $nJobListingsPerPage = 10;
//    protected $additionalLoadDelaySeconds = 2;
//
//    protected $arrListingTagSetup = array(
////        'tag_pages_count' => array('selector' => '#iCIMS_Paginator', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?\s+(\d+)\s*$/'),
//        'tag_listings_section' => array(array('tag' => 'ul', 'attribute'=>'class', 'attribute_value' => 'unstyled job-listing-container'), array('tag' => 'li', 'attribute' => 'class', 'attribute_value' =>'job-item')),
//        'tag_title' =>  array(array('tag' => 'h3'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
//        'tag_link' =>  array(array('tag' => 'h3'), array('tag' => 'a'), 'return_attribute' => 'href'),
//        'tag_job_id' =>  array(array('tag' => 'h3'), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>  '/\/jobs\/(\d+).*/i'),
//        'tag_location' =>  array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'primaryInfo job-location'),
//        'tag_employment_type' =>  array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'primaryInfo'),
//        'tag_job_posting_date' =>  array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'termInfo'), array('tag' => 'span')),
//        'tag_next_button' =>  array(array('tag' => 'li', 'attribute' => 'class', 'attribute_value' =>'PagedList-skipToNext next-page-link'), array('tag' => 'a'))
//    );
//
//}
//
//



class PluginFredHutch extends BasePluginiCIMS
{
    protected $siteName = 'FredHutch';
    protected $siteBaseURL = "https://hub-fhcrc.icims.com";

    // Results Columns:  "Posting date", "Job ID", "Job title", "Department", "Location", "Remote base"
    protected $arrResultsRowTDIndex = array('tag_job_posting_date' => null, 'tag_job_id' => 0, 'tag_title' => 1, 'tag_link' => 1, 'tag_department' => null, 'tag_location' => 2 );
}

class PluginRedHat extends BasePluginiCIMS
{
    protected $siteName = 'RedHat';
    protected $nJobListingsPerPage = 10;
    protected $siteBaseURL = "https://careers-redhat.icims.com";

    // Results Columns:  "Posting date", "Job ID", "Job title", "Department", "Location", "Remote base"
    protected $arrResultsRowTDIndex = array('tag_job_posting_date' => 0, 'tag_job_id' => 1, 'tag_title' => 2, 'tag_link' => 2, 'tag_department' => 3, 'tag_location' => 4 );
}

class PluginParivedaSolutions extends BasePluginiCIMS
{
    protected $siteName = 'ParivedaSolutions';
    protected $siteBaseURL = "https://careers-parivedasolutions.icims.com";
    protected $arrResultsRowTDIndex = array('tag_job_posting_date' => null, 'tag_job_id' => 0, 'tag_title' => 1, 'tag_link' => 1, 'tag_department' => null, 'tag_location' => 2 );
}

class BasePluginiCIMS extends ClassHTMLJobSitePlugin
{
    protected $additionalFlags = [C__JOB_ITEMCOUNT_NOTAPPLICABLE__, C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED];
    protected $nJobListingsPerPage = 20;
    protected $strInitialReferer = "https://careers-parivedasolutions.icims.com/jobs/search?pr=0";
    protected $arrResultsRowTDIndex = null;

    function __construct($strBaseDir = null)
    {
        $this->strBaseURLFormat = $this->siteBaseURL . "/jobs/search?pr=***PAGE_NUMBER***&in_iframe=1&searchKeyword=***KEYWORDS***";

        if(is_null($this->arrResultsRowTDIndex))
            throw new InvalidArgumentException("Error in iCIMS plugin:  you must map the columns in the results table to the correct indexes for HTML tag matching. Aborting.");

        foreach(array_keys($this->arrResultsRowTDIndex) as $tagKey)
        {
            if(array_key_exists($tagKey, $this->arrResultsRowTDIndex) === true && is_null($this->arrResultsRowTDIndex[$tagKey]) !== true &&
                array_key_exists($tagKey, $this->arrListingTagSetup))
            {
                if(array_key_exists(0, $this->arrListingTagSetup[$tagKey]) && is_array($this->arrListingTagSetup[$tagKey]) === true)
                    $this->arrListingTagSetup[$tagKey][0]['index'] = $this->arrResultsRowTDIndex[$tagKey];
                else
                    $this->arrListingTagSetup[$tagKey]['index'] = $this->arrResultsRowTDIndex[$tagKey];
            }
        }

        parent::__construct($strBaseDir);

    }

    protected $arrListingTagSetup = array(
        'tag_pages_count' => array('selector' => '#iCIMS_Paginator', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?\s+(\d+)\s*$/'),
        'tag_listings_section' => array(array('tag' => 'table', 'attribute'=>'class', 'attribute_value' => 'iCIMS_JobsTable iCIMS_Table'), array('tag' => 'tr')),
        'tag_title' =>  array(array('tag' => 'td', 'index' =>null), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'td', 'index' =>null), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' => '/(.*?)\?.*/'),
        'tag_job_id' =>  array('tag' => 'td', 'index' =>null, 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?([\d\-]+).*?$/'),
        'tag_location' =>  array('tag' => 'td', 'index' =>null, 'return_attribute' => 'plaintext',  'return_value_regex' => '/.*?Loc[\w]+:&nbsp;\s*([\w\s[:punct:]]+)?$/')
//        'tag_title' =>  array(array('tag' => 'td', 'index' =>1), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
//        'tag_link' =>  array(array('tag' => 'td', 'index' =>1), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' => '/(.*?)\?.*/'),
//        'tag_job_id' =>  array('tag' => 'td', 'index' =>1), 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?(\d+\-\d+).*?/'),
//        'tag_location' =>  array('tag' => 'td', 'index' =>2, 'return_attribute' => 'plaintext',  'return_value_regex' => '/.*?Loc[\w]+:&nbsp;\s*([\w\s\-]+)?$/')
    );
    protected function getPageURLValue($nPage) { return ($nPage - 1); }

}



class PluginCyclon extends ClassHTMLJobSitePlugin
{
    protected $siteName = 'Cylcon';
    protected $siteBaseURL = "http://cylcon.com";
    protected $strBaseURLFormat = "http://cylcon.com/jobs.php?q=***KEYWORDS***&l=***LOCATION***&sort=date&radius=50&start=***ITEM_NUMBER***";
    protected $additionalFlags = [C__JOB_DAYS_VALUE_NOTAPPLICABLE__];
    protected $nJobListingsPerPage = 15;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';


    protected $arrListingTagSetup = array(
        'tag_listings_count' => array('selector' => '#searchCount' , 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?of\s+(\d+).*?/'),
        'tag_listings_section' => array('tag' => 'div', 'attribute'=>'class', 'attribute_value' => 'joblists clearfix'),
        'tag_title' =>  array(array('tag' => 'div'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array('tag' => 'a', 'return_attribute' => 'href'),
        'tag_next_button' => array('selector' => '#page-top > section > div > div.row.text-left > div.col-lg-9 > table > tbody > tr:nth-child(1) > td:nth-child(3) > a'),
        'tag_job_id' =>  array('tag' => 'a', 'return_attribute' => 'href', 'return_value_regex' => '/.*?[Rr]edirect[Ww][Ee][Bb]\.php\?q=([^&]+)&*.*/'),
        'tag_company' =>  array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'col-lg-10'), array('tag' => 'p'), array('tag' => 'strong'), 'return_attribute' => 'plaintext', 'return_value_regex' => '/(.*?)-.*/'),
        'tag_locationd' =>  array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'col-lg-10'), array('tag' => 'p'), array('tag' => 'strong'), array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'location'), 'return_attribute' => 'plaintext', 'return_value_regex' => '/-(.*?)-.*/'),
        'tag_job_posting_date' =>  array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'date')
    );
//    protected function getItemURLValue($nItem) { return ($nItem == null || $nItem == "" || $nItem <= 1) ? "" : ($nItem - 1); }

}








class PluginBetalist extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'Betalist';
    protected $siteBaseURL = "https://betalist.com";
    protected $nJobListingsPerPage = 500;

    // Note:  Betalist has a short list of jobs (< 500-1000 total) so we exclude keyword search here as an optimization.  The trick we use is putting a blank space in the search text box.
    //        The space returns all jobs whereas blank returns a special landing page.
    protected $strBaseURLFormat = "https://betalist.com/jobs?q=%20&hPP=500&p=***PAGE_NUMBER***&dFR%5Bcommitment%5D%5B0%5D=Full-Time&dFR%5Bcommitment%5D%5B1%5D=Part-Time&dFR%5Bcommitment%5D%5B2%5D=Contractor&dFR%5Bcommitment%5D%5B3%5D=Internship&is_v=1";
#    protected $strBaseURLFormat = "https://betalist.com/jobs?q=***KEYWORDS***&hPP=500&p=***PAGE_NUMBER***&dFR%5Bcommitment%5D%5B0%5D=Full-Time&dFR%5Bcommitment%5D%5B1%5D=Part-Time&dFR%5Bcommitment%5D%5B2%5D=Contractor&dFR%5Bcommitment%5D%5B3%5D=Internship&is_v=1";

    protected $additionalFlags = [  C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED ];  // TODO:  Add Lat/Long support for BetaList location search.
    protected $additionalLoadDelaySeconds = 10;

    function parseTotalResultsCount($objSimpHTML)
    {
        $nTotalResults = C__TOTAL_ITEMS_UNKNOWN__;

        //
        // Find the HTML node that holds the result count
        $nodeCounts = $objSimpHTML->find("span.ais-refinement-list--count");
        if($nodeCounts != null && is_array($nodeCounts) && isset($nodeCounts[0]))
        {
            foreach($nodeCounts as $spanCount)
            {
                $strVal = $spanCount->plaintext;
                $nVal = intval(str_replace(",", "", $strVal));
                if($nTotalResults == C__TOTAL_ITEMS_UNKNOWN__)
                    $nTotalResults = $nVal;
                else
                    $nTotalResults += $nVal;
            }
        }
        else
        {
            return 0;
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

class PluginFacebook extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'Facebook';
    protected $siteBaseURL = 'https://www.facebook.com/careers/';
    protected $strBaseURLFormat = "https://www.facebook.com/careers/search/?q=&location=***LOCATION***";
    protected $typeLocationSearchNeeded = 'location-city';
    protected $additionalFlags = [C__JOB_SINGLEPAGE_RESULTS, C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_LOCATION_REQUIRES_LOWERCASE, C__JOB_PAGECOUNT_NOTAPPLICABLE__];

    protected $arrListingTagSetup = array(
        'tag_listings_count' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'_1dc4', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?(\d+).*?/'),
        'tag_listings_section' => array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value' => '_3k6i')),
        'tag_title' => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => '_5144', 'return_attribute' => 'plaintext'),
        'tag_link' => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => '_5144', 'return_attribute' => 'href'),
        'tag_location' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'_3k6m'),
        'regex_link_job_id' => '/\/[j\/]{0,2}(.*)/i'
    );

}


class PluginAuthenticJobs extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'AuthenticJobs';
    protected $siteBaseURL = "https://authenticjobs.com";
    protected $strBaseURLFormat = 'https://authenticjobs.com/#location=***LOCATION***&query=***KEYWORDS***';
    protected $additionalFlags = [C__JOB_CLIENTSIDE_INFSCROLLPAGE, C__JOB_PAGECOUNT_NOTAPPLICABLE__ , C__JOB_ITEMCOUNT_NOTAPPLICABLE__];
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $nJobListingsPerPage = 50;


    protected $arrListingTagSetup = array(
        'tag_listings_count' => array(array('tag' => 'ul', 'attribute'=>'id', 'attribute_value' => 'listings'), 'return_attribute' => 'data-total'),
        'tag_listings_section' => array(array('tag' => 'ul', 'attribute'=>'id', 'attribute_value' => 'listings'), array('tag' => 'li')),
        'tag_title' =>  array(array('tag' => 'a'), array('tag' => 'div'),array('tag' => 'h3'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_company' =>  array(array('tag' => 'a'), array('tag' => 'div'),array('tag' => 'h4'), 'return_attribute' => 'plaintext'),
        'tag_location' =>  array(array('tag' => 'a'), array('tag' => 'ul'),array('tag' => 'li', 'attribute' => 'class', 'attribute_value' =>'location'), 'return_attribute' => 'plaintext'),
        'tag_employment_type' =>  array(array('tag' => 'a'), array('tag' => 'ul'),array('tag' => 'li', 'index' => 0), 'return_attribute' => 'plaintext'),
        'tag_job_id' =>  array(array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>  '/\/jobs\/([^?]+)/i'),
        'tag_load_more' =>  array('tag' => 'a', 'attribute' => 'class', 'attribute_value' =>'ladda-button')
    );

}


class PluginDotJobs extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'dotjobs';
    // BUGBUG:  hard coded to be washington state
    protected $siteBaseURL = 'http://washington.jobs';
    protected $nJobListingsPerPage = 20;
    protected $additionalFlags = [C__JOB_PAGECOUNT_NOTAPPLICABLE__, C__JOB_CLIENTSIDE_INFSCROLLPAGE ];
    protected $strBaseURLFormat = "http://washington.jobs/jobs?location=***LOCATION***&q=***KEYWORDS***";
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $additionalLoadDelaySeconds = 3;

    protected $arrListingTagSetup = array(
        'tag_listings_count' => array('tag' => 'h3', 'attribute'=>'class', 'attribute_value' => 'direct_highlightedText', 'return_attribute' => 'plaintext', 'return_value_regex' =>  '/.*?(\d+)\s*jobs/i'),
        'tag_listings_section' => array(array('tag' => 'ul', 'attribute'=>'class', 'attribute_value' => 'default_jobListing'), array('tag' => 'li')),
        'tag_title' =>  array(array('tag' => 'h4'), array('tag' => 'a'), array('tag' => 'span'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'h4'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_company' =>  array(array('tag' => 'div'), array('tag' => 'span'),array('tag' => 'b'), 'return_attribute' => 'plaintext'),
        'tag_location' =>  array(array('tag' => 'div'), array('tag' => 'span', 'attribute'=>'class', 'attribute_value' => 'hiringPlace'), array('tag' => 'span'), array('tag' => 'span'), 'return_attribute' => 'plaintext'),
        'tag_job_id' =>  array(array('tag' => 'h4'), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>  '/\/([\w\d]+)\/job.*/i'),
        'tag_load_more' =>  array('tag' => 'a', 'attribute' => 'id', 'attribute_value' =>'button_moreJobs')
    );


}




class PluginMediaBistro extends ClassHTMLJobSitePlugin
{
    protected $siteName = 'MediaBistro';
    protected $childSiteURLBase = 'https://www.mediabistro.com';
    protected $childSiteListingPage = 'https://www.mediabistro.com/jobs/openings/';
    protected $addtionalFlags = [C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_PREFER_MICRODATA];
}



class PluginAltasource extends BaseForceComClass
{
    protected $siteName = 'Altasource';
    protected $siteBaseURL = "http://altasourcegroup.force.com";
    protected $nJobListingsPerPage = 30;
    protected $strBaseURLFormat = "http://altasourcegroup.force.com/careers";
}

class PluginSalesforce extends BaseForceComClass
{
    protected $siteName = 'Salesforce';
    protected $siteBaseURL = "https://careers.secure.force.com";
    protected $strBaseURLFormat = "https://careers.secure.force.com/jobs";

    // Alternate job site that could be used instead:   http://salesforce.careermount.com/candidate/job_search/quick/results?location=seattle&keyword=developer&sort_dir=desc&sort_field=post_date&relevance=false
}

class BaseForceComClass extends ClassClientHTMLJobSitePlugin
{
    protected $additionalFlags = [ C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED ];
    protected $additionalLoadDelaySeconds = 3;
    protected $nJobListingsPerPage = 50;
    protected $nextPageScript = "A4J.AJAX.Submit('j_id0:j_id1:atsForm',event,{'similarityGroupingId':'j_id0:j_id1:atsForm:j_id123','containerId':'j_id0:j_id1:atsForm:j_id77','parameters':{'j_id0:j_id1:atsForm:j_id123':'j_id0:j_id1:atsForm:j_id123'} ,'status':'j_id0:j_id1:atsForm:ats_pagination_status'} );return false;";


    function parseTotalResultsCount($objSimpHTML)
    {
        $nTotalResults = C__TOTAL_ITEMS_UNKNOWN__;

        //
        // Find the HTML node that holds the result count
        $nodeCounts = $objSimpHTML->find("div[id='atsSearchResultsText']");
        if($nodeCounts != null && is_array($nodeCounts) && isset($nodeCounts[0]))
        {
            $counts = explode("&nbsp", $nodeCounts[0]->plaintext);
            $nTotalResults = \Scooper\intceil($counts[0]);
        }


        return $nTotalResults;

    }

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('selector' => "table.atsSearchResultsTable tbody tr"),
        'tag_title' =>  array(array('tag' => 'td', 'index' => 0), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'td', 'index' => 0), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_department' =>  array(array('tag' => 'td'), array('tag' => 'span'), 'index' => 0),
        'tag_location' =>  array(array('tag' => 'td'), array('tag' => 'span'), 'index' => 1),
#        'tag_company' => array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'jobCard__details__company'), array('tag' => 'a')),
        'regex_link_job_id' => '/.*?jobId=([^&]+)/i'
    );

    public function takeNextPageAction($driver)
    {
        $driver->executeScript("function callNextPage() { " . $this->nextPageScript ." } ; callNextPage();");
        sleep(2);
        return $driver;

    }

}



?>