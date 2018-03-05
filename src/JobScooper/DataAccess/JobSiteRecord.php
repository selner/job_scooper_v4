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

namespace JobScooper\DataAccess;

use \Exception;
use JobScooper\DataAccess\Base\JobSiteRecord as BaseJobSiteRecord;
use JobScooper\BasePlugin\Classes\BaseJobsSite;

/**
 * Skeleton subclass for representing a row from the 'job_site' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class JobSiteRecord extends BaseJobSiteRecord
{
	/**
	 * @var \JobScooper\BasePlugin\Classes\BaseJobsSite
	 */
	private $_pluginObject = null;


	/**
	 * @return \JobScooper\BasePlugin\Classes\BaseJobsSite|null
	 * @throws \Exception
	 */
	function getPlugin()
    {
	    if (is_null($this->getPluginClassName()))
		    throw new \Exception("Missing jobsite plugin class name for " . $this->getJobSiteKey());

	    if (!is_null($this->_pluginObject))
	        return $this->_pluginObject;
	    try {
		    $class = $this->getPluginClassName();
//			    if (!in_array($class, get_declared_classes())) {
//				    LogWarning("Unable to find declared class " . $this->getPluginClassName() . "] for plugin " . $this->getJobSiteKey() .".  Disabling JobSite for future runs.");
//				    $this->_pluginObject = null;
//				    $this->setisDisabled(true);
//				    $this->save();
//			    }
//			    else
//			    {
		    if(empty($class))
		        LogWarning("Unable to find plugin class name for {$this->getJobSiteKey()}.");
		    else
		    {
			    $this->_pluginObject = new $class();

			    setCacheItem("all_jobsites_and_plugins", $this->getJobSiteKey(), $this);
		    }

	    } catch (\Exception $ex) {
		    LogError("Error instantiating jobsite plugin object" . $this->getJobSiteKey() . " with class name [" . $this->getPluginClassName() . "]:  " . $ex->getMessage());
		    unset($this->_pluginObject);
	    }
	    finally
	    {
		    return $this->_pluginObject;
	    }
    }


	/**
	 * Derived method to catches calls to undefined methods.
	 *
	 *
	 * @param string $name
	 * @param mixed  $params
	 *
	 * @return array|string
	 */
	public function __call($method, $params)
	{
		if (method_exists($this, $method)) {
			return call_user_func(
				array($this, $method),
					$params
			);
		}
		else {
			$plugin = $this->getPlugin();
			if (!empty($plugin) &&
				method_exists($plugin, $method))
			{
				return call_user_func_array(
					array($plugin, $method),
					$params
				);
			}
		}
		return false;
	}

}
