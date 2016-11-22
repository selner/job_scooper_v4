<?php
/**
 * Copyright 2014-16 Bryan Selner
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



class PluginCraigslist extends ClassBaseSimpleJobSitePlugin
{
    protected $siteName = 'Craigslist';
    protected $nJobListingsPerPage = 100;
    protected $siteBaseURL = 'http://seattle.craigslist.org';
    protected $strBaseURLFormat = "http://***LOCATION***.craigslist.org/search/jjj?s=***ITEM_NUMBER***&catAbb=jjj&query=***KEYWORDS***&srchType=T";
    protected $additionalFlags = [C__JOB_BASETYPE_WEBPAGE_FLAGS, C__JOB_LOCATION_REQUIRES_LOWERCASE, C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS, C__JOB_PAGECOUNT_NOTAPPLICABLE__];
    protected $typeLocationSearchNeeded = 'location-city';
    protected $strKeywordDelimiter = "|";

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('tag' => 'li', 'attribute' => 'class', 'attribute_value' =>'result-row'),
        'tag_title' => array('tag' => 'a', 'attribute' => 'class', 'attribute_value' => 'result-title hdrlnk'),
        'tag_link' => array('tag' => 'a', 'attribute' => 'class', 'attribute_value' => 'result-title hdrlnk'),
        'tag_department' => array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'listing-department'),
        'tag_location' => array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'result-hood'),
        'tag_postdate' => array('tag' => 'time', 'attribute' => 'class', 'attribute_value' =>'result-date'),

        'regex_link_job_id' => '.*?/\w{3}\/\w{3}\/([^\/\.]+)/i'
    );

    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 0) { return 0; }

        return $nItem - 1;
    }

}

