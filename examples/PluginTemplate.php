<?php

    /**
     * Copyright 2014-15 Bryan Selner
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
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');


/**
 * Class PluginExample
 *
 * Add the code to implement the necessary functions and then
 * add a record to SitePlugins.php for that new plugin.  Any search
 * specified with the site name you added will call this new plugin to
 * process the results.
 *

 */
class PluginExample extends ClassJobsSitePlugin
{
    protected $siteName = 'ExampleSiteName';
    protected $siteBaseURL = 'http://www.examplesite.com';

    /**
     * If the site does not have a URL parameter for number of days
     * then set the plugin flag to C__JOB_DAYS_VALUE_NOTAPPLICABLE__
     * in the SitePlugins.php file and just comment out this function.
     *
     * getDaysURLValue returns the value that is used to replace
     * the ***DAYS*** token in the search URL for the number of
     * days requested.
     *
     * @param $days
     * @return int|string
     */
    function getDaysURLValue($days = null) {
        $ret = "1d";

        if(isset($days))
        {
            switch($days)
            {
                case ($days>3 && $days<=7):
                    $ret = "7d";
                    break;

                case ($days>=3 && $days<7):
                    $ret = "3d";
                    break;


                case $days<=1:
                default:
                    $ret = "1d";
                    break;

            }
        }

        return $ret;

    }

    /**
     * parseTotalResultsCount
     *
     * If the site does not show the total number of results
     * then set the plugin flag to C__JOB_PAGECOUNT_NOTAPPLICABLE__
     * in the SitePlugins.php file and just comment out this function.
     *
     * parseTotalResultsCount returns the total number of listings that
     * the search returned by parsing the value from the returned HTML
     * *
     * @param $objSimpHTML
     * @return string|null
     */
    function parseTotalResultsCount($objSimpHTML)
    {
        //
        // Find the HTML node that holds the result count
        //
        $resultsSection= $objSimpHTML->find("div[id='search-showing']");

        // get the text value of that node
        $totalItemsText = $resultsSection[0]->plaintext;

        // If the node text is something like "44 of 104 results"
        // then split the string by the ' ' character and return
        // the right array item for that number.
        //
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = $arrItemItems[4];

        // parse out any commas so that the string returned is purely digits
        //
        return str_replace(",", "", $strTotalItemsCount);
    }

    /**
    /**
     * parseJobsListForPage
     *
     * This does the heavy lifting of parsing each job record from the
     * page's HTML it was passed.
     * *
     * @param $objSimpHTML
     * @return array|null
     */
    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('div[class="listing"]');


        foreach($nodesJobs as $node)
        {
            //
            // get a new record with all columns set to null
            //
            $item = $this->getEmptyJobListingRecord();


            $item['job_site'] = $this->siteName;
            $item['date_pulled'] = \Scooper\getTodayAsString();

            $titleLink = $node->find("a[class='listing-title']")[0];
            $item['job_title'] = $titleLink->firstChild()->plaintext;

            // If we couldn't parse the job title, it's not really a job
            // listing so just continue to the next one
            //
            if($item['job_title'] == '') continue;

            $item['job_post_url'] = $titleLink->href;
            $item['company'] = $node->find("span[class='listing-company']")[0]->plaintext;
            $item['job_id'] = $node->attr['data-hash'];
            $item['location'] = trim($node->find("span[class='listing-location'] span")[0]->plaintext) . "-" .
                    trim($node->find("span[class='listing-location'] span")[1]->plaintext);

            $item['job_site_category'] = $node->find("span[class='listing-tag']")[0]->plaintext;
            $item['job_site_date'] = $node->find("span[class='listing-date']")[0]->plaintext;


            //
            // Call normalizeItem to standardize the resulting listing result
            //
            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}