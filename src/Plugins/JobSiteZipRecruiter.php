<?php
namespace Jobscooper\Plugins;

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


class JobSiteZipRecruiter extends \Jobscooper\BasePlugin\ClientSideHTMLJobSitePlugin
{
    protected $siteName = 'ziprecruiter';
    protected $siteBaseURL = 'www.ziprecruiter.com';
    protected $nJobListingsPerPage = C__TOTAL_ITEMS_UNKNOWN__; // we use this to make sure we only have 1 single results page

    protected $strBaseURLFormat = "https://www.ziprecruiter.com/candidate/search?search=***KEYWORDS***&include_near_duplicates=1&location=***LOCATION***&radius=25&days=***NUMBER_DAYS***";
    protected $paginationType = C__PAGINATION_INFSCROLLPAGE_VIALOADMORE;
    protected $typeLocationSearchNeeded = 'location-city-comma-statecode';

    protected $arrListingTagSetup = array(
        'tag_listings_noresults'    => array('selector' => 'section.no-results h2', 'return_attribute' => 'plaintext', 'return_value_callback' => "isNoJobResults"),
        'tag_listings_count'        => array('selector' => '#h1.headline', 'return_attribute' => 'plaintext', 'return_value_regex' =>  '/\b(\d+)\b/i'),
        'tag_listings_section'      => array('selector' => '#job_list div article'),
        'tag_title'                 => array('selector' => 'span.just_job_title', 'return_attribute' => 'plaintext'),
        'tag_link'                  => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => 'job_link', 'return_attribute' => 'href'),
        'tag_company'               => array('tag' => 'a', 'attribute'=>'class', 'attribute_value' => 't_org_link name', 'return_attribute' => 'plaintext'),
        'tag_location'              => array('tag' => '*', 'attribute'=>'class', 'attribute_value' => 'location', 'return_attribute' => 'plaintext'),
        'tag_job_id'                => array('tag' => 'span', 'attribute'=>'class', 'attribute_value' => 'just_job_title', 'return_attribute' => 'data-job-id'),
    );

    function isNoJobResults($var)
    {
        return noJobStringMatch($var, "No jobs");
    }
    
    function parseTotalResultsCount($objSimpl)
    {
        sleep($this->additionalLoadDelaySeconds + 1);

        $dismissPopup = "
            var popup = document.querySelector('.div#createAlertPop'); 
            if (popup != null) 
            {
                var popupstyle = popup.getAttribute('style'); 
                if (popupstyle!= null && popupstyle.indexOf('display: none') < 0) {
                    var close = document.querySelector('.modal-close'); 
                    if (close != null) 
                    {
                        console.log('Clicking close on modal popup dialog...');
                        close.click();
                    }
                }
            }
        ";

        $this->runJavaScriptSnippet($dismissPopup, true);

        return parent::parseTotalResultsCount($objSimpl);
    }

    protected function goToEndOfResultsSetViaLoadMore($nTotalItems = null)
    {
        $this->selectorMoreListings = ".load_more_jobs";
        parent::goToEndOfResultsSetViaLoadMore($nTotalItems);

        parent::goToEndOfResultsSetViaPageDown($nTotalItems);

    }

}
