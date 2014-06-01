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
require_once dirname(__FILE__) . '/../include/ClassJobsSitePlugin.php';



class PluginGoogle extends ClassJobsSitePlugin
{
    protected $siteName = 'Google';
    protected $siteBaseURL = 'https://www.google.com/about/careers/search/';
    protected $nJobListingsPerPage = 10;



    function getDaysURLValue($nDays)
    {
        if($nDays > 1)
        {
            __debug__printLine($this->siteName ." jobs can only be pulled for, at most, 1 day.  Ignoring number of days value and just pulling current listings.", C__DISPLAY_WARNING__);

        }
        return 1;

    }

    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem <= 10 ) { return "li=0"; }
        return "li=".$nItem."&st=".($nItem+10);
    }

    function getMyJobsForSearch($search, $nDays = -1)
    {
        return $this->getMyJobsFromHTMLFiles($this->siteName);
    }

    function parseTotalResultsCount($objSimpHTML)
    {
/*        $resultsSection= $objSimpHTML->find("iframe div[class='kd-count']");
        $resultsSection = $resultsSection->find("span");
        $totalItemsText = $resultsSection[1]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = trim($arrItemItems[3]);
        $strTotalItemsCount = str_replace(",", "", $strTotalItemsCount);

        return (intceil($strTotalItemsCount) * $nJobListingsPerPage);
*/
    // not necessary for an HTML file loaded plugin
        return -1;

    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('div[class="sr-content"]');
        $counter = 0;

        foreach($nodesJobs as $node)
        {
            $item = parent::getEmptyItemsArray();
            $item['job_site'] = $this->siteName;
            $item['company'] = $this->siteName;
            $item['job_title'] = $node->find("a[class='title heading sr-title'] span")[0]->plaintext;
            if($item['job_title'] == '') continue;
            $item['job_post_url'] = $this->siteBaseURL . $node->find("a[class='title heading sr-title']")[0]->attr['href'];
            $item['job_id'] = explode("jid=", $item['job_post_url'])[1];
            $item['job_id'] = str_replace("&amp;", "", $item['job_id']);
            $item['job_post_url'] = str_replace("&amp;", "&", $item['job_post_url']);

            $item['job_site_category'] = $node->find("a[class='greytext']")[0]->plaintext;
            $item['location'] = 'Seattle or Kirkland';

            $item['date_pulled'] = getTodayAsString();

            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}

?>
