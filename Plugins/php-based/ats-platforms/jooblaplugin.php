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

class PluginJoobla extends \JobScooper\SitePlugins\RestApiPlugin
{
    /**
     * @var string
     */
    protected $JobPostingBaseUrl = 'http://search.digitalgov.gov/developer/jobs.html';
    /**
     * @var string
     */
    protected $SearchUrlFormat = 'https://us.jooble.org/api/';
    /**
     * @var string
     */
    protected $JobSiteName = 'Joobla';
    /**
     * @var int
     */
    protected $JobListingsPerPage = 20;
    /**
     * @var string
     */
    protected $LocationType = 'location-city';
    /**
     * @var array
     */
    protected $CountryCodes = array("UK", "US");

    /**
     * @param $searchDetails
     * @param int $pageNumber
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSearchJobsFromAPI($searchDetails, $pageNumber = 1)
    {
        $resp = $this->_callApi($searchDetails, $pageNumber);
        $nTotalJobs = $resp['totalCount'];
        $arrJobs = array_column($resp['jobs'], null, 'id');
        $nJobs = count($arrJobs);
        while($pageNumber < ceil($nTotalJobs/$this->JobListingsPerPage) && ($nJobs < $this->nMaxJobsToReturn)) {
            $pageNumber += 1;
            $resp = $this->_callApi($searchDetails, $pageNumber);
            $arrJobs = $arrJobs +  array_column($resp['jobs'], null, 'id');
            $nJobs = count($arrJobs);
        }
        return $arrJobs;
    }

    /**
     * @param $searchDetails
     * @param int $pageNumber
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function _callApi($searchDetails, $pageNumber = 1)
    {
        $response = null;

        if(!array_key_exists('apikey', $this->_otherPluginSettings)) {
            throw new InvalidArgumentException("{$this->JobSiteName} plugin missing required apikey value in config.");
        }
        try {
            $uri = "{$this->SearchUrlFormat}{$this->_otherPluginSettings['apikey']}";

            // Add parameters to the query via the constructor
            //
            /* searchMode = Job listings display mode
                1 - Recommended job listngs + *JDP (Jooble Job Description mode for a better user experience)
                2 - Recommended job listings
                3 - All job listings (not recommended)
            */
            $data = [
                'location' => $searchDetails->getGeoLocation("{Place}"),
                'keyword' => $searchDetails->getUserKeyword(),
                'searchMode' => 3,
                'radius' => 15,
                'page' => $pageNumber
            ];

            $client = new GuzzleHttp\Client();

//            $headers = [
//                'Content-type' => 'application/x-www-form-urlencoded; charset=utf-8',
//                'Accept' => 'application/json',
//            ];

            // Grab the client's handler instance.
            $clientHandler = $client->getConfig('handler');

            // Create a middleware that echoes parts of the request.
            $tapMiddleware = \GuzzleHttp\Middleware::tap(function ($request) {
                echo $request->getHeaderLine('Content-Type');
                // application/json
                echo $request->getBody();
                // {"foo":"bar"}
            });

            $response = $client->request(
                'POST',
                $uri,
                [
                    'json'    => $data,
                    'debug'    => isDebug(),
                    'http_errors' => false,
                    'handler' => $tapMiddleware($clientHandler)
                ]
            );

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                $strError = 'Failed to download jobs from ' . $this->JobSiteName . ' jobs for search ' . $searchDetails->getUserSearchSiteRunKey() . '[URL=' . $uri . ", ERROR={$response->getReasonPhrase()}. Exception Details: ";
                throw new Exception($strError . $response->getBody());
            }

            $response_data = json_decode($response->getBody()->getContents(), true);

            return $response_data;

        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $arrJobsList
     * @return array
     */
    protected function remapJobItems($arrJobsList) {

        $ret = array();

        foreach ($arrJobsList as $jobItem) {

            array_replace_keys($jobItem, [
                'id' => 'JobSitePostId',
                'company' => 'Company',
                'title' => 'Title',
                'salary' => 'PayRange',
                'link' => 'Url',
                'type' => 'EmploymentType',
                'location' => 'Location',
                'updated' => 'UpdatedAt',
                'snippet' => 'Brief'
            ]);

            $ret[$jobItem['JobSitePostId']] = $jobItem;
        }
        return $ret;
    }
}

class PluginJoobla22 extends \JobScooper\SitePlugins\ApiPlugin
{
    private $_apiURI = "https://us.jooble.org/api/";
    protected $SiteReferenceKey = "joobla";

    private $searchJsonUrlFmt = null;
    private $currentJsonSearchDetails = null;
    private $lastResponseData = null;
    protected $JobListingsPerPage = 1000;
    protected $nTotalJobs = null;
    protected $additionalBitFlags = [C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED];

    protected $arrListingTagSetup = array(
//		'JobPostItem' => array('Selector' => 'ul.list-group li.list-group-item'),
//		'Title' => array('Selector' => 'h4.list-group-item-heading a'),
//		'Url' => array('Selector' => 'h4.list-group-item-heading a', 'Attribute' => 'href'),
//		'Location' => array('Selector' => 'ul li', 'Index' => 0),
//		'Department' => array('Selector' => 'ul li', 'Index' => 1),
    );

    public function __construct()
    {
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

            if(!is_empty_value($job->metadata) && count($job->metadata) >= 1 && !is_empty_value($job->metadata[0]->value)) {
                $item['Department'] = $job->metadata[0]->value;
            }

            if(!is_empty_value($job->metadata) && count($job->metadata) >= 2 && !is_empty_value($job->metadata[1]->value)) {
                $item['ExperienceType'] = $job->metadata[1]->value;
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


    private function callAPI() {
        $client = new GuzzleHttp\Client( [
        ]);
        $response = $client->post(
            $this->_apiURI,
            array(
                'body' => [
                    'location' => $this->currentJsonSearchDetails->getGeoLocationURLValue(),
                    'keyword' => $this->currentJsonSearchDetails->getKeywordURLValue()
                ]
            )
        );

        echo $response->getBody();
        $curl = new \JobScooper\Utils\CurlWrapper();

        $payload = array(
        );
        $result = $curl->cURL($this->_apiURI, $action='POST', $json=$payload);

        $key = "<YOUR_API_KEY>";

//create request object
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url."".$key);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{ "keywords": "it", "location": "Bern"}');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

// receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);
        curl_close ($ch);

    }

    public function parseTotalResultsCount(\JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML)
    {
        try {
            $retData = $this->getJsonResultsPage('jobs', "meta->total");
            $this->nTotalJobs = $retData['count'];
            return $this->nTotalJobs;
        } catch (Exception $ex) {
            handleException($ex);
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
                ;
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
        } catch (Exception $ex) {
            handleException($ex);
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
        LogMessage("Downloading JSON listing data from {$this->searchJsonUrlFmt} for {$this->JobSiteKey}...");
        $hostPageUri = $this->currentJsonSearchDetails->getSearchStartUrl();

        $ret = array();
        $respdata = $this->getJsonApiResult($this->searchJsonUrlFmt, $this->currentJsonSearchDetails, $hostPageUri);
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
            } catch (Exception $ex) {
                throw new Exception($respdata->error);
            }
        }

        return $ret;
    }

}
