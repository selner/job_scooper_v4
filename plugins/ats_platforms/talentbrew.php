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


/*class PluginHP extends AbstractTalentBrew
{
    protected $siteName = "HP";
    protected $siteBaseURL = 'https://h30631.www3.hp.com';
    protected $nJobListingsPerPage = 15;
    protected $strBaseURLFormat = "/search-jobs";  // HP's keyword search is a little squirrelly so more successful if we don't filter up front and get the mismatches removed later
    protected $additionalFlags = [ C__PAGINATION_INFSCROLLPAGE_NOCONTROL];

    function __construct($strBaseDir = null)
    {
        unset($this->arrListingTagSetup['NextButton']);

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


class PluginBoeing extends AbstractTalentBrew
{
    protected $siteName = 'Boeing';
    protected $siteBaseURL = 'https://jobs.boeing.com';
    protected $nJobListingsPerPage = 20;
    protected $strBaseURLFormat = "/search-jobs";

    function __construct()
    {
        unset($this->arrListingTagSetup['TotalPostCount']);
        parent::__construct();
    }
}

class PluginDisney extends AbstractTalentBrew
{
    protected $siteName = 'Disney';
    protected $siteBaseURL = 'https://jobs.disneycareers.com';
    protected $additionalFlags = [ C__JOB_ITEMCOUNT_NOTAPPLICABLE__ ];
    protected $nJobListingsPerPage = 15;

    function __construct()
    {
        parent::__construct();
        unset($this->arrListingTagSetup['TotalPostCount']);
        $this->arrListingTagSetup['JobPostItem'] = array(array('tag' => 'section', 'attribute' => 'id', 'attribute_value' => 'search-results-list'), array('tag' => 'table'),array('tag' => 'tr'));
        $this->arrListingTagSetup['NextButton'] = array(array('tag' => 'a', 'attribute' => 'class', 'attribute_value' => 'next', 'index' => 0));


        $this->strBaseURLFormat = $this->siteBaseURL . "/search-jobs?k=&alp=6252001-5815135&alt=3";
    }

}

class AbstractTalentBrew extends \JobScooper\Plugins\lib\AjaxHtmlSimplePlugin
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
        'TotalPostCount' => array(array('tag' => 'section', 'attribute' => 'id', 'attribute_value' => 'search-results'), array('tag' => 'h1'), 'return_attribute' => 'plaintext', 'return_value_regex' =>  '/(.*?) .*/'),
        'TotalResultPageCount' => array(array('tag' => 'span', 'attribute' => 'class', 'attribute_value' => 'pagination-total-pages'), 'return_attribute' => 'plaintext', 'return_value_regex' =>  '/of (.*)/'),
        'JobPostItem' => array(array('tag' => 'section', 'attribute' => 'id', 'attribute_value' => 'search-results-list'), array('tag' => 'ul'),array('tag' => 'li')),
        'Title' =>  array('selector' => 'a h2'),
        'Url' =>  array('tag' => 'a', 'return_attribute' => 'href'),
        'JobSitePostId' =>  array('tag' => 'a', 'return_attribute' => 'data-job-id'),
        'Company' =>  array('return_value_callback' => 'setCompanyToSiteName'),
        'Location' =>  array('tag' => 'span', 'attribute' => 'class', 'attribute_value' => 'job-location'),
        'PostedAt' =>  array('tag' => 'span', 'attribute' => 'class', 'attribute_value' => 'job-date-posted'),
        'NextButton' => array('selector' => '#pagination-bottom a.next')
    );
    function __construct()
    {

        parent::__construct();
        $this->strBaseURLFormat = $this->siteBaseURL . $this->strBaseURLFormat;
    }

}