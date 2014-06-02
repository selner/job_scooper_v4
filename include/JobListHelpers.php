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
function wasMarkedDuplicateAutomatically($var)
{
    if(substr_count($var['interested'], C__STR_TAG_DULICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__) > 0) return true;

    return false;
}

function wasInterestedMarkedAutomatically($var)
{
    $GLOBALS['CNT'][$var['interested']] +=1;
    if(substr_count($var['interested'], C__STR_TAG_AUTOMARKEDJOB__) > 0)
    {
        return true;
    };

    return false;
}

function isNewJobAddedToday($var)
{
    return isMarkedInterested_IsBlank($var) && isJobPulledToday($var);
}

function isJobPulledToday($var)
{
    return (strcasecmp($var['date_pulled'], getTodayAsString()) == 0);
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
    if(substr_count($var['interested'], "No ") > 0 || (substr_count($var['interested'], "New") == 1)) return false;
    return true;
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
    if((substr_count($var['interested'], "No ") > 0) && wasInterestedMarkedAutomatically($var) == false) return true;
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

    if(wasInterestedMarkedAutomatically($var) == true) $filterYes = true;
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

    $strKey = strScrub($job['job_site'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACES_SPACES_WITH_HYPHENS);

    // For craigslist, they change IDs on every post, so deduping that way doesn't help
    // much.  Instead, let's dedupe for Craigslist by using the role title and the jobsite
    // (Company doesn't usually get filled out either with them.)
    if(strcasecmp($strKey, "craigslist") == 0)
    {
        $strKey = $strKey . "-" . strScrub($job['job_title'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACES_SPACES_WITH_HYPHENS );
    }
    if($job['job_id'] != null && $job['job_id'] != "")
    {
        $strKey = $strKey . "-" . strScrub($job['job_id'], REPLACES_SPACES_WITH_HYPHENS | REMOVE_PUNCT | HTML_DECODE | LOWERCASE);
    }
    else
    {
        $strKey = $strKey . "-" . strScrub($job['company'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACES_SPACES_WITH_HYPHENS );
        $strKey = $strKey . "-" . strScrub($job['job_title'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACES_SPACES_WITH_HYPHENS );
    }

    return $strKey;
}

function addJobsToJobsList(&$arrJobsListToUpdate, $arrAddJobs)
{
    $nstartcount = count($arrJobsListToUpdate);
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

    $strKey = getArrayKeyValueForJob($job);
    $arrJobsListToUpdate[$strKey] = $job;
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