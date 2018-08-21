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

define('MAX_RESULTS_PER_PAGE', 2500);

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
 * @return \JobScooper\DataAccess\UserJobMatch[]
 *
 * @throws \Exception
 * @throws \Propel\Runtime\Exception\PropelException
 */
function getAllMatchesForUserNotification($userNotificationState, $arrGeoLocIds=null, $nNumDaysBack=null, $userFacts=null, $countsOnly=false)
{
    $results = null;

    $query = \JobScooper\DataAccess\UserJobMatchQuery::create();
    $query->joinWithJobPostingFromUJM();

    if ($countsOnly !== true) {
        $query->limit(2500);

        $query->useJobPostingFromUJMQuery()
            ->orderByKeyCompanyAndTitle()
            ->endUse();

        $keyResults = 'UserJobMatchId';
    } else {
        $sitekeyColumnName = $query->getAliasedColName(\JobScooper\DataAccess\Map\JobPostingTableMap::COL_JOBSITE_KEY);
        $query->clearSelectColumns()
            ->addAsColumn('TotalNewUserJobMatches', 'COUNT(DISTINCT(UserJobMatch.UserJobMatchId))')
            ->addAsColumn('TotalNewJobPostings', 'COUNT(DISTINCT(UserJobMatch.JobPostingId))')
            ->select(array($sitekeyColumnName, 'TotalNewUserJobMatches', 'TotalNewJobPostings'))
            ->groupBy(array($sitekeyColumnName));

        $keyResults = null;
    }


    $query->filterByUserNotificationStatus($userNotificationState);
    $query->filterByUserId($userFacts['UserId']);
    $query->filterByDaysAgo($nNumDaysBack);
    $query->filterByGeoLocationIds($arrGeoLocIds);

    if (null !== $keyResults) {
        $results = $query->find()->toKeyIndex($keyResults);
    } else {
        $results = $query->find()->getData();
    }

    if (!empty($results) && !empty($sitekeyColumnName)) {
        $results = array_column($results, null, $sitekeyColumnName);
    }

    $query = null;

    return $results;
}

/**
 * @param $query
 * @param $callback
 * @param $backOffsetDelta
 * @param $maxResultsPerPage
 *
 * @throws \Exception
 * @throws \Propel\Runtime\Exception\PropelException
 */
function doBatchCallbackForQuery(&$query, $callback, $backOffsetDelta = 0, $maxResultsPerPage = MAX_RESULTS_PER_PAGE)
{
    $resultsPageData = null;
    $con = \Propel\Runtime\Propel::getWriteConnection('default');
    
    $nResults = 0;
    $queryClass = get_class($query);
	$totalResults = $query->count($con);
    $nLastPage = round($totalResults / $maxResultsPerPage, 0, PHP_ROUND_HALF_UP) + 1;

    for($nCurrentPage = 1; $nCurrentPage <= $nLastPage; $nCurrentPage++) {
        $offset = ($nCurrentPage - 1) * $maxResultsPerPage;
        //
        /// // go backwards one if we can so we don't miss dupes at the border of page sets
        //
        $backStepOffset = 0;
        if($offset > 0) {
            $backStepOffset = $backOffsetDelta;
        }
        $query->setOffset($offset + $backStepOffset);
        $query->limit($maxResultsPerPage + abs($backStepOffset));
        $resultsPageData = $query->find();

        $nSetResults = $nResults + count($resultsPageData);
        LogMessage("Processing query results #" . (string)($nResults+$backStepOffset) . " - " . (string)($nSetResults+$backStepOffset) . " of {$totalResults} total results via callback {$queryClass}...");
        $nResults = $nResults + $nSetResults + $backStepOffset;
        call_user_func_array($callback, array(&$resultsPageData));
	    unset($resultsPageData);
    }

    $con = null;
    $jobsPager = null;
    $query = null;
 
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
function doCallbackForAllMatches($callback, $userNotificationState, $arrGeoIds=null, $nNumDaysBack=null, $userFacts=null)
{
    $chunkResults = null;
    $continueLoop = true;

    $nResults = 0;
    while ((null !== $chunkResults && !is_empty_value($chunkResults)) || $continueLoop === true) {
        $chunkResults = getAllMatchesForUserNotification($userNotificationState, $arrGeoIds, $nNumDaysBack, $userFacts);
        if (null !== $chunkResults && !is_empty_value($chunkResults)) {
            $nSetResults = $nResults + count($chunkResults) - 1;
            LogMessage("Processing user match results #{$nResults} - {$nSetResults} via callback...");
            $callback($chunkResults);
            $nResults += ($nResults + count($chunkResults) - 1);
        } else {
            $continueLoop = false;
        }
    }

    if ($nResults === 0) {
        LogMessage('No user job matches were found to auto-mark.');
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

/**
 * @param $arrUserJobMatchIds
 * @param $strNewStatus
 *
 * @throws \Exception
 * @throws \Propel\Runtime\Exception\PropelException
 */
function updateUserJobMatchesStatus($arrUserJobMatchIds, $strNewStatus)
{
    LogMessage('Marking ' . count($arrUserJobMatchIds) . " user job matches as {$strNewStatus}...");
    $con = \Propel\Runtime\Propel::getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
    $valueSet = UserJobMatchTableMap::getValueSet(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE);
    $statusInt = array_search($strNewStatus, $valueSet);
    $nChunkCounter = 1;

    foreach (array_chunk($arrUserJobMatchIds, 50) as $chunk) {
        $nMax = ($nChunkCounter+50);
        \JobScooper\DataAccess\UserJobMatchQuery::create()
            ->filterByUserJobMatchId($chunk)
            ->update(array('UserNotificationState' => $statusInt), $con);

        $nChunkCounter += 50;
        if ($nChunkCounter % 100 === 0) {
            $con->commit();
            // fetch a new connection
            $con = \Propel\Runtime\Propel::getWriteConnection('default');
            LogMessage("Marking user job matches {$nChunkCounter} - " . ($nMax >= count($arrUserJobMatchIds) ? count($arrUserJobMatchIds) - 1 : $nMax) . " as {$strNewStatus}...");
        }
    }
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
    return ((empty($var['IsExcluded']) || $var['IsExcluded'] !== true) && (!empty($var['IsJobMatch']) && $var['IsJobMatch'] === true));
}
