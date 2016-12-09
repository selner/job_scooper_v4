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
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');

abstract class ClassBaseJobsAPIPlugin extends ClassBaseSimpleJobSitePlugin
{
    protected $siteBaseURL = '';
    protected $siteName = '';

    function __construct($strBaseDir = null)
    {
        $this->additionalFlags = [C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__, C__JOB_PAGECOUNT_NOTAPPLICABLE__, C__JOB_ITEMCOUNT_NOTAPPLICABLE__];
        parent::__construct($strBaseDir);
    }
    function getSearchJobsFromAPI($searchDetails) { return VALUE_NOT_SUPPORTED; }

    protected function _getMyJobsForSearchFromJobsAPI_($searchDetails)
    {
        $nItemCount = 0;

        $arrSearchReturnedJobs = [];
        $GLOBALS['logger']->logLine("Downloading count of " . $this->siteName ." jobs for search '".$searchDetails['key']. "'", \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $pageNumber = 1;
        $noMoreJobs = false;
        while($noMoreJobs != true)
        {
            $arrPageJobsList = [];
            $apiJobs = $this->getSearchJobsFromAPI($searchDetails, $pageNumber);

            foreach($apiJobs as $job)
            {
                $item = $this->getEmptyJobListingRecord();
                $item['job_site'] = $this->siteName;
                $item['job_title'] = $job->name;
                $item['job_id'] = $job->sourceId;
                if($item['job_id'] == null)
                    $item['job_id'] = $job->url;

                if(strlen(trim($item['job_title'])) == 0 || strlen(trim($item['job_id'])) == 0)
                {
                    continue;
                }
                $item['location'] = $job->location;
                $item['company'] = $job->company;
                if($job->datePosted != null)
                    $item['job_site_date'] = $job->datePosted->format('D, M d');
                $item['job_post_url'] = $job->url;

                $item = $this->normalizeItem($item);
                $strCurrentJobIndex = getArrayKeyValueForJob($item);
                $arrPageJobsList[$strCurrentJobIndex] = $item;
                $nItemCount += 1;
            }
            if(count($arrPageJobsList) < $this->nJobListingsPerPage)
            {
                addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);
                $noMoreJobs = true;
            }
            else
            {
                addJobsToJobsList($arrSearchReturnedJobs, $arrPageJobsList);
            }
            $pageNumber++;
        }

        $GLOBALS['logger']->logLine($this->siteName . "[".$searchDetails['name']."]" .": " . $nItemCount . " jobs found." .PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
        return $arrSearchReturnedJobs;
    }
}


abstract class ClassSimpleFullPageJobSitePlugin extends ClassBaseSimpleJobSitePlugin
{
    protected $childSiteURLBase = '';
    protected $childSiteListingPage = '';

    function __construct($strOutputDirectory = null)
    {
        return parent::__construct($strOutputDirectory);
    }


    protected function _getURLfromBase_($searchDetails, $nPage = null, $nItem = null)
    {
        return $this->childSiteListingPage;
    }
}


abstract class ClassBaseMicroDataPlugin extends ClassBaseSimpleJobSitePlugin
{
    protected $siteBaseURL = '';
    protected $siteName = '';

    function __construct($strBaseDir = null)
    {
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_RETURN_ALL_JOBS_ON_SINGLE_PAGE_NO_LOCATION  | C__JOB_PREFER_MICRODATA;
        parent::__construct($strBaseDir);
    }

}


abstract class ClassBaseSimpleJobSitePlugin extends ClassJobsSitePlugin
{
    protected $siteName = '';
    protected $siteBaseURL = '';
    protected $additionalFlags = [C__JOB_SEARCH_RESULTS_TYPE_WEBPAGE__, C__JOB_PAGECOUNT_NOTAPPLICABLE__];
    protected $nJobListingsPerPage = 20;
    protected $flagSettings = C__JOB_SEARCH_RESULTS_TYPE_WEBPAGE__;
    protected $childSiteURLBase = '';
    protected $childSiteListingPage = '';

