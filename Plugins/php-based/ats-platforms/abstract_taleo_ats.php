<?php

/**
 * Copyright 2014-18 Bryan Selner
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

use \JobScooper\Utils\SimpleHTMLHelper;

/**
 * Class AbstractTaleoATS
 */
abstract class AbstractTaleoATS extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    protected $use1ToTDForCount = false;
    protected $taleoOrgID = null;
    protected $JobListingsPerPage = 50;
    protected $arrResultsCountTag = array('Type' =>null, 'value'=>null, 'Index'=>null);
    protected $server = null;

    protected $PaginationType = C__PAGINATION_PAGE_VIA_NEXTBUTTON;

    protected $arrBaseListingTagSetup = [
		'JobPostCount' => ['Selector' => 'table#cws-search-results', 'Index' => 0, 'Pattern'=>'/.*(\d+).*/'],
        'JobPostItem' => ['Selector'=>'div.oracletaleocwsv2-accordion-head-info'],
        'NextButton' => ['Selector' => 'a.next'],
        'Title' => ['Selector' => 'h4 a'],
        'Url' => ['Selector' => 'h4 a', 'Index' => 0, 'Attribute' => 'href'],
        'Location' => ['Selector' => 'div', 'Index' => 1],
        'Category' => ['Selector' => 'div', 'Index' => 0],
        'JobSitePostId' => ['Selector' => 'h4 a', 'Index' => 0, 'Attribute' => 'href', 'Pattern' =>  '/rid=(.*)/i']
    ];

    /**
     * AbstractTaleoATS constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->additionalBitFlags['COMPANY'] = C__JOB_USE_SITENAME_AS_COMPANY;
        $this->PaginationType = C__PAGINATION_PAGE_VIA_URL;
        
        if (is_empty_value($this->server)) {
            $this->server = 'https://ch.tbe.taleo.net/CH11/ats/careers/searchResults.jsp';
        }

        if (is_empty_value($this->SearchUrlFormat) && !is_empty_value($this->taleoOrgID)) {
            $this->SearchUrlFormat = $this->server.'?org=' . $this->taleoOrgID . '&cws=1***ITEM_NUMBER***';
        }

        if (is_empty_value($this->JobSiteName)) {
            $strPluginSite = get_class($this);
            $strPluginSite = str_replace('Plugin', '', $strPluginSite);
            $this->JobSiteName = $strPluginSite;
        }

        parent::__construct();
    }

    /**
     * @param $objSimpHTML
     *
     * @throws \Exception
     * @return array|null|string
     */
    public function parseTotalResultsCount(\JobScooper\Utils\SimpleHTMLHelper $objSimpHTML)
    {
        if ($this->use1ToTDForCount) {
            return $this->parseTotalResultsCountFrom1ToTD($objSimpHTML);
        } else {
            return $this->parseTotalResultsCountFromTaleoCommonDivTable($objSimpHTML, $this->arrResultsCountTag['Type'], $this->arrResultsCountTag['Value'], $this->arrResultsCountTag['Index']);
        }
    }



    protected $SearchUrlFormat = null;

    /**
     * @param $nItem
     *
     * @return int|string
     */
    public function getItemURLValue($nItem)
    {
        $strFirst = "&act=first&rowFrom=" . $this->JobListingsPerPage;
        $strNext = "&act=next&rowFrom=";

        if ($nItem == null || $nItem == 1) {
            return $strFirst;
        }

        $ret = $nItem - $this->JobListingsPerPage;
        if ($ret < 0) {
            return $strFirst;
        }

        return $strNext . $ret;
    }


    /**
     * @param \JobScooper\Utils\SimpleHTMLHelper $objSimpHTML
     *
     * @return array|null
     * @throws \Exception
     */
    public function parseJobsListForPage(\JobScooper\Utils\SimpleHTMLHelper $objSimpHTML)
    {
        $ret = null;


        $nodesJobs= $objSimpHTML->find('table[id="cws-search-results"] tr');
        $counter = 0;

        foreach ($nodesJobs as $node) {
            if ($counter == 0) {
                $counter++;
                continue;
            } // skip the header row
            $counter++;

            $item = getEmptyJobListingRecord();
            $item['Company'] = $this->JobSiteName;
            $item['Url'] = $node->find("td a")[0]->href;
            $item['Title'] = $node->find("td a")[0]->text();
            $item['JobSitePostId'] = explode("rid=", $item['Url'])[1];
            if ($item['Title'] == '') {
                continue;
            }

            $tds = $node->find("td");
            if (isset($tds, $tds[1])) {
                $item['Location'] = $node->find("td")[1]->text();
            }
            if (isset($tds) && isset($tds[2])) {
                $item['Category'] = $tds[2]->text();
            }

            $ret[] = $item;
        }

        return $ret;
    }

    /**
     * @param $objSimpHTML
     * @throws \Exception
     * @return null
     */
    public function parseTotalResultsCountFrom1ToTD(SimpleHTMLHelper $objSimpHTML)
    {
        $nodes = $objSimpHTML->find('td[class="nowrapRegular"] b');
        if ($nodes && \count($nodes)>=2) {
            return $nodes[1]->text();
        }

        return null;
    }


    /**
     * @param \JobScooper\Utils\SimpleHTMLHelper $objSimpHTML
     * @param                                    $divTagType
     * @param                                    $divTagValue
     * @param                                    $trIndex
     *
     * @return array|null
     * @throws \Exception
     */
    public function parseTotalResultsCountFromTaleoCommonDivTable(SimpleHTMLHelper $objSimpHTML, $divTagType, $divTagValue, $trIndex)
    {
        $strTotalItemsCount = null;
        $node = $objSimpHTML->find("#wrapper div.avada-row table tbody tr td b");
        if (!empty($node)) {
            $text = $node[0];
            $strTotalItemsCount = $node[0]->text();
        } else {
            $nodeHelper = new SimpleHTMLHelper($objSimpHTML);
            $node = $nodeHelper->find("div[" . $divTagType . "='" . $divTagValue . "'] table tbody tr");
            if (!empty($node) && !empty($trIndex) && \count($node) > $trIndex + 1) {
                $node = $node[$trIndex];
            } else {
                return null;
            }

            $nodeSecond = new SimpleHTMLHelper($node);
            $nodeThird = $nodeSecond->find("td");
            if (!empty($nodeThird) && \count($nodeThird) >= 1) {
                $totalItemsText = $nodeThird[0]->text();
                $arrItemsFirstSplit = explode("found ", trim($totalItemsText));
                $strTotalItemsCount = explode(" matching", trim($arrItemsFirstSplit[1]));
            }
        }

        return $strTotalItemsCount;
    }
}


/**
 * Class PluginTraderJoes
 */
class PluginTraderJoes extends AbstractTaleoATS
{
    protected $use1ToTDForCount = true;
    protected $JobPostingBaseUrl = 'http://www.traderjoes.com/careers/index.asp';
    protected $taleoOrgID = 'TRADERJOES';
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'taleoContent', 'Index'=>1);


    /**
     * @param $arrItem
     * @throws \Exception
     * @return array
     */
    public function cleanupJobItemFields($arrItem)
    {
        if (array_key_exists('Location', $arrItem) && !empty($arrItem['Location'])) {
            $arrItem['Location'] = preg_replace('/Store #\d+ - /', '', $arrItem['Location']);
        }
        return parent::cleanupJobItemFields($arrItem);
    }
}

/**
 * Class PluginPorch
 */
class PluginPorch extends AbstractTaleoATS
{
    protected $use1ToTDForCount = true;
    protected $JobPostingBaseUrl = 'http://about.porch.com/careers';
    protected $taleoOrgID = 'PORCH';
    protected $arrResultsCountTag = array('type' =>'id', 'value'=>'summary', 'Index'=>1);
}
