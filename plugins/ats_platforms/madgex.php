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
require_once __ROOT__."/bootstrap.php";

abstract class BaseMadgexATSPlugin extends \JobScooper\Plugins\Base\AjaxHtmlSimplePlugin
{

    function __construct()
    {
        $this->prevURL = $this->childSiteURLBase;
        $this->paginationType = C__PAGINATION_PAGE_VIA_URL;

        $this->siteBaseURL = $this->childSiteURLBase;
        $this->strBaseURLFormat = $this->childSiteURLBase . $this->strBaseURLFormat;
        parent::__construct();
    }

    protected $siteName = 'madgexats';
    protected $strBaseURLFormat = '?Keywords=***KEYWORDS***&radialtown=***LOCATION***&LocationId=&RadialLocation=50&NearFacetsShown=true&countrycode=***COUNTRYCODE***&Page=***PAGE_NUMBER***';
    protected $locationid = null;
    protected $currentSearchAlternateURL = null;

    protected $typeLocationSearchNeeded = 'location-city-comma-state';

    protected function getPageURLfromBaseFmt(&$searchDetails, $nPage = null, $nItem = null)
    {
        if(is_null($this->currentSearchAlternateURL)) {
            $strURL = parent::getPageURLfromBaseFmt($searchDetails, $nPage, $nItem);
            $strURL = str_ireplace("***COUNTRYCODE***", $GLOBALS['USERDATA']['configuration_settings']['location_sets'][$searchDetails['location_set_key']]['location-countrycode'], $strURL);

            if (!is_null($this->locationid))
                $strURL = $strURL . "&LocationID=" . $this->locationid;
        }
        else
        {
            $searchDetails['base_url_format'] = $this->currentSearchAlternateURL;
            $strURL = parent::getPageURLfromBaseFmt($searchDetails, $nPage, $nItem);
        }
        $searchDetails['base_url_format'] = preg_replace('/[Ppage]{4}=\d+/', 'Page=***PAGE_NUMBER***', $strURL);
        $this->strBaseURLFormat = $searchDetails['base_url_format'];
        return $strURL;

    }

    static function isNoJobResults($var)
    {
        return noJobStringMatch($var, "Found 0 jobs");
    }

    protected $arrListingTagSetup = array(
        'tag_listings_noresults'    => array('selector' => 'h1#searching', 'return_attribute' => 'plaintext', 'return_value_callback' => 'isNoJobResults'),
        'tag_listings_count'        => array('selector' => 'h1#searching', 'return_attribute' => 'plaintext', 'return_value_regex' =>  '/\b(\d+)\b/i'),
        'tag_listings_section'      => array('selector' => 'li.lister__item'),
        'tag_title'                 => array('selector' => 'h3 a.js-clickable-area-link span[itemprop="title"]', 'return_attribute' => 'plaintext'),
        'tag_link'                  => array('selector' => 'h3 a.js-clickable-area-link ', 'return_attribute' => 'href'),
        'tag_company'               => array('selector' => 'li[itemprop="hiringOrganization"]', 'return_attribute' => 'plaintext'),
        'tag_location'              => array('selector' => 'li[itemprop="location"]', 'return_attribute' => 'plaintext'),
        'tag_job_id'                =>  array('selector' => 'li.lister__item', 'return_attribute' => 'id', 'return_value_regex' =>  '/item\-(\d+)/i'),
        'tag_job_posted_date'       => array('selector' => 'li.job-actions__action pipe', 'index=0'),
        'tag_company_logo'          => array('selector' => 'img.lister__logo', 'return_attribute' => 'src'),
        'tag_next_button'           => array('selector' => 'li.paginator__item a[rel="next"]')
    );

    protected function getLocationURLValue($searchDetails, $locSettingSets = null)
    {
        $ret = parent::getLocationURLValue($searchDetails, $locSettingSets);
        if (stristr($ret, "%2C+washington") !== false)
            $ret .= "+state";
        return $ret;
    }

    function parseAndRedirectToLocation(&$objSimpHTML)
    {
        $locationSelectNode = $objSimpHTML->find("h2");
        if (!is_null($locationSelectNode) && count($locationSelectNode) == 1)
        {
            if(stristr($locationSelectNode[0]->plaintext, "select a location") !== false)
            {
                $nodeLocs = $objSimpHTML->find("li.lap-larger__item a");
                if (!is_null($nodeLocs) && count($nodeLocs) > 1)
                {
                    try
                    {
                        $newUrlPath = $nodeLocs[0]->href;
                        $newUrlPath = str_ireplace("&amp;", "&", $newUrlPath);
                        $arrMatches = array();
                        $matched = preg_match('/.*LocationId=(\d+).*/', $newUrlPath, $arrMatches);
                        if ($matched !== false && count($arrMatches) > 1)
                        {
                            $this->locationid = $arrMatches[1];
                        }
                        $url = parse_url($this->childSiteURLBase, PHP_URL_SCHEME) . "://" . parse_url($this->childSiteURLBase, PHP_URL_HOST) . $newUrlPath . "&RadialLocation=50";
                        $this->currentSearchBeingRun['search_start_url'] = $url;
                        $this->currentSearchAlternateURL = preg_replace('/[Ppage]{4}=\d+/', 'Page=***PAGE_NUMBER***', $url);

                        $this->selenium->loadPage($url);
                        $html = $this->selenium->getPageHTML($url);
                        $objSimpHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);
                    } catch (Exception $ex) {
                        $strError = "Failed to get dynamic HTML via Selenium due to error:  " . $ex->getMessage();
                        handleException(new Exception($strError), null, true);
                    }
                }
            }
        }
    }

    function parseTotalResultsCount($objSimpHTML)
    {

        $this->parseAndRedirectToLocation($objSimpHTML);

        return parent::parseTotalResultsCount($objSimpHTML);
    }
}

class PluginTheGuardian extends BaseMadgexATSPlugin
{
    protected $siteName = 'theguardian';
    protected $childSiteURLBase = 'https://jobs.theguardian.com/searchjobs';
}

class PluginMediaBistro extends BaseMadgexATSPlugin
{
    protected $siteName = 'mediabistro';
    protected $childSiteURLBase = 'https://www.mediabistro.com/jobs/search';
}
class PluginWashingtonPost extends BaseMadgexATSPlugin
{
    protected $siteName = 'washingtonpost';
    protected $childSiteURLBase = 'https://jobs.washingtonpost.com/searchjobs';
}

class PluginJobfinderUSA extends BaseMadgexATSPlugin
{
    protected $siteName = 'jobfinderusa';
    protected $childSiteURLBase = "https://www.jobfinderusa.com/searchjobs/";
}

class PluginGreatJobSpot extends BaseMadgexATSPlugin
{
    protected $siteName = 'greatjobspot';
    protected $childSiteURLBase = "https://www.greatjobspot.com/searchjobs/";
}


class PluginExecAppointments extends BaseMadgexATSPlugin
{
    protected $siteName = 'execappointments';
    protected $childSiteURLBase = "https://www.exec-appointments.com/searchjobs/";
}

class PluginEconomist extends BaseMadgexATSPlugin
{
    protected $siteName = 'economist';
    protected $childSiteURLBase = "http://jobs.economist.com/searchjobs";
}

class PluginStarTribune extends BaseMadgexATSPlugin
{
    protected $siteName = 'startribune';
    protected $childSiteURLBase = "http://jobs.startribune.com/searchjobs/";
}

