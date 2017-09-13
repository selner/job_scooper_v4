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

class PluginMonster extends ClassBaseServerHTMLJobSitePlugin
{
    protected $siteName = 'Monster';
    protected $siteBaseURL = 'http://www.monster.com';
    protected $strBaseURLFormat = "https://www.monster.com/jobs/search/?q=***KEYWORDS***&sort=dt.rv.di&where=***LOCATION***&tm=***NUMBER_DAYS***&pg=***PAGE_NUMBER***";
    protected $nJobListingsPerPage = 25;
    protected $additionalFlags = [C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES];
    protected $paginationType = C__PAGINATION_PAGE_VIA_URL;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode-underscores-and-dashes';
    protected $regex_link_job_id = '/\.com\/([^\/]+\/)?([^\.]+)/i';
    protected $strKeywordDelimiter = ",";

    function getDaysURLValue($days = null) {
        $ret = "1";

        if($days != null)
        {
            switch($days)
            {

                case ($days>=31):
                    $ret = "";
                    break;

                case ($days>=15 && $days<31):
                    $ret = "30";
                    break;

                case ($days>=7 && $days<15):
                    $ret = "14";
                    break;

                case ($days>=3 && $days<7):
                    $ret = "7";
                    break;

                case ($days>=1 && $days<3):
                    $ret = "3";
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

        $noResults = $objSimpHTML->find("div[class='jsresultsheader'] h1");
        if (!is_null($noResults) && is_array($noResults) && count($noResults) > 0)
        {
            if(strncasecmp('Sorry,', trim($noResults[0]->plaintext), 6) == 0)
            {
                $GLOBALS['logger']->logLine("Search returned no jobs found and matched expected 'No results' tag for " . $this->siteName, \Scooper\C__DISPLAY_ITEM_DETAIL__);
                return null;
            }
        }

        $resultsSection= $objSimpHTML->find("h2[class='page-title']");
        $totalItemsText = $resultsSection[0]->plaintext;
        $arrItemItems = explode(" ", trim($totalItemsText));
        $strTotalItemsCount = $arrItemItems[0];

        return str_replace(",", "", $strTotalItemsCount);
    }

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('article[class="js_result_row"]');


        foreach($nodesJobs as $node)
        {
            $item = getEmptyJobListingRecord();

            $subNode = $node->find("div[class='jobTitle'] h2 a");
            if(isset($subNode) && isset($subNode[0]))
            {
                $item['job_title'] = $subNode[0]->attr['title'];
                $item['job_id'] = $subNode[0]->attr['data-m_impr_j_postingid'];
                if(is_null($item['job_id']) || empty($item['job_id']))
                    $item['job_id'] = $subNode[0]->attr['data-m_impr_j_jobid'];
                $item['job_post_url'] = $subNode[0]->attr['href'];


                $mousedownval = html_entity_decode($subNode[0]->attr['onmousedown'], ENT_QUOTES | ENT_XML1, 'UTF-8');

                $parts = explode("clickJobTitleSiteCat('{", $mousedownval);
                $vars = array();
                if(count($parts) >= 2)
                {

                    foreach(explode(",", $parts[1]) as $v)
                    {
                        $keyval = explode(":", $v);
                        if(count($keyval) >= 2)
                        {
                            $key = str_replace("\"", "", $keyval[0]);
                            $val = str_replace("\"", "", $keyval[1]);
                            $vars[$key] = $val;
                        }
                    }

                    if(array_key_exists("eVar31", $vars) === true)
                        $item['location'] = str_replace("_", " ", $vars['eVar31']);

                    if(array_key_exists("prop24", $vars) === true)
                    {
                        $item['job_site_date'] = str_replace("_", "", $vars["prop24"]);
                        $dateVal = strtotime($item['job_site_date']);
                        if(!($dateVal === false))
                            $item['job_site_date'] = $dateVal->format('Y-m-d');
                    }
                }
            }

            if($item['job_title'] == '' || $item['job_id'] == '')
                continue;

            $subNode = $node->find("div[class='company'] a span");
            if(isset($subNode) && isset($subNode[0]))
                $item['company'] = $subNode[0]->plaintext;

            $subNode = $node->find("span[itemprop='address'] span[itemprop='addressLocality']");
            if(isset($subNode) && isset($subNode[0]))
            {
                $item['location'] = $subNode[0]->plaintext;
                $stateNode = $subNode[0]->nextSibling();
                $item['location'] = $item['location'] . ", " . $stateNode->plaintext;
            }

            $ret[] = $item;

        }

        return $ret;
    }

}
