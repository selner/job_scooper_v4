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
    protected $siteBaseURL = 'https://www.google.com/about/careers';
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


    function parseTotalResultsCount($objSimpHTML)
    {
        dump_html_tree($objSimpHTML);
        $resultsSection= $objSimpHTML->find("iframe div[class='kd-count']");
        var_dump($resultsSection);
        $resultsSection = $resultsSection->find("span");
        dump_html_tree($resultsSection);
        $totalItemsText = $resultsSection[1]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = trim($arrItemItems[3]);
        $strTotalItemsCount = str_replace(",", "", $strTotalItemsCount);

        return (intceil($strTotalItemsCount) * $nJobListingsPerPage);
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('div[class="sr sr-a"]');
        $counter = 0;

        foreach($nodesJobs as $node)
        {
            dump_html_tree($node);
            if($counter == 0)
            {
                $counter++;
                continue;
            } // skip the header row
            $counter++;

            $item = parent::getEmptyItemsArray();
            $item['job_site'] = $this->siteName;
            $item['company'] = $this->siteName;
            $item['job_id'] = $node->attr['id'];
            $item['job_title'] = $jobLink->find("span")[0]->plaintext;
            $jobLink = $node->find("a[class='title heading sr-title']");
            $item['job_post_url'] = $jobLink[0]->href;
            // if($item['job_title'] == '') continue;

            $item['job_site_category'] = $node->find("a[class='greytext']")[0]->plaintext;
            $item['location'] = $node->find("a[class='source']")[0]->plaintext;
            $item['date_pulled'] = $this->getTodayAsString();

            var_dump($item);
            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}

?>
