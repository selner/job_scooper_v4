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

namespace JobScooper\Builders;

use JobScooper\DataAccess\Map\UserSearchPairTableMap;
use JobScooper\DataAccess\User;
use JobScooper\DataAccess\UserSearchSiteRunQuery;
use JobScooper\DataAccess\UserSearchSiteRun;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Class SearchBuilder
 * @package JobScooper\Builders
 */
class SearchBuilder
{


	/**
	 * @throws \Propel\Runtime\Exception\PropelException
	 * @throws \Exception
	 */
	public function createSearchesForUser(User $user)
    {

	    $sites = JobSitePluginBuilder::getIncludedJobSites();

	    $ignoreRecent = filter_var(getConfigurationSetting('command_line_args.ignore_recent'), FILTER_VALIDATE_BOOLEAN);

	    if($ignoreRecent !== true)
		    $this->_filterRecentlyRunJobSites($sites, $user);

	    $countryCodes = array();
	    $searchLoc = $user->getSearchGeoLocations();
	    foreach($searchLoc as $loc)
	    {
		    $countryCodes[] = $loc->getCountryCode();
	    }
	    JobSitePluginBuilder::filterJobSitesByCountryCodes($sites, $countryCodes);

	    //
	    // Create searches needed to run all the keyword sets
	    //
	    $searchesByJobSite = $this->_generateUserSearchSiteRuns($sites, $user);

	    if($ignoreRecent !== true)
			$this->_filterRecentlyRunUserSearchRuns($searchesByJobSite, $user);

	    return $searchesByJobSite;

    }

