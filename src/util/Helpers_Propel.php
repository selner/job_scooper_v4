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
 *  New, Propel-Related util
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
    $con = \Propel\Runtime\Propel::getConnection();
    try {
        $expath = '/opt/sqlite/extensions/libsqlitefunctions.so';

        $sql2 = "SELECT load_extension('{$expath}');";
        $stmt = $con->prepare($sql2);
        $stmt->execute();
    } catch (Exception $ex) {
        handleException($ex,"FAILED to load math functions extension for SQLite with call: " . $sql2 . "   ERROR DETAILS: %s", true);
    }
}


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
                throw new InvalidArgumentException("Attempted to create a new job posting record without a valid jobsite value set.");

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


function getAllUserMatchesNotNotified($appRunId=null, $jobsiteKey=null)
{
    $userObject = $GLOBALS['USERDATA']['configuration_settings']['user_details'];

    $query = \JobScooper\DataAccess\UserJobMatchQuery::create()
        ->filterByUserNotificationState(array("not-ready", "ready"))
        ->filterByUserSlug($userObject->getUserSlug())
        ->joinWithJobPosting();

    if(!is_null($appRunId))
    {
        $query->filterBy("AppRunId",$appRunId);
    }

    if(!is_null($jobsiteKey))
    {
        $query->useJobPostingQuery()
            ->filterByJobSiteKey($jobsiteKey)
            ->endUse();
    }

    $results =  $query->find();
    return $results->getData();
}

