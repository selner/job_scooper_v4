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



/******************************************************************************
 *
 *  Database Helper Functions
 *
 ******************************************************************************/
function loadSqlite3MathExtensions()
{
	try {
	    $con = \Propel\Runtime\Propel::getConnection();
        $expath = '/opt/sqlite/extensions/libsqlitefunctions';
        if(PHP_OS == "Darwin")
            $expath .= ".dylib";
        else
            $expath .= ".so";

        $sql2 = "SELECT load_extension('{$expath}');";
        $stmt = $con->prepare($sql2);
        $stmt->execute();
        return true;

	} catch (Exception $ex) {
		handleException($ex,"FAILED to load math functions extension for SQLite with call: " . $sql2 . "   ERROR DETAILS: %s", true);
	}

	return false;
}


/******************************************************************************
 *
 *  JobPosting Helper Functions
 *
 ******************************************************************************/

function updateOrCreateJobPosting($arrJobItem)
{
    if(is_null($arrJobItem) || !is_array($arrJobItem))
        return null;

    try {
        if(array_key_exists('JobPostingId', $arrJobItem) && !is_null($arrJobItem['JobPostingId'])) {
            $jobRecord =  \JobScooper\DataAccess\JobPostingQuery::create()
                ->filterByPrimaryKey($arrJobItem['JobPostingId'])
                ->findOneOrCreate();
        }
        else {
            if(is_null($arrJobItem['JobSiteKey']) || strlen($arrJobItem['JobSiteKey']) == 0)
                throw new InvalidArgumentException("Attempted to create a new job posting record without a valid JobSiteKey value set.");

            $jobRecord = \JobScooper\DataAccess\JobPostingQuery::create()
                ->filterByJobSiteKey($arrJobItem['JobSiteKey'])
                ->filterByJobSitePostId($arrJobItem['JobSitePostId'])
                ->findOneOrCreate();
        }

        $jobRecord->fromArray($arrJobItem);
        $jobRecord->save();
        return $jobRecord;

    }
    catch (Exception $ex)
    {
        handleException($ex);
    }

    return null;

}


/**
 * @param null $jobsiteKey
 * @param null $usrTitleKeywordSets
 *
 * @return \JobScooper\DataAccess\UserJobMatch[]
 * @throws \Propel\Runtime\Exception\PropelException
 */
function getAllMatchesForUserNotification($jobsiteKey=null, $excludeNonJobMatches=false, $arrGeoLocIds=null)
{
    $user= \JobScooper\DataAccess\User::getCurrentUser();


    $query = \JobScooper\DataAccess\UserJobMatchQuery::create()
        ->filterByUserNotificationState(\JobScooper\DataAccess\Map\UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_SENT, \Propel\Runtime\ActiveQuery\Criteria::NOT_EQUAL)
        ->filterByUserFromUJM($user)
        ->joinWithJobPostingFromUJM();

    if($excludeNonJobMatches === true)
    {
    	$query->filterByIsJobMatch(true);
    }

    if(!empty($jobsiteKey))
    {
        $query->useJobPostingFromUJMQuery()
            ->filterByJobSiteKey($jobsiteKey)
            ->endUse();
    }
    else
    {
	    $includedSites = \JobScooper\Builders\JobSitePluginBuilder::getIncludedJobSites();
	    $query->useJobPostingFromUJMQuery()
		    ->filterByJobSiteKey(array_keys($includedSites), \Propel\Runtime\ActiveQuery\Criteria::IN)
		    ->endUse();
    }

    if(!empty($arrGeoLocIds) && is_array($arrGeoLocIds))
    {
	    $query->useJobPostingFromUJMQuery()
		    ->filterByGeoLocationId($arrGeoLocIds)
		    ->endUse();
    }

    $results =  $query->find();
    return $results->getData();
}




/**
 * @return array
 */
function getAllPluginClassesByJobSiteKey()
{
    $classList = get_declared_classes();
    sort($classList);
    $pluginClasses = array_filter($classList, function ($class) {
        return (stripos($class, "Plugin") !== false) && stripos($class, "\\Classes\\") === false && in_array("JobScooper\BasePlugin\Interfaces\IJobSitePlugin", class_implements($class));
    });

    $classListBySite = array();
    foreach($pluginClasses as $class)
    {
        $jobsitekey= cleanupSlugPart(str_replace("Plugin", "", $class));
        $classListBySite[$jobsitekey] = $class;
    }

    return $classListBySite;
}

