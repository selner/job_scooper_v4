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
use JobScooper\DataAccess\UserSearchRun;
use JobScooper\DataAccess\UserSearchRunQuery;

class SearchBuilder
{

    function __construct()
    {
        $GLOBALS['USERDATA']['configuration_settings']['searches'] = array();

    }

    public function initializeSearches()
    {

        if(count($GLOBALS['USERDATA']['configuration_settings']['included_sites']) == 0)
        {
            LogError("No plugins could be found for the user's searches.  Aborting.");
            return;
        }

        //
        // Create searches needed to run all the keyword sets
        //
        $this->_addSearchesToUser_();

        $this->_createSearchRunInstances();

        $this->filterSearchesThatShouldNotRunYet();

        $this->_filterSearchesByCountry();

        $this->setSearchesByJobSiteOrderedByLastComplete();
    }

    private function setSearchesByJobSiteOrderedByLastComplete()
    {
        $searches = getConfigurationSettings('SEARCHES_TO_RUN');
        if(empty($searches))
            return;

        foreach(getConfigurationSettings('included_sites') as $site) {
            $sitekey = $site->getJobSiteKey();
            $sitesearches = array_filter(array_map(function($n) use ($sitekey) { if($n->getJobSiteKey() === $sitekey) return $n; }, $searches));
            uasort($sitesearches, function ($a, $b) {

                if ($a->getJobSiteKey() == $b->getJobSiteKey()) {
                    if ($a->getLastCompletedAt() == $b->getLastCompletedAt()) {
                        return 0;
                    }
                    return ($a->getLastCompletedAt() < $b->getLastCompletedAt()) ? -1 : 1;
                } else
                    return ($a->getJobSiteKey() < $b->getJobSiteKey()) ? -1 : 1;
            });
            $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'][$sitekey] = $sitesearches;
        }
    }


    private function _addSearchesToUser_()
    {

        $arrLocations = getConfigurationSettings('search_locations');
        if(empty($arrLocations)) {
            LogLine("No search locations have been set.  Cannot create searches.", C__DISPLAY_WARNING__);
            return null;
        }

        foreach($arrLocations as $searchLoc) {

            // If the keyword settings scope is all sites, then create a search for every possible site
            // so that it runs with the keywords settings if it was included_<site> = true
            //
            if (isset($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) && count($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) > 0) {
                LogLine("Adding user searches in " . $searchLoc->getDisplayName() . " for user's keywords sets", \C__DISPLAY_ITEM_START__);

                foreach ($GLOBALS['USERDATA']['configuration_settings']['keyword_sets'] as $keywordSet) {
                    $arrKeys = array_keys($keywordSet['keywords_array']);
                    foreach ($arrKeys as $kwdkey) {
                        $search_kwds = $keywordSet['keywords_array'][$kwdkey];
                        if(!is_array($search_kwds))
                            $search_kwds = preg_split("/\s+/", $search_kwds);
                        $user_search = UserSearchQuery::create()
                            ->filterByKeywords($search_kwds)
                            ->filterByUser(User::getCurrentUser())
                            ->filterByGeoLocation($searchLoc)
                            ->findOneOrCreate();


                        $user_search->setUser(User::getCurrentUser());
                        $user_search->setSearchKeyFromConfig($keywordSet['key'] . "_" . cleanupSlugPart($kwdkey));
                        $user_search->setKeywords($search_kwds);
                        $user_search->setGeoLocation($searchLoc);
                        $user_search->save();

                        $GLOBALS['USERDATA']['configuration_settings']['user_searches'][$user_search->getUserSearchKey()] = $user_search;
                    }
                }
            }
        }

        LogLine("Added or updated " . $GLOBALS['USERDATA']['configuration_settings']['user_searches'] . " user searches for this run...");
    }

    private function filterSearchesThatShouldNotRunYet()
    {
        $searches = getConfigurationSettings('SEARCHES_TO_RUN');
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
            $GLOBALS['USERDATA']['configuration_settings']['SEARCHES_TO_RUN'] = $searches;
        }
    }

    private function _filterSearchesByCountry()
    {
        $jobsites = getConfigurationSettings('included_sites');
        $searches = getConfigurationSettings('SEARCHES_TO_RUN');
        if(!empty($searches))
        {
            foreach($searches as $search)
            {
                $jobsiteKey = $search->getJobSiteKey();
                $site = $jobsites[$jobsiteKey];
                $plugin = $site->getPluginObject();
                if(!empty($plugin))
                {
                    $pluginCountries = $plugin->getSupportedCountryCodes();
                    if (!is_null($pluginCountries)) {
                        $matchedCountries = array_intersect($pluginCountries, $GLOBALS['USERDATA']['configuration_settings']['country_codes']);
                        if ($matchedCountries === false || count($matchedCountries) == 0) {
                            LogDebug("Excluding " . $jobsiteKey . " because it does not support any of the country codes required for the user's searches (" . getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['country_codes']));
                            unset($GLOBALS['USERDATA']['configuration_settings']['included_sites'][$jobsiteKey]);
                            continue;
                        }
                    }
                }
            }
        }
    }


    private function _createSearchRunInstances()
    {
        //
        // let's start with the searches specified with the details in the the config.ini
        //
        $userSearches = getConfigurationSettings('user_searches');
        $includedSites = getConfigurationSettings("included_sites");
        LogLine(" Creating search instances for this run from " . strval(count($userSearches)) . " user searches.", \C__DISPLAY_ITEM_DETAIL__);
        $GLOBALS['USERDATA']['configuration_settings']['SEARCHES_TO_RUN'] = array();

        if (empty($userSearches) || empty($includedSites))
            return;

        foreach($includedSites as $site)
        {
            foreach($userSearches as $search) {
                $searchrun = new UserSearchRun();
                $searchrun->setUserSearch($search);
                $searchrun->setJobSiteKey($site);
                $searchrun->setAppRunId(getConfigurationSettings('app_run_id'));
                $searchrun->setStartedAt(time());
                $searchrun->save();

                $GLOBALS['USERDATA']['configuration_settings']['SEARCHES_TO_RUN'][$searchrun->getUserSearchRunKey()] = $searchrun;

            }
        }
    }


}
