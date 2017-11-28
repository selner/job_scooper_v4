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


use JobScooper\DataAccess\UserSearchRun;

class SearchBuilder
{

    function __construct()
    {
        $GLOBALS['USERDATA']['configuration_settings']['searches'] = array();

    }

    public function initializeSearches()
    {
        $this->_filterSearchesByCountry();

        if(count($GLOBALS['USERDATA']['configuration_settings']['included_sites']) == 0)
        {
            LogError("No plugins could be found for the user's searches.  Aborting.");
            return;
        }

        //
        // Create searches needed to run all the keyword sets
        //
        $this->_addSearchesForKeywordSets_();

        $this->filterSearchesThatShouldNotRunYet();

        $this->orderSearchesByLastRunDate();

        //
        // Finally create the instances of user search runs
        // that we will use during the run
        //
        $this->_createSearchInstancesForRun();

    }

    private function orderSearchesByLastRunDate()
    {
        if(empty($GLOBALS['USERDATA']['configuration_settings']['searches'])) 
            return;
        
        $arrUserSearches = $GLOBALS['USERDATA']['configuration_settings']['searches'];
        uasort($arrUserSearches, function($a, $b) {

            if ($a->getLastRunAt() == $b->getLastRunAt()) {
                return 0;
            }
            return ($a->getLastRunAt() < $b->getLastRunAt()) ? -1 : 1;
        });

        $GLOBALS['USERDATA']['configuration_settings']['searches'] = $arrUserSearches;
    }

