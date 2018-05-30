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

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\User as BaseUser;
use JobScooper\DataAccess\Map\JobSiteRecordTableMap;
use JobScooper\Utils\Settings;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use JobScooper\DataAccess\Map\UserSearchPairTableMap;
use Propel\Runtime\Propel;

/**
 * Skeleton subclass for representing a row from the 'user' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class User extends BaseUser
{
    private $_userSearchSiteRunsByJobSite = null;
	private $_userSearchCountryCodes = null;

    /**
     * @return array
     */
    public static function getCurrentUserFacts()
    {
        $userId = Settings::getValue('current_user_id');
        if(null === $userId) {
        	return null;
        }
		return self::getUserFactsById($userId);
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public static function getUserFactsById($userId)
    {
        if(null === $userId)
        	throw new \InvalidArgumentException('Requested UserId value was null.');

    	$user = self::getUserObjById($userId);
    	$arrFacts = null;

		if(null === $user) {
			LogWarning("Requested UserId {$userId} could not be found in database.");
		}
		else {
			$arrFacts = $user->toArray();
		}

		$user = null;
		return $arrFacts;
    }

    /**
     * @param int $userId
     *
     * @return \JobScooper\DataAccess\User
     */
    public static function getUserObjById($userId)
    {
       return UserQuery::create()
            ->findOneByUserId($userId);
    }

    /**
     * @param \JobScooper\DataAccess\User $user
     *
     * @throws \PDOException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public static function setCurrentUser(User $user)
    {
    	$user->save();
        Settings::setValue('current_user_id', $user->getUserId());
        Settings::setValue('alerts.results.to', $user->toArray());
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function canNotifyUser()
    {
        $now = new \DateTime();
        $lastNotify = $this->getLastNotifiedAt();
        if (empty($lastNotify)) {
            return true;
        }

        $numDays = $this->getNotificationFrequency();
        if ($numDays === 0) {
            return true;
        }

        if (empty($numDays)) {
            $numDays = 1;
        }
        $interval = date_interval_create_from_date_string("{$numDays} days");
        $nextNotify = clone $lastNotify;
        $nextNotify->add($interval);

        return empty($lastNotify) || $now >= $nextNotify;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return bool|void
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function postSave(ConnectionInterface $con = null)
    {
        parent::postSave($con);

        if (!empty($this->getSearchLocations()) && !empty($this->getSearchKeywords())) {
            try {
                $this->_updateUserSearchPairs();
            } catch (PropelException $ex) {
                handleException($ex);
            }
        }
    }

    /**
     * @return \JobScooper\DataAccess\GeoLocation[]
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getSearchGeoLocations()
    {
        $loc = array();
        $searchpairs = $this->getUserSearchPairs();
        foreach ($searchpairs as $pair) {
            $loc[] = $pair->getGeoLocationFromUS();
        }
        return array_unique($loc);
    }

    /**
     * @param array  $arr
     * @param string $keyType
     *
     * @throws \Exception
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        if (array_key_exists('email', $arr)) {
            $this->setEmailAddress($arr['email']);
            unset($arr['email']);
        }

        if (array_key_exists('display_name', $arr)) {
            $this->setName($arr['display_name']);
            unset($arr['display_name']);
        }

        foreach ($arr as $k => $v) {
            switch (strtolower($k)) {
                case 'email':
                    $this->setEmailAddress($arr['email']);
                    unset($arr['email']);
                    break;

                case 'display_name':
                    $this->setName($arr['display_name']);
                    unset($arr['display_name']);
                    break;

                case 'search_keywords':
                case 'keywords':
                    $this->setSearchKeywords($arr[strtolower($k)]);
                    unset($arr[strtolower($k)]);
                    break;

                case 'search_locations':
                    $this->setSearchLocations($arr['search_locations']);
                    unset($arr['search_locations']);
                    break;

                case 'inputfiles':
                    $this->_parseConfigUserInputFiles($arr);
                    unset($arr['inputfiles']);
                    break;
                    
                case 'notification_delay':
                    $this->setNotificationFrequency($arr['notification_delay']);
                    unset($arr['notification_delay']);
                    break;

                default:
                    $arr[ucwords($k)] = $v;
                    unset($arr[$k]);
            }
        }

        parent::fromArray($arr, $keyType);
    }

    /**
     * @param array $arrUserFacts
     *
     * @throws \Exception
     */
    private function _parseConfigUserInputFiles($arrUserFacts)
    {
        //
        // Validate each of the inputfiles that the user passed
        // and configure all searches
        //
        $verifiedInputFiles = array();
        if (array_key_exists('inputfiles', $arrUserFacts) && !empty($arrUserFacts['inputfiles']) && is_array($arrUserFacts['inputfiles'])) {
            $inputfiles = $arrUserFacts['inputfiles'];
            foreach ($inputfiles as $key => $cfgvalue) {
                $split= explode(';', $cfgvalue);
                $type = $split[0];
                $path = $split[1];

                $tempFileDetails = null;
                $fileinfo = new \SplFileInfo($path);
                if ($fileinfo->getRealPath() !== false) {
                    $tempFileDetails = parsePathDetailsFromString($fileinfo->getRealPath(), C__FILEPATH_FILE_MUST_EXIST);
                }

                if (null === $tempFileDetails || $tempFileDetails->isFile() !== true) {
                    throw new \Exception("Specified input file '{$path}' was not found.  Aborting.");
                }

                $key = $fileinfo->getBasename('.csv');
                if (!array_key_exists($type, $verifiedInputFiles)) {
                    $verifiedInputFiles[$type] = array();
                }
                $verifiedInputFiles[$type][$key] = $tempFileDetails->getPathname();
            }

            $this->setInputFiles($verifiedInputFiles);
        }
    }

    /**
     * @param array[]|null $v
     *
     * @throws \Exception
     */
    public function setInputFiles($v)
    {
        if (!empty($v)) {
            $this->setInputFilesJson(encodeJSON($v));
        }
    }

    /**
     * @param string $type Return the subset of input files of a specific type only.
     *
     * @return array[]|null
     * @throws \Exception
     */
    public function getInputFiles($type=null)
    {
        $files = null;
        $v = $this->getInputFilesJson();
        if (!empty($v) && is_string($v)) {
            $files = decodeJSON($v);
        }

        if (!is_empty_value($type) && !is_empty_value($files)) {
            if (array_key_exists($type, $files)) {
                $files = $files[$type];
            } else {
                $files = array();
            }
        }

        return $files;
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function _updateUserSearchPairs()
    {
        $userSearchPairs = array();
        $slug = $this->getUserSlug();

        $searchLocations = $this->getSearchLocations();
        if (empty($searchLocations)) {
            LogWarning("No search locations have been set for {$slug}. Unable to create any search pairings for user.");
            return ;
        }

        $searchKeywords = $this->getSearchKeywords();
        if (empty($searchKeywords)) {
            LogWarning("No user search keywords have been configuredfor {$slug}. Unable to create any search pairings for user");
            return ;
        }

        $locmgr = GeoLocationManager::getLocationManager();
        if (is_empty_value($locmgr)) {
            GeoLocationManager::create();
            $locmgr = GeoLocationManager::getLocationManager();
        }
        $searchGeoLocIds = array();

        foreach ($searchLocations as $lockey => $searchLoc) {
            $location = $locmgr->lookupAddress($searchLoc);
            if ($location !== null && !is_empty_value($location)) {
                LogMessage("Updating/adding user search keyword/location pairings for location {$location->getDisplayName()} and user {$slug}'s keywords");
                $locId = $location->getGeoLocationId();
                if(is_empty_value($locId)) {
                	throw new \InvalidArgumentException("Unable to find GeoLocationId for Geolocation object.");
                }
                $searchGeoLocIds[$locId] = $locId;

                foreach ($searchKeywords as $kwd) {
                    $user_search = UserSearchPairQuery::create()
                        ->filterByUserId($this->getUserId())
                        ->filterByUserKeyword($kwd)
                        ->filterByGeoLocationId($locId)
                        ->findOneOrCreate();

                    $user_search->setUserId($this->getUserId());
                    $user_search->setUserKeyword($kwd);
                    $user_search->setIsActive(true);
                    $user_search->setGeoLocationId($locId);
                    $user_search->save();

                    $userSearchPairs[$user_search->getUserSearchPairId()] = $user_search;
                }
            } else {
                LogError("Could not create user searches for the '{$searchLoc}' search location.");
            }
        }

        try {
            $query = UserSearchPairQuery::create();

            $locIdColumnName = $query->getAliasedColName(UserSearchPairTableMap::COL_GEOLOCATION_ID);
            $kwdColumnName = $query->getAliasedColName(UserSearchPairTableMap::COL_USER_KEYWORD);
            $con = Propel::getWriteConnection(UserSearchPairTableMap::DATABASE_NAME);

            $oldPairUpdate  = $query->filterByUserSearchPairId(array_keys($userSearchPairs), Criteria::NOT_IN)
                ->filterByUserId($this->getUserId())
                ->filterByIsActive(true, Criteria::EQUAL)
                ->addCond('condUserKwds', $kwdColumnName, array_values($searchKeywords), Criteria::NOT_IN)
                ->addCond('condUserLocs', $locIdColumnName, array_values($searchGeoLocIds), Criteria::NOT_IN)
                ->combine(array('condUserKwds', 'condUserLocs'), Criteria::LOGICAL_OR)
                ->update(array('IsActive' => false), $con);
			$query = null;
			$con = null;
            LogMessage("Marked {$oldPairUpdate} previous user search pairs as inactive.");
        } catch (PropelException $ex) {
            handleException($ex, null, true);
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        }

        if (empty($userSearchPairs)) {
            LogMessage('Could not create user searches for the given user keyword sets and geolocations.  Cannot continue.');
            return ;
        }
        LogMessage('Updated or created ' . count($userSearchPairs) . " user search pairs for {$slug}.");
    }

    /**
     * @return \JobScooper\DataAccess\UserSearchPair[]|null
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getActiveUserSearchPairs()
    {
        $ret = null;

        $searchPairs = $this->getUserSearchPairs();
        if (!empty($searchPairs)) {
            $ret = array_filter($searchPairs->getData(), function (UserSearchPair $v) {
                return $v->isActive();
            });
        }

        return $ret;
    }
    /**
     * @return integer[]
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getActiveUserSearchPairIds()
    {
    	$pairIds = array();

		$pairs = $this->getActiveUserSearchPairs();
		if(!is_empty_value($pairs) && is_array($pairs)) {
			foreach($pairs as $k => $pair) {
				$pairIds[] = $pair->getUserSearchPairId();
				$pairs[$k] = null;
			}
		}
		return is_empty_value($pairIds) ? null : $pairIds;
	}

	/**
     * @param string $jobSiteKey
     *
     * @return array
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
     */
    public function getUserSearchSiteRunsForJobSite($jobSiteKey)
    {

        if(null === $jobSiteKey) {
        	throw new \InvalidArgumentException('Cannot get UserSearchSiteRuns for null JobSiteKey.');
        }


        if (empty($this->_userSearchSiteRunsByJobSite)) {
            $this->initializeSiteRuns();
        }

        if (empty($this->_userSearchSiteRunsByJobSite)) {
            return null;
        }

        $arrSearchesToRun = array();

		if(array_key_exists($jobSiteKey, $this->_userSearchSiteRunsByJobSite) &&  !is_empty_value($this->_userSearchSiteRunsByJobSite[$jobSiteKey])) {
			$arrSearchesToRun = $this->_userSearchSiteRunsByJobSite[$jobSiteKey];
		}

        return $arrSearchesToRun;
    }

    /**
    *
    * @return void
    * @throws \Propel\Runtime\Exception\PropelException
    * @throws \Exception
	*/
    private function initializeSiteRuns()
    {
    	if(null !== $this->_userSearchSiteRunsByJobSite)
    		return;
    	
    	$this->_userSearchSiteRunsByJobSite = array();
    	
        $sites = JobSiteManager::getJobSitesIncludedInRun();

		$userSearchRuns = $this->queryUserSearchSiteRuns();
        if(!is_empty_value($userSearchRuns))
        {
        	foreach($userSearchRuns as $k => $run)
            {
            	$this->_userSearchSiteRunsByJobSite[$run->getJobSiteKey()][$k] = $run->toFlatArray();
            }
        	return;
        }

        $searchPairs = $this->getActiveUserSearchPairs();
        $nTotalPairs = count($searchPairs);
        $nKeywords = count($this->getSearchKeywords());
        $nLocations = countAssociativeArrayValues($this->getSearchLocations());
        $nTotalPossibleSearches = $nKeywords * $nLocations * count($sites);

        $countryCodes = $this->getCountryCodesForUser();

        LogDebug("Configuring {$nTotalPairs} search pairs X " . count($sites) . " jobsites = up to {$nTotalPossibleSearches} total searches, from {$nKeywords} search keywords and {$nLocations} search locations in " . implode(", ", $countryCodes) .".");

        $ntotalSearchRuns = 0;

        foreach ($sites as $jobsiteKey => $site) {
	    	$this->_userSearchSiteRunsByJobSite[$jobsiteKey] = array();

            $ccJobSite = $site->getSupportedCountryCodes();
            $ccSiteOverlaps = array_intersect($countryCodes, $ccJobSite);

            if(!is_empty_value($ccSiteOverlaps)) {
	            foreach ($searchPairs as $pairKey => $searchPair) {
	            	$pairGeo = $searchPair->getGeoLocationFromUS();
					$ccPair = array();
	            	if(!is_empty_value($pairGeo)) {
	            		$ccPair = $pairGeo->getCountryCode();
	            	}
	            	$ccPairOverlaps = array_intersect($countryCodes, array($ccPair));
	                if (!is_empty_value($ccPairOverlaps)) {

						if($site->getResultsFilterType() !== JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE_ALL_ONLY ||
							!array_key_exists($jobsiteKey, $this->_userSearchSiteRunsByJobSite) || count( $this->_userSearchSiteRunsByJobSite[$jobsiteKey]) < 1) {
		                    $searchrun = new UserSearchSiteRun();
		                    $searchrun->setUserSearchPairId($searchPair->getUserSearchPairId());
		                    $searchrun->setJobSiteKey($site);
		                    $searchrun->setAppRunId(Settings::getValue('app_run_id'));
		                    $searchrun->setStartedAt(time());
		                    $searchrun->save();
		                    if (!array_key_exists($jobsiteKey, $this->_userSearchSiteRunsByJobSite)) {
		                        $this->_userSearchSiteRunsByJobSite[$jobsiteKey]= array();
		                    }
	
		                    $this->_userSearchSiteRunsByJobSite[$jobsiteKey][$searchrun->getUserSearchSiteRunKey()] = $searchrun->toFlatArray();
	                    }
	                    $searchrun = null;
		            } else {
		                LogDebug("Skipping searches for SearchPairId {$searchPair['UserSearchPairId']} because its country codes [" . implode('|', $ccPair) . "] do not include the user's search pair's country codes [{$countryCodes}]...");
		            }
                }
            } else {
                LogMessage("JobSite {$jobsiteKey}'s country codes [" . implode('|', $ccJobSite) . "] do not include {$this->getUserSlug()}'s search pair's country codes [" . getArrayDebugOutput($countryCodes)."].  Skipping {$jobsiteKey} searches...");
            }
			$site = null;
	        $sites[$jobsiteKey] = null;
            unset($sites[$jobsiteKey]);
        }

        $totalRuns = 0;
        foreach(array_keys($this->_userSearchSiteRunsByJobSite) as $siteKey)
        {
        	if(!is_empty_value($this->_userSearchSiteRunsByJobSite[$siteKey])) {
	            UserSearchSiteRunManager::filterRecentlyRunUserSearchRuns($this->_userSearchSiteRunsByJobSite[$siteKey]);
	            if(!is_empty_value($this->_userSearchSiteRunsByJobSite[$siteKey])) {
		            $totalRuns += count($this->_userSearchSiteRunsByJobSite[$siteKey]);
	            }
            }
		}
        $totalSkippedSearches = $nTotalPossibleSearches - $totalRuns;
        LogMessage("{$totalRuns} search runs configured for {$this->getUserSlug()}; {$totalSkippedSearches} searches were skipped.");

		$searchPairs = null;
        $sites = null;

    }

    /**
	 * @return array|null
	 * @throws \Propel\Runtime\Exception\PropelException
	*/
    private function getCountryCodesForUser()
    {
    	if(null === $this->_userSearchCountryCodes) {
    		$this->_userSearchCountryCodes = array();

	        $searchPairs = $this->getActiveUserSearchPairs();
	        while($searchPairs !== null && !empty($searchPairs))
	        {
	        	$pair = array_pop($searchPairs);
		        $geoloc = $pair->getGeoLocationFromUS();
		        if(null !== $geoloc) {
			        $code = $geoloc->getCountryCode();

			        if(null !== $code && !in_array($code, $this->_userSearchCountryCodes, false))
		            {
			            $this->_userSearchCountryCodes[] = $code;
			        }
				}
		        $geoloc = null;
				$pair = null;
	        }
        }

        return $this->_userSearchCountryCodes;
    }


    /*
    * @return array
	* @throws \Propel\Runtime\Exception\PropelException
	*/
    /**
 * @return array|null
* @throws \Propel\Runtime\Exception\PropelException
*/private function queryUserSearchSiteRuns()
    {
        $appRunId = Settings::getValue('app_run_id');
    	$searchPairIds = array();

        //
        // let's start with the searches specified with the details in the the config.ini
        //
        $searchPairs = $this->getActiveUserSearchPairs();
        $nTotalPairs = 0;
        if (empty($searchPairs)) {
            return null;
        }

    	foreach($searchPairs as $k => $pair) {
    		$searchPairIds[] = $searchPairs[$k]->getUserSearchPairId();
    	    ++$nTotalPairs;
    		// free the object & related db connection
    		$searchPairs[$k] = null;
        }
		$searchPairs = null;

        return UserSearchSiteRunQuery::create()
            ->filterByAppRunId($appRunId)
            ->filterByUserSearchPairId($searchPairIds, Criteria::IN)
            ->find()
            ->toArray("UserSearchSiteRunKey");
    }
}
