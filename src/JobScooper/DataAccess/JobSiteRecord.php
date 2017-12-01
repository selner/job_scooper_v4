<?php

namespace JobScooper\DataAccess;

use \Exception;
use JobScooper\DataAccess\Base\JobSiteRecord as BaseJobSiteRecord;
use Propel\Runtime\Connection\ConnectionInterface;

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
    private $_pluginObject = null;

    function getPluginObject()
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
                return null;
            }
        }

        return $this->_pluginObject;
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
                $this->_pluginObject = null;
            }

            $this->_pluginObject = new $class();
        }
        catch (\Exception $ex)
        {
            LogError("Error instantiating jobsite plugin object" . $this->getJobSiteKey() . " with class name [" . $this->getPluginClassName() ."]:  ". $ex->getMessage());
            $this->_pluginObject = null;
        }
    }

}
