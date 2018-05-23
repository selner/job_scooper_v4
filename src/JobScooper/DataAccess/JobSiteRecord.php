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

use JobScooper\DataAccess\Base\JobSiteRecord as BaseJobSiteRecord;

/**
 * Skeleton subclass for representing a row from the 'job_site' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * magic methods:
 *
 * @method getSupportedCountryCodes() Gets the country codes from the JobSite plugin
 */
class JobSiteRecord extends BaseJobSiteRecord
{
    /**
     * @var \JobScooper\SitePlugins\Base\SitePlugin
     */
    private $_pluginObject = null;

    /**
	 * @return \JobScooper\SitePlugins\Base\SitePlugin
     * @throws \Exception
	*/
	public function getPlugin(){
	    if(null === $this->_pluginObject) {
            $this->instantiatePlugin();
	    }

	    return $this->_pluginObject;
	}

    /**
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    private function instantiatePlugin()
    {
        if (null !== $this->_pluginObject) {
            return;
        }

        $class = $this->getPluginClassName();
        try {
            if (is_empty_value($class)) {
            	$class = "Plugin{$this->getJobSiteKey()}";
	            if (!in_array($class, get_declared_classes())) {
	                throw new \InvalidArgumentException("Unable to find declared plugin class {$class} for {$this->getJobSiteKey()}.");
	            }
            }
            $this->_pluginObject = new $class();
        } catch (\Exception $ex) {
            LogError("Error instantiating jobsite {$this->getJobSiteKey()} plugin object by class [{$class}]:  {$ex->getMessage()}");
            $this->_pluginObject = null;
            unset($this->_pluginObject);
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
     *@throws \Exception
*/
    public function __call($method, $params)
    {
    	$obj = null;
        if (method_exists($this, $method)) {
            $obj = $this;
        } else {
            $plugin = $this->getPlugin();
            if (null !== $plugin && method_exists($plugin, $method)) {
            	$obj = $plugin;
	        }
        }

        if(null !== $obj) {
           return call_user_func_array(array($obj, $method), $params);
        }
        $class = self::class;
        LogError("{$method} not found for class {$class}.");
        return false;
    }
}
