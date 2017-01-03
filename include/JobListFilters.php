<?php
/**
 * Copyright 2014-16 Bryan Selner
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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }


//
// Jobs List Filter Functions
//
function isInterested_MarkedDuplicateAutomatically($var)
{
    if(substr_count($var['interested'], C__STR_TAG_DUPLICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__) > 0) return true;

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
    return isMarkedBlank($var) && wasJobPulledToday($var);
}

function onlyBadTitlesAndRoles($var)
{
    if(substr_count($var['interested'], C__STR_TAG_BAD_TITLE_POST__) > 0)
    {
        return true;
    };

    return false;
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


function isJobUpdatedTodayOrIsInterestedOrBlank($var)
{
    return (isJobUpdatedToday($var) && isMarkedBlank($var));
}

function isJobUpdatedTodayAndBlank($var)
{
    return (isJobUpdatedToday($var) && isMarked_InterestedOrBlank($var));
}

function isJobUpdatedTodayNotInterested($var)
{
    return (isJobUpdatedToday($var) && !isMarked_InterestedOrBlank($var));
}


function isMarkedBlank($var)
{
    $ret = false;
    if($var['interested'] == "")
        $ret = true;

    if(strlen($var['interested']) == 0)
        $ret = true;

    return $ret;
}

function isMarkedNotBlank($var)
{
    return !(isMarkedBlank($var));
}

function isMarked_InterestedOrBlank($var)
{
   $res = (isMarkedBlank($var) || !isMarked_NotInterested($var) );
   return $res;
}

// TODO: Test that isMarked_NotInterested() == isMarked_InterestedOrBlank().  They should match, no?
function isMarked_NotInterested($var)
{
    if(substr_count($var['interested'], "No ") <= 0) return false;
    return true;
}


function isMarked_NotInterestedAndNotBlank($var)
{
    return !(isMarkedBlank($var));
}

function isMarked_ManuallyNotInterested($var)
{
    if((substr_count($var['interested'], "No ") > 0) && isInterested_MarkedAutomatically($var) == false) return true;
    return false;
}

function isJobAutoUpdatable($var)
{
    if(isMarkedBlank($var) == true || (substr_count($var['interested'], "New") == 1) ) return true;

    return false;
}

function includeJobInFilteredList($var)
{
    $filterYes = false;

    if(isInterested_MarkedAutomatically($var) == true) $filterYes = true;
    if(isMarked_NotInterested($var) == true) $filterYes = true;

    return !$filterYes;

}

function isIncludedJobSite($var)
{
    return (array_search($var['job_site'], array_keys($GLOBALS['USERDATA']['configuration_settings']['included_sites'])) !== false);
}
