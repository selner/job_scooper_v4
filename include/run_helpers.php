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
require_once dirname(__FILE__) . '/JobSiteClasses.php';


//
// Default settings for the job sites
//


/****************************************************************************************************************/
/**************                                                                                                         ****/
/**************          Helper Function:  Pulling the Active Jobs                                                         ****/
/**************                                                                                                         ****/
/****************************************************************************************************************/

function __runCommandLine($arrSearches = null, $arrInputFiles = null)
{
    $GLOBALS["bit_flags"] = C_NORMAL;
    __initializeArgs__();

    $classInit = new ClassMultiSiteSearch($GLOBALS["bit_flags"], null /* no dir needed */, $arrSearches);


    __getPassedArgs__();



    __runAllJobs__($arrSearches, $arrInputFiles , $nDays, $fIncludeFilteredListings  );

}


function __runAllJobs__($arrSearches, $arrSourceFiles = null, $nDays = -1, $fIncludeFilteredJobsInResults = null)
{
    $arrSitesSettings =  $GLOBALS['sites_supported'];
    $strOutputFolder = $GLOBALS['output_file_details']['directory'];
    $strOutName = $GLOBALS['output_file_details']['full_file_path'];

    $arrListAllJobsFromSearches = null;

    $strOutputFile_Filtered = null;

    __debug__printLine(PHP_EOL."**************  Start  **************  ".PHP_EOL, C__DISPLAY_NORMAL__);

    $classJobExportHelper_Main = new ClassJobsSiteNoActualSite(C_NORMAL, $strOutputFolder);

    if($GLOBALS['output_file_details']['file_name'] == null || $GLOBALS['output_file_details']['full_file_path'] == "")
    {
        $strOutName = $classJobExportHelper_Main->getOutputFileFullPath("_runjobs_notnamed_");
    }

    if($arrSourceFiles != null)
    {
        __debug__printLine(PHP_EOL."**************  Loading jobs from ". count($arrSourceFiles) ." user input source files **************  ".PHP_EOL, C__DISPLAY_NORMAL__);
        $classJobExportHelper_Main->loadMyJobsListFromCSVs($arrSourceFiles);
        addJobsToJobsList($arrListAllJobsFromSearches, $classJobExportHelper_Main->getMyJobsList());
    }


    __debug__printLine(PHP_EOL."**************  Adding jobs from " . count($arrSearches) . " searches ***************".PHP_EOL, C__DISPLAY_NORMAL__);

    $classMulti = new ClassMultiSiteSearch($GLOBALS["bit_flags"], $strOutputFolder);
    $classMulti->addSearches($arrSearches);
    $classMulti->downloadAllUpdatedJobs( $GLOBALS['OPTS']['number_days']);
    addJobsToJobsList($arrListAllJobsFromSearches, $classMulti->getMyJobsList());


    if($arrSitesSettings['Amazon']['include_in_run'] == true)
    {
        __debug__printLine("Adding Amazon jobs....", C__DISPLAY_ITEM_START__);
        $class = new ClassAmazonJobs($GLOBALS["bit_flags"], $strOutputFolder);
        $class->downloadAllUpdatedJobs( $GLOBALS['OPTS']['number_days']);
        addJobsToJobsList($arrListAllJobsFromSearches, $class->getMyJobsList());
        $arrOutputFilesToIncludeInResults[] = $class->writeMyJobsListToFile();
    }

    __debug__printLine(PHP_EOL."**************  " . count($arrListAllJobsFromSearches) . " job listings from all sources have been loaded.  **************  ".PHP_EOL, C__DISPLAY_NORMAL__);

    __debug__printLine(PHP_EOL."**************  Updating jobs list for known filters ***************".PHP_EOL, C__DISPLAY_NORMAL__);
    $classJobExportHelper_Main->markJobsList_withAutoItems($arrListAllJobsFromSearches, "RUNNER");

    __debug__printLine(PHP_EOL."**************  Writing final list of " . count($arrListAllJobsFromSearches) . " to filtered and all results files.  ***************  ".PHP_EOL, C__DISPLAY_NORMAL__);
    $class = null;

    // Write to the main output file name that the user passed in
    $classJobExportHelper_Main->writeJobsListToFile($strOutName, $arrListAllJobsFromSearches, $fIncludeFilteredJobsInResults);

    $strOutDetailsFilteredName = getFullPathFromFileDetails(parseFilePath($strOutName), "", "_filtered");
    $classJobExportHelper_Main->writeJobsListToFile($strOutDetailsFilteredName, $arrListAllJobsFromSearches, false);

    $strOutDetailsAllResultsName = getFullPathFromFileDetails(parseFilePath($strOutName), "", "_alljobs");
    $classJobExportHelper_Main->writeJobsListToFile($strOutDetailsAllResultsName , $arrListAllJobsFromSearches, true);


    __debug__printLine(PHP_EOL."**************  DONE.  Cleaning up.  **************  ".PHP_EOL, C__DISPLAY_NORMAL__);

    $arrRunAutoMark = $arrListAllJobsFromSearches;
    $classJobExportHelper_Main->markJobsList_SetAutoExcludedTitles($arrRunAutoMark, "run_jobs");
    $arrAutoDupes = array_filter($arrRunAutoMark, "isMarked_AutoDupe");
    $arrAllAutoMarked = array_filter($arrRunAutoMark, "isMarked_Auto");
    $arrNotInterested = array_filter($arrRunAutoMark, "isMarked_NotInterested");
    $arrInteresting = array_filter($arrRunAutoMark, "isMarked_InterestedOrBlank");


    __debug__printLine("Total jobs:  ".count($arrListAllJobsFromSearches). PHP_EOL."Interesting: ". count($arrInteresting) . " / ". count($arrNotInterested) . PHP_EOL. "Auto-Marked: ".count($arrAllAutoMarked). " / Dupes: ".count($arrAutoDupes) , C__DISPLAY_SUMMARY__);

    var_dump($GLOBALS['CNT']);
}

?>