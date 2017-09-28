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
require_once dirname(dirname(__FILE__))."/bootstrap.php";


// TODO:  Make abstract class to power sites like http://www.careerbuilder.com/jobs/greenbay,wisconsin/category/engineering/?channel=en&siteid=gagbp037&sc_cmp1=JS_Sub_Loc_EN&lr=cbga_gbp
// just have to add the following terms per site &siteid=gagbp037&lr=cbga_gbp

class PluginCareerBuilder extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'CareerBuilder';
    protected $siteBaseURL = 'http://www.careerbuilder.com';
    protected $strBaseURLFormat = "http://www.careerbuilder.com/jobs-***KEYWORDS***-in-***LOCATION***?keywords=***KEYWORDS***&location=***LOCATION***&radius=50&page_number=***PAGE_NUMBER***&posted=***NUMBER_DAYS***";
    protected $additionalFlags = [C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES];
    protected $typeLocationSearchNeeded = 'location-city-dash-statecode';
    protected $additionalLoadDelaySeconds = 5;
    protected $nJobListingsPerPage = 25;

    protected $arrListingTagSetup = array(
        'tag_listings_count' => array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => "count"), 'return_attribute' => 'plaintext', 'return_value_regex' => '/[^\d]+(\d+).*?/'),
        'tag_listings_section' => array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'jobs'), array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'job-row')),
        'tag_title' =>  array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'row', 'index' =>1), array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'column small-10'), array('tag' => 'h2'), array('tag' => 'a'), 'index'=> 0, 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'row', 'index' =>1), array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'column small-10'), array('tag' => 'h2'), array('tag' => 'a'), 'index'=> 0, 'return_attribute' => 'href'),
        'tag_job_id' =>  array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'row', 'index' =>1), array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'column small-10'), array('tag' => 'h2'), array('tag' => 'a'), 'index'=> 0, 'return_attribute' => 'data-job-did'),
        'tag_company' =>  array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'row job-information'), array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'columns large-2 medium-3 small-12'), array('tag' => 'h4', 'attribute'=>'class', 'attribute_value'=>'job-text'),  array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_location' =>  array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'row job-information'), array('tag' => 'div', 'attribute'=>'class', 'attribute_value'=>'columns end large-2 medium-3 small-12'), array('tag' => 'h4', 'attribute'=>'class', 'attribute_value'=>'job-text'), 'return_attribute' => 'plaintext'),
        'tag_job_posting_date' =>  array(array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'column small-2 time-posted'), array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'show-for-medium-up'), 'return_attribute' => 'plaintext'),
        'tag_employment_type' =>  array('selector' => 'div.job-row div.row.job-information div.columns.medium-6.large-8 h4.job-text.employment-info', 'return_attribute' => 'plaintext'),
        'tag_next_button' =>  array('selector' => '#next-button'),
    );

    protected function getKeywordURLValue($searchDetails) {
        $keywordval = parent::getKeywordURLValue($searchDetails);
        return strtolower($keywordval);
    }
}


class PluginCareerBuilderUK extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'CareerBuilderUk';
    protected $nJobListingsPerPage = 20;
    protected $siteBaseURL = 'http://https://www.careerbuilder.co.uk';
    protected $strBaseURLFormat = "https://www.careerbuilder.co.uk/search?q=***KEYWORDS***&sc=1&loc=***LOCATION***&pg=***PAGE_NUMBER***";
    protected $additionalFlags = [];
    protected $typeLocationSearchNeeded = 'location-city';
    protected $strKeywordDelimiter = "|";
    protected $paginationType = C__PAGINATION_PAGE_VIA_URL;
    protected $countryCodes = array("UK");

    protected $arrListingTagSetup = array(
        'tag_listings_noresults' => array('selector' => 'h1', 'return_attribute' => 'plaintext', 'return_value_callback' => "isNoJobResults"),
        'tag_listings_count' => array('selector' => 'h1', 'index'=> 0, 'return_attribute' => 'plaintext', 'return_value_regex' => '/(\d+).*?/'),
        'tag_listings_section' => array('selector' => 'article.job-list'),
        'tag_link' => array('selector' => 'a.job-title', 'return_attribute' => 'href'),
        'tag_title' => array('selector' => 'a.job-title', 'return_attribute' => 'plaintext'),
        'tag_location' => array('selector' => 'ul.inline-list li', 'index' => 0, 'return_attribute' => 'plaintext', 'return_value_regex' => '/\s*Location\s*(\w+)\s*/'),
        'tag_payrange' => array('selector' => 'ul.inline-list li', 'index' => 1, 'return_attribute' => 'plaintext', 'return_value_regex' => '/\s*Type\s*(\w+)\s*/'),
        'tag_category' => array('selector' => 'ul.inline-list li', 'index' => 2, 'return_attribute' => 'plaintext', 'return_value_regex' => '/\s*Posted\s*Posted\s*(\w+)\s*/'),
        'tag_job_posting_date' => array('selector' => 'ul.inline-list li span', 'index' => 4, 'return_attribute' => 'plaintext'),
        'tag_company' => array('selector' => 'a.show-for-large-up', 'index' => 0, 'return_attribute' => 'plaintext'),
        'tag_job_id' => array('selector' => 'a.job-title', 'return_attribute' => 'href', 'return_value_regex' => '/.*?\/(.*?)\/\?.*/'),
    );

    static function isNoJobResults($var)
    {
        return noJobStringMatch($var, "Nothing found");
    }

    protected function getKeywordURLValue($searchDetails) {
        $keywordval = parent::getKeywordURLValue($searchDetails);
        return strtolower($keywordval);
    }

}
