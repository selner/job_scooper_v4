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

use \Exception;
use JobScooper\DataAccess\Base\JobSitePlugin as BaseJobSitePlugin;
use JobScooper\DataAccess\Map\JobSitePluginTableMap;

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



    function doesSiteReturnAllJobs($ignoreLocation=false)
    {
        switch($this->getResultsFilterType())
        {
            case "all-only":
                return true;
                break;

            case "all-by-location":
                if($ignoreLocation)
                    return true;
                return false;
                break;

            default:
                return false;
        }

        return false;
    }

    function getJobSitePluginObject()
    {
        if (is_null($this->_pluginObject))
        {
            try
            {
                $this->instantiateJobSiteClass();
            }
            catch (Exception $ex)
            {
                handleException($ex, "Unable to instantiate expected Job Site plugin class for " . $this->getJobSiteKey() . ":  %s", false);
                setSiteAsExcluded($this->getJobSiteKey());
                $this->setLastFailedAt(time());
                $this->setLastRunWasSuccessful(false);
                return null;
            }
        }

        return $this->_pluginObject;
    }

    function getPluginClassName()
    {
        $class = parent::getPluginClassName();
        if(empty($class))
            $class = getJobSitePluginClassName($this->getJobSiteKey());

        return $class;
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


    function instantiateJobSiteClass()
    {
        if (is_null($this->getPluginClassName()))
            throw new \Exception("Missing jobsite plugin class name for " . $this->getJobSiteKey());

        try {
            $class = $this->getPluginClassName();
            if (!in_array($class, get_declared_classes()))
            {
                LogError("Unable to find declared class " . $this->getPluginClassName() ."] for plugin " . $this->getJobSiteKey());
                $this->setLastRunWasSuccessful(false);
                $this->setLastFailedAt(time());
                $this->setLastRunAt(time());
                $this->_updateAutoColumns();
                $this->_pluginObject = null;
            }

            $this->_pluginObject = new $class();

            $this->setSupportedCountryCodes($this->_pluginObject->getSupportedCountryCodes());
            $this->save();
        }
        catch (\Exception $ex)
        {
            LogError("Error instantiating jobsite plugin object" . $this->getJobSiteKey() . " with class name [" . $this->getPluginClassName() ."]:  ". $ex->getMessage());
            $this->_pluginObject = null;
        }
    }

    function setPluginClassName($v)
    {
        if(empty($this->getDisplayName()))
        {
            $class = $v;
            $name = str_replace("Plugin", "", $v);
            $this->setDisplayName($name);
        }
        return parent::setPluginClassName($v);
    }

    private function _updateAutoColumns()
    {
        if (empty(parent::getPluginClassName())) {
            $classname = $this->getPluginClassName();
            $this->setPluginClassName($classname);
        }

        if($this->isModified() && $this->isColumnModified(JobSitePluginTableMap::COL_WAS_SUCCESSFUL) &&
            $this->isLastRunWasSuccessful() == true)
        {
            $objJobSite = $this->getJobSitePluginObject();
            if(!empty($objJobSite))
            {
                if ($objJobSite->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
                    if ($objJobSite->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED))
                        $this->setResultsFilterType("all-by-location");
                    else
                        $this->setResultsFilterType("all-only");
                }
            }
        }

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
