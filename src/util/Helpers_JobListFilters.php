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
require_once __ROOT__ . "/bootstrap.php";


function isSuccessfulUserJobMatch($var)
{
    assert(method_exists($var, "getIsJobMatch") === true);
    return $var->getIsJobMatch();
}

function isNotUserJobMatch($var)
{
    return !isSuccessfulUserJobMatch($var);
}


function isExcludedJob($var)
{
    assert(method_exists($var, "setIsExcluded") === true);
    return $var->setIsExcluded();
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
