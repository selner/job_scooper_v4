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
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');

class PluginInternetBrands extends BaseTaleoPlugin
{
    protected $siteName = 'InternetBrands';
    protected $siteBaseURL = 'http://www.internetbrands.com/work-with-us/';
    protected $strBaseURLFormat = "http://ch.tbe.taleo.net/CH05/ats/careers/searchResults.jsp?org=CARSDIRECT&cws=2***ITEM_NUMBER***";
    protected $nJobListingsPerPage = 100;

    function parseTotalResultsCount($objSimpHTML)
    {
        return $this->parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, "class", "avada-row", 1);
    }
}

class PluginTraderJoes extends BaseTaleoPlugin
{
    protected $siteName = 'TraderJoes';
    protected $siteBaseURL = 'http://www.traderjoes.com/careers/index.asp';
    protected $strBaseURLFormat = "http://ch.tbe.taleo.net/CH14/ats/careers/searchResults.jsp?org=TRADERJOES&cws=1***ITEM_NUMBER***";
    protected $nJobListingsPerPage = 50;

    function parseTotalResultsCount($objSimpHTML)
    {
        return $this->parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, "id", "taleoContent", 1);
    }
}



class PluginViacom extends BaseTaleoPlugin
{
    protected $siteName = 'Viacom';
    protected $siteBaseURL = 'http://tbe.taleo.net/CH05/ats/careers/jobSearch.jsp?org=MTVNETWORKS&cws=1';
    protected $strBaseURLFormat = "http://ch.tbe.taleo.net/CH05/ats/careers/searchResults.jsp?org=MTVNETWORKS&cws=1***ITEM_NUMBER***";
    protected $nJobListingsPerPage = 100;

    function parseTotalResultsCount($objSimpHTML)
    {
        return $this->parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, "id", "content", 2);
    }

}



class PluginPorch extends BaseTaleoPlugin
{
    protected $siteName = 'Porch';
    protected $siteBaseURL = 'http://about.porch.com/careers';
    protected $strBaseURLFormat = "http://ch.tbe.taleo.net/CH10/ats/careers/searchResults.jsp?org=PORCH&cws=1***ITEM_NUMBER***";
    protected $nJobListingsPerPage = 100;

    function parseTotalResultsCount($objSimpHTML)
    {
        return $this->parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, "id", "summary", 1);
    }

}



class PluginTableau extends BaseTaleoPlugin
{
    protected $siteName = 'Tableau';
    protected $strBaseURLFormat = 'https://ch.tbe.taleo.net/CH11/ats/careers/searchResults.jsp?org=TABLEAU&cws=1***ITEM_NUMBER***';
    protected $nJobListingsPerPage = 100;

    function parseTotalResultsCount($objSimpHTML)
    {
        return $this->parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, "id", "taleoContent", 3);
    }

}




abstract class BaseTaleoPlugin extends ClassJobsSitePlugin
{
    protected $siteName = null;
    protected $strBaseURLFormat = null;
    protected $flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_RETURN_ALL_JOBS_NO_LOCATION;

    function getItemURLValue($nItem)
    {
        $strFirst = "&act=first&rowFrom=" . $this->nJobListingsPerPage;
        $strNext = "&act=next&rowFrom=";

        if($nItem == null || $nItem == 1) { return $strFirst; }

        $ret = $nItem - $this->nJobListingsPerPage;
        if($ret < 0) return $strFirst;

        return $strNext . $ret;
    }


    function parseTotalResultsCount($objSimpHTML)
    {
        return -1;
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('table[id="cws-search-results"] tr');
        $counter = 0;

        foreach($nodesJobs as $node)
        {
            if($counter == 0)
            {
                $counter++;
                continue;
            } // skip the header row
            $counter++;

            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;
            $item['company'] = $this->siteName;
            $item['job_post_url'] = $node->find("td a")[0]->href;
            $item['job_title'] = $node->find("td a")[0]->plaintext;
            $item['job_id'] = explode("rid=", $item['job_post_url'])[1];
            if($item['job_title'] == '') continue;

            $item['job_site_category'] = $node->find("td")[1]->plaintext;
            $item['location'] = $node->find("td")[2]->plaintext;
            $item['date_pulled'] = \Scooper\getTodayAsString();

            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }


    function parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, $divTagType, $divTagValue, $trIndex)
    {
        $resultsSection= $objSimpHTML->find("div[". $divTagType . "='".$divTagValue."'] table tr");  // "1 - 10 of 10 Job Results"
        $trSecond = $resultsSection[$trIndex];
        $tdNode = $trSecond->find("td");
        $totalItemsText = $tdNode[0]->plaintext;
        $arrItemsFirstSplit = explode("found ", trim($totalItemsText));
        $arrItemsSecondSplit = explode(" matching", trim($arrItemsFirstSplit[1]));
        $strTotalItemsCount = str_replace(",", "", trim($arrItemsSecondSplit[0]));

        return $strTotalItemsCount;
    }

}

?>
