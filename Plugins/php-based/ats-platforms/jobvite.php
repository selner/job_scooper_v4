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
	function __construct()
    {
	    $this->additionalBitFlags[] = C__JOB_ITEMCOUNT_NOTAPPLICABLE__;
	    $this->additionalBitFlags[] = C__JOB_USE_SITENAME_AS_COMPANY;
        parent::__construct();
    }

	/**
	 * @param $var
	 *
	 * @return int|null
	 * @throws \Exception
	 */
	static function checkNoJobResults($var)
    {
        return noJobStringMatch($var, "Found 0 jobs");
    }

	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 */
	function doFirstPageLoad(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
	{
		$this->_currentSearchDetails = $searchDetails;
	}

	/**
	 * @var array
	 */
	protected $arrListingTagSetup = array(
//        'JobPostItem'      => array('frame' => 'jobvite_careersite_iframe', 'selector' => 'table.jv-job-list tr'),
        'JobPostItem'      => array('selector' => 'table.jv-job-list tr'),
        'Title'                 => array('selector' => 'td.jv-job-list-name a'),
        'Url'                 => array('selector' => 'td.jv-job-list-name a', 'return_attribute' => 'href'),
        'Location'              => array('selector' => 'td.jv-job-list-location', 'return_attribute' => 'text'),
        'JobSitePostId'                 => array('selector' => 'td.jv-job-list-name a', 'return_attribute' => 'href', 'return_value_regex' =>  '/job\/(.*)/i'),
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
	function parseJobsListForPage(\JobScooper\Utils\SimpleHTMLHelper $objSimpHTML)
    {

        $frame = $objSimpHTML->find("*[name='jobvite_careersite_iframe']");
        if(!empty($frame) && array_key_exists('attr', $frame[0]))
        {
            $srcurl = $frame[0]->attr["src"];
            if(!empty($srcurl)) {
                $newUrl = parse_url($srcurl);
                $currentUrl = parse_url($this->getActiveWebdriver()->getCurrentUrl());
                $newUrl['scheme'] = $currentUrl['scheme'];
                $url = http_build_url($newUrl);
                $objSimpHTML = $this->getSimpleHtmlDomFromSeleniumPage($this->_currentSearchDetails, $url);
            }
        }
        $retItems = parent::parseJobsListForPage($objSimpHTML);

	    foreach($retItems as $k => $ret)
	    {
	    	if(!array_key_exists('Company', $ret) || empty($ret['Company']))
			    $retItems[$k]['Company'] = $this->getJobSiteKey();
	    }

	    return $retItems;
    }
}
