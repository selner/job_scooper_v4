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



// TODO:  Make abstract class to power sites like http://www.careerbuilder.com/jobs/greenbay,wisconsin/category/engineering/?channel=en&siteid=gagbp037&sc_cmp1=JS_Sub_Loc_EN&lr=cbga_gbp
// just have to add the following terms per site &siteid=gagbp037&lr=cbga_gbp

class PluginCareerBuilder extends \JobScooper\Plugins\lib\AjaxHtmlSimplePlugin
{
    protected $siteName = 'CareerBuilder';
    protected $siteBaseURL = 'http://www.careerbuilder.com';
    protected $strBaseURLFormat = "http://www.careerbuilder.com/jobs-***KEYWORDS***-in-***LOCATION***?keywords=***KEYWORDS***&location=***LOCATION***&radius=50&page_number=***PAGE_NUMBER***&posted=***NUMBER_DAYS***";
    protected $additionalFlags = [C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES];
    protected $typeLocationSearchNeeded = 'location-city-dash-statecode';
    protected $additionalLoadDelaySeconds = 5;
    protected $nJobListingsPerPage = 25;

    protected $arrListingTagSetup = array(
        'TotalPostCount' => array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => "count"), 'return_attribute' => 'plaintext', 'return_value_regex' => '/[^\d]+(\d+).*?/'),
        'JobPostItem' => array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'jobs'), array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'job-row')),
        'Title' =>  array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'row', 'index' =>1), array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'column small-10'), array('tag' => 'h2'), array('tag' => 'a'), 'index'=> 0, 'return_attribute' => 'plaintext'),
        'Url' =>  array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'row', 'index' =>1), array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'column small-10'), array('tag' => 'h2'), array('tag' => 'a'), 'index'=> 0, 'return_attribute' => 'href'),
        'JobSitePostId' =>  array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'row', 'index' =>1), array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'column small-10'), array('tag' => 'h2'), array('tag' => 'a'), 'index'=> 0, 'return_attribute' => 'data-job-did'),
        'Company' =>  array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'row job-information'), array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'columns large-2 medium-3 small-12'), array('tag' => 'h4', 'attribute'=>'class', 'attribute_value'=>'job-text'),  array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'Location' =>  array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'row job-information'), array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'columns end large-2 medium-3 small-12'), array('tag' => 'h4', 'attribute'=>'class', 'attribute_value'=>'job-text'), 'return_attribute' => 'plaintext'),
        'PostedAt' =>  array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'column small-2 time-posted'), array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'show-for-medium-up'), 'return_attribute' => 'plaintext'),
        'EmploymentType' =>  array('selector' => 'div.job-row div.row.job-information div.columns.medium-6.large-8 h4.job-text.employment-info', 'return_attribute' => 'plaintext'),
        'NextButton' =>  array('selector' => '#next-button'),
    );

    protected function getKeywordURLValue($searchDetails) {
        $keywordval = parent::getKeywordURLValue($searchDetails);
        return strtolower($keywordval);
    }
}


class PluginCareerBuilderUK extends \JobScooper\Plugins\lib\AjaxHtmlSimplePlugin
{
    protected $siteName = 'CareerBuilderUk';
    protected $nJobListingsPerPage = 20;
    protected $siteBaseURL = 'http://https://www.careerbuilder.co.uk';
    protected $strBaseURLFormat = "https://www.careerbuilder.co.uk/search?q=***KEYWORDS***&sc=1&loc=***LOCATION***&pg=***PAGE_NUMBER***";
    protected $additionalFlags = [];
    protected $typeLocationSearchNeeded = 'location-city';
    protected $strKeywordDelimiter = "|";
    protected $paginationType = C__PAGINATION_PAGE_VIA_URL;
    protected $countryCodes = array("GB");

    protected $arrListingTagSetup = array(
        'NoPostsFound' => array('selector' => 'h1', 'return_attribute' => 'plaintext', 'return_value_callback' => "checkNoJobResults"),
        'TotalPostCount' => array('selector' => 'h1', 'index'=> 0, 'return_attribute' => 'plaintext', 'return_value_regex' => '/(\d+).*?/'),
        'JobPostItem' => array('selector' => 'article.job-list'),
        'Url' => array('selector' => 'a.job-title', 'return_attribute' => 'href'),
        'Title' => array('selector' => 'a.job-title', 'return_attribute' => 'plaintext'),
        'Location' => array('selector' => 'ul.inline-list li', 'index' => 0, 'return_attribute' => 'plaintext', 'return_value_regex' => '/\s*Location\s*(.*)/'),
        'PayRange' => array('selector' => 'ul.inline-list li', 'index' => 1, 'return_attribute' => 'plaintext', 'return_value_regex' => '/\s*Pay\s*(.*)/'),
        'Category' => array('selector' => 'ul.inline-list li', 'index' => 2, 'return_attribute' => 'plaintext', 'return_value_regex' => '/\s*Type\s*(\w+)\s*/'),
        'PostedAt' => array('selector' => 'ul.inline-list li span', 'index' => 4, 'return_attribute' => 'plaintext', 'return_value_regex' => '/\s*Posted\s*(.*)/'),
        'Company' => array('selector' => 'a.show-for-large-up', 'index' => 0, 'return_attribute' => 'plaintext'),
        'JobSitePostId' => array('selector' => 'a.job-title', 'return_attribute' => 'href', 'return_value_regex' => '/\/([^\/]*)\/\?.*/'),
    );

    static function checkNoJobResults($var)
    {
        return noJobStringMatch($var, "Nothing found");
    }

    protected function getKeywordURLValue($searchDetails) {
        $keywordval = parent::getKeywordURLValue($searchDetails);
        return strtolower($keywordval);
    }

}
