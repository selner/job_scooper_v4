<?php

    /**
     * Copyright 2014-16 Bryan Selner
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




class PluginLinkUp extends ClassBaseServerHTMLJobSitePlugin
{
    protected $siteName = 'LinkUp';
    protected $nJobListingsPerPage = 50;
    protected $siteBaseURL = 'http://www.linkup.com';
    protected $strBaseURLFormat = "http://www.linkup.com/results.php?ttl=***KEYWORDS***&l=***LOCATION***&sort=d&tm=***NUMBER_DAYS***&page=***PAGE_NUMBER***&p=50";
    protected $additionalFlags = C__JOB_KEYWORD_MULTIPLE_TERMS_SUPPORTED;
    protected $typeLocationSearchNeeded = 'location-city-comma-state';
    protected $strKeywordDelimiter = "or";

    function getDaysURLValue($days = null) {
        $ret = "1d";

        if($days != null)
        {
            switch($days)
            {
                case ($days>3 && $days<=7):
                    $ret = "7d";
                    break;

                case ($days>=3 && $days<7):
                    $ret = "3d";
                    break;


                case $days<=1:
                default:
                    $ret = "1d";
                    break;

            }
        }

        return $ret;

    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $nodeHelper = new CSimpleHTMLHelper($objSimpHTML);

        $pageText = $nodeHelper->getText("div[id='search-showing']", 0, false);
        // # of items to parse
        $arrItemItems = explode(" ", trim($pageText));
        if(isset($arrItemItems) && count($arrItemItems) >= 5)
            return $arrItemItems[4];
        else
            return null;
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('div[class="listing js-listing"]');


        foreach($nodesJobs as $node)
        {
            $item = $this->getEmptyJobListingRecord();
            $nodeHelper = new CSimpleHTMLHelper($node);

            $item['job_title'] = $nodeHelper->getAllChildrenText("a[class='listing-title']", 0, false);
            if($item['job_title'] == '') continue;

            $item['job_site'] = $this->siteName;
            $item['job_post_url'] = $nodeHelper->getProperty("a[class='listing-title']", 0, "href", false );
            $item['company'] = $nodeHelper->getText("span[class='listing-company']", 0, false );


            $item['job_id'] = $nodeHelper->getAttribute(null, null, "data-hash", false );
            $item['location'] = $nodeHelper->getText("span[class='listing-location'] span", 0, false ) . "-" . $nodeHelper->getText("span[class='listing-location'] span", 1, false );

            $item['date_pulled'] = getTodayAsString();

            $item['job_site_category'] = $nodeHelper->getText("span[class='listing-tag']", 0, false );
            $dateText = $nodeHelper->getText("span[class='listing-date']", 0, false );
            $item['job_site_date'] = getDateForDaysAgo($dateText);

            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}