	/**
	 * @param                             $sites
	 * @param \JobScooper\DataAccess\User $user
	 *
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	private function _filterRecentlyRunJobSites(&$sites, User $user)
	{
		$sitesToSkip = array();
		$siteWaitCutOffTime = date_sub(new \DateTime(), date_interval_create_from_date_string('23 hours'));

		$completedSitesAllOnly = UserSearchSiteRunQuery::create()
			->useJobSiteFromUSSRQuery()
			->filterByResultsFilterType("all-only")
			->withColumn("JobSiteFromUSSR.ResultsFilterType", "ResultsFilterType")
			->endUse()
			->addAsColumn('LastCompleted', 'MAX(user_search_site_run.date_ended)')
			->select(array('JobSiteKey', 'ResultsFilterType', 'LastCompleted'))
			->filterByRunResultCode(array("successful", "failed"), Criteria::IN)
			->groupBy(array("JobSiteKey", "ResultsFilterType"))
			->find()
			->getData();

		if (!empty($completedSitesAllOnly)) {
			$sitesToSkip = array_merge($sitesToSkip, $completedSitesAllOnly);
		}

		$searchLocations = $user->getSearchGeoLocations();

		foreach ($searchLocations as $location) {
			$sitesAllLocationOnly = UserSearchSiteRunQuery::create()
				->useJobSiteFromUSSRQuery()
				->filterByResultsFilterType("all-by-location")
				->withColumn("JobSiteFromUSSR.ResultsFilterType", "ResultsFilterType")
				->endUse()
				->addAsColumn('LastCompleted', 'MAX(user_search_site_run.date_ended)')
				->select(array('JobSiteKey', 'ResultsFilterType', 'LastCompleted'))
				->filterByRunResultCode(array("successful", "failed"))
				->useUserSearchPairFromUSSRQuery()
				->filterByGeoLocationFromUS($location)
				->endUse()
				->groupBy(array("JobSiteKey", "ResultsFilterType"))
				->find()
				->getData();

			if (!empty($sitesAllLocationOnly)) {
				$sitesToSkip = array_merge($sitesToSkip, $sitesAllLocationOnly);
			}
		}
		$sitesToSkip = array_column($sitesToSkip, "LastCompleted", "JobSiteKey");

		if(!empty($sitesToSkip)) {
			// Filter sites that can be skipped by date.
			//
			// Remove any that ran before the cache cut off time, not since that time.
			// We are left with only those we should skip, aka the ones that
			// ran after our cutoff time
			//
			foreach ($sitesToSkip as $key => $result) {
				if (new \DateTime($result) <= $siteWaitCutOffTime)
					unset($sitesToSkip[$key]);
			}

			JobSitePluginBuilder::setSitesAsExcluded($sitesToSkip);
			$sites = array_diff_key($sites, $sitesToSkip);
			JobSitePluginBuilder::setIncludedJobSites($sites);

			$skipTheseSearches = array_intersect_key($sites, $sitesToSkip);
			foreach ($skipTheseSearches as $siteKey => $siteSearches) {
				if (!empty($siteSearches))
					foreach ($siteSearches as $search) {
						$search->setRunResultCode("skipped");
						$search->save();
					}
			}

			if (!empty($skipTheseSearches))
				LogMessage("Skipping the following sites because they have run, successfully or not, since " . $siteWaitCutOffTime->format("Y-m-d H:i") . ": " . getArrayDebugOutput($skipTheseSearches));
		}
	}

	/**
	 * @param                             $sites
	 * @param \JobScooper\DataAccess\User $user
	 *
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	private function _filterRecentlyRunUserSearchRuns(array &$searches, User $user)
	{
		$skipTheseSearches = array();
		$siteWaitCutOffTime = date_sub(new \DateTime(), date_interval_create_from_date_string('23 hours'));

		$recordsToSkip = UserSearchSiteRunQuery::create()
			->addAsColumn('LastCompleted', 'MAX(user_search_site_run.date_ended)')
			->select(array('JobSiteKey', 'UserSearchPairId', 'LastCompleted'))
			->filterByRunResultCode(array("successful", "failed"))
			->groupBy(array("JobSiteKey", 'UserSearchPairId'))
			->find()
			->getData();

		if(!empty($recordsToSkip)) {
			// Filter sites that can be skipped by date.
			//
			// Remove any that ran before the cache cut off time, not since that time.
			// We are left with only those we should skip, aka the ones that
			// ran after our cutoff time
			//
			$searchPairsToSkip= array();
			foreach ($recordsToSkip as $search) {
				if (new \DateTime($search['LastCompleted']) >= $siteWaitCutOffTime)
				{
					if(!array_key_exists($search['JobSiteKey'], $searchPairsToSkip))
						$searchPairsToSkip[$search['JobSiteKey']] = array();
					$searchPairsToSkip[$search['JobSiteKey']][$search['UserSearchPairId']] = $search['UserSearchPairId'];
				}
			}

			foreach(array_keys($searches) as $siteKey) {
				if (!empty($searches[$siteKey])) {
					foreach ($searches[$siteKey] as $searchKey => $search) {
						if (!empty($searchPairsToSkip[$siteKey]) && array_key_exists($search->getUserSearchPairId(), $searchPairsToSkip[$siteKey])) {
							$search->setRunResultCode("skipped");
							$search->save();
							unset($searches[$siteKey][$searchKey]);
							$skipTheseSearches[] = $searchKey;
						}
					}
				}
			}

			if (!empty($skipTheseSearches))
				LogMessage("Skipping the following searches because they have run since " . $siteWaitCutOffTime->format("Y-m-d H:i") . ": " . getArrayDebugOutput($skipTheseSearches));

		}
	}


	/**
	 * @param                             $sites
	 * @param \JobScooper\DataAccess\User $user
	 *
	 * @return array
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	private function _generateUserSearchSiteRuns($sites, User $user)
    {
        //
        // let's start with the searches specified with the details in the the config.ini
        //
	    $userSearchPairs = $user->getActiveUserSearchPairs();
	    if (empty($userSearchPairs) || empty($sites))
		    return array();

	    $nKeywords = count($user->getSearchKeywords());
	    $nLocations = countAssociativeArrayValues($user->getSearchGeoLocations());
		$nTotalPairs = countAssociativeArrayValues($userSearchPairs);
	    $nTotalSearches = $nKeywords * $nLocations * count($sites);

        LogMessage(" Creating search runs for {$nTotalPairs} search pairs X " . count($sites) . " jobsites = up to {$nTotalSearches} total searches, from {$nKeywords} search keywords and {$nLocations} search locations.");

        $searchRuns = array();
	    $ntotalSearchRuns = 0;

        foreach($sites as $jobsiteKey => $site)
        {


            foreach($userSearchPairs as $searchPair)
            {
            	$geoloc = $searchPair->getGeoLocationFromUS();
            	$ccSearch = $geoloc->getCountryCode();
            	$ccJobSite = $site->getSupportedCountryCodes();
	            $matches = null;
	            $ccOverlaps= array_intersect(array($ccSearch), $ccJobSite);
	            if(!empty($ccOverlaps)) {
		            $searchrun = new UserSearchSiteRun();
		            $searchrun->setUserSearchPairFromUSSR($searchPair);
	                $searchrun->setJobSiteKey($site);
	                $searchrun->setAppRunId(getConfigurationSetting('app_run_id'));
	                $searchrun->setStartedAt(time());
	                $searchrun->save();

	                if(!array_key_exists($jobsiteKey, $searchRuns))
	                	$searchRuns[$jobsiteKey] = array();
		            $searchRuns[$jobsiteKey][$searchrun->getUserSearchSiteRunKey()] = $searchrun;
		            $ntotalSearchRuns += 1;
	            }
	            else
		            LogDebug("JobSite {$jobsiteKey} supported countries [" . join("|", $ccJobSite) . "] does not include the search's country [{$ccSearch}].  Skipping search.");


            }
        }

	    LogMessage(" Generated {$ntotalSearchRuns} total search runs to process.");
        return $searchRuns;
    }


}
