<?php

    /**
     * Copyright 2014-17 Bryan Selner
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
require_once dirname(dirname(dirname(__FILE__)))."/bootstrap.php";

abstract class AlternateAdicioCareerCastPlugin extends BaseAdicioCareerCastPlugin
{
    protected $nJobListingsPerPage = 20;
}

abstract class BaseAdicioCareerCastPlugin extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = '';
    protected $siteBaseURL = '';
    protected $childSiteURLBase = '';
    protected $nJobListingsPerPage = 50;
    protected $strBaseURLPathSuffix = "";
    protected $strBaseURLFormat = null;

    // postDate param below could also be modifiedDate =***NUMBER_DAYS***.  Unclear which is more correct when...
    //
    // BUGBUG: setting "search job title only" seems to not find jobs with just one word in the title.  "Pharmacy Intern" does not come back for "intern" like it should.  Therefore not setting the kwsJobTitleOnly=true flag.
    //
    protected $strBaseURLPathSection = "/jobs/results/keyword/***KEYWORDS***?view=List_Detail&SearchNetworks=US&networkView=national&location=***LOCATION***&radius=50&sort=PostDate+desc%2C+Priority+desc%2C+score+desc&rows=50&page=***PAGE_NUMBER***&postDate=***NUMBER_DAYS***";
#    protected $strBaseURLPathSection = "/jobs/search/results?kwsJobTitleOnly=true&view=List_Detail&networkView=national&radius=50&&sort=PostDate+desc%2C+Priority+desc%2C+score+desc&rows=50&page=***PAGE_NUMBER***&postDate=***NUMBER_DAYS***";
    protected $additionalLoadDelaySeconds = 10;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode-comma-countrycode';

    protected $arrListingTagSetup = array(
        'tag_listings_noresults' => array(array('tag' => 'div', 'attribute'=>'id', 'attribute_value' => 'aiAppWrapperBox'), array('tag' => 'form'), array('tag' => 'h2'), 'return_attribute' => 'plaintext'),
    );

    function __construct($strOutputDirectory = null)
    {
        $this->additionalFlags[] = C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES;
        $this->paginationType = C__PAGINATION_PAGE_VIA_URL;

        $this->siteBaseURL = $this->childSiteURLBase;
        $this->strBaseURLFormat = $this->childSiteURLBase . $this->strBaseURLPathSection . $this->strBaseURLPathSuffix;
        parent::__construct($strOutputDirectory);
    }

    protected function getKeywordURLValue($searchDetails) {
        $searchDetails['keywords_string_for_url'] = strtolower($searchDetails['keywords_string_for_url']);
        return parent::getKeywordURLValue($searchDetails);
    }

    /**
     * If the site does not have a URL parameter for number of days
     * then set the plugin flag to C__JOB_DAYS_VALUE_NOTAPPLICABLE__
     * in the PluginOptions.php file and just comment out this function.
     *
     * getDaysURLValue returns the value that is used to replace
     * the ***DAYS*** token in the search URL for the number of
     * days requested.
     *
     * @param $days
     * @return int|string
     */
    function getDaysURLValue($days = null)
    {
        $ret = "%5BNOW-1DAYS+TO+NOW%5D";

        if($days != null)
        {
            switch($days)
            {
                case ($days>1 && $days<8):
                    $ret = "%5BNOW-7DAYS+TO+NOW%5D";
                    break;

                case ($days>7):
                    $ret = "%5BNOW-28DAYS+TO+NOW%5D";
                    break;


                case $days<=1:
                default:
                    $ret = "%5BNOW-1DAYS+TO+NOW%5D";
                    break;

            }
        }

        return $ret;
    }



    /**
     * parseTotalResultsCount
     *
     * If the site does not show the total number of results
     * then set the plugin flag to C__JOB_PAGECOUNT_NOTAPPLICABLE__
     * in the PluginOptions.php file and just comment out this function.
     *
     * parseTotalResultsCount returns the total number of listings that
     * the search returned by parsing the value from the returned HTML
     * *
     * @param $objSimpHTML
     * @return string|null
     */
    function parseTotalResultsCount($objSimpHTML)
    {
        $noResultsText = null;
        $totalItemsText = C__TOTAL_ITEMS_UNKNOWN__;

        //
        // Find the HTML node that holds the no results text
        //
        $resultsSection = $objSimpHTML->find("div#aiSearchResultsSuccess h2");
        if($resultsSection && isset($resultsSection[0]))
        {
            // get the text value of that node
            $noResultsText = $resultsSection[0]->plaintext;
        }
        else
        {
            //
            // Find the HTML node that holds the no results text
            //
            $resultsSection = $objSimpHTML->find("div#arNoResultsContainer div h5");
            if($resultsSection && isset($resultsSection[0]))
            {
                // get the text value of that node
                $noResultsText = $resultsSection[0]->plaintext;
            }

        }

        if(!is_null($noResultsText))
        {
            if(strcasecmp($noResultsText, "Oops! Nothing was found.") == 0)
            {
                $totalItemsText = 0;
            }

        }

        if($totalItemsText != 0) {
            //
            // Find the HTML node that holds the result count
            //
            $resultsSection = $objSimpHTML->find("span[id='retCountNumber']");

            if ($resultsSection && isset($resultsSection[0])) {
                // get the text value of that node
                $totalItemsText = $resultsSection[0]->plaintext;
            }
        }

        return $totalItemsText;
    }

    /**
    /**
     * parseJobsListForPage
     *
     * This does the heavy lifting of parsing each job record from the
     * page's HTML it was passed.
     * *
     * @param $objSimpHTML
     * @return array|null
     */
    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;
        $item = null;

        // first looked for the detail view layout and parse that
        $nodesJobRows = $objSimpHTML->find('div[class="aiResultsWrapper"]');
        if(isset($nodesJobRows) && count($nodesJobRows) > 0 )
        {
            foreach($nodesJobRows as $node)
            {
                //
                // get a new record with all columns set to null
                //
                $item = getEmptyJobListingRecord();


                $divMain = $node->find("div[class='aiResultsMainDiv']");
                if(isset($divMain) && count($divMain) >= 1)
                {
                    $divID = $divMain[0]->attr['id'];
                    $item['job_id']= preg_replace('/[^\d]*/', "", $divID);
                }

                $titleLink = $node->find("a")[0];
                $item['job_title'] = $titleLink->plaintext;

                // If we couldn't parse the job title, it's not really a job
                // listing so just continue to the next one
                //
                if($item['job_title'] == '') continue;


                $item['job_post_url'] =  $titleLink->href;


                $detailLIs = $node->find("ul li");
                $item['company'] = $detailLIs[0]->plaintext;
                $item['location'] = $detailLIs[1]->plaintext;
                $item['job_site_date'] = $detailLIs[2]->plaintext;
                $item['job_site_category'] = $detailLIs[3]->plaintext;

                $ret[] = $item;
            }
        }
        else
        {
            // it's the brief layout, so use that



            $nodesJobRows= $objSimpHTML->find('tr[class="aiResultsRow"]');
            if(isset($nodesJobRows))
            {
                foreach($nodesJobRows as $node)
                {
                    //
                    // get a new record with all columns set to null
                    //
                    $item = getEmptyJobListingRecord();

                    $titleLink = $node->find("a")[0];
                    $item['job_title'] = $titleLink->plaintext;


                    $item['job_post_url'] = $titleLink->href;
                    $arrURLParts= explode("-", $item['job_post_url']);
                    $item['job_id'] = $arrURLParts[count($arrURLParts) - 2];


                    // If we couldn't parse the job title, it's not really a job
                    // listing so just continue to the next one
                    //
                    if($item['job_title'] == '') continue;

                    $locNode = $node->find("td[class='aiResultsLocation']");
                    $item['location'] = $locNode[0]->plaintext;

                    $companyNode = $node->find("td[class='aiResultsCompany']");
                    $item['company'] = $companyNode[0]->plaintext;

                    $ret[] = $item;
                }
            }

            else
            {
                return parent::parseJobsListForPage($objSimpHTML);
            }
        }

        return $ret;
    }

}


