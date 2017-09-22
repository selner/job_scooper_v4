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

function isMarkedNotBlank($var)
{
    return !(isSuccessfulUserMatch($var));
}

function isUserMatchValueOf($var, $value)
{
    if (method_exists($var, "getUserMatchStatus") === true)
    {
        $matchStatus = $var->getUserMatchStatus();
        if(strcasecmp($matchStatus, $value) == 0)
            return true;
    }
    else
    {
        if(array_key_exists("interested", $var) === true)
            if(substr_count($var['interested'], $value) <= 0) return false;
    }
    return false;
}

function isSuccessfulUserMatch($var)
{
    if (method_exists($var, "getUserMatchStatus") === true)
    {
//        $valSet = \JobScooper\Map\UserJobMatchTableMap::getValueSet(\JobScooper\Map\UserJobMatchTableMap::COL_USER_MATCH);
//        $didMatchVals = array(array_search("none", $valSet),array_search("include-match", $valSet));
//
        $matchStatus = $var->getUserMatchStatus();
        if(in_array($matchStatus, array("none", "include-match")))
            return true;
    }
    else
    {
        if(array_key_exists("interested", $var) === true)
            if(strlen($var['interested']) == 0) return true;
    }
    return false;
}

function isNotUserJobMatch($var)
{
    return isUserMatchValueOf($var, "exclude-match");
}

function isNotUserJobMatchAndNotBlank($var)
{
    return !(isSuccessfulUserMatch($var));
}

function isJobAutoUpdatable($var)
{
    return isSuccessfulUserMatch($var);
}

function includeJobInFilteredList($var)
{
    return !(isNotUserJobMatchAndNotBlank($var) == true);

}

function isIncludedJobSite($var)
{
    return (in_array(strtolower($var->getJobPosting()->getJobSite()), $GLOBALS['USERDATA']['configuration_settings']['included_sites']) === true);
}




function sortByErrorThenCount($a, $b)
{

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

