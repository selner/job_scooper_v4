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
require_once dirname(__FILE__) . '/include/Functions-RunJobs.php';


/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Class:  Pulling the Active Jobs from Amazon's site                                      ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

// function __runAllJobs__($incAmazon= 1, $inclCraigslist = 0, $incSimplyHired = 1, $incIndeed = 1, $arrSourceFiles = null, $nDays = -1)


$arrBryanTrackingFiles = array(
    C_STR_DATAFOLDER . 'bryans_list_active.csv',
    C_STR_DATAFOLDER . 'bryans_list_inactive.csv'
);

// Run AMZN
// __runAllJobs__(1, 0, 0, 0, $arrBryanTrackingFiles);

// Run All but CL
//__runAllJobs__(1, 0, 1, 1, $arrBryanTrackingFiles);


// Get the last 7 days worth from Indeed & SimplyHired
// __runAllJobs__(0, 0, 1, 1, $arrBryanTrackingFiles, 7);
// __runAllJobs__(1, 0, 0, 0, $arrBryanTrackingFiles, 1);




$arrSitesSettings = $g_arrJobSitesList;
$arrSitesSettings['Indeed']['include_in_run'] = true;
$arrSitesSettings['Amazon']['include_in_run'] = true;
$arrSitesSettings['SimplyHired']['include_in_run'] = true;


__runAllJobs__($arrSitesSettings, null, $arrBryanTrackingFiles, 1, false);
