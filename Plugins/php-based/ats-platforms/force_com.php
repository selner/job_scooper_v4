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


abstract class BaseForceComClass extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    protected $additionalLoadDelaySeconds = 3;
    protected $JobListingsPerPage = 25;
    protected $PaginationType = C__PAGINATION_PAGE_VIA_CALLBACK;

    /**
     * BaseForceComClass constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if (null === $this->SearchUrlFormat || strlen($this->SearchUrlFormat) == 0) {
            $this->JobPostingBaseUrl = "http://" . strtolower($this->JobSiteName) . ".force.com/careers";
            $this->SearchUrlFormat = "http://" . strtolower($this->JobSiteName) . ".force.com/careers";
        }
    }

    /**
     * @param null $nItem
     * @param null $nPage
     */
    public function takeNextPageAction()
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
        'TotalPostCount' => array('Selector' => 'div#atsSearchResultsText', 'Pattern' => '/(\d+).*?/'),
        'JobPostItem' => array('Selector' => "table.atsSearchResultsTable tbody tr"),
        'Title' =>  array('Selector' => 'td a', 'Index' => 0, 'Attribute' => 'text'),
        'Url' =>  array('Selector' => 'td a', 'Index' => 0, 'Attribute' => 'href'),
        'JobSitePostId' =>  array('Selector' => 'td a', 'Index' => 0, 'Attribute' => 'href', 'Pattern' => '/.*?jobId=(\w+)&.*?/'),
        'Department' =>  array('Selector' => 'td span', 'Index' => 0, 'Attribute' => 'text'),
        'Location' =>  array('Selector' => 'td span', 'Index' => 1, 'Attribute' => 'text'),
        'PostedAt' =>  array('Selector' => 'td span', 'Index' => 2, 'Attribute' => 'text')
    );
}



abstract class BaseNoDeptForceComClass extends BaseForceComClass
{
    /**
     * BaseNoDeptForceComClass constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->arrListingTagSetup['Department'] = null;
        $this->arrListingTagSetup['Location']['Index'] = 0;
        $this->arrListingTagSetup['PostedAt']['Index'] = 1;
    }
}

class PluginSlalom extends BaseNoDeptForceComClass
{
    protected $JobSiteName = "Slalom";
}
