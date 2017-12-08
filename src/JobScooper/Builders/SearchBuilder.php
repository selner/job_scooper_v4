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

namespace JobScooper\Builders;

use JobScooper\DataAccess\User;
use JobScooper\DataAccess\UserSearchQuery;
use JobScooper\DataAccess\UserSearchSiteRunQuery;
use JobScooper\DataAccess\UserSearchSiteRun;

class SearchBuilder
{
    public function initializeSearches()
    {

        //
        // Create searches needed to run all the keyword sets
        //
	    JobSitePluginBuilder::filterJobSitesByCountryCodes(getConfigurationSetting("country_codes"));

        $this->_setUserSearches();

	    $this->_generateUserSearchSiteRuns();

	    $this->filterSearchRunsThatShouldNotRunYet();

	    $this->_optimizeSearchRunOrder();
    }

    private function _optimizeSearchRunOrder()
    {
        $searches = getConfigurationSetting('user_search_site_runs');
        if(empty($searches))
            return;

        foreach(JobSitePluginBuilder::getIncludedJobSites() as $sitekey => $site) {
            $sitesearches = $searches[$sitekey];
            uasort($sitesearches, function ($a, $b) {

//                if ($a->getJobSiteKey() == $b->getJobSiteKey()) {
//                    if ($a->getLastCompletedAt() == $b->getLastCompletedAt()) {
//                        return 0;
//                    }
//                    return ($a->getLastCompletedAt() < $b->getLastCompletedAt()) ? -1 : 1;
//                } else
                    return ($a->getJobSiteKey() < $b->getJobSiteKey()) ? -1 : 1;
            });
            $searches[$sitekey] = $sitesearches;
        }

        setConfigurationSetting("user_search_site_runs", $searches);
    }


    private function _setUserSearches()
    {

	    $arrLocations = getConfigurationSetting('search_locations');
	    if (empty($arrLocations)) {
		    LogLine("No search locations have been set. Unable to setup a user search run.", C__DISPLAY_WARNING__);

		    return null;
	    }

	    $keywordSets = getConfigurationSetting('user_keyword_sets');
	    if (empty($keywordSets)) {
		    LogLine("No user keyword sets have been configured. Unable to setup a user search run.", C__DISPLAY_WARNING__);

		    return null;
	    }

	    $userSearches = array();
	    foreach ($arrLocations as $lockey => $searchLoc) {
		    LogLine("Adding user searches in " . $searchLoc->getDisplayName() . " for user's keywords sets", \C__DISPLAY_ITEM_START__);

		    foreach ($keywordSets as $setKey => $keywordSet) {

			    $user_search = UserSearchQuery::create()
				    ->filterByUserKeywordSetFromUS($keywordSet)
				    ->filterByUserFromUS(User::getCurrentUser())
				    ->filterByGeoLocationId($searchLoc->getGeoLocationId())
				    ->findOneOrCreate();

			    $user_search->setUserKeywordSetFromUS($keywordSet);
			    $user_search->setUserFromUS(User::getCurrentUser());
			    $user_search->setGeoLocationFromUS($searchLoc);
			    $user_search->save();

			    $userSearches[$user_search->getUserSearchKey()] = $user_search;
		    }
	    }

	    if (empty($userSearches)) {
		    LogLine("Could not create user searches for the given user keyword sets and geolocations.  Cannot continue.");

		    return null;
	    }
	    setConfigurationSetting("user_searches", $userSearches);
	    LogLine("Generated " . count($userSearches) . " user searches.");

    }

    private function filterSearchRunsThatShouldNotRunYet()
    {
        $searches = getConfigurationSetting("SEARCHES_TO_RUN");

	    if (!empty($searches) && is_array($searches)) {
            foreach (array_keys($searches) as $searchKey) {
                $search = $searches[$searchKey];
                $lastCompleted = $search->getLastCompletedAt();
                $cacheCutOffTime = date_sub(new \DateTime(), date_interval_create_from_date_string('18 hours'));
                if (!empty($lastCompleted) && $cacheCutOffTime >= $lastCompleted) {
                    LogLine("... skipping search " . $searchKey . ". It has run too recently. (Last successful run = " . $lastCompleted->format("Y-m-d H:i") . ")", C__DISPLAY_ITEM_DETAIL__);
                    unset($searches[$searchKey]);
                }
            }
            LogLine("... " . count($searches) . " now remain to be run after filtering out recent search runs.", C__DISPLAY_ITEM_DETAIL__);
            setConfigurationSetting("SEARCHES_TO_RUN", $searches);
        }
    }


    private function _generateUserSearchSiteRuns()
    {
        //
        // let's start with the searches specified with the details in the the config.ini
        //
        $userSearches = getConfigurationSetting('user_searches');
        $includedSites = JobSitePluginBuilder::getIncludedJobSites();
	    if (empty($userSearches) || empty($includedSites))
		    return;

        LogLine(" Creating search runs for " . strval(count($userSearches)) . " user searches across " . count($includedSites) . " jobsites.", \C__DISPLAY_ITEM_DETAIL__);

        $user = User::getCurrentUser();
        $searchRuns = array();

        foreach($includedSites as $jobsiteKey => $site)
        {
        	$searchRuns[$jobsiteKey] = array();

            foreach($userSearches as $search)
            {
//            	$searchRun = UserSearchSiteRunQuery::create()
//		            ->filterByUser($user)
//		            ->filterByUserSearch($search)
//		            ->filterByJobSiteKey()
//		            ->filterByAppRunId($apprun)
//

                $searchrun = new UserSearchSiteRun();
	            $searchrun->setUserFromUSSR(User::getCurrentUser());
	            $searchrun->setUserSearchFromUSSR($search);
                $searchrun->setJobSiteKey($site);
                $searchrun->setAppRunId(getConfigurationSetting('app_run_id'));
                $searchrun->setStartedAt(time());
                $searchrun->save();

                $searchRuns[$jobsiteKey][$searchrun->getUserSearchSiteRunKey()] = $searchrun;

            }
        }

	    setConfigurationSetting("user_search_site_runs", $searchRuns);
    }


}
