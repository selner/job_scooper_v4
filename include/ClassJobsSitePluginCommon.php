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
require_once dirname(__FILE__) . '/../include/Options.php';

const C__STR_TAG_AUTOMARKEDJOB__ = "[auto-marked]";
const C__STR_TAG_DULICATE_POST__ = "No (Duplicate Job Post?)";
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

class ClassJobsSitePluginCommon
{

    private $arrKeysForDeduping = array('job_site', 'job_id');

    private $_bitFlags = null;
    protected $strOutputFolder = "";

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function __construct($bitFlags = null)
    {
        $this->_bitFlags = $bitFlags;
    }

     function getMyBitFlags() { return $this->_bitFlags; }

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function getEmptyItemsArray()
    {
        return array(
            'job_site' => '',
            'job_id' => '',
            'company' => '',
            'job_title' => '',
            'interested' => '',
            'notes' => '',
            'status' => '',
            'last_status_update' => '',
            'date_pulled' => '',
            'job_post_url' => '',
            'location' => '',
            'job_site_category' => '',
            'job_site_date' =>''        );
    }

    function normalizeJobList($arrJobList)
    {
        $arrRetList = null;

        if($arrJobList == null) return null;

        foreach($arrJobList as $job)
        {
            $jobNorm = $this->normalizeItem($job);
            addJobToJobsList($arrRetList, $jobNorm);
        }
        return $arrRetList;
    }

    function normalizeItem($arrItem)
    {
        $retArrNormalized = $arrItem;


        $retArrNormalized ['job_site'] = strScrub($retArrNormalized['job_site'], DEFAULT_SCRUB);
        $retArrNormalized ['job_id'] = strScrub($retArrNormalized['job_id']);
        $retArrNormalized ['job_title'] = strScrub($retArrNormalized['job_title'], DEFAULT_SCRUB);

        $retArrNormalized ['job_site_category'] = strScrub($retArrNormalized['job_site_category'], REMOVE_EXTRA_WHITESPACE | HTML_DECODE | LOWERCASE);
        $retArrNormalized ['job_site_date'] = strScrub($retArrNormalized['job_site_date'], REMOVE_EXTRA_WHITESPACE | LOWERCASE | HTML_DECODE );
        $retArrNormalized ['job_post_url'] = trim($retArrNormalized['job_post_url']); // DO NOT LOWER, BREAKS URLS
        $retArrNormalized ['location'] = strScrub($retArrNormalized['location'], REMOVE_EXTRA_WHITESPACE | HTML_DECODE | LOWERCASE);
        $retArrNormalized ['brief'] = strScrub($retArrNormalized['brief'], REMOVE_EXTRA_WHITESPACE | HTML_DECODE | LOWERCASE);

        $retArrNormalized ['company'] = strScrub($retArrNormalized['company'], DEFAULT_SCRUB );

        // Remove common company name extenisons like "Corporation" or "Inc." so we have
        // a higher match likelihood
        $retArrNormalized ['company'] = str_replace(array(" corporation", " corp", " inc", " llc"), "", $retArrNormalized['company']);

        switch($retArrNormalized ['company'])
        {
            case "amazon":
            case "amazon com":
            case "a2z":
            case "amazon corporate llc":
            case "amazon Web Services":
            case "amazon fulfillment services":
            case "amazonwebservices":
            case "amazon (seattle)":
                $retArrNormalized ['company'] = "amazon";
                break;

            case "market leader":
            case "market leader inc":
            case "market leader llc":
                $retArrNormalized ['company'] = "market leader";
                break;


            case "walt disney parks &amp resorts online":
            case "walt disney parks resorts online":
            case "the walt disney studios":
            case "walt disney studios":
            case "the walt disney company corporate":
            case "the walt disney company":
            case "disney parks &amp resorts":
            case "disney parks resorts":
            case "walt disney parks resorts":
            case "walt disney parks &amp resorts":
            case "walt disney parks resorts careers":
            case "walt disney parks &amp resorts careers":
            case "disney":
                $retArrNormalized ['company'] = "disney";
                break;

        }

        return $retArrNormalized;
    }

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function setOutputFolder($strPath)
    {
        if($strPath == "") $strPath = null;

        $this->strOutputFolder = $strPath;
    }


