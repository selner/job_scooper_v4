<?php

    /**
     * Copyright 2014-16 Bryan Selner
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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');


abstract class BaseAdicioCareerCastPlugin extends ClassBaseClientSideHTMLJobSitePlugin
{
    protected $siteName = '';
    protected $siteBaseURL = '';
    protected $childSiteURLBase = '';
    protected $nJobListingsPerPage = 50;
    protected $strBaseURLPathSuffix = "";
    protected $strBaseURLFormat = null;

    // postDate param below could also be modifiedDate =***NUMBER_DAYS***.  Unclear which is more correct when...
    protected $strBaseURLPathSection = "/jobs/results/keyword/***KEYWORDS***?kwsJobTitleOnly=true&view=List_Detail&networkView=national&location=***LOCATION***&radius=50&sort=PostDate+desc%2C+Priority+desc%2C+score+desc&rows=50&page=***PAGE_NUMBER***&postDate=***NUMBER_DAYS***";
#    protected $strBaseURLPathSection = "/jobs/search/results?kwsJobTitleOnly=true&view=List_Detail&networkView=national&radius=50&&sort=PostDate+desc%2C+Priority+desc%2C+score+desc&rows=50&page=***PAGE_NUMBER***&postDate=***NUMBER_DAYS***";
    protected $additionalLoadDelaySeconds = 10;
    protected $typeLocationSearchNeeded = 'location-city-comma-state';


    function __construct($strOutputDirectory = null)
    {
        $this->additionalFlags[] = C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES;
        $this->siteBaseURL = $this->childSiteURLBase;
        $this->strBaseURLFormat = $this->childSiteURLBase . $this->strBaseURLPathSection . $this->strBaseURLPathSuffix;
        parent::__construct($strOutputDirectory);
    }

    protected function _getURLfromBase_($searchDetails, $nPage = null, $nItem = null)
    {
        return parent::_getURLfromBase_($searchDetails, $nPage, $nItem);
    }

    protected function getKeywordURLValue($searchDetails) {
        $searchDetails['keywords_string_for_url'] = strtolower($searchDetails['keywords_string_for_url']);
        return parent::getKeywordURLValue($searchDetails);
    }

    function getPageURLValue($page)
    {
        if($page == 1) { return 0; } else {return $page; }
    }

    /**
     * If the site does not have a URL parameter for number of days
     * then set the plugin flag to C__JOB_DAYS_VALUE_NOTAPPLICABLE__
     * in the SitePlugins.php file and just comment out this function.
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
     * in the SitePlugins.php file and just comment out this function.
     *
     * parseTotalResultsCount returns the total number of listings that
     * the search returned by parsing the value from the returned HTML
     * *
     * @param $objSimpHTML
     * @return string|null
     */
    function parseTotalResultsCount($objSimpHTML)
    {

        //
        // Find the HTML node that holds the result count
        //
        $resultsSection = $objSimpHTML->find("span[id='retCountNumber']");

        if($resultsSection && isset($resultsSection[0]))
        {
            // get the text value of that node
            $totalItemsText = $resultsSection[0]->plaintext;
        }
        else
            $totalItemsText = 0;

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
                $item = $this->getEmptyJobListingRecord();


                $item['job_site'] = $this->siteName;
                $item['date_pulled'] = getTodayAsString();


                $titleLink = $node->find("a")[0];
                $item['job_title'] = $titleLink->plaintext;

                // If we couldn't parse the job title, it's not really a job
                // listing so just continue to the next one
                //
                if($item['job_title'] == '') continue;


                $item['job_post_url'] = $this->siteBaseURL . $titleLink->href;
                $arrURLParts= explode("-", $item['job_post_url']);
                $item['job_id'] = $arrURLParts[count($arrURLParts) - 2];



                $detailLIs = $node->find("ul li");
                $item['company'] = $detailLIs[0]->plaintext;
                $item['location'] = $detailLIs[1]->plaintext;
                $item['job_site_date'] = $detailLIs[2]->plaintext;
                $item['job_site_category'] = $detailLIs[3]->plaintext;

                //
                // Call normalizeItem to standardize the resulting listing result
                //
                $ret[] = $this->normalizeItem($item);

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
                    $item = $this->getEmptyJobListingRecord();


                    $item['job_site'] = $this->siteName;
                    $item['date_pulled'] = getTodayAsString();


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

                    //
                    // Call normalizeItem to standardize the resulting listing result
                    //
                    $ret[] = $this->normalizeItem($item);

                }
            }
        }

        return $ret;
    }

}

class PluginMashable extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Mashable';
    protected $childSiteURLBase = 'http://jobs.mashable.com';
    protected $strBaseURLPathSection = "/jobs/results/keyword/***KEYWORDS***?location=***LOCATION***&kwsMustContain=***KEYWORDS***&radius=50&view=List_Detail&sort=PostType+asc%2C+PostDate+desc%2C+IsFeatured+desc&rows=50&modifiedDate=***NUMBER_DAYS***%page=***PAGE_NUMBER***&PostDate=***NUMBER_DAYS***";

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

class PluginRetailCareersNow extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'RetailCareersNow';
    protected $childSiteURLBase = 'http://retail.careers.adicio.com';
}
class PluginHealthJobs extends BaseAdicioCareerCastPlugin
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
    protected $childSiteURLBase = 'http://jobshome.crossroadsfinder.com';
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

