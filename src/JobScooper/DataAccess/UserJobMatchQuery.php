<?php

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\UserJobMatchQuery as BaseUserJobMatchQuery;
use JobScooper\DataAccess\Map\UserJobMatchTableMap;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Skeleton subclass for performing query and update operations on the 'user_job_match' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class UserJobMatchQuery extends BaseUserJobMatchQuery
{

	/**
	 * @param $userNotificationState
	 *
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	function filterByUserNotificationStatus($userNotificationState)
	{
		$userStateCriteria = [UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_NOT_YET_MARKED, \Propel\Runtime\ActiveQuery\Criteria::EQUAL];
		if(empty(!$userNotificationState) && is_array($userNotificationState))
		{
			$userStateCriteria = $userNotificationState;
			$this->filterByUserNotificationState($userStateCriteria[0], $userStateCriteria[1]);
		}

	}
	/**
	 * @param null $nNumDaysBack
	 */
	function filterByDaysAgo($nNumDaysBack = null)
	{
		if(!empty($nNumDaysBack) && is_integer($nNumDaysBack))
		{
			$startDate = new \DateTime();
			$strMod = "-{$nNumDaysBack} days";
			$dateDaysAgo = $startDate->modify($strMod);
			$strDateDaysAgo = $dateDaysAgo->format("Y-m-d");

			$this->filterByFirstMatchedAt($strDateDaysAgo, Criteria::GREATER_EQUAL);
		}
	}

	/**
	 * @param $user
	 *
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	function filterByUser($user)
	{
		if(!empty($user))
			$this->filterByUserFromUJM($user);
	}

	function filterByGeoLocationIds($arrGeoLocIds)
	{
		if(!empty($arrGeoLocIds) && is_array($arrGeoLocIds))
		{
			$locIdColumnName = $this->getAliasedColName(\JobScooper\DataAccess\Map\JobPostingTableMap::COL_GEOLOCATION_ID);
			$this->useJobPostingFromUJMQuery()
				->addCond('locIdsCond1', $locIdColumnName, $arrGeoLocIds, Criteria::IN)
				->addCond('locIdsCond2', $locIdColumnName, null, Criteria::ISNULL)
				->combine(array('locIdsCond1', 'locIdsCond2'), Criteria::LOGICAL_OR)
				->orderByKeyCompanyAndTitle()
				->endUse();
		}
	}

}
