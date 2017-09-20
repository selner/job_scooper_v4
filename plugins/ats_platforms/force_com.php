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
require_once dirname(dirname(dirname(__FILE__)))."/bootstrap.php";

class BaseForceComClass extends ClassClientHTMLJobSitePlugin
{
    protected $additionalLoadDelaySeconds = 3;
    protected $nJobListingsPerPage = 50;
    protected $paginationType = C__PAGINATION_PAGE_VIA_CALLBACK;

    function takeNextPageAction($nItem=null, $nPage=null)
    {
        $nextPageJS = "function contains(selector, text) {
                var elements = document.querySelectorAll(selector);
                return Array.prototype.filter.call(elements, function(element){
                return RegExp(text).test(element.textContent);
                });
            }
            var linkNext = contains('a', 'Next');
            if(linkNext.length >= 1)
            {
                console.log(linkNext[0]);
                linkNext[0].click();
            }
        ";

        $this->runJavaScriptSnippet($nextPageJS, false);
    }

    function parseTotalResultsCount($objSimpHTML)
    {
        $nTotalResults = C__TOTAL_ITEMS_UNKNOWN__;

        //
        // Find the HTML node that holds the result count
        $nodeCounts = $objSimpHTML->find("div[id='atsSearchResultsText']");
        if($nodeCounts != null && is_array($nodeCounts) && isset($nodeCounts[0]))
        {
            $counts = explode("&nbsp", $nodeCounts[0]->plaintext);
            $nTotalResults = \Scooper\intceil($counts[0]);
        } 


        return $nTotalResults;

    }

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('selector' => "table.atsSearchResultsTable tbody tr"),
        'tag_title' =>  array(array('tag' => 'td', 'index' => 0), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'td', 'index' => 0), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_department' =>  array(array('tag' => 'td'), array('tag' => 'span'), 'index' => 0),
        'tag_location' =>  array(array('tag' => 'td'), array('tag' => 'span'), 'index' => 1),
        'tag_company' =>  array('return_value_callback' => 'setCompanyToSiteName'),
        'tag_next_button' => array('selector' => '#j_id0:j_id1:atsForm:j_id154'),
        'regex_link_job_id' => '/.*?jobId=([^&]+)/i'
    );

}


class PluginAltasource extends BaseForceComClass
{
    protected $siteName = 'Altasource';
    protected $siteBaseURL = "http://altasourcegroup.force.com";
    protected $nJobListingsPerPage = 25;
    protected $strBaseURLFormat = "http://altasourcegroup.force.com/careers";
}