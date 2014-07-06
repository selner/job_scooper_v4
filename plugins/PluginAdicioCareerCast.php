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


class PluginMilwaukeeWAJournalSentinal extends PluginAdicioCareerCastBase
{
    protected $siteName = 'MilwaukeeJournalSentinal';
    protected $siteBaseURL = 'http://jobs.jsonline.com';
}
class PluginLocalworkCA extends PluginAdicioCareerCastBase
{
    protected $siteName = 'LocalworkCA';
    protected $siteBaseURL = 'http://jobs.localwork.ca';
}

class PluginEverettWAHerald extends PluginAdicioCareerCastBase
{
    protected $siteName = 'EverettWAHerald';
    protected $siteBaseURL = 'http://heraldnet.careers.adicio.com';
}

class PluginPolitico extends PluginAdicioCareerCastBase
{
    protected $siteName = 'Politico';
    protected $siteBaseURL = 'http://jobs.powerjobs.com';
}
class PluginAsheboroNCCourierTribune extends PluginAdicioCareerCastBase
{
    protected $siteName = 'AsheboroCourierTribune';
    protected $siteBaseURL = 'http://asheboro.jobs.careercast.com';
}
class PluginIEEE extends PluginAdicioCareerCastBase
{
    protected $siteName = 'IEEE';
    protected $siteBaseURL = 'http://jobs.ieee.org';
}
class PluginVariety extends PluginAdicioCareerCastBase
{
    protected $siteName = 'Variety';
    protected $siteBaseURL = 'http://jobs.variety.com';
}
class PluginLasVegasReviewJournal extends PluginAdicioCareerCastBase
{
    protected $siteName = 'LVReviewJournal';
    protected $siteBaseURL = 'http://careers.reviewjournal.com';
}
class PluginCellCom extends PluginAdicioCareerCastBase
{
    protected $siteName = 'CellCom';
    protected $siteBaseURL = 'http://careers.cell.com';
}
class PluginCareerJet extends PluginAdicioCareerCastBase
{
    protected $siteName = 'CareerJet';
    protected $siteBaseURL = 'http://www.careerjet.co.uk';
}
class PluginVirginiaPilotOnline extends PluginAdicioCareerCastBase
{
    protected $siteName = 'VirginiaPilotOnline';
    protected $siteBaseURL = 'http://careers.hamptonroads.com';
}
class PluginHamptonRoads extends PluginAdicioCareerCastBase
{
    protected $siteName = 'HamptonRoads';
    protected $siteBaseURL = 'http://careers.hamptonroads.com';
}
class PluginAnalyticTalent extends PluginAdicioCareerCastBase
{
    protected $siteName = 'AnalyticTalent';
    protected $siteBaseURL = 'http://careers.analytictalent.com';
}
class PluginKenoshaNews extends PluginAdicioCareerCastBase
{
    protected $siteName = 'KenoshaNews';
    protected $siteBaseURL = 'http://jobs.kenoshanews.com';
}

class PluginAdWeek extends PluginAdicioCareerCastBase
{
    protected $siteName = 'AdWeek';
    protected $siteBaseURL = 'http://jobs.adweek.com';
}

class PluginRetailCareersNow extends PluginAdicioCareerCastBase
{
    protected $siteName = 'RetailCareersNow';
    protected $siteBaseURL = 'http://retail.careers.adicio.com';
}
class PluginArkansasOnline extends PluginAdicioCareerCastBase
{
    protected $siteName = 'ArkansasOnline';
    protected $siteBaseURL = 'http://jobs.arkansasonline.com';
}
class PluginHealthJobs extends PluginAdicioCareerCastBase
{
    protected $siteName = 'HealthProfressionalsJobsPlus';
    protected $siteBaseURL = 'http://healthjobs.careers.adicio.com';
}
class PluginPharmacyJobCenter extends PluginAdicioCareerCastBase
{
    protected $siteName = 'PharmacyJobCenter';
    protected $siteBaseURL = 'http://pharmacy.careers.adicio.com';
}
class PluginKCBD extends PluginAdicioCareerCastBase
{
    protected $siteName = 'KCBD';
    protected $siteBaseURL = 'http://kcbd.careers.adicio.com';
}
class PluginAfro extends PluginAdicioCareerCastBase
{
    protected $siteName = 'Afro';
    protected $siteBaseURL = 'http://afro.careers.adicio.com';
}
class PluginJamaCareerCenter extends PluginAdicioCareerCastBase
{
    protected $siteName = 'JamaCareerCenter';
    protected $siteBaseURL = 'http://jama.careers.adicio.com';
}

class PluginSeacoastOnline extends PluginAdicioCareerCastBase
{
    protected $siteName = 'SeacoastOnline';
    protected $siteBaseURL = 'http://seacoast.careers.adicio.com';
}

class PluginTulsaWorld extends PluginAdicioCareerCastBase
{
    protected $siteName = 'TulsaWorld';
    protected $siteBaseURL = 'http://tulsa.careers.adicio.com';

}

class PluginSeattleTimes extends PluginAdicioCareerCastBase
{
    protected $siteName = 'SeattleTimes';
    protected $siteBaseURL = 'http://nwjobs.seattletimes.com';
}

class PluginAlbuquerqueJournal extends PluginAdicioCareerCastBase
{
    protected $siteName = 'AlbuquerqueJournal';
    protected $siteBaseURL = 'http://abqcareers.careers.adicio.com';
}


class PluginAdicioCareerCast extends PluginAdicioCareerCastBase
{
    protected $siteName = 'CareerCast';
    protected $siteBaseURL = 'http://www.careercast.com';
}

abstract class PluginAdicioCareerCastBase extends ClassJobsSitePlugin
{
    protected $siteName = '';
    protected $siteBaseURL = '';
    protected $nJobListingsPerPage = 25;
    protected $strBaseURLFormatSuffix = "/jobs/results/keyword/***KEYWORDS***?location=***LOCATION***&kwsJobTitleOnly=true&view=List_Detail&workType%5B0%5D=employee&radius=15&sort=Priority%20desc,%20PostDate%20desc&page=***PAGE_NUMBER***&modifiedDate=***NUMBER_DAYS***";
    protected $strBaseURLFormat = null;

    protected function _getURLfromBase_($searchDetails, $nDays, $nPage = null, $nItem = null, $strKeywords=null)
    {
        $this->strBaseURLFormat = $this->siteBaseURL . $this->strBaseURLFormatSuffix;
        return parent::_getURLfromBase_($searchDetails, $nDays, $nPage, $nItem, $strKeywords);
    }

    // if this is a client-side HTML download plugin, you will need to add a script
    // for driving Safari to download the files and set that script name here.
    //
    // This value is unused for XML or server-side webpage download plugins.
    protected $strFilePath_HTMLFileDownloadScript = "PluginCareerCast_downloadjobs.applescript";

    function getPageURLValue($page)
    {
        if($page == 1) { return 0; } else {return $oage; }
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
                $item['job_title'] = $this->siteBaseURL . $titleLink->plaintext;

                // If we couldn't parse the job title, it's not really a job
                // listing so just continue to the next one
                //
                if($item['job_title'] == '') continue;


                $item['job_post_url'] = $titleLink->href;
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