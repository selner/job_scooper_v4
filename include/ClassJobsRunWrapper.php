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
require_once dirname(__FILE__) . '/SitePlugins.php';



class ClassJobsRunWrapper extends ClassJobsSitePluginNoActualSite
{
    protected $arrJobSearches = null;
    protected $arrUserInputJobs_Active = null;
    protected $arrUserInputJobs_Inactive = null;
    protected $arrJobCSVUserInputFiles = null;
    protected $nNumDaysToSearch = -1;


    function ClassJobsRunWrapper($arrSearches = null, $arrJobCSVUserInputFiles = null, $nDays = -1)
    {
        $this->arrJobSearches = $arrSearches;
        $this->arrSourceFiles = $arrJobCSVUserInputFiles;
        $this->nNumDaysToSearch = $nDays;
        $this->arrLatestJobs = null;
        $this->arrLatestJobs_FilteredByUserInput = null;
        $this->arrLatestJobs_UnfilteredByUserInput = null;

        $this->getCommandLine($arrSearches);
        $this->detailsOutputFile =  $GLOBALS['output_file_details'];
        $this->setOutputFolder($this->detailsOutputFile['directory']);
        $this->setMyBitFlags(C_NORMAL);

        if($this->detailsOutputFile['directory'] == null || $this->detailsOutputFile['directory']== "")
        {
            throw new ErrorException("Required value for the output folder was not specified. Exiting.");
        }
        if($this->detailsOutputFile['file_name'] == null || $this->detailsOutputFile['full_file_path'] == "")
        {
            $strOutPathWithName = $this->getOutputFileFullPath("_runjobs_notnamed_");
            $this->detailsOutputFile = parseFilePath($strOutPathWithName);
        }

    }

    private function getCommandLine($arrSearches=null)
    {
            $GLOBALS["bit_flags"] = C_NORMAL;
            __initializeArgs__();

            $classInit = new ClassMultiSiteSearch($GLOBALS["bit_flags"], null /* no dir needed */, $arrSearches);
            __getPassedArgs__();

    }


    private function loadUserInputJobsFromCSV()
    {
        $arrAllJobsLoadedFromSrc = $this->loadJobsListFromCSVs($this->arrSourceFiles);
        $this->normalizeJobList($arrAllJobsLoadedFromSrc);
        if($GLOBALS['OPTS']['DEBUG'] == true)
        {
            $strCSVInputJobsPath = getFullPathFromFileDetails($this->detailsOutputFile, "", "_Jobs_From_UserInput");
            $this->writeJobsListToFile($strCSVInputJobsPath, $arrAllJobsLoadedFromSrc, true, false, "ClassJobRunner-LoadCSVs");
        }

        // These will be used at the beginning and end of
        // job processing to filter out jobs we'd previous seen
        // and to make sure our notes get updated on active jobs
        // that we'd seen previously
        //
        //
        // Set a global var with an array of all input cSV jobs marked new or not marked as excluded (aka "Yes" or "Maybe")
        //
        $this->arrUserInputJobs_Active = array_filter($arrAllJobsLoadedFromSrc, "isMarked_InterestedOrBlank");
        __debug__printLine(count($this->arrUserInputJobs_Active). " active job listings loaded from user input CSVs.", C__DISPLAY_SUMMARY__);

        //
        // Set a global var with an array of all input CSV jobs that are not in the first set (aka marked Not Interested & Not Blank)
        //
        $this->arrUserInputJobs_Inactive = array_filter($arrAllJobsLoadedFromSrc, "isMarked_NotInterestedAndNotBlank");
        __debug__printLine(count($this->arrUserInputJobs_Inactive). " inactive job listings loaded from user input CSVs.", C__DISPLAY_SUMMARY__);

    }

    private function writeRunsJobsToFile($strFileOut, $arrJobsToOutput, $strLogDescriptor, $strExt = "CSV")
    {
        $this->writeJobsListToFile($strFileOut, $arrJobsToOutput, true, false, "ClassJobRunner-".$strLogDescriptor, $strExt);

    }

