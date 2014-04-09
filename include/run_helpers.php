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
    $GLOBALS["bit_flags"] = C_NORMAL;
    __initializeArgs__();

    $classInit = new ClassMultiSiteSearch($arrSearches);


    __getPassedArgs__();



    __runAllJobs__($arrSearches, $arrInputFiles , $nDays, $fIncludeFilteredListings  );

}


function __runAllJobs__($arrSearches, $arrSourceFiles = null, $nDays = -1, $fIncludeFilteredJobsInResults = null)
{
    $arrSitesSettings =  $GLOBALS['sites_supported'];
    $strOutputFolder = $GLOBALS['output_file_details']['directory'];

    $strOutputFile_Filtered = null;

    $arrOutputFilesToIncludeInResults = array();


    __debug__printLine("Adding listings for ". count($arrSearches) ." job searches....", C__DISPLAY_ITEM_START__);
    $classMulti = new ClassMultiSiteSearch();
    $classMulti->setOutputFolder($strOutputFolder);
    $classMulti->addSearches($arrSearches);
    $classMulti->downloadAllUpdatedJobs( $GLOBALS['OPTS']['number_days'], null, $fIncludeFilteredJobsInResults );
    $arrOutputFilesToIncludeInResults[] = $classMulti->writeMyJobsListToFile();


    if($arrSitesSettings['Amazon']['include_in_run'] == true)
    {
        __debug__printLine("Adding Amazon jobs....", C__DISPLAY_ITEM_START__);
        $class = new ClassAmazonJobs(null, $GLOBALS["bit_flags"]);
        $class ->setOutputFolder($strOutputFolder);
        $class->downloadAllUpdatedJobs( $GLOBALS['OPTS']['number_days'], null, $fIncludeFilteredJobsInResults );
        $arrOutputFilesToIncludeInResults[] = $class->writeMyJobsListToFile();
    }

    $class = null;

    $classJobExportHelper_Main = new ClassJobsSiteExport();
    $classJobExportHelper_Filtered = new ClassJobsSiteExport();
    $classJobExportHelper_Unfiltered = new ClassJobsSiteExport();
    $classJobExportHelper_Main->setOutputFolder($strOutputFolder);
    $classJobExportHelper_Filtered->setOutputFolder($strOutputFolder);
    $classJobExportHelper_Unfiltered->setOutputFolder($strOutputFolder);

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


    $arrOutDetails_Main = $GLOBALS['output_file_details'];
    if(strlen($arrOutDetails_Main['file_name_base'] == 0))
    {
        $classJobExportHelper_Main->setOutputFolder($arrOutDetails_Main['directory']);
        $strTemp = $classJobExportHelper_Main->getOutputFileFullPath("ALL-", "jobs", "csv");
        $arrOutDetails_Main = parseFilePath($strTemp, false);
    }

    $strTemp = $arrOutDetails_Main['directory']  . $arrOutDetails_Main['file_name_base']  ."_filtered".".".$arrOutDetails_Main['file_extension'];
    $arrOutDetails_Filtered = parseFilePath($strTemp );

    $strTemp = $arrOutDetails_Main['directory'] . $arrOutDetails_Main['file_name_base']  ."_unfiltered" .".".$arrOutDetails_Main['file_extension'];
    $arrOutDetails_Unfiltered = parseFilePath($strTemp );

    $retFile = $classJobExportHelper_Unfiltered->writeMergedJobsCSVFile($arrOutDetails_Unfiltered['full_file_path'], $arrOutputFilesToIncludeInResults, null, true);
    if(!$retFile)
    {
        throw new ErrorException("Failed to combine new job lists with source files.");
    }

    $retFile = $classJobExportHelper_Main->writeMergedJobsCSVFile($arrOutDetails_Main['full_file_path'], $arrOutputFilesToIncludeInResults, null, false);
    if(!$retFile)
    {
        throw new ErrorException("Failed to combine new job lists with source files.");
    }


    $retFile = $classJobExportHelper_Filtered->writeMergedJobsCSVFile($arrOutDetails_Filtered['full_file_path'], $arrOutputFilesToIncludeInResults, null, true);
    if(!$retFile)
    {
        throw new ErrorException("Failed to combine new job lists with source files.");
    }


    __debug__printLine("Complete run." , C__DISPLAY_RESULT__);

}

?>