abstract class OptimizedAdicioCareerCastPlugin extends BaseAdicioCareerCastPlugin
{
    function __construct($strOutputDirectory = null)
    {
        $this->strBaseURLPathSection = str_replace("/jobs/results/keyword/***KEYWORDS***", "/jobs/search/results", $this->strBaseURLPathSection);
        $this->strBaseURLPathSection = str_replace("&kwsMustContain=***KEYWORDS***", "", $this->strBaseURLPathSection);
        parent::__construct($strOutputDirectory);
    }

}


class PluginMashable extends OptimizedAdicioCareerCastPlugin
{
    protected $siteName = 'Mashable';
    protected $childSiteURLBase = 'http://jobs.mashable.com';
    // Note:  Mashable has a short list of jobs (< 500-1000 total) so we exclude keyword search here as an optimization.  We may download more jobs overall, but through fewer round trips to the servers
//    protected $strBaseURLPathSection = "/jobs/results/keyword/***KEYWORDS***?location=***LOCATION***&kwsMustContain=***KEYWORDS***&radius=50&view=List_Detail&sort=PostType+asc%2C+PostDate+desc%2C+IsFeatured+desc&rows=50&modifiedDate=***NUMBER_DAYS***%page=***PAGE_NUMBER***&PostDate=***NUMBER_DAYS***";
//    protected $strBaseURLPathSection = "/jobs/search/results?location=***LOCATION***&radius=50&view=List_Detail&sort=PostType+asc%2C+PostDate+desc%2C+IsFeatured+desc&rows=50&modifiedDate=***NUMBER_DAYS***%page=***PAGE_NUMBER***&PostDate=***NUMBER_DAYS***";
    protected $strBaseURLPathSection = "/jobs/search/results?location=***LOCATION***&radius=50&view=List_Detail&sort=PostType+asc%2C+PostDate+desc%2C+IsFeatured+desc&rows=50&modifiedDate=***NUMBER_DAYS***&SearchNetworks=US&networkView=national#PostDate=***NUMBER_DAYS***&page=***PAGE_NUMBER***";
    protected $typeLocationSearchNeeded = 'location-city-comma-state';

}

