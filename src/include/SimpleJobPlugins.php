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
if (!strlen(__ROOT__) > 0) {
    define('__ROOT__', dirname(dirname(__FILE__)));
}
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');


abstract class ClassClientHTMLJobSitePlugin extends ClassBaseHTMLJobSitePlugin
{
    protected $pluginResultsType = C__JOB_SEARCH_RESULTS_TYPE_CLIENTSIDE_WEBPAGE__;

    function __construct($strBaseDir = null)
    {
        $this->additionalFlags[] = C__JOB_USE_SELENIUM;
        parent::__construct($strBaseDir);

    }
}



abstract class ClassHTMLJobSitePlugin extends ClassBaseHTMLJobSitePlugin
{
    protected $pluginResultsType = C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__;
}

abstract class ClassBaseHTMLJobSitePlugin extends AbstractClassBaseJobsPlugin
{
    protected $siteName = '';
    protected $siteBaseURL = '';
    protected $nJobListingsPerPage = 20;
    protected $childSiteURLBase = '';
    protected $childSiteListingPage = '';
    protected $additionalLoadDelaySeconds = 2;
    protected $nextPageScript = null;

    function __construct($strBaseDir = null)
    {
        if (strlen($this->siteBaseURL) == 0)
            $this->siteBaseURL = $this->childSiteURLBase;
        if (strlen($this->strBaseURLFormat) == 0)
            $this->strBaseURLFormat = $this->childSiteURLBase;

        parent::__construct($strBaseDir);

    }


    protected $arrListingTagSetup = array(
        'tag_listings_noresults' => null,
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
        'tag_next_button' => null,
        'regex_link_job_id' => null,
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
        if (array_key_exists('tag_listings_noresults', $this->arrListingTagSetup) && !is_null($this->arrListingTagSetup['tag_listings_noresults'])) {
            try
            {
                $noResultsVal = $this->_getTagMatchValue_($objSimpHTML, $this->arrListingTagSetup['tag_listings_noresults'], $propertyName = 'plaintext');
                if (!is_null($noResultsVal)) {
                    $GLOBALS['logger']->logLine("Search returned " . $noResultsVal . " and matched expected 'No results' tag for " . $this->siteName, \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    return $noResultsVal;
                }
            } catch (Exception $ex) {
                $GLOBALS['logger']->logLine("Warning: Did not find matched expected 'No results' tag for " . $this->siteName . ".  %s", \Scooper\C__DISPLAY_WARNING__);
            }
        }

        $retJobCount = C__TOTAL_ITEMS_UNKNOWN__;
        if (array_key_exists('tag_listings_count', $this->arrListingTagSetup) && !is_null($this->arrListingTagSetup['tag_listings_count'])) {
            $retJobCount = $this->_getTagMatchValue_($objSimpHTML, $this->arrListingTagSetup['tag_listings_count'], $propertyName = 'plaintext');
            if (is_null($retJobCount) || (is_string($retJobCount) && strlen($retJobCount) == 0))
                throw new Exception("Unable to determine number of listings for the defined tag:  " . getArrayValuesAsString($this->arrListingTagSetup['tag_listings_count']));
        } else if (array_key_exists('tag_pages_count', $this->arrListingTagSetup) && !is_null($this->arrListingTagSetup['tag_pages_count'])) {
            $retPageCount = $this->_getTagMatchValue_($objSimpHTML, $this->arrListingTagSetup['tag_pages_count'], $propertyName = 'plaintext');
            if (is_null($retJobCount) || (is_string($retJobCount) && strlen($retJobCount) == 0))
                throw new Exception("Unable to determine number of listings for the defined tag:  " . getArrayValuesAsString($this->arrListingTagSetup['tag_pages_count']));

            $retJobCount = $retPageCount * $this->nJobListingsPerPage;
        } elseif ($this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__))
            $retJobCount = C__TOTAL_ITEMS_UNKNOWN__;
        else
            throw new Exception("Error: plugin is missing either C__JOB_PAGECOUNT_NOTAPPLICABLE__ flag or an implementation of parseTotalResultsCount for that job site. Cannot complete search.");

        return $retJobCount;

    }