    private function outputFilteredJobsListToFile($strFilterToApply, $strFileNameAppend, $strExt = "CSV", $strFilterDescription = null)
    {
        $arrToOutput = null;

        if($strFilterToApply == null || $strFilterToApply == "")
        {
            throw new ErrorException("Required array filter callback was not specified.  Cannot output filtered jobs list.");
        }

        if($strFileNameAppend == null || $strFileNameAppend == "")
        {
            throw new ErrorException("Required array filter callback was not specified.  Cannot output " . $strFilterToApply . " filtered jobs list.");
        }

        $arrToOutput = array_filter($this->arrLatestJobs, $strFilterToApply);
        $details = $this->detailsOutputFile;
        $details['file_extension'] = $strExt;
        $strFilteredCSVOutputPath = getFullPathFromFileDetails($details, "", $strFileNameAppend);
        $this->writeRunsJobsToFile($strFilteredCSVOutputPath, $arrToOutput, $strFilterToApply, $strExt);
        __debug__printLine(($strFilterDescription != null ? $strFilterDescription : $strFileNameAppend) . " " . count($arrToOutput). " job listings output to  " . $strFilteredCSVOutputPath, C__DISPLAY_SUMMARY__);

        return $arrToOutput;
    }

    //
    // Note:  This function does not take the user's input job listings into account at all.  It
    //        returns the pure new job listings from all the specified searches
    //
    protected function getLatestRawJobsFromAllSearches()
    {
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Download all the job listings for all the users searches
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        __debug__printLine(PHP_EOL."**************  Starting Run of " . count($this->arrJobSearches) . " Searches  **************  ".PHP_EOL, C__DISPLAY_NORMAL__);


        //
        // the Multisite class handles the heavy lifting for us by executing all
        // the searches in the list and returning us the combined set of new jobs
        // (with the exception of Amazon for historical reasons)
        //
        $classMulti = new ClassMultiSiteSearch($GLOBALS["bit_flags"], $this->detailsOutputFile['full_file_path']);
        $classMulti->addSearches($this->arrJobSearches);
        $classMulti->downloadAllUpdatedJobs( $this->nNumDaysToSearch);
        $this->_addJobsToMyJobsList_($classMulti->getMyJobsList());

        //
        // Let's go get Amazon too if the user asked for Amazon's jobs
        //
        if($GLOBALS['site_plugins']['Amazon']['include_in_run'] == true)
        {
            __debug__printLine("Adding Amazon jobs....", C__DISPLAY_ITEM_START__);
            $class = new PluginAmazon($GLOBALS["bit_flags"], $this->detailsOutputFile['full_file_path']);
            $class->downloadAllUpdatedJobs( $this->nNumDaysToSearch);
            $this->_addJobsToMyJobsList_($class->getMyJobsList());
        }

        //
        // Let's save off the unfiltered jobs list in case we need it later.  The $this->arrLatestJobs
        // will shortly have the user's input jobs applied to it
        //

        addJobsToJobsList($this->arrLatestJobs_UnfilteredByUserInput, $this->arrLatestJobs);

        $strRawJobsListOutput = getFullPathFromFileDetails($this->detailsOutputFile, "", "_rawjobslist_preuser_filtering");
//        $this->writeJobsListToFile($strRawJobsListOutput, $this->arrLatestJobs_UnfilteredByUserInput , true, false, "ClassJobsRunWrapper-_rawjobslist_preuser_filtering");
        $this->writeRunsJobsToFile($strRawJobsListOutput, $this->arrLatestJobs_UnfilteredByUserInput, "RawJobsList_PreUserDataFiltering");

       __debug__printLine(count($this->arrLatestJobs_UnfilteredByUserInput). " raw, latest job listings from " . count($this->arrJobSearches) . " searches downloaded to " . $strRawJobsListOutput, C__DISPLAY_SUMMARY__);

    }

