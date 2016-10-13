<?php

    /**
     * Copyright 2014-15 Bryan Selner
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
require_once(__ROOT__ . '/include/ClassJobsSitePluginCommon.php');


class PluginMashable extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Mashable';
    protected $childSiteURLBase = 'http://jobs.mashable.com';
    protected $strBaseURLSuffix = "&SearchNetworks=US&networkView=national";


}
class PluginLocalworkCA extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'LocalworkCA';
    protected $childSiteURLBase = 'http://jobs.localwork.ca';
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
    protected $childSiteURLBase = 'http://jobs.cell.com/';
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
    protected $childSiteURLBase = 'http://kenosha.careers.adicio.com/';
}

class PluginRetailCareersNow extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'RetailCareersNow';
    protected $childSiteURLBase = 'http://retail.careers.adicio.com';
}
class PluginHealthJobs extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'HealthProfressionalsJobsPlus';
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


class PluginAdicioCareerCast extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'CareerCast';
    protected $childSiteURLBase = 'http://www.careercast.com';
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



abstract class BaseAdicioCareerCastPlugin extends ClassJobsSitePlugin
{
    protected $siteName = '';
    protected $siteBaseURL = '';
    protected $childSiteURLBase = '';
    protected $nJobListingsPerPage = 50;
    protected $strBaseURLPathSuffix = "";
    protected $strBaseURLFormat = null;

    // postDate param below could also be modifiedDate=***NUMBER_DAYS***.  Unclear which is more correct when...
#    protected $strBaseURLPathSection = "/jobs/results/keyword/***KEYWORDS***?location=***LOCATION***&kwsJobTitleOnly=true&view=List_Detail&networkView=national&radius=50&&sort=PostDate+desc%2C+Priority+desc%2C+score+desc&rows=50&page=***PAGE_NUMBER***&postDate=***NUMBER_DAYS***";
    protected $strBaseURLPathSection = "/jobs/search/results?kwsJobTitleOnly=true&view=List_Detail&networkView=national&radius=50&&sort=PostDate+desc%2C+Priority+desc%2C+score+desc&rows=50&page=***PAGE_NUMBER***&postDate=***NUMBER_DAYS***";
    protected $additionalLoadDelaySeconds = 10;




    function __construct($strOutputDirectory = null)
    {
        $this->siteBaseURL = $this->childSiteURLBase;
        $this->typeLocationSearchNeeded = 'location-city-comma-state-country';
        $this->strBaseURLFormat = $this->childSiteURLBase . $this->strBaseURLPathSection . $this->strBaseURLPathSuffix;
        parent::__construct($strOutputDirectory);
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS | C__JOB_USE_SELENIUM | C__JOB_PREFER_MICRODATA;
    }

    protected function _getURLfromBase_($searchDetails, $nPage = null, $nItem = null)
    {
        return parent::_getURLfromBase_($searchDetails, $nPage, $nItem);
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
                $item['date_pulled'] = \Scooper\getTodayAsString();


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



            $nodesJobRows= $objSimpHTML->find('table[id="aiResultsBrief"] tr[class="aiResultsRow   "]');
            if(isset($nodesJobRows))
            {
                foreach($nodesJobRows as $node)
                {
                    //
                    // get a new record with all columns set to null
                    //
                    $item = $this->getEmptyJobListingRecord();


                    $item['job_site'] = $this->siteName;
                    $item['date_pulled'] = \Scooper\getTodayAsString();


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