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




class PluginTableau extends ClassJobsSitePlugin
{
    protected $siteName = 'Tableau';
    protected $strBaseURLFormat = 'https://ch.tbe.taleo.net/CH11/ats/careers/searchResults.jsp?org=TABLEAU&cws=1&act=next&rowFrom=***ITEM_NUMBER***';
    protected $nJobListingsPerPage = 100;
    protected $flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_RETURN_ALL_JOBS_NO_LOCATION;


    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 1) { return 0; }

        $ret = $nItem - 100;
        if($ret < 0) $ret = 0;
        return $ret;
    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $resultsSection= $objSimpHTML->find("div[id='taleoContent'] table tr td");  // "1 - 10 of 10 Job Results"
        $totalItemsText = $resultsSection[3]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = trim($arrItemItems[3]);
        $strTotalItemsCount = str_replace(",", "", $strTotalItemsCount);

        return $strTotalItemsCount;
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

}

?>
