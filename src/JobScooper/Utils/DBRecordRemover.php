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

namespace JobScooper\Utils;

use JobScooper\DataAccess\UserKeywordSetQuery;
use JobScooper\DataAccess\UserQuery;
use JobScooper\DataAccess\UserSearchQuery;
use JobScooper\DataAccess\UserSearchSiteRunQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;

class DBRecordRemover
{
	static function removeAllUserData()
	{
		$users = UserQuery::create()
			->find()
			->getData();

		DBRecordRemover::removeUsers($users);
	}

	static function removeUsers($users=null)
	{
		$query = UserQuery::create();
		return DBRecordRemover::removeUserRelatedRecords($query, $users);
	}

	static function removeUserKeywordSets($users=null, $userKeywordSets = null)
	{
		$query = UserKeywordSetQuery::create();
		return DBRecordRemover::removeUserRelatedRecords($query, $users, $userKeywordSets);
	}

	static function removeUserSearchSiteRuns($users=null, $userKeywordSets = null, $userSearches=null, $geolocations=null, $jobsites=null)
	{
		$query = UserSearchSiteRunQuery::create();
		return DBRecordRemover::removeUserRelatedRecords($query, $users, $userKeywordSets, $userSearches, $geolocations, $jobsites);
	}

	static function removeUserSearches($users=null, $userKeywordSets=null, $userSearches=null, $geolocations=null)
	{
		$query = UserSearchQuery::create();
		return DBRecordRemover::removeUserRelatedRecords($query, $users, $userKeywordSets, $userSearches, $geolocations);
	}

	static function removeUserRelatedRecords(&$query, $users=null, $userKeywordSets=null, $userSearches=null, $geolocations=null, $jobsites=null)
	{

		if(empty($query))
			return false;

		try {
			$queryFilters = array();
			foreach (["users" => $users, "keywordsets" => $userKeywordSets, "searches" => $userSearches, "geolocations" => $geolocations, "jobsites" => $jobsites] as $k => $param) {
				if (!empty($param) && !is_array($param)) {
					$queryFilters[$k] = array($param);
				} elseif (empty($param)) {
					$queryFilters[$k] = array();
				}
				else
				{
					$queryFilters[$k] = $param;
				}
			}

			foreach($queryFilters as $k => $v)
			{
				foreach($v as $itemKey => $item) {
					if (is_object($item)) {
						switch ($k) {
							case "users":
								$queryFilters[$k][$itemKey] = $item->getUserId();
								break;

							case "keywordsets":
								$queryFilters[$k][$itemKey] = $item->getUserKeywordSetKey();
								break;


							case "searches":
								$queryFilters[$k][$itemKey] = $item->getUserSearchKey();
								break;

							case "geolocations":
								$queryFilters[$k][$itemKey] = $item->getGeolocationId();
								break;

							case "jobsites":
								$queryFilters[$k][$itemKey] = $item->getJobSiteKey();
								break;
						}
					}
				}
			}

			if (!empty($queryFilters['users']))
				$query->filterByUserId($queryFilters['users'], Criteria::CONTAINS_SOME);

			if (!empty($queryFilters['keywordsets']))
				$query->filterByUserKeywordSetKey($queryFilters['keywordsets'], Criteria::CONTAINS_SOME);

			if (!empty($queryFilters['searches']))
				$query->filterByUserSearchKey($queryFilters['searches'], Criteria::CONTAINS_SOME);

			if (!empty($queryFilters['geolocations']))
				$query->filterByGeoLocationId($queryFilters['geolocations'], Criteria::CONTAINS_SOME);

			if (!empty($queryFilters['jobsites']))
				$query->filterByJobSiteKey($queryFilters['jobsites'], Criteria::CONTAINS_SOME);


			if(!isDebug())
			{
				throw new \Exception("Removing users in this manner is only allowed if the developer is running in debug mode.  Aborting.");
			}
			else {
				$con = Propel::getServiceContainer()->getWriteConnection("default");
				$query->deleteAll($con);
			}
		}
		catch (\Exception $ex)
		{
			handleException($ex);
		}

	}
}