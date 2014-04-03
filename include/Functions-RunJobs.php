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
require_once dirname(__FILE__) . '/../src/ClassAmazonJobs.php';
require_once dirname(__FILE__) . '/../src/ClassCraigslist.php';
require_once dirname(__FILE__) . '/../src/ClassIndeed.php';
require_once dirname(__FILE__) . '/../src/ClassSimplyHired.php';


//
// Default settings for the job sites
//
$g_arrJobSitesList = array(
    'Amazon' => array('site_name' => 'Amazon', 'include_in_run' => false, 'working_subfolder' => 'amazon_jobs'),
    'Craigslist' => array('site_name' => 'Craigslist', 'include_in_run' => false, 'working_subfolder' => 'craigslist_jobs'),
    'Indeed' => array('site_name' => 'Indeed', 'include_in_run' => false, 'working_subfolder' => 'indeed_jobs'),
    'SimplyHired' => array('site_name' => 'SimplyHired', 'include_in_run' => false, 'working_subfolder' => 'simply_jobs'),
);



/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Function:  Pulling the Active Jobs                                                         ****/
/****                                                                                                        ****/
/****************************************************************************************************************/



function __runAllJobs__($arrSitesSettings = null, $strOutputFile = null, $arrSourceFiles = null, $nDays = -1, $fIncludeFilteredJobsInResults = true)
{
    if(!$arrSitesSettings || !is_array($arrSitesSettings))
    {
        $arrSitesSettings = $g_arrJobSitesList;
    }


    $arrOutputFilesToIncludeInResults = array();

    if($arrSourceFiles  && is_array($arrSourceFiles))
    {
        $arrOutputFilesToIncludeInResults = array_copy($arrSourceFiles);
        __debug__printLine("Adding source file jobs from " . var_export($arrSourceFiles, true), C__DISPLAY_ITEM_START__);
    }


    if($arrSitesSettings['Indeed']['include_in_run'] == true)
    {
        __debug__printLine("Adding Indeed jobs....", C__DISPLAY_ITEM_START__);
        $class  = new ClassIndeed(null, C_NORMAL);
        $class ->setOutputFolder(C_STR_DATAFOLDER  .  $arrSitesSettings['Indeed']['working_subfolder']);
        $arrOutputFilesToIncludeInResults[] = $class->downloadAllUpdatedJobs($nDays, $arrSourceFiles, $fIncludeFilteredJobsInResults );
    }

    if($arrSitesSettings['SimplyHired']['include_in_run'] == true)
    {
        __debug__printLine("Adding SimplyHired jobs....", C__DISPLAY_ITEM_START__);
        $class = new ClassSimplyHired(null, C_NORMAL);
        $class ->setOutputFolder(C_STR_DATAFOLDER  . $arrSitesSettings['SimplyHired']['working_subfolder']);
        $arrOutputFilesToIncludeInResults[] = $class->downloadAllUpdatedJobs($nDays, $arrSourceFiles, $fIncludeFilteredJobsInResults );
    }


    if($arrSitesSettings['Craigslist']['include_in_run'] == true)
    {
        __debug__printLine("Adding Craigslist jobs....", C__DISPLAY_ITEM_START__);
        $class = new ClassCraigslist(null, C_NORMAL);
        $class ->setOutputFolder(C_STR_DATAFOLDER  . $arrSitesSettings['Craigslist']['working_subfolder']);
        $arrOutputFilesToIncludeInResults[] = $class->downloadAllUpdatedJobs($nDays, $arrSourceFiles, $fIncludeFilteredJobsInResults );
    }

    if($arrSitesSettings['Amazon']['include_in_run'] == true)
    {
        __debug__printLine("Adding Amazon jobs....", C__DISPLAY_ITEM_START__);
        $class = new ClassAmazonJobs(null, C_NORMAL);
        $class ->setOutputFolder(C_STR_DATAFOLDER  . $arrSitesSettings['Amazon']['working_subfolder']);
        $arrOutputFilesToIncludeInResults[] = $class->downloadAllUpdatedJobs($nDays, $arrSourceFiles, $fIncludeFilteredJobsInResults );
    }

    $class = null;

    __debug__printLine("Combining records from these files: " .var_export($arrOutputFilesToIncludeInResults, true), C__DISPLAY_ITEM_START__);
    $classJobExportHelper = new ClassJobsSiteExport();
    $classJobExportHelper->setOutputFolder(C_STR_DATAFOLDER);

    if(!$strOutputFile )
    {
        $strOutputFile = $classJobExportHelper->getOutputFileFullPath("ALL-", "jobs", "csv");
    }

    $retFile = $classJobExportHelper->combineMultipleJobsCSVs($strOutputFile, $arrOutputFilesToIncludeInResults, $fIncludeFilteredJobsInResults);
    if($retFile)
    {
        __debug__printLine("Combined results written to " . $strOutputFile , C__DISPLAY_ITEM_START__);
    }

    __debug__printLine("Run completed.", C__DISPLAY_ITEM_START__);

}

?>