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


class PluginMonster extends \JobScooper\Plugins\lib\ServerHtmlSimplePlugin
{
    protected $JobSiteName = 'Monster';
    protected $JobPostingBaseUrl = 'http://www.monster.com';
    protected $SearchUrlFormat = "https://www.monster.com/jobs/search/?q=***KEYWORDS***&sort=dt.rv.di&where=***LOCATION***&tm=***NUMBER_DAYS***&pg=***PAGE_NUMBER***";
    protected $JobListingsPerPage = 25;
    protected $additionalBitFlags = [C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES, C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER];
    protected $PaginationType = C__PAGINATION_PAGE_VIA_URL;
    protected $LocationType = 'location-city-comma-statecode-underscores-and-dashes';
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
            if(strncasecmp('Sorry,', trim($noResults[0]->text()), 6) == 0)
            {
                $GLOBALS['logger']->logLine("Search returned no jobs found and matched expected 'No results' tag for " . $this->JobSiteName, \C__DISPLAY_ITEM_DETAIL__);
                return null;
            }
        }

        $resultsSection= $objSimpHTML->find("h2[class='page-title']");
        $totalItemsText = $resultsSection[0]->text();
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
                $item['Title'] = $subNode[0]->attr['Title'];
                $item['JobSitePostId'] = $subNode[0]->attr['data-m_impr_j_postingid'];
                if(is_null($item['JobSitePostId']) || empty($item['JobSitePostId']))
                    $item['JobSitePostId'] = $subNode[0]->attr['data-m_impr_j_jobid'];
                $item['Url'] = $subNode[0]->attr['href'];


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
                        $item['Location'] = str_replace("_", " ", $vars['eVar31']);

                    if(array_key_exists("prop24", $vars) === true)
                    {
                        $item['PostedAt'] = str_replace("_", "", $vars["prop24"]);
                        $dateVal = strtotime($item['PostedAt']);
                        if(!($dateVal === false))
                            $item['PostedAt'] = $dateVal->format('Y-m-d');
                    }
                }
            }

            if($item['Title'] == '' || $item['JobSitePostId'] == '')
                continue;

            $subNode = $node->find("div[class='Company'] a span");
            if(isset($subNode) && isset($subNode[0]))
                $item['Company'] = $subNode[0]->text();

            $subNode = $node->find("span[itemprop='address'] span[itemprop='addressLocality']");
            if(isset($subNode) && isset($subNode[0]))
            {
                $item['Location'] = $subNode[0]->text();
                $stateNode = $subNode[0]->nextSibling();
                $item['Location'] = $item['Location'] . ", " . $stateNode->text();
            }

            $ret[] = $item;

        }

        return $ret;
    }

}
