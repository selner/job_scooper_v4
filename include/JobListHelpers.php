<?php
/**
 * Copyright 2014 Bryan Selner
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
require_once dirname(__FILE__) . '/Options.php';
require_once dirname(__FILE__) . '/ClassJobsSitePluginCommon.php';

const C__JOB_PAGECOUNT_NOTAPPLICABLE__ = -1;
const C__JOB_ITEMCOUNT_UNKNOWN__ = 11111;



//
// Jobs List Filter Functions
//
function isInterested_MarkedDuplicateAutomatically($var)
{
    if(substr_count($var['interested'], C__STR_TAG_DULICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__) > 0) return true;

    return false;
}

function isInterested_MarkedAutomatically($var)
{
    if(substr_count($var['interested'], C__STR_TAG_AUTOMARKEDJOB__) > 0)
    {
        return true;
    };

    return false;
}

function isNewJobToday_Interested_IsBlank($var)
{
    return isMarkedInterested_IsBlank($var) && wasJobPulledToday($var);
}

function isNewJobToday_Interested_IsNo($var)
{
    return isMarked_NotInterested($var) && wasJobPulledToday($var);
}

function wasJobPulledToday($var)
{
    return (strcasecmp($var['date_pulled'], getTodayAsString()) == 0);
}


function isJobUpdatedToday($var)
{
    return (strcasecmp($var['date_last_updated'], getTodayAsString()) == 0);
}


function isMarkedInterested_IsBlank($var)
{
    if($var['interested'] == null || trim($var['interested']) =="" || strlen(trim($var['interested']))==0)
    {
        return true;
    }
    return false;
}


function isMarked_InterestedOrBlank($var)
{
   return (!isMarked_NotInterested($var) || isMarkedInterested_IsBlank($var));
}

// TODO: Test that isMarked_NotInterested() == isMarked_InterestedOrBlank().  They should match, no?
function isMarked_NotInterested($var)
{
    if(substr_count($var['interested'], "No ") <= 0) return false;
    return true;
}


function isMarked_NotInterestedAndNotBlank($var)
{
    return !(isMarkedInterested_IsBlank($var));
}

function isMarked_ManuallyNotInterested($var)
{
    if((substr_count($var['interested'], "No ") > 0) && isInterested_MarkedAutomatically($var) == false) return true;
    return false;
}

function isJobAutoUpdatable($var)
{
    if(isMarkedInterested_IsBlank($var) == true || (substr_count($var['interested'], "New") == 1) ) return true;

    return false;
}

function includeJobInFilteredList($var)
{
    $filterYes = false;

    if(isInterested_MarkedAutomatically($var) == true) $filterYes = true;
    if(isMarked_NotInterested($var) == true) $filterYes = true;

    return !$filterYes;

}
/*
function isJob_InWashingtonState_or_UnknownLocation($var)
{
    $arrCities = array("seattle", "redmond", "bothell", )

    $tempLocation = strTrimAndLower($var['location']);
    if((substr_count($tempLocation, "wa ") > 0) || (substr_count($tempLocation, "washington") > 0)) { $fRet = true; }

    if((substr_count($tempLocation, "wa ") > 0) || (substr_count($tempLocation, "washington") > 0)) { $fRet = true; }


    return false;
}*/



/**
 * TODO:  DOC
 *
 *
 * @param  string TODO DOC
 * @param  string TODO DOC
 * @return string TODO DOC
 */

function getArrayKeyValueForJob($job)
{

    return $job['key_jobsite_siteid'];

/*
    $strKey = strScrub($job['job_site'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS);

    // For craigslist, they change IDs on every post, so deduping that way doesn't help
    // much.  Instead, let's dedupe for Craigslist by using the role title and the jobsite
    // (Company doesn't usually get filled out either with them.)
    if(strcasecmp($strKey, "craigslist") == 0)
    {
        $strKey = $strKey . "-" . strScrub($job['job_title'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS );
    }
    if($job['job_id'] != null && $job['job_id'] != "")
    {
        $strKey = $strKey . "-" . strScrub($job['job_id'], REPLACE_SPACES_WITH_HYPHENS | REMOVE_PUNCT | HTML_DECODE | LOWERCASE);
    }
    else
    {
        $strKey = $strKey . "-" . strScrub($job['company'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS );
        $strKey = $strKey . "-" . strScrub($job['job_title'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS );
    }
    return $strKey;
*/


}

