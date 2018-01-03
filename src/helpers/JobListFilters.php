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



function isUserJobMatch(\JobScooper\DataAccess\UserJobMatch $var)
{
    assert(method_exists($var, "getIsJobMatch") === true);
	return (!empty($var->getIsJobMatch()) && $var->getIsJobMatch() === true);
}

function isNotUserJobMatch(\JobScooper\DataAccess\UserJobMatch $var)
{
	assert(method_exists($var, "getIsJobMatch") === true);
    return (empty($var->getIsJobMatch()) || $var->getIsJobMatch() !== true);
}

function isExcluded(\JobScooper\DataAccess\UserJobMatch $var)
{
    assert(method_exists($var, "getIsExcluded") === true);

	return (!empty($var->getIsExcluded()) && $var->getIsExcluded() === true);

}

function isUserJobMatchButExcluded(\JobScooper\DataAccess\UserJobMatch $var)
{
    assert(method_exists($var, "getIsExcluded") === true);

	return ((!empty($var->getIsExcluded()) && $var->getIsExcluded() === true) && (!empty($var->getIsJobMatch()) && $var->getIsJobMatch() === true));
}

function isUserJobMatchAndNotExcluded(\JobScooper\DataAccess\UserJobMatch $var)
{
	return ((empty($var->getIsExcluded()) || $var->getIsExcluded() !== true) && (!empty($var->isJobMatch()) && $var->getIsJobMatch() === true));
}


//
// Jobs List Sort Functions
//

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

