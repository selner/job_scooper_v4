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

class PluginCareerBuilder extends \JobScooper\BasePlugin\Classes\AjaxHtmlSimplePlugin
{
    protected $JobSiteName = 'CareerBuilder';
    protected $JobPostingBaseUrl = 'http://www.careerbuilder.com';
    protected $SearchUrlFormat = "http://www.careerbuilder.com/jobs-***KEYWORDS***-in-***LOCATION***?keywords=***KEYWORDS***&location=***LOCATION***&radius=50&page_number=***PAGE_NUMBER***&posted=***NUMBER_DAYS***&sc=date_desc";
    protected $additionalBitFlags = [C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES, C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER];
    protected $LocationType = 'location-city-dash-statecode';
    protected $additionalLoadDelaySeconds = 5;
    protected $JobListingsPerPage = 25;

    protected $arrListingTagSetup = array(
        'NoPostsFound' => array('selector' => 'div.noresults h3', 'return_attribute' => 'text', 'return_value_callback' => "matchesNoResultsPattern", 'callback_parameter' => 'no results were found'),
        'TotalPostCount' => array('selector' => 'div.count', 'return_value_regex' => '/.*?(\d+).?Job/'),
        'JobPostItem' => array('selector' => 'div.job-row'),
        'Title' =>  array('selector' => 'h2 a', 'return_attribute' => 'text', 'index' => 0),
        'Url' =>  array('selector' => 'h2 a', 'return_attribute' => 'href', 'index' => 0),
        'JobSitePostId' =>  array('selector' => 'h2 a', 'index' => 0, 'return_attribute' => 'data-job-did'),
        'Company' =>  array('selector' => 'div.job-information div h4 a'),
        'EmploymentType' =>  array('selector' => 'div.job-information div h4.job-text', 'index'=> 0),
        'Location' =>  array('selector' => 'div.job-information div h4.job-text', 'index'=> 2),
        'PostedAt' =>  array('selector' => 'div.time-posted div.show-for-medium-up', 'return_attribute' => 'text'),
        'NextButton' =>  array('selector' => 'a#next-button'),
    );

    protected function getKeywordURLValue(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails) {
        $keywordval = parent::getKeywordURLValue($searchDetails);
        return strtolower($keywordval);
    }
}


class PluginCareerBuilderUK extends \JobScooper\BasePlugin\Classes\AjaxHtmlSimplePlugin
{
    protected $JobSiteName = 'CareerBuilderUk';
    protected $JobListingsPerPage = 20;
    protected $JobPostingBaseUrl = 'http://https://www.careerbuilder.co.uk';
    protected $SearchUrlFormat = "https://www.careerbuilder.co.uk/search?q=***KEYWORDS***&sc=1&loc=***LOCATION***&pg=***PAGE_NUMBER***&sc=1";
    protected $additionalBitFlags = [C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER];
    protected $LocationType = 'location-city';
    protected $strKeywordDelimiter = "|";
    protected $PaginationType = C__PAGINATION_PAGE_VIA_URL;
    protected $CountryCodes = array("UK");

    protected $arrListingTagSetup = array(
        'NoPostsFound' => array('selector' => 'h1', 'return_attribute' => 'text', 'return_value_callback' => "checkNoJobResults"),
        'TotalPostCount' => array('selector' => 'h1', 'index'=> 0, 'return_attribute' => 'text', 'return_value_regex' => '/(\d+).*?/'),
        'JobPostItem' => array('selector' => 'article.job-list'),
        'Url' => array('selector' => 'a.job-title', 'return_attribute' => 'href'),
        'Title' => array('selector' => 'a.job-title', 'return_attribute' => 'text'),
        'Location' => array('selector' => 'ul.inline-list li', 'index' => 0, 'return_attribute' => 'text', 'return_value_regex' => '/\s*Location\s*(.*)/'),
        'PayRange' => array('selector' => 'ul.inline-list li', 'index' => 1, 'return_attribute' => 'text', 'return_value_regex' => '/\s*Pay\s*(.*)/'),
        'Category' => array('selector' => 'ul.inline-list li', 'index' => 2, 'return_attribute' => 'text', 'return_value_regex' => '/\s*Type\s*(\w+)\s*/'),
        'PostedAt' => array('selector' => 'ul.inline-list li span', 'index' => 4, 'return_attribute' => 'text', 'return_value_regex' => '/\s*Posted\s*(.*)/'),
        'Company' => array('selector' => 'a.show-for-large-up', 'index' => 0, 'return_attribute' => 'text'),
        'JobSitePostId' => array('selector' => 'a.job-title', 'return_attribute' => 'href', 'return_value_regex' => '/\/([^\/]*)\/\?.*/'),
    );

    static function checkNoJobResults($var)
    {
        return noJobStringMatch($var, "Nothing found");
    }

    protected function getKeywordURLValue(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails) {
        return strtolower(parent::getKeywordURLValue($searchDetails));
    }

}
