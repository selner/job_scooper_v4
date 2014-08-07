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
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/include/ClassJobsSitePluginCommon.php');


class PluginMashable extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Mashable';
    protected $childSiteURLBase = 'http://jobs.mashable.com';
    protected $strBaseURLSuffix = "&SearchNetworks=US&networkView=national";


}
class PluginMilwaukeeWAJournalSentinal extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'MilwaukeeJournalSentinal';
    protected $childSiteURLBase = 'http://jobs.jsonline.com';
}
class PluginLocalworkCA extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'LocalworkCA';
    protected $childSiteURLBase = 'http://jobs.localwork.ca';
}

class PluginEverettWAHerald extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'EverettWAHerald';
    protected $childSiteURLBase = 'http://heraldnet.careers.adicio.com';
}

class PluginPolitico extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'Politico';
    protected $childSiteURLBase = 'http://jobs.powerjobs.com';
}
class PluginAsheboroNCCourierTribune extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'AsheboroCourierTribune';
    protected $childSiteURLBase = 'http://jobs.r-jobs.com';
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
class PluginLasVegasReviewJournal extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'LVReviewJournal';
    protected $childSiteURLBase = 'http://careers.reviewjournal.com';
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
class PluginVirginiaPilotOnline extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'VirginiaPilotOnline';
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

class PluginAdWeek extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'AdWeek';
    protected $childSiteURLBase = 'http://jobs.adweek.com';
}

class PluginRetailCareersNow extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'RetailCareersNow';
    protected $childSiteURLBase = 'http://retail.careers.adicio.com';
}
class PluginArkansasOnline extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'ArkansasOnline';
    protected $childSiteURLBase = 'http://jobs.arkansasonline.com';
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

class PluginTulsaWorld extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'TulsaWorld';
    protected $childSiteURLBase = 'http://tulsa.careers.adicio.com';

}

class PluginSeattleTimes extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'SeattleTimes';
    protected $childSiteURLBase = 'http://nwjobs.seattletimes.com';
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

class PluginBartlesvilleOKExaminerEnterprise extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'BartlesvilleOKExaminerEnterprise';
    protected $childSiteURLBase = 'http://jobs.bvillejobs.com';
}

class PluginKFVS12MO extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'KFVS12';
    protected $childSiteURLBase = 'http://kfvs.careers.adicio.com';
}

class PluginClevelandDotCom extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'ClevelandDotCom';
    protected $childSiteURLBase = 'http://jobs.cleveland.com';
}
class PluginBatonRougeAdvocate extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'BatonRougeAdvocate';
    protected $childSiteURLBase = 'http://advocate.careers.adicio.com';
}

class PluginNewOrleansAdvocate extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'NewOrleansAdvocate';
    protected $childSiteURLBase = 'http://advocate.careers.adicio.com';
}
class PluginAcandiaAdvocate extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'AcandiaAdvocate';
    protected $childSiteURLBase = 'http://advocate.careers.adicio.com';
}
class PluginVictoriaTXAdvocate extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'VictoriaTXAdvocate';
    protected $childSiteURLBase = 'http://jobshome.crossroadsfinder.com';
}
class PluginJobsArkansas extends BaseAdicioCareerCastPlugin
{
    protected $siteName = 'JobsArkansas';
    protected $childSiteURLBase = 'http://jobs.arkansasonline.com';
}



abstract class BaseAdicioCareerCastPlugin extends ClassJobsSitePlugin
{
    protected $siteName = '';
    protected $siteBaseURL = '';
    protected $childSiteURLBase = '';
    protected $nJobListingsPerPage = 50;
    protected $strBaseURLPathSuffix = "";
    protected $strBaseURLFormat = null;
    protected $flagSettings = C__JOB_BASETYPE_HTML_DOWNLOAD_FLAGS;

    // postDate param below could also be modifiedDate=***NUMBER_DAYS***.  Unclear which is more correct when...
    protected $strBaseURLPathSection = "/jobs/results/keyword/***KEYWORDS***?location=***LOCATION***&kwsJobTitleOnly=true&view=List_Detail&networkView=national&radius=15&&sort=PostDate+desc%2C+Priority+desc%2C+score+desc&rows=50&page=***PAGE_NUMBER***&postDate=***NUMBER_DAYS***";




    function __construct($strOutputDirectory = null)
    {
        $this->siteBaseURL = $this->childSiteURLBase;
        $this->typeLocationSearchNeeded = 'location-city-comma-state-country';
        $this->strBaseURLFormat = $this->childSiteURLBase . $this->strBaseURLPathSection . $this->strBaseURLPathSuffix;
        return parent::__construct($strOutputDirectory);
    }

    protected function _getURLfromBase_($searchDetails, $nDays, $nPage = null, $nItem = null)
    {
        return parent::_getURLfromBase_($searchDetails, $nDays, $nPage, $nItem);
    }

    // if this is a client-side HTML download plugin, you will need to add a script
    // for driving Safari to download the files and set that script name here.
    //
    // This value is unused for XML or server-side webpage download plugins.
    protected $strFilePath_HTMLFileDownloadScript = "PluginAdicioCareerCast_downloadjobs.applescript";

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
    function getDaysURLValue($days)
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
        \SimpleHtmlDom\dump_html_tree($objSimpHTML);
        // get the text value of that node
        $totalItemsText = $resultsSection[0]->plaintext;

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
        if($nodesJobRows != null && count($nodesJobRows) > 0 )
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

       return $ret;
    }

}