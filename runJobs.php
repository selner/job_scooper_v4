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
require_once dirname(__FILE__) . '/include/SitePlugins.php';


/****************************************************************************************************************/
/**************                                                                                                         ****/
/**************          Helper Class:  Pulling the Active Jobs from Amazon's site                                      ****/
/**************                                                                                                         ****/
/****************************************************************************************************************/

const C_STR_DATAFOLDER = '/Users/bryan/Code/data/jobs/';
const C_STR_FOLDER_JOBSEARCH= '/Users/bryan/OneDrive/OneDrive-JobSearch/';

$GLOBALS['DEBUG'] = false;


$arrBryanSearches = array(
    array('site_name' => 'Craigslist', 'search_name' => "exec titles", 'base_url_format' => "http://seattle.craigslist.org/search/jjj?s=***ITEM_NUMBER***&catAbb=jjj&query=%22Vice%20President%22%20%7C%20%22Chief%20Technology%20Office%22%20%7C%20%22Chief%20Products%20Officer%22%20%7C%20%22CTO%22%20%7C%20%22CPO%22%20%7C%20%22VP%22%20%7C%20%22V.P.%22%20%7C%20%22Director%22%20%7C%20%20%22product%20management%22%20%7C%20%22general%20manager%22%20&srchType=T"),
    array('site_name' => 'Expedia', 'search_name' => "all jobs", 'base_url_format' => "http://expediajobs.findly.com/candidate/job_search/advanced/results/***PAGE_NUMBER***?job_type=5517&state=2336&country=5492&sort=date"),
    array('site_name' => 'Porch', 'search_name' => "all jobs", 'base_url_format' => "http://about.porch.com/careers/"),
    array('site_name' => 'SimplyHired', 'search_name' => "exec titles", 'base_url_format' => "http://www.simplyhired.com/search?t=%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22product+management%22+or+%22general+manager%22+or+%22Chief+Technology+Officer%22&lc=Seattle&ls=WA&fdb=***NUMBER_DAYS***&&ws=50&sb=dd&pn=***PAGE_NUMBER***"),
    array('site_name' => 'Indeed', 'search_name' => "exec titles", 'base_url_format' => "http://www.indeed.com/jobs?q=title%3A%28%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22Chief+Technology+Officer%22%29&l=Seattle%2C+WA&sort=date&limit=50&fromage=***NUMBER_DAYS***&start=***ITEM_NUMBER***"),
    array('site_name' => 'Glassdoor', 'search_name' => "vice president", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-vice-president-job-openings-SRCH_IL.0,7_IC1150505_KO8,22***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "director product", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-director-product-job-opportunities-SRCH_IL.0,7_IC1150505_KO8,24***PAGE_NUMBER***.htm?romAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "chief product officer", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-chief-product-officer-job-openings-SRCH_IL.0,7_IC1150505_KO8,29***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "director product management", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-director-product-management-job-listings-SRCH_IL.0,7_IC1150505_KO8,35***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "director ux", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-director-ux-job-openings-SRCH_IL.0,7_IC1150505_KO8,16_KE17,19***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'LinkUp', 'search_name' => "exec titles", 'base_url_format' => "http://www.linkup.com/results.php?q=title%3A%28%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22Chief+Technology+Officer%22%29&l=seattle%2C+washington&sort=d&tm==***NUMBER_DAYS***d&page=***PAGE_NUMBER***"),
//    array('site_name' => 'Monster', 'search_name' => "exec titles", 'base_url_format' => "http://jobsearch.monster.com/search/CPO+CTO+Chief-Product-Officer+Chief-Technology-Officer+Director+General-Manager+VP+Vice-President_55555555?where=seattle__2C-wa&tm=***NUMBER_DAYS***&pg=***PAGE_NUMBER***"),
    array('site_name' => 'CareerBuilder', 'search_name' => "director", 'base_url_format' => "http://www.careerbuilder.com/jobseeker/jobs/jobresults.aspx?qb=1&SB%3Asbkw=director&SB%3As_freeloc=SEATTLE%2C+WA&SB%3Asbfr=***NUMBER_DAYS***&sbsbmt=Find+Jobs&IPath=ILKGV&excrit=freeLoc%3DSEATTLE%2C+WA%3Bst%3DA%3Buse%3DALL%3BrawWords%3Ddirector%3BTID%3D0%3BCTY%3DSeattle%3BSID%3DWA%3BCID%3DUS%3BLOCCID%3DUS%3BENR%3DNO%3BDTP%3DDRNS%3BYDI%3DYES%3BIND%3DALL%3BPDQ%3DAll%3BPDQ%3DAll%3BPAYL%3D0%3BPAYH%3DGT120%3BPOY%3DNO%3BETD%3DJTFT%3BRE%3DALL%3BMGT%3DDC%3BSUP%3DDC%3BFRE%3D3%3BCHL%3DAL%3BQS%3DADVSEARCHFRM%3BSS%3DNO%3BTITL%3D0%3BOB%3D-relv%3BRAD%3D30%3BJQT%3DRAD%3BJDV%3DFalse%3BSITEENT%3DUSJOB%3BMaxLowExp%3D-1%3BRecsPerPage%3D25&cid=US&pg=***PAGE_NUMBER***&IPath=ILKGV"),
    array('site_name' => 'CareerBuilder', 'search_name' => "vice president", 'base_url_format' => "http://www.careerbuilder.com/jobseeker/jobs/jobresults.aspx?as%3AMXJobSrchCriteria_JobQueryType=RAD&as%3Ach=al&as%3AMXJobSrchCriteria_QuerySrc=AdvSearchFrm&as%3AIPath=QA&qb=1&as%3As_rawwords=vice+president&as%3As_use=ALL&as%3As_freeloc=SEATTLE%2C+WA&as%3As_freeloc=&as%3As_freeloc=&as%3As_radius=30&as%3As_freshness=***NUMBER_DAYS****&as%3AMXJOBSRCHCRITERIA_Industries=&as%3As_jobtypes=ALL&as%3As_jobtypes=ALL&as%3As_jobtypes=ALL&as%3As_education=DRNS&as%3As_includelowereducationvalues=YES&s_includelowereducationvalues_hidden=&as%3As_emptype=JTFT&as%3As_paylow=0&as%3As_payhigh=gt120&as%3Aexkw=&as%3Aexjl=&as%3Aexcn=&pg=***PAGE_NUMBER***&SearchBtn=Find+Jobs"),
    array('site_name' => 'CareerBuilder', 'search_name' => "director product", 'base_url_format' => "http://www.careerbuilder.com/jobseeker/jobs/jobresults.aspx?qb=1&SB%3Asbkw=director+product&SB%3As_freeloc=seattle%2C+wa&SB%3Asbfr=***NUMBER_DAYS***&sbsbmt=Find+Jobs&IPath=QAKVGV&excrit=freeLoc%3Dseattle%2C+wa%3Bst%3DA%3Buse%3DALL%3BrawWords%3Ddirector+product%3BTID%3D0%3BCTY%3DSeattle%3BSID%3DWA%3BCID%3DUS%3BLOCCID%3DUS%3BENR%3DYES%3BDTP%3DDRNS%3BYDI%3DYES%3BIND%3DALL%3BPDQ%3DAll%3BPDQ%3DAll%3BPAYL%3D0%3BPAYH%3DGT120%3BPOY%3DNO%3BETD%3DJTFT%3BETD%3DJTCT%3BRE%3DALL%3BMGT%3DDC%3BSUP%3DDC%3BFRE%3D7%3BCHL%3DAL%3BQS%3DADVSEARCHFRM%3BSS%3DNO%3BTITL%3D0%3BOB%3D-relv%3BRAD%3D20%3BJQT%3DRAD%3BJDV%3DFalse%3BSITEENT%3DUSJOB%3BMaxLowExp%3D-1%3BRecsPerPage%3D25&cid=US&pg=***PAGE_NUMBER***&findjob=sb"),


);

