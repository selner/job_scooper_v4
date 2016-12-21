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
    protected $flagSettings = [C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__, C__JOB_PAGECOUNT_NOTAPPLICABLE__, C__JOB_ITEMCOUNT_NOTAPPLICABLE__];

    function getSearchJobsFromAPI($searchDetails) { return VALUE_NOT_SUPPORTED; }

}


abstract class ClassSimpleFullPageJobSitePlugin extends ClassBaseSimpleJobSitePlugin
{
    protected $childSiteURLBase = '';
    protected $childSiteListingPage = '';
    protected $flagSettings = [C__JOB_BASETYPE_WEBPAGE_FLAGS, C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_PAGECOUNT_NOTAPPLICABLE__, C__JOB_ITEMCOUNT_NOTAPPLICABLE__];

    protected function _getURLfromBase_($searchDetails, $nPage = null, $nItem = null)
    {
        return $this->childSiteListingPage;
    }
}


abstract class ClassBaseMicroDataPlugin extends ClassBaseSimpleJobSitePlugin
{
    protected $siteBaseURL = '';
    protected $siteName = '';
    protected $flagSettings = [C__JOB_BASETYPE_WEBPAGE_FLAGS, C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_PREFER_MICRODATA];

}


abstract class ClassBaseSimpleJobSitePlugin extends ClassJobsSitePlugin
{
    protected $siteName = '';
    protected $siteBaseURL = '';
    protected $additionalFlags = [];
    protected $nJobListingsPerPage = 20;
    protected $flagSettings = C__JOB_SEARCH_RESULTS_TYPE_WEBPAGE__;
    protected $childSiteURLBase = '';
    protected $childSiteListingPage = '';
    protected $additionalLoadDelaySeconds = 2;

    function __construct($strBaseDir = null)
    {
        if(strlen($this->siteBaseURL) == 0)
            $this->siteBaseURL = $this->childSiteURLBase;
        if(strlen($this->strBaseURLFormat) == 0)
            $this->strBaseURLFormat = $this->childSiteURLBase;

        parent::__construct($strBaseDir);

    }


    protected $arrListingTagSetup = array(
        'tag_listings_count' => null,
        'tag_pages_count' => null,
        'tag_listings_section' => null,
        'tag_job_id' => null,
        'tag_title' => null,
        'tag_link' => null,
        'tag_department' => null,
        'tag_location' => null,
        'tag_company' => null,
        'tag_job_posting_date' => null,
        'tag_employment_type' => null,
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
        $retJobCount = C__TOTAL_ITEMS_UNKNOWN__;
        if($this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__))
            $retJobCount = C__TOTAL_ITEMS_UNKNOWN__;
        else if(array_key_exists('tag_listings_count', $this->arrListingTagSetup) && !is_null($this->arrListingTagSetup['tag_listings_count']))
        {
            $retJobCount = $this->_getTagMatchValue_($objSimpHTML, $this->arrListingTagSetup['tag_listings_count'], $propertyName='plaintext');
            if(is_null($retJobCount) || (is_string($retJobCount) && strlen($retJobCount) == 0))
                throw new Exception("Unable to determine number of listings for the defined tag:  " . getArrayValuesAsString($this->arrListingTagSetup['tag_listings_count']));
        }
        else if(array_key_exists('tag_pages_count', $this->arrListingTagSetup) && !is_null($this->arrListingTagSetup['tag_pages_count']))
        {
            $retPageCount = $this->_getTagMatchValue_($objSimpHTML, $this->arrListingTagSetup['tag_pages_count'], $propertyName='plaintext');
            if(is_null($retJobCount) || (is_string($retJobCount) && strlen($retJobCount) == 0))
                throw new Exception("Unable to determine number of listings for the defined tag:  " . getArrayValuesAsString($this->arrListingTagSetup['tag_pages_count']));

            $retJobCount = $retPageCount * $this->nJobListingsPerPage;
        }

    else
            throw new Exception("Error: plugin is missing either C__JOB_PAGECOUNT_NOTAPPLICABLE__ flag or an implementation of parseTotalResultsCount for that job site. Cannot complete search.");

