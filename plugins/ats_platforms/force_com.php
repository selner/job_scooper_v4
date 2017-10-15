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


abstract class BaseForceComClass extends \JobScooper\Plugins\lib\AjaxHtmlSimplePlugin
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
        'TotalPostCount' => array('selector' => 'div#atsSearchResultsText', 'return_value_regex' => '/(\d+).*?/'),
        'JobPostItem' => array('selector' => "table.atsSearchResultsTable tbody tr"),
        'Title' =>  array('selector' => 'td a', 'index' => 0, 'return_attribute' => 'plaintext'),
        'Url' =>  array('selector' => 'td a', 'index' => 0, 'return_attribute' => 'href'),
        'JobSitePostId' =>  array('selector' => 'td a', 'index' => 0, 'return_attribute' => 'href', 'return_value_regex' => '/.*?jobId=(\w+)&.*?/'),
        'Department' =>  array('selector' => 'td span', 'index' => 0, 'return_attribute' => 'plaintext'),
        'Location' =>  array('selector' => 'td span', 'index' => 1, 'return_attribute' => 'plaintext'),
        'PostedAt' =>  array('selector' => 'td span', 'index' => 2, 'return_attribute' => 'plaintext'),
        'Company' =>  array('return_value_callback' => 'setCompanyToSiteName')
    );

}


class PluginAltasource extends BaseForceComClass
{
    protected $siteName = 'Altasource';
    protected $siteBaseURL = "http://altasourcegroup.force.com";
    protected $strBaseURLFormat = "http://altasourcegroup.force.com/careers";


}


abstract class BaseNoDeptForceComClass extends BaseForceComClass
{
    function __construct()
    {
        parent::__construct();
        $this->arrListingTagSetup['Department'] = null;
        $this->arrListingTagSetup['Location']['index'] = 0;
        $this->arrListingTagSetup['PostedAt']['index'] = 1;
    }
}

class PluginSlalom extends BaseNoDeptForceComClass
{
    protected $siteName = "Slalom";
}
class PluginRobertHalfExec extends BaseForceComClass
{
    protected $siteName= "RobertHalfExec";
    protected $siteBaseURL = "http://roberthalf.force.com";
    protected $strBaseURLFormat = "http://roberthalf.force.com/careers";
    protected $additionalFlags = [ C__JOB_USE_SELENIUM ];
    function __construct()
    {
        parent::__construct();
        $this->arrListingTagSetup['Department']['index'] = 1;
        $this->arrListingTagSetup['Location']['index'] = 2;
        $this->arrListingTagSetup['PostedAt'] = null;
    }

    function doFirstPageLoad($searchDetails)
    {
        $js = "
            setTimeout(clickSearchButton, " . strval($this->additionalLoadDelaySeconds) .");

            function clickSearchButton() 
            {
                var btnSearch = document.querySelector(\"input[value=Search]\");
                if(btnSearch != null && !typeof(btnSearch.click) !== \"function\" && btnSearch.length >= 1) {
                    btnSearch = btnSearch[0];
                } 
                
                if(btnSearch != null && btnSearch.style.display === \"\") 
                { 
                    btnSearch.click();  
                    console.log(\"Clicked search button control...\");
                }
                else
                {
                    console.log('Search button was not active.');
                }
            }  
        ";

        $this->selenium->getPageHTML($searchDetails->getSearchParameter('search_start_url'));

        $this->runJavaScriptSnippet($js, false);
        sleep($this->additionalLoadDelaySeconds + 2);

        $html = $this->getActiveWebdriver()->getPageSource();
        return $html;
    }

}