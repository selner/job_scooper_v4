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

use JobApis\Jobs\Client\Queries\UsajobsQuery;
use JobApis\Jobs\Client\Providers\UsajobsProvider;
use \JobScooper\SitePlugins\ApiPlugin;

class PluginUSAJobs extends \JobScooper\SitePlugins\Base\SitePlugin
{
    protected $JobPostingBaseUrl = 'http://search.digitalgov.gov/developer/jobs.html';
    protected $SearchUrlFormat = 'https://api.usa.gov/jobs/search.json?query=in+***LOCATION***&keyword=***KEYWORDS***';
    protected $JobSiteName = 'USAJobs';
    protected $JobListingsPerPage = 500;
    protected $LocationType = 'location-city-comma-state';
    protected $JobMapping = Array(
        'sourceId' => 'JobSitePostId',
        'company' => 'Company',
        'title' => 'Title',
        'posted-date' => 'PostedAt',
        'PositionURI' => 'Url',
        'locationName' => 'Location'
    );

    public function __construct($strBaseDir = null)
    {

        $this->pluginResultsType = C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__;
        $this->additionalBitFlags = [!C__JOB_LOCATION_REQUIRES_LOWERCASE];

        parent::__construct($strBaseDir);

    }

    /**
     * @param     $searchDetails
     * @param int $pageNumber
     *
     * @return array|null
     * @throws \JobApis\Jobs\Client\Exceptions\MissingParameterException
     */
    public function getSearchJobsFromAPI($searchDetails, $pageNumber = 1)
    {
        // Add parameters to the query via the constructor
        $options = [
            'AuthorizationKey' => $this->_otherPluginSettings['authorization_key'],
            'LocationName' => $searchDetails->getGeoLocationURLValue("{Place} {Region}"),
            'SortField' => 'opendate',
            'Keyword' => $searchDetails->getKeywordURLValue(),
            'ResultsPerPage' => $this->JobListingsPerPage
        ];

        $firstRun = TRUE;
        $nPage = 1;
        $apiJobs = ['items' => null ];

        $jobresults = [];
        $retJobs = [];
        $nReturnJobsInPage = 0;
        while ($firstRun || $this->JobListingsPerPage <= $nReturnJobsInPage) {
            $firstRun = FALSE;

            $qopts = array_copy($options);
            $qopts['Page'] = $nPage;
            $nPage = $nPage + 1;

            $query = new UsajobsQuery($qopts);

            $client = new UsajobsProvider($query);
            LogMessage("Getting jobs from " . $query->getUrl() . "[". $searchDetails->getUserSearchSiteRunKey());
            $apiJobs = $client->getJobs();

            try {
                $nReturnJobsInPage = count($apiJobs->all());
                $retJobs = array_replace($retJobs, $apiJobs->all());

            } catch (Exception $ex) {
                handleThrowable($ex);
            }

        }

        return $retJobs;
    }
}
