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

use JobScooper\Base\UserJobMatch as BaseUserJobMatch;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Map\TableMap;


/**
 * Skeleton subclass for representing a row from the 'user_job_match' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class UserJobMatch extends BaseUserJobMatch
{
    public function __construct()
    {
        $this->setAppRunId($GLOBALS['USERDATA']['configuration_settings']['app_run_id']);
        parent::__construct();
    }

    private $delim = ' | ';

    private function _setMatchStatus()
    {
        if(count($this->getMatchedUserKeywords()) > 0 )
            $this->setIsJobMatch(true);

        if(count($this->getMatchedNegativeTitleKeywords()) > 0 )
            $this->setIsExcluded(true);

        if(count($this->getMatchedNegativeCompanyKeywords()) > 0 )
            $this->setIsExcluded(true);

        if($this->isOutOfUserArea() === true)
            $this->setIsExcluded(true);

//        if($this->getIsJobMatch() === true)
            $this->setIsIncludeInNotifications(true);
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        $this->_setMatchStatus();

        if (is_callable('parent::preSave')) {
            return parent::preSave($con);
        }
        return true;
    }

    public function toFlatArray()
    {
        $arrUserJobMatch = $this->toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false);
        $arrItem = array_merge_recursive_distinct($arrUserJobMatch, $this->getJobPosting()->toFlatArray());

        foreach(array_keys($arrItem) as $key)
            if(is_array($arrItem[$key]))
                $arrItem[$key] = join("|", flattenWithKeys(array($key => $arrItem[$key])));

        return $arrItem;

    }


}
