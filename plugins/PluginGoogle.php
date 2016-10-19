<?php

/**
 * Copyright 2014-15 Bryan Selner
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


class PluginGoogle extends ClassJobsSitePlugin
{

    // BUGBUG: currently uses microdata only which is missing location, date posted.  Need to switch to HTML parsed later to pick those facts up as well

    // BUGBUG: currently does not handle pagination of job listings


    protected $siteName = 'Google';
    protected $siteBaseURL = 'https://www.google.com';
    protected $strBaseURLFormat = 'https://www.google.com/about/careers/jobs#t=sq&q=j&li=20&l=false&jlo=en-US&j=***KEYWORDS***';
    protected $classToCheckExists = null;
    protected $regex_link_job_id = '/.*?([0-9]+)[\?\/&]*$/i';


    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem <= 10 ) { return "li=0"; }
        return "li=".$nItem."&st=".($nItem+10);
    }

    function __construct($strBaseDir = null)
    {
        parent::__construct($strBaseDir);
        $this->flagSettings = C__JOB_BASETYPE_WEBPAGE_FLAGS | C__JOB_USE_SELENIUM | C__JOB_PREFER_MICRODATA;
    }

    /**
     * parseTotalResultsCount
     *
     * If the site does not show the total number of results
     * then set the plugin flag to C__JOB_PAGECOUNT_NOTAPPLICABLE__
     * in the SitePlugins.php file and just comment out this function.
     *
     * parseTotalResultsCount returns the total number of listings that
     * the search returned by parsing the value from the returned HTML
     * *
     * @param $objSimpHTML
     * @return string|null
     */
    function parseTotalResultsCount($objSimpHTML)
    {
        $fResultsMsgShown = false;

        //
        // Find the HTML node that holds the result count
        //
        $noresultsDiv = $objSimpHTML->find("div[class='page-title']");
        if($noresultsDiv != null && is_array($noresultsDiv) && isset($noresultsDiv[0]))
        {
            $fResultsMsgShown = isset($noresultsDiv[0]->attr['style']) && (strcasecmp(strtolower($noresultsDiv[0]->attr['style']), "display: none;")  == 0 || strcasecmp(strtolower($noresultsDiv[0]->attr['aria-hidden']), "true") == 0 );
            $fResultsMsgShown = $fResultsMsgShown || (strcasecmp(strtolower($noresultsDiv[0]->plaintext), "no results found") == 0);

            if($fResultsMsgShown)
            {
                // found text telling us no results were found
                return 0;
            }
        }
        //
        // Find the HTML node that holds the result count
        //
        $fResultsMsgShown = false;
        $noresultsDiv = $objSimpHTML->find("div[class='no-content-txt headline secondary-text']");
        if($noresultsDiv && is_array($noresultsDiv) && isset($noresultsDiv[0]))
        {
            $fResultsMsgShown = (isset($noresultsDiv[0]->attr['style']) && (strcasecmp(strtolower($noresultsDiv[0]->attr['style']), "display: none;")  == 0) || (isset($noresultsDiv[0]->attr['aria-hidden']) && strcasecmp(strtolower($noresultsDiv[0]->attr['aria-hidden']), "true") == 0 ));
            $fResultsMsgShown = $fResultsMsgShown && (strcasecmp(strtolower($noresultsDiv[0]->plaintext), "no matching jobs") == 0);
            $fResultsMsgShown = $fResultsMsgShown == true || (isset($noresultsDiv[0]->parent()->attr['style']) && !(strcasecmp(strtolower($noresultsDiv[0]->parent()->attr['style']), "display: none;") == 0)) || (!isset($noresultsDiv[0]->parent()->attr['aria-hidden']) && strcasecmp(strtolower($noresultsDiv[0]->parent()->attr['aria-hidden']), "true") == 0 );

            if($fResultsMsgShown)
            {
                // found text telling us no results were found
                return 0;
            }

        }



        return C__TOTAL_ITEMS_UNKNOWN__;

    }



    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;

        $nodesJobs= $objSimpHTML->find('div[class="sr-title-container subhead2"]');

        if(!$nodesJobs) return null;

        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();
            $item['job_post_url'] = $this->siteBaseURL . $node->children(0)->attr['href'];
            $item['job_title'] = $node->children(0)->attr['title'];;
            $item['job_site'] = $this->siteName;
            $item['company'] = $this->siteName;
            if($item['job_title'] == '') continue;

            $summaryNode = $node->next_sibling();
            $item['location'] = $summaryNode->children(2)->attr['title'];

            $descrNode = $summaryNode->next_sibling();
            $item['job_site_date'] = $descrNode->children(0)->plaintext;
            $item['date_pulled'] = \Scooper\getTodayAsString();

            $ret[] = $this->normalizeItem($item);
        }

        return $ret;
    }

}

?>
