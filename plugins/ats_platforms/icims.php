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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(dirname(__FILE__)))); }
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');



class PluginHibbettSports extends BasePluginiCIMS
{
    protected $siteName = 'HibbettSports';
    protected $siteBaseURL = "https://retailcareers-hibbett.icims.com";
    protected $regex_link_job_id = '/.*?([\d\-]+).*?$/';
    protected $additionalLoadDelaySeconds = 3;

    protected $arrResultsRowTDIndex = array('tag_title' => 0, 'tag_link' => 0, 'tag_job_id' => null, 'tag_department' => 1, 'tag_location' => 2, 'tag_job_posting_date' => null  );
    function __construct($strBaseDir = null)
    {
        $additionalFlags[] = C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED;

        $this->arrListingTagSetup['tag_pages_count'] = array(array('tag' => 'div', 'attribute'=>'class', 'attribute_value' => 'iCIMS_Paginator_Bottom'), array('tag' => 'div') , array('tag' => 'div') , array('tag' => 'div'), 'index' => 1, 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?of\s+(\d+).*/');
        parent::__construct($strBaseDir);
        $this->strBaseURLFormat = $this->siteBaseURL . "/jobs/search?pr=***PAGE_NUMBER***&in_iframe=1";
    }
}


class PluginFredHutch extends BasePluginiCIMS
{
    protected $siteName = 'FredHutch';
    protected $siteBaseURL = "https://hub-fhcrc.icims.com";

    // Results Columns:  "Posting date", "Job ID", "Job title", "Department", "Location", "Remote base"
    protected $arrResultsRowTDIndex = array('tag_job_posting_date' => null, 'tag_job_id' => 0, 'tag_title' => 1, 'tag_link' => 1, 'tag_department' => null, 'tag_location' => 2 );
}

class PluginRedHat extends BasePluginiCIMS
{
    protected $siteName = 'RedHat';
    protected $nJobListingsPerPage = 10;
    protected $siteBaseURL = "https://careers-redhat.icims.com";

    // Results Columns:  "Posting date", "Job ID", "Job title", "Department", "Location", "Remote base"
    protected $arrResultsRowTDIndex = array('tag_job_posting_date' => 0, 'tag_job_id' => 1, 'tag_title' => 2, 'tag_link' => 2, 'tag_department' => 3, 'tag_location' => 4 );
}

class PluginParivedaSolutions extends BasePluginiCIMS
{
    protected $siteName = 'ParivedaSolutions';
    protected $siteBaseURL = "https://careers-parivedasolutions.icims.com";
    protected $arrResultsRowTDIndex = array('tag_job_posting_date' => null, 'tag_job_id' => 0, 'tag_title' => 1, 'tag_link' => 1, 'tag_department' => null, 'tag_location' => 2 );
}

class BasePluginiCIMS extends ClassHTMLJobSitePlugin
{
    protected $additionalFlags = [C__JOB_ITEMCOUNT_NOTAPPLICABLE__, C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED];
    protected $nJobListingsPerPage = 20;
    protected $strInitialReferer = null;
    protected $arrResultsRowTDIndex = null;

    function __construct($strBaseDir = null)
    {
        $additionalFlags[] = C__JOB_DAYS_VALUE_NOTAPPLICABLE__;
        $additionalFlags[] = C__JOB_ITEMCOUNT_NOTAPPLICABLE__;
        $additionalFlags[] = C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED;
        $this->strInitialReferer = $this->siteBaseURL . "/jobs/search?pr=0";
        $this->strBaseURLFormat = $this->siteBaseURL . "/jobs/search?pr=***PAGE_NUMBER***&in_iframe=1&searchKeyword=***KEYWORDS***";

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

        parent::__construct($strBaseDir);

    }

    protected $arrListingTagSetup = array(
        'tag_pages_count' => array('selector' => '#iCIMS_Paginator', 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?\s+(\d+)\s*$/'),
        'tag_listings_section' => array(array('tag' => 'table', 'attribute'=>'class', 'attribute_value' => 'iCIMS_JobsTable iCIMS_Table'), array('tag' => 'tr')),
        'tag_title' =>  array(array('tag' => 'td', 'index' =>null), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
        'tag_link' =>  array(array('tag' => 'td', 'index' =>null), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' => '/(.*?)\?.*/'),
        'tag_job_id' =>  array('tag' => 'td', 'index' =>null, 'return_attribute' => 'href', 'return_value_regex' => '/.*?([\d\-]+).*?$/'),
        'tag_location' =>  array('tag' => 'td', 'index' =>null, 'return_attribute' => 'plaintext',  'return_value_regex' => '/.*?Loc[\w]+:&nbsp;\s*([\w\s[:punct:]]+)?$/'),
        'tag_company' =>  array('return_value_callback' => 'ClassBaseHTMLJobSitePlugin::setCompanyToSiteName'),
//        'tag_title' =>  array(array('tag' => 'td', 'index' =>1), array('tag' => 'a'), 'return_attribute' => 'plaintext'),
//        'tag_link' =>  array(array('tag' => 'td', 'index' =>1), array('tag' => 'a'), 'return_attribute' => 'href', 'return_value_regex' => '/(.*?)\?.*/'),
//        'tag_job_id' =>  array('tag' => 'td', 'index' =>1), 'return_attribute' => 'plaintext', 'return_value_regex' => '/.*?(\d+\-\d+).*?/'),
//        'tag_location' =>  array('tag' => 'td', 'index' =>2, 'return_attribute' => 'plaintext',  'return_value_regex' => '/.*?Loc[\w]+:&nbsp;\s*([\w\s\-]+)?$/')
    );
    protected function getPageURLValue($nPage) { return ($nPage - 1); }

}
