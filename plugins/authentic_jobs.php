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
require_once dirname(dirname(__FILE__))."/bootstrap.php";

class PluginAuthenticJobs extends \JobScooper\Plugins\Base\AjaxHtmlSimplePlugin
{
    protected $siteName = 'AuthenticJobs';
    protected $siteBaseURL = "https://authenticjobs.com";
    protected $strBaseURLFormat = 'https://authenticjobs.com/#location=***LOCATION***';
#    protected $strBaseURLFormat = 'https://authenticjobs.com/#location=***LOCATION***&query=***KEYWORDS***';
    protected $typeLocationSearchNeeded = 'location-city';
    protected $nJobListingsPerPage = 50;

    protected $arrListingTagSetup = array(
        'tag_listings_noresults'    => array('selector' => 'ul#listings li#no-results', 'return_attribute' => 'plaintext', 'return_value_callback' => "isNoJobResults"),
        'tag_listings_section'      => array('selector' => 'ul#listings li'),
        'tag_title'                 =>  array('selector' => 'a div h3', 'return_attribute' => 'plaintext'),
        'tag_link'                  =>  array('selector' => 'a', 'return_attribute' => 'href'),
        'tag_company'               =>  array('selector' => 'a div h4', 'return_attribute' => 'plaintext'),
        'tag_location'              =>  array('selector' => 'a ul li.location', 'return_attribute' => 'plaintext'),
        'tag_employment_type'       =>  array('selector' => 'a ul li', 'index' => 0, 'return_attribute' => 'plaintext'),
        'tag_job_id'                =>  array('selector' => 'a', 'return_attribute' => 'href', 'return_value_regex' =>  '/\/jobs\/([^?]+)/i'),
        'tag_load_more'             =>  array('selector' => 'a.ladda-button')
    );

    function isNoJobResults($var)
    {
        return noJobStringMatch($var, "No jobs");
    }


    protected function goToEndOfResultsSetViaLoadMore()
    {
        $objSimplHtml = $this->getSimpleHtmlDomFromSeleniumPage();

        $node = $objSimplHtml->find("p.more");
        if($node == null || count($node) == 0)
        {
            return false;
        }
        else
        {
            if(stristr($node[0]->attr["style"], "display: none") !== false) {
                return false;
            }
        }

        return parent::goToEndOfResultsSetViaLoadMore();
    }

}

