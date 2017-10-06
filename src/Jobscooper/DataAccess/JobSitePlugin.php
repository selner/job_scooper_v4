<?php
/**
 * Copyright 2014-17 Bryan Selner
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


namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\JobSitePlugin as BaseJobSitePlugin;

/**
 * Skeleton subclass for representing a row from the 'jobsite_plugin' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class JobSitePlugin extends BaseJobSitePlugin
{

    private $_pluginObject = null;




    function getJobSitePluginObject()
    {
        if(is_null($this->_pluginObject))
            $this->_instantiateJobSiteClass();

        return $this->_pluginObject;
    }

    function getPluginClassName()
    {
        return getJobSitePluginClassName($this->getJobSiteKey());
    }

    function isSearchIncludedInRun()
    {
        if(array_key_exists($this->getJobSiteKey(), $GLOBALS['USERDATA']['configuration_settings']['excluded_sites']))
        {
            return false;
        }
        else
        {
            $strIncludeKey = 'include_' . $this->getJobSiteKey();

            $valInclude = get_PharseOptionValue($strIncludeKey);

            if (!isset($valInclude) || $valInclude == 0) {
                setSiteAsExcluded($this->getJobSiteKey());
                return false;
            }
        }

        return true;
    }


    private function _instantiateJobSiteClass()
    {
        if (is_null($this->getPluginClassName()))
            throw new \Exception("Missing jobsite plugin class name.");

        try {
            $class = $this->getPluginClassName();
            $this->save();
            $this->_pluginObject = new $class();

            $this->setSupportedCountryCodes($this->_pluginObject->getSupportedCountryCodes());

        }
        catch (\Exception $ex)
        {
            handleException($ex, "Error instantiating jobsite plugin object" . $this->getJobSiteKey() . " with class name [" . $this->getPluginClassName() ."]:  %s");
        }
    }

    private function _updateAutoColumns()
    {
        $classname = $this->getPluginClassName();
        $this->setPluginClassName($classname);

    }

    public function preSave(\Propel\Runtime\Connection\ConnectionInterface $con = null)
    {
        $this->_updateAutoColumns();
        return parent::preSave($con);
    }

    protected function doInsert(\Propel\Runtime\Connection\ConnectionInterface $con)
    {
        assert(strlen($this->getPluginClassName()) > 0 && "Plugin is missing class name!" );
        LogLine("Inserting new JobSitePlugin record: " . $this->getPluginClassName());
        parent::doInsert($con);

    }


}
