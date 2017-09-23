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
    protected $nJobListingsPerPage = 25;
    protected $paginationType = C__PAGINATION_PAGE_VIA_CALLBACK;

    function __construct()
    {
        parent::__construct();

        if(is_null($this->strBaseURLFormat) || strlen($this->strBaseURLFormat) == 0) {
            $this->siteBaseURL = "http://" . strtolower($this->siteName) . ".force.com/careers";
            $this->strBaseURLFormat = "http://" . strtolower($this->siteName) . ".force.com/careers";
        }
    }

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



    protected $arrListingTagSetup = array(
        'tag_listings_count' => array('selector' => 'div#atsSearchResultsText', 'return_value_regex' => '/(\d+).*?/'),
        'tag_listings_section' => array('selector' => "table.atsSearchResultsTable tbody tr"),
        'tag_title' =>  array('selector' => 'td a', 'index' => 0, 'return_attribute' => 'plaintext'),
        'tag_link' =>  array('selector' => 'td a', 'index' => 0, 'return_attribute' => 'href'),
        'tag_job_id' =>  array('selector' => 'td a', 'index' => 0, 'return_attribute' => 'href', 'return_value_regex' => '/.*?jobId=(\w+)&.*?/'),
        'tag_department' =>  array('selector' => 'td span', 'index' => 1, 'return_attribute' => 'plaintext'),
        'tag_location' =>  array('selector' => 'td span', 'index' => 2, 'return_attribute' => 'plaintext'),
        'tag_job_posting_date' =>  array('selector' => 'td  pan', 'index' => 3, 'return_attribute' => 'plaintext'),
        'tag_company' =>  array('return_value_callback' => 'setCompanyToSiteName')
    );

}


class PluginAltasource extends BaseForceComClass
{
    protected $siteName = 'Altasource';
    protected $siteBaseURL = "http://altasourcegroup.force.com";
    protected $strBaseURLFormat = "http://altasourcegroup.force.com/careers";

}


class PluginSlalom extends BaseForceComClass
{
    function __construct()
    {
        $this->siteName = 'Slalom';
        parent::__construct();
        $this->arrListingTagSetup['tag_department'] = null;
        $this->arrListingTagSetup['tag_location']['index'] = 1;
        $this->arrListingTagSetup['tag_job_posting_date']['index'] = 2;
    }
}