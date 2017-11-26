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




class AbstractIcims extends \JobScooper\Plugins\lib\ServerHtmlSimplePlugin
{
    protected $additionalBitFlags = [C__JOB_ITEMCOUNT_NOTAPPLICABLE__, C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED];
    protected $JobListingsPerPage = 20;
    protected $strInitialReferer = null;
    protected $arrResultsRowTDIndex = null;

    function __construct($strBaseDir = null)
    {
        $this->strInitialReferer = $this->JobPostingBaseUrl . "/jobs/search?pr=0";
#        $this->SearchUrlFormat = $this->JobPostingBaseUrl . "/jobs/search?pr=***PAGE_NUMBER***&in_iframe=1&searchKeyword=***KEYWORDS***";
        $this->SearchUrlFormat = $this->JobPostingBaseUrl . "/jobs/search?pr=***PAGE_NUMBER***&in_iframe=1";
        $this->PaginationType = C__PAGINATION_PAGE_VIA_URL;

        if(is_null($this->arrResultsRowTDIndex))
            throw new InvalidArgumentException("Error in iCIMS plugin:  you must map the columns in the results table to the correct indexes for HTML tag matching. Aborting.");

        foreach(array_keys($this->arrResultsRowTDIndex) as $tagKey)
        {
            if(array_key_exists($tagKey, $this->arrResultsRowTDIndex) === true && is_null($this->arrResultsRowTDIndex[$tagKey]) !== true &&
                array_key_exists($tagKey, $this->arrListingTagSetup))
            {
                if(array_key_exists(0, $this->arrListingTagSetup[$tagKey]) && is_array($this->arrListingTagSetup[$tagKey]) === true)
                    $this->arrListingTagSetup[$tagKey][0]['index'] = $this->arrResultsRowTDIndex[$tagKey];
                else
                    $this->arrListingTagSetup[$tagKey]['index'] = $this->arrResultsRowTDIndex[$tagKey];
            }
        }

        parent::__construct();

    }

    protected $arrListingTagSetup = array(
#        'NoPostsFound' => array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'iCIMS_ListingsPage', 'return_attribute' => 'plaintext', 'return_value_regex' => '/\s*Job Listings\s*(Sorry)/'),
        'TotalResultPageCount' => array('selector' => '#iCIMS_Paginator', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?\s+(\d+)\s*$/'),
        'JobPostItem' => array(array('tag' => 'table', 'attribute'=>'class', 'attribute_value' => 'iCIMS_JobsTable iCIMS_Table'), array('tag' => 'tr')),
        'Title' =>  array(array('tag' => 'td', 'index' =>null), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'Url' =>  array(array('tag' => 'td', 'index' =>null), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' => '/(.*?)\?.*/'),
        'JobSitePostId' =>  array('tag' => 'td', 'index' =>null, 'return_attribute' => 'href', 'return_value_regex' => '/.*?([\d\-]+).*?$/'),
        'Location' =>  array('tag' => 'td', 'index' =>null, 'return_attribute' => 'plaintext',  'return_value_regex' => '/.*?Loc[\w]+:&nbsp;\s*([\w\s[:punct:]]+)?$/'),
//        'Title' =>  array(array('tag' => 'td', 'index' =>1), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
//        'Url' =>  array(array('tag' => 'td', 'index' =>1), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' => '/(.*?)\?.*/'),
//        'JobSitePostId' =>  array('tag' => 'td', 'index' =>1), 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?(\d+\-\d+).*?/'),
//        'Location' =>  array('tag' => 'td', 'index' =>2, 'return_attribute' => 'plaintext',  'return_value_regex' => '/.*?Loc[\w]+:&nbsp;\s*([\w\s\-]+)?$/')
    );
    protected function getPageURLValue($nPage) { return ($nPage - 1); }

}

class PluginHibbettSports extends AbstractIcims
{
    protected $JobSiteName = 'HibbettSports';
    protected $JobPostingBaseUrl = "https://retailcareers-hibbett.icims.com";
    protected $regex_link_job_id = '/.*?([\d\-]+).*?$/';
    protected $additionalLoadDelaySeconds = 3;

    protected $arrResultsRowTDIndex = array('Title' => 0, 'Url' => 0, 'JobSitePostId' => null, 'Department' => 1, 'Location' => 2, 'PostedAt' => null  );
    function __construct($strBaseDir = null)
    {
        $this->arrListingTagSetup['TotalResultPageCount'] = array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value' => 'iCIMS_Paginator_Bottom'), array('tag' => 'div') , array('tag' => 'div') , array('tag' => 'div'), 'index' => 1, 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?of\s+(\d+).*/');
        parent::__construct();
        $this->SearchUrlFormat = $this->JobPostingBaseUrl . "/jobs/search?pr=***PAGE_NUMBER***&in_iframe=1";
    }
}


class PluginFredHutch extends AbstractIcims
{
    protected $JobSiteName = 'FredHutch';
    protected $JobPostingBaseUrl = "https://hub-fhcrc.icims.com";
    protected $JobListingsPerPage = 167;

    // Results Columns:  "Posting date", "Job ID", "Job title", "Department", "Location", "Remote base"
    protected $arrResultsRowTDIndex = array('PostedAt' => null, 'JobSitePostId' => 0, 'Title' => 1, 'Url' => 1, 'Department' => null, 'Location' => 2 );
}

class PluginRedHat extends AbstractIcims
{
    protected $JobSiteName = 'RedHat';
    protected $JobListingsPerPage = 10;
    protected $JobPostingBaseUrl = "https://careers-redhat.icims.com";

    // Results Columns:  "Posting date", "Job ID", "Job title", "Department", "Location", "Remote base"
    protected $arrResultsRowTDIndex = array('PostedAt' => 0, 'JobSitePostId' => 1, 'Title' => 2, 'Url' => 2, 'Department' => 3, 'Location' => 4 );
}

class PluginParivedaSolutions extends AbstractIcims
{
    protected $JobSiteName = 'ParivedaSolutions';
    protected $JobPostingBaseUrl = "https://careers-parivedasolutions.icims.com";
    protected $arrResultsRowTDIndex = array('PostedAt' => null, 'JobSitePostId' => 0, 'Title' => 1, 'Url' => 1, 'Department' => null, 'Location' => 2 );
}
