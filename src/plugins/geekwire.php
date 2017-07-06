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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');





class PluginGeekwire extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'Geekwire';
    protected $siteBaseURL = 'http://www.geekwire.com/';
    protected $strBaseURLFormat = "http://www.geekwire.com/jobs/";
    protected $additionalLoadDelaySeconds = 20;
    protected $additionalFlags = [C__JOB_SETTINGS_GET_ALL_JOBS_UNFILTERED, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_CLIENTSIDE_INFSCROLLPAGE_NOCONTROL, C__JOB_ITEMCOUNT_NOTAPPLICABLE__, C__JOB_PAGECOUNT_NOTAPPLICABLE__];
    protected $arrListingTagSetup = array(
        'tag_listings_section' => array('selector' => 'ul.job_listings li.type-job_listing'),
        'tag_title' => array('tag' => 'h3'),
        'tag_link' => array('tag' => 'a', 'index' => 0, 'return_attribute' => 'href'),
        'tag_company' => array('selector' => 'div.company span', 'index' => 0),
        'tag_location' => array('selector' => 'div.location'),
        'tag_job_posting_date' => array('selector' => 'date', 'index' => 0),
        'tag_job_category' => array('selector' => 'ul.meta li', 'index' => 0),
        'tag_job_id' =>  array('tag' => 'a', 'index' => 0, 'return_attribute' => 'href', 'return_value_regex' =>  '/\/jobs\/job\/(.*)/i')
    );



}
