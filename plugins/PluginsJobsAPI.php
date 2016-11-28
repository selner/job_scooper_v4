<?php

/**
 * Copyright 2014-16 Bryan Selner
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
if (!strlen(__ROOT__) > 0) {
    define('__ROOT__', dirname(dirname(__FILE__)));
}
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');
use JobApis\Jobs\Client\Queries\GovtQuery;
use JobApis\Jobs\Client\Queries\DiceQuery;
use JobApis\Jobs\Client\Queries\UsajobsQuery;
use JobApis\Jobs\Client\Providers\GovtProvider;
use JobApis\Jobs\Client\Providers\UsajobsProvider;
use JobApis\Jobs\Client\Providers\DiceProvider;


class PluginGovtJobs extends ClassBaseJobsAPIPlugin
{
    protected $siteBaseURL = 'http://search.digitalgov.gov/developer/jobs.html';
    protected $strBaseURLFormat = 'http://search.digitalgov.gov/developer/jobs.html';
    protected $siteName = 'GovtJobs';
    protected $nJobListingsPerPage = 20;


    function getSearchJobsFromAPI($searchDetails)
    {
        $strKeywords = $this->getCombinedKeywordString($searchDetails['keyword_set']);

        // Add parameters to the query via the constructor
        $options = [
            'query' => $strKeywords
        ];
        $query = new GovtQuery($options);
        $query->set('size', '1000');

        $client = new GovtProvider($query);
        $GLOBALS['logger']->logLine("Getting jobs from " . $query->getUrl() . "[". $searchDetails['name'] , \Scooper\C__DISPLAY_ITEM_DETAIL__);

        // Get a Collection of Jobs
        $apiJobs = $client->getJobs();
        return $apiJobs->all();

    }

}


class PluginUSAJobs extends ClassBaseJobsAPIPlugin
{
    protected $siteBaseURL = 'http://search.digitalgov.gov/developer/jobs.html';
    protected $strBaseURLFormat = 'http://search.digitalgov.gov/developer/jobs.html';
    protected $siteName = 'USAJobs';
    protected $nJobListingsPerPage = 25;
    protected $typeLocationSearchNeeded = 'location-city-comma-state';

    function getSearchJobsFromAPI($searchDetails, $pageNumber = 1)
    {
        $strKeywords = $this->getCombinedKeywordString($searchDetails['keyword_set']);


        // Add parameters to the query via the constructor
        $options = [
            'AuthorizationKey' => $this->authorization_key,
            'Keyword' => $strKeywords,
            'LocationName' => $searchDetails['location_set'][$this->typeLocationSearchNeeded],
            'Page' => $pageNumber
        ];
        $query = new UsajobsQuery($options);
        $client = new UsajobsProvider($query);
        $GLOBALS['logger']->logLine("Getting jobs from " . $query->getUrl() . "[". $searchDetails['name'] , \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $apiJobs = $client->getJobs();
        return $apiJobs->all();

    }
}




class PluginDice extends ClassBaseJobsAPIPlugin
{
    protected $siteBaseURL = 'http://www.dice.com';
    protected $strBaseURLFormat = 'http://service.dice.com/api/rest/jobsearch/v1/simple.json?text=java&city=New+York,+NY&pgcnt=20';
    protected $siteName = 'Dice';
//    protected $nJobListingsPerPage = 1000;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $regex_link_job_id = '/^.*\/job\/result\/([^\/]+)\/.*/i';



    function getSearchJobsFromAPI($searchDetails, $pageNumber = 1)
    {
        $strKeywords = $this->getCombinedKeywordString($searchDetails['keyword_set']);

        // Add parameters to the query via the constructor
        $options = [
            'text' => $strKeywords,
//            'page' => $pageNumber,
//            'pgcnt' => 1000
//            'city' => $searchDetails['location']
        ];
        $query = new DiceQuery($options);
        $client = new DiceProvider($query);

        $GLOBALS['logger']->logLine("Getting jobs from " . $query->getUrl() . " for search ". $searchDetails['name'] , \Scooper\C__DISPLAY_ITEM_DETAIL__);

        // Get a Collection of Jobs
        $apiJobs = $client->getJobs();
        $retJobs = [];
        $jobsForPage = $apiJobs->all();
        while($jobsForPage != null)
        {
            foreach($jobsForPage as $job)
            {
                $id = $this->getIDFromLink($this->regex_link_job_id, $job->url);
                $job->setSourceId( $id );
                $strCurrentJobIndex = getArrayKeyValueForJob($job);
                $retJobs[$strCurrentJobIndex] = $job;
            }
        }
        return $retJobs;
    }

}




