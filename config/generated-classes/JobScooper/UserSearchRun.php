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

namespace JobScooper;
require_once dirname(dirname(dirname(dirname(__FILE__))))."/bootstrap.php";

use Exception as Exception;
use JobScooper\Base\UserSearchRun as BaseUserSearchRun;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Skeleton subclass for representing a row from the 'user_search_run' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class SearchSettings extends \ArrayObject
{
    function __construct()
    {
        $arrFields = Array(
            'key' => null,
            'site_name' => null,
            'search_start_url' => null,
            'keywords_string_for_url' => null,
            'base_url_format' => null,
            'location_user_specified_override' => null,
            'location_search_value' => VALUE_NOT_SUPPORTED,
            'keyword_search_override' => null,
            'keywords_array' => null,
        );
        parent::__construct($arrFields, \ArrayObject::ARRAY_AS_PROPS);

        return $this;
    }
}

class UserSearchRun extends BaseUserSearchRun implements \ArrayAccess
{
    private $searchSettingKeys = array(
        'search_start_url',
        'keywords_string_for_url',
        'base_url_format',
        'location_user_specified_override',
        'location_search_value',
        'location_set_key',
        'keyword_search_override',
        'keywords_array',
        'keywords_array_tokenized');


    private $userObject = null;

    public function __construct($arrSearchDetails = null, $outputDirectory = null)
    {
        parent::__construct();

        if ($this->getSearchSettings()) {
            $this->setSearchSettings(new SearchSettings());
        }
        $this->setAppRunId($GLOBALS['USERDATA']['configuration_settings']['app_run_id']);

        $this->userObject = $GLOBALS['USERDATA']['configuration_settings']['user_details'];
        $this->setUserSlug($this->userObject->getUserSlug());

        if (!is_null($arrSearchDetails) && is_array($arrSearchDetails) && count($arrSearchDetails) > 0) {
            $this->fromSearchDetailsArray($arrSearchDetails);
        }

    }

    private function _setOldNameToNewColumn($keyOldName, $arrDetails)
    {
        $valueSet = false;

        if (array_key_exists($keyOldName, $arrDetails) && !is_null($arrDetails[$keyOldName])) {
            switch ($keyOldName) {

                case 'key':
                    $this->setKey($arrDetails[$keyOldName]);
                    $valueSet = true;
                    break;

                case 'site_name':
                    $this->setJobSiteKey($arrDetails[$keyOldName]);
                    $valueSet = true;
                    break;

                case in_array($keyOldName, $this->searchSettingKeys):
                    $settings = $this->getSearchSettings();
                    $settings[$keyOldName] = $arrDetails[$keyOldName];
                    $this->setSearchSettings($settings);
                    $valueSet = true;
                    break;

                default:
                    break;
            }
        }

        return $valueSet;
    }

    public function fromSearchDetailsArray($arrDetails)
    {
        if ($this->getSearchSettings()) {
            $this->setSearchSettings(new SearchSettings());
        }

        foreach (array_keys($arrDetails) as $key) {
            $this->_setOldNameToNewColumn($key, $arrDetails);
        }
    }

    public function setRunResult($run_result, $errDetails=array())
    {
        $this->setRunResultCode($run_result);
        if(!is_null($errDetails) && is_array($errDetails))
        {
            $this->setRunErrorDetails($errDetails);
        }
    }

    public function setJobSiteKey($v)
    {
        $slug = cleanupSlugPart($v);
        findOrCreateJobSitePlugin($slug);
        parent::setJobSiteKey($slug);
    }

    public function getJobSitePluginObject()
    {
        return getPluginObjectForJobSite($this->getJobSiteKey());
    }

    public function getJobSiteObject()
    {
        $jobsiteObj = findOrCreateJobSitePlugin($this->getJobSiteKey());
        return $jobsiteObj;
    }

    function isSearchIncludedInRun()
    {
        return $this->getJobSiteObject()->isSearchIncludedInRun();
    }

    public function set($name, $value)
    {

        switch ($name) {
            case in_array($name, $this->searchSettingKeys):
                $settings = $this->getSearchSettings();
                $settings[$name] = $value;
                $this->setSearchSettings($settings);
                break;

            case 'site_name':
                return $this->setJobSiteKey($value);
                break;

            default:
                $throwEx = null;
                try {
                    $this->{$name} = $value;
                    $throwEx = null;
                } catch (Exception $ex) {
                    $throwEx = $ex;
                }

                try {
                    $this->setByName($name, \JobScooper\Map\UserSearchAuditTableMap::TYPE_FIELDNAME, $value);
                    $throwEx = null;
                } catch (Exception $ex) {
                    $throwEx = $ex;
                }

                try {
                    $this->setByName($name, \JobScooper\Map\UserSearchAuditTableMap::TYPE_COLNAME, $value);
                    $throwEx = null;
                } catch (Exception $ex) {
                    $throwEx = $ex;
                }

                try {
                    $this->setByName($name, \JobScooper\Map\UserSearchAuditTableMap::TYPE_CAMELNAME, $value);
                    $throwEx = null;
                } catch (Exception $ex) {
                    $throwEx = $ex;
                }

                if (!is_null($throwEx))
                    throw $throwEx;

                break;
        }

    }

    public function save(ConnectionInterface $con = null, $skipReload = false)
    {
        try
        {
            parent::save($con, $skipReload);
        }
        catch (Exception $ex)
        {
            try
            {
                $pk = $this->getPrimaryKey();
            }
            catch (Exception $exother)
            {
                $pk = "unknown-primary-key";
            }
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Error saving object " . __CLASS__ . " key " . $pk . ": " . $ex->getMessage() . PHP_EOL . "Class data = " . getArrayValuesAsString($this->toArray()), \C__DISPLAY_ERROR__);
            throw $ex;
//            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Skipping failed save of object " . __CLASS__ . " key " . $pk . ": " . $ex->getMessage(), \C__DISPLAY_WARNING__);
        }
    }

    public function &get($name)
    {

        switch ($name) {

            case in_array($name, $this->searchSettingKeys):
                $settings = $this->getSearchSettings();
                return $settings[$name];
                break;

            case 'site_name':
                return $this->getJobSiteKey();
                break;

            default:
                $throwEx = null;
                try {
                    return $this->{$name};
                } catch (Exception $ex) {
                    $throwEx = $ex;
                }

                try {
                    return $this->getByName($name, \JobScooper\Map\UserSearchAuditTableMap::TYPE_FIELDNAME);
                } catch (Exception $ex) {
                    $throwEx = $ex;
                }

                try {
                    return $this->getByName($name, \JobScooper\Map\UserSearchAuditTableMap::TYPE_COLNAME);
                } catch (Exception $ex) {
                    $throwEx = $ex;
                }

                break;
        }
    }


    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Whether or not an offset exists
     *
     * @param string An offset to check for
     * @access public
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset)
    {
        return null !== $this->get($offset);
    }

    /**
     * Unsets an offset
     *
     * @param string The offset to unset
     * @access public
     * @abstracting ArrayAccess
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            $this->set($offset, null);
        }
    }
}

