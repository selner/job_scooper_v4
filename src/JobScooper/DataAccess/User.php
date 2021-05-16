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
use function JBZoo\Utils\int;

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
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function postSave(ConnectionInterface $con = null)
    {
        parent::postSave($con);

        if (!empty($this->getSearchLocations()) && !empty($this->getSearchKeywords())) {
            try {
                $this->_updateUserSearchPairs();
            } catch (PropelException $ex) {
                handleThrowable($ex);
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
     * @return string[]|null
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getSearchLocationCountryCodes()
    {
        $arrCCs = array();
        $loc = array();
        $searchpairs = $this->getUserSearchPairs();
        foreach ($searchpairs as $pair) {
            $loc[] = $pair->getGeoLocationFromUS();
            if(!is_empty_value($loc)) {
                $cc = $loc->getCountryCode();
                if(!is_empty_value($cc)) {
                    $arrCCs[$cc] = $cc;
                }
            }
        }
        return array_unique($arrCCs);
    }

    /**
     * @param array  $arr
     * @param string $keyType
     *
     * @throws \Exception
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
    	$objKeys = \JobScooper\DataAccess\Map\UserTableMap::getFieldNames();
    	$keyLookups = array();
    	foreach($objKeys as $k) {
    		$keyLookups[strtolower($k)] = $k;
    		$keyLookups[$k] = $k;
    	}
		$keyLookups['email'] = 'EmailAddress';
    	$keyLookups['display_name'] = 'Name';
    	$keyLookups['keywords'] = 'SearchKeywords';
    	$keyLookups['inputfiles'] = 'InputFiles';
    	$keyLookups['notification_delay'] = 'NotificationFrequency';
    	
    	$arrToSet = array();
    	
        foreach ($arr as $k => $v) {
            $arrToSet[$keyLookups[$k]] = $v;
        }

        parent::fromArray($arrToSet, $keyType);
    }


    /**
     * @param $arrUserFacts
     * @return array
     * @throws \ErrorException
     */
    static function parseConfigUserInputFiles($arrUserFacts):array
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
                    throw new \Exception("Specified input file '$path' was not found.  Aborting.");
                }

                $key = $fileinfo->getBasename('.csv');
                if (!array_key_exists($type, $verifiedInputFiles)) {
                    $verifiedInputFiles[$type] = array();
                }
                $verifiedInputFiles[$type][$key] = $tempFileDetails->getPathname();
            }
            
        }
        return $verifiedInputFiles;
	}

    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        $arrUser = parent::toArray($keyType, $includeLazyLoadColumns, $alreadyDumpedObjects, $includeForeignObjects);
        if(array_key_exists('InputFilesJson', $arrUser) and strlen($arrUser['InputFilesJson']) > 0) {
            $arrFiles = json_decode($arrUser['InputFilesJson'], True, flags: JSON_OBJECT_AS_ARRAY);
            $arrUser['inputfiles'] = $arrFiles;
        }

        return $arrUser;
    }

    /**
     * @param array $arrUserFacts
     *
     * @throws \Exception
     */
    private function _setConfigUserInputFiles($arrUserFacts)
    {
    	$verifiedInputFiles = self::parseConfigUserInputFiles($arrUserFacts);
        $this->setInputFiles($verifiedInputFiles);
    }

    /**
     * @param array[]|null $v
     *
     * @throws \Exception
     */
    public function setInputFiles($v)
    {
        if (!empty($v)) {
            $this->setInputFilesJson(encodeJson($v));
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
            $files = decodeJson($v);
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

        $locmgr = LocationLookup::getInstance();

        $searchGeoLocIds = array();

        foreach ($searchLocations as $lockey => $searchLoc) {
            $location = $locmgr->lookup($searchLoc);
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
            handleThrowable($ex, null, true);
        } catch (\Throwable $ex) {
            handleThrowable($ex, null, true);
        }

        if (empty($userSearchPairs)) {
            LogMessage('Could not create user searches for the given user keyword sets and geolocations.  Cannot continue.');
            return ;
        }
        LogMessage('Updated or created ' . \count($userSearchPairs) . " user search pairs for {$slug}.");
    }

    /**
     * @return \JobScooper\DataAccess\UserSearchPair[]|null
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getActiveUserSearchPairs()
    {
        $searchPairs = $this->getUserSearchPairs();
        if (!is_empty_value($searchPairs) && !$searchPairs->isEmpty()) {
            foreach($searchPairs->getIterator() as $pair) {
                if($pair->isActive() !== true) {
                    $searchPairs->removeObject($pair);
                }
            }
        }

        return $searchPairs;
    }

    /**
     * @return integer[]|null
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getActiveUserSearchPairIds()
    {
    	$ret = array();

		$pairs = $this->getActiveUserSearchPairs();
		if(!is_empty_value($pairs)) {
            $ret = $pairs->toKeyValue("UserSearchPairId","UserSearchPairId");
        }

        unset($pairs);
        return $ret;
	}


    /**
     * @param null $searchPairIdOnly
     * @return array|null
     * @throws PropelException
     */
    private function getCountryCodesForUser($searchPairIdOnly=null)
    {
    	if(null === $this->_userSearchCountryCodes) {
    		$this->_userSearchCountryCodes = array();

	        $searchPairs = $this->getActiveUserSearchPairs();
	        if(!is_empty_value($searchPairs) && !$searchPairs->isEmpty()) {
                foreach($searchPairs->getIterator() as $pair) {

                    $geoloc = $pair->getGeoLocationFromUS();
                    if (null !== $geoloc) {
                        $code = $geoloc->getCountryCode();

                        if (null !== $code && !in_array($code, $this->_userSearchCountryCodes, false)) {
                            $this->_userSearchCountryCodes[$pair->getUserSearchPairId()] = $code;
                        }
                    }
                    $geoloc = null;
                }
				$pair = null;
	        }
        }

        if(!is_empty_value($searchPairIdOnly)) {
            if(array_key_exists($searchPairIdOnly, $this->_userSearchCountryCodes)) {
                return $this->_userSearchCountryCodes[$searchPairIdOnly];
            }

            return null;
        }

        return $this->_userSearchCountryCodes;
    }

}
