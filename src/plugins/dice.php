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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');


class PluginDice extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'dice';
    protected $siteBaseURL = 'http://www.dice.com';
    protected $strBaseURLFormat = 'https://www.dice.com/jobs/advancedResult.html?for_one=&for_all=***KEYWORDS***&for_exact=&for_none=&for_jt=&for_com=&for_loc=***LOCATION***&sort=date&limit=100&radius=50';
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $nJobListingsPerPage = 100;
    protected $additionalLoadDelaySeconds = 5;
    protected $additionalFlags = [ C__JOB_PAGECOUNT_NOTAPPLICABLE__, C__JOB_CLIENTSIDE_PAGE_VIA_JS ] ;

    function isNoResults($var)
    {
        if (stristr($var, "No jobs found") != "") {
            return true;
        }

        return null;
    }

    protected $arrListingTagSetup = array(

        'tag_listings_count' => array(array('tag' => 'span', 'attribute' => 'id', 'attribute_value' => 'posiCountId'), 'attribute_value' => 'plaintext', 'return_value_regex' => '/.*?(\d+).*?/'),
        'tag_listings_noresults' => array('selector' => 'h1', 'return_attribute' => 'plaintext', 'return_value_callback' => "PluginDice::isNoResults"),
        'tag_listings_section' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'complete-serp-result-div'),
        'tag_title' => array('selector' => 'div.serp-result-content ul:nth-child(3) li:nth-child(1) h3 a', 'return_attribute' => 'plaintext'),
        'tag_link' => array('selector' => 'div.serp-result-content ul:nth-child(3) li:nth-child(1) h3 a', 'return_attribute' => 'href'),
        'tag_job_id' => array('selector' => 'div.serp-result-content ul:nth-child(3) li:nth-child(1) h3 a', 'return_attribute' => 'value'),
        'tag_company' => array(array('tag' => 'li', 'attribute' => 'class', 'attribute_value' => 'employer'), array('tag' => 'span', 'attribute' => 'class', 'attribute_value' => 'hidden-xs'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_location' => array(array('tag' => 'li', 'attribute' => 'class', 'attribute_value' => 'location'), 'return_attribute' => 'plaintext'),
        'tag_job_posting_date' => array(array('li' => 'span', 'attribute' => 'class', 'attribute_value' => 'posted'), 'return_attribute' => 'plaintext'),
        'tag_next_button' => array('selector' => 'span.icon-filled-arrow-66')
    );

}