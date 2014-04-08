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
/****                                                                                                        ****/
/****         Helper Function:  Pulling the Active Jobs                                                         ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

function __runCommandLine($arrSearches = null, $arrInputFiles = null)
{
    $bitFlagsForRun = C_NORMAL;
    __initializeArgs__();

    $GLOBALS['SITES_SUPPORTED']['Amazon'] =  array('site_name' => 'Amazon', 'include_in_run' => false, 'working_subfolder' => 'amazon_jobs');



    __getPassedArgs__();



    __runAllJobs__($arrSearches, null, $strOutputDir, $arrInputFiles , $nDays, $fIncludeFilteredListings  );

}

function __runAllJobs__($arrSearches, $strOutputFile = null, $arrSourceFiles = null, $nDays = -1, $fIncludeFilteredJobsInResults = null)
{
    $arrSitesSettings =  $GLOBALS['SITES_SUPPORTED'];

    if(!$arrSitesSettings || !is_array($arrSitesSettings))
    {
        $arrSitesSettings = $g_arrJobSitesList;
    }

    $arrOutputFilesToIncludeInResults = array();


    __debug__printLine("Adding listings for ". count($arrSearches) ." job searches....", C__DISPLAY_ITEM_START__);
    $classMulti = new ClassMultiSiteSearch();
    $classMulti->setOutputFolder(C_STR_DATAFOLDER);
    $classMulti->addSearches($arrSearches);
    $classMulti->downloadAllUpdatedJobs( $GLOBALS['OPTS']['number_days'], null, $fIncludeFilteredJobsInResults );
    $arrOutputFilesToIncludeInResults[] = $classMulti->getMyJobsList();


    if($arrSitesSettings['Amazon']['include_in_run'] == true)
    {
        __debug__printLine("Adding Amazon jobs....", C__DISPLAY_ITEM_START__);
        $class = new ClassAmazonJobs(null, $bitFlagsForRun );
        $class ->setOutputFolder(C_STR_DATAFOLDER  .  $arrSitesSettings['Amazon']['working_subfolder'] . "/");
        $arrOutputFilesToIncludeInResults[] = $class->downloadAllUpdatedJobs( $GLOBALS['OPTS']['number_days'], null, $fIncludeFilteredJobsInResults );
    }

    $class = null;

    $classJobExportHelper = new ClassJobsSiteExport();
    $classJobExportHelper->setOutputFolder(C_STR_DATAFOLDER);

    if($arrSourceFiles  && is_array($arrSourceFiles))
    {
        foreach($arrSourceFiles as $source)
        {
            //
            // Push the source files to the front of the array so that they
            // get into the final jobs list first.  That way the others are
            // de-duped against them, not the other way around.  Helps prevent
            // input data loss.
            //
            array_push($arrOutputFilesToIncludeInResults, $source);
            // __debug__printLine("Adding input source file: " . $source, C__DISPLAY_ITEM_START__);
        }
    }

    if(!$strOutputFile_Filtered )
    {
        $strOutputFile_Filtered = $classJobExportHelper->getOutputFileFullPath("ALL-", "jobs", "csv");

    }
    $strOutputFile_Unfiltered = $classJobExportHelper->getOutputFileFullPath("ALL-unfiltered-", "jobs", "csv");


    $retFile = $classJobExportHelper->writeMergedJobsCSVFile($strOutputFile_Filtered, $arrOutputFilesToIncludeInResults, null, $fIncludeFilteredJobsInResults);
    if(!$retFile)
    {
        throw new ErrorException("Failed to combine new job lists with source files.");
    }

    if($fIncludeFilteredJobsInResults == null)
    {

        $retFile = $classJobExportHelper->writeMergedJobsCSVFile($strOutputFile_Unfiltered, $arrOutputFilesToIncludeInResults, null, false);
        if(!$retFile)
        {
            throw new ErrorException("Failed to combine new job lists with source files.");
        }
    }

    __debug__printLine("Complete. Results written to " . $strOutputFile_Filtered , C__DISPLAY_RESULT__);


}

?>