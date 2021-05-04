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

define('MAX_RESULTS_PER_PAGE', 1500);

/******************************************************************************
 *
 *
 *
 *
 *  New, Propel-Related Utils
 *
 *
 *
 *
 *
 ******************************************************************************/

use \JobScooper\DataAccess\Map\UserJobMatchTableMap;
use \Propel\Runtime\ActiveQuery\Criteria;

/******************************************************************************
 *
 *  Database Helper Functions
 *
 ******************************************************************************/
/**
 *
 * @return bool
 * @throws \Exception
 */
function loadSqlite3MathExtensions()
{
    try {
        $sql2 = '[UNKNONWN]';

        $con = \Propel\Runtime\Propel::getConnection();
        $expath = '/opt/sqlite/extensions/libsqlitefunctions';
        if (PHP_OS === 'Darwin') {
            $expath .= '.dylib';
        } else {
            $expath .= '.so';
        }

        $sql2 = "SELECT load_extension('{$expath}');";
        $stmt = $con->prepare($sql2);
        $stmt->execute();
        return true;
    } catch (Exception $ex) {
        handleException($ex, "FAILED to load math functions extension for SQLite with call: {$sql2}.   ERROR DETAILS: %s", true);
    }

    return false;
}


/******************************************************************************
 *
 *  JobPosting Helper Functions
 *
 ******************************************************************************/


/**
 * @param array $arrJobItem
 *
 * @return \JobScooper\DataAccess\JobPosting|null
 * @throws \Exception
 */
function updateOrCreateJobPosting($arrJobItem, \JobScooper\DataAccess\GeoLocation $searchLoc = null)
{
    if (is_empty_value($arrJobItem)  || !is_array($arrJobItem)) {
        return null;
    }

    try {
        if (array_key_exists('JobPostingId', $arrJobItem) && null !== $arrJobItem['JobPostingId']) {
            $jobRecord =  \JobScooper\DataAccess\JobPostingQuery::create()
                ->filterByPrimaryKey($arrJobItem['JobPostingId'])
                ->findOneOrCreate();
        } else {
            if (is_empty_value($arrJobItem['JobSiteKey'])) {
                throw new InvalidArgumentException('Attempted to create a new job posting record without a valid JobSiteKey value.');
            }

            $jobRecord = \JobScooper\DataAccess\JobPostingQuery::create()
                ->filterByJobSiteKey($arrJobItem['JobSiteKey'])
                ->filterByJobSitePostId($arrJobItem['JobSitePostId'])
                ->findOneOrCreate();
        }

        if (null !== $searchLoc) {
            $jobRecord->setSearchLocation($searchLoc->getGeoLocationId());
        }
        $jobRecord->fromArray($arrJobItem);
        $jobRecord->save();
        return $jobRecord;
    } catch (Exception $ex) {
        handleException($ex);
    }

    return null;
}


/**
 * @param null $userNotificationState
 * @param array|null $arrGeoLocIds
 * @param int|null $nNumDaysBack
 * @param array|null $userFacts
 *
 * @param array $orderByCols
 * @return \JobScooper\DataAccess\UserJobMatchQuery
 *
 * @throws \Propel\Runtime\Exception\PropelException
 */
function getAllUserNotificationMatchesQuery($userNotificationState, $arrGeoLocIds=null, $nNumDaysBack=null, $userFacts=null, $orderByCols=[])
{
    $results = null;

    $query = \JobScooper\DataAccess\UserJobMatchQuery::create();
    $query->joinWithJobPostingFromUJM();

//        $query->limit(MAX_RESULTS_PER_PAGE);


    $query->filterByUserNotificationStatus($userNotificationState);
    $query->filterByUserId($userFacts['UserId']);
    $query->filterByDaysAgo($nNumDaysBack);
    $query->filterByGeoLocationIds($arrGeoLocIds);

    if(is_empty_value($orderByCols)) {
        $query->useJobPostingFromUJMQuery()
            ->orderByKeyCompanyAndTitle()
            ->endUse();
    }
    else {
        foreach($orderByCols as $col) {
            $colparams = explode(" ", $col);
            if(count($colparams) > 1 && strtolower($colparams[1]) === "desc") {
                $query->addDescendingOrderByColumn($colparams[0]);
            }
            else {
                $query->addAscendingOrderByColumn($colparams[0]);
            }
        }
    }



    return $query;
}

/**
 * @param null $userNotificationState
 * @param array|null $arrGeoLocIds
 * @param int|null $nNumDaysBack
 * @param array|null $userFacts
 *
 * @return \JobScooper\DataAccess\UserJobMatch[]
 *
 * @throws \Exception
 * @throws \Propel\Runtime\Exception\PropelException
 */
