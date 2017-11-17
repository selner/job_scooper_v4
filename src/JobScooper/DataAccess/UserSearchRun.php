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


use JobScooper\DataAccess\Base\UserSearchRun as BaseUserSearchRun;
use JobScooper\DataAccess\Map\UserSearchRunTableMap;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Map\TableMap;
use Psr\Log\InvalidArgumentException;
use JBZoo\Utils\Arr;


class UserSearchRun extends BaseUserSearchRun
{
    protected $userObject = null;

    public function __construct($arrSearchDetails = null, $outputDirectory = null)
    {
        parent::__construct();

        $this->setAppRunId($GLOBALS['USERDATA']['configuration_settings']['app_run_id']);

        $this->userObject = $GLOBALS['USERDATA']['configuration_settings']['user_details'];
        $this->setUserSlug($this->userObject->getUserSlug());

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

    function failRunWithException($ex)
    {
        $line = null;
        $code = null;
        $msg = null;
        $file = null;

        if (!is_null($ex)) {
            $line = $ex->getLine();
            $code = $ex->getCode();
            $msg = $ex->getMessage();
            $file = $ex->getFile();
            $errexc = array(
                'error_details' => strval($ex),
                'exception_code' => $code,
                'exception_message' => $msg,
                'exception_line' => $line,
                'exception_file' => $file,
                'error_datetime' => new \DateTime()
            );
            $this->failRunWithErrorMessage($errexc);
        }
    }
    function failRunWithErrorMessage($err)
    {
        $arrV = object_to_array($err);
        $this->setRunResultCode("failed");
        parent::setRunErrorDetails($arrV);
    }

    function setRunSucceeded()
    {
        return $this->setRunResultCode('successful');
    }

    function setRunResultCode($val)
    {
        switch ($val) {
            case "failed":
                $this->setLastFailedAt(time());
                break;

            case 'successful':
                $this->_updateNextRunDate_();
                $this->setLastFailedAt(null);
                $this->setRunErrorDetails(array());
                break;

            case "skipped":
                break;

            case "not-run":
            case "excluded":
            default:
                break;
        }

        return parent::setRunResultCode($val);
    }

    public function setGeoLocationId($v)
    {
        parent::setGeoLocationId($v);
        $this->setUserSearchRunKey($this->createSlug());
    }

    public function setSearchKey($v)
    {
        parent::setSearchKey($v);
        $this->setUserSearchRunKey($this->createSlug());
    }

    public function setJobSiteKey($v)
    {
        $slug = cleanupSlugPart($v);
        parent::setJobSiteKey($slug);
        $this->setUserSearchRunKey($this->createSlug());
    }

    public function shouldRunNow()
    {
        $nextTime = $this->getStartNextRunAfter();
        if (!is_null($nextTime))
            return (time() > $nextTime->getTimestamp());

        return true;
    }

    public function getSearchParameters()
    {
        $paramdata = $this->getSearchParametersData();
        $params = decodeJSON($paramdata);
        return ($params === false ? null : $params);
    }


    public function getSearchParameter($param_key)
    {
        $params = $this->getSearchParameters();
        if(array_key_exists($param_key, $params))
            return $params[$param_key];

        return null;
    }

    public function setSearchParameters($objParams)
    {
        $arrParams = object_to_array($objParams);
        if(is_null($arrParams))
            throw new InvalidArgumentException("Data passed to setSearchParameters was not a valid parameters object.");

        $paramdata = encodeJSON($arrParams);
        $this->setSearchParametersData($paramdata);
    }

    public function setSearchParameter($param_key, $value)
    {
        $params = $this->getSearchParameters();
        $params[$param_key] = $value;
        $this->setSearchParameters($params);
    }

    protected function createSlug()
    {
        // create the slug based on the `slug_pattern` and the object properties
        $slug = $this->createRawSlug();
        // truncate the slug to accommodate the size of the slug column
        $slug = $this->limitSlugSize($slug);
//        // add an incremental index to make sure the slug is unique
//        $slug = $this->makeSlugUnique($slug);

        return $slug;
    }

    private function _updateNextRunDate_()
    {
        if (!is_null($this->getLastRunAt())) {
            $nextDate = $this->getLastRunAt();
            if (is_null($nextDate))
                $nextDate = new \DateTime();
            date_add($nextDate, date_interval_create_from_date_string('18 hours'));

            $this->setStartNextRunAfter($nextDate);
        }
    }



    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {

        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID)) {
            $this->setUserSearchRunId(null);
        }

        $this->setUserSearchRunKey($this->createSlug());


        if (is_callable('parent::preSave')) {
            return parent::preSave($con);
        }
        return true;

    }

    private $_targeting = null;

    public function getSearchLocationTargeting()
    {
        if(!is_null($this->_targeting))
            return $this->_targeting;

        $loc = $this->getGeoLocation();
        if(!is_null($loc))
        {
            $this->_targeting = array();
            $state = $loc->getState();
            if(!is_null($state))
                $this->_targeting['state'] = $state;
        }
        return $this->_targeting;
    }

    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {

        $params = $this->getSearchParameters();
        $selfArray = parent::toArray($keyType, $includeLazyLoadColumns, $alreadyDumpedObjects, $includeForeignObjects);

        return array_merge_recursive_distinct($selfArray, Arr::flat($params));
    }



}