$arrRudySearches= array(
    array('site_name' => 'Craigslist', 'search_name' => "real estate agent", 'base_url_format' => "http://seattle.craigslist.org/search/jjj?catAbb=jjj&query=%22real%20estate%20agent%22&s=***ITEM_NUMBER***"),
    array('site_name' => 'SimplyHired', 'search_name' => "real estate agent", 'base_url_format' => "http://www.simplyhired.com/search?q=real+estate+agent&l=seattle%2C+wa&pn=2fdb=***NUMBER_DAYS***&&ws=50&sb=dd&pn=start=***PAGE_NUMBER***"),
    array('site_name' => 'Indeed', 'search_name' => "real estate agent", 'base_url_format' => "http://www.indeed.com/jobs?as_and=&as_phr=%22real+estate+agent%22&as_any=&as_not=&as_ttl=&as_cmp=&jt=all&st=&salary=&radius=25&l=Seattle%2C+WA&fromage=any&limit=50&sort=date&psf=advsrch&start=***ITEM_NUMBER***"),
    array('site_name' => 'Glassdoor', 'search_name' => "real estate agent", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-real-estate-agent-job-listings-SRCH_IL.0,7_IC1150505_KO8,25_IP***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
);

$arrTestSearches = array(
    array('site_name' => 'CareerBuilder', 'search_name' => "director product", 'base_url_format' => "http://www.careerbuilder.com/jobseeker/jobs/jobresults.aspx?as%3AMXJobSrchCriteria_JobQueryType=RAD&as%3Ach=al&as%3AMXJobSrchCriteria_QuerySrc=AdvSearchFrm&as%3AIPath=QA&qb=1&as%3As_rawwords=product+director&as%3As_use=ALL&as%3As_freeloc=SEATTLE%2C+WA&as%3As_freeloc=&as%3As_freeloc=&as%3As_radius=30&as%3As_freshness=***NUMBER_DAYS***&pg=***PAGE_NUMBER***&as%3AMXJOBSRCHCRITERIA_Industries=&as%3As_jobtypes=ALL&as%3As_jobtypes=ALL&as%3As_jobtypes=ALL&as%3As_education=DRNS&as%3As_includelowereducationvalues=YES&s_includelowereducationvalues_hidden=&as%3As_emptype=JTFT&as%3A&as%3Aexkw=&as%3Aexjl=&as%3Aexcn=&SearchBtn=Find+Jobs"),
);



$GLOBALS['OPTS']['titles_to_filter_input_file'] = C_STR_DATAFOLDER  . "bryans_list_exclude_titles.csv";

$arrBryanTrackingFiles = array(
    C_STR_DATAFOLDER . 'bryans_list_active.csv',
    C_STR_DATAFOLDER . 'bryans_list_inactive.csv',
    '/Users/bryan/OneDrive/OneDrive-JobSearch/bryans_list_source_to_use/bryans_list_inactives_pre_2014_04_18.csv'
);


__runCommandLine($arrBryanSearches, $arrBryanTrackingFiles);

?>
