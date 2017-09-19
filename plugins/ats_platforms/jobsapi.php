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
require_once dirname(dirname(dirname(__FILE__)))."/bootstrap.php";
use JobApis\Jobs\Client\Queries\UsajobsQuery;
use JobApis\Jobs\Client\Providers\UsajobsProvider;

class PluginUSAJobs extends ClassBaseJobsAPIPlugin
{
    protected $siteBaseURL = 'http://search.digitalgov.gov/developer/jobs.html';
    protected $strBaseURLFormat = 'https://api.usa.gov/jobs/search.json?query=in+***LOCATION***';
    protected $siteName = 'USAJobs';
    protected $nJobListingsPerPage = 25;
    protected $typeLocationSearchNeeded = 'location-city-comma-state';
    protected $authorization_key = null;
    
    function getSearchJobsFromAPI($searchDetails, $pageNumber = 1)
    {
        // Add parameters to the query via the constructor
        $options = [
            'AuthorizationKey' => $this->authorization_key,

            'LocationName' => $searchDetails['location_search_value'],
            'Page' => $pageNumber
        ];
        $query = new UsajobsQuery($options);
        $client = new UsajobsProvider($query);
        $GLOBALS['logger']->logLine("Getting jobs from " . $query->getUrl() . "[". $searchDetails['key'] , \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $apiJobs = $client->getJobs();
        return $apiJobs->all();

    }
}



