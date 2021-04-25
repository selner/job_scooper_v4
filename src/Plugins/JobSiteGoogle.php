<?php
namespace Jobscooper\Plugins;

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


class JobSiteGoogle extends \Jobscooper\BasePlugin\ClientSideHTMLJobSitePlugin
{
    // BUGBUG: currently does not handle pagination of job listings


    protected $siteName = 'Google';
    protected $siteBaseURL = 'https://careers.google.com/jobs';
    protected $prevURL = 'https://careers.google.com/jobs';
    protected $additionalFlags = [C__JOB_ITEMCOUNT_NOTAPPLICABLE];
    protected $strBaseURLFormat = "https://careers.google.com/jobs#t=sq&q=j&so=dt_pd&li=20&l=false&jlo=en-US&";

    protected $additionalLoadDelaySeconds = 6;
    protected $nextPageScript = "var elem = document.getElementById('gjsrpn');  if (elem != null) { console.log('attempting next button click on element ID gjsrpn'); elem.click(); };";

    protected $arrListingTagSetup = array(
        'tag_next_button' => array('selector' => 'button[aria-label=\'Next page\']')
    );
    
//    function getItemURLValue($nItem)
//    {
//        if($nItem == null || $nItem <= 10 ) { return "li=0"; }
//        return "li=".$nItem."&st=".($nItem+10);
//    }

    function __construct($strBaseDir = null)
    {
        $this->regex_link_job_id = '/' . REXPR_PARTIAL_MATCH_URL_DOMAIN . '/.*?jid=([^&]*)/i';
        parent::__construct($strBaseDir);
    }



    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;

        $nodesJobs= $objSimpHTML->find(".GXRRIBB-vb-g");

        if(!$nodesJobs) return null;

        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();

            $item['job_id'] = $node->attr['id'];

            $subNode = $node->find("h2 a");
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
