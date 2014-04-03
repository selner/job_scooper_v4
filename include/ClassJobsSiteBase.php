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

abstract class ClassJobsSiteBase extends ClassJobsSiteExport
{
    protected $siteName = 'NAME-NOT-SET';
    abstract function getJobs($strAlternateLocalHTMLFile = null);

    function __destruct()
    {
        __debug__printLine("Done with ".$this->siteName." processing.", C__DISPLAY_ITEM_START__);
    }


    function downloadAllUpdatedJobs($nDays = -1, $varPathToInputFilesToMergeWithResults = null, $fIncludeFilteredJobsInResults = true )
    {
        $retFilePath = '';

        // Now go download and output the latest jobs from this site
        __debug__printLine("Downloading new ". $this->siteName ." jobs...", C__DISPLAY_ITEM_START__);

        //
        // Call the child classes getJobs function to update the object's array of job listings
        // and output the results to a single CSV
        //
        $retCSVFilePathJobsWrittenTo = $this->getJobs($nDays);

        //
        // Now, filter those jobs and mark any rows that are titles we want to automatically
        // exclude
        //
        $this->arrLatestJobs = $this->setExcludedTitles($this->arrLatestJobs);

        //
        // Write the resulting array of the latest job postings from this site to
        // a CSV file for the user
        //
        $this->writeJobsToCSV($retCSVFilePathJobsWrittenTo, $this->arrLatestJobs);

        __debug__printLine("Downloaded ". count($this->arrLatestJobs) ." new ". $this->siteName ." jobs to " . $retCSVFilePathJobsWrittenTo, C__DISPLAY_ITEM_DETAIL__);

        $arrFilesToCombine = array();
        $arrFilesToCombine[] = $retCSVFilePathJobsWrittenTo;
        if(is_array($varPathToInputFilesToMergeWithResults))
        {
            $arrFilePathsToCombine = array_merge($arrFilesToCombine, $varPathToInputFilesToMergeWithResults);
        }
        else if(is_string($varPathToInputFilesToMergeWithResults))
        {
            $arrFilesToCombine[] = $varPathToInputFilesToMergeWithResults;
        }


        $strOutFilePath = $this->getOutputFileFullPath("Final_CombinedWithUserInput");
        $retFilePath = $this->combineMultipleJobsCSVs($strOutFilePath, $arrFilesToCombine, $fIncludeFilteredJobsInResults);


        return $retFilePath;
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

    function getOutputFileFullPath($strFilePrefix = "", $strBase = "", $strExtension = "")
    {
        return parent::getOutputFileFullPath($this->siteName . "_" . $strFilePrefix, $strBase, $strExtension);
    }



}
