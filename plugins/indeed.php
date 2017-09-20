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
require_once dirname(dirname(__FILE__))."/bootstrap.php";

class PluginIndeed extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'Indeed';
    protected $nJobListingsPerPage = 50;
    protected $siteBaseURL = 'http://www.Indeed.com';
    protected $strBaseURLFormat = "https://www.indeed.com/jobs?as_and=***KEYWORDS***&as_phr=&as_any=&as_not=&as_ttl=&as_cmp=&jt=all&st=&salary=&radius=50&l=***LOCATION***&fromage=1&limit=50&sort=date***ITEM_NUMBER***&filter=0&psf=advsrch";
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';

    // Note:  C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS intentioanlly not set although Indeed supports it.  However, their support is too explicit of a search a will weed out
    //        too many potential hits to be worth it.
    protected $additionalFlags = [C__JOB_IGNORE_MISMATCHED_JOB_COUNTS];
    protected $paginationType = C__PAGINATION_PAGE_VIA_NEXTBUTTON;




    protected $arrListingTagSetup = array(
        'tag_listings_count' =>  array('selector' => '#searchCount', 'return_value_regex' => '/.*?of\s*(\d+).*?/'),
        'tag_listings_noresults' =>  array('selector' => 'div.bad_query h2', 'return_attribute' => 'plaintext', 'return_value_callback' => "isNoJobResults"),
        'tag_next_button' => array('selector' => 'span.np')
    );


    static function isNoJobResults($var)
    {
        return noJobStringMatch($var, "did not match any jobs");
    }


    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 1) { return ""; }

        return "&start=" . $nItem;
    }

    public function parseJobsListForPage($objSimpleHTML)
    {
        $ret = null;
        $cntNode = $objSimpleHTML->find("div[id='searchCount']");
        if(isset($cntNode) && count($cntNode) >= 1)
        {
            $GLOBALS['logger']->logLine("Processing records: " . $cntNode[0]->plaintext);
        }

        $nodesJobs = $objSimpleHTML->find('td[id=\'resultsCol\'] div[data-tn-component=\'organicJob\']');
        foreach($nodesJobs as $node)
        {

            if(!array_key_exists('itemtype', $node->attr))
            {
                $GLOBALS['logger']->logLine("Skipping job node without itemtype attribute; likely a sponsored and therefore not an organic search result.", \C__DISPLAY_MOMENTARY_INTERUPPT__);
                continue;
            }
            assert($node->attr['itemtype'] == "http://schema.org/JobPosting");

            $item = getEmptyJobListingRecord();

            if(isset($node) && isset($node->attr['data-jk']))
                $item['job_id'] = $node->attr['data-jk'];

            $subNodes = $node->find("a[data-tn-element='jobTitle']");
            if(isset($subNodes) && array_key_exists('title', $subNodes[0]->attr)) {
                $item['job_title'] = $subNodes[0]->attr['title'];
                $item['job_post_url'] = $subNodes[0]->attr['href'];
            }

            $coNode = $node->find("span[itemprop='hiringOrganization']");
            if(isset($coNode) && count($coNode) >= 1)
            {
                $item['company'] = combineTextAllChildren($coNode[0]);
            }

            $locNode= $node->find("span[itemprop='addressLocality']");
            if(isset($locNode) && count($locNode) >= 1)
            {
                $item['location'] = $locNode[0]->plaintext;
            }
            $dateNode = $node->find("span[class='date']");
            if(isset($dateNode ) && count($dateNode ) >= 1)
            {
                $item['job_site_date'] = $dateNode[0]->plaintext;
                if(strcasecmp(trim($item['job_site_date']), "Just posted") == 0)
                    $item['job_site_date'] = getTodayAsString();
            }

            if($item['job_title'] == '') continue;
            $ret[] = $item;

        }

        return $ret;
    }

}


class PluginIndeedUK extends PluginIndeed
{
    protected $siteName = 'IndeedUK';
    protected $nJobListingsPerPage = 50;
    protected $siteBaseURL = 'http://www.Indeed.co.uk';
    protected $strBaseURLFormat = "https://www.indeed.co.uk/jobs?as_and=***KEYWORDS***&as_phr=&as_any=&as_not=&as_ttl=&as_cmp=&jt=all&st=&salary=&radius=50&l=***LOCATION***&fromage=1&limit=50&sort=date***ITEM_NUMBER***&filter=0&psf=advsrch";
    protected $typeLocationSearchNeeded = 'location-city';
    protected $countryCodes = array("UK");
}