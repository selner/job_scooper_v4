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
abstract class AbstractMadgexATS extends \JobScooper\SitePlugins\AjaxSitePlugin
{
	protected $SiteSearchBaseUrl = null;

	/**
	 * AbstractMadgexATS constructor.
	 * @throws \Exception
	 */
	function __construct()
    {
    	$this->locationid = null;
	    $searchURL = $this->JobPostingBaseUrl;
    	if(null !== $this->SiteReferenceKey ) {
		    $this->JobPostingBaseUrl = "https://{$this->SiteReferenceKey}.com/searchjobs";
		    $searchURL = $this->JobPostingBaseUrl;
	    }
	    elseif(null !== $this->SiteSearchBaseUrl ) {
			$searchURL = $this->SiteSearchBaseUrl;
			$urlparts = parse_url($this->SiteSearchBaseUrl);
			if(empty($urlparts['path']))
				$searchURL = "{$searchURL}/searchjobs";
		}

		if(null !== $searchURL) {
			$this->SearchUrlFormat = "{$searchURL}?Keywords=***KEYWORDS***&radialtown=***LOCATION:{Place}***+***LOCATION:{Region}***&RadialLocation=50&NearFacetsShown=true&countrycode=***LOCATION:{CountryCode}***&sort=Date&Page=***PAGE_NUMBER***";
		}
        $this->prevURL = $this->JobPostingBaseUrl;
        $this->PaginationType = C__PAGINATION_PAGE_VIA_URL;
        $this->additionalLoadDelaySeconds = 3;

        $this->additionalBitFlags[] = C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER;
        parent::__construct();
	    $this->_flags_ &= ~C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED;
    }

    protected $JobSiteName = 'madgexats';
    protected $locationid = null;
    protected $SiteReferenceKey = null;

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
			$callback = "getLocationIdFromPage";
			if(array_key_exists("LocationCallback", get_object_vars($this))){
				$callback = $this->LocationCallback;
			}

			$this->$callback($searchDetails);
		}
		else {
			$url = $url . "&LocationID=" . $this->locationid;
			$searchDetails->setSearchStartUrl($url);
		}

		$this->selenium->loadPage($searchDetails->getSearchStartUrl());

		$jobitemsTarget = $this->getDomWindowVariable("window.MDGX.FacetLoader.pJaxTargetContainer");
		if(null !== $jobitemsTarget) {
			$this->arrListingTagSetup['JobPostItem']['selector'] = $jobitemsTarget;

		}


	}

    protected $arrBaseListingTagSetup = array(
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
	protected function getLocationIdFromPage(UserSearchSiteRun $searchDetails)
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

	    $this->locationid = $objLocChoices[0]->value;
		$newUrl = $searchDetails->getSearchStartUrl() . "&LocationId={$this->locationid}";
		$searchDetails->setSearchStartUrl($newUrl);
    }

    protected function setLocationFromJobTaxonomy(UserSearchSiteRun $searchDetails)
    {
    	foreach(array("{Place}", "{Region}") as $locstr)
	    {
		    $locValParam = $searchDetails->getGeoLocationURLValue($locstr);
		    $locApi = parse_url($searchDetails->getSearchStartUrl(), PHP_URL_SCHEME) . "://" . parse_url($searchDetails->getSearchStartUrl(), PHP_URL_HOST) . "/suggestions/JobTaxonomy/?q={$locValParam}";

		    LogMessage("Determining LocationId value for {$locValParam}... from {$locApi} ..." );
		    $data = $this->getJsonApiResult($locApi, $searchDetails, $searchDetails->getSearchStartUrl());
		    if(null === $data)
		    {
			    continue;
		    }

		    $arrLocChoices = object_to_array($data);
		    if (array_key_exists('data', $arrLocChoices['suggestions'][0]) && array_key_exists('url', $arrLocChoices['suggestions'][0]['data'])) {

			    $newUrl = parse_url($searchDetails->getSearchStartUrl(), PHP_URL_SCHEME) . "://" . parse_url($searchDetails->getSearchStartUrl(), PHP_URL_HOST) . $arrLocChoices['suggestions'][0]['data']['url'] .  "?" . parse_url($searchDetails->getSearchStartUrl(), PHP_URL_QUERY);

			    $searchDetails->setSearchStartUrl($newUrl);
			    return;
		    }
	    }

    }
}
