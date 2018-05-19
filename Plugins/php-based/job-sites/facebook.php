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

class AbstractFacebook  extends \JobScooper\BasePlugin\Classes\AjaxHtmlSimplePlugin
{

	/**
	 * @param \JobScooper\Utils\SimpleHTMLHelper $objSimpHTML
	 *
	 * @return array|null
	 * @throws \Exception
	 */
	function parseJobsListForPage(\JobScooper\Utils\SimpleHTMLHelper $objSimpHTML)
	{
		$ret = parent::parseJobsListForPage($objSimpHTML);

		if ($ret === null)
			throw new Exception("Unable to parse any jobs from Facebook site.");

		foreach($ret as $k => $job)
		{
			if(array_key_exists("Location", $job))
				if (count($job["Location"]) == 1)
				{
					$ret[$k]["Location"] = $job["Location"][0];
				}
				elseif(count($job["Location"]) > 1)
				{
					$jobSiteId = $job["JobSitePostId"];
					foreach($job["Location"] as $loc)
					{
						$newJob = array_copy($job);
						$newJob["Location"] = $loc;
						$newJob["JobSitePostId"] = "{$jobSiteId}-{$loc}";
						$ret[] = $newJob;
					}
					unset($ret[$k]);
				}
		}

		return $ret;
	}

	/**
	 * @param $var
	 *
	 * @return array[]|false|string[]
	 */
	function getLocations($var)
	{
		$delim = "~";
		$strLocations = combineTextAllNodes($var, $delim);
		$arrLocations = preg_split("/{$delim}/", $strLocations);
		return $arrLocations;
	}
}
