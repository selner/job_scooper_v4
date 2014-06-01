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

class PluginPorch extends ClassJobsSitePlugin
{
    protected $siteName = 'Porch';
    protected $siteBaseURL = 'http://about.porch.com/careers';


    function getDaysURLValue($days)
    {
       return "";
    }


    function parseTotalResultsCount($objSimpHTML)
    {
        return C__JOB_PAGECOUNT_NOTAPPLICABLE__; // only one page ever
    }

     function parseJobsListForPage($objSimpleHTML)
    {
        $ret = null;


        $resultsSection= $objSimpleHTML->find('div[id="job-listings"]');
        $resultsSection= $resultsSection[0];

        $nodesJobs = $resultsSection->find('div[class="cell"]');


        foreach($nodesJobs as $node)
        {
            $item = parent::getEmptyItemsArray();
            $item['company'] = 'Porch';
            $item['job_site'] = $item['company'];

            $item['job_title'] = $node->find("h4")[0]->plaintext;
            if($item['job_title'] == '') continue;
            $item['job_id'] = $item['job_title'];

            $item['job_post_url'] = $this->siteBaseURL . $node->find("a")[0]->href;
            $item['location'] = $node->find("span[class='location']")[0]->plaintext;
            $item['date_pulled'] = getTodayAsString();

            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }
}

?>
