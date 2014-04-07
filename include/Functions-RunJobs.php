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


/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Function:  Pulling the Active Jobs                                                         ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

function __runCommandLine()
{
    __get_ScooperUtil_args__();

    $arrJobSitesList = array(
        'Amazon' => array('site_name' => 'Amazon', 'include_in_run' => false, 'working_subfolder' => 'amazon_jobs'),
//        'Craigslist' => array('site_name' => 'Craigslist', 'include_in_run' => false, 'working_subfolder' => 'craigslist_jobs'),
        'Indeed' => array('site_name' => 'Indeed', 'include_in_run' => false, 'working_subfolder' => 'indeed_jobs'),
        'SimplyHired' => array('site_name' => 'SimplyHired', 'include_in_run' => false, 'working_subfolder' => 'simply_jobs'),
    );


    foreach($arrJobSitesList as $site)
    {
        $arrJobSitesList[$site['site_name']]['include_in_run'] = is_IncludeSite($site['site_name']);
    }



    $arrBryanTrackingFiles = array(
        C_STR_DATAFOLDER . 'bryans_list_active.csv',
        C_STR_DATAFOLDER . 'bryans_list_inactive.csv'
    );

    $nDays = get_PharseOptionValue('number_days');
    if($nDays == false) { $nDays = 1; }
    $fIncludeFilteredListings = true;
    if($GLOBALS['OPTS']['filter_notinterested_given'])
    {
        $fIncludeFilteredListings = false;
    }

    $strOutputDir =  get_PharseOptionValue("output_folder");
    if($strOutputDir == false)  { $strOutputDir  = null; }


    __runAllJobs__($arrJobSitesList, $strOutputDir, $arrBryanTrackingFiles, $nDays, $fIncludeFilteredListings  );

}

function __runAllJobs__($arrSitesSettings = null, $strOutputFile_Filtered = null, $arrSourceFiles = null, $nDays = -1, $fIncludeFilteredJobsInResults = null)
{
    if(!$arrSitesSettings || !is_array($arrSitesSettings))
    {
        $arrSitesSettings = $g_arrJobSitesList;
    }

    $arrOutputFilesToIncludeInResults = array();


    /*
    $class = new ClassAmazonJobs(null, C_NORMAL);
    $class ->setOutputFolder(C_STR_DATAFOLDER  .  $arrSitesSettings['Amazon']['working_subfolder'] . "/");
    $retPath = $class->getJobs_NewSite(false);
    $arrRet = $class->getMyJobsList();
    // var_dump('$arrRet = ', $arrRet);
    // var_dump('$retPath = ', $retPath);


    exit("test");
*/

    if($arrSitesSettings['Indeed']['include_in_run'] == true)
    {
        __debug__printLine("Adding Indeed jobs....", C__DISPLAY_ITEM_START__);
        $class  = new ClassIndeed(null, C_NORMAL);
        $class ->setOutputFolder(C_STR_DATAFOLDER . $arrSitesSettings['Indeed']['working_subfolder'] . "/");
        $arrOutputFilesToIncludeInResults[] = $class->downloadAllUpdatedJobs($nDays, null, $fIncludeFilteredJobsInResults );
    }

    if($arrSitesSettings['SimplyHired']['include_in_run'] == true)
    {
        __debug__printLine("Adding SimplyHired jobs....", C__DISPLAY_ITEM_START__);
        $class = new ClassSimplyHired(null, C_NORMAL);
        $class ->setOutputFolder(C_STR_DATAFOLDER  .  $arrSitesSettings['SimplyHired']['working_subfolder'] . "/");
        $arrOutputFilesToIncludeInResults[] = $class->downloadAllUpdatedJobs($nDays, null, $fIncludeFilteredJobsInResults );
    }


    if($arrSitesSettings['Craigslist']['include_in_run'] == true)
    {
        __debug__printLine("Adding Craigslist jobs....", C__DISPLAY_ITEM_START__);
        $class = new ClassCraigslist(null, C_NORMAL);
        $class ->setOutputFolder(C_STR_DATAFOLDER  . $arrSitesSettings['Craigslist']['working_subfolder'] . "/");
        $arrOutputFilesToIncludeInResults[] = $class->downloadAllUpdatedJobs($nDays, null, $fIncludeFilteredJobsInResults );
    }

    if($arrSitesSettings['Amazon']['include_in_run'] == true)
    {
        __debug__printLine("Adding Amazon jobs....", C__DISPLAY_ITEM_START__);
        $class = new ClassAmazonJobs(null, C_NORMAL);
        $class ->setOutputFolder(C_STR_DATAFOLDER  .  $arrSitesSettings['Amazon']['working_subfolder'] . "/");
        $arrOutputFilesToIncludeInResults[] = $class->downloadAllUpdatedJobs($nDays, null, $fIncludeFilteredJobsInResults );
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


    $strOutputFile_Unfiltered = str_ireplace("_filtered", "_unfiltered", $strOutputFile_Filtered);
    if(strcasecmp($strOutputFile_Unfiltered, $strOutputFile_Filtered) == 0)
        $strOutputFile_Unfiltered = "unfiltered_" . $strOutputFile_Filtered;


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