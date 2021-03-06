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
abstract class ATSGreenhouse extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    private $gh_api_fmt = "https://api.greenhouse.io/v1/boards/%s/embed/jobs";
    private $gh_hostpage_fmt = "https://boards.greenhouse.io/%s";
    private $searchJsonUrlFmt = null;
    private $currentJsonSearchDetails = null;
    private $lastResponseData = null;
    protected $SiteReferenceKey = null;
    protected $JobListingsPerPage = 1000;
    protected $nTotalJobs = null;
    protected $additionalBitFlags = [C__JOB_USE_SITENAME_AS_COMPANY, C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED];
	
    protected $arrListingTagSetup = array(
//		'JobPostItem' => array('Selector' => 'ul.list-group li.list-group-item'),
//		'Title' => array('Selector' => 'h4.list-group-item-heading a'),
//		'Url' => array('Selector' => 'h4.list-group-item-heading a', 'Attribute' => 'href'),
//		'Location' => array('Selector' => 'ul li', 'Index' => 0),
//		'Department' => array('Selector' => 'ul li', 'Index' => 1),
    );

    public function __construct()
    {
        $this->searchJsonUrlFmt = sprintf($this->gh_api_fmt, $this->SiteReferenceKey);
        parent::__construct();
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

    }

    public function getHostPageUrl() {
        return sprintf($this->gh_hostpage_fmt, strtolower($this->JobSiteKey));
    }

    public function getJsonSearchUrl() {
        return sprintf($this->gh_api_fmt, strtolower($this->JobSiteKey));
    }

    /**
     * @param $jobs
     *
     * @return array
     */
    private function _parseJsonJobs($jobs)
    {
        $ret = array();
        foreach ($jobs as $job) {
            $item = array(
                'JobSiteKey' => $this->JobSiteKey,
                'JobSitePostId' => $job->id,
                'Company' => $this->JobSiteName,
                'Title' =>  $job->title,
                'Url' => $job->absolute_url,
                'Location' => $job->location->name,
                'PostedAt' => $job->updated_at
            );

            if(!is_empty_value($job->metadata) && count($job->metadata) >= 1) {
                foreach($job->metadata as $metadatum) {
                    switch($metadatum->name) {
                        case "Legal Entity":
                            $item['Company'] = $metadatum->value;
                            break;

                        case "Country":
                            if($item['Location'] != null) {
                                if (strtolower($metadatum->value) != strtolower($item['Location'])) {
                                    $item['Location'] .= $metadatum->value;
                                }
                            }
                            else {
                                $item['Location'] = $metadatum->value;
                            }
                            break;

                        case str_contains($metadatum->name, "Function"):
                        case str_contains($metadatum->name, "Dept Mapping"):
                            $item['Department'] = $metadatum->value;
                            break;

                        case str_contains($metadatum->name, "Employment Status"):
                        case str_contains($metadatum->name, "Employment Type"):
                            $item['EmploymentType'] = $metadatum->value;
                            break;

                        default:
                            break;
                    }
                }
            }

            $arrJobFacts = json_decode(json_encode($job), $assoc=true);

            if(array_key_exists("internal_job_id", $arrJobFacts)) {
                $item['InternalJobId'] = $arrJobFacts['internal_job_id'];
            }

            if(array_key_exists("education", $arrJobFacts)) {
                $item['Education'] = $arrJobFacts['education'];
            }

            $ret[$job->id] = $item;
        }
        return $ret;
    }


    public function parseTotalResultsCount(\JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML)
    {
        try {
            $retData = $this->getJsonResultsPage('jobs', "meta->total");
            if($retData == null or count($retData) == 0) {
                throwException("Unable to find total results for {$this->JobSiteKey} search." );
            }
            $this->nTotalJobs = $retData['count'];
            return $this->nTotalJobs;
        } catch (Throwable $t) {
            handleThrowable($t);
        }
        return null;
    }

    /**
     * @param \JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML
     *
     * @return array|null
     * @throws \Exception
     */
    public function parseJobsListForPage(\JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML)
    {
        try {
            $ret = array();
            $nOffset = 0;
            $data = $this->getJsonResultsPage("jobs", "meta->total");
            if (!empty($data) && !empty($data['jobs']) && \count($data['jobs']) > 0) {
                $jobs = $data['jobs'];

                $nTotal = $data['count'];
                while (!empty($jobs)) {
                    $curPageJobs = $this->_parseJsonJobs($jobs);
                    $ret = array_merge($ret, $curPageJobs);
                    $nOffset += \count($jobs);
                    if ($nOffset < $nTotal) {
                        $retData = $this->getJsonResultsPage($nOffset);
                        $jobs = $retData['jobs'];
                    } else {
                        $jobs = null;
                    }
                }
            }

            return $ret;
        } catch (Throwable $t) {
            handleThrowable($t);
        }
    }

    /**
     * @param int $jobsKey
     * @param int|array|null $countKey
     *
     * @throws \ErrorException
     * @throws \Exception
     * @return array
     */
    private function getJsonResultsPage($jobsKey='jobs', $countKey=null)
    {
	    LogMessage("Downloading JSON listing data from {$this->getJsonSearchUrl()} for {$this->JobSiteKey}...");
        // $hostPageUri = $this->currentJsonSearchDetails->getSearchStartUrl();
        $hostPageUri = $this->getHostPageUrl();

        $ret = array();
        $respdata = $this->getAjaxWebPageCallResult($this->getJsonSearchUrl(), $this->currentJsonSearchDetails, $hostPageUri, true);
        if (!empty($respdata)) {
            $this->lastResponseData = $respdata;
            try {
                $ret['jobs'] = $respdata->$jobsKey;
                $ret['count'] = 0;
                if (null !== $countKey) {
                	if(false !== strpos($countKey, '->')) {
                		$keys = explode('->', $countKey);
                		$counts = $respdata;
                		foreach($keys as $k) {
                			$counts = $counts->$k;
                		}
                		$ret['count'] = $counts;
                	 }
                	 else {
	                    $ret['count'] = $respdata->$countKey;
                    }
                } elseif (!is_empty_value($ret['jobs'])) {
                    $ret['count'] = \count($ret['jobs']);
                }
            } catch (Throwable $t) {
                handleThrowable($t);
            }
        }

        return $ret;
    }
    
}
