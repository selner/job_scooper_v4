<?php
/**
 * Copyright 2014-18 Bryan Selner
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

namespace JobScooper\BasePlugin\Classes;

use \Exception;
use JobScooper\Utils\DomItemParser;
use JobScooper\Utils\ExtendedDiDomElement;
use JobScooper\Utils\SimpleHTMLHelper;
use Psr\Log\LogLevel;

/**
 * Class SimplePlugin
 * @package JobScooper\BasePlugin\Classes
 */
abstract class SimplePlugin extends BaseSitePlugin
{
    protected $JobSiteName = '';
    protected $JobPostingBaseUrl = '';
    protected $JobListingsPerPage = 20;
    protected $childSiteURLBase = '';
    protected $childSiteListingPage = '';
    protected $additionalLoadDelaySeconds = 2;
    protected $nextPageScript = null;


    protected $arrListingTagSetup = array();
	protected $arrBaseListingTagSetup = array();


	/**
	 * SimplePlugin constructor.
	 * @throws \Exception
	 */
	function __construct()
    {
        if (empty($this->arrListingTagSetup))
            $this->arrListingTagSetup = array();

	    if(!empty($this->arrBaseListingTagSetup))
            $this->arrListingTagSetup = array_replace($this->arrBaseListingTagSetup, $this->arrListingTagSetup);

        if (strlen($this->JobPostingBaseUrl) == 0)
            $this->JobPostingBaseUrl = $this->childSiteURLBase;
        if (strlen($this->SearchUrlFormat) == 0)
            $this->SearchUrlFormat = $this->childSiteURLBase;


        if (array_key_exists('NextButton', $this->arrListingTagSetup) && is_array($this->arrListingTagSetup['NextButton']) && count($this->arrListingTagSetup['NextButton'])) {
            $this->selectorMoreListings = DomItemParser::getSelector($this->arrListingTagSetup['NextButton']);
            $this->PaginationType = C__PAGINATION_PAGE_VIA_NEXTBUTTON;
        } elseif (array_key_exists('LoadMoreControl', $this->arrListingTagSetup) && is_array($this->arrListingTagSetup['LoadMoreControl']) && count($this->arrListingTagSetup['LoadMoreControl'])) {
            $this->PaginationType = C__PAGINATION_INFSCROLLPAGE_VIALOADMORE;
	        $this->selectorMoreListings = DomItemParser::getSelector($this->arrListingTagSetup['LoadMoreControl']);
        }

        if (!array_key_exists('TotalPostCount', $this->arrListingTagSetup) &&  !in_array(C__JOB_ITEMCOUNT_NOTAPPLICABLE__, $this->additionalBitFlags))
        {
            $this->additionalBitFlags[]  = C__JOB_ITEMCOUNT_NOTAPPLICABLE__;
        }

        if (!array_key_exists('TotalResultPageCount', $this->arrListingTagSetup) &&  !in_array(C__JOB_PAGECOUNT_NOTAPPLICABLE__, $this->additionalBitFlags))
        {
            $this->additionalBitFlags[]  = C__JOB_PAGECOUNT_NOTAPPLICABLE__;
        }

        parent::__construct();

        foreach(array_keys($this->arrListingTagSetup) as $k)
        {
            if(is_array($this->arrListingTagSetup[$k])) {
                if (array_key_exists('type', $this->arrListingTagSetup[$k])) {
                    switch ($this->arrListingTagSetup[$k]['type']) {
                        case "CSS":
                            if (array_key_exists('return_attribute', $this->arrListingTagSetup[$k]) &&
                                in_array(strtoupper($this->arrListingTagSetup[$k]['return_attribute']), array("NODE", "COLLECTION")) === true)
                                $rank = 10;
                            else
                                $rank = 50;
                            break;

                        case "STATIC":
                            $rank = 100;
                            break;


                        case "REGEX":
                            $rank = 1000;
                            break;

                        default:
                            $rank = 999999;
                            break;
                    }
                } else
                    $rank = 999999;
                $this->arrListingTagSetup[$k]['rank'] = $rank;
            }
        }

        uasort($this->arrListingTagSetup, function($a, $b) {
            if(!is_array($a) || !is_array($b))
                return 0;

            if ($a['rank'] == $b['rank']) {
                return 0;
            }
            return ($a['rank'] < $b['rank']) ? -1 : 1;

        });


    }

