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


class PluginGeekwire extends \JobScooper\Plugins\lib\AjaxHtmlSimplePlugin
{
    protected $siteName = 'Geekwire';
    protected $siteBaseURL = 'http://www.geekwire.com/';
    protected $strBaseURLFormat = "http://www.geekwire.com/jobs/";
    protected $additionalLoadDelaySeconds = 20;
    protected $paginationType = C__PAGINATION_NONE;

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('selector' => 'ul.job_listings li.type-job_listing'),
        'tag_title' => array('tag' => 'h3'),
        'tag_link' => array('tag' => 'a.job_listing-clickbox', 'index' => 0, 'return_attribute' => 'href'),
        'tag_company' => array('selector' => 'div.job_listing-company strong', 'return_attribute' => 'plaintext'),
        'tag_location' => array('selector' => 'div.job_listing-location a', 'return_attribute' => 'plaintext'),
        'tag_job_posting_date' => array('selector' => 'date', 'index' => 0),
        'tag_job_category' => array('selector' => 'ul.meta li', 'index' => 0),
        'tag_company_logo' => array('selector' => 'img.company_logo'),
        'tag_job_id' =>  array('tag' => 'a', 'index' => 0, 'return_attribute' => 'href', 'return_value_regex' =>  '/\/jobs\/job\/(.*)/i')
    );



}
