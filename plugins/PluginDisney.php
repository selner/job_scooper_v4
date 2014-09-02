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




class PluginDisney extends ClassJobsSitePlugin
{
    protected $siteName = 'Disney';
    protected $siteBaseURL = 'http://disneycareers.com/';
    protected $flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS_URL_FORMAT_REQUIRED;




    function parseTotalResultsCount($objSimpHTML)
    {
        $resultsSection= $objSimpHTML->find("div[id='searchResultMessage'] h1");  // "Your Search returned 30  results"
        $totalItemsText = $resultsSection[0]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = trim($arrItemItems[3]);
        $strTotalItemsCount = str_replace(",", "", $strTotalItemsCount);

        $GLOBALS['logger']->logLine($this->siteName ." only pulling the last 10 jobs posted out of " . $strTotalItemsCount, \Scooper\C__DISPLAY_WARNING__);

        return "10";
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('table[id="searchResultsBlock"] tr');


        foreach($nodesJobs as $node)
        {
/*            if(strcasecmp($node->attr['class'], "gradeA even") != 0 &&
                strcasecmp($node->attr['class'], "gradeA odd") != 0)
            {
                continue;
            }
*/
            $item = $this->getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;
            $item['company'] = $this->siteName;

            $titleLink = $node->find("td[class='column1'] div a");
            if(isset($titleLink) && isset($titleLink[0]))
            {
                $item['job_title'] = $titleLink[0]->plaintext;
                $item['job_post_url']  = $titleLink[0]->href;
            }

          if($item['job_title'] == '') continue;

            $item['job_id'] = explode("jobid=", $item['job_post_url'])[1];

            $subNode = $node->find("td[class='column2'] div");
            if(isset($subNode) && isset($subNode[0])) $item['job_site_category'] = $subNode[0]->plaintext;

            $subNode = $node->find("td[class='column3'] div span[class='bold-text']");
            if(isset($subNode) && isset($subNode[0])) $item['location'] = $subNode[0]->plaintext;


            $item['date_pulled'] = \Scooper\getTodayAsString();

            $subNode = $node->find("td[class='column4']");
            if(isset($subNode) && isset($subNode[0])) $item['job_site_date'] = $subNode[0]->plaintext;

            $ret[] = $this->normalizeItem($item);
        }


        return $ret;
    }

}