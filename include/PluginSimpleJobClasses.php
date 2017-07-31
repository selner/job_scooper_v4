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
require_once dirname(dirname(__FILE__))."/bootstrap.php";

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
    protected $arrListingTagSetup = array();

    function __construct($strBaseDir = null)
    {
        if (is_null($this->arrListingTagSetup))
            $this->arrListingTagSetup = ClassBaseHTMLJobSitePlugin::getEmptyListingTagSetup();

        if (strlen($this->siteBaseURL) == 0)
            $this->siteBaseURL = $this->childSiteURLBase;
        if (strlen($this->strBaseURLFormat) == 0)
            $this->strBaseURLFormat = $this->childSiteURLBase;


        if (array_key_exists('tag_next_button', $this->arrListingTagSetup) && is_array($this->arrListingTagSetup['tag_next_button']) && count($this->arrListingTagSetup['tag_next_button'])) {
            $this->selectorMoreListings = $this->getTagSelector($this->arrListingTagSetup['tag_next_button']);
            $this->paginationType = C__PAGINATION_PAGE_VIA_NEXTBUTTON;
        } elseif (array_key_exists('tag_load_more', $this->arrListingTagSetup) && is_array($this->arrListingTagSetup['tag_load_more']) && count($this->arrListingTagSetup['tag_load_more'])) {
            $this->paginationType = C__PAGINATION_INFSCROLLPAGE_VIALOADMORE;
            $this->selectorMoreListings = $this->getTagSelector($this->arrListingTagSetup['tag_load_more']);
        }

        if (!array_key_exists('tag_listings_count', $this->arrListingTagSetup) &&  !in_array(C__JOB_ITEMCOUNT_NOTAPPLICABLE__, $this->additionalFlags))
        {
            $this->additionalFlags[]  = C__JOB_ITEMCOUNT_NOTAPPLICABLE__;
        }

        if (!array_key_exists('tag_pages_count', $this->arrListingTagSetup) &&  !in_array(C__JOB_PAGECOUNT_NOTAPPLICABLE__, $this->additionalFlags))
        {
            $this->additionalFlags[]  = C__JOB_PAGECOUNT_NOTAPPLICABLE__;
        }

        parent::__construct($strBaseDir);
    }

    static function getEmptyListingTagSetup()
    {
        $arrListingTagSetup = array(
            'tag_pages_count' => array(),
            'tag_listings_noresults' => array(),
            'tag_listings_count' => array(),
            'tag_listings_section' => array(),
            'tag_next_button' => array(),
            'tag_job_id' => array(),
            'tag_title' => array(),
            'tag_link' => array(),
            'tag_department' => array(),
            'tag_location' => array(),
            'tag_job_category' => array(),
            'tag_company' => array(),
            'tag_company_logo' => array(),
            'tag_job_posting_date' => array(),
            'tag_employment_type' => array(),
            'regex_link_job_id' => array(),
        );
        return $arrListingTagSetup;
    }

    function matchesNoResultsPattern($var)
    {
        $val = $var[0];
        $match_value = $var[1];

        if(is_null($match_value))
            throw new Exception("Plugin " . $this->siteName  . " definition missing pattern match value for isNoJobResults callback.");
        return noJobStringMatch($val, $match_value);
    }

    /**
     * parseTotalResultsCount
     *
     * If the site does not show the total number of results
     * then set the plugin flag to C__JOB_PAGECOUNT_NOTAPPLICABLE__
     * in the PluginOptions.php file and just comment out this function.
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
                $GLOBALS['logger']->logLine("Warning: Did not find matched expected 'No results' tag for " . $this->siteName . ".  Error:" . $ex->getMessage(), \Scooper\C__DISPLAY_WARNING__);
            }
        }

        $retJobCount = C__TOTAL_ITEMS_UNKNOWN__;
        if (array_key_exists('tag_listings_count', $this->arrListingTagSetup) && is_array($this->arrListingTagSetup['tag_listings_count']) && count($this->arrListingTagSetup['tag_listings_count']) > 0) {
            $retJobCount = $this->_getTagMatchValue_($objSimpHTML, $this->arrListingTagSetup['tag_listings_count'], $propertyName = 'plaintext');
            if (is_null($retJobCount) || (is_string($retJobCount) && strlen($retJobCount) == 0))
                throw new Exception("Unable to determine number of listings for the defined tag:  " . getArrayValuesAsString($this->arrListingTagSetup['tag_listings_count']));
        } else if (array_key_exists('tag_pages_count', $this->arrListingTagSetup) && is_array($this->arrListingTagSetup['tag_pages_count']) && count($this->arrListingTagSetup['tag_pages_count']) > 0) {
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

    protected function _getTagMatchValue_($node, $arrTag, $returnAttribute = 'plaintext', $item = null)
    {
        if (array_key_exists("type", $arrTag) && !is_null($arrTag['type'])) {
            switch(strtoupper($arrTag['type']))
            {
                case 'CSS':
                    return $this->_getTagMatchValueCSS_($node, $arrTag, $returnAttribute);
                    break;

                case 'REGEX':
                    return $this->_getTagMatchValueRegex_($node, $arrTag, $returnAttribute, $item);
                    break;

                default:
                    throw new Exception("Unknown field definition type of " . $arrTag['type']);
            }
        }
        else
        {
            return $this->_getTagMatchValueCSS_($node, $arrTag, $returnAttribute);

        }

    }

    protected function _getTagMatchValueRegex_($node, $arrTag, $returnAttribute, $item)
    {
        $ret = null;
        if (array_key_exists("pattern", $arrTag) && !is_null($arrTag['pattern'])) {
            $pattern = $arrTag['pattern'];
            $value = "";
            if (array_key_exists("selector", $arrTag) && !is_null($arrTag['selector'])) {
                $value = $this->_getTagMatchValueCSS_($node, $arrTag, $returnAttribute);
            }
            elseif (array_key_exists("field", $arrTag) && !is_null($arrTag['field'])) {
                if (in_array($arrTag['field'], array_keys($item))) {
                    $value = $item[$arrTag['field']];
                }
            }

            if(is_null($value) || strlen($value) == 0)
                $ret = null;
            elseif (preg_match($pattern, $value, $matches) > 0) {
                switch($arrTag['index'])
                {
                    case null:
                        $ret = $matches[1];
                        break;

                    case "LAST":
                        $ret = $matches[count($matches) - 1];
                        break;

                    default:
                        $ret = $matches[$arrTag['index']];
                        break;
                }
            }
        }

        return $ret;
    }

        protected function _getTagMatchValueCSS_($node, $arrTag, $returnAttribute = 'plaintext')
    {
        $ret = null;
        $fReturnNodeObject = false;
        $propertyRegEx = null;

        if (array_key_exists("return_attribute", $arrTag) && !is_null($arrTag['return_attribute'])) {
            $returnAttribute = $arrTag['return_attribute'];
        }
        if(strtolower($returnAttribute) == 'collection' || strtolower($returnAttribute) == 'node')
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
            }
            $ret = $ret[0];
        }



        if ($fReturnNodeObject === false && !is_null($ret)) {
            $ret = $ret->$returnAttribute;

            if (!is_null($propertyRegEx) && is_string($ret) && strlen($ret) > 0) {
                $match = array();
                if (preg_match($propertyRegEx, $ret, $match) !== false && count($match) >= 1)
                    $ret = $match[1];
                else {
                    handleException(new Exception(sprintf("%s plugin failed to find match for regex '%s' for tag '%s' with value '%s' as expected.", $this->siteName, $propertyRegEx, getArrayValuesAsString($arrTag), $ret)), "", true);
                }
            }
        }

        if (!is_null($ret) && array_key_exists("return_value_callback", $arrTag) && (strlen($arrTag['return_value_callback']) > 0)) {
            $callback = get_class($this) . "::" . $arrTag['return_value_callback'];
            if (!method_exists($this, $arrTag['return_value_callback'])) {
                $strError = sprintf("%s plugin failed could not call the tag callback method '%s' for attribute name '%s'.", $this->siteName, $callback, $returnAttribute);
                $GLOBALS['logger']->logLine($strError, \Scooper\C__DISPLAY_ERROR__);
                throw new Exception($strError);
            }

            if (array_key_exists("callback_parameter", $arrTag) && (strlen($arrTag['callback_parameter']) > 0))
                $ret = call_user_func($callback, array($ret, $arrTag['callback_parameter']));
            else
                $ret = call_user_func($callback, $ret);
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
        $tagSetup = $this->arrListingTagSetup;

        // first looked for the detail view layout and parse that
        $strNodeMatch = $this->getTagSelector($tagSetup['tag_listings_section']);

        $GLOBALS['logger']->logLine($this->siteName . " finding nodes matching: " . $strNodeMatch, \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $nodesJobRows = $this->_getTagMatchValue_($objSimpHTML, $tagSetup['tag_listings_section'], 'collection');

        if ($nodesJobRows === false || (isset($nodesJobRows) && $nodesJobRows != null && count($nodesJobRows) > 0)) {
            foreach ($nodesJobRows as $node) {
                //
                // get a new record with all columns set to null
                //
                $item = $this->getEmptyJobListingRecord();

                $item['job_title'] = $this->_getTagMatchValue_($node, $tagSetup['tag_title'], 'plaintext');
                $item['job_post_url'] = $this->_getTagMatchValue_($node, $tagSetup['tag_link'], 'href');

                if (strlen($item['job_title']) == 0)
                    continue;

                if (array_key_exists('tag_company', $tagSetup) && count($tagSetup['tag_company']) >= 1)
                    $item['company'] = $this->_getTagMatchValue_($node, $tagSetup['tag_company'], 'plaintext', $item);

                if (array_key_exists('tag_department', $tagSetup) && count($tagSetup['tag_department']) >= 1)
                    $item['job_site_category'] = $this->_getTagMatchValue_($node, $tagSetup['tag_department'], 'plaintext', $item);

                if (array_key_exists('tag_location', $tagSetup) && count($tagSetup['tag_location']) >= 1)
                    $item['location'] = $this->_getTagMatchValue_($node, $tagSetup['tag_location'], 'plaintext', $item);

                if (array_key_exists('tag_job_posting_date', $tagSetup) && count($tagSetup['tag_job_posting_date']) >= 1)
                    $item['job_site_date'] = $this->_getTagMatchValue_($node, $tagSetup['tag_job_posting_date'], 'plaintext', $item);

                if (array_key_exists('tag_employment_type', $tagSetup) && count($tagSetup['tag_employment_type']) >= 1)
                    $item['employment_type'] = $this->_getTagMatchValue_($node, $tagSetup['tag_employment_type'], 'plaintext', $item);

                if (array_key_exists('tag_job_posting_date', $this->arrListingTagSetup))
                    $item['job_site_date'] = $this->_getTagMatchValue_($node, $this->arrListingTagSetup['tag_job_posting_date'], 'plaintext');

                if (array_key_exists('regex_link_job_id', $tagSetup) && count($tagSetup['regex_link_job_id']) >= 1)
                    $this->regex_link_job_id = $tagSetup['regex_link_job_id'];

                if (array_key_exists('tag_job_id', $tagSetup) && count($tagSetup['tag_job_id']) >= 1)
                    $item['job_id'] = $this->_getTagMatchValue_($node, $tagSetup['tag_job_id'], 'plaintext', $item);

                $ret[] = $item;

            }
        }
        else
        {
            $this->_writeDebugFiles_($this->currentSearchBeingRun, 'failed-find-listings', null, $objSimpHTML->root);
            handleException(new Exception("Could not find matching job elements in HTML for " . $strNodeMatch . " in plugin " . $this->siteName), null, true);
        }

        $GLOBALS['logger']->logLine($this->siteName . " returned " . countJobRecords($ret) . " jobs from page.", \Scooper\C__DISPLAY_ITEM_DETAIL__);

        return $ret;
    }

    function takeNextPageAction($driver)
    {
        $tagSetup = $this->arrListingTagSetup;

        if (!is_null($this->nextPageScript)) {
            $script = "function callNextPage() { " . $this->nextPageScript . " } ; callNextPage();";
            $GLOBALS['logger']->logLine("Going to next page of results via script: " . $script, \Scooper\C__DISPLAY_NORMAL__);
            $driver->executeScript($script);
            sleep($this->additionalLoadDelaySeconds);
        }
        elseif (array_key_exists('tag_next_button', $tagSetup) && !is_null($tagSetup['tag_next_button']))
        {
            if (!is_null($tagSetup['tag_next_button'])) {
                $strMatch = $this->getTagSelector($tagSetup['tag_next_button']);
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
            }
        }
        else
        {
            throw new Exception(sprintf("Error: plugin for %s is missing tag definition for the next page button to click. Cannot complete search.", $this->siteName));
        }
    }

}