    private function _addSearchesForKeywordSets_()
    {
        $arrSearchesPreLocation = array();
        //
        // explode any keyword sets we loaded into separate searches
        //
        // If the keyword settings scope is all sites, then create a search for every possible site
        // so that it runs with the keywords settings if it was included_<site> = true
        //
        if (isset($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) && count($GLOBALS['USERDATA']['configuration_settings']['keyword_sets']) > 0) {
            if (isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Adding new searches for user's keyword sets ", \C__DISPLAY_ITEM_START__);

            foreach ($GLOBALS['USERDATA']['configuration_settings']['keyword_sets'] as $keywordSet) {
                $arrSkippedPlugins = null;
                if (isset($GLOBALS['USERDATA']['configuration_settings']['included_sites']) && count($GLOBALS['USERDATA']['configuration_settings']['included_sites']) > 0)
                {
                    foreach ($GLOBALS['USERDATA']['configuration_settings']['included_sites'] as $siteToSearch)
                    {
                        LogLine("... configuring searches for " . $keywordSet['key'] . " keyword set on " . $siteToSearch, \C__DISPLAY_ITEM_DETAIL__);
                        $plugin = getPluginObjectForJobSite($siteToSearch);
                        $searchKey = cleanupSlugPart($keywordSet['key']);
                        if ($plugin->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
                            $searchKey = "alljobs";
                        }
                        $newSearch = findOrCreateUserSearchRun($searchKey, $siteToSearch);

                        $newSearch->setSearchParameter('keywords_array', $keywordSet['keywords_array']);
                        $newSearch->setSearchParameter('keywords_array_tokenized', $keywordSet['keywords_array_tokenized']);

                        if ($plugin->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED)) {
                            $arrSkippedPlugins[] = $siteToSearch;
                            continue;
                        }
                        elseif($plugin->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
                            $newSearch->save();
                            $arrSearchesPreLocation[$newSearch->getUserSearchRunKey()] = $newSearch;
                        }
                        else // if not, we need to add search for each keyword using that word as a single value in a keyword set
                        {
                            $arrKeys = array_keys($keywordSet['keywords_array']);
                            foreach ($arrKeys as $kwdkey) {
                                $thisSearchKey = $searchKey . "-" . strScrub($kwdkey, FOR_LOOKUP_VALUE_MATCHING);
                                $thisSearch = findOrCreateUserSearchRun($thisSearchKey, $newSearch->getJobSiteKey(), $newSearch->getGeoLocationId(), $newSearch);

                                $thisSearch->setSearchParameter('keywords_array', array($keywordSet['keywords_array'][$kwdkey]));
                                $thisSearch->setSearchParameter('keywords_array_tokenized', $keywordSet['keywords_array_tokenized'][$kwdkey]);
                                $thisSearch->save();
                                $arrSearchesPreLocation[$thisSearch->getUserSearchRunKey()] = $thisSearch;
                            }
                            $newSearch->delete();
                        }
                    }
                } else {
                    LogLine("No searches were set for keyword set " . $keywordSet['name'], \C__DISPLAY_WARNING__);
                }

                if (count($arrSkippedPlugins) > 0)
                    LogLine("Keyword set " . $keywordSet['name'] . " did not generate searches for " . count($arrSkippedPlugins) . " plugins because they do not support keyword search: " . getArrayValuesAsString($arrSkippedPlugins, ", ", null, false) . ".", \C__DISPLAY_WARNING__);
            }

            //
            // Full set of searches loaded (location-agnostic).  We've now
            // got the full set of searches, so update the set with the
            // primary location data we have in the config.
            //

            if(count($arrSearchesPreLocation) > 0)
            {
                $arrLocations = getConfigurationSettings('search_location');
                if(isset($arrLocations) && is_array($arrLocations) && count($arrLocations) >= 1)
                {
                    foreach ($arrSearchesPreLocation as $search)
                    {
                        $plugin = getPluginObjectForJobSite($search->getJobSiteKey());

                        if ($plugin->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) || $plugin->isBitFlagSet(C__JOB_SETTINGS_URL_VALUE_REQUIRED))
                        {
                            // this search doesn't support specifying locations so we shouldn't clone it for a second location set
                            $GLOBALS['USERDATA']['configuration_settings']['searches'][$search->getUserSearchRunKey()] = $search;
                            continue;
                        }

                        foreach ( $arrLocations as $searchlocation)
                        {
                            $searchForLoc = $this->_getSearchForSpecificLocationName_($search, $searchlocation);

                            if(!is_null($searchForLoc))
                                $GLOBALS['USERDATA']['configuration_settings']['searches'][$searchForLoc->getUserSearchRunKey()] = $searchForLoc;
                        }
                        $search->delete();
                    }
                }
            }
            else
                $GLOBALS['USERDATA']['configuration_settings']['searches'] = $arrSearchesPreLocation;
        }
    }

    private function filterSearchesThatShouldNotRunYet()
    {
        $searches = getConfigurationSettings('searches');
        if(!empty($searches) && is_array($searches))
        {
            foreach(array_keys($searches) as $searchKey)
            {

                if(!$searches[$searchKey]->shouldRunNow()) {
                    LogLine("... skipping search " . $searchKey . ". It has run too recently. (Next run = " . $searches[$searchKey]->getStartNextRunAfter("Y-m-d H:i") . ")", C__DISPLAY_ITEM_DETAIL__);
                    unset($searches[$searchKey]);
                }
            }
            LogLine("... " . count($searches) . " now remain to be run after filtering out recent search runs.", C__DISPLAY_ITEM_DETAIL__);
            $GLOBALS['USERDATA']['configuration_settings']['searches'] = $searches;
        }
    }

    private function _filterSearchesByCountry()
    {
        if (isset($GLOBALS['USERDATA']['configuration_settings']['included_sites']) && count($GLOBALS['USERDATA']['configuration_settings']['included_sites']) > 0) {
            foreach ($GLOBALS['USERDATA']['configuration_settings']['included_sites'] as $siteToSearch) {
                $plugin = getPluginObjectForJobSite($siteToSearch);
                $pluginCountries = $plugin->getSupportedCountryCodes();
                if (!is_null($pluginCountries)) {
                    $matchedCountries = array_intersect($pluginCountries, $GLOBALS['USERDATA']['configuration_settings']['country_codes']);
                    if ($matchedCountries === false || count($matchedCountries) == 0) {
                        LogDebug("Excluding " . $siteToSearch . " because it does not support any of the country codes required for the user's searches (" . getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['country_codes']));
                        setSiteAsExcluded($siteToSearch);
                        continue;
                    }
                }
            }
        }
    }

    private function _getSearchForSpecificLocationName_(UserSearchRun $search, $arrSearchLocation)
    {
        if ($search->isSearchIncludedInRun() !== true) {
            // this site was excluded for this run, so continue.
            return $search;
        }

        if (!is_null($search->getSearchParameter('location_user_specified_override'))) {
            // this search already has a location from the user, so we just do nothing else
            return $search;
        }

        LogDebug("Initializing new " . $search->getJobSiteKey() . " search for " . $search->getSearchKey() . " with location " . $arrSearchLocation['location_name_key'] . "...", \C__DISPLAY_NORMAL__);


        $plugin = getPluginObjectForJobSite($search->getJobSiteKey());
        $locTypeNeeded = $plugin->getGeoLocationSettingType($arrSearchLocation['location']);
        if (!is_null($locTypeNeeded)) {

            $newSearch = findOrCreateUserSearchRun($search->getSearchKey(), $search->getJobSiteKey(), $arrSearchLocation['location_name_key'], $search);

            $location = $newSearch->getGeoLocation();
            $formatted_search_term = $location->formatLocationByLocationType($locTypeNeeded);
            $newSearch->setSearchParameter('location_search_value', $formatted_search_term);
            if (is_null($formatted_search_term) || strlen($formatted_search_term) == 0)
            {
                LogLine(sprintf("Requested location type setting of '%s' for %s was not found for search location %s.", $locTypeNeeded, $search->getJobSiteKey(), $arrSearchLocation['location_name_key']), C__DISPLAY_WARNING__);
                return null;
            }

            if (!isValueURLEncoded($newSearch->getSearchParameter('location_search_value'))) {
                $newSearch->setSearchParameter('location_search_value', urlencode($newSearch->getSearchParameter('location_search_value')));
            }

            if ($plugin->isBitFlagSet(C__JOB_LOCATION_REQUIRES_LOWERCASE)) {
                $newSearch->setSearchParameter('location_search_value', strtolower($newSearch->getSearchParameter('location_search_value')));
            }

            $newSearch->save();

            return $newSearch;
        }

        return $search;
    }


    private function _createSearchInstancesForRun()
    {
        //
        // let's start with the searches specified with the details in the the config.ini
        //
        $arrSearchConfigSettings = getConfigurationSettings('searches');
        LogLine(" Creating search instances for this run from " . strval(count($arrSearchConfigSettings)) . " search config settings.", \C__DISPLAY_ITEM_DETAIL__);
        $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'] = array();

        if (!is_null($arrSearchConfigSettings) && is_array($arrSearchConfigSettings) && count($arrSearchConfigSettings) > 0)
        {
            //
            // Remove any sites that were excluded in this run from the searches list
            //
            foreach (array_keys($arrSearchConfigSettings) as $z) {
                $curSearchSettings = $arrSearchConfigSettings[$z];
                $jobsitekey = cleanupSlugPart($curSearchSettings->getJobSiteKey());

                if ($curSearchSettings->getJobSiteObject()->isSearchIncludedInRun()) {
                    if (!array_key_exists($jobsitekey, $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN']))
                        $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'][$jobsitekey] = array();

                    $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'][$jobsitekey][$curSearchSettings->getUserSearchRunKey()] = $curSearchSettings;
                }
            }
        }
    }


}