class PluginLocalworkCA extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'LocalworkCA';
    protected $childSiteURLBase = 'http://jobs.localwork.ca';
}

class PluginASME extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'ASME';
    protected $childSiteURLBase = 'http://jobsearch.asme.org';
}

class PluginJacksonville extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Jacksonville';
    protected $childSiteURLBase = 'http://http://jobs.jacksonville.com';
}


class PluginPolitico extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Politico';
    protected $childSiteURLBase = 'http://jobs.powerjobs.com';
}

class PluginIEEE extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'IEEE';
    protected $childSiteURLBase = 'http://jobs.ieee.org';
}
class PluginVariety extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Variety';
    protected $childSiteURLBase = 'http://jobs.variety.com';
}
class PluginCellCom extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'CellCom';
    protected $childSiteURLBase = 'http://jobs.cell.com';
}
class PluginCareerJet extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'CareerJet';
    protected $childSiteURLBase = 'http://www.careerjet.co.uk';
}
class PluginVirginiaPilot extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'VirginiaPilot';
    protected $childSiteURLBase = 'http://careers.hamptonroads.com';
}
class PluginHamptonRoads extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'HamptonRoads';
    protected $childSiteURLBase = 'http://careers.hamptonroads.com';
}
class PluginAnalyticTalent extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'AnalyticTalent';
    protected $childSiteURLBase = 'http://careers.analytictalent.com';
}

class PluginKenoshaNews extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'KenoshaNews';
    protected $childSiteURLBase = 'http://kenosha.careers.adicio.com';
}

class PluginTopekaCapitalJournal extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'TopekaCapitalJournal';
    protected $childSiteURLBase = 'http://jobs.cjonline.com';
}

class PluginRetailCareersNow extends AlternateAdicioCareerCastPlugin
{
    protected $siteName = 'RetailCareersNow';
    protected $childSiteURLBase = 'http://retail.careers.adicio.com';
}
class PluginHealthJobs extends OptimizedAdicioCareerCastPlugin
{
    protected $siteName = 'HealthJobs';
    protected $childSiteURLBase = 'http://healthjobs.careers.adicio.com';
}
class PluginPharmacyJobCenter extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'PharmacyJobCenter';
    protected $childSiteURLBase = 'http://pharmacy.careers.adicio.com';
}
class PluginKCBD extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'KCBD';
    protected $childSiteURLBase = 'http://kcbd.careers.adicio.com';
}
class PluginAfro extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Afro';
    protected $childSiteURLBase = 'http://afro.careers.adicio.com';
}
class PluginJamaCareerCenter extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'JamaCareerCenter';
    protected $childSiteURLBase = 'http://jama.careers.adicio.com';
}