     function getTodayAsString()
    {
        return date("Y-m-d");
    }



    /**
     * Initializes the global list of titles we will automatically mark
     * as "not interested" in the final results set.
     */
    function _loadTitlesToFilter_()
    {
        $fTitlesLoaded = false;
        $arrTitleFileDetails = $GLOBALS['titles_file_details'];
        $strFileName = "";

        if($GLOBALS['titles_to_filter'] != null && count($GLOBALS['titles_to_filter']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            $fTitlesLoaded = true;
            __debug__printLine("Using previously loaded " . count($GLOBALS['titles_to_filter']) . " titles to exclude." , C__DISPLAY_ITEM_DETAIL__);
            return;
        }
        else if($arrTitleFileDetails != null)
        {
            $strFileName = $arrTitleFileDetails ['full_file_path'];
            if(file_exists($strFileName ) && is_file($strFileName ))
            {
                __debug__printLine("Loading job titles to filter from ".$strFileName."." , C__DISPLAY_ITEM_DETAIL__);
                $classCSVFile = new SimpleScooterCSVFileClass($strFileName , 'r');
                $arrTitlesTemp = $classCSVFile->readAllRecords(true);
                __debug__printLine(count($arrTitlesTemp) . " titles found in the source file that will be automatically filtered from job listings." , C__DISPLAY_ITEM_DETAIL__);

                //
                // Add each title we found in the file to our list in this class, setting the key for
                // each record to be equal to the job title so we can do a fast lookup later
                //
                $GLOBALS['titles_to_filter'] = array();
                foreach($arrTitlesTemp as $titleRecord)
                {
                    $strTitleKey = strScrub($titleRecord['job_title']);
                    $titleRecord['job_title'] = strScrub($titleRecord['job_title']);

                    $GLOBALS['titles_to_filter'][$strTitleKey] = $titleRecord;
                }
                $fTitlesLoaded = true;
            }
        }

        if($fTitlesLoaded == false)
        {
            __debug__printLine("Could not load the list of titles to exclude from '" . $strFileName . "'.  Final list will not be filtered." , C__DISPLAY_WARNING__);
        }
        else
        {
            __debug__printLine("Loaded " . count($GLOBALS['titles_to_filter']) . " titles to exclude from '" . $strFileName . "'." , C__DISPLAY_WARNING__);

        }
    }

    /**
     * Initializes the global list of titles we will automatically mark
     * as "not interested" in the final results set.
     */
    function _loadTitlesRegexesToFilter_()
    {
        $fTitlesLoaded = false;
        $arrTitleFileDetails = $GLOBALS['titles_regex_file_details'];
        $strFileName = "";

        if($GLOBALS['titles_regex_to_filter'] != null && count($GLOBALS['titles_regex_to_filter']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            $fTitlesLoaded = true;
            __debug__printLine("Using previously loaded " . count($GLOBALS['titles_regex_to_filter']) . " regexed title strings to exclude." , C__DISPLAY_ITEM_DETAIL__);
            return;
        }
        else if($arrTitleFileDetails != null)
        {
            $strFileName = $arrTitleFileDetails ['full_file_path'];
            if(file_exists($strFileName ) && is_file($strFileName ))
            {
                __debug__printLine("Loading job title regexes to filter from ".$strFileName."." , C__DISPLAY_ITEM_DETAIL__);
                $classCSVFile = new SimpleScooterCSVFileClass($strFileName , 'r');
                $arrTitlesTemp = $classCSVFile->readAllRecords(true);
                __debug__printLine(count($arrTitlesTemp) . " titles found in the source file that will be automatically filtered from job listings." , C__DISPLAY_ITEM_DETAIL__);

                //
                // Add each title we found in the file to our list in this class, setting the key for
                // each record to be equal to the job title so we can do a fast lookup later
                //
                $GLOBALS['titles_regex_to_filter'] = array();
                foreach($arrTitlesTemp as $titleRecord)
                {

                    $GLOBALS['titles_regex_to_filter'][] = $titleRecord['title_match_regex'];
                }
                $fTitlesLoaded = true;
            }
        }

        if($fTitlesLoaded == false)
        {
            __debug__printLine("Could not load regex list for titles to exclude from '" . $strFileName . "'.  Final list will not be filtered." , C__DISPLAY_WARNING__);
        }
        else
        {
            var_dump('$GLOBALS[titles_regex_to_filter]', $GLOBALS['titles_regex_to_filter']);
            __debug__printLine("Loaded regexes to use for filtering titles from '" . $strFileName . "'." , C__DISPLAY_WARNING__);

        }
    }


    function markJobsList_withAutoItems(&$arrJobs, $strCallerDescriptor = "")
    {
        $this->markJobsList_SetAutoExcludedTitlesFromRegex($arrJobs, $strCallerDescriptor);
        $this->markJobsList_SetAutoExcludedTitles($arrJobs, $strCallerDescriptor);
        $this->markJobsList_SetLikelyDuplicatePosts($arrJobs, $strCallerDescriptor);
    }

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */


    // returns an array with the lookup value and true/false for whether found
    //
    private function _getJobFromArrayByKeyPair_($arr, $param1, $param2)
    {
        $ret = array('lookup_value' => null, 'found_in_array' => false );

        // remove spaces from any of the input strings
        $param1= str_replace( ' ', '', $param1);
        $param2= str_replace( ' ', '', $param2);

        if(strlen($param1) >= 0 && strlen($param1) >= 0)
        {
            $ret['lookup_value'] = strtolower(trim($param1)) . '-'. strtolower(trim($param2));
        }
        else
        {
            // Since we're missing one of the values, we can't really create a good lookup.  Instead
            // let's just make sure there's a unique one for the pairing and return that.
            //
            $ret['lookup_value'] = strtolower(trim($param1)) . '-'. strtolower(trim($param2)) . "[ID-" . uniqid(). "]";

        }

        // Did we find a match in the array?
        if($arr[$ret['lookup_value']] != null)
        {
            $ret['found_in_array'] = true;
        }


        return $ret;
    }


    function markJobsList_SetLikelyDuplicatePosts(&$arrToMark, $strCallerDescriptor = null)
    {
        $nJobsSMatched = 0;
        $nUniqueRoles = 0;
        $nProblemRolesSkipped= 0;

        $arrCompanyRoleNamePairsFound = $GLOBALS['company_role_pairs'];
        $arrSiteJobIDPairs = $GLOBALS['site_jobid_pairs'];
        if($arrCompanyRoleNamePairsFound == null) { $arrCompanyRoleNamePairsFound = array(); }
        if($arrSiteJobIDPairs == null) { $arrSiteJobIDPairs = array(); }

        __debug__printLine("Checking " . count($arrToMark) . " jobs for duplicates by company/role pairing. ".count($arrCompanyRoleNamePairsFound)." previous roles are being used to seed the process." , C__DISPLAY_ITEM_START__);

        foreach($arrToMark as $job)
        {
            $match = false;
            $strThisJobKey = getArrayKeyValueForJob($job);

            // look for it by company / title
            //
            $strCompanyKey = $job['company'];
            if($job['company'] == null || $job['company'] == "")
            {
                $strCompanyKey = $job['job_site'];
            }

            $arrPrevMatchJob = $this->_getJobFromArrayByKeyPair_($arrCompanyRoleNamePairsFound, $strCompanyKey, $job['job_title']);

            if($arrPrevMatchJob['found_in_array'] == true)
            {
                // Go get the current job we have stored for this lookup pair
                // Now, compare that previously stored job against this new one.  Whichever has a value for
                // "interested" wins and becomes the stored job for this lookup.  The other gets marked as
                // duplicate (if it's not been set with a status yet.)
                //
                $arrMatchedEarlierJob = $arrCompanyRoleNamePairsFound[$arrPrevMatchJob['lookup_value'] ];
                $strEarlierMatchJobKey = getArrayKeyValueForJob($arrMatchedEarlierJob);
/*                var_dump('earlier saved job record', $arrToMark[$strEarlierMatchJobIndex]);
                var_dump('newly saved job record', $arrToMark[$strJobIndex]);
                var_dump('$strJobIndex', $strJobIndex);
                var_dump('$strEarlierMatchJobIndex', $strEarlierMatchJobIndex);
                var_dump('earlier saved job record[interested]', $arrToMark[$strJobIndex]['interested']);
                var_dump('newly saved job record[interested]', $arrToMark[$strJobIndex]['interested']);
                $match = true;
                var_dump('Lookup Key', $arrPrevMatchJob['lookup_value']);
*/
                if(!isMarked_InterestedEqualBlank($arrToMark[$strThisJobKey ]))
                {

                    $arrToMark[$strEarlierMatchJobKey]['interested'] =  C__STR_TAG_DULICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__;
                    $arrToMark[$strEarlierMatchJobKey]['notes'] =  $arrToMark[$strEarlierMatchJobIndex]['notes'] . " *** Likely a duplicate post of ". $strThisJobKey;

                    // If we haven't added a note saying it matches this other specific job, add it now.
                    if(substr_count($arrToMark[$strThisJobKey]['notes'], $strEarlierMatchJobKey) <= 0)
                    {
                        $arrToMark[$strThisJobKey]['notes'] =  $arrToMark[$strThisJobKey]['notes'] . "  (Also listed as ". $strEarlierMatchJobKey .") ";
                    }
                    $arrCompanyRoleNamePairsFound[$arrPrevMatchJob['lookup_value'] ] = $arrToMark[$strThisJobKey];
                }
                else
                {

                    //
                    // Not the first time we've seen this before so
                    // mark it as a likely dupe and note who it's a dupe of
                    //
                    $arrToMark[$strThisJobKey ]['interested'] =  C__STR_TAG_DULICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__;
                    $arrToMark[$strThisJobKey ]['notes'] =  $arrToMark[$strThisJobKey ]['notes'] . " *** Likely a duplicate post of ". $strEarlierMatchJobKey;

                    // If we haven't added a note saying it matches this other specific job, add it now.
                    if(substr_count($arrToMark[$strEarlierMatchJobKey]['notes'], $strThisJobKey) <= 0)
                    {
                        $arrToMark[$strEarlierMatchJobKey]['notes'] =  $arrToMark[$strEarlierMatchJobKey]['notes'] . "  (Also listed as ". $strThisJobKey .") ";
                    }
                }

                $nJobsSMatched++;
            }
            else
            {
                // add it to the list
                $arrCompanyRoleNamePairsFound[$arrPrevMatchJob['lookup_value'] ] = $job;
                $nUniqueRoles++;
            }

/*            if(strcasecmp($job['job_title'], "director of product management enterprise smartsheet com") == 0)
            {
                $strEarlierMatchJobIndex = getArrayKeyValueForJob($arrCompanyRoleNamePairsFound[$arrPrevMatchJob['lookup_value'] ]);
                var_dump('earlier saved job record', $arrToMark[$strEarlierMatchJobIndex]);
                var_dump('newly saved job record', $arrToMark[$strJobIndex]);
                var_dump('$strJobIndex', $strJobIndex);
                var_dump('$strEarlierMatchJobIndex', $strEarlierMatchJobIndex);
                var_dump('earlier saved job record[interested]', $arrToMark[$strJobIndex]['interested']);
                var_dump('newly saved job record[interested]', $arrToMark[$strJobIndex]['interested']);
                var_dump('Lookup Key', $arrPrevMatchJob['lookup_value']);

            }
*/
        }
        // set it back to the global so we lookup better each search
        $GLOBALS['company_role_pairs'] = array_copy($arrCompanyRoleNamePairsFound);
        $GLOBALS['site_jobid_pairs'] = array_copy($arrSiteJobIDPairs);

        $strTotalRowsText = "/".count($arrToMark);
        __debug__printLine("Marked  ".$nJobsSMatched .$strTotalRowsText ." roles as likely duplicates based on company/role " . ($strCallerDescriptor != null ? "from " . $strCallerDescriptor : "") . " as 'No/Not Interested'. (Skipped: " . $nProblemRolesSkipped . $strTotalRowsText ."; Unique jobs: ". $nUniqueRoles . $strTotalRowsText .")" , C__DISPLAY_ITEM_RESULT__);

    }

    function markJobsList_SetAutoExcludedTitles(&$arrToMark, $strCallerDescriptor = null)
    {
        __debug__printLine("Excluding Jobs by Exact Title Matches", C__DISPLAY_ITEM_START__);
        $this->_loadTitlesToFilter_();

        $nJobsSkipped = 0;
        $nJobsNotMarked = 0;
        $nJobsMarkedAutoExcluded = 0;


        __debug__printLine("Checking ".count($arrToMark) ." roles against ". count($GLOBALS['titles_to_filter']) ." excluded titles.", C__DISPLAY_ITEM_DETAIL__);

        foreach($arrToMark as $job)
        {
            $strJobIndex = getArrayKeyValueForJob($job);
            // First, make sure we don't already have a value in the interested column.
            // if we do, skip it and move to the next one
            if($job['interested'] == null || strlen($job['interested']) <= 0)
            {
                $nJobsSkipped++;
                continue;
            }

            $strJobKeyToMatch = strScrub($job['job_title'], REPLACES_SPACES_WITH_HYPHENS | DEFAULT_SCRUB );

            // Look for a matching title in our list of excluded titles
            $varValMatch =  $GLOBALS['titles_to_filter'][$strJobKeyToMatch];

            // Look for a matching title in our list of excluded titles
//            __debug__printLine("Matching listing job title '".$job['job_title'] ."' and found " . (!$varValMatch  ? "nothing" : var_export($varValMatch, true)) . " for " . $this->arrTitlesToFilter[$job['job_title']], C__DISPLAY_ITEM_DETAIL__);

            // if we got a match, we'll get an array back with that title and some other data
            // such as the reason it's excluded
            //


            if($varValMatch != null && $varValMatch['exclude_reason'] != null)
            {
                if(strlen($varValMatch['exclude_reason']) > 0)
                {
                    $arrToMark[$strJobIndex]['interested'] = $varValMatch['exclude_reason'] . C__STR_TAG_AUTOMARKEDJOB__;
                }
                else
                {
                    $arrToMark[$strJobIndex]['interested'] = 'No (EXCLUDED TITLE BUT UNKNOWN REASON VALUE)';
                    __debug__printLine("Excluded title " . $job['job_title'] . " did not have an exclude reason.  Cannot mark.", C__DISPLAY_ERROR__);
                }
                $nJobsMarkedAutoExcluded++;
            }
            else              // we're ignoring the Excluded column fact for the time being. If it's in the list, it's excluded
            {
                $nJobsNotMarked++;
//                __debug__printLine("Job title '".$job['job_title'] ."' was not found in the exclusion list.  Keeping for review." , C__DISPLAY_ITEM_DETAIL__);
            }


        }

        $strTotalRowsText = "/".count($arrToMark);
        __debug__printLine("Automatically marked ".$nJobsMarkedAutoExcluded .$strTotalRowsText ." roles " . ($strCallerDescriptor != null ? "from " . $strCallerDescriptor : "") . " as 'No/Not Interested' because the job title was in the exclusion list. (Skipped: " . $nJobsSkipped . $strTotalRowsText ."; Untouched: ". $nJobsNotMarked . $strTotalRowsText .")" , C__DISPLAY_ITEM_RESULT__);
    }

    function markJobsList_SetAutoExcludedTitlesFromRegex(&$arrToMark, $strCallerDescriptor = null)
    {
        $this->_loadTitlesRegexesToFilter_();

        $nJobsNotMarked = 0;
        $nJobsMarkedAutoExcluded = 0;

        __debug__printLine("Excluding Jobs by Title Regex Matches", C__DISPLAY_ITEM_START__);
        __debug__printLine("Checking ".count($arrToMark) ." roles against ". count($GLOBALS['titles_regex_to_filter']) ." excluded titles.", C__DISPLAY_ITEM_DETAIL__);
        $arrJobs_BlankInterested = array_filter($arrToMark, "isMarked_InterestedEqualBlank");
        $nJobsSkipped = count($arrToMark) - count($arrJobs_BlankInterested);

        if(count($arrJobs_BlankInterested) > 0)
        {
            foreach($arrJobs_BlankInterested as $job)
            {
                $fMatched = false;
                // get all the job records that do not yet have an interested value

                foreach($GLOBALS['titles_regex_to_filter'] as $rxInput )
                {
                    $arrRXInput = explode("|", strtolower($rxInput));
                    if(count($arrRXInput) == 0)
                    {
                        $arrRXInput = array($rxInput);
                    }

                    foreach($arrRXInput as $rxItem)
                    {
                        $rx = '/'.$rxItem.'/';
                        if(preg_match($rx, strtolower($job['job_title'])))
                        {
                            $strJobIndex = getArrayKeyValueForJob($job);
                            $arrToMark[$strJobIndex]['interested'] = 'No (Title Excluded Via RegEx)' . C__STR_TAG_AUTOMARKEDJOB__;
                            $nJobsMarkedAutoExcluded++;
                            $fMatched = true;
                            break;
                        }
                        else              // we're ignoring the Excluded column fact for the time being. If it's in the list, it's excluded
                        {
                            $nJobsNotMarked++;
        //                __debug__printLine("Job title '".$job['job_title'] ."' was not found in the exclusion list.  Keeping for review." , C__DISPLAY_ITEM_DETAIL__);
                        }
                    }
                    if($fMatched == true) break;
                }

            }
        }
        $strTotalRowsText = "/".count($arrToMark);
        __debug__printLine("Marked not interested via regex(".$nJobsMarkedAutoExcluded . $strTotalRowsText .") , skipped: " . $nJobsSkipped . $strTotalRowsText .", untouched: ". $nJobsNotMarked . $strTotalRowsText .")" , C__DISPLAY_ITEM_RESULT__);
    }
    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function filterOutUninterestedJobs($arrJobsToFilter, $fIncludeFilteredJobsInResults = true)
    {

        if($fIncludeFilteredJobsInResults == true)
        {
            __debug__printLine("Not filtering results." , C__DISPLAY_WARNING__);
            return $arrJobsToFilter;
        }
        else
        {
            __debug__printLine("Applying filters to " . count($arrJobsToFilter). " jobs.", C__DISPLAY_ITEM_DETAIL__);

        }

        $arrNotInterested = array_filter($arrJobsToFilter, "isMarked_NotInterested");
        $arrInteresting = array_filter($arrJobsToFilter, "isMarked_InterestedOrBlank");


        __debug__printLine("Filtering complete:  ". count($arrNotInterested)." filtered; ". count($arrInteresting). " not filtered " . count($arrJobsToFilter) . " total records." , C__DISPLAY_ITEM_RESULT__);

        return $arrInteresting;

    }


    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function writeJobsListToFile($strOutFilePath, $arrJobsRecordsToUse, $fIncludeFilteredJobsInResults = true, $fFirstAutoMarkJobs = false, $strCallerDescriptor = "")
    {
        if(!$strOutFilePath || strlen($strOutFilePath) <= 0)
        {
            $strOutFilePath = $this->getOutputFileFullPath();
            __debug__printLine("Warning: writeJobsListToFile was called without an output file name.  Using default value: " . $strOutFilePath, C__DISPLAY_ITEM_DETAIL__);

//            throw new ErrorException("Error: writeJobsListToFile called without an output file path to use.");
        }
        if(count($arrJobsRecordsToUse) == 0)
        {
            __debug__printLine("Warning: writeJobsListToFile had no records to write to  " . $strOutFilePath, C__DISPLAY_ITEM_DETAIL__);

        }

        if($fFirstAutoMarkJobs == true)
        {
            $this->markJobsList_withAutoItems($arrJobsRecordsToUse);
        }

        if($fIncludeFilteredJobsInResults == false)
        {
            $arrJobsRecordsToUse = array_filter($arrJobsRecordsToUse, "includeJobInFilteredList");
//            $arrJobsRecordsToUse = $this->filterOutUninterestedJobs($arrJobsRecordsToUse, $fIncludeFilteredJobsInResults);

        }


        $classCombined = new SimpleScooterCSVFileClass($strOutFilePath , "w");
        $classCombined->writeArrayToCSVFile($arrJobsRecordsToUse, array_keys($this->getEmptyItemsArray()), $this->arrKeysForDeduping);
        __debug__printLine($strCallerDescriptor . ($strCallerDescriptor  != "" ? " jobs" : "Jobs ") ."list had  ". count($arrJobsRecordsToUse) . " jobs and was written to " . $strOutFilePath , C__DISPLAY_ITEM_START__);

        return $strOutFilePath;

    }


    /**
     * Merge multiple lists of jobs from memory and from file into a new single CSV file of jobs
     *
     *
     * @param  string $strOutFilePath The file to output the jobs list to
     * @param  Array $arrFilesToCombine An array of optional jobs CSV files to combine into the file output CSV
     * @param  Array $arrMyRecordsToInclude An array of optional job records to combine into the file output CSV
     * @param  integer $fIncludeFilteredJobsInResults False if you do not want jobs marked as interested = "No *" excluded from the results
     * @return string $strOutFilePath The file the jobs was written to or null if failed.
     */
    function loadJobsListFromCSVs($arrFilesToLoad)
    {
        $arrRetJobsList = array();

        if(!is_array($arrFilesToLoad) || count($arrFilesToLoad) == 0)
        {
            throw new ErrorException("Error: loadJobsListFromCSVs called with an empty array of file names to load. ");

        }


        __debug__printLine("Loading jobs from " . count($arrFilesToLoad) . " CSV input files: " . var_export($arrFilesToLoad, true), C__DISPLAY_ITEM_START__);

        foreach($arrFilesToLoad as $fileInput)
        {
            $classCombinedRead = new SimpleScooterCSVFileClass($fileInput, "r");
            $arrCurFileJobs = $classCombinedRead->readAllRecords(true, array_keys($this->getEmptyItemsArray()));
            $classCombinedRead = null;
            $arrCurNormalizedJobs =  $this->normalizeJobList($arrCurFileJobs);

            addJobsToJobsList($arrRetJobsList, $arrCurNormalizedJobs);
        }


        __debug__printLine("Loaded " .count($arrRetJobsList)." jobs from " . count($arrFilesToLoad) . " CSV input files.", C__DISPLAY_ITEM_RESULT__);

        return $arrRetJobsList;

    }


    /**
     * Merge multiple lists of jobs from memory and from file into a new single CSV file of jobs
     *
     *
     * @param  string $strOutFilePath The file to output the jobs list to
     * @param  Array $arrFilesToCombine An array of optional jobs CSV files to combine into the file output CSV
     * @param  Array $arrMyRecordsToInclude An array of optional job records to combine into the file output CSV
     * @param  integer $fIncludeFilteredJobsInResults False if you do not want jobs marked as interested = "No *" excluded from the results
     * @return string $strOutFilePath The file the jobs was written to or null if failed.
     */
    function writeMergedJobsCSVFile($strOutFilePath, $arrFilesToCombine, $arrMyRecordsToInclude = null, $fIncludeFilteredJobsInResults = true)
    {
        $arrRetJobs = array();
        if(!$strOutFilePath || strlen($strOutFilePath) <= 0)
        {
            $strOutFilePath = $this->getOutputFileFullPath('writeMergedJobsCSVFile_');
        }


        if(!is_array($arrFilesToCombine) || count($arrFilesToCombine) == 0)
        {
            if(count($arrMyRecordsToInclude) > 0)
            {
                $this->writeJobsListToFile($strOutFilePath, $arrRetJobs, $fIncludeFilteredJobsInResults);
            }
            else
            {
                throw new ErrorException("Error: writeMergedJobsCSVFile called with an empty array of filenames to combine. ");

            }

        }
        else
        {


            __debug__printLine("Combining jobs into " . $strOutFilePath . " from " . count($arrMyRecordsToInclude) ." records and " . count($arrFilesToCombine) . " CSV input files: " . var_export($arrFilesToCombine, true), C__DISPLAY_ITEM_DETAIL__);



            if(count($arrFilesToCombine) > 1)
            {
                $classCombined = new SimpleScooterCSVFileClass($strOutFilePath , "w");
                $arrRetJobs = $classCombined->readMultipleCSVsAndCombine($arrFilesToCombine, array_keys($this->getEmptyItemsArray()), $this->arrKeysForDeduping);

            }
            else if(count($arrFilesToCombine) == 1)
            {
                $classCombinedRead = new SimpleScooterCSVFileClass($arrFilesToCombine[0], "r");
                $arrRetJobs = $classCombinedRead->readAllRecords(true, array_keys($this->getEmptyItemsArray()));
            }


            if(count($arrMyRecordsToInclude) > 1)
            {
                $arrRetJobs = my_merge_add_new_keys($arrMyRecordsToInclude, $arrRetJobs);
            }

            $this->writeJobsListToFile($strOutFilePath, $arrRetJobs, $fIncludeFilteredJobsInResults);
            __debug__printLine("Combined file has ". count($arrRetJobs) . " jobs and was written to " . $strOutFilePath , C__DISPLAY_ITEM_START__);

        }
        return $strOutFilePath;

    }


    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function getSimpleObjFromPathOrURL($filePath = "", $strURL = "")
    {
//         __debug__printLine("getSimpleObjFromPathOrURL(".$filePath.', '.$strURL.")", C__DISPLAY_ITEM_DETAIL__);
        $objSimpleHTML = null;

        if(!$objSimpleHTML && ($filePath && strlen($filePath) > 0))
        {
            __debug__printLine("Loading ALTERNATE results from ".$filePath, C__DISPLAY_ITEM_START__);
            $objSimpleHTML =  $this->getSimpleHTMLObjForFileContents($filePath);
        }


        if(!$objSimpleHTML && $strURL && strlen($strURL) > 0)
        {
//             __debug__printLine("Loading results from ".$strURL, C__DISPLAY_ITEM_DETAIL__);
            $options  = array('http' => array( 'timeout' => 10,                 'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.131 Safari/537.36'));
            $context  = stream_context_create($options);
            $objSimpleHTML = file_get_html($strURL, false, $context);
        }

        if(!$objSimpleHTML)
        {
            throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$filePath.') or '.$strURL);
        }

        return $objSimpleHTML;
    }

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function getOutputFileFullPath($strFilePrefix = "", $strBase = 'jobs', $strExtension = 'csv')
    {
        $strFullPath = getDefaultJobsOutputFileName($strFilePrefix, $strBase , $strExtension);

        if($this->strOutputFolder != null && strlen($this->strOutputFolder) > 0)
        {
            $strFullPath = $this->strOutputFolder . $strFullPath;
        }

        $arrReturnPathDetails = parseFilePath($strFullPath, false);

        return $arrReturnPathDetails ['full_file_path'];
    }

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function getSimpleHTMLObjForFileContents($strInputFileFullPath)
    {
        $objSimpleHTML = null;
        __debug__printLine("Loading HTML from ".$strInputFileFullPath, C__DISPLAY_ITEM_DETAIL__);

        if(!file_exists($strInputFileFullPath) && !is_file($strInputFileFullPath))  return $objSimpleHTML;
        $fp = fopen($strInputFileFullPath , 'r');
        if(!$fp ) return $objSimpleHTML;

        $strHTML = fread($fp, MAX_FILE_SIZE);
        $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
        $objSimpleHTML = $dom->load($strHTML, $lowercase, $stripRN);
        fclose($fp);

        return $objSimpleHTML;
    }

}


?>
