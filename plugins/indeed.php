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


class PluginIndeed extends \JobScooper\Plugins\lib\AjaxHtmlSimplePlugin
{
    protected $siteName = 'Indeed';
    protected $nJobListingsPerPage = 50;
    protected $siteBaseURL = 'http://www.Indeed.com';
    protected $strBaseURLFormat = "https://www.indeed.com/jobs?as_and=***KEYWORDS***&as_phr=&as_any=&as_not=&as_ttl=&as_cmp=&jt=all&st=&salary=&radius=50&l=***LOCATION***&fromage=1&limit=50&sort=date***ITEM_NUMBER***&filter=0&psf=advsrch";
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';

    // Note:  C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS intentioanlly not set although Indeed supports it.  However, their support is too explicit of a search a will weed out
    //        too many potential hits to be worth it.
    protected $additionalFlags = [C__JOB_IGNORE_MISMATCHED_JOB_COUNTS, C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER];
    protected $paginationType = C__PAGINATION_PAGE_VIA_NEXTBUTTON;

    protected $arrListingTagSetup = array(
        'TotalPostCount' =>  array('selector' => 'div#searchCount', 'return_value_regex' => '/.*?of\s*(\d+).*?/'),
        'NoPostsFound' =>  array('selector' => 'body', 'index' => 0, 'return_attribute' => 'node', 'return_value_callback' => "checkNoJobResults"),
        'NextButton' => array('selector' => 'span.np'),
        'JobPostItem' => array('selector' => 'td#resultsCol div[data-tn-component=\'organicJob\']'),
        'Url' => array('selector' => 'a[data-tn-element=\'jobTitle\']', 'index'=> 0, 'return_attribute' => 'href'),
        'Title' => array('selector' => 'a[data-tn-element=\'jobTitle\']', 'index'=> 0, 'return_attribute' => 'plaintext'),
        'JobSitePostId' => array('selector' => 'span.tt_set a', 'index'=> 0, 'return_attribute' => 'id', 'return_value_regex' => '/[sj_]{0,3}(.*)/'),
        'Company' => array('selector' => 'span.company', 'index'=> 0),
        'Location' => array('selector' => 'span.location', 'index'=> 0),
        'PostedAt' => array('selector' => 'span.date', 'index'=> 0)
        );

    static function checkNoJobResults($var)
    {
        $ret = null;
        if(is_array($var))
            $var = $var[0];
        $node1 = $var->find("p.message");
        if(!is_null($node1) && count($node1) > 0) {
            $text = $node1[0]->plaintext;
            $ret = noJobStringMatch($text, "No jobs match your search");
        }
        else {
            $node2 = $var->find("div.bad_query h2");
            if (!is_null($node2) && count($node2) > 0) {
                $text = $node2[0]->plaintext;
                $ret = noJobStringMatch($text, "did not match");
            }
        }
        return $ret;
    }

    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 1) { return ""; }

        return "&start=" . $nItem;
    }
//
}


class PluginIndeedUK extends PluginIndeed
{
    protected $siteName = 'IndeedUK';
    protected $nJobListingsPerPage = 50;
    protected $siteBaseURL = 'http://www.Indeed.co.uk';
    protected $strBaseURLFormat = "https://www.indeed.co.uk/jobs?as_and=***KEYWORDS***&as_phr=&as_any=&as_not=&as_ttl=&as_cmp=&jt=all&st=&salary=&radius=50&l=***LOCATION***&fromage=1&limit=50&sort=date***ITEM_NUMBER***&filter=0&psf=advsrch";
    protected $typeLocationSearchNeeded = 'location-city';
    protected $countryCodes = array("GB");
}