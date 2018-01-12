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
use JobScooper\DataAccess\UserSearchSiteRun;

/**
 * Class AbstractMadgexATS
 */
abstract class AbstractMadgexATS extends \JobScooper\BasePlugin\Classes\AjaxHtmlSimplePlugin
{

	/**
	 * AbstractMadgexATS constructor.
	 * @throws \Exception
	 */
	function __construct()
    {
        $this->prevURL = $this->childSiteURLBase;
        $this->PaginationType = C__PAGINATION_PAGE_VIA_URL;

        $this->JobPostingBaseUrl = $this->childSiteURLBase;
        $this->SearchUrlFormat = $this->childSiteURLBase . $this->SearchUrlFormat;
        $this->additionalBitFlags[] = C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER;
        parent::__construct();
    }

    protected $JobSiteName = 'madgexats';
    protected $SearchUrlFormat = '?Keywords=***KEYWORDS***&radialtown=***LOCATION***&LocationId=&RadialLocation=50&NearFacetsShown=true&countrycode=***COUNTRYCODE***&sort=Date&Page=***PAGE_NUMBER***';
    protected $locationid = null;

    protected $LocationType = 'location-city-comma-state';

	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 * @param null                                     $nPage
	 * @param null                                     $nItem
	 *
	 * @return mixed|null|string|string[]
	 * @throws \Exception
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	protected function getPageURLfromBaseFmt(UserSearchSiteRun $searchDetails, $nPage = null, $nItem = null)
    {
        $strURL = parent::getPageURLfromBaseFmt($searchDetails, $nPage, $nItem);
        $location = $searchDetails->getGeoLocation();
        $ccode = "";
        if(!empty($location))
            $ccode = $location->getCountryCode();
        $strURL = str_ireplace("***COUNTRYCODE***", $ccode, $strURL);

        if (!empty($this->locationid))
            $strURL = $strURL . "&LocationID=" . $this->locationid;

        $page = $nPage == 1 ? "" : "&Page=" . $this->getPageURLValue($nPage);
        $strURL = preg_replace('/&[Pp]age=\d+/', $page, $strURL);
        return $strURL;

    }

	/**
	 * @param $var
	 *
	 * @return int|null
	 * @throws \Exception
	 */
	static function checkNoJobResults($var)
    {
        return noJobStringMatch($var, "Found 0 jobs");
    }

    protected $arrListingTagSetup = array(
        'NoPostsFound'          => array('selector' => 'h1#searching', 'return_attribute' => 'text', 'return_value_callback' => 'checkNoJobResults'),
        'TotalPostCount'        => array('selector' => 'h1#searching', 'return_attribute' => 'text', 'return_value_regex' =>  '/\b(\d+)\b/i'),
        'JobPostItem'           => array('selector' => 'li.lister__item'),
        'Title'                 => array('selector' => 'h3.lister__header a span', 'return_attribute' => 'text'),
        'Url'                   => array('selector' => 'h3.lister__header a', 'return_attribute' => 'href'),
        'Company'               => array('selector' => 'ul li.lister__meta-item--recruiter', 'return_attribute' => 'text'),
        'PageRange'             => array('selector' => 'ul li.lister__meta-item--salary', 'return_attribute' => 'text'),
        'Location'              => array('selector' => 'ul li.lister__meta-item--location', 'return_attribute' => 'text'),
        'JobSitePostId'         => array('selector' => 'li.lister__item', 'return_attribute' => 'id', 'return_value_regex' =>  '/item\-(\d+)/i'),
        'PostedAt'              => array('selector' => 'li.job-actions__action', 'index' =>0),
        'company_logo'          => array('selector' => 'img.lister__logo', 'return_attribute' => 'src'),
        'NextButton'            => array('selector' => 'li.paginator__item a[rel="next"]')
    );

	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 *
	 * @return null|string
	 * @throws \ErrorException
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	protected function getGeoLocationURLValue(UserSearchSiteRun $searchDetails)
    {
        $ret = parent::getGeoLocationURLValue($searchDetails);
        if (stristr($ret, "%2C+washington") !== false)
            $ret .= "+state";
        return $ret;
    }

