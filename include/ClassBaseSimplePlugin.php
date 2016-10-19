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
require_once(__ROOT__ . '/include/ClassJobsSitePluginCommon.php');

abstract class ClassBaseSimplePlugin extends ClassJobsSitePlugin
{
    protected $siteName = '';
    protected $siteBaseURL = '';
    protected $childSiteURLBase = '';
    protected $childSiteListingPage = '';
    protected $additionalFlags = null;
    protected $nJobListingsPerPage = 1000;
    protected $flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_RETURN_ALL_JOBS_ON_SINGLE_PAGE_NO_LOCATION;

    protected $arrListingTagSetup = array(
        'tag_listings_section' => null,
        'tag_title' => null,
        'tag_link' => null,
        'tag_department' => null,
        'tag_location' => null,
        'regex_link_job_id' => '/.com\/apply\/(\S*)\//i',
    );
    function __construct($strOutputDirectory = null)
    {
        $this->siteBaseURL = $this->childSiteURLBase;
        $this->strBaseURLFormat = $this->childSiteURLBase;
        if($this->additionalFlags)
        {
            foreach($this->additionalFlags as $flag)
            {
                $this->flagSettings = $this->flagSettings | $flag;
            }
        }
        return parent::__construct($strOutputDirectory);
    }

    protected function _getURLfromBase_($searchDetails, $nPage = null, $nItem = null)
    {
        return $this->childSiteListingPage;
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
        return -1;
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
                $item['date_pulled'] = \Scooper\getTodayAsString();
                $item['job_title'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_title'], 'plaintext');
                $item['job_post_url'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_link'], 'href');


                $item['company'] = $item['job_site'];
                $item['location'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_location'], 'plaintext');
                $item['job_site_category'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_department'], 'plaintext');


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

abstract class ClassBaseMicroDataPlugin extends ClassBaseSimplePlugin
{
    protected $siteBaseURL = '';
    protected $siteName = '';

    function __construct($strBaseDir = null)
    {
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_RETURN_ALL_JOBS_ON_SINGLE_PAGE_NO_LOCATION  | C__JOB_PREFER_MICRODATA;
        parent::__construct($strBaseDir);
    }

}