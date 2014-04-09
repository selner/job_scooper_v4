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
require_once dirname(__FILE__) . '/include/JobSiteClasses.php';


/****************************************************************************************************************/
/**************                                                                                                         ****/
/**************          Helper Class:  Pulling the Active Jobs from Amazon's site                                      ****/
/**************                                                                                                         ****/
/****************************************************************************************************************/



$arrSearches = array(
    array('site_name' => 'Craigslist', 'search_name' => "exec titles", 'base_url_format' => "http://seattle.craigslist.org/search/jjj?s=***ITEM_NUMBER***&catAbb=jjj&query=%22Vice%20President%22%20%7C%20%22Chief%20Technology%20Office%22%20%7C%20%22Chief%20Products%20Officer%22%20%7C%20%22CTO%22%20%7C%20%22CPO%22%20%7C%20%22VP%22%20%7C%20%22V.P.%22%20%7C%20%22Director%22%20%7C%20%20%22product%20management%22%20%7C%20%22general%20manager%22%20&srchType=T"),
    array('site_name' => 'Porch', 'search_name' => "all jobs", 'base_url_format' => "http://about.porch.com/careers/"),
    array('site_name' => 'SimplyHired', 'search_name' => "exec titles", 'base_url_format' => "http://www.simplyhired.com/search?t=%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22product+management%22+or+%22general+manager%22+or+%22Chief+Technology+Officer%22&lc=Seattle&ls=WA&fdb=***NUMBER_DAYS***&&ws=50&sb=dd&pn=start=***PAGE_NUMBER***"),
    array('site_name' => 'Indeed', 'search_name' => "exec titles", 'base_url_format' => "http://www.indeed.com/jobs?q=title%3A%28%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22Chief+Technology+Officer%22%29&l=Seattle%2C+WA&sort=date&limit=50&fromage=***NUMBER_DAYS***&start=***ITEM_NUMBER***"),
    array('site_name' => 'Glassdoor', 'search_name' => "vice president", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-vice-president-job-opportunities-SRCH_IL.0,7_IC1150505_KO8,22_IP***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "director product", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-director-product-job-opportunities-SRCH_IL.0,7_IC1150505_KO8,24_IP***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "chief product officer", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-chief-product-officer-job-openings-SRCH_IL.0,7_IC1150505_KO8,29_IP***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
);

$arrSearches = array(
    array('site_name' => 'Craigslist', 'search_name' => "exec titles", 'base_url_format' => "http://seattle.craigslist.org/search/jjj?s=***ITEM_NUMBER***&catAbb=jjj&query=%22Vice%20President%22%20%7C%20%22Chief%20Technology%20Office%22%20%7C%20%22Chief%20Products%20Officer%22%20%7C%20%22CTO%22%20%7C%20%22CPO%22%20%7C%20%22VP%22%20%7C%20%22V.P.%22%20%7C%20%22Director%22%20%7C%20%20%22product%20management%22%20%7C%20%22general%20manager%22%20&srchType=T"),
    array('site_name' => 'Porch', 'search_name' => "all jobs", 'base_url_format' => "http://about.porch.com/careers/"),
    array('site_name' => 'SimplyHired', 'search_name' => "exec titles", 'base_url_format' => "http://www.simplyhired.com/search?t=%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22product+management%22+or+%22general+manager%22+or+%22Chief+Technology+Officer%22&lc=Seattle&ls=WA&fdb=***NUMBER_DAYS***&&ws=50&sb=dd&pn=start=***PAGE_NUMBER***"),
    array('site_name' => 'Indeed', 'search_name' => "exec titles", 'base_url_format' => "http://www.indeed.com/jobs?q=title%3A%28%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22Chief+Technology+Officer%22%29&l=Seattle%2C+WA&sort=date&limit=50&fromage=***NUMBER_DAYS***&start=***ITEM_NUMBER***"),
    array('site_name' => 'Glassdoor', 'search_name' => "vice president", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-vice-president-job-opportunities-SRCH_IL.0,7_IC1150505_KO8,22_IP***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "director product", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-director-product-job-opportunities-SRCH_IL.0,7_IC1150505_KO8,24_IP***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "chief product officer", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-chief-product-officer-job-openings-SRCH_IL.0,7_IC1150505_KO8,29_IP***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
);

$GLOBALS['OPTS']['titles_to_filter_input_file'] = C_STR_DATAFOLDER  . "bryans_list_exclude_titles.csv";

$arrBryanTrackingFiles = array(
    C_STR_DATAFOLDER . 'bryans_list_active.csv',
    C_STR_DATAFOLDER . 'bryans_list_inactive.csv'
);



__runCommandLine($arrSearches, $arrBryanTrackingFiles);

?>