    protected function getTagSelector($arrTags)
    {
        if ($arrTags == null) return null;

        $arrKeys = array_keys($arrTags);
        if ($arrKeys[0] != "0") {
            $arrTags = array($arrTags);
        }
        $strMatch = "";

        foreach ($arrTags as $arrTag) {
            if (!is_array($arrTag))
                continue;
            if (array_key_exists("selector", $arrTag)) {
                $strMatch = $strMatch . $arrTag['selector'];
            } elseif(array_key_exists("tag", $arrTag)) {
               if (strlen($strMatch) > 0) $strMatch = $strMatch . ' ';
                {
                    $strMatch = $strMatch . $arrTag['tag'];
                    if (array_key_exists('attribute', $arrTag) && strlen($arrTag['attribute']) > 0) {
                        $strMatch = $strMatch . '[' . $arrTag['attribute'];
                        if (array_key_exists('attribute_value', $arrTag) && strlen($arrTag['attribute_value']) > 0) {
                            $strMatch = $strMatch . '="' . $arrTag['attribute_value'] . '"';
                        }
                        $strMatch = $strMatch . ']';
                    }
                }
            }
        }

        return $strMatch;
    }

    protected function _getTagMatchValue_($node, $arrTag, $returnAttribute = 'plaintext', $propertyRegEx = null)
    {
        $ret = null;
        $fReturnNodeObject = false;

        if (array_key_exists("return_attribute", $arrTag)) {
            $returnAttribute = $arrTag['return_attribute'];
        }
        if($returnAttribute == 'collection' || $returnAttribute == 'node')
        {
            $returnAttribute = null;
            $fReturnNodeObject = true;
        }

        if (array_key_exists("return_value_regex", $arrTag)) {
            $propertyRegEx = $arrTag['return_value_regex'];
        }

        $strMatch = $this->getTagSelector($arrTag);
        if (!isset($strMatch)) {
            return $ret;
        }

        $nodeMatches = $node->find($strMatch);
        if (isset($nodeMatches) && !is_null($nodeMatches) && count($nodeMatches) >=1) {
            $ret = $nodeMatches;
        }

        if ($fReturnNodeObject === true) {
            // do nothing.  We already have the ndoe set correctly
        } elseif (!is_null($ret) && isset($arrTag['index']) && is_array($ret) && intval($arrTag['index']) < count($ret)) {
            $index = $arrTag['index'];
            if (count($nodeMatches) <= $index) {
                $strError = sprintf("%s plugin failed to find index #%d in the %d nodes matching '%s'. ", $this->siteName, $index, count($nodeMatches), $strMatch);
                $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                throw new Exception($strError);
            }
            $ret = $nodeMatches[$index];
        } elseif (!is_null($ret) && is_array($ret)) {
            if (count($ret) > 1) {
                $strError = sprintf("Warning:  %s plugin matched %d nodes to selector '%s' but did not specify an index.  Assuming first node.", $this->siteName, count($ret), $strMatch);
                $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_WARNING__);
//                    throw new Exception($strError);
            }
            $ret = $ret[0];
        }



        if ($fReturnNodeObject === false && !is_null($ret)) {
            assert(!is_array($ret));
            $ret = $ret->$returnAttribute;

            if (!is_null($propertyRegEx) && is_string($ret) && strlen($ret) > 0) {
                $match = array();
                if (preg_match($propertyRegEx, $ret, $match) !== false && count($match) > 1)
                    $ret = $match[1];
                else {
                    $strError = sprintf("%s plugin failed to find match for regex '%s' for tag '%s' with value '%s' as expected.", $this->siteName, $propertyRegEx, getArrayValuesAsString($arrTag), $ret);
                    $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                    throw new Exception($strError);
                }
            }
        }

        if (array_key_exists("return_value_callback", $arrTag) && strlen($arrTag['return_value_callback']) > 0) {
            if (!is_callable($arrTag['return_value_callback'])) {
                $strError = sprintf("%s plugin failed could not call the tag callback method '%s' for attribute name '%s'.", $this->siteName, $arrTag['return_value_callback'], $returnAttribute);
                $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                throw new Exception($strError);
            }
            $ret = call_user_func($arrTag['return_value_callback'], $ret);
        }


