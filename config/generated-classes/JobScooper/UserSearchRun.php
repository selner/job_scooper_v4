<?php

namespace JobScooper;

use JobScooper\Base\UserSearchRun as BaseUserSearchRun;

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

class SearchRunResult extends \ArrayObject
{
    function __construct()
    {
        $arrFields = Array(
            'success' => false,
            'error_datetime' => new \DateTime(),
            'error_details' => null,
            'exception_code' => null,
            'exception_message' => null,
            'exception_line' => null,
            'exception_file' => null,
            'error_files' => array()
        );
        return parent::__construct($arrFields, \ArrayObject::ARRAY_AS_PROPS);
    }
}

class UserSearchRun extends BaseUserSearchRun implements \ArrayAccess
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


    private $userObject = null;

    public function __construct($arrSearchDetails = null, $outputDirectory = null)
    {
        parent::__construct();
        if (is_null($this->getSearchRunResult())) {
            $this->setSearchRunResult(new SearchRunResult());
        }
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

