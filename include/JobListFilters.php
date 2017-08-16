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


//
// Jobs List Filter Functions
//
function isInterested_MarkedDuplicateAutomatically($var)
{
    if(substr_count($var->getUserMatchStatus(), C__STR_TAG_DUPLICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__) > 0) return true;

    return false;
}

function isInterested_MarkedAutomatically($var)
{
    if(substr_count($var->getUserMatchStatus(), C__STR_TAG_AUTOMARKEDJOB__) > 0)
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
    if(substr_count($var->getUserMatchStatus(), C__STR_TAG_BAD_TITLE_POST__) > 0)
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
    return (isJobUpdatedToday($var) && isMarked_InterestedOrBlank($var));
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
    if(is_null($var->getUserMatchStatus()) || $var->getUserMatchStatus() == "")
        return true;

    return false;
}

function isMarkedNotBlank($var)
{
    return !(isMarkedBlank($var));
}

function isMarked_InterestedOrBlank($var)
{
   $res = (isMarkedBlank($var) || (isMarked_NotInterested($var) === false));
   return $res;
}

// TODO: Test that isMarked_NotInterested() == isMarked_InterestedOrBlank().  They should match, no?
function isMarked_NotInterested($var)
{
    if(substr_count($var->getUserMatchStatus(), "No ") <= 0) return false;
    return true;
}

function isMarked_NotInterestedAndNotBlank($var)
{
    return !(isMarkedBlank($var));
}

function isMarked_ManuallyNotInterested($var)
{
    if((substr_count($var->getUserMatchStatus(), "No ") > 0) && isInterested_MarkedAutomatically($var) == false) return true;
    return false;
}

function isJobAutoUpdatable($var)
{
    if(isMarkedBlank($var) == true || (substr_count($var->getUserMatchStatus(), "New") == 1) ) return true;

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
    return (in_array(strtolower($var['job_site']), $GLOBALS['USERDATA']['configuration_settings']['included_sites']) === true);
}

function isNotExcludedJobSite($var)
{
    return (!in_array(strtolower($var['job_site']), $GLOBALS['USERDATA']['configuration_settings']['excluded_sites']) === true);
}


function sortByErrorThenCount($a, $b)
{
    $rank = array($a['name'] => 0, $b['name'] => 0);

    if ($a['had_error'] > $b['had_error']) {
        return 1;
    } elseif ($a['had_error'] < $b['had_error']) {
        return -1;
    }

    if ($a["total_listings"] == $b["total_listings"]) {
        return 0;
    }

    return ($a["total_listings"] < $b["total_listings"]) ? +1 : -1;
}

function sortJobsListByCompanyRole(&$arrJobList)
{

    if (countJobRecords($arrJobList) > 0) {
        $arrFinalJobIDs_SortedByCompanyRole = array();
        $finalJobIDs_CompanyRole = array_column($arrJobList, 'key_company_role', 'key_jobsite_siteid');
        foreach (array_keys($finalJobIDs_CompanyRole) as $key) {
            // Need to add uniq key of job site id to the end or it will collapse duplicate job titles that
            // are actually multiple open posts
            $arrFinalJobIDs_SortedByCompanyRole[$finalJobIDs_CompanyRole[$key] . "-" . $key] = $key;
        }

        ksort($arrFinalJobIDs_SortedByCompanyRole);
        $arrFinalJobs_SortedByCompanyRole = array();
        foreach ($arrFinalJobIDs_SortedByCompanyRole as $jobid) {
            $arrFinalJobs_SortedByCompanyRole[$jobid] = $arrJobList[$jobid];
        }
        $arrJobList = $arrFinalJobs_SortedByCompanyRole;
    }

}

