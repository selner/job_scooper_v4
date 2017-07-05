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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');


class PluginGoogle extends ClassClientHTMLJobSitePlugin
{
    // BUGBUG: currently does not handle pagination of job listings


    protected $siteName = 'Google';
    protected $siteBaseURL = 'https://www.google.com/about/careers/jobs';
    protected $additionalFlags = [C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_ITEMCOUNT_NOTAPPLICABLE__, C__JOB_CLIENTSIDE_PAGE_VIA_NEXTBUTTON];
    // BUGBUG:  Hard coded to use Seattle, Sea-Tac and Mountain View locations for the time being
    protected $strBaseURLFormat = 'https://www.google.com/about/careers/jobs#t=sq&q=j&li=20&l=false&jlo=en-US&jcoid=7c8c6665-81cf-4e11-8fc9-ec1d6a69120c&jcoid=e43afd0d-d215-45db-a154-5386c9036525&jl=47.6062095%3A-122.3320708%3ASeattle%2C+WA%2C+USA%3AUS%3AUS%3A9.901219492788272%3ALOCALITY%3A%3A%3A%3A%3A%3A&jl=47.7881528%3A-122.3087405%3AMountlake+Terrace%2C+WA%2C+USA%3AUS%3AUS%3A1.8843888568290035%3ALOCALITY%3A%3A%3A%3A%3A%3A&jl=47.4435903%3A-122.2960726%3ASeaTac%2C+WA%2C+USA%3AUS%3AUS%3A3.5844312389483015%3ALOCALITY%3A%3A%3A%3A%3A%3A&jl=37.3860517%3A-122.0838511%3AMountain+View%2C+CA%2C+USA%3AUS%3AUnited+States%3A9.901223692706639%3ALOCALITY%3A%3A%3A%3ACA%3ASanta+Clara+County%3AMountain+View&jld=100&j=***KEYWORDS***';
    protected $additionalLoadDelaySeconds = 4;
    protected $nextPageScript = "var elem = document.getElementById('gjsrpn');  if (elem != null) { console.log('attempting next button click on element ID gjsrpn'); elem.click(); };";

    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem <= 10 ) { return "li=0"; }
        return "li=".$nItem."&st=".($nItem+10);
    }

    function __construct($strBaseDir = null)
    {
        $this->regex_link_job_id = '/' . REXPR_PARTIAL_MATCH_URL_DOMAIN . '/.*?jid=([^&]*)/i';
        parent::__construct($strBaseDir);
    }



    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;

        $nodesJobs= $objSimpHTML->find("div[role='listitem']");

        if(!$nodesJobs) return null;

        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();

            $item['job_id'] = $node->attr['id'];;

            $subNode = $node->find("div[class='sr-content-container'] h2 a");
            if(isset($subNode) && count($subNode) >= 1)
            {
                $item['job_post_url'] = $subNode[0]->attr['href'];
                $item['job_title'] = $subNode[0]->attr['title'];
            }

            if($item['job_id'] == '') continue;

            $subNode = $node->find("span[class='location secondary-text']");
            if(isset($subNode))
                $item['location'] = $subNode[0]->attr['title'];

            $subNode = $node->find("span[class='secondary-text']");
            if(isset($subNode))
                $item['company'] = $subNode[0]->plaintext;
            else
                $item['company'] = $this->siteName;

            $ret[] = $this->normalizeJobItem($item);

        }

        return $ret;
    }

}

?>
