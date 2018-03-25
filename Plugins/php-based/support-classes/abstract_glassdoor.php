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
 * Class AbstractGlassdoor
 *
 *       Used by glassdoor.json plugin configuration to override single method
 */
abstract class AbstractGlassdoor extends \JobScooper\BasePlugin\Classes\AjaxHtmlSimplePlugin
{
	/**
	 * AbstractGlassdoor constructor.
	 * @throws \Exception
	 */
	function __construct()
	{

		parent::__construct();
		$this->_flags_ &= ~C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED;

	}


	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 *
	 * @return string
	 * @throws \ErrorException
	 * @throws \Exception
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	function doFirstPageLoad(\JobScooper\DataAccess\UserSearchSiteRun $searchDetails)
	{
		LogMessage("Setting search location via JavaScript on load of first page...");
		$jsCode = /** @lang javascript */ <<<JSCODE
            fetch('https://www.glassdoor.com/findPopularLocationAjax.htm?term=" . $searchDetails->getGeoLocationURLValue() . "&maxLocationsToReturn=10').then(function(response) {
              if(response.ok) {
                response.json().then(function(json) {
                  if(json.length > 0)
                  {
                    console.log('query returned ' + json[0]['longName']);
                    document.getElementById('sc.location').value = json[0]['longName'];
                    document.getElementById('HeroSearchButton').click();
                  }
                  else
                    console.log('No location was found.');
                })
            }
            else
                console.log('location query failed.');
        });
JSCODE;

		$this->selenium->getPageHTML($searchDetails->getSearchStartUrl());

		$retval = $this->runJavaScriptSnippet($jsCode, false);
		LogDebug("Found and set location '{$retval}'");
		sleep($this->additionalLoadDelaySeconds + 2);

		$html = $this->getActiveWebdriver()->getPageSource();

		return $html;

	}


}