function getAllSearchesThatWereIncluded()
{
    $arrSearchesRun = array();

    foreach($GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'] as $siteSearches)
    {
        if(is_array($siteSearches))
            foreach($siteSearches as $search)
            {
                $arrSearchesRun[$search->getUserSearchRunKey()] = $search;
            }
    }

    return $arrSearchesRun;
}

function getAllJobSitesThatWereLastRun()
{
    $sites = array();
    $runSearches = getAllSearchesThatWereLastRun();
    foreach($runSearches as $search)
        if(!in_array($search->getRunResultCode(), array("excluded", "not-run")))
            $sites[] = $search->getJobSiteKey();
    $ret = array_unique($sites);
    if(!is_array($ret))
        $ret = array();

    return $ret;
}

function getAllSearchesThatWereLastRun()
{
    $lastSearches = getAllSearchesThatWereIncluded();
    return $lastSearches;

//    $valSet = UserSearchRunTableMap::getValueSet(UserSearchRunTableMap::COL_RUN_RESULT);
//    $didRunVals = array(array_search("successful", $valSet),array_search("failed", $valSet));
//
//    $runSearches = array_filter($lastSearches, function ($var) use ($didRunVals) {
//        return in_array($var->getRunResultCode(), $didRunVals);
//    });
//
//    return $runSearches;

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
        LogLine("Loading and updating JobPostings from from json file '" . $filepath ."'", \C__DISPLAY_ITEM_DETAIL__);
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


/******************************************************************************
 *
 *  User Object Helper Functions
 *
 ******************************************************************************/



function getUserBySlug($slug)
{
    LogLine("Searching for database user '" . $slug ."'", \C__DISPLAY_NORMAL__);
    $user = \JobScooper\DataAccess\UserQuery::create()
        ->filterByPrimaryKey($slug)
        ->findOne();

    return $user;
}

function findOrCreateUser($value)
{
    $slug = cleanupSlugPart($value);

    LogLine("Searching for database user '" . $slug ."'", \C__DISPLAY_NORMAL__);
    $user = \JobScooper\DataAccess\UserQuery::create()
        ->filterByPrimaryKey($slug)
        ->findOneOrCreate();

    return $user;
}

function getJobSitePluginClassName($jobsite)
{
    $plugin_classname = null;

    if (!is_null($jobsite)) {
        $slug = cleanupSlugPart($jobsite);
        if (!array_key_exists($slug, $GLOBALS['JOBSITE_PLUGINS']) &&
            !is_null($GLOBALS['JOBSITE_PLUGINS'][$slug]['class_name'])) {
            return $GLOBALS['JOBSITE_PLUGINS'][$slug]['class_name'];
        } else {
            $classnamematch = "plugin" . $slug;
            $classList = get_declared_classes();
            foreach ($classList as $class) {
                if (strcasecmp($class, $classnamematch) == 0) {
                    return $class;
                }
            }
        }
    }

    return null;
}


function findOrCreateJobSitePlugin($jobsite)
{
    $slug = cleanupSlugPart($jobsite);

    if(!is_array($GLOBALS['JOBSITE_PLUGINS']))
        $GLOBALS['JOBSITE_PLUGINS'] = array();

    if (!array_key_exists($slug, $GLOBALS['JOBSITE_PLUGINS'])) {
        $GLOBALS['JOBSITE_PLUGINS'][$slug] = array('name' => $jobsite, 'class_name' => getJobSitePluginClassName($slug), 'jobsite_db_object' => null, 'include_in_run' => false, 'other_settings' => []);
    }

    if (is_null($GLOBALS['JOBSITE_PLUGINS'][$slug]['jobsite_db_object']))
    {
        $GLOBALS['JOBSITE_PLUGINS'][$slug]['jobsite_db_object'] = \JobScooper\DataAccess\JobSitePluginQuery::create()
            ->filterByPrimaryKey($slug)
            ->findOneOrCreate();

        if($GLOBALS['JOBSITE_PLUGINS'][$slug]['jobsite_db_object']->isNew()) {
            $GLOBALS['JOBSITE_PLUGINS'][$slug]['jobsite_db_object']->setJobSiteKey($slug);
            $GLOBALS['JOBSITE_PLUGINS'][$slug]['jobsite_db_object']->save();
        }
    }

    return $GLOBALS['JOBSITE_PLUGINS'][$slug]['jobsite_db_object'];
}

function getPluginObjectForJobSite($jobsite)
{
    $objJobSite = findOrCreateJobSitePlugin($jobsite);
    return $objJobSite->getJobSitePluginObject();
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
        $userrec = new \JobScooper\DataAccess\User();
    }

    $userrec->fromArray($arrUserDetails);
    $userrec->save();
    return $userrec;
}



function findOrCreateUserSearchRun($searchKey, $jobsiteKey, $locationKey="any-location", $copyFrom=null)
{
    $userObject = $GLOBALS['USERDATA']['configuration_settings']['user_details'];
    $userSlug = $userObject->getUserSlug();

    if(isDebug())
        LogLine("Searching for user '{$userSlug} / jobsite {$jobsiteKey} / search '{$searchKey} / location '{$locationKey}'...", \C__DISPLAY_NORMAL__);

    $search = \JobScooper\DataAccess\UserSearchRunQuery::create()
        ->filterByUserSlug($userSlug)
        ->filterByJobSiteKey($jobsiteKey)
        ->filterBySearchKey($searchKey)
        ->useGeoLocationQuery()
            ->filterByGeoLocationKey($locationKey)
        ->endUse()
        ->findOne();

    if (!$search) {
        if(isDebug())
            LogLine("Search Not Found -- Creating New User Search Run for user '{$userSlug} / plugin {$jobsiteKey} / search '{$searchKey} / location '{$locationKey}'...", \C__DISPLAY_WARNING__);
        $search = new \JobScooper\DataAccess\UserSearchRun();
    }
    else
    {
        if(isDebug())
            LogLine("Found matching UserSearchRun ID # " . $search->getUserSearchRunId() ." for {$userSlug} / jobsite {$jobsiteKey} / search {$searchKey} / location {$locationKey}...", \C__DISPLAY_NORMAL__);
    }


    if (!is_null($copyFrom))
    {
        $allFields = \JobScooper\DataAccess\Map\UserSearchRunTableMap::getTableMap(\Propel\Runtime\Map\TableMap::TYPE_PHPNAME);
        foreach($allFields->getColumns() as $v) {
            $field = $v->getPhpName();
            if (!in_array($field, array('UserSearchRunId', 'UserSlug', 'RunResult', 'RunErrorDetails')))
            {
                $getCall = "get" . $field;
                $setCall = "set" . $field;
                $curval = call_user_func(array($copyFrom, $getCall));
                call_user_func(array($search, $setCall), $curval);
            }
        }
    }

    $search->setSearchKey($searchKey);
    $search->setJobSiteKey($jobsiteKey);
    $search->setUserSlug($userSlug);
    $locId = $search->getGeoLocationId();
    if(is_null($locId))
    {
        $loc = \JobScooper\DataAccess\GeoLocationQuery::create()
            ->findOneByGeoLocationKey($locationKey);
        $search->setGeoLocation($loc);
    }

    $search->save();
    return $search;
}

function cleanupTextValue($v)
{
    if(is_null($v) || strlen($v) == 0 || !is_string($v))
        return $v;

    $v = html_entity_decode($v);
    $v = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $v);
    $v = clean_utf8($v);
    $v = trim($v);

    return $v;
}
