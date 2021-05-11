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

use http\Url;

/**
 * Class PluginCareerCast
 */
class PluginCareerCast extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    protected $JobSiteName = 'CareerCast';
    protected $PaginationType = C__PAGINATION_PAGE_VIA_URL;
    protected $JobListingsPerPage = 50;
    protected $LastKnownSiteLayout = 'jobsresponsivedefault';
    protected $CountryCodes = array("US");

    // BUGBUG: setting "search job title only" seems to not find jobs with just one word in the title.  "Pharmacy Intern" does not come back for "intern" like it should.  Therefore not setting the kwsJobTitleOnly=true flag.
    //
    protected $SearchUrlFormat = "http://www.careercast.com/jobs/results/keyword/***KEYWORDS***?view=List_Detail&SearchNetworks=US&networkView=national&location=***LOCATION***&radius=50&sort=PostDate+desc%2C+Priority+desc%2C+score+desc&rows=50&page=***PAGE_NUMBER***";
    protected $additionalLoadDelaySeconds = 2;
    protected $strBaseURLPathSuffix = "";
    protected $LocationType = 'location-city-comma-statecode';
    protected $nTotalJobs = null;
    protected $lastResponseData = null;
    /**
     * @var \JobScooper\DataAccess\UserSearchSiteRun|null
     */
    protected $currentJsonSearchDetails = null;

    protected $arrBaseListingTagSetupNationalSearch = array(
        'TotalPostCount' => ['Selector'=> 'div.arSearchResultsH1 h1.arSearchTitle'],  # BUGBUG:  need this empty array so that the parent class doesn't auto-set to C__JOB_ITEMCOUNT_NOTAPPLICABLE
        'NoPostsFound' => ['Selector'=> 'div#arNoResultsContainer div h5', 'Attribute'=> 'text', 'Callback'=> "matchesNoResultsPattern", 'CallbackParameter'=> 'Oops'],
        'JobPostItem' => ['Selector'=> 'div.arJobPodWrap'],
        'Title' => ['Selector'=> 'div.arJobTitle h3 a'],
        'Url' => ['Selector'=> 'div.arJobTitle h3 a', 'Attribute'=> 'href'],
        'JobSitePostId' => array('Selector' => 'div.arSaveJob a', 'Attribute' => 'data-jobid'),
        'Company' => array('Selector' => 'div.arJobCoLink'),
        'Location' => array('Selector' => 'div.arJobCoLoc'),
        'PostedAt' => array('Selector' => 'div.arJobPostDate'),
        'Category' => array('Selector' => 'div.aiDescriptionPod ul li', 'Index' => 3),
        'Brief' => array('Selector' => 'arJobSummary')
    );

    protected $arrBaseListingTagSetupJobsResponsive = array(
        'TotalPostCount' => array('Selector' => 'h1#search-title-holder', 'Pattern' => '/(.*) [Jj]obs/'),
        'JobPostItem' => array('Selector' => 'div.arJobPodWrap'),
        'Title' => array('Selector' => 'div.arJobTitle h3 a'),
        'Url' => array('Selector' => 'div.arJobTitle h3 a', 'Attribute' => 'href'),
        'JobSitePostId' => array('Selector' => 'div.arSaveJob a', 'Attribute' => 'data-jobid'),
        'Company' => array('Selector' => 'div.arJobCoLink'),
        'Location' => array('Selector' => 'div.arJobCoLoc'),
        'PostedAt' => array('Selector' => 'div.arJobPostDate span')
    );

    protected $_layout = null;

    /**
     * AbstractAdicio constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $fDoNotRemoveSetup = false;
        if (!is_empty_value($this->arrListingTagSetup)) {
            $fDoNotRemoveSetup = true;
        } else {
            $this->arrListingTagSetup = $this->arrBaseListingTagSetupNationalSearch;
        }

        parent::__construct();

        if ($fDoNotRemoveSetup !== true) {
            $this->arrListingTagSetup = array();
        }
    }

    /**
     * @param $apiUri
     * @param $hostPageUri
     *
     * @throws \Exception
     * @return \stdClass
     */
    private function getJsonResultsPage($apiUri, $hostPageUri=null)
    {
        LogMessage("Downloading JSON listing data from {$apiUri} for " . $this->JobSiteKey . "...");
        if (is_empty_value($hostPageUri)) {
            $hostPageUri = $this->getActiveWebdriver()->getCurrentURL();
        }
        if (is_empty_value($hostPageUri) && !is_empty_value($this->currentJsonSearchDetails)) {
            $hostPageUri = $this->currentJsonSearchDetails->searchResultsPageUrl;
        }

        $ret = array();
        $respdata = $this->getAjaxWebPageCallResult($apiUri, $this->currentJsonSearchDetails, $hostPageUri, true);
        if (!is_empty_value($respdata)) {
            $this->lastResponseData = $respdata;
            try {
                $ret['count'] = $respdata->Total;
                $ret['jobs'] = $respdata->Jobs;
            } catch (Throwable $t) {
                throw new \JobScooper\Exceptions\JobSitePluginException($respdata->error, previous: $t);
            }
        }

        return $respdata;
    }

    /**
     * @param $jobs
     *
     * @return array
     */
    private function _parseJsonJobs($jobs)
    {
        $jobsite = $this->JobSiteKey;
        $ret = array();
        foreach ($jobs as $job) {
            $ret[$job->Id] = array(
                'JobSiteKey' => $jobsite,
                'JobSitePostId' => "{$job->AdId}-{$job->Id}",
                'Company' => $job->Company,
                'Title' =>  $job->JobTitle,
                'Url' => $job->Url,
                'Location' => $job->FormattedCityStateCountry,
                'Category' => is_array($job->CategoryDisplay) ? implode(" | ", $job->CategoryDisplay) : null,
                'PostedAt' => $job->PostDate
            );
        }

        LogMessage("Loaded " . \count($ret) . " jobs from JSON with " . \count($jobs));
        return $ret;
    }

    /**
     * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
     * @param null                                     $nOffset
     *
     * @return string
     */
    private function _getJsonSearchUrl(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails, $nPageCount=1)
    {
        $pageUrl = $searchDetails->getPageURLfromBaseFmt($nPageCount);
        $jsonUrl = $pageUrl. "&format=json";

        return $jsonUrl;
    }
    /**
     * @param $searchDetails
     *
     * @return mixed
     * @throws \Exception
     */
    public function doFirstPageLoad(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
    {
        $this->nTotalJobs = 0;
        $this->lastResponseData = 0;

        $this->currentJsonSearchDetails = $searchDetails;
        $hostPage = $searchDetails->getSearchStartUrl();
        LogMessage("Loading first page for {$this->JobSiteKey} from {$hostPage}");
		$this->getSimpleHtmlDomFromSeleniumPage($searchDetails, $hostPage);
		
        LogMessage("Loading first page JSON for {$this->JobSiteKey} from {$hostPage}");
        $jsonUrl = $this->_getJsonSearchUrl($searchDetails);
        unset($retData);
        try {
            $retData = $this->getJsonResultsPage($jsonUrl, $hostPage);
            $this->nTotalJobs = $retData->Total;
            $this->lastResponseData = $retData;
        } catch (Throwable $t) {
            //
        }

        if (is_empty_value($this->nTotalJobs)) {
            $this->setLayoutIfNeeded($searchDetails);
        }

        if (is_empty_value($this->arrListingTagSetup)) {
            $this->setLayoutIfNeeded($searchDetails);
        }
    }

    public function getPageURLValue($nPage)
    {
        return (empty($nPage) || $nPage === 0) ? "1" : $nPage;
    }

    /**
     * @throws \Exception
     */
    protected function setLayoutIfNeeded(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
    {
        if (!is_empty_value($this->LastKnownSiteLayout)) {
            $this->setAdicioPageLayout($this->LastKnownSiteLayout);
        } else {
            $template = $this->_determinePageLayout($searchDetails);

            $this->setAdicioPageLayout($template);
            LogDebug("Adicio Template for " . get_class($this) . " with url '{$this->SearchUrlFormat}: " . PHP_EOL . "$template = {$template}," . PHP_EOL . "layout = {$this->_layout},  " . PHP_EOL . "template = {$template},  ");
        }
    }

    /**
     * parseTotalResultsCount
     *
     * If the site does not show the total number of results
     * then set the plugin flag to C__JOB_PAGECOUNT_NOTAPPLICABLE
     * in the Constants.php file and just comment out this function.
     *
     * parseTotalResultsCount returns the total number of listings that
     * the search returned by parsing the value from the returned HTML
     * *
     * @param $objSimpHTML
     * @return string|null
     * @throws \Exception
     */
    public function parseTotalResultsCount(\JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML)
    {
        if (!is_empty_value($this->nTotalJobs)) {
            return $this->nTotalJobs;
        }

        return parent::parseTotalResultsCount($objSimpHTML);
    }


    /**
     * @param \JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML
     *
     * @return array|null
     * @throws \Exception
     */
    public function parseJobsListForPage(\JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML)
    {
        $jobs = null;
        $pageNumber = 1;
        $totalPages = 1;

        if (!is_empty_value($this->lastResponseData) || !is_empty_value($this->currentJsonSearchDetails)) {
            try {
                $ret = array();
                $nOffset = 0;
                if (!is_empty_value($this->lastResponseData) && !is_empty_value($this->lastResponseData->Jobs) && \count($this->lastResponseData->Jobs) > 0) {
                    $jobs = $this->lastResponseData->Jobs;
                    unset($this->lastResponseData);
                } else {
                    $jsonUrl = $this->_getJsonSearchUrl($this->currentJsonSearchDetails, $pageNumber);
                    LogMessage("Loading job results JSON data for {$this->JobSiteKey} from {$jsonUrl}");
                    $respData = $this->getJsonResultsPage($jsonUrl);
                    $jobs = $respData->Jobs;
                    $this->nTotalJobs = $respData->Total;
                    $totalPages= ceil($this->nTotalJobs / $this->JobListingsPerPage);
                }
                //				return $this->_parseJsonJobs($jobs);
//
                while (!is_empty_value($jobs) && $pageNumber <= $totalPages) {
                    $pageNumber = $pageNumber + 1;
                    $curPageJobs = $this->_parseJsonJobs($jobs);
                    unset($jobs);
                    $ret = array_merge($ret, $curPageJobs);
                    $nOffset = $nOffset + \count($curPageJobs);
                    if ($nOffset < $this->nTotalJobs) {
                        $jsonUrl = $this->_getJsonSearchUrl($this->currentJsonSearchDetails, $pageNumber);
                        LogMessage("Loading next page ({$pageNumber}) of JSON data for {$this->JobSiteKey} from {$this->getActiveWebdriver()->getCurrentURL()}");
                        $respData = $this->getJsonResultsPage($jsonUrl);
                        $jobs = $respData->Jobs;
                    }
                    else {
                        $jobs = null;
                    }
                }

                return $ret;
            } catch (Throwable $t) {
                LogWarning("Failed to download " . $this->JobSiteKey . " listings via JSON.  Reverting to HTML.  " . $t->getMessage());

                return parent::parseJobsListForPage($objSimpHTML);
            }
        }

        return parent::parseJobsListForPage($objSimpHTML);
    }

    /**
     * @throws \Exception
     */
    private function _determinePageLayout(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
    {
        $template = null;
        $urlParts = parse_url($this->SearchUrlFormat);
        $urlParts['query'] = "";
        $urlParts['fragment'] = "";
        $urlParts['path'] = "/jobs/search/results";
        $baseUrl = \JBZoo\Utils\Url::buildAll($urlParts);
        $url = $baseUrl;
        if (null === $this->selenium) {
            try {
                $this->selenium = new \JobScooper\Manager\SeleniumManager();
            } catch (Throwable $t) {
                handleThrowable($t, "Unable to start Selenium to get jobs for plugin '" . $this->JobSiteName . "'", true);
            }
        }

        $baseHTML = $this->getSimpleHtmlDomFromSeleniumPage($searchDetails, $url);
        $this->_layout = "careersdefault";

        if (!is_empty_value($baseHTML)) {
            try {
                $head = $baseHTML->find("head");
                if (!is_empty_value($head) && \count($head) >= 1) {
                    foreach ($head[0]->children() as $child) {
                        if ($child->isCommentNode()) {
                            $template = "unknown";
                            $id = 0;
                            $matches = array();
                            $matched = preg_match('/actually used:\s*([^(\-]+)\s*.*?/', $child->text(), $matches);
                            if ($matched !== false) {
                                if (count($matches) > 2) {
                                    $template = $matches[2];
                                    $id = $matches[3];
                                } elseif (count($matches) == 2) {
                                    $template = $matches[1];
                                }
                                break;
                            }
                        }
                    }
                }
            } catch (Throwable $t) {
            } finally {
                $this->selenium->done();
                unset($this->selenium);
            }
        }
    }

    /**
     * @param $layout
     */
    protected function setAdicioPageLayout($layout)
    {
        $tags = array();
        $switchVal = cleanupSlugPart($layout, "");
        $this->_layout = $switchVal;
        switch ($switchVal) {
            case 'jobsresponsivedefault':
                $tags = $this->arrBaseListingTagSetupJobsResponsive;
                break;

            case "careersdefault":
                $tags = $this->arrBaseListingTagSetupNationalSearch;
                break;

            case "jobsearchresults":
                $tags = $this->arrBaseListingTagSetupNationalSearch;
                $this->arrListingTagSetup['TotalPostCount']['Selector'] = "span#retCountNumber";
                break;

            default:
                LogWarning("UNKNOWN ADICIO LAYOUT");
                $this->_layout = "default";
                $tags = $this->arrBaseListingTagSetupNationalSearch;
                break;
        }
        $this->arrListingTagSetup = array_merge_recursive_distinct($tags, $this->arrListingTagSetup);
    }
}
