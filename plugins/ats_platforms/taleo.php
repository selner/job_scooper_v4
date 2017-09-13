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

class PluginEntercom extends BaseTaleoPlugin
{
    protected $use1ToTDForCount = True;
    protected $taleoOrgID = "ENTERCOM";
    protected $nJobListingsPerPage = 100;
    protected $server = 'http://chk.tbe.taleo.net/chk05/ats/careers/searchResults.jsp';

}


class PluginSeattleGenetics extends BaseTaleoPlugin
{
    protected $server = "http://chp.tbe.taleo.net/chp04/ats/careers/searchResults.jsp";
    protected $taleoOrgID = "SEAGEN";

    function parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, $divTagType, $divTagValue, $trIndex)
    {
        $nodeHelper = new CSimpleHTMLHelper($objSimpHTML);
        $node = $nodeHelper->get("div.inner table tbody tr[2] td b", 0, true);
        $totalItemsText = $node->innertext();

        return $totalItemsText;
    }

    protected $arrResultsCountTag = array('type' =>'class', 'value'=>'inner', 'index'=>0);
}

class PluginInternetBrands extends BaseTaleoPlugin
{
    protected $siteBaseURL = 'http://www.internetbrands.com/work-with-us/';
    protected $taleoOrgID = "CARSDIRECT";
    protected $arrResultsCountTag = array('type' =>'class', 'value'=>'avada-row', 'index'=>1);
}

class PluginTraderJoes extends BaseTaleoPlugin
{
    protected $use1ToTDForCount = True;
    protected $siteBaseURL = 'http://www.traderjoes.com/careers/index.asp';
    protected $taleoOrgID = "TRADERJOES";
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'taleoContent', 'index'=>1);
}
class PluginPorch extends BaseTaleoPlugin
{
    protected $use1ToTDForCount = True;
    protected $siteBaseURL = 'http://about.porch.com/careers';
    protected $taleoOrgID = "PORCH";
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'summary', 'index'=>1);
}


abstract class BaseTaleoPlugin extends ClassBaseServerHTMLJobSitePlugin
{
    protected $use1ToTDForCount = False;
    protected $taleoOrgID = null;
    protected $nJobListingsPerPage = 50;
    protected $arrResultsCountTag = array('type' =>null, 'value'=>null, 'index'=>null);
    protected $siteName = null;
    protected $server = null;

    function __construct($strOutputDirectory = null)
    {
        $this->paginationType = C__PAGINATION_PAGE_VIA_URL;
        
        if(!isset($this->server) or strlen($this->server) <= 0) {
            $this->server = "https://ch.tbe.taleo.net/CH11/ats/careers/searchResults.jsp";
        }

        if(isset($this->taleoOrgID) and strlen($this->taleoOrgID) > 0)
        {
            $this->strBaseURLFormat = $this->server.'?org=' . $this->taleoOrgID . '&cws=1***ITEM_NUMBER***';
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
        if($this->use1ToTDForCount)
            return $this->parseTotalResultsCountFrom1ToTD($objSimpHTML);
        else
            return $this->parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, $this->arrResultsCountTag['type'], $this->arrResultsCountTag['value'], $this->arrResultsCountTag['index']);
    }



    protected $strBaseURLFormat = null;

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

            $item = getEmptyJobListingRecord();
            $item['company'] = $this->siteName;
            $item['job_post_url'] = $node->find("td a")[0]->href;
            $item['job_title'] = $node->find("td a")[0]->plaintext;
            $item['job_id'] = explode("rid=", $item['job_post_url'])[1];
            if($item['job_title'] == '') continue;

            $tds = $node->find("td");
            if(isset($tds) && isset($tds[1])) $item['location'] = $node->find("td")[1]->plaintext;
            if(isset($tds) && isset($tds[2]))$item['job_site_category'] = $tds[2]->plaintext;

            $ret[] = $item;
        }

        return $ret;
    }

    function parseTotalResultsCountFrom1ToTD($objSimpHTML) {
        $nodes = $objSimpHTML->find('td[class="nowrapRegular"] b');
        if($nodes && count($nodes)>=2)
        {
            return $nodes[1]->plaintext;
        }

    }


    function parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, $divTagType, $divTagValue, $trIndex)
    {
        $nodeHelper = new CSimpleHTMLHelper($objSimpHTML);


        $node = $nodeHelper->get("div[". $divTagType . "='".$divTagValue."'] table tbody tr", $trIndex, true);

        $trSecond = new CSimpleHTMLHelper($node);
        $totalItemsText = $trSecond->getText("td", 0, true);
        $arrItemsFirstSplit = explode("found ", trim($totalItemsText));
        $strTotalItemsCount = explode(" matching", trim($arrItemsFirstSplit[1]));

        return $strTotalItemsCount;
    }

}
