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
    	$this->locationid = null;
    	if(empty($this->JobPostingBaseUrl))
		    $this->JobPostingBaseUrl = "https://{$this->SiteReferenceKey}.com";

	    $this->SearchUrlFormat = "{$this->JobPostingBaseUrl}/searchjobs?Keywords=***KEYWORDS***&radialtown=***LOCATION:{Place}***+***LOCATION:{Region}***&RadialLocation=50&NearFacetsShown=true&countrycode=***LOCATION:{CountryCode}***&sort=Date&Page=***PAGE_NUMBER***";
        $this->prevURL = $this->JobPostingBaseUrl;
        $this->PaginationType = C__PAGINATION_PAGE_VIA_URL;
        $this->additionalLoadDelaySeconds = 3;

        $this->additionalBitFlags[] = C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER;
        parent::__construct();
    }

    protected $JobSiteName = 'madgexats';
    protected $locationid = null;
    protected $SiteReferenceKey = "UNKNOWN";

    protected $LocationType = 'location-city-comma-state';



	/**
	 * @param $searchDetails
	 *
	 * @throws \Exception
	 */
	function doFirstPageLoad(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
	{
		$this->selenium->getPageHTML($searchDetails->getSearchStartUrl());

		$url = $this->getActiveWebdriver()->getCurrentURL();
		if (empty($this->locationid)) {
			$this->locationid = $this->getLocationIdFromPage($searchDetails);
		}

		if (!empty($this->locationid))
			$url = $url . "&LocationID=" . $this->locationid;

		$searchDetails->setSearchStartUrl($url);
		$this->selenium->loadPage($url);
	}

    protected $arrListingTagSetup = array(
        'NoPostsFound'          => array('selector' => 'h1#searching', 'return_attribute' => 'text', 'return_value_callback' => 'matchesNoResultsPattern', 'callback_parameter' => "Found 0 jobs"),
        'TotalPostCount'        => array('selector' => 'h1#searching', 'return_attribute' => 'text', 'return_value_regex' =>  '/\b([,\d]+)\b/i'),
//        'JobPostItem'           => array('selector' => 'li.lister__item'),
        'JobPostItem'           => array('selector' => 'ul#listing li.cf'),
        'Title'                 => array('selector' => 'h3.lister__header a span', 'return_attribute' => 'text', 'index' =>0),
        'Url'                   => array('selector' => 'h3.lister__header a', 'return_attribute' => 'href', 'index' =>0),
        'Company'               => array('selector' => 'ul li.lister__meta-item--recruiter', 'return_attribute' => 'text', 'index' =>0),
        'PageRange'             => array('selector' => 'ul li.lister__meta-item--salary', 'return_attribute' => 'text', 'index' =>0),
        'Location'              => array('selector' => 'ul li.lister__meta-item--location', 'return_attribute' => 'text', 'index' =>0),
        'JobSitePostId'         => array('selector' => 'li', 'return_attribute' => 'id', 'return_value_regex' =>  '/item\-(\d+)/i', 'index' =>0),
        'PostedAt'              => array('selector' => 'li.job-actions__action', 'index' =>0),
        'company_logo'          => array('selector' => 'img.lister__logo', 'return_attribute' => 'src', 'index' =>0),
        'NextButton'            => array('selector' => 'a[rel="next"]', 'index' =>0)
    );


	/**
	 * @param UserSearchSiteRun &$searchDetails
	 *
	 * @return null|integer
	 *
	 * @throws \Exception
	 */
	protected function getLocationIdFromPage(UserSearchSiteRun &$searchDetails)
    {
	    $locValParam = $searchDetails->getGeoLocationURLValue("{Place} {Region}");
	    $locApi = parse_url($searchDetails->getSearchStartUrl(), PHP_URL_SCHEME) . "://" . parse_url($searchDetails->getSearchStartUrl(), PHP_URL_HOST) . "/location-lookup/?term={$locValParam}";
//	    $locApi = "http://" . parse_url($this->JobPostingBaseUrl, PHP_URL_HOST) . "/location-lookup/?term={$locValParam}";

	    LogMessage("Determining LocationId value for {$locValParam}... from {$locApi} ..." );
	    $objLocChoices = $this->getJsonApiResult($locApi, $searchDetails, $searchDetails->getSearchStartUrl());
	    if(empty($objLocChoices) || !is_array($objLocChoices))
	    {
	    	return null;
	    }

	    $locId = $objLocChoices[0]->value;

	    $newUrl = $searchDetails->getSearchStartUrl() . "&LocationId=" . strval($locId);
	    $searchDetails->setSearchStartUrl($newUrl);

	    return $locId;
    }
}
