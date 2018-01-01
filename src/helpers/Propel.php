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
 * @return array
 * @throws \Propel\Runtime\Exception\PropelException
 */
function getAllMatchesForUserNotification($jobsiteKey=null, $usrTitleKeywordSets=null)
{
    $user= \JobScooper\DataAccess\User::getCurrentUser();


    $query = \JobScooper\DataAccess\UserJobMatchQuery::create()
        ->filterByUserNotificationState(array("not-ready", "ready"))
        ->filterByUserFromUJM($user)
        ->joinWithJobPostingFromUJM();

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


    if(!empty($usrTitleKeywordSets)) {
//	    $jpquery = \JobScooper\DataAccess\Base\JobPostingQuery::create("JobPostingFromUJM");
	    $first = true;

	    foreach ($usrTitleKeywordSets as $kwd_set) {
		    $arrKwdSetTokens = preg_split("/[\s|\|]/", $kwd_set);
		    $jpquery  = $query->useJobPostingFromUJMQuery();
		    $jpquery->filterByTitleTokenList($arrKwdSetTokens, \Propel\Runtime\ActiveQuery\Criteria::CONTAINS_ALL);
		    $jpquery->endUse();
		    if($first == false)
			    $query->_or();
		    else
			    $first = false;
	    }
    }

    $results =  $query->find();
    return $results->getData();
}


/******************************************************************************
 *
 *  JobPostings <-> JSON Helper Functions
 *
 ******************************************************************************/


/**
 * Writes JSON encoded file of an array of JobPosting records named "jobslist"
 *
 * @param String $filepath The output json file to save to
 * @param array $arrJobRecords The array of JobPosting objects to export
 *
 * @returns String Returns filepath of exported file if successful
 */
function writeJobRecordsToJson($filepath, $arrJobRecords)
{
    if (is_null($arrJobRecords))
        $arrJobRecords = array();


    $arrOfJobs = array();
    foreach($arrJobRecords as $jobRecord)
    {
        $arrOfJobs[$jobRecord->getJobPostingId()] = $jobRecord->getJobPostingFromUJM()->toArray();
    }

    $data = array('jobs_count' => count($arrJobRecords), 'jobslist' => $arrOfJobs);
    return writeJSON($data, $filepath);
}

/**
 * Reads JSON encoded file with an array of JobPosting records named "jobslist"
 * and updates the database with the values for each job
 *
 * @param String $filepath The input json file to load
 *
 * @returns array Returns array of JobPostings if successful; empty array if not.
 */
function updateJobRecordsFromJson($filepath)
{
    $arrJobRecs = array();

    if (stripos($filepath, ".json") === false)
        $filepath = $filepath . "-" . strtolower(getTodayAsString("")) . ".json";

    if (is_file($filepath)) {
        LogMessage("Loading and updating JobPostings from from json file '" . $filepath ."'");
        $data = loadJSON($filepath);

        $arrJobsArray = $data['jobslist'];

        if(!is_null($arrJobsArray) && is_array($arrJobsArray))
        {
            foreach(array_keys($arrJobsArray) as $jobkey)
            {
                $item = $arrJobsArray[$jobkey];
                $jobRec = updateOrCreateJobPosting($item);
                $arrJobRecs[$jobkey] = $jobRec;
            }
        }
    }

    return $arrJobRecs;
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

