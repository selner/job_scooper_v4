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
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');



class PluginCraigslist extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'Craigslist';
    protected $nJobListingsPerPage = 100;
    protected $siteBaseURL = 'http://seattle.craigslist.org';
    protected $strBaseURLFormat = "http://***LOCATION***.craigslist.org/search/jjj?bundleDuplicates=1&query=%22***KEYWORDS***%22&srchType=T&searchNearby=1&s=***ITEM_NUMBER***";
//    protected $strBaseURLFormat = "http://***LOCATION***.craigslist.org/search/jjj?s=***ITEM_NUMBER***&catAbb=jjj&query=***KEYWORDS***&srchType=T&bundleDuplicates=1";
    // BUGBUG: craigslist treats sub-rows differently for counting results in different cases.  When a single page of results is returned, they are included in the overall count
    //         But when a multi-page result set is returned, they are not! Setting C__JOB_IGNORE_MISMATCHED_JOB_COUNTS to work around this.
    protected $additionalFlags = [C__JOB_LOCATION_REQUIRES_LOWERCASE, C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS, C__JOB_IGNORE_MISMATCHED_JOB_COUNTS];
    protected $typeLocationSearchNeeded = 'location-city';
    protected $paginationType = C__PAGINATION_PAGE_VIA_URL;
    protected $strKeywordDelimiter = "|";

    protected $arrListingTagSetup = array(
        'tag_listings_noresults' => array('tag' => 'div', 'attribute'=>'class', 'attribute_value' => 'noresults', 'return_attribute' => 'plaintext', 'return_value_callback' => "isNoJobResults"),
        'tag_listings_count' => array('tag' => 'span', 'attribute'=>'class', 'attribute_value' => 'totalcount', 'index'=> 0, 'return_attribute' => 'plaintext'),
        'tag_listings_section' => array('selector' => 'li[data-pid]', 'return_value_callback' => "filterListingNodes"),
        'tag_link' => array('tag' => '*', 'attribute' => 'class', 'attribute_value' => 'hdrlnk', 'index'=> 0, 'return_attribute' => 'href'),
        'tag_title' => array('tag' => '*', 'attribute' => 'class', 'attribute_value' => 'hdrlnk', 'index'=> 0, 'return_attribute' => 'plaintext'),
        'tag_job_id' => array('tag' => '*', 'attribute' => 'class', 'attribute_value' => 'hdrlnk', 'index'=> 0, 'return_attribute' => 'data-id'),
        'tag_department' => array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'listing-department', 'index'=> 0),
        'tag_location' => array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'result-hood', 'index'=> 0),
        'tag_job_posting_date' => array('tag' => 'time', 'attribute' => 'class', 'attribute_value' =>'result-date', 'index'=> 0)
    );

    static function isNoJobResults($var)
    {
        return noJobStringMatch($var, "nothing found");
    }

    static function filterListingNodes($var)
    {
        $retNodes = array();
        foreach($var as $v)
        {
            $parent = $v->parentNode();
            if(stristr($parent->class, "duplicate") != "")
                continue;

            $retNodes[] = $v;
        }

        return $retNodes;
    }


    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 0) { return 0; }

        return $nItem - 1;
    }

}

