<?php

    /**
     * Copyright 2014 Bryan Selner
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
require_once(__ROOT__ . '/include/ClassJobsSitePluginCommon.php');


/**
 * Class PluginExample
 *
 * Add the code to implement the necessary functions and then
 * add a record to SitePlugins.php for that new plugin.  Any search
 * specified with the site name you added will call this new plugin to
 * process the results.
 *

 */
class PluginZipRecruiter extends ClassJobsSitePlugin
{
    protected $siteName = 'ZipRecruiter';
    protected $siteBaseURL = 'https://jobs.ziprecruiter.com';
    protected $nJobListingsPerPage = 20;
    protected $fQuoteKeywords = true;
    protected $strBaseURLFormat = "https://jobs.ziprecruiter.com/candidate/search?search=***KEYWORDS***&location=***LOCATION***&radius=25&page=***PAGE_NUMBER***&days=***NUMBER_DAYS***";


    // if this is a client-side HTML download plugin, you will need to add a script
    // for driving Safari to download the files and set that script name here.
    //
    // This value is unused for XML or server-side webpage download plugins.
    protected $strFilePath_HTMLFileDownloadScript = null;


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
    function getDaysURLValue($days)
    {
        $ret = "1";

        if($days != null)
        {
            switch($days)
            {
                case ($days>5 && $days<=10):
                    $ret = "10";
                    break;

                case ($days>1 && $days<=5):
                    $ret = "5";
                    break;


                case $days<=1:
                default:
                    $ret = "1";
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
        $resultsSection = $objSimpHTML->find("strong[id='default_total_entries']");

        // get the text value of that node
        $totalItemsText = $resultsSection[0]->plaintext;

        return $totalItemsText;
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


        $nodesJobs= $objSimpHTML->find('li[class="job_row_ai"]');


        foreach($nodesJobs as $node)
        {
            //
            // get a new record with all columns set to null
            //
            $item = $this->getEmptyJobListingRecord();


            $item['job_site'] = $this->siteName;
            $item['date_pulled'] = \Scooper\getTodayAsString();


            $titleNode = $node->find("h4[class='font14 fBold mb2 font13Phone']");
            $item['job_title'] = $titleNode[0]->plaintext;


            $titleLink = $node->find("a[class='clickable_target']")[0];
            $item['job_post_url'] = $titleLink->href;


            // If we couldn't parse the job title, it's not really a job
            // listing so just continue to the next one
            //
            if($item['job_title'] == '') continue;

            $idLink = $node->find("a[class='toggle_job_save btn btn-small']");
            $jobID = $idLink[0]->attr['data-external_job_id'];
            $item['job_id'] = $jobID;

            $companyNode = $node->find("p[class='font12Phone clearLeft']");
            $arrCompanyParts = explode(" - ", $companyNode[0]->plaintext);
            $item['company'] = $arrCompanyParts [0];
            $item['location'] = $arrCompanyParts[1];


            $jobDetailsNode = $node->find("p[class='greenText font12 font11Phone'] span");
            $strJobDetails = \Scooper\strScrub($jobDetailsNode[0]->plaintext, SIMPLE_TEXT_CLEANUP);
            $arrJobDetailsParts = explode(" ", $strJobDetails);

            $item['job_site_category'] = $arrJobDetailsParts[5];
            if(is_numeric($arrJobDetailsParts[1]) )
            {
                $daysToSubtract = $arrJobDetailsParts[1];
            }
            elseif(strcasecmp($arrJobDetailsParts[1], "yesterday") == 0)
            {
                $daysToSubtract = 1;
            }
            else
            {
                $daysToSubtract = 0;
            }
            $date = new DateTime();
            $date->modify("-".$daysToSubtract." days");
            $item['job_site_date'] = $date->format('Y-m-d');

            //
            // Call normalizeItem to standardize the resulting listing result
            //
            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}