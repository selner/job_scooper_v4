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




class PluginLinkUp extends ClassHTMLJobSitePlugin
{
    protected $siteName = 'LinkUp';
    protected $nJobListingsPerPage = 50;
    protected $siteBaseURL = 'http://www.linkup.com';
    protected $strBaseURLFormat = "http://www.linkup.com/results.php?q=***KEYWORDS***&l=***LOCATION***&sort=d&tm=***NUMBER_DAYS***&page=***PAGE_NUMBER***&p=50";
    protected $additionalFlags = [C__JOB_PAGE_VIA_URL];
    protected $typeLocationSearchNeeded = 'location-city-comma-state';
    protected $strKeywordDelimiter = "or";

    function getDaysURLValue($days = null)
    {
        $ret = "1d";

        if ($days != null) {
            switch ($days) {
                case ($days > 3 && $days <= 7):
                    $ret = "7d";
                    break;

                case ($days >= 3 && $days < 7):
                    $ret = "3d";
                    break;


                case $days <= 1:
                default:
                    $ret = "1d";
                    break;

            }
        }

        return $ret;

    }

    protected $arrListingTagSetup = array(
        'tag_listings_count' => array('tag' => '#search-showing', 'return_value_regex' => '/\d+\s\-\s\d+[\sof]+([\d,]+).*/'),
        'tag_listings_section' => array('selector' => 'div.listing', 'return_value_callback' => "PluginLinkup::filterSponsoredAds"),
        'tag_title' => array('selector' => 'a.listing-title strong'),
        'tag_job_id' => array('selector' => 'div.listing', 'return_attribute' => 'data-hash'),
        'tag_link' => array('selector' => 'a.listing-title', 'return_attribute' => 'href'),
        'tag_company' => array('selector' => 'span.listing-company', 'return_attribute' => 'plaintext'),
        'tag_location' => array('selector' => 'span.listing-location', 'return_attribute' => 'plaintext'),
        'tag_job_posting_date' => array('selector' => 'span.listing-date', 'return_attribute' => 'plaintext'),
        'tag_department' => array('selector' => 'span.listing-tag', 'return_attribute' => 'plaintext')
    );


    function filterSponsoredAds($var)
    {
        $retArray = Array();
        if (!is_null($var) && is_array($var) && count($var) > 0) {
            foreach ($var as $jobnode) {
                if (array_key_exists('class', $jobnode->attr) && stristr($jobnode->attr['class'], "sponsor") != "")
                    continue;
                $retArray[] = $jobnode;
            }
        }
        return $retArray;

    }
}