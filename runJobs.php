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
require_once dirname(__FILE__) . '/include/scooter_utils_common.php';
require_once dirname(__FILE__) . '/src/ClassAmazonJobs.php';
require_once dirname(__FILE__) . '/src/ClassCraigsList.php';
require_once dirname(__FILE__) . '/src/ClassIndeed.php';
require_once dirname(__FILE__) . '/src/ClassSimplyHired.php';


/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Class:  Pulling the Active Jobs                                                         ****/
/****                                                                                                        ****/
/****************************************************************************************************************/


// $classCL = new ClassCraigslist();
// $classCL->getJobs();



$strDataPathBase = dirname(__FILE__).'/../data';
$arrCSVResultsFiles = array();

$strInputFile =  '/Users/bryan/Dropbox/Job Search 2013/Company Research/AMZNJobs-Bryan-Tracking-List.csv';

/*
$arrCSVResultsFiles[] = 'SimplyHired_20140402-020411_jobs_.csv' ;
$arrCSVResultsFiles[] = 'SimplyHired_20140402-020428_jobs_.csv' ;
$classTest= new SimpleScooterCSVFileClass("foo.csv", "w");

$classTest->combineMultipleCSVs($arrCSVResultsFiles);
*/

//
// Read in all the rows I already had in my list
//

if(file_exists($strInputFile ) && is_file($strInputFile))
{
    $arrCSVResultsFiles[] = $strInputFile;

}

var_dump($arrCSVResultsFiles);

__debug__printLine("Adding Indeed jobs....", C__DISPLAY_ITEM_START__);
// $classIndeed = new ClassIndeed(C_NORMAL, $strDataPathBase .'/indeed_jobs/results.html');
$classIndeed = new ClassIndeed(C_NORMAL, null);
$classIndeed->getJobs();
$arrCSVResultsFiles[] = $classIndeed->getOutputFileFullPath();
var_dump($arrCSVResultsFiles);


__debug__printLine("Adding SimplyHired jobs....", C__DISPLAY_ITEM_START__);
//$classSimply= new ClassSimplyHired(C_NORMAL, $strDataPathBase .'/simply_jobs/results.html');
$classSimply= new ClassSimplyHired(C_NORMAL, null);
$classSimply->getJobs();
$arrCSVResultsFiles[] = $classSimply->getOutputFileFullPath();

__debug__printLine("Combining Input, Indeed and SimplyHired.", C__DISPLAY_ITEM_START__);

$strCombinedFileName = $classSimply->getOutputFileName("Combined", "jobs", "csv");

$classCombined = new SimpleScooterCSVFileClass($strCombinedFileName, "w");
$classCombined->combineMultipleCSVs($arrCSVResultsFiles);
$arrCSVResultsFiles = array();
$arrCSVResultsFiles[] = $strCombinedFileName;


__debug__printLine("Input, Indeed and SimplyHired completed.", C__DISPLAY_ITEM_START__);
$classAmazon = new ClassAmazonJobs(C_NORMAL);

__debug__printLine("Adding Amazon New Site jobs...", C__DISPLAY_ITEM_START__);
$arrCSVResultsFiles[] = $classAmazon->getJobs_NewSite();
$classCombined->combineMultipleCSVs($arrCSVResultsFiles);
$arrCSVResultsFiles = array();
$arrCSVResultsFiles[] = $strCombinedFileName;


__debug__printLine("Adding Amazon Old Site PM jobs...", C__DISPLAY_ITEM_START__);
$arrCSVResultsFiles[] = $strCombinedFileName;
$arrCSVResultsFiles = array();
$arrCSVResultsFiles[] = $strCombinedFileName;

$arrCSVResultsFiles[] = $classAmazon->getJobs_OldSite_PMCategory();
__debug__printLine("Adding Amazon Old Site Keyword jobs...", C__DISPLAY_ITEM_START__);
$arrCSVResultsFiles[] = $classAmazon->getJobs_OldSite_Keywords();
$arrCSVResultsFiles = array();
$arrCSVResultsFiles[] = $strCombinedFileName;



$classCombined->combineMultipleCSVs($arrCSVResultsFiles);


?>
