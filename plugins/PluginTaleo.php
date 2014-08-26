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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');

class PluginEntercom extends BaseTaleoPlugin
{
    protected $taleoOrgID = "ENTERCOM";
    protected $nJobListingsPerPage = 100;
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'taleo_container', 'index'=>1);
}

class PluginTesla extends BaseTaleoPlugin
{
    protected $taleoOrgID = "TESLA";
    protected $nJobListingsPerPage = 100;
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'taleocontent', 'index'=>4);
}

class PluginPacMed extends BaseTaleoPlugin
{
    protected $taleoOrgID = "PACMED";
    protected $siteBaseURL = 'http://pacificmedicalcenters.org/index.php/work-with-us/';
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'taleoContent', 'index'=>1);
}

class PluginSeattleGenetics extends BaseTaleoPlugin
{
    protected $siteBaseURL = 'http://www.seattlegenetics.com/careers';
    protected $taleoOrgID = "SEAGEN";
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'taleocontent', 'index'=>1);
}

class PluginInternetBrands extends BaseTaleoPlugin
{
    protected $siteBaseURL = 'http://www.internetbrands.com/work-with-us/';
    protected $taleoOrgID = "CARSDIRECT";
    protected $arrResultsCountTag = array('type' =>'class', 'value'=>'avada-row', 'index'=>1);
}

class PluginTraderJoes extends BaseTaleoPlugin
{
    protected $siteBaseURL = 'http://www.traderjoes.com/careers/index.asp';
    protected $taleoOrgID = "TRADERJOES";
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'taleoContent', 'index'=>1);
}
class PluginViacom extends BaseTaleoPlugin
{
    protected $siteBaseURL = 'http://tbe.taleo.net/CH05/ats/careers/jobSearch.jsp?org=MTVNETWORKS&cws=1';
    protected $taleoOrgID = "MTVNETWORKS";
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'content', 'index'=>2);
}
class PluginPorch extends BaseTaleoPlugin
{
    protected $siteBaseURL = 'http://about.porch.com/careers';
    protected $taleoOrgID = "PORCH";
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'summary', 'index'=>1);
}
class PluginTableau extends BaseTaleoPlugin
{
    protected $taleoOrgID = "TABLEAU";
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'taleoContent', 'index'=>3);
}


abstract class BaseTaleoPlugin extends ClassJobsSitePlugin
{
    protected $taleoOrgID = null;
    protected $nJobListingsPerPage = 50;
    protected $arrResultsCountTag = array('type' =>null, 'value'=>null, 'index'=>null);
    protected $siteName = null;

    function __construct($strOutputDirectory = null)
    {
        if(isset($this->taleoOrgID) and strlen($this->taleoOrgID) > 0)
        {
            $this->strBaseURLFormat = 'https://ch.tbe.taleo.net/CH11/ats/careers/searchResults.jsp?org=' . $this->taleoOrgID . '&cws=1***ITEM_NUMBER***';
        }

        if(!isset($this->siteName) && strlen($this->siteName) <= 0)
        {
            $strPluginSite = get_class($this);
            $strPluginSite = str_replace("Plugin", "", $strPluginSite);
            $this->siteName = $strPluginSite;
        }

        return parent::__construct($strOutputDirectory);
    }

    function parseTotalResultsCount($objSimpHTML)
    {
        return $this->parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, $this->arrResultsCountTag['type'], $this->arrResultsCountTag['value'], $this->arrResultsCountTag['index']);
    }



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
        $resultsSection= $objSimpHTML->find("div[". $divTagType . "='".$divTagValue."'] table tbody tr");  // "1 - 10 of 10 Job Results"
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
