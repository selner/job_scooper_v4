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

namespace JobScooper\BasePlugin\Classes;


use JobScooper\Utils\SimpleHTMLHelper;

class AjaxMicrodataOnlyPlugin extends AjaxHtmlSimplePlugin
{
	function __construct()
	{
		parent::__construct();
	}

	protected $arrListingTagSetup = array(
		'JobPostItem' => array('selector' => ''),
		'Title' => array('selector' => ''),
		'Url' => array('selector' => ''),
		'Company' => array('selector' => ''),
		'Location' => array('selector' => ''),
		'PostedAt' => array('selector' => ''),
		'Category' => array('selector' => ''),
		'JobSitePostId' =>  array('selector' => '')
	);


	/**
	 * /**
	 * parseJobsListForPage
	 *
	 * This does the heavy lifting of parsing each job record from the
	 * page's HTML it was passed.
	 *
	 * @param \JobScooper\Utils\SimpleHTMLHelper $objSimpHTML
	 *
	 * @return array|null
	 * @throws \Exception
	 */
	function parseJobsListForPage(SimpleHTMLHelper $objSimpHTML)
	{
		$ret = array();


		$itempropNodes = $objSimpHTML->find("*[itemtype=\"http://schema.org/JobPosting\"]");
		if(!empty($itempropNodes) && is_array($itempropNodes)) {
			foreach($itempropNodes as $itemnode)
			{
				$item = $this->parseSingleJob($itemnode);
				$item = $this->cleanupJobItemFields($item);
				$ret[] = $item;
			}
		}
		return $ret;
	}
}
