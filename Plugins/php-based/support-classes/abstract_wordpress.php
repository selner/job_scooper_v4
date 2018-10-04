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
 * Class AbstractWPJobify
 */
abstract class AbstractWordPress extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    protected $additionalLoadDelaySeconds = 20;
    protected $PaginationType = C__PAGINATION_INFSCROLLPAGE_VIALOADMORE;

    /**
     * @param \JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML
     *
     * @return array|null
     * @throws \Exception
     */
    public function parseJobsListForPage(\JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML)
    {
        $this->arrListingTagSetup['JobListingsPerPage'] =  array('Selector' => 'div.job_listings', 'Attribute' => 'data-per_page');
        $count = \JobScooper\Utils\SimpleHtml\DomItemParser::getTagValue($objSimpHTML, $this->arrListingTagSetup['JobListingsPerPage'], null, $this);
        if (!empty($count)) {
            $this->JobListingsPerPage = (int) $count;
        }
        unset($this->arrListingTagSetup['JobListingsPerPage']);

        return parent::parseJobsListForPage($objSimpHTML);
    }

    protected $arrBaseListingTagSetup = array(
        'JobPostItem' => array('Selector' => 'ul.job_listings li.job_listing'),
        'Title' => array('Selector' => 'h3'),
        'Url' => array('Selector' => 'a.job_listing-clickbox', 'Index' => 0, 'Attribute' => 'href'),
        'Company' => array('Selector' => 'div.job_listing-company strong', 'Attribute' => 'text'),
        'Location' => array('Selector' => 'div.job_listing-location a', 'Attribute' => 'text'),
        'PostedAt' => array('Selector' => 'date', 'Index' => 0),
        'Category' => array('Selector' => 'ul.meta li', 'Index' => 0),
        'company_logo' => array('Selector' => 'img.company_logo'),
        'JobSitePostId' =>  array('Selector' => 'a', 'Index' => 0, 'Attribute' => 'href', 'Pattern' =>  '/\/jobs\/job\/(.*)/i'),
        'LoadMoreControl' => array('Selector' => 'a.load_more_jobs')
    );
}