class PluginSeacoastOnline extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'SeacoastOnline';
    protected $childSiteURLBase = 'http://seacoast.careers.adicio.com';
}

class PluginAlbuquerqueJournal extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'AlbuquerqueJournal';
    protected $childSiteURLBase = 'http://abqcareers.careers.adicio.com';
}

class PluginWestHawaiiToday extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'WestHawaiiToday';
    protected $childSiteURLBase = 'http://careers.westhawaiitoday.com';
}

class PluginDeadline extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Deadline';
    protected $childSiteURLBase = 'http://jobsearch.deadline.com';
}

class PluginLogCabinDemocrat extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'LogCabinDemocrat';
    protected $childSiteURLBase = 'jobs.thecabin.net';
}

class PluginPennEnergy extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'PennEnergy';
    protected $childSiteURLBase = 'http://careers.pennenergyjobs.com';
}

class PluginBig4Firms extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Big4Firms';
    protected $childSiteURLBase = 'http://careers.big4.com';
}

class PluginVindy extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Vindy';
    protected $childSiteURLBase = 'http://careers.vindy.com';
}

class PluginAdicioCareerCast extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'CareerCast';
    protected $childSiteURLBase = 'http://www.careercast.com';
}

class PluginTheLancet extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'TheLancet';
    protected $childSiteURLBase = 'http://careers.thelancet.com';
}


class PluginClevelandDotCom extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'ClevelandDotCom';
    protected $childSiteURLBase = 'http://jobs.cleveland.com';
}

class PluginVictoriaTXAdvocate extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'VictoriaTXAdvocate';
    protected $childSiteURLBase = 'http://jobs.crossroadsfinder.com';
}

class PluginTVB extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'TVB';
    protected $childSiteURLBase = 'http://postjobs.tvb.org';
}

class PluginOregonLive extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'OregonLive';
    protected $childSiteURLBase = 'http://jobs.oregonlive.com';
}

class PluginSHRM extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'SHRM';
    protected $childSiteURLBase = 'http://hrjobs.shrm.org';
}

class PluginCareerCastIT extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastIT";
    protected $childSiteURLBase = "http://it.careercast.com";
}

class PluginCareerCastHealthcare extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastHealthcare";
    protected $childSiteURLBase = "http://healthcare.careercast.com";
}

class PluginCareerCastNursing extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastNursing";
    protected $childSiteURLBase = "http://nursing.careercast.com";
}

class PluginCareerCastTempJobs extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastTempJobs";
    protected $childSiteURLBase = "http://tempjobs.careercast.com";
}

class PluginCareerCastMarketing extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastMarketing";
    protected $childSiteURLBase = "http://marketing.careercast.com";
}

class PluginCareerCastRetail extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastRetail";
    protected $childSiteURLBase = "http://retail.careercast.com";
}

class PluginCareerCastGreenNetwork extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastGreenNetwork";
    protected $childSiteURLBase = "http://green.careercast.com";
}

class PluginCareerCastDiversity extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastDiversity";
    protected $childSiteURLBase = "http://diversity.careercast.com";
}

class PluginCareerCastConstruction extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastConstruction";
    protected $childSiteURLBase = "http://construction.careercast.com";
}

class PluginCareerCastEnergy extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastEnergy";
    protected $childSiteURLBase = "http://energy.careercast.com";
}

class PluginCareerCastTrucking extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastTrucking";
    protected $childSiteURLBase = "http://trucking.careercast.com";
}

class PluginCareerCastDisability extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastDisability";
    protected $childSiteURLBase = "http://disability.careercast.com";
}

class PluginCareerCastHR extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastHR";
    protected $childSiteURLBase = "http://hr.careercast.com";
}

class PluginCareerCastVeteran extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastVeteran";
    protected $childSiteURLBase = "http://veteran.careercast.com";
}

class PluginCareerCastHospitality extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastHospitality";
    protected $childSiteURLBase = "http://hospitality.careercast.com";
}

class PluginCareerCastFinance extends BaseAdicioCareerCastPlugin
{
    protected $siteName = "CareerCastFinance";
    protected $childSiteURLBase = "http://finance.careercast.com";
}

