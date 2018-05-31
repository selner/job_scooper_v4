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
	protected $SiteVariant = null;


    private $tagsBySiteVariant = [
    	'JobCountInH1' => [
	        'TotalPostCount'        => ['Selector' => 'div#results h1', 'Attribute' => 'text', 'Pattern' =>  '/.*?\s([\d,]+)\s*/']
		],
    	'JobCountInH2' => [
	        'TotalPostCount'        => ['Selector' => 'h2', 'Attribute' => 'text', 'Pattern' =>  '/.*?\s([\d,]+)\s*/']
		]
	];

    /**
     * AbstractMadgexATS constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->locationid = null;
        $searchURL = $this->JobPostingBaseUrl;
        if (null !== $this->SiteReferenceKey) {
            $this->JobPostingBaseUrl = "https://{$this->SiteReferenceKey}.com/searchjobs";
            $searchURL = $this->JobPostingBaseUrl;
        } elseif (null !== $this->SiteSearchBaseUrl) {
            $searchURL = $this->SiteSearchBaseUrl;
            $urlparts = parse_url($this->SiteSearchBaseUrl);
            if (empty($urlparts['path'])) {
                $searchURL = "{$searchURL}/searchjobs";
            }
        }

        if (null !== $searchURL) {
            $this->SearchUrlFormat = "{$searchURL}?Keywords=***KEYWORDS***&radialtown=***LOCATION:{Place}***+***LOCATION:{Region}***&RadialLocation=50&NearFacetsShown=true&countrycode=***LOCATION:{CountryCode}***&sort=Date&Page=***PAGE_NUMBER***";
        }
        $this->prevURL = $this->JobPostingBaseUrl;
        $this->PaginationType = C__PAGINATION_PAGE_VIA_URL;
        $this->additionalLoadDelaySeconds = 3;

        $this->additionalBitFlags[] = C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER;
        parent::__construct();
        $this->_flags_ &= ~C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED;

        if(!is_empty_value($this->SiteVariant) && array_key_exists($this->SiteVariant, $this->tagsBySiteVariant))
        {
        	$this->arrListingTagSetup = array_merge_recursive_distinct($this->arrListingTagSetup,$this->tagsBySiteVariant[$this->SiteVariant]);
        }
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
    public function doFirstPageLoad(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
    {
        $this->getSimpleHtmlDomFromSeleniumPage($searchDetails, $searchDetails->getSearchStartUrl());

        $url = $this->getActiveWebdriver()->getCurrentURL();
        if (empty($this->locationid)) {
            $callback = "getLocationIdFromPage";
            if (array_key_exists("LocationCallback", get_object_vars($this))) {
                $callback = $this->LocationCallback;
            }

            $this->$callback($searchDetails);
        } else {
            $url = $url . "&LocationID=" . $this->locationid;
            $searchDetails->setSearchStartUrl($url);
        }

        $this->selenium->loadPage($searchDetails->getSearchStartUrl());

        $jobitemsTarget = $this->getDomWindowVariable('
			var val = null;
			try {
			    val = window.MDGX.FacetLoader.pJaxTargetContainer;
			} catch (err) {
			    console.log(xyz);
			}
			return val;
');
        if (null !== $jobitemsTarget) {
            $this->arrListingTagSetup['JobPostItem']['Selector'] = $jobitemsTarget;
        }
    }

    protected $arrBaseListingTagSetup = array(
        'NoPostsFound'          => array('Selector' => 'h1#searching', 'Attribute' => 'text', 'Callback' => 'matchesNoResultsPattern', 'CallbackParameter' => "Found 0 jobs"),
        'TotalPostCount'        => array('Selector' => 'h1#searching', 'Attribute' => 'text', 'Pattern' =>  '/\b([,\d]+)\b/i'),
//        'JobPostItem'           => array('Selector' => 'li.lister__item'),
        'JobPostItem'           => array('Selector' => 'ul#listing li.cf'),
        'Title'                 => array('Selector' => 'h3.lister__header a span', 'Attribute' => 'text', 'Index' =>0),
        'Url'                   => array('Selector' => 'h3.lister__header a', 'Attribute' => 'href', 'Index' =>0),
        'Company'               => array('Selector' => 'ul li.lister__meta-item--recruiter', 'Attribute' => 'text', 'Index' =>0),
        'PageRange'             => array('Selector' => 'ul li.lister__meta-item--salary', 'Attribute' => 'text', 'Index' =>0),
        'Location'              => array('Selector' => 'ul li.lister__meta-item--location', 'Attribute' => 'text', 'Index' =>0),
        'JobSitePostId'         => array('Selector' => 'li', 'Attribute' => 'id', 'Pattern' =>  '/item\-(\d+)/i', 'Index' =>0),
        'PostedAt'              => array('Selector' => 'li.job-actions__action', 'Index' =>0),
        'company_logo'          => array('Selector' => 'img.lister__logo', 'Attribute' => 'src', 'Index' =>0),
        'NextButton'            => array('Selector' => 'a[rel="next"]', 'Index' =>0)
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

        LogMessage("Determining LocationId value for {$locValParam}... from {$locApi} ...");
        $objLocChoices = $this->getJsonApiResult($locApi, $searchDetails, $searchDetails->getSearchStartUrl());
        if (empty($objLocChoices) || !is_array($objLocChoices)) {
            return null;
        }

        $this->locationid = $objLocChoices[0]->value;
        $newUrl = $searchDetails->getSearchStartUrl() . "&LocationId={$this->locationid}";
        $searchDetails->setSearchStartUrl($newUrl);
    }

    protected function setLocationFromJobTaxonomy(UserSearchSiteRun $searchDetails)
    {
        foreach (array("{Place}", "{Region}") as $locstr) {
            $locValParam = $searchDetails->getGeoLocationURLValue($locstr);
            $locApi = parse_url($searchDetails->getSearchStartUrl(), PHP_URL_SCHEME) . "://" . parse_url($searchDetails->getSearchStartUrl(), PHP_URL_HOST) . "/suggestions/JobTaxonomy/?q={$locValParam}";

            LogMessage("Determining LocationId value for {$locValParam}... from {$locApi} ...");
            $data = $this->getJsonApiResult($locApi, $searchDetails, $searchDetails->getSearchStartUrl());
            if (is_empty_value($data)) {
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