	/**
	 * @param $var
	 *
	 * @return int|null
	 * @throws \Exception
	 */
	function matchesNoResultsPattern($var)
    {
        $val = $var[0];
        $match_value = $var[1];

        if(is_null($match_value))
            throw new \Exception("Plugin " . $this->JobSiteName  . " definition missing pattern match value for matchesNoResultsPattern callback.");
        return noJobStringMatch($val, $match_value);
    }

    /**
     * parseTotalResultsCount
     *
     * If the site does not show the total number of results
     * then set the plugin flag to C__JOB_PAGECOUNT_NOTAPPLICABLE__
     * in the Constants.php file and just comment out this function.
     *
     * parseTotalResultsCount returns the total number of listings that
     * the search returned by parsing the value from the returned HTML
     * *
     * @param $objSimpHTML
     * @return string|null
     * @throws \Exception
     */
    function parseTotalResultsCount(SimpleHTMLHelper $objSimpHTML)
    {
        if (array_key_exists('NoPostsFound', $this->arrListingTagSetup) && !is_null($this->arrListingTagSetup['NoPostsFound']) && count($this->arrListingTagSetup['NoPostsFound']) > 0) {
            try
            {
            	$noResultsVal = DomItemParser::getTagValue($objSimpHTML, $this->arrListingTagSetup['NoPostsFound']);
                if (!is_null($noResultsVal)) {
                    $this->log("Search returned " . $noResultsVal . " and matched expected 'No results' tag for " . $this->JobSiteName);
                    return $noResultsVal;
                }
            } catch (\Exception $ex) {
	            $this->log("Warning: Did not find matched expected 'No results' tag for " . $this->JobSiteName . ".  Error:" . $ex->getMessage(), LogLevel::WARNING);
            }
        }

        $retJobCount = C__TOTAL_ITEMS_UNKNOWN__;
        if (array_key_exists('TotalPostCount', $this->arrListingTagSetup) && is_array($this->arrListingTagSetup['TotalPostCount']) && count($this->arrListingTagSetup['TotalPostCount']) > 0) {
	        $retJobCount = DomItemParser::getTagValue($objSimpHTML, $this->arrListingTagSetup['TotalPostCount']);
            if (is_null($retJobCount) || (is_string($retJobCount) && strlen($retJobCount) == 0))
                throw new \Exception("Unable to determine number of listings for the defined tag:  " . getArrayValuesAsString($this->arrListingTagSetup['TotalPostCount']));
        } else if (array_key_exists('TotalResultPageCount', $this->arrListingTagSetup) && is_array($this->arrListingTagSetup['TotalResultPageCount']) && count($this->arrListingTagSetup['TotalResultPageCount']) > 0) {
	        $retPageCount = DomItemParser::getTagValue($objSimpHTML, $this->arrListingTagSetup['TotalResultPageCount']);
            if (is_null($retJobCount) || (is_string($retJobCount) && strlen($retJobCount) == 0))
                throw new \Exception("Unable to determine number of pages for the defined tag:  " . getArrayValuesAsString($this->arrListingTagSetup['TotalResultPageCount']));

            $retJobCount = $retPageCount * $this->JobListingsPerPage;
        } elseif ($this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__))
            $retJobCount = C__TOTAL_ITEMS_UNKNOWN__;
        else
            throw new \Exception("Error: plugin is missing either C__JOB_ITEMCOUNT_NOTAPPLICABLE__ flag or an implementation of parseTotalResultsCount for that job site. Cannot complete search.");

