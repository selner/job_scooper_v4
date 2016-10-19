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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');


class PluginZipRecruiter extends ClassJobsSitePlugin
{
    protected $siteName = 'ZipRecruiter';
    protected $siteBaseURL = 'https://jobs.ziprecruiter.com';
    protected $nJobListingsPerPage = 20;
    protected $strBaseURLFormat = "https://www.ziprecruiter.com/candidate/search?search=***KEYWORDS***&location=***LOCATION***&radius=25&page=***PAGE_NUMBER***&days=***NUMBER_DAYS***";
    protected $flagSettings = null;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $regex_link_job_id = '/^.*\/clk\/(.*)/i';

    function __construct($strBaseDir = null)
    {
        parent::__construct($strBaseDir);

        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS  | C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS | C__JOB_PREFER_MICRODATA;
    }

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
    function getDaysURLValue($days = null)
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
        $resultsSection = $objSimpHTML->find("h1[class='headline']");

        // get the text value of that node
        if($resultsSection != null)
        {
            $totalItemsText = trim($resultsSection[0]->plaintext);
            $count = trim(substr($totalItemsText, 0, strpos($totalItemsText, ' ')));

            return $count;
        }
        else
        {
            return -1;
        }

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
            if(isset($titleNode) && isset($titleNode[0]))
            {
                $item['job_title'] = $titleNode[0]->plaintext;
            }

            // If we couldn't parse the job title, it's not really a job
            // listing so just continue to the next one
            //
            if($item['job_title'] == '') continue;

            $titleLink = $node->find("a[class='clickable_target']");
            if(isset($titleLink) && isset($titleLink[0]))
            {
                $item['job_post_url'] = $titleLink[0]->href;
            }


            // get the id and parse it down to <name>-<identifier>
            $strExternalJobID = $node->attr['id'];
            $fMatch = preg_match('/quiz-card-(\w{1,}-?\w{0,}-?\w{0,})/i', $strExternalJobID, $arrExternalIDParts );
            assert($fMatch == true);
            $strExternalJobID = $arrExternalIDParts[1];

            // remove "remaining15" or similar if it exists
            $strExternalJobID = preg_replace('/remaining\d{1,3}/i', "", $strExternalJobID);

            // remove "_cpc" from the ID if it still exists
            $strExternalJobID = preg_replace('/_cpc/i', "", $strExternalJobID );

            $item['job_id'] = $strExternalJobID;

            $companyNode = $node->find("p[class='font12Phone clearLeft']");
            if(isset($companyNode) && isset($companyNode[0]))
            {
                $arrCompanyParts = explode(" - ", $companyNode[0]->plaintext);
                if(isset($arrCompanyParts) && count($arrCompanyParts)>=2)
                {
                    $company = str_ireplace("at ", "", $arrCompanyParts[0]);
                    $item['company'] = $company;
                    $item['location'] = $arrCompanyParts[1];
                }
            }

            $jobDetailsNode = $node->find("p[class='greenText font12 font11Phone'] span");
            if(isset($jobDetailsNode) && isset($jobDetailsNode[0]))
            {
                $strJobDetails = \Scooper\strScrub($jobDetailsNode[0]->plaintext, SIMPLE_TEXT_CLEANUP);
                $arrJobDetailsParts = explode(" ", $strJobDetails);

                $item['job_site_category'] = $arrJobDetailsParts[count($arrJobDetailsParts) - 1];
                $item['job_site_date'] = getDateForDaysAgo($arrJobDetailsParts[1]);
            }

            //
            // Call normalizeItem to standardize the resulting listing result
            //
            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}