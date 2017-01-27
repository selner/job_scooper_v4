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
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');

class PluginOuterwall extends ClassHTMLJobSitePlugin
{
    protected $siteName = 'Outerwall';
    protected $siteBaseURL = 'http://outerwall.jobs';
    protected $strBaseURLFormat = "http://outerwall.jobs/***LOCATION***/usa/jobs/";
    protected $typeLocationSearchNeeded = 'location-state';

    protected $arrListingTagSetup = array(

        'tag_listings_count' => array('tag' => 'h3', 'attribute' => 'class', 'attribute_value' =>'direct_highlightedText', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?(\d+).*?/'),
        'tag_listings_section' => array(array('tag' => 'ul', 'attribute'=>'class', 'attribute_value' => 'default_jobListing'), array('tag' => 'li')),
        'tag_title' => array(array('tag' => 'h4'), array('tag' => 'a'), array('tag' => 'span'), 'return_attribute' => 'plaintext'),
        'tag_company' =>  array('return_value_callback' => 'ClassBaseHTMLJobSitePlugin::setCompanyToSiteName'),
        'tag_link' =>  array(array('tag' => 'h4'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_location' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' =>'direct_joblocation'),
        'regex_link_job_id' => '/\/[j\/]{0,2}(.*)/i'
    );

}

