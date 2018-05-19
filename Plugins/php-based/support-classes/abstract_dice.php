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


Use JBZoo\Utils\Url;

/**
 * Class AbstractDice
 *
 *       Used by dice.json plugin configuration to override single method
 */
abstract class AbstractBaseDice extends \JobScooper\SitePlugins\AjaxSitePlugin
{

	/**
	 * @param $searchDetails
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	function doFirstPageLoad(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
	{
		$urlInfo = parse_url($searchDetails->getSearchStartUrl());
		if(array_key_exists("query", $urlInfo))
			unset($urlInfo['query']);
		if(array_key_exists("fragment", $urlInfo))
			unset($urlInfo['fragment']);
		$url = Url::buildAll($urlInfo);
		$this->getSimpleHtmlDomFromSeleniumPage($searchDetails, $url);

		return $this->getSimpleHtmlDomFromSeleniumPage($searchDetails, $searchDetails->getSearchStartUrl());
	}
}
