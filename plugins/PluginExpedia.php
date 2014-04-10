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

//.// http://expediajobs.findly.com/candidate/job_search/advanced/results?job_type=5517&state=2336&country=5492&sort=date


class PluginExpedia extends ClassJobsSitePlugin
{
    protected $siteName = 'Expedia';
    protected $siteBaseURL = 'http://expediajobs.findly.com';


    function getDaysURLValue($days) {
        $ret = 1;

        if($days != null)
        {
            switch($days)
            {
                case ($days>1 && $days<=7):
                    $ret = 7;
                    break;


                case $days<=1:
                default:
                    $ret = 1;
                    break;

            }
        }

        return $ret;

    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $resultsSection= $objSimpHTML->find("td[class='td-result']");

        $totalItemsText = $resultsSection[0]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = $arrItemItems[7];

        var_dump($strTotalItemsCount);

        return str_replace(",", "", $strTotalItemsCount);
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret=null;

        $nodesJobs = $objSimpHTML->find('div[class="jobScopeWrapper"]');

//        var_dump('found ' . count($nodesJobs) . ' nodes');

        foreach($nodesJobs as $node)
        {
            $item = parent::getEmptyItemsArray();

            $jobLink = $node->find("a[class='jobLink']")[1];
            $titleTextNode = $jobLink->firstChild();
            if($titleTextNode->hasChildNodes())
            {
                $item['job_title'] = $titleTextNode->firstChild()->plaintext;
            }
            else
            {
                $item['job_title'] = $titleTextNode->plaintext;
            }

            $item['job_post_url'] = $this->siteBaseURL . $jobLink->href;

            // <a href="/partner/jobListing.htm?pos=115&amp;ao=29933&amp;s=58&amp;guid=000001453fb833deb3e300823643def8&amp;src=GD_JOB_AD&amp;t=SR&amp;extid=1&amp;exst=OL&amp;ist=&amp;ast=OL&amp;vt=w&amp;cb=1396933407969&amp;jobListingId=1008408496" rel="nofollow" class="jobLink" data-ja-clk="1" data-gd-view="1" data-ev-a="B-S"><tt class="notranslate"><strong>Director, Product</strong> Management</tt></a>
            $fIDMatch = preg_match("/jobListingId=([0-9]+)/", $jobLink->href, $arrIDMatches);
            if($fIDMatch) { $item['job_id'] = str_replace("jobListingId=", "", $arrIDMatches[0]); }

            $item['date_pulled'] = $this->getTodayAsString();
            $item['job_site'] = $this->siteName . "(" . trim($node->find("span[class='displaySource']")[0]->plaintext) .")";
            $item['company']= trim($node->find("span[class='employerName']")[0]->plaintext);
            $item['location'] =trim( $node->find("span[class='location'] span span span")[0]->plaintext);

            $item['job_site_date'] =trim( $node->find("div[class='minor nowrap']")[0]->plaintext);
            if(strlen($item['job_site_date']) == 0)  { $item['job_site_date'] = "N/A (likely sponsored result)";}

            if($this->is_IncludeBrief() == true)
            {
                $item['brief'] =trim( $node->find("p[class='desc']")[0]->plaintext);
            }

            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}