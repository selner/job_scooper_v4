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


class PluginGeekwire extends \JobScooper\BasePlugin\Classes\AjaxHtmlSimplePlugin
{
    protected $JobSiteName = 'Geekwire';
    protected $JobPostingBaseUrl = 'http://www.geekwire.com/';
    protected $SearchUrlFormat = "http://www.geekwire.com/jobs/";
    protected $additionalLoadDelaySeconds = 20;
    protected $PaginationType = C__PAGINATION_NONE;

    protected $arrListingTagSetup = array(
        'JobPostItem' => array('selector' => 'ul.job_listings li.job_listing'),
        'Title' => array('tag' => 'h3'),
        'Url' => array('tag' => 'a.job_listing-clickbox', 'index' => 0, 'return_attribute' => 'href'),
        'Company' => array('selector' => 'div.job_listing-company strong', 'return_attribute' => 'text'),
        'Location' => array('selector' => 'div.job_listing-location a', 'return_attribute' => 'text'),
        'PostedAt' => array('selector' => 'date', 'index' => 0),
        'Category' => array('selector' => 'ul.meta li', 'index' => 0),
        'company_logo' => array('selector' => 'img.company_logo'),
        'JobSitePostId' =>  array('tag' => 'a', 'index' => 0, 'return_attribute' => 'href', 'return_value_regex' =>  '/\/jobs\/job\/(.*)/i')
    );



}