        return $ret;
    }

    /**
     * /**
     * parseJobsListForPage
     *
     * This does the heavy lifting of parsing each job record from the
     * page's HTML it was passed.
     * *
     * @param \voku\helper\SimpleHtmlDom $objSimpHTML
     * @return array|null
     */
    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;
        $item = null;

        // first looked for the detail view layout and parse that
        $strNodeMatch = $this->getTagSelector($this->arrListingTagSetup['tag_listings_section']);

        $GLOBALS['logger']->logLine($this->siteName . " finding nodes matching: " . $strNodeMatch, \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $nodesJobRows = $this->_getTagMatchValue_($objSimpHTML, $this->arrListingTagSetup['tag_listings_section'], 'collection');

        if (isset($nodesJobRows) && $nodesJobRows != null && count($nodesJobRows) > 0) {
            foreach ($nodesJobRows as $node) {
                //
                // get a new record with all columns set to null
                //
                $item = $this->getEmptyJobListingRecord();

                $item['job_title'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_title'], 'plaintext');
                $item['job_post_url'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_link'], 'href');

                if (strlen($item['job_title']) == 0)
                    continue;

                if (array_key_exists('tag_company', $this->arrListingTagSetup)) {
                    $item['company'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_company'], 'plaintext');
                }

                if (array_key_exists('tag_job_id', $this->arrListingTagSetup))
                    $item['job_id'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_job_id'], 'plaintext');

                if (array_key_exists('tag_department', $this->arrListingTagSetup))
                    $item['job_site_category'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_department'], 'plaintext');

                if (array_key_exists('tag_location', $this->arrListingTagSetup))
                    $item['location'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_location'], 'plaintext');

                if (array_key_exists('tag_job_posting_date', $this->arrListingTagSetup))
                    $item['job_site_date'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_job_posting_date'], 'plaintext');

                if (array_key_exists('tag_employment_type', $this->arrListingTagSetup))
                    $item['employment_type'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_employment_type'], 'plaintext');

                if (array_key_exists('regex_link_job_id', $this->arrListingTagSetup))
                    $this->regex_link_job_id = $this->arrListingTagSetup['regex_link_job_id'];

                $ret[] = $this->normalizeJobItem($item);

            }
        }

        return $ret;
    }

    function takeNextPageAction($driver)
    {
        if (array_key_exists('tag_next_button', $this->arrListingTagSetup)) {
            if (!is_null($this->arrListingTagSetup['tag_next_button'])) {
                $strMatch = $this->getTagSelector($this->arrListingTagSetup['tag_next_button']);
                if (isset($strMatch)) {
                    try {
                        $GLOBALS['logger']->logLine("Going to next page of results via CSS object " . $strMatch, \Scooper\C__DISPLAY_NORMAL__);
                        $arrArgs = array();
                        $ret = $driver->executeScript(sprintf("function callNextPage() { var elem = window.document.querySelector('" . $strMatch . "');  if (elem != null) { console.log('attempting next button click on element " . $strMatch . "'); elem.click(); return true; } else return false; } ; return callNextPage();", $arrArgs));
                        if ($ret === false) {
                            $ex = new Exception("Failed to find and click the control to go to the next page of results for " . $this->siteName);
                            handleException($ex, $fmtLogMsg = null, $raise = true);
                        } else
                            sleep($this->additionalLoadDelaySeconds);
                        $GLOBALS['logger']->logLine("Next page of job listings loaded successfully.  ", \Scooper\C__DISPLAY_NORMAL__);

                    } catch (Exception $ex) {
                        $strError = "Error clicking next: " . $ex->getMessage();
                        handleException(new Exception($strError), $fmtLogMsg = null, $raise = true);
                    }
                }
            } elseif (!is_null($this->nextPageScript)) {
                $script = "function callNextPage() { " . $this->nextPageScript . " } ; callNextPage();";
                $GLOBALS['logger']->logLine("Going to next page of results via script: " . $script, \Scooper\C__DISPLAY_NORMAL__);
                $driver->executeScript($script);
                sleep($this->additionalLoadDelaySeconds);
            }
        }
        else
        {
            throw new Exception(sprintf("Error: plugin for %s is missing tag definition for the next page button to click. Cannot complete search.", $this->siteName));
        }
    }

    protected function getNextInfiniteScrollSet($driver)
    {
        if (array_key_exists('tag_load_more', $this->arrListingTagSetup) && !is_null($this->arrListingTagSetup['tag_load_more'])) {
            $strMatch = $this->getTagSelector($this->arrListingTagSetup['tag_load_more']);
            if (isset($strMatch)) {
                $GLOBALS['logger']->logLine("Loading more results via CSS object " . $strMatch, \Scooper\C__DISPLAY_NORMAL__);
                $arrArgs = array();
                $ret = $driver->executeScript(sprintf("function callLoadMore() { var elem = document.querySelector('" . $strMatch . "');  if (elem != null) { console.log('Attempting more button click on element " . $strMatch . "'); elem.click(); return true; } else { return false; }; } ; return callLoadMore();", $arrArgs));
                if ($ret === false)
                    $GLOBALS['logger']->logLine("Failed to find and click the control to load the next set of results...", \Scooper\C__DISPLAY_ERROR__);
                else
                    sleep($this->additionalLoadDelaySeconds);
                $GLOBALS['logger']->logLine("Next page of job listings loaded successfully.  ", \Scooper\C__DISPLAY_NORMAL__);
                sleep($this->additionalLoadDelaySeconds);
                return;
            }
        }
        throw new Exception(sprintf("Error: plugin for %s is missing tag definition for the infinite scroll button to click. Cannot complete search.", $this->siteName));

    }

}
