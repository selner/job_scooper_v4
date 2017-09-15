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
require_once dirname(dirname(__FILE__))."/bootstrap.php";





/******************************************************************************
 *
 *
 *
 *
 *  New, Propel-Related Helpers
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


/**
 * Cleanup a string to make a slug of it
 * Removes special characters, replaces blanks with a separator, and trim it
 *
 * @param     string $slug        the text to slugify
 * @param     string $replacement the separator used by slug
 * @return    string               the slugified text
 */
function cleanupSlugPart($slug, $replacement = '-')
{
    // transliterate
    if (function_exists('iconv')) {
        $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
    }

    // lowercase
    if (function_exists('mb_strtolower')) {
        $slug = mb_strtolower($slug);
    } else {
        $slug = strtolower($slug);
    }

    // remove accents resulting from OSX's iconv
    $slug = str_replace(array('\'', '`', '^'), '', $slug);

    // replace non letter or digits with separator
    $slug = preg_replace('/\W+/', $replacement, $slug);

    // trim
    $slug = trim($slug, $replacement);

    if (empty($slug)) {
        return 'n-a';
    }

    return $slug;
}


/******************************************************************************
 *
 *  JobPosting Helper Functions
 *
 ******************************************************************************/

function updateOrCreateJobPosting($jobArray)
{
    if(is_null($jobArray) || !is_array($jobArray))
        return null;

    try {
        if(array_key_exists('JobPostingId', $jobArray) && !is_null($jobArray['JobPostingId'])) {
            $jobRecord =  \JobScooper\JobPostingQuery::create()
                ->filterByPrimaryKey($jobArray['JobPostingId'])
                ->findOneOrCreate();
        }
        else {
            if(is_null($jobArray['job_site']) || strlen($jobArray['job_site']) == 0)
                throw new InvalidArgumentException("Attempted to create a new job posting record without a valid jobsite value set.");

            $jobRecord = \JobScooper\JobPostingQuery::create()
                ->filterByJobSite($jobArray['job_site'])
                ->filterByJobSitePostID($jobArray['job_id'])
                ->findOneOrCreate();
        }

        $jobRecord->fromArray($jobArray);
        $jobRecord->save();
        return $jobRecord;

    }
    catch (Exception $ex)
    {
        handleException($ex);
    }

}

//
//function getUserJobMatchesForAppRun()
//{
//    $userObject = $GLOBALS['USERDATA']['configuration_settings']['user_details'];
//    $query = \JobScooper\UserJobMatchQuery::create()
//        ->filterByUserMatchStatus(null)
//        ->filterByUserSlug($userObject->getUserSlug())
////        ->filterBy("AppRunId", $GLOBALS['USERDATA']['configuration_settings']['app_run_id'])
//        ->joinWithJobPosting();
//
//    $results =  $query->find();
//    return $results->getData();
//}

function getAllUserMatchesNotNotified()
{
    $userObject = $GLOBALS['USERDATA']['configuration_settings']['user_details'];
    $query = \JobScooper\UserJobMatchQuery::create()
        ->filterByUserNotificationState(null)
        ->filterByUserSlug($userObject->getUserSlug())
        ->joinWithJobPosting();

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
        $arrOfJobs[$jobRecord->getJobPostingId()] = $jobRecord->getJobPosting()->toArray();
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
        LogLine("Loading and updating JobPostings from from json file '" . $filepath ."'", \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $data = loadJSON($filepath);

        $arrJobsArray = $data['jobslist'];

        foreach(array_keys($arrJobsArray) as $jobkey)
        {
            $item = $arrJobsArray[$jobkey];
            $jobRec = updateOrCreateJobPosting($item);
            $arrJobRecs[$jobkey] = $jobRec;
        }

    }

    return $arrJobRecs;
}


/******************************************************************************
 *
 *  User Object Helper Functions
 *
 ******************************************************************************/



function getUserBySlug($slug)
{
    LogLine("Searching for database user '" . $slug ."'", \Scooper\C__DISPLAY_NORMAL__);
    $user = \JobScooper\UserQuery::create()
        ->filterByPrimaryKey($slug)
        ->findOne();

    return $user;
}

function findOrCreateUser($value)
{
    $slug = cleanupSlugPart($value);

    LogLine("Searching for database user '" . $slug ."'", \Scooper\C__DISPLAY_NORMAL__);
    $user = \JobScooper\UserQuery::create()
        ->filterByPrimaryKey($slug)
        ->findOneOrCreate();

    return $user;
}

function getUserByName($username)
{
    $slug = cleanupSlugPart($username);
    return getUserBySlug($slug);

}
function updateOrCreateUser($arrUserDetails)
{
    if(is_null($arrUserDetails) || !is_array($arrUserDetails))
        return null;

    try {
        $userrec = findOrCreateUser($arrUserDetails['Name']);
    }
    catch (Exception $ex)
    {
        $userrec = new \JobScooper\User();
    }

    $userrec->fromArray($arrUserDetails);
    $userrec->save();
    return $userrec;
}


