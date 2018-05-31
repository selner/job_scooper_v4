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
use JobScooper\DataAccess\UserSearchSiteRun;

abstract class AbstractJobviteATS extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    /**
     * AbstractJobviteATS constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->additionalBitFlags[] = C__JOB_ITEMCOUNT_NOTAPPLICABLE__;
        $this->additionalBitFlags[] = C__JOB_USE_SITENAME_AS_COMPANY;
        parent::__construct();
    }

    /**
     * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
     */
    public function doFirstPageLoad(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
    {
        $this->_currentSearchDetails = $searchDetails;
    }

    /**
     * @var array
     */
    protected $arrListingTagSetup = array(
//        'JobPostItem'      => array('frame' => 'jobvite_careersite_iframe', 'Selector' => 'table.jv-job-list tr'),
        'JobPostItem'      => array('Selector' => 'table.jv-job-list tr'),
        'Title'                 => array('Selector' => 'td.jv-job-list-name a'),
        'Url'                 => array('Selector' => 'td.jv-job-list-name a', 'Attribute' => 'href'),
        'Location'              => array('Selector' => 'td.jv-job-list-location', 'Attribute' => 'text'),
        'JobSitePostId'                 => array('Selector' => 'td.jv-job-list-name a', 'Attribute' => 'href', 'Pattern' =>  '/job\/(.*)/i'),
    );
    /**
     * @var null
     */
    private $_currentSearchDetails = null;

    /**
     * @param \JobScooper\Utils\SimpleHTMLHelper $objSimpHTML
     *
     * @return \JobScooper\DataAccess\JobPosting[]|null
     * @throws \Exception
     */
    public function parseJobsListForPage(\JobScooper\Utils\SimpleHTMLHelper $objSimpHTML)
    {
        $frame = $objSimpHTML->find("*[name='jobvite_careersite_iframe']");
        if (!empty($frame) && array_key_exists('attr', $frame[0])) {
            $srcurl = $frame[0]->attr["src"];
            if (!empty($srcurl)) {
                $newUrl = parse_url($srcurl);
                $currentUrl = parse_url($this->getActiveWebdriver()->getCurrentURL());
                $newUrl['scheme'] = $currentUrl['scheme'];
                $url = http_build_url($newUrl);
                $objSimpHTML = $this->getSimpleHtmlDomFromSeleniumPage($this->_currentSearchDetails, $url);
            }
        }
        $retItems = parent::parseJobsListForPage($objSimpHTML);

        foreach ($retItems as $k => $ret) {
            if (!array_key_exists('Company', $ret) || empty($ret['Company'])) {
                $retItems[$k]['Company'] = $this->JobSiteKey;
            }
        }

        return $retItems;
    }
}