    function __construct($strBaseDir = null)
    {
        $this->siteBaseURL = $this->childSiteURLBase;
        $this->strBaseURLFormat = $this->childSiteURLBase;
        parent::__construct($strBaseDir);
    }


    protected $arrListingTagSetup = array(
        'tag_listings_section' => null,
        'tag_title' => null,
        'tag_link' => null,
        'tag_department' => null,
        'tag_location' => null,
        'tag_company' => null,
        'tag_job_posting_date' => null,
        'regex_link_job_id' => '/.com\/apply\/(\S*)\//i',
    );

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
        assert($this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__));

        return C__TOTAL_ITEMS_UNKNOWN__;
    }

    private function _getTagMatchString_($arrTags)
    {
        if($arrTags == null) return null;

        if(isset($arrTags['tag']))
        {
            $arrTags = array($arrTags);
        }
        $strMatch = "";

        foreach($arrTags as $arrTag)
        {
            if(strlen($strMatch) > 0) $strMatch = $strMatch . ' ';
            $strMatch = $strMatch . $arrTag['tag'];
            if(isset($arrTag['attribute']) && strlen($arrTag['attribute']) > 0)
            {
                $strMatch = $strMatch .'[' . $arrTag['attribute'] . '="' . $arrTag['attribute_value'] . '"]';
            }
        }

        return $strMatch;
    }

    private function _getTagMatchValue_($node, $arrTag, $nameProperty = 'plaintext')
    {
        $strReturn = '';

        $strMatch = $this->_getTagMatchString_($arrTag);
        if(isset($strMatch))
        {
//            $GLOBALS['logger']->logLine(" Looking for nodes matching: " . $strMatch, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            $retNode = $node->find($strMatch);
            if(isset($retNode) && isset($retNode[0]))
            {
                $strReturn = $retNode[0]->$nameProperty;
                if(isset($arrTag['index']) )
                    if(isset($retNode[$arrTag['index']]))
                        $strReturn = $retNode[$arrTag['index']]->$nameProperty;
            }
        }

        return $strReturn;
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
        $item = null;

        // first looked for the detail view layout and parse that
        $strNodeMatch = $this->_getTagMatchString_($this->arrListingTagSetup['tag_listings_section']);

        $GLOBALS['logger']->logLine($this->siteName . " finding nodes matching: " . $strNodeMatch, \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $nodesJobRows = $objSimpHTML->find($strNodeMatch);

        if(isset($nodesJobRows) && $nodesJobRows != null && count($nodesJobRows) > 0 )
        {
            foreach($nodesJobRows as $node)
            {
                //
                // get a new record with all columns set to null
                //
                $item = $this->getEmptyJobListingRecord();

                $item['job_site'] = $this->siteName;
                $item['date_pulled'] = getTodayAsString();
                $item['job_title'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_title'], 'plaintext');
                $item['job_post_url'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_link'], 'href');


                if(array_key_exists('tag_company', $this->arrListingTagSetup))
                {
                    $item['company'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_company'], 'plaintext');
                }
                else
                {
                    $item['company'] = $item['job_site'];
                }

                if(array_key_exists('tag_department', $this->arrListingTagSetup))
                    $item['job_site_category'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_department'], 'plaintext');

                if(array_key_exists('tag_location', $this->arrListingTagSetup))
                    $item['location'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_location'], 'plaintext');



                $fMatchedID = preg_match($this->arrListingTagSetup['regex_link_job_id'], $item['job_post_url'], $idMatches);
                if($fMatchedID && count($idMatches) > 1)
                {
                    $item['job_id'] = $idMatches[1];
                }

                //
                // Call normalizeItem to standardize the resulting listing result
                //
                $ret[] = $this->normalizeItem($item);

            }
        }

        return $ret;
    }

}
