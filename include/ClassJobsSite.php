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
    public $arrLatestJobs = null;

    abstract function getMyJobs($strAlternateLocalHTMLFile = null, $fIncludeFilteredJobsInResults = true);


    function __destruct()
    {
        __debug__printLine("Done with ".$this->siteName." processing.", C__DISPLAY_ITEM_START__);
    }



    function returnMyCurrentJobsList() { return $this->arrLatestJobs; }

    function addJobsToList($arrAdd)
    {

//        var_dump('arrLatest = ', count($this->arrLatestJobs), $this->arrLatestJobs);
//        var_dump('arrAdd = ', count($arrAdd), $arrAdd);

        if(!is_array($arrAdd) || count($arrAdd) == 0)
        {
            // skip. no jobs to add
            return;
        }

        if(is_array($this->arrLatestJobs))
        {
            $this->arrLatestJobs = my_merge_add_new_keys($this->arrLatestJobs, $arrAdd );
        }
        else
        {
            $this->arrLatestJobs = array_copy( $arrAdd );

        }


    }

    function writeMyJobsListToFile($strOutFilePath, $fIncludeFilteredJobsInResults = true)
    {
        return $this->writeJobsListToFile($strOutFilePath, $this->arrLatestJobs, $fIncludeFilteredJobsInResults);
    }

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
        $this->markMyJobsListForAnyAutoExcludedTitles();


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

    function markMyJobsListForAnyAutoExcludedTitles()
    {
        $this->_loadTitlesToFilter_();
        if(!is_array($this->arrTitlesToFilter))
        {
            __debug__printLine("No titles found to exclude. Skipping results filtering." , C__DISPLAY_MOMENTARY_INTERUPPT__);
            return $arrJobsToFilter;
        }
        else
        {
            __debug__printLine("Applying " .count($this->arrTitlesToFilter) . " to " .count($this->arrLatestJobs) . " job records.", C__DISPLAY_MOMENTARY_INTERUPPT__);

        }

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

    function is_IncludeBrief()
    {
        $val = $this->_bitFlags & C_EXCLUDE_BRIEF;
        $notVal = !($this->_bitFlags & C_EXCLUDE_BRIEF);
        // __debug__printLine('ExcludeBrief/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);
        return false;
    }

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



}
