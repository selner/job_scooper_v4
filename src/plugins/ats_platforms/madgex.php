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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');


abstract class BaseMadgexATSPlugin extends ClassClientHTMLJobSitePlugin
{
    function __construct($strOutputDirectory = null)
    {
        $this->additionalFlags[] = C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES;
        $this->additionalFlags[] = C__JOB_PAGE_VIA_URL;

        $this->siteBaseURL = $this->childSiteURLBase;
        $this->strBaseURLFormat = $this->childSiteURLBase . $this->strBaseURLFormat;
        parent::__construct($strOutputDirectory);
    }

    protected $siteName = 'madgexats';
    protected $strBaseURLFormat = '?Keywords=***KEYWORDS***&radialtown=***LOCATION***&LocationId=&RadialLocation=50&NearFacetsShown=true&countrycode=***COUNTRYCODE***&Page=***PAGE_NUMBER***';
    protected $locationid = null;

    protected $typeLocationSearchNeeded = 'location-city-comma-state';

    protected function getPageURLfromBaseFmt($searchDetails, $nPage = null, $nItem = null)
    {
        $strURL = parent::getPageURLfromBaseFmt($searchDetails, $nPage, $nItem);
        $strURL = str_ireplace("***COUNTRYCODE***", $GLOBALS['USERDATA']['configuration_settings']['location_sets'][$searchDetails['location_set_key']]['location-countrycode'], $strURL);

        if (!is_null($this->locationid))
            $strURL = $strURL . "&LocationID=" . $this->locationid;

        return $strURL;

    }

    function parseTotalResultsCount(&$objSimpHTML)
    {


        $nodeLocs = $objSimpHTML->find("li.lap-larger__item a");
        if (!is_null($nodeLocs) && count($nodeLocs) > 1)
        {
            try
            {

                $url = parse_url($this->childSiteURLBase, PHP_URL_SCHEME) . "://" . parse_url($this->childSiteURLBase, PHP_URL_HOST) . $nodeLocs['0']->href . "&RadialLocation=50";
                $url = str_ireplace("&amp;", "&", $url);

                $arrMatches = array();
                $matched = preg_match('/.*LocationId=(\d+).*/', $url, $arrMatches);
                if ($matched !== false && count($arrMatches) > 1)
                {
                    $this->locationid = $arrMatches[1];
                }

                $selen = new SeleniumSession($this->additionalLoadDelaySeconds);
                $html = $selen->getPageHTML($url);
                $objSimpHTML = new SimpleHtmlDom\simple_html_dom($html, null, true, null, null, null, null);
            } catch (Exception $ex) {
                $strError = "Failed to get dynamic HTML via Selenium due to error:  " . $ex->getMessage();
                handleException(new Exception($strError), null, true);
            }
        }


        $nTotalResults = C__TOTAL_ITEMS_UNKNOWN__;

        //
        // Find the HTML node that holds the result count
        $nodeCounts = $objSimpHTML->find("h1");
        if($nodeCounts != null && is_array($nodeCounts) && isset($nodeCounts[0]))
        {
            $counts = explode(" ", $nodeCounts[0]->plaintext);
            $nTotalResults = \Scooper\intceil($counts[1]);
        }


        return $nTotalResults;

    }

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('selector' => "li.lister__item"),
        'tag_link' =>  array('selector' => 'a.js-clickable-area-link', 'return_attribute' => 'href'),
        'tag_title' =>  array('selector' => 'a.js-clickable-area-link span', 'return_attribute' => 'plaintext'),
        'tag_job_id' =>  array('selector' => 'li.lister__item', 'return_attribute' => 'id'),
        'tag_location' =>  array('selector' => 'p.lister__meta span', 'index' => 0),
        'tag_company' =>  array('selector' => 'p.lister__meta span', 'index' => 2),
        'tag_job_posted' =>  array('selector' => 'ul.job-actions li', 'index' => 0),
        'tag_next_button' =>  array('selector' => 'a[title="Next page"]')

    );

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
    protected $childSiteURLBase = 'https://jobs.washingtonpost.com/jobs';
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

