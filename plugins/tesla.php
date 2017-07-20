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

class PluginTesla extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'Tesla';
    protected $childSiteURLBase = 'https://www.tesla.com/careers/search#';
    protected $childSiteListingPage = 'https://www.tesla.com/careers/search#';

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array(array('tag' => 'table', 'attribute' => 'class', 'attribute_value' =>'table'), array('tag' => 'tbody', 'attribute' => 'class', 'attribute_value' =>'table-body'), array('tag' => 'tr', 'attribute' => 'class', 'attribute_value' =>'table-row')),
        'tag_title' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_company' =>  array('return_value_callback' => 'setCompanyToSiteName'),
        'tag_job_id' => array(array('tag' => 'th', 'attribute' => 'class', 'attribute_value' => 'listing-title'), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>  '/.*?-(\d+)/i'),
        'tag_department' => array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'listing-department'),
        'tag_location' => array('tag' => 'td', 'attribute' => 'class', 'attribute_value' =>'listing-location')
    );

}