        return $retJobCount;

    }

    protected function getTagSelector($arrTag)
    {
        if(array_key_exists("selector", $arrTag))
        {
            $strMatch = $arrTag['selector'];
        }
        else
        {
            $strMatch = $this->_getTagMatchString_($arrTag);
        }
        return $strMatch;
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
            if (!is_array($arrTag))
                continue;

            if(strlen($strMatch) > 0) $strMatch = $strMatch . ' ';
            $strMatch = $strMatch . $arrTag['tag'];
            if(isset($arrTag['attribute']) && strlen($arrTag['attribute']) > 0)
            {
                $strMatch = $strMatch .'[' . $arrTag['attribute'] . '="' . $arrTag['attribute_value'] . '"]';
            }
        }

        return $strMatch;
    }

    private function _getTagMatchValue_($node, $arrTag, $propertyName = 'plaintext', $propertyRegEx = null)
    {
        $strReturn = '';
        if(array_key_exists("return_attribute", $arrTag))
        {
            $propertyName = $arrTag['return_attribute'];
        }

        if(array_key_exists("return_value_regex", $arrTag))
        {
            $propertyRegEx = $arrTag['return_value_regex'];
        }

        $strMatch = $this->getTagSelector($arrTag);
        if(isset($strMatch))
        {
            $retNode = $node->find($strMatch);
            if(isset($retNode) && isset($retNode[0]))
            {
                $strReturn = $retNode[0]->$propertyName;
                if(isset($arrTag['index']) )
                    if(isset($retNode[$arrTag['index']]))
                        $strReturn = $retNode[$arrTag['index']]->$propertyName;

                if(!is_null($propertyRegEx) && strlen($strReturn) > 0)
                {
                    $match = array();
                    if(preg_match($propertyRegEx, $strReturn, $match) !== false && count($match) > 1)
                        $strReturn = $match[1];
                    else
                    {
                        $strError = sprintf("%s plugin failed to find match for regex '%s' for attribute name '%s' with value '%s' as expected." , $this->siteName, $propertyRegEx , $propertyName, $strReturn);
                        $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                        throw new Exception($strError);
                    }
                }
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

                if(strlen($item['job_title']) == 0)
                    continue;

                if(array_key_exists('tag_company', $this->arrListingTagSetup))
                {
                    $item['company'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_company'], 'plaintext');
                }
                else
                {
                    $item['company'] = $item['job_site'];
                }

                if(array_key_exists('tag_job_id', $this->arrListingTagSetup))
                    $item['job_id'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_job_id'], 'plaintext');

                if(array_key_exists('tag_department', $this->arrListingTagSetup))
                    $item['job_site_category'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_department'], 'plaintext');

                if(array_key_exists('tag_location', $this->arrListingTagSetup))
                    $item['location'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_location'], 'plaintext');

                if(array_key_exists('tag_job_posting_date', $this->arrListingTagSetup))
                    $item['job_site_date'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_job_posting_date'], 'plaintext');

                if(array_key_exists('tag_employment_type', $this->arrListingTagSetup))
                    $item['employment_type'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_employment_type'], 'plaintext');

                if(array_key_exists('regex_link_job_id', $this->arrListingTagSetup))
                    $this->regex_link_job_id = $this->arrListingTagSetup['regex_link_job_id'];

                //
                // Call normalizeItem to standardize the resulting listing result
                //
                $ret[] = $this->normalizeItem($item);

            }
        }

        return $ret;
    }

    function getNextPage($driver, $nextPageNum)
    {
        if(array_key_exists('tag_next_button', $this->arrListingTagSetup) && !is_null($this->arrListingTagSetup['tag_next_button']))
        {
            $strMatch = $this->getTagSelector($this->arrListingTagSetup['tag_next_button']);
            if(isset($strMatch))
            {
                $driver->executeScript(sprintf("function callNextPage() { var elem = document.querySelector(\"%s\");  if (elem != null) { console.log('attempting next button click on element a.next'); elem.click(); }; } ; callNextPage();", $strMatch));
                sleep($this->additionalLoadDelaySeconds);
                return $driver;
            }
        }
        throw new Exception(sprintf("Error: plugin for %s is missing tag definition for the next page button to click. Cannot complete search.", $this->siteName));
    }

    protected function getNextInfiniteScrollSet($driver)
    {
        if(array_key_exists('tag_load_more', $this->arrListingTagSetup) && !is_null($this->arrListingTagSetup['tag_load_more']))
        {
            $strMatch = $this->getTagSelector($this->arrListingTagSetup['tag_load_more']);
            if(isset($strMatch))
            {
                $driver->executeScript(sprintf("function callLoadMore() { var elem = document.querySelector('%s');  if (elem != null) { console.log('Attempting more button click on element %s'); elem.click(); return true; } else { return false; }; } ; callLoadMore();", $strMatch, $strMatch));
                sleep($this->additionalLoadDelaySeconds);
                return $driver;
            }
        }
        throw new Exception(sprintf("Error: plugin for %s is missing tag definition for the infinite scroll button to click. Cannot complete search.", $this->siteName));

    }

}
