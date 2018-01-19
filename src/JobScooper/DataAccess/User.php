<?php

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\User as BaseUser;
use JobScooper\DataAccess\Map\UserTableMap;
use JobScooper\Manager\LocationManager;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;

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

	/**
	 * @return \JobScooper\DataAccess\User
	 */
	static function getCurrentUser()
    {
        return getConfigurationSetting('current_user');
    }

	/**
	 * @param \JobScooper\DataAccess\User $user
	 */
	static function setCurrentUser(User $user)
    {
        setConfigurationSetting('current_user', $user);
    }

	/**
	 * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
	 *
	 * @return bool|void
	 * @throws \Exception
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
	    foreach ($searchpairs as $pair)
	    {
	    	$loc[] = $pair->getGeoLocationFromUS();
	    }
	    return $loc;
    }

	/**
	 * @param array  $arr
	 * @param string $keyType
	 */
	public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
	    if (array_key_exists("email", $arr)) {
		    $this->setEmailAddress($arr["email"]);
		    unset($arr['email']);
	    }

	    if (array_key_exists("display_name", $arr)) {
		    $this->setName($arr["display_name"]);
		    unset($arr['display_name']);
	    }

	    foreach ($arr as $k => $v)
	    {
			switch(strtolower($k))
			{
				case "email":
					$this->setEmailAddress($arr["email"]);
					unset($arr['email']);
					break;

				case "display_name":
					$this->setName($arr["display_name"]);
					unset($arr['display_name']);
					break;

				case "search_keywords":
				case "keywords":
					$this->setSearchKeywords($arr[strtolower($k)]);
					unset($arr[strtolower($k)]);
					break;

				case "search_locations":
					$this->setSearchLocations($arr["search_locations"]);
					unset($arr['search_locations']);
					break;

				default:
					$arr[ucwords($k)] = $v;
					unset($arr[$k]);
			}

	    }

	    parent::fromArray($arr, $keyType);
    }

	/**
	 * @return null
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	private function _updateUserSearchPairs()
	{
		$userSearchPairs = array();

		$searchLocations = $this->getSearchLocations();
		if (empty($searchLocations)) {
			LogWarning("No search locations have been set. Unable to setup a user search run.");
			return ;
		}

		$searchKeywords = $this->getSearchKeywords();
		if (empty($searchKeywords)) {
			LogWarning("No user search keywords have been configured. Unable to setup a user search run.");
			return ;
		}

		$locmgr = LocationManager::getLocationManager();
		if(empty($locmgr)) {
			LocationManager::create();
			$locmgr = LocationManager::getLocationManager();
		}

		foreach ($searchLocations as $lockey => $searchLoc)
		{
			$location = $locmgr->getAddress($searchLoc);
			if (!empty($location)) {
				LogMessage("Adding user searches in " . $location->getDisplayName() . " for user's keywords sets");
				$locId = $location->getGeoLocationId();

				foreach ($searchKeywords as $kwd) {
					$user_search = UserSearchPairQuery::create()
						->filterByUserId($this->getUserId())
						->filterByUserKeyword($kwd)
						->filterByGeoLocationId($locId)
						->findOneOrCreate();


					$user_search->setUserId($this->getUserId());
					$user_search->setUserKeyword($kwd);
					$user_search->setGeoLocationId($locId);
					$user_search->save();

					$userSearchPairs[$user_search->getUserSearchPairId()] = $user_search;
				}
			}
			else
				LogError("Could not create user searches for the '{$searchLoc}'' search location.");
		}

//		try {
//			$oldPairUpdate = UserSearchPairQuery::create()
//				->filterByUserId($this->getUserId())
//				->filterByIsActive(true)
//				->filterByUserSearchPairId(array_keys($userSearchPairs), Criteria::NOT_IN)
//				->update(array("is_active", false));
//			LogMessage("Marked {$$oldPairUpdate} previous user search pairs as inactive.");
//
//		} catch (PropelException $ex) {
//			handleException($ex, null, false);
//		} catch (\Exception $ex) {
//			handleException($ex, null, false);
//		}

		if (empty($userSearchPairs)) {
			LogMessage("Could not create user searches for the given user keyword sets and geolocations.  Cannot continue.");
			return ;
		}

		LogMessage("Generated " . count($userSearchPairs) . " user search pairs.");

	}

}
