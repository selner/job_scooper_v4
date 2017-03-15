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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(dirname(__FILE__)))); }

require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');
use JobApis\Jobs\Client\Queries\GovtQuery;
use JobApis\Jobs\Client\Queries\DiceQuery;
use JobApis\Jobs\Client\Queries\UsajobsQuery;
use JobApis\Jobs\Client\Providers\GovtProvider;
use JobApis\Jobs\Client\Providers\UsajobsProvider;
use JobApis\Jobs\Client\Providers\DiceProvider;
//
//
//class PluginGovtJobs extends ClassBaseJobsAPIPlugin
//{
//    protected $siteBaseURL = 'http://search.digitalgov.gov/developer/jobs.html';
//    protected $strBaseURLFormat = 'http://search.digitalgov.gov/developer/jobs.html';
//    protected $siteName = 'GovtJobs';
//    protected $nJobListingsPerPage = 100;
//
//
//    function getSearchJobsFromAPI($searchDetails)
//    {
//        $strKeywords = $this->getCombinedKeywordString($searchDetails['keyword_set']);
//
//        // Add parameters to the query via the constructor
//        $options = [
//            'query' => $strKeywords,
//            'size' => 100,
//        ];
//        $query = new GovtQuery($options);
//
//        $client = new GovtProvider($query);
//        $GLOBALS['logger']->logLine("Getting jobs from " . $query->getUrl() . "[". $searchDetails['name'] , \Scooper\C__DISPLAY_ITEM_DETAIL__);
//
//        // Get a Collection of Jobs
//        $apiJobs = $client->getJobs();
//        return $apiJobs->all();
//
//    }
//
//}


class PluginUSAJobs extends ClassBaseJobsAPIPlugin
{
    protected $siteBaseURL = 'http://search.digitalgov.gov/developer/jobs.html';
    protected $strBaseURLFormat = 'http://search.digitalgov.gov/developer/jobs.html';
    protected $siteName = 'USAJobs';
    protected $nJobListingsPerPage = 25;
    protected $typeLocationSearchNeeded = 'location-city-comma-state';

    function getSearchJobsFromAPI($searchDetails, $pageNumber = 1)
    {
        $strKeywords = $this->getCombinedKeywordString($searchDetails['keywords_array']);

        // Add parameters to the query via the constructor
        $options = [
            'AuthorizationKey' => $this->authorization_key,
            'Keyword' => $strKeywords,
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




class PluginDice extends ClassBaseJobsAPIPlugin
{
    protected $siteBaseURL = 'http://www.dice.com';
    protected $strBaseURLFormat = 'http://service.dice.com/api/rest/jobsearch/v1/simple.json?';
    protected $siteName = 'Dice';
//    protected $nJobListingsPerPage = 1000;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $regex_link_job_id = '/^.*\/job\/result\/([^\?]+).*/i';
    protected $nJobListingsPerPage = 50;

    // BUGBUG: API is unsupported and doesn't seem to work.  Should probably switch to website
    // https://www.dice.com/jobs/advancedResult.html?for_one=&for_all=head+of&for_exact=&for_none=&for_jt=&for_com=&for_loc=Seattle%2C+WA&sort=relevance&radius=0&searchid=9171364965913

    function getSearchJobsFromAPI($searchDetails, $pageNumber = 1)
    {
        $strKeywords = $this->getCombinedKeywordString($searchDetails['keywords_array']);

        $city = (array_key_exists('location-city-comma-nospace-statecode', $GLOBALS['USERDATA']['configuration_settings']['location_sets'][$searchDetails['location_set_key']])) ? $GLOBALS['USERDATA']['configuration_settings']['location_sets'][$searchDetails['location_set_key']]['location-city-comma-nospace-statecode'] : "";
        $state = (array_key_exists('location-statecode', $GLOBALS['USERDATA']['configuration_settings']['location_sets'][$searchDetails['location_set_key']])) ? $GLOBALS['USERDATA']['configuration_settings']['location_sets'][$searchDetails['location_set_key']]['location-statecode'] : "";
        $country = (array_key_exists('location-countrycode', $GLOBALS['USERDATA']['configuration_settings']['location_sets'][$searchDetails['location_set_key']])) ? $GLOBALS['USERDATA']['configuration_settings']['location_sets'][$searchDetails['location_set_key']]['location-countrycode'] : "";
        

        // Add parameters to the query via the constructor
        $options = [
            'text' => $strKeywords,
            'page' => $pageNumber,
            'pgcnt' => $this->nJobListingsPerPage,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'age' => $GLOBALS['USERDATA']['configuration_settings']['number_days'],
            'sort' => 1 // sort by date
        ];
        $query = new DiceQuery($options);
        $client = new DiceProvider($query);

        $GLOBALS['logger']->logLine("Getting jobs from " . $query->getUrl() . " for search ". $searchDetails['key'] , \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $apiJobs = null;
        // Get a Collection of Jobs
        try {
            $apiJobs = $client->getJobs();
        } catch (Exception $ex) {
            handleException($ex, null, false);
            $apiJobs = null;
        }

        if(!is_null($apiJobs))
        {
            $jobsForPage = $apiJobs->all();

            if($jobsForPage != null)
            {
                foreach($jobsForPage as $job)
                {
                    $id = $this->getIDFromLink($this->regex_link_job_id, $job->url);
                    $job->setSourceId( $id );
                }
            }
            return $jobsForPage;
        }
    }

}




