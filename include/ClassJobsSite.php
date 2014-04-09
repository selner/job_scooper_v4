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
require_once dirname(__FILE__) . '/scooter_utils_common.php';
require_once dirname(__FILE__) . '/ClassJobsSiteExport.php';

abstract class ClassJobsSite extends ClassJobsSiteExport
{
    protected $siteName = 'NAME-NOT-SET';
    protected $arrLatestJobs = null;

    abstract function getMyJobs($nDays = null, $fIncludeFilteredJobsInResults = true);


    function __destruct()
    {
        __debug__printLine("Done with ".$this->siteName." processing.", C__DISPLAY_ITEM_START__);
    }



    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function getMyJobsList() { return $this->arrLatestJobs; }


    /**
     * Main worker function for all jobs sites.
     *
     *
     * @param  integer $nDays Number of days of job listings to pull
     * @param  Array $arrInputFilesToMergeWithResults Optional list of jobs list CSV files to include in the results
     * @param  integer $fIncludeFilteredJobsInResults If true, filters out jobs flagged with "not interested" values from the results.
     * @return string If successful, the final output CSV file with the full jobs list
     */
    function downloadAllUpdatedJobs($nDays = -1, $arrInputFilesToMergeWithResults = null, $fIncludeFilteredJobsInResults = true )
    {
        $retFilePath = '';

        // Now go download and output the latest jobs from this site
        __debug__printLine("Downloading new ". $this->siteName ." jobs...", C__DISPLAY_ITEM_START__);

        //
        // Call the child classes getJobs function to update the object's array of job listings
        // and output the results to a single CSV
        //
        $this->getMyJobs($nDays, $fIncludeFilteredJobsInResults);

        //
        // Now, filter those jobs and mark any rows that are titles we want to automatically
        // exclude
        //
        $this->markMyJobsList_SetAutoExcludedTitles();

        //
        // Now, mark jobs that look like duplicates by company name & title.
        //
        $this->markMyJobsList_SetLikelyDuplicatePosts();

        //
        // Write the resulting array of the latest job postings from this site to
        // a CSV file for the user
        //
        $strOutFilePath = $this->getOutputFileFullPath();
        $this->writeMyJobsListToFile($strOutFilePath , $fIncludeFilteredJobsInResults);

        if(count($arrInputFilesToMergeWithResults ) >= 1)
        {
            $retFilePath = $this->writeMergedJobsCSVFile($strOutFilePath, $arrInputFilesToMergeWithResults, $this->arrLatestJobs, $fIncludeFilteredJobsInResults);
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
    function markMyJobsList_SetLikelyDuplicatePosts()
    {
        $nJobsSMatched = 0;
        $nUniqueRoles = 0;
        $nProblemRolesSkipped= 0;


        $arrCompanyRoleNamePairsFound = $GLOBALS['company_role_pairs'];
        if($arrCompanyRoleNamePairsFound == null) { $arrCompanyRoleNamePairsFound = array(); }

        __debug__printLine("Checking " . count($this->arrLatestJobs) . " jobs for duplicates by company/role pairing. ".count($arrCompanyRoleNamePairsFound)." previous roles are being used to seed the process." , C__DISPLAY_ITEM_START__);

        $nIndex = 0;
        foreach($this->arrLatestJobs as $job)
        {

            if(strlen($job['company']) == 0 || strlen($job['job_title']) == 0)
            {
                // we're missing one part of the key we need, so cannot dedupe
                // it successfully.  Skip it and move on.
                $nProblemRolesSkipped++;
                __debug__printLine("Skipping " . $job['job_title'] . "/". $job['company'] . " due to insufficient information to detect duplicates with." , C__DISPLAY_ITEM_DETAIL__);
            }
            else
            {

                $strRoleKey = $job['company'] . '-'. $job['job_title'];

                // is it the first time we've seen this pairing?
                if($arrCompanyRoleNamePairsFound[$strRoleKey] == null)
                {
                    // add it to the list
                    $arrCompanyRoleNamePairsFound[$strRoleKey] = $job;
                    $nUniqueRoles++;
                }
                else
                {

//                    $datePulled = strtotime($this->arrLatestJobs['date_pulled'], TODO);
//                   $now = new DateTime();

//                    if($now->diff($datePulled)->days > 60) // if it's been over 60 days, then

                    //
                    // Not the first time we've seen this before so
                    // mark it as a likely dupe and note who it's a dupe of
                    //
                    $this->arrLatestJobs[$nIndex]['interested'] = 'Maybe (Likely Duplicate Job Post)[auto-marked]';
                    $this->arrLatestJobs[$nIndex]['notes'] =  $this->arrLatestJobs[$nIndex]['notes'] . " *** Likely a duplicate post of ". $arrCompanyRoleNamePairsFound[$strRoleKey]['job_site'] . " ID#" . $arrCompanyRoleNamePairsFound[$strRoleKey]['job_id'];
                    $nJobsSMatched++;
                }
            }
            $nIndex++;
        }

        // set it back to the global so we lookup better each search
        $GLOBALS['company_role_pairs'] = array_copy($arrCompanyRoleNamePairsFound);


        __debug__printLine("Completed marking posts that are likely duplicates by company/title:  ".$nJobsSMatched ." roles marked duplicate,  " . $nProblemRolesSkipped . " skipped,  ". $nUniqueRoles . " unique jobs.." ,    C__DISPLAY_ITEM_DETAIL__);
        __debug__printLine(count($GLOBALS['company_role_pairs']) ." company/role pairs have been stored to check the next jobs list.",C__DISPLAY_ITEM_DETAIL__);
    }

    /**
     *
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function markMyJobsList_SetAutoExcludedTitles()
    {

        $nJobsSkipped = 0;
        $nJobsNotMarked = 0;
        $nJobsMarkedAutoExcluded = 0;

        $nIndex = 0;
        foreach($this->arrLatestJobs as $job)
        {
            // First, make sure we don't already have a value in the interested column.
            // if we do, skip it and move to the next one
            if(strlen($this->arrLatestJobs[$nIndex]['interested']) > 0)
            {
                $nJobsSkipped++;
                continue;
            }

            // Look for a matching title in our list of excluded titles
            $varValMatch = $this->arrTitlesToFilter[$job['job_title']];
            //           __debug__printLine("Matching listing job title '".$job['job_title'] ."' and found " . (!$varValMatch  ? "nothing" : $varValMatch ) . " for " . $this->arrTitlesToFilter[$job['job_title']], C__DISPLAY_ITEM_DETAIL__);

            // if we got a match, we'll get an array back with that title and some other data
            // such as the reason it's excluded
            //

            if(is_array($varValMatch))
            {
                if(strlen($varValMatch['exclude_reason']) > 0)
                {
                    $this->arrLatestJobs[$nIndex]['interested'] = $varValMatch['exclude_reason'] . "[auto-filtered]";
                }
                else
                {
                    $this->arrLatestJobs[$nIndex]['interested'] = 'UNKNOWN - FOUND MATCH BUT NO REASON (Auto)';
                    __debug__printLine("Excluded title " . $job['job_title'] . " did not have an exclude reason.  Cannot mark.", C__DISPLAY_ERROR__);
                }
                $nJobsMarkedAutoExcluded++;
            }
            else              // we're ignoring the Excluded column fact for the time being. If it's in the list, it's excluded
            {
                $nJobsNotMarked++;
                __debug__printLine("Job title '".$job['job_title'] ."' was not found in the exclusion list.  Keeping for review." , C__DISPLAY_ITEM_DETAIL__);
            }
            $nIndex++;
        }


        __debug__printLine("Completed marking auto-excluded titles:  '".$nJobsMarkedAutoExcluded ."' were marked as auto excluded, " . $nJobsSkipped . " skipped and ". $nJobsNotMarked . " jobs were not." , C__DISPLAY_ITEM_RESULT__);
    }

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */

    function getActualPostURL($strSrcURL)
    {
        $retURL = null;

        $classAPI = new APICallWrapperClass();
        __debug__printLine("Getting source URL for ". $strSrcURL , C__DISPLAY_ITEM_START__);

        try
        {
            $curlObj = $classAPI->cURL($strSrcURL);
            if($curlObj && !$curl_object['error_number'] && $curl_object['error_number'] == 0 )
            {
                $retURL  =  $curlObj['actual_site_url'];
            }
        }
        catch(ErrorException $err)
        {
            // do nothing
        }
        return $retURL;
    }

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function is_IncludeBrief()
    {
        $val = $this->_bitFlags & C_EXCLUDE_BRIEF;
        $notVal = !($this->_bitFlags & C_EXCLUDE_BRIEF);
        // __debug__printLine('ExcludeBrief/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);
        return false;
    }

    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function is_IncludeActualURL()
    {
        $val = $this->_bitFlags & C_EXCLUDE_GETTING_ACTUAL_URL;
        $notVal = !($this->_bitFlags & C_EXCLUDE_GETTING_ACTUAL_URL);
        // __debug__printLine('ExcludeActualURL/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);

        return !$notVal;
    }

    function getOutputFileFullPath($strFilePrefix = "", $strBase = "jobs", $strExtension = "csv")
    {
        return parent::getOutputFileFullPath($this->siteName . "_" . $strFilePrefix, $strBase, $strExtension);
    }


    /**
     * Write this class instance's list of jobs to an output CSV file
     *
     *
     * @param  string $strOutFilePath The file to output the jobs list to
     * @param  Array $arrMyRecordsToInclude An array of optional job records to combine into the file output CSV
     * @param  integer $fIncludeFilteredJobsInResults False if you do not want jobs marked as interested = "No *" excluded from the results
     * @return string $strOutFilePath The file the jobs was written to or null if failed.
     */
    function writeMyJobsListToFile($strOutFilePath = null, $fIncludeFilteredJobsInResults = true)
    {
        return $this->writeJobsListToFile($strOutFilePath, $this->arrLatestJobs, $fIncludeFilteredJobsInResults);
    }


    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function _addJobsToList_($arrAdd)
    {

        if(!is_array($arrAdd) || count($arrAdd) == 0)
        {
            // skip. no jobs to add
            return;
        }


        if($this->arrLatestJobs == null || !is_array($this->arrLatestJobs ))
        {
            $this->arrLatestJobs = array();
            $this->arrLatestJobs = array_copy( $arrAdd );
        }
        else
        {
            foreach($arrAdd as $jobRecord)
            {
                   $this->arrLatestJobs[] = $jobRecord;
            }

        }

        return;


    }



}
