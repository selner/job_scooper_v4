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
require_once dirname(dirname(dirname(__FILE__)))."/bootstrap.php";

/*class PluginHP extends BasePluginTalentBrew
{
    protected $siteName = "HP";
    protected $siteBaseURL = 'https://h30631.www3.hp.com';
    protected $nJobListingsPerPage = 15;
    protected $strBaseURLFormat = "/search-jobs";  // HP's keyword search is a little squirrelly so more successful if we don't filter up front and get the mismatches removed later
    protected $additionalFlags = [ C__PAGINATION_INFSCROLLPAGE_NOCONTROL];

    function __construct($strBaseDir = null)
    {
        unset($this->arrListingTagSetup['tag_next_button']);

        parent::__construct();
    }

    function takeNextPageAction($nItem=null, $nPage=null)
    {
        $this->runJavaScriptSnippet("document.getElementsByClassName(\"next\")[0].setAttribute(\"class\", \"pagination-show-all\"); document.getElementsByClassName(\"pagination-show-all\")[0].click();", false, $this->additionalLoadDelaySeconds + 10);
        sleep($this->additionalLoadDelaySeconds+1 );
        $this->nMaxJobsToReturn = C_JOB_MAX_RESULTS_PER_SEARCH * 3;
        $this->nJobListingsPerPage = $this->nMaxJobsToReturn;

        $this->moveDownOnePageInBrowser();
        
    }
}*/


class PluginBoeing extends BasePluginTalentBrew
{
    protected $siteName = 'Boeing';
    protected $siteBaseURL = 'https://jobs.boeing.com';
    protected $nJobListingsPerPage = 20;
    protected $strBaseURLFormat = "/search-jobs";

    function __construct()
    {
        unset($this->arrListingTagSetup['tag_listings_count']);
        parent::__construct();
    }
}

class PluginDisney extends BasePluginTalentBrew
{
    protected $siteName = 'Disney';
    protected $siteBaseURL = 'https://jobs.disneycareers.com';
    protected $additionalFlags = [ C__JOB_ITEMCOUNT_NOTAPPLICABLE__ ];
    protected $nJobListingsPerPage = 15;

    function __construct()
    {
        parent::__construct();
        unset($this->arrListingTagSetup['tag_listings_count']);
        $this->arrListingTagSetup['tag_listings_section'] = array(array('tag' => 'section', 'attribute' => 'id', 'attribute_value' => 'search-results-list'), array('tag' => 'table'),array('tag' => 'tr'));
        $this->arrListingTagSetup['tag_next_button'] = array(array('tag' => 'a', 'attribute' => 'class', 'attribute_value' => 'next', 'index' => 0));


        $this->strBaseURLFormat = $this->siteBaseURL . "/search-jobs?k=&alp=6252001-5815135&alt=3";
    }

}

class BasePluginTalentBrew extends \JobScooper\Plugins\Base\AjaxHtmlSimplePlugin
{
    protected $strBaseURLFormat = "/search-jobs/***KEYWORDS***/***LOCATION***";
    //
    // BUGBUG:  Disney & Boeing are both hit or miss around returning the full set of listings correctly.
    //          Setting to ignore_mismatched to avoid the error results that will happen when they do.
    //
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $additionalLoadDelaySeconds = 5;

    protected $nJobListingsPerPage = 50;

    protected $arrListingTagSetup = array(
        'tag_listings_count' => array(array('tag' => 'section', 'attribute' => 'id', 'attribute_value' => 'search-results'), array('tag' => 'h1'), 'return_attribute' => 'plaintext', 'return_value_regex' =>  '/(.*?) .*/'),
        'tag_pages_count' => array(array('tag' => 'span', 'attribute' => 'class', 'attribute_value' => 'pagination-total-pages'), 'return_attribute' => 'plaintext', 'return_value_regex' =>  '/of (.*)/'),
        'tag_listings_section' => array(array('tag' => 'section', 'attribute' => 'id', 'attribute_value' => 'search-results-list'), array('tag' => 'ul'),array('tag' => 'li')),
        'tag_title' =>  array('selector' => 'a h2'),
        'tag_link' =>  array('tag' => 'a', 'return_attribute' => 'href'),
        'tag_job_id' =>  array('tag' => 'a', 'return_attribute' => 'data-job-id'),
        'tag_company' =>  array('return_value_callback' => 'setCompanyToSiteName'),
        'tag_location' =>  array('tag' => 'span', 'attribute' => 'class', 'attribute_value' => 'job-location'),
        'tag_job_posting_date' =>  array('tag' => 'span', 'attribute' => 'class', 'attribute_value' => 'job-date-posted'),
        'tag_next_button' => array('selector' => '#pagination-bottom a.next')
    );
    function __construct()
    {

        parent::__construct();
        $this->strBaseURLFormat = $this->siteBaseURL . $this->strBaseURLFormat;
    }

}