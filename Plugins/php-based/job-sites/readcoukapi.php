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
use Httpful\Request;

/**
 * Class AbstractReedApi
 *
 *       Used by dice.json plugin configuration to override single method
 */
class PluginReed extends \JobScooper\SitePlugins\ApiPlugin
{
    protected $SearchUrlFormat = "https://www.reed.co.uk/api/1.0/search?keywords=***KEYWORDS***&location=***LOCATION:{Place}***&distancefromlocation=15&***ITEM_NUMBER:&resultsToSkip={item_index}***";
    protected $JobSiteName = 'Reed';
    protected $CountryCodes = array("UK");
    protected $_apikey = null;
    protected $additionalBitFlags = array();

    public function __construct($strBaseDir = null)
    {
        $this->additionalBitFlags[] = C__JOB_PAGECOUNT_NOTAPPLICABLE;
        $this->pluginResultsType = C__JOB_SEARCH_RESULTS_TYPE_RESTSAPI__;

        parent::__construct();
    }

    /**
     * @param $searchDetails
     */
    protected function getSearchJobsFromAPI($searchDetails)
    {
        $uri = $searchDetails->getSearchStartUrl();

        $settings = \JobScooper\Utils\Settings::getValue('plugin_settings.' . $this->JobSiteKey);
        if(!is_empty_value($settings) && array_key_exists("apikey", $settings)) {
            $key = $settings['apikey'];
        }
        else
        {
            throw new \InvalidArgumentException("{$this->JobSiteName} requires the API key to be set in the configuration file.");
        }

        // Create the template
        $template = Request::init()
            ->method(\Httpful\Http::GET)        // Alternative to Request::post
            ->withoutStrictSsl()        // Ease up on some of the SSL checks
            ->expectsJson()
            ->authenticateWith($key, '');

        // Set it as a template
        Request::ini($template);

        // This new request will have all the settings
        // of our template by default.  We can override
        // any of these settings by settings them on this
        // new instance as we've done with expected type.
        $r = Request::get($uri)->send();
        if($r->hasBody()) {
            $totalJobCount = $r->body->totalResults;
            if($totalJobCount > $this->nMaxJobsToReturn) {
                $totalJobCount = $this->nMaxJobsToReturn;
            }
            $jobs = array();
            $countJobs = 0;
            if(!is_empty_value($totalJobCount) && $countJobs < $totalJobCount-1) {
                while($countJobs < $totalJobCount-1 && $r->hasBody()) {
                    $uri = $this->setResultPageUrl($searchDetails, null, $countJobs);
                    $r = Request::get($uri)->send();
                    if ($r->hasBody()) {
                        $newJobs = $this->_getJobsFromResponse($r);
                        $jobs = $jobs + $newJobs;
                        $countJobs = count($jobs);
                        $this->log("... downloaded {$countJobs} of {$totalJobCount} jobs from {$this->JobSiteName}..." . PHP_EOL);
                        $newJobs = [];
                    }
                    else {
                        break;
                    }
                }
            }
        }
        return $jobs;
    }

    private function _getJobsFromResponse(\Httpful\Response $resp) {
        $retJobs = [];

        if($resp->hasBody() && !$resp->hasErrors()) {
            $results = $resp->body->results;
            if(!is_empty_value($results) && count($results) > 0) {

                foreach($results as $item) {
                    $arrItem = objectToArray($item);
                    $job = array_replace_keys($arrItem, [
                        'jobId' => 'JobSitePostId',
                        'employerName' => 'Company',
                        'jobTitle' => 'Title',
                        'date' => 'PostedAt',
                        'jobUrl' => 'Url',
                        'locationName' => 'Location'
                    ]);

                    $job['PostedAt'] = DateTime::createFromFormat('d/m/Y', $job['PostedAt'])->format("m/d/Y");

                    $retJobs[$job['JobSitePostId']] = $job;
                }
            }
        }

        return $retJobs;

    }
}
