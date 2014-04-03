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


const C_STR_DATAFOLDER = '/Users/bryan/Code/data/';
const C_STR_FOLDER_JOBSEARCH= '/Users/bryan/Dropbox/Job Search 2013/';






/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Function:  Pulling the Active Jobs                                                         ****/
/****                                                                                                        ****/
/****************************************************************************************************************/



function __runAllJobs__($incAmazon= 1, $inclCraigslist = 0, $incSimplyHired = 1, $incIndeed = 1, $arrSourceFiles = null, $nDays = -1)
{

    $G_FINALOUTPUT_FILE_NAME = C_STR_FOLDER_JOBSEARCH . getDefaultJobsOutputFileName("ALL-", "jobs", "csv");



    $arrDownloadedJobsFiles = array();



    if($incIndeed)
    {
        __debug__printLine("Adding Indeed jobs....", C__DISPLAY_ITEM_START__);
        $classIndeed = new ClassIndeed(null, C_NORMAL);
        $classIndeed->setOutputFolder(C_STR_DATAFOLDER   . 'indeed_jobs');
        $arrDownloadedJobsFiles[] = $classIndeed->downloadAllUpdatedJobs($nDays );
    }

    if($incSimplyHired)
    {
        __debug__printLine("Adding SimplyHired jobs....", C__DISPLAY_ITEM_START__);
        $classSimply= new ClassSimplyHired(null, C_NORMAL);
        $classSimply->setOutputFolder(C_STR_DATAFOLDER  . 'simply_jobs');
        $arrDownloadedJobsFiles[] = $classSimply->downloadAllUpdatedJobs($nDays);
    }


    if($inclCraigslist)
    {
        __debug__printLine("Adding Craigslist jobs....", C__DISPLAY_ITEM_START__);
        $classCraig= new ClassCraigslist(null, C_NORMAL);
        $classCraig->setOutputFolder(C_STR_DATAFOLDER  . 'craigslist_jobs');
        $arrDownloadedJobsFiles[] = $classCraig->downloadAllUpdatedJobs($nDays);
    }

    if($incAmazon)
    {
        __debug__printLine("Adding Amazon jobs....", C__DISPLAY_ITEM_START__);
        $classAmazon= new ClassAmazonJobs(null, C_NORMAL);
        $classAmazon->setOutputFolder(C_STR_DATAFOLDER  . 'amzn_jobs');
        $arrDownloadedJobsFiles[] = $classAmazon->downloadAllUpdatedJobs($nDays);
    }



    if($arrSourceFiles  && is_array($arrSourceFiles))
    {
        foreach($arrSourceFiles as $source)
        {
            __debug__printLine("Adding source file jobs from " . $source, C__DISPLAY_ITEM_START__);
            $arrDownloadedJobsFiles[] = $source;
        }
    }


    __debug__printLine("Combining records from these files: " .var_export($arrDownloadedJobsFiles, true), C__DISPLAY_ITEM_START__);

    $classCombined = new SimpleScooterCSVFileClass($G_FINALOUTPUT_FILE_NAME, "w");
    $classCombined->combineMultipleCSVs($arrDownloadedJobsFiles);

    __debug__printLine("Download complete to " . $G_FINALOUTPUT_FILE_NAME , C__DISPLAY_ITEM_START__);

}

?>