    function RunAll()
    {

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Process the input CSVs of job listings that the user specified.
        // The inactives get added to the full jobs list as the starting jobs
        // The actives will get added at the end so they overwrite any jobs that
        // were found again
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if($this->arrSourceFiles != null)
        {
            __debug__printLine(PHP_EOL."**************  Loading user-specified jobs list information from ". count($this->arrSourceFiles) ." CSV files **************  ".PHP_EOL, C__DISPLAY_NORMAL__);
            $this->loadUserInputJobsFromCSV();
        }


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // At this point, we have a full list of all the new jobs that have been posted within the user's search parameters
        // completely unfiltered.  Let's save that off now before we update it with the values that user passed in via CSVs.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if($this->arrJobSearches != null)
        {
            __debug__printLine(PHP_EOL."************** Get the latest jobs for all searches ****************".PHP_EOL, C__DISPLAY_NORMAL__);
            $this->getLatestRawJobsFromAllSearches();
        }




        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Now we can update the full jobs list with the active jobs we loaded from the CSV at the start
        // $this->arrLatestJobs_UnfilteredByUserInput is the unfiltered list;
        // $this->arrLatestJobs is the processed & filtered jobs list
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        if($this->arrUserInputJobs_Inactive != null)
        {
            $this->arrLatestJobs = null;
            addJobsToJobsList($this->arrLatestJobs, $this->arrUserInputJobs_Inactive);
            addJobsToJobsList($this->arrLatestJobs, $this->arrLatestJobs_UnfilteredByUserInput);
        }

        if($this->arrUserInputJobs_Active  != null)
        {
            addJobsToJobsList($this->arrLatestJobs, $this->arrUserInputJobs_Active);
        }


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        __debug__printLine(PHP_EOL."**************  Updating jobs list for known filters ***************".PHP_EOL, C__DISPLAY_NORMAL__);
        $this->markMyJobsList_withAutoItems();


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Output the full jobs list into a file and into files for different cuts at the jobs list data
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        __debug__printLine(PHP_EOL."**************  Writing final list of " . count($this->arrLatestJobs) . " jobs to output files.  ***************  ".PHP_EOL, C__DISPLAY_NORMAL__);
        $class = null;

        // Write to the main output file name that the user passed in
        $this->writeRunsJobsToFile($this->detailsOutputFile['full_file_path'], $this->arrLatestJobs, "ClassJobsRunWrapper-UserOutputFile");


        //
        // Output all job records and their values
        //
        $strOutDetailsAllResultsName = getFullPathFromFileDetails($this->detailsOutputFile, "", "_AllJobs");
        $this->writeJobsListToFile($strOutDetailsAllResultsName, $this->arrLatestJobs, "ClassJobsRunWrapper-AllJobs");

        //
        // Now, output the various subsets of the total jobs list
        //



        // Output only records that are new or not marked as excluded (aka "Yes" or "Maybe")
        $arrJobs_Active = $this->outputFilteredJobsListToFile("isMarked_InterestedOrBlank", "_ActiveJobs", "CSV");
        $arrJobs_Active = $this->outputFilteredJobsListToFile("isMarked_InterestedOrBlank", "_ActiveJobs", "HTML");

        $arrJobs_Active = $this->outputFilteredJobsListToFile("isJobUpdatedToday", "_UpdatedJobs");

        // Output only new records that haven't been looked at yet
        $arrJobs_NewOnly = $this->outputFilteredJobsListToFile("isNewJobToday_Interested_IsBlank", "_NewJobs_ForReview", "CSV");
        $arrJobs_NewOnly = $this->outputFilteredJobsListToFile("isNewJobToday_Interested_IsBlank", "_NewJobs_ForReview", "HTML");

        // Output all records that were automatically excluded
        $arrJobs_AutoExcluded = $this->outputFilteredJobsListToFile("isMarked_NotInterested", "_ExcludedJobs");

        // Output only records that were auto-marked as duplicates
        // $arrJobs_AutoDupe = $this->outputFilteredJobsListToFile("isInterested_MarkedDuplicateAutomatically", "_AutoDuped");
        // $arrJobs_NewButFiltered = $this->outputFilteredJobsListToFile("isNewJobToday_Interested_IsNo", "_NewJobs_AutoExcluded");
        // Output all records that were previously marked excluded manually by the user
        // $arrJobs_ManualExcl = $this->outputFilteredJobsListToFile("isMarked_ManuallyNotInterested", "_ManuallyExcludedJobs");

        __debug__printLine(PHP_EOL."**************  DONE.  Cleaning up.  **************  ".PHP_EOL, C__DISPLAY_NORMAL__);


        __debug__printLine("Total jobs downloaded:  ".count($this->arrLatestJobs_UnfilteredByUserInput). PHP_EOL. "Total jobs:  ".count($this->arrLatestJobs). PHP_EOL."New: ". count($arrJobs_NewOnly) . " jobs for review. " .count($arrJobs_NewButFiltered). " jobs were auto-filtered.".PHP_EOL."Active: ". count($arrJobs_Active)  . PHP_EOL. "Auto-Filtered: ".count($arrJobs_AutoExcluded). PHP_EOL."Dupes: ".count($arrJobs_AutoDupe) , C__DISPLAY_SUMMARY__);

    }
} 