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
const C_STR_FOLDER_JOBSEARCH = '/Users/bryan/Dropbox/JobSearch-and-Consulting/JobPosts-Tracking/';

function debug_GetStaticSiteSearchesOnly($arrSearches)
{
    $arrRet = array();

    foreach($arrSearches as $search )
    {
        switch($search['site_name'])
        {
            case "Groupon":
            case "Outerwall":
            case "Porch":
            case "Expedia":
            case "eBay":
                $arrRet[] = $search;
                break;

            default:
                break;
        }

    }

    return $arrRet;

}

$arrBryanSearches = array(
    array('site_name' => 'Groupon', 'search_name' => "all seattle jobs", 'base_url_format' => "https://jobs.groupon.com/careers/seattle-wa-united-states"),
    array('site_name' => 'EmploymentGuide', 'search_name' => "director", 'base_url_format' => "http://seattle.employmentguide.com/searchresults.php?page=***PAGE_NUMBER***&q=director&l=seattle%2C+wa&radius=20&sort=date&posted_after=***NUMBER_DAYS***"),
    array('site_name' => 'EmploymentGuide', 'search_name' => "senior manager", 'base_url_format' => "http://seattle.employmentguide.com/searchresults.php?page=***PAGE_NUMBER***&q=senior+manager&l=seattle%2C+wa&radius=20&sort=date&posted_after=***NUMBER_DAYS***"),
    array('site_name' => 'Facebook', 'search_name' => "all SEA jobs", 'base_url_format' => "https://www.facebook.com/careers/locations/seattle"),
    array('site_name' => 'Tableau', 'search_name' => "all jobs", 'base_url_format' => "https://ch.tbe.taleo.net/CH11/ats/careers/searchResults.jsp?org=TABLEAU&cws=1&act=next&rowFrom=***ITEM_NUMBER***"),
    array('site_name' => 'Outerwall', 'search_name' => "all jobs in WA", 'base_url_format' => "http://outerwall.jobs/washington/usa/jobs/"),
    array('site_name' => 'Disney', 'search_name' => "all digital/online in WA", 'base_url_format' => "http://disneycareers.com/en/search-jobs/jobsearch-results/?jqs=%5B%7B%22c%22%3A%22US%257CCA%22%2C%22s%22%3A%22WA%22%2C%22g%22%3A%22Seattle%22%2C%22co%22%3A%22%22%2C%22in%22%3A%22Corporate%252COnline%252CDigital%2520%252F%2520Interactive%22%2C%22p%22%3A%22%22%2C%22jc%22%3A%22%22%2C%22e%22%3A%22%22%2C%22q%22%3A%22%22%2C%22r%22%3A%22%22%7D%5D"),
    array('site_name' => 'Glassdoor', 'search_name' => "director product management", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-director-product-management-job-listings-SRCH_IL.0,7_IC1150505_KO8,35***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Expedia', 'search_name' => "all jobs", 'base_url_format' => "http://expediajobs.findly.com/candidate/job_search/advanced/results/***PAGE_NUMBER***?job_type=5517&state=2336&country=5492&sort=date"),
    array('site_name' => 'Porch', 'search_name' => "all jobs", 'base_url_format' => "http://about.porch.com/careers/"),
    array('site_name' => 'SimplyHired', 'search_name' => "exec titles", 'base_url_format' => "http://www.simplyhired.com/search?t=%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22product+management%22+or+%22general+manager%22+or+%22Chief+Technology+Officer%22&lc=Seattle&ls=WA&fdb=***NUMBER_DAYS***&&ws=50&sb=dd&pn=***PAGE_NUMBER***"),
    array('site_name' => 'Indeed', 'search_name' => "exec titles", 'base_url_format' => "http://www.indeed.com/jobs?q=title%3A%28%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22Chief+Technology+Officer%22%29&l=Seattle%2C+WA&sort=date&limit=50&fromage=***NUMBER_DAYS***&start=***ITEM_NUMBER***"),
    array('site_name' => 'Glassdoor', 'search_name' => "vice president", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-vice-president-job-openings-SRCH_IL.0,7_IC1150505_KO8,22***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "director product", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-director-product-job-opportunities-SRCH_IL.0,7_IC1150505_KO8,24***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "chief product officer", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-chief-product-officer-job-openings-SRCH_IL.0,7_IC1150505_KO8,29***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'Glassdoor', 'search_name' => "director ux", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-director-ux-job-openings-SRCH_IL.0,7_IC1150505_KO8,16_KE17,19***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
    array('site_name' => 'LinkUp', 'search_name' => "exec titles", 'base_url_format' => "http://www.linkup.com/results.php?q=title%3A%28%22vice+president%22+or+VP+or+director+or+CTO+or+CPO+or+director+or+%22chief+product+officer%22+or+%22Chief+Technology+Officer%22%29&l=seattle%2C+washington&sort=d&tm==***NUMBER_DAYS***d&page=***PAGE_NUMBER***"),
    array('site_name' => 'Monster', 'search_name' => "chief product", 'base_url_format' => "http://jobsearch.monster.com/search/CPO+Product+Chief-Product-Officer+Full-Time+Contract_55588?tm=Today&where=seattle__2c-wa&tm=***NUMBER_DAYS***&pg=***PAGE_NUMBER***"),
    array('site_name' => 'Monster', 'search_name' => "vice president, director", 'base_url_format' => "http://jobsearch.monster.com/search/Director+VP+Vice-President_555?where=seattle__2C-wa&tm=***NUMBER_DAYS***&pg=***PAGE_NUMBER***"),
    array('site_name' => 'CareerBuilder', 'search_name' => "director", 'base_url_format' => "http://www.careerbuilder.com/jobseeker/jobs/jobresults.aspx?qb=1&SB%3Asbkw=director&SB%3As_freeloc=SEATTLE%2C+WA&SB%3Asbfr=***NUMBER_DAYS***&sbsbmt=Find+Jobs&IPath=ILKGV&excrit=freeLoc%3DSEATTLE%2C+WA%3Bst%3DA%3Buse%3DALL%3BrawWords%3Ddirector%3BTID%3D0%3BCTY%3DSeattle%3BSID%3DWA%3BCID%3DUS%3BLOCCID%3DUS%3BENR%3DNO%3BDTP%3DDRNS%3BYDI%3DYES%3BIND%3DALL%3BPDQ%3DAll%3BPDQ%3DAll%3BPAYL%3D0%3BPAYH%3DGT120%3BPOY%3DNO%3BETD%3DJTFT%3BRE%3DALL%3BMGT%3DDC%3BSUP%3DDC%3BFRE%3D3%3BCHL%3DAL%3BQS%3DADVSEARCHFRM%3BSS%3DNO%3BTITL%3D0%3BOB%3D-relv%3BRAD%3D30%3BJQT%3DRAD%3BJDV%3DFalse%3BSITEENT%3DUSJOB%3BMaxLowExp%3D-1%3BRecsPerPage%3D25&cid=US&pg=***PAGE_NUMBER***&IPath=ILKGV"),
    array('site_name' => 'CareerBuilder', 'search_name' => "vice president", 'base_url_format' => "http://www.careerbuilder.com/jobseeker/jobs/jobresults.aspx?as%3AMXJobSrchCriteria_JobQueryType=RAD&as%3Ach=al&as%3AMXJobSrchCriteria_QuerySrc=AdvSearchFrm&as%3AIPath=QA&qb=1&as%3As_rawwords=vice+president&as%3As_use=ALL&as%3As_freeloc=SEATTLE%2C+WA&as%3As_freeloc=&as%3As_freeloc=&as%3As_radius=30&as%3As_freshness=***NUMBER_DAYS****&as%3AMXJOBSRCHCRITERIA_Industries=&as%3As_jobtypes=ALL&as%3As_jobtypes=ALL&as%3As_jobtypes=ALL&as%3As_education=DRNS&as%3As_includelowereducationvalues=YES&s_includelowereducationvalues_hidden=&as%3As_emptype=JTFT&as%3As_paylow=0&as%3As_payhigh=gt120&as%3Aexkw=&as%3Aexjl=&as%3Aexcn=&pg=***PAGE_NUMBER***&SearchBtn=Find+Jobs"),
    array('site_name' => 'CareerBuilder', 'search_name' => "director product", 'base_url_format' => "http://www.careerbuilder.com/jobseeker/jobs/jobresults.aspx?qb=1&SB%3Asbkw=director+product&SB%3As_freeloc=seattle%2C+wa&SB%3Asbfr=***NUMBER_DAYS***&sbsbmt=Find+Jobs&IPath=QAKVGV&excrit=freeLoc%3Dseattle%2C+wa%3Bst%3DA%3Buse%3DALL%3BrawWords%3Ddirector+product%3BTID%3D0%3BCTY%3DSeattle%3BSID%3DWA%3BCID%3DUS%3BLOCCID%3DUS%3BENR%3DYES%3BDTP%3DDRNS%3BYDI%3DYES%3BIND%3DALL%3BPDQ%3DAll%3BPDQ%3DAll%3BPAYL%3D0%3BPAYH%3DGT120%3BPOY%3DNO%3BETD%3DJTFT%3BETD%3DJTCT%3BRE%3DALL%3BMGT%3DDC%3BSUP%3DDC%3BFRE%3D7%3BCHL%3DAL%3BQS%3DADVSEARCHFRM%3BSS%3DNO%3BTITL%3D0%3BOB%3D-relv%3BRAD%3D20%3BJQT%3DRAD%3BJDV%3DFalse%3BSITEENT%3DUSJOB%3BMaxLowExp%3D-1%3BRecsPerPage%3D25&cid=US&pg=***PAGE_NUMBER***&findjob=sb"),
    array('site_name' => 'Mashable', 'search_name' => "senior manager", 'base_url_format' => "http://jobs.mashable.com/jobs/results/keyword/senior-manager?kwsJobTitleOnly=true&location=Seattle%2C+Washington%2C+United+States&radius=25&view=List_Detail&page=***PAGE_NUMBER***&sort=PostType+asc%2C+PostDate+desc%2C+IsFeatured+desc&rows=50&modifiedDate=***NUMBER_DAYS***"),
    array('site_name' => 'Mashable', 'search_name' => "vice president", 'base_url_format' => "http://jobs.mashable.com/jobs/results/keyword/vice-president?kwsJobTitleOnly=true&location=Seattle%2C+Washington%2C+United+States&radius=25&view=List_Detail&page=***PAGE_NUMBER***&sort=PostType+asc%2C+PostDate+desc%2C+IsFeatured+desc&rows=50&modifiedDate=***NUMBER_DAYS***"),
    array('site_name' => 'Mashable', 'search_name' => "director", 'base_url_format' => "http://jobs.mashable.com/jobs/results/keyword/director?kwsJobTitleOnly=true&location=Seattle%2C+Washington%2C+United+States&radius=25&view=List_Detail&page=***PAGE_NUMBER***&sort=PostType+asc%2C+PostDate+desc%2C+IsFeatured+desc&rows=50&modifiedDate=***NUMBER_DAYS***"),
    array('site_name' => 'Mashable', 'search_name' => "product", 'base_url_format' => "http://jobs.mashable.com/jobs/results/keyword/product?kwsJobTitleOnly=true&location=Seattle%2C+Washington%2C+United+States&radius=25&view=List_Detail&page=***PAGE_NUMBER***&sort=PostType+asc%2C+PostDate+desc%2C+IsFeatured+desc&rows=50&modifiedDate=***NUMBER_DAYS***"),
    array('site_name' => 'Craigslist', 'search_name' => "exec titles", 'base_url_format' => "http://seattle.craigslist.org/search/jjj?s=***ITEM_NUMBER***&catAbb=jjj&query=%22Vice%20President%22%20%7C%20%22Chief%20Technology%20Office%22%20%7C%20%22Chief%20Products%20Officer%22%20%7C%20%22CTO%22%20%7C%20%22CPO%22%20%7C%20%22VP%22%20%7C%20%22V.P.%22%20%7C%20%22Director%22%20%7C%20%20%22product%20management%22%20%7C%20%22general%20manager%22%20&srchType=T"),
    array('site_name' => 'Craigslist', 'search_name' => "exec titles", 'base_url_format' => "http://seattle.craigslist.org/search/jjj?s=***ITEM_NUMBER***&catAbb=jjj&query=%22Vice%20President%22%20%7C%20%22Chief%20Technology%20Office%22%20%7C%20%22Chief%20Products%20Officer%22%20%7C%20%22CTO%22%20%7C%20%22CPO%22%20%7C%20%22VP%22%20%7C%20%22V.P.%22%20%7C%20%22Director%22%20%7C%20%20%22product%20management%22%20%7C%20%22general%20manager%22%20&srchType=T"),
    array('site_name' => 'Google', 'search_name' => "all Seattle/Kirkland jobs", 'base_url_format' => "https://www.google.com/about/careers/search/#t=sq&q=j&***ITEM_NUMBER***&jl=47.6062095%253A-122.3320708%253ASeattle%252C+WA%252C+USA%253Anull%253Aundefined%253A9.903894146066163%253ALOCALITY&jl=47.6814875%253A-122.2087353%253AKirkland%252C+WA%252C+USA%253Anull%253Aundefined%253A5.173281946960293%253ALOCALITY&"),
    array('site_name' => 'eBay', 'search_name' => "all WA jobs", 'base_url_format' => "http://jobs.ebaycareers.com/search/advanced-search/ASCategory/-1/ASPostedDate/-1/ASCountry/-1/ASState/Washington/ASCity/-1/ASLocation/-1/ASCompanyName/-1/ASCustom1/-1/ASCustom2/-1/ASCustom3/-1/ASCustom4/-1/ASCustom5/-1/ASIsRadius/false/ASCityStateZipcode/-1/ASDistance/-1/ASLatitude/-1/ASLongitude/-1/ASDistanceType/-1"),
//    array('site_name' => 'Geekwire', 'search_name' => "director in WA", 'base_url_format' => "http://www.geekwork.com/jobs/?type=full-time&search_location=WA&search_keywords=director"),


//


//    array('site_name' => 'Verizon', 'search_name' => "all WA jobs", 'base_url_format' => "http://www.verizon.com/jobs/verizon/search-jobs/Washington-jobs-2?refineSearchViewAll=Y"),
//    VZW site uses JSON & HTML forms for results.  We could do a querystring hack like this: "http://www.verizon.com/jobs/jobsearchresultservlet?queryparenturl=&queryurl1=http%3A%2F%2Fvzweb.verizon.com%2Fsearch%2Fcgi-bin%2Fvelocity%3Fv.function%3Dquery-search%26v.app%3Dapi-rest%26v.username%3Dcoa-user%26v.password%3DVer!zon%26sources%3Dvz-careers%26query%3Djob%26start%3D0%26num%3D100%26sort-by%3Ddate%26query-condition-xpath%3D(%24city!%3D%27%27and%24city!%3D%27null%27)and(%24locationfulldetail%3D%27Washington%27or%24locationfulldetail%3D%27Washington%27or%24locationfulldetail%3D%27WASHINGTON%27)and%24business%3D%27vzw%27and%24contenttype%3D%27job%27and%24iscampus%3D%27N%27&seoqueryparenturl=http%3A%2F%2Fvzweb.verizon.com%2Fsearch%2Fcgi-bin%2Fvelocity%3Fv.function%3Dquery-search%26v.app%3Dapi-rest%26v.username%3Dcoa-user%26v.password%3DVer!zon%26sources%3Dvz-careers%26query%3Djob%26start%3D0%26num%3D100%26sort-by%3Ddate%26query-condition-xpath%3D(%24city!%3D%27%27and%24city!%3D%27null%27)and(%24locationfulldetail%3D%27Washington%27or%24locationfulldetail%3D%27Washington%27or%24locationfulldetail%3D%27WASHINGTON%27)and%24business%3D%27vzw%27and%24contenttype%3D%27job%27and%24iscampus%3D%27N%27&hdnSizeVal=100&hdnMaxResult=241&pagenumber=3&pagesizeresults=100&mode=pagination&sorttype=jobposted&sortmode=desc&lastpage=3&filtertype=&filtermode=&filtervalue=&addSelectedCategories=&addAllCategories=Clearance%7CCustomer+Service%2FClient+Care%7CEngineering%7CExecutive+Sales%7CFacilities+Management%7CFinance+And+Accounting%7CInformation+Technology%7CLegal+and+Regulatory%7CNetwork%7CRetail+Sales%7CSales%7CSolution+Sales%7CSolutions+Architect%7CTechnology%7CUX%7CUofP%7C&addCatCount=Clearance(1)%7CCustomer+Service%2FClient+Care(16)%7CEngineering(7)%7CExecutive+Sales(1)%7CFacilities+Management(1)%7CFinance+And+Accounting(1)%7CInformation+Technology(2)%7CLegal+and+Regulatory(1)%7CNetwork(4)%7CRetail+Sales(71)%7CSales(87)%7CSolution+Sales(44)%7CSolutions+Architect(1)%7CTechnology(1)%7CUX(2)%7CUofP(1)%7C&hdnSearchKeyword=&addSelectedLocations=&addAllLocations=Auburn%2CWA%2CUSA%7CBellevue%2CWA%2CUSA%7CBellingham%2CWA%2CUSA%7CBurlington%2CWA%2CUSA%7CCollege+Place%2CWA%2CUSA%7CCovington%2CWA%2CUSA%7CEverett%2CWA%2CUSA%7CFederal+Way%2CWA%2CUSA%7CIssaquah%2CWA%2CUSA%7CKennewick%2CWA%2CUSA%7CLacey%2CWA%2CUSA%7CLakewood%2CWA%2CUSA%7CLynnwood%2CWA%2CUSA%7CMarysville%2CWA%2CUSA%7COlympia%2CWA%2CUSA%7CPuyallup%2CWA%2CUSA%7CRedmond%2CWA%2CUSA%7CRenton%2CWA%2CUSA%7CSeattle%2CWA%2CUSA%7CSilverdale%2CWA%2CUSA%7CSpokane+Valley%2CWA%2CUSA%7CSpokane%2CWA%2CUSA%7CTacoma%2CWA%2CUSA%7CTukwila%2CWA%2CUSA%7CVancouver%2CWA%2CUSA%7CWashington%2CDC%2CUSA%7CWashington%2CNJ%2CUSA%7CWashington%2CPA%2CUSA%7CWenatchee%2CWA%2CUSA%7CWoodinville%2CWA%2CUSA%7CYakima%2CWA%2CUSA%7C&noofdays=734%20Response%20Headersview%20source"

);

$arrRudySearches= array(
    array('site_name' => 'Craigslist', 'search_name' => "real estate agent", 'base_url_format' => "http://seattle.craigslist.org/search/jjj?catAbb=jjj&query=%22real%20estate%20agent%22&s=***ITEM_NUMBER***"),
    array('site_name' => 'SimplyHired', 'search_name' => "real estate agent", 'base_url_format' => "http://www.simplyhired.com/search?q=real+estate+agent&l=seattle%2C+wa&pn=2fdb=***NUMBER_DAYS***&&ws=50&sb=dd&pn=start=***PAGE_NUMBER***"),
    array('site_name' => 'Indeed', 'search_name' => "real estate agent", 'base_url_format' => "http://www.indeed.com/jobs?as_and=&as_phr=%22real+estate+agent%22&as_any=&as_not=&as_ttl=&as_cmp=&jt=all&st=&salary=&radius=25&l=Seattle%2C+WA&fromage=any&limit=50&sort=date&psf=advsrch&start=***ITEM_NUMBER***"),
    array('site_name' => 'Glassdoor', 'search_name' => "real estate agent", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-real-estate-agent-job-listings-SRCH_IL.0,7_IC1150505_KO8,25_IP***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
);

$arrMichaelSearches= array(
    array('site_name' => 'Craigslist', 'search_name' => "analytics", 'base_url_format' => "http://seattle.craigslist.org/search/jjj?catAbb=jjj&query=%22analytics%20manager%22&s=***ITEM_NUMBER***"),
    array('site_name' => 'SimplyHired', 'search_name' => "real estate agent", 'base_url_format' => "http://www.simplyhired.com/search?q=analytics+manager&l=seattle%2C+wa&pn=2fdb=***NUMBER_DAYS***&&ws=50&sb=dd&pn=start=***PAGE_NUMBER***"),
    array('site_name' => 'Indeed', 'search_name' => "real estate agent", 'base_url_format' => "http://www.indeed.com/jobs?as_and=&as_phr=%22analytics+manager%22&as_any=&as_not=&as_ttl=&as_cmp=&jt=all&st=&salary=&radius=25&l=Seattle%2C+WA&fromage=any&limit=50&sort=date&psf=advsrch&start=***ITEM_NUMBER***"),
    array('site_name' => 'Glassdoor', 'search_name' => "real estate agent", 'base_url_format' => "http://www.glassdoor.com/Job/seattle-analytics-manager-job-openings-SRCH_IL.0,7_IC1150505_KO8,25_IP***PAGE_NUMBER***.htm?fromAge=***NUMBER_DAYS***"),
);


$arrBryanTrackingFiles = array(
    C_STR_FOLDER_JOBSEARCH . 'bryans_list_source_to_use/bryans_list_active.csv',
    C_STR_FOLDER_JOBSEARCH . 'bryans_list_source_to_use/bryans_list_inactive.csv',
    //C_STR_FOLDER_JOBSEARCH . 'bryans_list_source_to_use/bryans_list_inactives_before_jun_1_2014.csv',
);



$classRunJobs = new ClassJobsRunWrapper($arrBryanSearches, $arrBryanTrackingFiles, $GLOBALS['OPTS']['number_days']);
$classRunJobs->RunAll();

//__runCommandLine($arrBryanSearches, $arrBryanTrackingFiles);
//   __runAllJobs__($arrSearches, $arrInputFiles , $nDays, $fIncludeFilteredListings  );

?>
