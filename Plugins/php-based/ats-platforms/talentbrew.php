<?php

/**
 * Copyright 2014-18 Bryan Selner
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

class PluginBoeing extends AbstractTalentBrew
{
    protected $JobSiteName = 'Boeing';
    protected $JobPostingBaseUrl = 'https://jobs.boeing.com';
    protected $JobListingsPerPage = 20;
    protected $SearchUrlFormat = "/search-jobs";
    protected $arrListingTagSetup = [
        'TotalPostCount' => ['Selector' => 'h1[role="status"]'],
        'JobPostItem' => ['Selector' => 'section#search-results-list ul li'],
        'Title' =>  ['Selector' => 'a h2'],
        'Url' =>  ['Selector' => 'a', 'Attribute' => 'href'],
        'JobSitePostId' =>  ['Selector' => 'a', 'Attribute' => 'data-job-id'],
        'Location' => ['Selector' => 'li a'],
        'PostedAt' => ['Selector' => 'li a span.job-date-posted'],
        'NextButton' => ['Selector' => '#pagination-bottom a.next']
    ];

    public function __construct()
    {
        $this->arrListingTagSetup['TotalPostCount'] = array('Selector' => 'h1[role="status"]');
        parent::__construct();
    }

     /**
     * parseJobsListForPage
     *
     * This does the heavy lifting of parsing each job record from the
     * page's HTML it was passed.
     *
     * @param \JobScooper\Utils\SimpleHTMLHelper $objSimpHTML
     *
     * @return array|null
     * @throws \Exception
     */
    public function parseJobsListForPage(\JobScooper\Utils\SimpleHTMLHelper $objSimpHTML)
    {
		$ret = parent::parseJobsListForPage($objSimpHTML);
	
		if(!is_empty_value($ret))  {
			foreach($ret as $k => $v) {
				if(array_key_exists('Location', $v) && !is_empty_value($v['Location'])) {
					$cleanedLoc = str_replace($v['Title'], '', $v['Location']);
					if(!is_empty_value($cleanedLoc) && array_key_exists('PostedAt', $v) && !is_empty_value($v['PostedAt'])) {
						$cleanedLoc = str_replace($v['PostedAt'], '', $cleanedLoc);
					}
					$ret[$k]['Location'] = $cleanedLoc;
				}
			}
		}
		
		return $ret;
	}
}

class PluginDisney extends AbstractTalentBrew
{
    protected $JobSiteName = 'Disney';
    protected $JobPostingBaseUrl = 'https://jobs.disneycareers.com';
    protected $additionalBitFlags = [ C__JOB_ITEMCOUNT_NOTAPPLICABLE ];
    protected $JobListingsPerPage = 15;

    public function __construct()
    {
        parent::__construct();
        unset($this->arrListingTagSetup['TotalPostCount']);
        $this->arrListingTagSetup['JobPostItem'] = ['Selector' => 'section#search-results-list table tr'];
        $this->arrListingTagSetup['NextButton'] = ['Selector' => 'a.next', 'Index' => 0];


        $this->SearchUrlFormat = $this->JobPostingBaseUrl . "/search-jobs?k=&alp=6252001-5815135&alt=3";
    }
}

class AbstractTalentBrew extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    protected $SearchUrlFormat = "/search-jobs/***KEYWORDS***/***LOCATION***";
    //
    // BUGBUG:  Disney & Boeing are both hit or miss around returning the full set of listings correctly.
    //          Setting to ignore_mismatched to avoid the error results that will happen when they do.
    //
    protected $LocationType = 'location-city-comma-statecode';
    protected $additionalLoadDelaySeconds = 5;

    protected $JobListingsPerPage = 50;

    protected $arrListingTagSetup = [
        'TotalPostCount' => ['selector' => 'section#search-results h1', 'Attribute' => 'text', 'Pattern' =>  '/(.*?) .*/'],
        'TotalResultPageCount' => ['selector' => 'span.pagination-total-pages', 'Attribute' => 'text', 'Pattern' =>  '/of (.*)/'],
        'JobPostItem' => ['tag' => 'section#search-results-list ul li'],
        'Title' =>  ['Selector' => 'a h2'],
        'Url' =>  ['Selector' => 'a', 'Attribute' => 'href'],
        'JobSitePostId' =>  ['Selector' => 'a', 'Attribute' => 'data-job-id'],
        'Location' =>  ['Selector' => 'span', 'Attribute' => 'job-location'],
        'PostedAt' =>  ['Selector' => 'span', 'Attribute' => 'job-date-posted'],
        'NextButton' => ['Selector' => '#pagination-bottom a.next']
    ];

    public function __construct()
    {
        $this->additionalBitFlags['COMPANY'] = C__JOB_USE_SITENAME_AS_COMPANY;
        parent::__construct();
        $this->SearchUrlFormat = $this->JobPostingBaseUrl . $this->SearchUrlFormat;
    }
}
