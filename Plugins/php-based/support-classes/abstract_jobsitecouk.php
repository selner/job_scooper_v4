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
 * Class AbstractJobSiteCoUk
 *
 *       Used by dice.json plugin configuration to override single method
 */
abstract class AbstractJobSiteCoUk extends \JobScooper\SitePlugins\AjaxSitePlugin
{

	public function __construct($strBaseDir = null) {
		
		parent::__construct($strBaseDir);
	}
	
    /**
     * @param $searchDetails
     *
     * @return mixed
     * @throws \Exception
     */
    public function parseTotalResultsCount(\JobScooper\Utils\SimpleHtml\SimpleHTMLHelper $objSimpHTML)
    {
        if (empty($this->arrListingTagSetup)) {
            throw new \BadMethodCallException(sprintf('Not implemented method  %s called on class %s', __METHOD__, __CLASS__));
        }
        
        $count = $this->getActiveWebdriver()->executeScript(
        	'return window.analytics.TotalJobCount;');
        return $count;
    }
}
