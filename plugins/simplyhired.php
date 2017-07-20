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



class PluginSimplyHired extends ClassBaseServerHTMLJobSitePlugin
{
    protected $siteBaseURL = 'http://www.simplyhired.com';
    protected $siteName = 'SimplyHired';
    protected $nJobListingsPerPage = 10;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
    protected $strKeywordDelimiter = "or";
    protected $strBaseURLFormat = "http://www.simplyhired.com/search?q=***KEYWORDS***&l=***LOCATION***&fdb=***NUMBER_DAYS***&&ws=25&mi=50&sb=dd&pn=***PAGE_NUMBER***";
    protected $paginationType = C__PAGINATION_PAGE_VIA_URL;
    
    function getItemURLValue($nItem)
    {
        if($nItem == null || $nItem == 1) { return 0; }

        return $nItem;
    }


    /**
     * If the site does not have a URL parameter for number of days
     * then set the plugin flag to C__JOB_DAYS_VALUE_NOTAPPLICABLE__
     * in the PluginOptions.php file and just comment out this function.
     *
     * getDaysURLValue returns the value that is used to replace
     * the ***DAYS*** token in the search URL for the number of
     * days requested.
     *
     * @param $days
     * @return int|string
     */
    function getDaysURLValue($days = null)
    {
        $ret = "";

        if($days != null)
        {
            switch($days)
            {
                case ($days==1):
                    $ret = "1";
                    break;

                case ($days>1 && $days<8):
                    $ret = "7";
                    break;

                case ($days>14 && $days < 30):
                    $ret = "14";
                    break;

                case ($days>=30):
                    $ret = "30";
                    break;


                default:
                    $ret = "";
                    break;

            }
        }

        return $ret;
    }

    protected function getPageURLfromBaseFmt(&$searchDetails, $nPage = null, $nItem = null)
    {
        $searchDetailsBackup = \Scooper\array_copy($searchDetails);
        
        $strURL = $this->_getBaseURLFormat_($searchDetails);

        $strURL = str_ireplace("***NUMBER_DAYS***", $this->getDaysURLValue($GLOBALS['OPTS']['number_days']), $strURL );
        $strURL = str_ireplace("***PAGE_NUMBER***", $this->getPageURLValue($nPage), $strURL );
        $strURL = str_ireplace("***ITEM_NUMBER***", $this->getItemURLValue($nItem), $strURL );
        if(!$this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED))
        {
            assert($searchDetails['keywords_string_for_url'] != VALUE_NOT_SUPPORTED);
            $strURL = str_ireplace(BASE_URL_TAG_KEYWORDS, $searchDetails['keywords_string_for_url'], $strURL );
        }


        $strURL = str_ireplace("***ITEM_NUMBER***", $this->getItemURLValue($nItem), $strURL );

        $nSubtermMatches = substr_count($strURL, BASE_URL_TAG_LOCATION);

        if(!$this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED) && $nSubtermMatches > 0)
        {
            if($searchDetails['location_search_value'] == VALUE_NOT_SUPPORTED)
            {
                $msg = "Failed to run search:  search is missing the required location type of " . $this->getLocationSettingType() ." set.  Skipping search '". $searchDetails['key'] .".";
                if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine($msg, \Scooper\C__DISPLAY_ERROR__);
                throw new IndexOutOfBoundsException($msg);
            }
            else
            {
                $strURL = str_ireplace(BASE_URL_TAG_LOCATION, $searchDetails['location_search_value'], $strURL);
            }
        }

        if($strURL == null) {
            throw new ErrorException("Location value is required for " . $this->siteName . ", but was not set for the search '" . $searchDetails['key'] ."'.". " Aborting all searches for ". $this->siteName, \Scooper\C__DISPLAY_ERROR__);
        }

        $searchDetails = \Scooper\array_copy($searchDetailsBackup);

        return $strURL;
    }


    function parseTotalResultsCount($objSimpHTML)
    {
        $node = $objSimpHTML->find("div[class='result-headline'] div[class='hidden-sm-down'] div");
        if($node && isset($node) && is_array($node))
        {
            $arrParts = explode(" ", $node[0]->plaintext);
            return $arrParts[3];
        }

        return null;
    }


    function parseJobsListForPage($objSimpHTML)
    {

        $ret = null;
        $nodesJobs= $objSimpHTML->find('div[class="js-job"]');

        foreach($nodesJobs as $node)
        {

            $item = $this->getEmptyJobListingRecord();

            $titlelink = $node->find('a[class="card-link js-job-link"]');
            $item['job_title'] = combineTextAllChildren($titlelink[0]);;
            $item['job_post_url'] = $this->siteBaseURL . $titlelink[0]->href;

            if($item['job_title'] == '') continue;

            $datenode = $node->find('span[class="serp-timestamp"]');
            if(isset($datenode) && is_array($datenode))
            {
                $item['job_site_date'] = $datenode[0]->plaintext;
            }

            $companynode = $node->find('span[class="serp-company"]');
            if(isset($companynode ) && is_array($companynode ))
            {
                $item['company'] = combineTextAllChildren($companynode [0]);
            }

            $locnode = $node->find('span[class="serp-location"] span span[class="serp-location"]');
            if(isset($locnode) && is_array($locnode))
            {
                $item['location'] = combineTextAllChildren($locnode[0]);
            }

            $item['job_id'] = $this->getIDFromLink('/\/a\/job-details\/\?a=([^\/]+)/i', $item['job_post_url']);

            $ret[] = $this->normalizeJobItem($item);
        }

        return $ret;
    }








}


?>
