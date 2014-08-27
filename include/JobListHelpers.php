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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }

require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');

const C__STR_TAG_AUTOMARKEDJOB__ = "[auto-marked]";
const C__STR_TAG_DUPLICATE_POST__ = "No (Duplicate Job Post?)";
const C__STR_TAG_BAD_TITLE_POST__ = "No (Bad Title & Role)";
const C__STR_TAG_NOT_STRICT_TITLE_MATCH__ = "No (Not a Strict Title Match)";
const C__STR_TAG_NOT_EXACT_TITLE_MATCH__ = "No (Not an Exact Title Match)";

const C__STR_TAG_EXCLUDED_TITLE_REGEX = 'No (Title Excluded Via RegEx)';

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

function isInterested_TitleExcludedViaRegex($var)
{
    if(substr_count($var['interested'], C__STR_TAG_EXCLUDED_TITLE_REGEX) > 0)
    {
        return true;
    };

    return false;
}

function isNewJobToday_Interested_IsBlank($var)
{
    return isMarkedInterested_IsBlank($var) && wasJobPulledToday($var);
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
    return (strcasecmp($var['date_pulled'], \Scooper\getTodayAsString()) == 0);
}


function isJobUpdatedToday($var)
{
    return (strcasecmp($var['date_last_updated'], \Scooper\getTodayAsString()) == 0);
}


function isJobUpdatedTodayOrIsInterestedOrBlank($var)
{
    return (isJobUpdatedToday($var) && isMarked_InterestedOrBlank($var));
}

function isJobUpdatedTodayNotInterested($var)
{
    return (isJobUpdatedToday($var) && !isMarked_InterestedOrBlank($var));
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



function combineTextAllChildren($node, $fRecursed = false)
{

    $retStr = "";
    if($node->hasChildNodes())
    {
        $retStr = combineTextAllChildren($node->firstChild(), true);
    }

    if($node->plaintext != null && $fRecursed == false)
    {
        $retStr = \Scooper\strScrub($node->plaintext . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE  );
    }
    return $retStr;


}

function sortByCountDesc($a, $b)
{
    $al = $a["updated_today"];
    $bl = $b["updated_today"];
    if ($al == $bl) {
        return 0;
    }
    return ($al < $bl) ? +1 : -1;
}

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
    $strKey = \Scooper\strScrub($job['job_site'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS);

    // For craigslist, they change IDs on every post, so deduping that way doesn't help
    // much.  Instead, let's dedupe for Craigslist by using the role title and the jobsite
    // (Company doesn't usually get filled out either with them.)
    if(strcasecmp($strKey, "craigslist") == 0)
    {
        $strKey = $strKey . "-" . \Scooper\strScrub($job['job_title'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS );
    }
    if($job['job_id'] != null && $job['job_id'] != "")
    {
        $strKey = $strKey . "-" . \Scooper\strScrub($job['job_id'], REPLACE_SPACES_WITH_HYPHENS | REMOVE_PUNCT | HTML_DECODE | LOWERCASE);
    }
    else
    {
        $strKey = $strKey . "-" . \Scooper\strScrub($job['company'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS );
        $strKey = $strKey . "-" . \Scooper\strScrub($job['job_title'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS );
    }
    return $strKey;
*/


}

function countAssociativeArrayValues($arrToCount)
{
    if($arrToCount == null || !is_array($arrToCount))
    {
        return 0;
    }

    $count = 0;
    foreach($arrToCount as $item)
    {
        $count = $count + 1;
    }

    $arrValues = array_values($arrToCount);
    $nValues = count($arrValues);
    return max($nValues, $count);
//    $arrKeys = array_keys($arrToCount);
//    $nKeys = count($arrKeys);

//    return max($nKeys, $nValues);
}

function countJobRecords($arrJobs)
{
    return countAssociativeArrayValues($arrJobs);
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

    $jobToAdd = \Scooper\array_copy($job);



    if(isset($arrJobsListToUpdate[$job['key_jobsite_siteid']]))
    {
        $jobToAdd = getMergedJobRecord($arrJobsListToUpdate[$job['key_jobsite_siteid']], $job);
    }


    $arrJobsListToUpdate[$job['key_jobsite_siteid']] = $jobToAdd;
}



function updateJobColumn(&$job, $newJob, $strColumn, $fAllowEmptyValueOverwrite = false)
{
    $prevJob = \Scooper\array_copy($job);

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
        if(strcasecmp(\Scooper\strScrub($job[$strColumn]), \Scooper\strScrub($newJob[$strColumn])) != 0)
        {
            $job[$strColumn] = $newJob[$strColumn];
            $job['notes'] .= PHP_EOL.$strColumn . ": old[" . $prevJob[$strColumn]."], new[" .$job[$strColumn]."]".PHP_EOL;
        }
    }

}

function getArrayItemDetailsAsString($arrItem, $key, $fIsFirstItem = true, $strDelimiter = "", $strIntro = "", $fIncludeKey = true)
{
    $strReturn = "";

    if(isset($arrItem[$key]))
    {
        $val = $arrItem[$key];
        if(is_string($val) && strlen($val) > 0)
        {
            $strVal = $val;
        }
        elseif(is_array($val) && !(\Scooper\is_array_multidimensional($val)))
        {
            $strVal = join(" | ", $val);
        }
        else
        {
            $strVal = var_export($val, true);
        }

        if($fIsFirstItem == true)
        {
            $strReturn = $strIntro;
        }
        else
        {
            $strReturn .= $strDelimiter;
        }
        if($fIncludeKey == true) {
            $strReturn .= $key . '=['.$strVal.']';
        } else {
            $strReturn .= $strVal;
        }

    }


    return $strReturn;
}

function getArrayValuesAsString($arrDetails, $strDelimiter = ", ", $strIntro = "", $fIncludeKey = true)
{
    $strReturn = "";

    if(isset($arrDetails) && is_array($arrDetails))
    {
        foreach(array_keys($arrDetails) as $key)
        {
            $strReturn .= getArrayItemDetailsAsString($arrDetails, $key, (strlen($strReturn) <= 0), $strDelimiter, $strIntro, $fIncludeKey);
        }
    }

    return $strReturn;
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
    $mergedJob = \Scooper\array_copy($prevJobRecord);

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
    $mergedJob['date_last_updated'] = \Scooper\getTodayAsString();

    return $mergedJob;

}

/**
 * Allows multiple expressions to be tested on one string.
 * This will return a boolean, however you may want to alter this.
 *
 * @author William Jaspers, IV <wjaspers4@gmail.com>
 * @created 2009-02-27 17:00:00 +6:00:00 GMT
 * @access public
 *
 * @param array $patterns An array of expressions to be tested.
 * @param String $subject The data to test.
 * @param array $findings Optional argument to store our results.
 * @param mixed $flags Pass-thru argument to allow normal flags to apply to all tested expressions.
 * @param array $errors A storage bin for errors
 *
 * @returns bool Whether or not errors occurred.
 */
function preg_match_multiple(
    array $patterns=array(),
    $subject=null,
    &$findings=array(),
    $flags=false,
    &$errors=array()
) {
    foreach( $patterns as $name => $pattern )
    {
        if( 1 <= preg_match_all( $pattern, $subject, $found, $flags ) )
        {
            $findings[$name] = $found;
        } else
        {
            if( PREG_NO_ERROR !== ( $code = preg_last_error() ))
            {
                $errors[$name] = $code;
            } else $findings[$name] = array();
        }
    }
    return (0===sizeof($errors));
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