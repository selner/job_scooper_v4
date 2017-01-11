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

class PluginMediaBistro extends ClassHTMLJobSitePlugin
{
protected $siteName = 'MediaBistro';
protected $childSiteURLBase = 'https://www.mediabistro.com';
protected $childSiteListingPage = 'https://www.mediabistro.com/jobs/openings/';
protected $addtionalFlags = [C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_PREFER_MICRODATA];


function parseTotalResultsCount($objSimpHTML)
{
$nTotalResults = C__TOTAL_ITEMS_UNKNOWN__;

//
// Find the HTML node that holds the result count
$nodeCounts = $objSimpHTML->find("div[id='atsSearchResultsText']");
if($nodeCounts != null && is_array($nodeCounts) && isset($nodeCounts[0]))
{
$counts = explode("&nbsp", $nodeCounts[0]->plaintext);
$nTotalResults = \Scooper\intceil($counts[0]);
}


return $nTotalResults;

}

protected $arrListingTagSetup = array(
'tag_listings_section' => array('selector' => "table.atsSearchResultsTable tbody tr"),
'tag_title' =>  array(array('tag' => 'td', 'index' => 0), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
'tag_link' =>  array(array('tag' => 'td', 'index' => 0), array('tag' => 'a'), 'return_attribute' => 'href'),
'tag_department' =>  array(array('tag' => 'td'), array('tag' => 'span'), 'index' => 0),
'tag_location' =>  array(array('tag' => 'td'), array('tag' => 'span'), 'index' => 1),
'tag_company' =>  array('return_value_callback' => 'ClassBaseHTMLJobSitePlugin::setCompanyToSiteName'),
'regex_link_job_id' => '/.*?jobId=([^&]+)/i'
);

public function takeNextPageAction($driver)
{
$GLOBALS['logger']->logLine("Going to next page of results via script: " . $this->nextPageScript  , \Scooper\C__DISPLAY_NORMAL__);
$driver->executeScript("function callNextPage() { " . $this->nextPageScript ." } ; callNextPage();");
sleep($this->additionalLoadDelaySeconds);
return $driver;

}

}

