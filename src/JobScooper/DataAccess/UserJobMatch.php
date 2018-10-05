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

use JobScooper\DataAccess\Base\UserJobMatch as BaseUserJobMatch;
use JobScooper\DataAccess\Map\UserJobMatchTableMap;
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
    private $delim = ' | ';


    /**
     * @param mixed $value
     * @return BaseUserJobMatch|UserJobMatch
     */
    public function addMatchedNegativeCompanyKeyword($value)
    {
        $this->setIsExcluded(true);
        return parent::addMatchedNegativeCompanyKeyword($value);
    }

    /**
     * @param mixed $value
     * @return BaseUserJobMatch|UserJobMatch
     */
    public function addMatchedNegativeTitleKeyword($value)
    {
        $this->setIsExcluded(true);
        return parent::addMatchedNegativeTitleKeyword($value);
    }

    /**
     * @param mixed $value
     * @return BaseUserJobMatch|UserJobMatch
     */
    public function addMatchedUserKeyword($value)
    {
        $this->isJobMatch(true);
        return parent::addMatchedUserKeyword($value);
    }

    /**
     * @param bool|int|string $v
     * @return BaseUserJobMatch|UserJobMatch
     */
    public function setOutOfUserArea($v)
    {
        if(filter_var($v, FILTER_VALIDATE_BOOLEAN) === true) {
            $this->setIsExcluded(true);
        }
        return parent::setOutOfUserArea($v);
    }

    public function autoUpdateIsJobMatch() {
        $usrKwdMatches = $this->getMatchedUserKeywords();

        if (is_empty_value($usrKwdMatches)) {
            $this->setIsJobMatch(false);
        } elseif(is_array($usrKwdMatches) &&  strlen(trim(join("", $usrKwdMatches))) > 0) {
            $this->setIsJobMatch(true);
        }
        else {
            $this->setIsJobMatch(false);
        }
    }

    public function preSave(ConnectionInterface $con = null)
    {
        $this->autoUpdateIsJobMatch();
        return parent::preSave($con);
    }

    /**
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function recalcUserMatchStatus()
    {
        $this->setIsExcluded(false);

        $this->autoUpdateIsJobMatch();

        if (!is_empty_value($this->getMatchedNegativeTitleKeywords())) {
            $this->setIsExcluded(true);
        }

        if (!is_empty_value($this->getMatchedNegativeCompanyKeywords()) > 0) {
            $this->setIsExcluded(true);
        }

        if(filter_var($this->isOutOfUserArea(), FILTER_VALIDATE_BOOLEAN) === true) {
            $this->setIsExcluded(true);
        }

        $jp = $this->getJobPostingFromUJM();
        if (null !== $jp && !is_empty_value($jp->getDuplicatesJobPostingId()) &&
            filter_var($jp->getDuplicatesJobPostingId(), FILTER_VALIDATE_BOOLEAN) === true) {
            $this->setIsExcluded(true);
        }
    }

    /**
     * @param null $limitToKeys
     *
     * @return array
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
     */
    public function toFlatArrayForCSV($limitToKeys=null)
    {
        $jobPost = $this->getJobPostingFromUJM();
        if (null === $jobPost && $this->isNew()) {
            $jobPost = new JobPosting();
        }
        $arrJobPost = $jobPost->toFlatArrayForCSV();

        $arrUserJobMatch = $this->toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false);
        foreach ($arrUserJobMatch as $k => $v) {
            if (is_array($v)) {
                $arrUserJobMatch[$k] = implode("|", $v);
            }
        }
        updateColumnsForCSVFlatArray($arrUserJobMatch, new UserJobMatchTableMap());

        $arrItem = array_merge_recursive_distinct($arrJobPost, $arrUserJobMatch);

        if (!is_empty_value($limitToKeys) && is_array($limitToKeys)) {
            return array_subset_keys($arrItem, $limitToKeys);
        }

        return $arrItem;
    }

    /**
     * @param mixed $value
     * @return BaseUserJobMatch|UserJobMatch
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function removeMatchedNegativeTitleKeyword($value)
    {
        $ret = parent::removeMatchedNegativeTitleKeyword($value);
        $this->recalcUserMatchStatus();
        return $ret;
    }

    /**
     * @param mixed $value
     * @return BaseUserJobMatch|UserJobMatch
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function removeMatchedUserKeyword($value)
    {
        $ret = parent::removeMatchedUserKeyword($value);
        $this->recalcUserMatchStatus();
        return $ret;
    }

    /**
     * @param mixed $value
     * @return BaseUserJobMatch|UserJobMatch
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function removeMatchedNegativeCompanyKeyword($value)
    {
        $ret = parent::removeMatchedNegativeCompanyKeyword($value);
        $this->recalcUserMatchStatus();
        return $ret;
    }

    /**
     * @param array $v
     * @return BaseUserJobMatch|UserJobMatch|mixed
     */
    public function setMatchedNegativeTitleKeywords($v)
    {
        return $this->setArray($v, "MatchedNegativeTitleKeywords");
    }

    /**
     * @param array $v
     * @return BaseUserJobMatch|UserJobMatch|mixed
     */
    public function setMatchedNegativeCompanyKeywords($v)
    {
        return $this->setArray($v, "MatchedNegativeCompanyKeywords");
    }


    /**
     * @param array $v
     * @return BaseUserJobMatch|UserJobMatch|mixed
     */
    public function setMatchedUserKeywords($v)
    {
        return $this->setArray($v, "MatchedUserKeywords");
    }

    /**
     * @param $v
     * @param $fact
     * @return $this|mixed
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setArray($v, $fact)
    {
        $factSet = "get{$fact}";
        $orig = call_user_func(array($this, $factSet));

        if($orig ===$v)
            return $this;

        if (!is_empty_value($v) && is_array($v)) {
            foreach ($v as &$item) {
                if (is_array($item)) {
                    $item = implode(" ", $item);
                }
            }
        } elseif (is_string($v) && !is_empty_value($v)) {
            $v = array($v);
        } elseif (is_empty_value($v)) {
            $v = array();
            $this->recalcUserMatchStatus();
        }

        $factSet = "parent::set{$fact}";
        return call_user_func(array($this, $factSet), $v);

    }

}