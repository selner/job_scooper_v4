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


class PluginAuthenticJobs extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    protected $JobSiteName = 'AuthenticJobs';
    protected $JobPostingBaseUrl = 'https://authenticjobs.com';
    protected $SearchUrlFormat = 'https://authenticjobs.com/#location=***LOCATION***';
    #    protected $SearchUrlFormat = 'https://authenticjobs.com/#location=***LOCATION***&query=***KEYWORDS***';
    protected $LocationType = 'location-city';
    protected $JobListingsPerPage = 50;
    private $_currentSearchDetails = null;

    protected $arrListingTagSetup = [
        'NoPostsFound'    => ['Selector' => 'ul#listings li#no-results h1', 'Attribute' => 'text', 'Callback' => 'matchesNoResultsPattern', 'CallbackParameter' => '/no results/'],
        'JobPostItem'      => ['Selector' => 'ul#listings li'],
        'Title'                 =>  ['Selector' => 'a div h3', 'Attribute' => 'text'],
        'Url'                  =>  ['Selector' => 'a', 'Attribute' => 'href'],
        'Company'               =>  ['Selector' => 'a div h4', 'Attribute' => 'text'],
        'Location'              =>  ['Selector' => 'a ul li.location', 'Attribute' => 'text'],
        'EmploymentType'       =>  ['Selector' => 'a ul li', 'Index' => 0, 'Attribute' => 'text'],
        'JobSitePostId'                =>  ['Selector' => 'a', 'Attribute' => 'href', 'Pattern' =>  '/\/jobs\/([^?]+)/i'],
        'LoadMoreControl'             =>  ['Selector' => 'a.ladda-button']
    ];

    public function doFirstPageLoad(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
    {
        $this->_currentSearchDetails = $searchDetails;
    }

    protected function goToEndOfResultsSetViaLoadMore($nTotalItems)
    {
        $objSimplHtml = $this->getSimpleHtmlDomFromSeleniumPage($this->_currentSearchDetails);

        $node = $objSimplHtml->find('p.more');
        if ($node == null || count($node) == 0) {
            return false;
        } else {
            if (false !== stripos($node[0]->attr['style'], 'display: none')) {
                return false;
            }
        }

        return parent::goToEndOfResultsSetViaLoadMore($nTotalItems);
    }
}
