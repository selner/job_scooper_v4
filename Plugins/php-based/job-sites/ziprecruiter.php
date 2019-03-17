<?php

/**
 * Copyright 2014-18 Bryan Selner
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



/**
 * Class PluginZipRecruiter
 * @package JobScooper\Plugins
 */
class PluginZipRecruiter extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    protected $JobSiteName = 'ziprecruiter';
    protected $JobPostingBaseUrl = 'www.ziprecruiter.com';
    protected $JobListingsPerPage = C__TOTAL_ITEMS_UNKNOWN__; // we use this to make sure we only have 1 single results page
    protected $CountryCodes = array("US");

    protected $SearchUrlFormat = "https://www.ziprecruiter.com/candidate/search?search=***KEYWORDS***&include_near_duplicates=1&location=***LOCATION***&radius=25&days=***NUMBER_DAYS***";
    protected $PaginationType = C__PAGINATION_INFSCROLLPAGE_VIALOADMORE;
    protected $LocationType = 'location-city-comma-statecode';

    protected $arrListingTagSetup = [
        'NoPostsFound'    => ['Selector' => 'section.no-results h2', 'Attribute' => 'text', 'Callback' => 'matchesNoResultsPattern', 'CallbackParameter' => 'no jobs'],
        'TotalPostCount'        => ['Selector' => 'h1.headline', 'Attribute' => 'text', 'Pattern' =>  '/\b([\d,]+)\+?\b/i'],
        'JobPostItem'      => ['Selector' => '#job_list div article'],
        'Title'                 => ['Selector' => 'span.just_job_title', 'Attribute' => 'text'],
        'Url'                  => ['Selector' => 'a.job_link', 'Attribute' => 'href'],
        'Company'               => ['Selector' => 'a.t_org_link name', 'Attribute' => 'text'],
        'Location'              => ['Selector' => '*.location', 'Attribute' => 'text'],
        'JobSitePostId'                => ['Selector' => 'span.just_job_title', 'Attribute' => 'data-job-id']
    ];
    
    /**
     * @param $objSimpHTML
     *
     * @return null|string
     * @throws \Exception
     */
    public function parseTotalResultsCount(\JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML)
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

        return parent::parseTotalResultsCount($objSimpHTML);
    }

    /**
     * @param null $nTotalItems
     *
     * @throws \Exception
     */
    protected function goToEndOfResultsSetViaLoadMore($nTotalItems = null)
    {
        $this->selectorMoreListings = ".load_more_jobs";
        parent::goToEndOfResultsSetViaLoadMore($nTotalItems);

        parent::goToEndOfResultsSetViaPageDown($nTotalItems);
    }
}


class PluginZipRecruiterUK extends PluginZipRecruiter
{
    protected $JobSiteName = 'ZipRecruiterUK';
    protected $JobPostingBaseUrl = 'http://www.ziprecruiter.co.uk';
    protected $SearchUrlFormat = "https://www.ziprecruiter.co.uk/candidate/search?search=***KEYWORDS***&include_near_duplicates=1&location=***LOCATION***&radius=25&days=***NUMBER_DAYS***";
    protected $CountryCodes = array("UK");
}