	/**
	 * @param $objSimpHTML
	 *
	 * @throws \Exception
	 */
	function parseAndRedirectToLocation(&$objSimpHTML)
    {
        $locationSelectNode = $objSimpHTML->find("h2");
        if (!is_null($locationSelectNode) && count($locationSelectNode) == 1)
        {
            if(stristr($locationSelectNode[0]->text(), "select a location") !== false)
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
	                    $curSearch = getConfigurationSetting('current_user_search_details');
	                    if(!empty($curSearch))
		                    $curSearch->setSearchStartUrl($url);
//                        $this->currentSearchAlternateURL = preg_replace('/[Ppage]{4}=\d+/', 'Page=***PAGE_NUMBER***', $url);

                        $this->selenium->loadPage($url);
                        $html = $this->selenium->getPageHTML($url);
                        $objSimpHTML = new \JobScooper\Utils\SimpleHTMLHelper($html);
                    } catch (Exception $ex) {
                        handleException(new Exception("Failed to parseAndRedirectToLocation", $ex->getCode(), $ex), null, true);
                    }
                }
            }
        }
    }

	/**
	 * @param $objSimpHTML
	 *
	 * @return null|string
	 * @throws \Exception
	 */
	function parseTotalResultsCount(\JobScooper\Utils\SimpleHTMLHelper $objSimpHTML)
    {

        $this->parseAndRedirectToLocation($objSimpHTML);

        return parent::parseTotalResultsCount($objSimpHTML);
    }
}

/**
 * Class PluginTheGuardian
 */
class PluginTheGuardian extends AbstractMadgexATS
{
    protected $JobSiteName = 'theguardian';
    protected $childSiteURLBase = 'https://jobs.theguardian.com/searchjobs';
}

/**
 * Class PluginLocalworkCA
 */
class PluginLocalworkCA extends AbstractMadgexATS
{
	protected $JobSiteName = 'LocalworkCA';
	protected $childSiteURLBase = 'https://www.localwork.ca/searchjobs';
	protected $CountryCodes = ["CA"];
}


/**
 * Class PluginMediaBistro
 */
class PluginMediaBistro extends AbstractMadgexATS
{
    protected $JobSiteName = 'mediabistro';
    protected $childSiteURLBase = 'https://www.mediabistro.com/jobs/search';
}

/**
 * Class PluginWashingtonPost
 */
class PluginWashingtonPost extends AbstractMadgexATS
{
    protected $JobSiteName = 'washingtonpost';
    protected $childSiteURLBase = 'https://jobs.washingtonpost.com/searchjobs';
}

/**
 * Class PluginJobfinderUSA
 */
class PluginJobfinderUSA extends AbstractMadgexATS
{
    protected $JobSiteName = 'jobfinderusa';
    protected $childSiteURLBase = "https://www.jobfinderusa.com/searchjobs/";
//	https://www.jobfinderusa.com/searchjobs/?LocationId=5865100&keywords=Experience&radiallocation=50&countrycode=US&sort=Date&Page=2
}

/**
 * Class PluginGreatJobSpot
 */
class PluginGreatJobSpot extends AbstractMadgexATS
{
    protected $JobSiteName = 'greatjobspot';
    protected $childSiteURLBase = "https://www.greatjobspot.com/searchjobs/";
}


/**
 * Class PluginExecAppointments
 */
class PluginExecAppointments extends AbstractMadgexATS
{
    protected $JobSiteName = 'execappointments';
    protected $childSiteURLBase = "https://www.exec-appointments.com/searchjobs/";
}

/**
 * Class PluginEconomist
 */
class PluginEconomist extends AbstractMadgexATS
{
    protected $JobSiteName = 'economist';
    protected $childSiteURLBase = "http://jobs.economist.com/searchjobs";
}

/**
 * Class PluginStarTribune
 */
class PluginStarTribune extends AbstractMadgexATS
{
    protected $JobSiteName = 'startribune';
    protected $childSiteURLBase = "http://jobs.startribune.com/searchjobs/";
}