function getAllUserNotificationCounts($userNotificationState, $arrGeoLocIds=null, $nNumDaysBack=null, $userFacts=null)
{
    $results = null;

    $query = getAllUserNotificationMatchesQuery($userNotificationState, $arrGeoLocIds, $nNumDaysBack, $userFacts);

    $sitekeyColumnName = $query->getAliasedColName(\JobScooper\DataAccess\Map\JobPostingTableMap::COL_JOBSITE_KEY);
    $query->clearSelectColumns()
        ->addAsColumn('TotalNewUserJobMatches', 'COUNT(DISTINCT(UserJobMatch.UserJobMatchId))')
        ->addAsColumn('TotalNewJobPostings', 'COUNT(DISTINCT(UserJobMatch.JobPostingId))')
        ->select(array($sitekeyColumnName, 'TotalNewUserJobMatches', 'TotalNewJobPostings'))
        ->groupBy(array($sitekeyColumnName))
        ->orderBy("UserJobMatch.IsJobMatch", Criteria::DESC);
    
    $results = $query->find()->getData();

    if (!empty($results) && !empty($sitekeyColumnName)) {
        $results = array_column($results, null, $sitekeyColumnName);
    }

    $query = null;

    return $results;
}




/**
 * @param null $userNotificationState
 * @param array|null $arrGeoLocIds
 * @param int|null $nNumDaysBack
 * @param array|null $userFacts
 *
 * @return \JobScooper\DataAccess\UserJobMatch[]
 *
 * @throws \Exception
 * @throws \Propel\Runtime\Exception\PropelException
 */
function getAllMatchesForUserNotification($userNotificationState, $arrGeoLocIds=null, $nNumDaysBack=null, $userFacts=null, $countsOnly=false)
{
    $results = null;

    $query = getAllUserNotificationMatchesQuery(
        $userNotificationState,
        $arrGeoLocIds,
        $nNumDaysBack,
        $userFacts,
        $orderByCols = [ 'is_job_match DESC', 'jobposting.key_company_and_title ASC']
    );


    $results = $query->find()->toKeyIndex('UserJobMatchId');

    $query = null;

    return $results;
}

//
// User Job Match List Functions
//

/**
 * @param                                  $callback
 * @param                                  $userNotificationState
 * @param null                             $arrGeoIds
 * @param null                             $nNumDaysBack
 * @param array|null $userFacts
 *
 * @throws \Exception
 * @throws \Propel\Runtime\Exception\PropelException
 */
function doCallbackForAllMatches($callback, $userNotificationState, $arrGeoIds=null, $nNumDaysBack=null, $userFacts=null, $addlCallbackParams=[], $orderByCols=[])
{
    $nResults = 0;
 
    LogMessage('Getting all user job matches results query...');

    $query = getAllUserNotificationMatchesQuery($userNotificationState, $arrGeoIds, $nNumDaysBack, $userFacts, $orderByCols);
    $resultsPager = $query->paginate(1, $maxPerPage = MAX_RESULTS_PER_PAGE);
    
    $totalResults = $resultsPager->getNbResults();
    $nTotalPages = intceil($totalResults/MAX_RESULTS_PER_PAGE);

    if ($totalResults === 0 ) {
        LogMessage('No user job matches were found to auto-mark.');
    }
    else
    {
        $moreResults = true;
		$nCurrentPage = 1;
        
        try {
	        while($moreResults === true && $nResults <= $totalResults && $nCurrentPage <= $nTotalPages)
	        {
		            startLogSection("Processing user matches page {$nCurrentPage} (#{$resultsPager->getFirstIndex()} - {$resultsPager->getLastIndex()} ) via callback...");

                    $results = $resultsPager->getResults();
		            if(!is_empty_value($addlCallbackParams)) {
                        $callback($results, $addlCallbackParams);
		            }
		            else {
		                $callback($results);
		            }
		            $nResults += $resultsPager->count();
		            endLogSection("Callback complete for results page {$nCurrentPage} ");
	
		            if(!$resultsPager->haveToPaginate() || $resultsPager->isLastPage()) {
		                $moreResults = false;
		            }
		            else
		            {
		                $nCurrentPage = $resultsPager->getNextPage();
		                LogMessage("Getting page {$nCurrentPage} of user match results...");
		                unset($resultsPager);
					    $resultsPager = $query->paginate($nCurrentPage, $maxPerPage = MAX_RESULTS_PER_PAGE);
		            }
	        }
        
        }
        catch (Exception $ex)
        {
            handleException($ex);
        }
        finally {
            unset($resultsPager);
        }
    }

}

/**
 * @param \JobScooper\DataAccess\GeoLocation $sourceGeoLocation
 *
 * @return array
 */
function getGeoLocationsNearby(\JobScooper\DataAccess\GeoLocation $sourceGeoLocation)
{
    $arrNearbyIds = \JobScooper\DataAccess\GeoLocationQuery::create()
        ->filterByDistanceFrom($sourceGeoLocation->getLatitude(), $sourceGeoLocation->getLongitude(), 50, \JobScooper\DataAccess\Map\GeoLocationTableMap::MILES_UNIT, Criteria::LESS_THAN)
        ->find()
        ->toKeyValue("GeoLocationId", "GeoLocationId");

    $arrNearbyIds[] = $sourceGeoLocation->getGeoLocationId();
    
    return $arrNearbyIds;
}


//
// Jobs List Filter Functions
//

/**
 * @param $var
 *
 * @return bool
 */
function isUserJobMatchAndNotExcluded($var)
{
    if(array_key_exists('IsExcluded', $var) && array_key_exists('IsJobMatch', $var)) {
        return ($var['IsExcluded'] === false && $var['IsJobMatch'] === true);
    }
    return false;
}