function addJobsToJobsList(&$arrJobsListToUpdate, $arrAddJobs)
{
    if($arrAddJobs == null) return;

    if(!is_array($arrAddJobs) || count($arrAddJobs) == 0)
    {
        // skip. no jobs to add
        return;
    }
    if($arrJobsListToUpdate == null) $arrJobsListToUpdate = array();

    foreach($arrAddJobs as $jobRecord)
    {
        addJobToJobsList($arrJobsListToUpdate, $jobRecord);
    }

}


function addJobToJobsList(&$arrJobsListToUpdate, $job)
{
    if($arrJobsListToUpdate == null) $arrJobsListToUpdate = array();

    $jobToAdd = array_copy($job);



    if($arrJobsListToUpdate[$job['key_jobsite_siteid']] != null)
    {
        $jobToAdd = getMergedJobRecord($arrJobsListToUpdate[$job['key_jobsite_siteid']], $job);
    }

    if($arrJobsListToUpdate[$job['key_jobsite_siteid']] && $arrJobsListToUpdate[$job['key_jobsite_siteid']]['company'] == "groupon")
    {
        var_dump('$prevJobRecord', $arrJobsListToUpdate[$job['key_jobsite_siteid']]);
        var_dump('$newerJobRecord', $job);
        var_dump('$jobToAdd', $jobToAdd);
    }

    $arrJobsListToUpdate[$job['key_jobsite_siteid']] = $jobToAdd;
}



function updateJobColumn(&$job, $newJob, $strColumn, $fAllowEmptyValueOverwrite = false)
{
    $prevJob = array_copy($job);

    if(strlen($job[$strColumn]) == 0)
    {
        $job[$strColumn] = $newJob[$strColumn];
    }
    elseif(strlen($newJob[$strColumn]) == 0)
    {
        if($fAllowEmptyValueOverwrite == true)
        {
            $job[$strColumn] = $newJob[$strColumn];
            $job['notes'] .= $strColumn . " value '" . $prevJob[$strColumn]."' removed.'".PHP_EOL;
        }
    }
    else
    {
        if(strcasecmp(strScrub($job[$strColumn]), strScrub($newJob[$strColumn])) != 0)
        {
            $job[$strColumn] = $newJob[$strColumn];
            $job['notes'] .= PHP_EOL.$strColumn . ": old[" . $prevJob[$strColumn]."], new[" .$job[$strColumn]."]".PHP_EOL;
        }
    }

}

function updateJobRecord($prevJobRecord, $jobRecordChanges)
{

    $ret = getMergedJobRecord($prevJobRecord, $jobRecordChanges);
}

function getMergedJobRecord($prevJobRecord, $newerJobRecord)
{
    if($prevJobRecord['key_jobsite_siteid'] == $newerJobRecord['key_jobsite_siteid'])
    {
        return $prevJobRecord; // don't update yourself.
    }

    // Since we already had a job record for this particular listing,
    // we'll merge the new info into the old one.  For most fields,
    // the latter (aka the passed in $job) values will win.  For some
    // fields such as Notes, the values will be combined.
    //
    $mergedJob = array_copy($prevJobRecord);

    updateJobColumn($mergedJob, $newerJobRecord, 'company', false);
    updateJobColumn($mergedJob, $newerJobRecord, 'job_title', false);
//    updateJobColumn($mergedJob, $newerJobRecord, 'location', false);
//    updateJobColumn($mergedJob, $newerJobRecord, 'job_site_category', false);
//    updateJobColumn($mergedJob, $newerJobRecord, 'date_pulled', false);
//    updateJobColumn($mergedJob, $newerJobRecord, 'job_post_url', false);
//    updateJobColumn($mergedJob, $newerJobRecord, 'job_site_date', false);

    if(!isMarked_InterestedOrBlank($prevJobRecord))
    {
        updateJobColumn($mergedJob, $newerJobRecord, 'interested', false);
        updateJobColumn($mergedJob, $newerJobRecord, 'status', false);
    }


    $mergedJob['notes'] = $newerJobRecord['notes'] . ' ' . $mergedJob['notes'];
    $mergedJob['date_last_updated'] = getTodayAsString();

    return $mergedJob;

}


function getDefaultJobsOutputFileName($strFilePrefix = '', $strBase = '', $strExt = '')
{
    $strFilename = '';
    if(strlen($strFilePrefix) > 0) $strFilename .= $strFilePrefix . "_";
    $date=date_create(null);
    $strFilename .= date_format($date,"Y-m-d_Hi");

    if(strlen($strBase) > 0) $strFilename .= "_" . $strBase;
    if(strlen($strExt) > 0) $strFilename .= "." . $strExt;

    return $strFilename;
}

?>