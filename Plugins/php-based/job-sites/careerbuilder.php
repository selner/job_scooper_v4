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



// TODO:  Make abstract class to power sites like http://www.careerbuilder.com/jobs/greenbay,wisconsin/category/engineering/?channel=en&siteid=gagbp037&sc_cmp1=JS_Sub_Loc_EN&lr=cbga_gbp
// just have to add the following terms per site &siteid=gagbp037&lr=cbga_gbp

class PluginCareerBuilder extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    protected $JobSiteName = 'CareerBuilder';
    protected $JobPostingBaseUrl = 'http://www.careerbuilder.com';
    #	protected $SearchUrlFormat = "http://www.careerbuilder.com/jobs-***KEYWORDS***-in-***LOCATION***?keywords=***KEYWORDS***&location=***LOCATION***&radius=50&page_number=***PAGE_NUMBER***&posted=***NUMBER_DAYS***&sc=date_desc&sort=date_desc";
    protected $SearchUrlFormat = "http://www.careerbuilder.com/jobs-***KEYWORDS***-in-***LOCATION***?keywords=***KEYWORDS***&location=***LOCATION***&radius=50&posted=3&sort=date_desc";
    protected $additionalBitFlags = [C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES, C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER, C__JOB_KEYWORD_REQUIRES_LOWERCASE];
    protected $LocationType = 'location-city-dash-statecode';
    protected $additionalLoadDelaySeconds = 5;
    protected $JobListingsPerPage = 25;

    protected $arrListingTagSetup = array(
        'NoPostsFound' => array('Selector' => 'div.noresults h3', 'Attribute' => 'text', 'Callback' => "matchesNoResultsPattern", 'CallbackParameter' => 'no results were found'),
        'TotalPostCount' => array('Selector' => 'div.count', 'Pattern' => '/.*?([\d]+).*?Job/'),
        'JobPostItem' => array('Selector' => 'div.job-row'),
        'Title' =>  array('Selector' => 'h2 a', 'Attribute' => 'text', 'Index' => 0),
        'Url' =>  array('Selector' => 'h2 a', 'Attribute' => 'href', 'Index' => 0),
        'JobSitePostId' =>  array('Selector' => 'h2 a', 'Index' => 0, 'Attribute' => 'data-job-did'),
        'Company' =>  array('Selector' => 'h4.job-text a', 'Index' => 0),
        'EmploymentType' =>  array('Selector' => 'h4.employment-info', 'Index'=> 0),
        'Location' =>  array('Selector' => 'h4.job-text', 'Index'=> 2),
        'PostedAt' =>  array('Selector' => 'div.time-posted div em', 'Index' => 0, 'Attribute' => 'text'),
        'NextButton' =>  array('Selector' => 'a#next-button'),
    );

    /**
     * @return bool
     * @throws \Exception
     */
    protected function goToNextPageOfResultsViaNextButton()
    {
        $secs = $this->additionalLoadDelaySeconds * 1000;
        if ($secs <= 0) {
            $secs = 1000;
        }
        
        $jsEscSelector = swapDoubleSingleQuotes($this->selectorMoreListings);


        LogMessage("Clicking button [{$jsEscSelector}] to go to the next page of results...");

	    $jsCode = /** @lang javascript */ <<<JSCODE
            document.getElementById('direct_moreLessLinks_listingDiv').setAttribute('data-num-items', 50);
            scroll = setTimeout(doNextPage, 5000);
            function doNextPage() 
            {
                var loadnext = document.querySelector("{$jsEscSelector}");
                if(loadnext != null && !typeof(loadnext.click) !== \"function\" && loadnext.length >= 1) {
                    loadnext = loadnext[0];  
                } 
                if(loadnext != null) {    
                    console.log('Clicked load next results control a#next-button...');
                    loadnext.click();  
                } 
                else 
                { 
                    console.log('No next button found to click.');
                }
            }
              scroll = setTimeout(doNextPage, {$secs});
JSCODE;

        $this->runJavaScriptSnippet($jsCode, false);

        sleep($this->additionalLoadDelaySeconds > 0 ? $this->additionalLoadDelaySeconds : 2);

        return true;
    }
}


class PluginCareerBuilderUK extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    protected $JobSiteName = 'CareerBuilderUk';
    protected $JobListingsPerPage = 20;
    protected $JobPostingBaseUrl = 'http://https://www.careerbuilder.co.uk';
    protected $SearchUrlFormat = "https://www.careerbuilder.co.uk/search?q=***KEYWORDS***&sc=1&loc=***LOCATION***&pg=***PAGE_NUMBER***&sc=1";
    protected $additionalBitFlags = [C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER, C__JOB_KEYWORD_REQUIRES_LOWERCASE];
    protected $LocationType = 'location-city';
    protected $PaginationType = C__PAGINATION_PAGE_VIA_URL;
    protected $CountryCodes = array("UK");

    protected $arrListingTagSetup = array(
        'NoPostsFound' => array('Selector' => 'div.row.section-body-container.full-width div div strong', 'Index' => 0, 'Attribute' => 'text',  'Callback' => 'matchesNoResultsPattern', 'CallbackParameter' => "no results"),
        'TotalPostCount' => array('Selector' => 'h1', 'Index'=> 0, 'Attribute' => 'text', 'Pattern' => '/(\d+).*?/'),
        'JobPostItem' => array('Selector' => 'article.job-list'),
        'Url' => array('Selector' => 'a.job-title', 'Attribute' => 'href'),
        'Title' => array('Selector' => 'a.job-title', 'Attribute' => 'text'),
        'Location' => array('Selector' => 'ul.inline-list li', 'Index' => 0, 'Attribute' => 'text', 'Pattern' => '/\s*Location\s*(.*)/'),
        'PayRange' => array('Selector' => 'ul.inline-list li', 'Index' => 1, 'Attribute' => 'text', 'Pattern' => '/\s*Pay\s*(.*)/'),
        'Category' => array('Selector' => 'ul.inline-list li', 'Index' => 2, 'Attribute' => 'text', 'Pattern' => '/\s*Type\s*(\w+)\s*/'),
        'PostedAt' => array('Selector' => 'ul.inline-list li span', 'Index' => 4, 'Attribute' => 'text', 'Pattern' => '/\s*Posted\s*(.*)/'),
        'Company' => array('Selector' => 'a.show-for-large-up', 'Index' => 0, 'Attribute' => 'text'),
        'JobSitePostId' => array('Selector' => 'a.job-title', 'Attribute' => 'href', 'Pattern' => '/\/([^\/]*)\/\?.*/'),
    );

    /**
     * @param $searchDetails
     *
     * @return mixed
     * @throws \Exception
     */
    public function doFirstPageLoad(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
    {
	    $jsCode = /** @lang javascript */ <<<JSCODE
			var elem = document.querySelector('a.close-reveal-modal');
			if(elem != null)
			{
				elem.click();
			}
JSCODE;
        $this->getSimpleHtmlDomFromSeleniumPage($searchDetails, $searchDetails->getSearchStartUrl());
        $this->runJavaScriptSnippet($jsCode);
    }
}
