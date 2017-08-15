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
//
// If installed as part of the package, uses Klogger v0.1 version (http://codefury.net/projects/klogger/)
//
require_once(dirname(dirname(__FILE__)) . "/bootstrap.php");

use Propel\Runtime\Connection\ConnectionInterface;

class JobPosting extends \JobScooper\Base\JobPosting
{
    protected function updateAutoColumns()
    {
        $this->setKeyCompanyAndTitle($this->getCompany() . $this->getTitle());
        $this->setKeySiteAndPostID($this->getJobSite() . $this->getJobSitePostID());
    }

    public function setAutoColumnRelatedProperty($method, $v)
    {
        if (is_null($v) || strlen($v) <= 0)
            $v = "_VALUENOTSET_";
        $ret = parent::$method($v);
        $this->updateAutoColumns();
        return $ret;
    }

    public function setCompany($v)
    {
        return $this->setAutoColumnRelatedProperty(__METHOD__, $v);
    }

    public function setJobsite($v)
    {
        return $this->setAutoColumnRelatedProperty(__METHOD__, $v);
    }

    public function setJobSitePostID($v)
    {
        return $this->setAutoColumnRelatedProperty(__METHOD__, $v);
    }

    public function setTitle($v)
    {
        return $this->setAutoColumnRelatedProperty(__METHOD__, $v);
    }


    public function preInsert(\Propel\Runtime\Connection\ConnectionInterface $con = null)
    {
        $this->updateAutoColumns();

        if (is_callable('parent::preInsert')) {
            return parent::preInsert($con);
        }
        return true;
    }


}


class SearchSettings extends ArrayObject
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
        parent::__construct($arrFields, ArrayObject::ARRAY_AS_PROPS);

        return $this;
    }
}

class SearchRunResult extends ArrayObject
{
    function __construct()
    {
        $arrFields = Array(
            'success' => false,
            'error_datetime' => new DateTime(),
            'error_details' => null,
            'exception_code' => null,
            'exception_message' => null,
            'exception_line' => null,
            'exception_file' => null,
            'error_files' => array()
        );
        return parent::__construct($arrFields, ArrayObject::ARRAY_AS_PROPS);
    }
}

class UserSearchRun extends \JobScooper\Base\UserSearchAudit implements ArrayAccess
{
    private $plugin = null;
    private $pluginClass = null;
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


    public function __construct($arrSearchDetails = null, $userId = null, $outputDirectory = null)
    {
        parent::__construct();
        if (is_null($this->getSearchRunResult())) {
            $this->setSearchRunResult(new SearchRunResult());
        }
        if ($this->getSearchSettings()) {
            $this->setSearchSettings(new SearchSettings());
        }

        $this->setRunDate(new DateTime('NOW'));
        if (is_null($userId))
            $this->setUserId($GLOBALS['USERDATA']['configuration_settings']['user_details']['UserId']);
        else
            $this->setUserId($userId);

        if (!is_null($arrSearchDetails) && is_array($arrSearchDetails) && count($arrSearchDetails) > 0) {
            $this->fromSearchDetailsArray($arrSearchDetails);
        }


//        if (!is_null($this->getJobSite()) && strlen($this->getJobSite()) > 0)
//        {
//            $pluginclass = $GLOBALS['JOBSITE_PLUGINS'][strtolower($this->getJobSite())];
//            if(!is_null($outputDirectory))
//            {
//                $pathDetails = \Scooper\getFullPathFromFileDetails($outputDirectory), $pluginclass);
//            }
//            $this->plugin = new $pluginclass['class_name']($outputDirectory);
//        }

    }


    function __destruct()
    {
    }

    private function _setPluginClass($outputDirectory = null)
    {
        if(is_null($this->getJobSite()))
            throw new Exception("Unable to look up plugin class because the job site is unknown.");

        $this->pluginClass = $GLOBALS['JOBSITE_PLUGINS'][$this->getJobSite()]['class_name'];
        $this->plugin = new $this->pluginClass($outputDirectory, null);
    }

    function getPlugin($outputDirectory = null)
    {
        if(is_null($this->plugin))
            $this->_setPluginClass($outputDirectory);

        return $this->plugin;
    }

    function getPluginClass()
    {
        if(is_null($this->plugin))
            $this->_setPluginClass();

        return $this->pluginClass;
    }

    function isSearchIncludedInRun()
    {
        $strIncludeKey = 'include_'.strtolower($this->getJobSite());
        $valInclude = \Scooper\get_PharseOptionValue($strIncludeKey);
        if(is_null($valInclude) || $valInclude == 0 || $valInclude === false)
        {
            return false;
        }

        return true;

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
                    $this->setJobSite($arrDetails[$keyOldName]);
                    $valueSet = true;
                    break;

                case 'search_run_result':
                    $this->setSearchRunResult($arrDetails[$keyOldName]);
                    $valueSet = true;
                    break;

                case in_array($keyOldName, $this->searchSettingKeys):
//                case 'search_start_url':
//                case 'keywords_string_for_url':
//                case 'base_url_format':
//                case 'location_user_specified_override':
//                case 'location_search_value':
//                case 'location_set_key':
//                case 'keyword_search_override':
//                case 'keywords_array':
//                case 'keywords_array_tokenized':
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
        if (is_null($this->getSearchRunResult())) {
            $this->setSearchRunResult(new SearchRunResult());
        }
        if ($this->getSearchSettings()) {
            $this->setSearchSettings(new SearchSettings());
        }

        foreach (array_keys($arrDetails) as $key) {
            $this->_setOldNameToNewColumn($key, $arrDetails);
        }
    }

    public function setSearchRunResult($v)
    {
        parent::setSearchRunResult($v);
        if(!is_null($this->getKey()))
            $this->save();
    }

    public function setJobSite($v)
    {
        parent::setJobSite($v);
        $this->_setPluginClass();

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
                return $this->setJobSite($value);
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
            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Error saving object " . __CLASS__ . " key " . $pk . ": " . $ex->getMessage() . PHP_EOL . "Class data = " . getArrayValuesAsString($this->toArray()), \Scooper\C__DISPLAY_ERROR__);
            throw $ex;
//            if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Skipping failed save of object " . __CLASS__ . " key " . $pk . ": " . $ex->getMessage(), \Scooper\C__DISPLAY_WARNING__);
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
                return $this->getJobSite();
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