        return $retJobCount;

    }


	/**
	 * getJobFactsFromMicrodata
	 *
	 * @param SimpleHTMLHelper $objSimpHTML
	 * @param array            $item
	 *
	 * @return array
	 * @throws \Exception
	 */
	function getJobFactsFromMicrodata($objSimpHTML, $item=array())
	{
		if(empty($objSimpHTML) || !method_exists($objSimpHTML, "find"))
			return $item;

		$itempropNodes = $objSimpHTML->find("*[itemprop]");
		if(!empty($itempropNodes) && is_array($itempropNodes)) {
			foreach ($itempropNodes as $node) {
				$attribs = $node->attributes();

				if (!empty($attribs)) {
					$itemPropKind = strtolower($attribs['itemprop']);
					$eachProp = preg_split("/\s+/", $itemPropKind);
					foreach ($eachProp as $propKind)
					{
						switch ($propKind) {
							case "itemlistelement":
								if (array_key_exists("id", $attribs))
									$item['JobSitePostId'] = $attribs['id'];
								if (array_key_exists("data-index", $attribs))
									$item['JobSitePostId'] = empty($item['JobSitePostId']) ? $attribs['data-index'] : $item['JobSitePostId'] . "-" . $attribs['data-index'];
								break;

							case "name":
							case "title":
								$item['Title'] = combineTextAllChildren($node);
								break;

							case "identifier":
								$item['Title'] = combineTextAllChildren($node);
								break;

							case "url":
								$item['Url'] = $attribs['href'];
								break;

							case "joblocation":
							case "address":
							case "postaladdress":
								$item['Location'] = combineTextAllChildren($node);
								break;

							case "employmenttype":
								$item['EmploymentType'] = combineTextAllChildren($node);
								break;

							case "dateposted":
								$item['PostedAt'] = combineTextAllChildren($node);
								break;

							case "industry":
							case "occupationalcategory":
								$item['Category'] = combineTextAllChildren($node);
								break;

							case "hiringorganization":
								$item['Company'] = combineTextAllChildren($node);
								break;


						}
					}
				}
			}
		}
		return $item;
	}


	/**
     * /**
     * parseJobsListForPage
     *
     * This does the heavy lifting of parsing each job record from the
     * page's HTML it was passed.
     *
     * @param \JobScooper\Utils\SimpleHTMLHelper $objSimpHTML
     *
     * @return array|null
     * @throws \Exception
     */
    function parseJobsListForPage(SimpleHTMLHelper $objSimpHTML)
    {

	    if(!array_key_exists('JobPostItem', $this->arrListingTagSetup))
	    {
		    throw new Exception("Plugin did not define the tags necessary to find 'JobPostItem' nodes: " . getArrayDebugOutput($this->arrListingTagSetup));
	    }

        $ret = null;
        $item = null;

        if(array_key_exists('return_attribute', $this->arrListingTagSetup['JobPostItem']) === false)
        {
            $this->arrListingTagSetup['JobPostItem']['return_attribute'] = 'collection';
        }

	    $nodesJobRows = DomItemParser::getTagValue($objSimpHTML, $this->arrListingTagSetup['JobPostItem']);

        if ($nodesJobRows !== false && !is_null($nodesJobRows) && is_array($nodesJobRows) && count($nodesJobRows) > 0) {
            foreach ($nodesJobRows as $node) {
	            $job = $this->parseSingleJob($node);
	            if(!empty($job))
		            $ret[] = $job;
            }
        }
        else
        {
            $objSimpHTML->debug_dump_to_file();
	        $strNodeMatch = DomItemParser::getSelector($this->arrListingTagSetup['JobPostItem']);

            throw new \Exception("Could not find matching job elements in HTML for " . $strNodeMatch . " in plugin " . $this->JobSiteName);
        }

	    $this->log($this->JobSiteName . " returned " . countAssociativeArrayValues($ret) . " jobs from page.");

        return $ret;
    }

	/**
	 * @param \JobScooper\Utils\ExtendedDiDomElement $node
	 *
	 * @return array|null
	 * @throws \Exception
	 */
	function parseSingleJob(ExtendedDiDomElement $node)
    {
	    //
	    // get a new record with all columns set to null
	    //
	    $item = getEmptyJobListingRecord();

	    $item = $this->getJobFactsFromMicrodata($node, $item);

	    foreach(array_keys($this->arrListingTagSetup) as $itemKey)
	    {
		    if(in_array($itemKey, ["JobPostItem", "NextButton", "TotalResultPageCount", "TotalPostCount", "NoPostsFound"]))
			    continue;

		    $newVal = DomItemParser::getTagValue($node, $this->arrBaseListingTagSetup[$itemKey], $item);
		    if(!empty($newVal))
			    $item[$itemKey] = $newVal;
	    }

	    if (empty($item['Title']) || strcasecmp($item['Title'], "title") == 0)
		    return null;

	    if(empty($item['JobSiteKey']))
		    $item['JobSiteKey'] = $this->JobSiteName;

	    return $item;
    }

}
