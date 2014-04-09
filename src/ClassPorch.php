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
require_once dirname(__FILE__) . '/../include/ClassJobsSiteGeneric.php';

class ClassPorchJobs extends ClassJobsSiteGeneric
{
    protected $siteName = 'Porch';
    protected $siteBaseURL = 'http://www.porch.com';


    function getDaysURLValue($days)
    {
       return "";
    }


    function parseTotalResultsCount($objSimpHTML)
    {
        return 1; // only one page ever
    }

     function parseJobsListForPage($objSimpleHTML)
    {
        $ret = null;


        $resultsSection= $objSimpleHTML->find('div[id="job-listings"]');
        $resultsSection= $resultsSection[0];

        $nodesJobs = $resultsSection->find('div[class="cell"]');

        var_dump('found ' . count($nodesJobs) . ' nodes');

        foreach($nodesJobs as $node)
        {
            $item = parent::getEmptyItemsArray();
            $item['company'] = 'Porch';
            $item['job_site'] = $item['company'];

            $item['job_title'] = $node->find("h4")[0]->plaintext;
            if($item['job_title'] == '') continue;
            $item['job_id'] = $item['job_title'];

            $item['job_post_url'] = 'http://about.porch.com/careers/' . $node->find("a")[0]->href;
            $item['location'] =trim( $node->find("span[class='location']")[0]->plaintext);
            $item['date_pulled'] = $this->getTodayAsString();

            $ret[] = $item;

        }

        return $ret;
    }
}