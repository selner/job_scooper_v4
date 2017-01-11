
<?php

/**
 * Copyright 2014-16 Bryan Selner
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

class PluginAuthenticJobs extends ClassClientHTMLJobSitePlugin
{
protected $siteName = 'AuthenticJobs';
protected $siteBaseURL = "https://authenticjobs.com";
protected $strBaseURLFormat = 'https://authenticjobs.com/#location=***LOCATION***&query=***KEYWORDS***';
protected $additionalFlags = [C__JOB_CLIENTSIDE_INFSCROLLPAGE, C__JOB_PAGECOUNT_NOTAPPLICABLE__ , C__JOB_ITEMCOUNT_NOTAPPLICABLE__];
protected $typeLocationSearchNeeded = 'location-city-comma-statecode';
protected $nJobListingsPerPage = 50;


protected $arrListingTagSetup = array(
'tag_listings_count' => array(array('tag' => 'ul', 'attribute'=>'id', 'attribute_value' => 'listings'), 'return_attribute' => 'data-total'),
'tag_listings_section' => array(array('tag' => 'ul', 'attribute'=>'id', 'attribute_value' => 'listings'), array('tag' => 'li')),
'tag_title' =>  array(array('tag' => 'a'), array('tag' => 'div'),array('tag' => 'h3'), 'return_attribute' => 'plaintext'),
'tag_link' =>  array(array('tag' => 'a'), 'return_attribute' => 'href'),
'tag_company' =>  array(array('tag' => 'a'), array('tag' => 'div'),array('tag' => 'h4'), 'return_attribute' => 'plaintext'),
'tag_location' =>  array(array('tag' => 'a'), array('tag' => 'ul'),array('tag' => 'li', 'attribute' => 'class', 'attribute_value' =>'location'), 'return_attribute' => 'plaintext'),
'tag_employment_type' =>  array(array('tag' => 'a'), array('tag' => 'ul'),array('tag' => 'li', 'index' => 0), 'return_attribute' => 'plaintext'),
'tag_job_id' =>  array(array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' =>  '/\/jobs\/([^?]+)/i'),
'tag_load_more' =>  array('tag' => 'a', 'attribute' => 'class', 'attribute_value' =>'ladda-button')
);

}

