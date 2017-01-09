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
        'tag_company' =>  array('return_value_callback' => 'ClassBaseHTMLJobSitePlugin::setCompanyToSiteName'),
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
        'tag_company' =>  array('return_value_callback' => 'ClassBaseHTMLJobSitePlugin::setCompanyToSiteName'),
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
        'tag_company' =>  array('return_value_callback' => 'ClassBaseHTMLJobSitePlugin::setCompanyToSiteName'),
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


class PluginCyberCoders extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'CyberCoders';
    protected $siteBaseURL = "https://www.cybercoders.com";
    protected $strBaseURLFormat = "https://www.cybercoders.com/search/?page=***PAGE_NUMBER***&searchterms=***KEYWORDS***&searchlocation=***LOCATION***&newsearch=true&originalsearch=true&sorttype=date";

    protected $additionalFlags = [C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_PAGE_VIA_URL];
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

class PluginCyclon extends ClassHTMLJobSitePlugin
{
    protected $siteName = 'Cylcon';
    protected $siteBaseURL = "http://cylcon.com";
    protected $strBaseURLFormat = "http://cylcon.com/jobs.php?q=***KEYWORDS***&l=***LOCATION***&sort=date&radius=50&start=***ITEM_NUMBER***";

    //
    // BUGBUG: We shouldn't have to do C__JOB_IGNORE_MISMATCHED_JOB_COUNTS here, but have not yet figured out what is causing lower counts
    //         to sporadically happen
    //
    protected $additionalFlags = [C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_IGNORE_MISMATCHED_JOB_COUNTS];
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

    protected $additionalFlags = [  C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_PAGE_VIA_URL ];  // TODO:  Add Lat/Long support for BetaList location search.
    protected $additionalLoadDelaySeconds = 10;
    protected function getPageURLValue($nPage) { return ($nPage - 1); }

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


        $this->additionalLoadDelaySeconds = $this->additionalLoadDelaySeconds + \Scooper\intceil($this->nJobListingsPerPage/100) * 2;

        //
        // Betalist maxes out at a 1000 listings.  If we're over that, reduce the count to 1000 so we don't try to download more
        //
        return ($nTotalResults > 1000) ? 1000 : $nTotalResults;

    }


    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'ais-hits--item'),
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
        'tag_company' =>  array('return_value_callback' => 'ClassBaseHTMLJobSitePlugin::setCompanyToSiteName'),
        'tag_link' => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => '_5144', 'return_attribute' => 'href'),
        'tag_job_id' => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => '_5144', 'return_attribute' => 'href', 'return_value_regex' => '/\/careers\/jobs\/([^\/]+)/'),
        'tag_location' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'_3k6m'),
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
    protected $nJobListingsPerPage = 25;
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
    protected $nextPageScript = "function contains(selector, text) {
              var elements = document.querySelectorAll(selector);
              return Array.prototype.filter.call(elements, function(element){
                return RegExp(text).test(element.textContent);
              });
            }
            var linkNext = contains('a', 'Next');
            if(linkNext.length >= 1)
            {
                console.log(linkNext[0]);
                linkNext[0].click();
            }
    ";

//    A4J.AJAX.Submit('j_id0:j_id1:atsForm',event,{'similarityGroupingId':'j_id0:j_id1:atsForm:j_id123','containerId':'j_id0:j_id1:atsForm:j_id77','parameters':{'j_id0:j_id1:atsForm:j_id123':'j_id0:j_id1:atsForm:j_id123'} ,'status':'j_id0:j_id1:atsForm:ats_pagination_status'} );return false;";
//

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
        'tag_company' =>  array('return_value_callback' => 'ClassBaseHTMLJobSitePlugin::setCompanyToSiteName'),
        'regex_link_job_id' => '/.*?jobId=([^&]+)/i'
    );

    public function takeNextPageAction($driver)
    {
        $GLOBALS['logger']->logLine("Going to next page of results via script: " . $this->nextPageScript  , \Scooper\C__DISPLAY_NORMAL__);
        $driver->executeScript("function callNextPage() { " . $this->nextPageScript ." } ; callNextPage();");
        sleep($this->additionalLoadDelaySeconds);
        return $driver;

    }

}



?>