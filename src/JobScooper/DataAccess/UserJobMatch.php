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

const LIST_SEPARATOR_TOKEN = '_||_';

class UserJobMatch extends BaseUserJobMatch
{
    private function _cleanupKeywordListValue($v) {
        if (is_array($v)) {
            $v = join(LIST_SEPARATOR_TOKEN, $v);
        }

        $v = cleanupTextValue($v);

        return $v;
    }

    private function _convertKeywordListToArray($v) {
        if (is_string($v) && !is_empty_value($v)) {
            $v = explode(LIST_SEPARATOR_TOKEN, $v);
        }

        return $v;
    }

    public function getGoodJobTitleKeywordMatches()
    {
        $v = parent::getGoodJobTitleKeywordMatches();
        return $this->_convertKeywordListToArray($v);
    }


    public function setGoodJobTitleKeywordMatches($v)
    {
        $v = $this->_cleanupKeywordListValue($v);

        return parent::setGoodJobTitleKeywordMatches($v);
    }

    public function getBadJobTitleKeywordMatches()
    {
        $v = parent::getBadJobTitleKeywordMatches();
        return $this->_convertKeywordListToArray($v);
    }

    public function setBadJobTitleKeywordMatches($v)
    {
        $v = $this->_cleanupKeywordListValue($v);

        return parent::setBadJobTitleKeywordMatches($v);
    }

    public function getBadCompanyNameKeywordMatches()
    {
        $v = parent::getBadCompanyNameKeywordMatches();
        return $this->_convertKeywordListToArray($v);
    }

    public function setBadCompanyNameKeywordMatches($v)
    {
        $v = $this->_cleanupKeywordListValue($v);

        return parent::setBadCompanyNameKeywordMatches($v);
    }

    public function autoUpdateIsJobMatch() {

        if(!is_empty_value($this->getGoodJobTitleKeywordMatches())) {
            $this->setIsJobMatch(true);
        }
        else {
            $this->setIsJobMatch(false);
        }
    }

    /**
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function preSave(ConnectionInterface $con = null)
    {
        $this->autoUpdateIsJobMatch();
        $this->autoUpdateIsExcluded();
        return parent::preSave($con);
    }

    /**
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function autoUpdateIsExcluded()
    {
        $this->setIsExcluded(false);

        if($this->getOutOfUserArea() === true) {
            $this->setIsExcluded(true);
        }

        if(!is_empty_value($bad_comp_matches = $this->getBadCompanyNameKeywordMatches())) {
            $this->setIsExcluded(true);
        }

        if(!is_empty_value($bad_title_matches = $this->getBadJobTitleKeywordMatches())) {
            $this->setIsExcluded(true);
        }

        $jp = $this->getJobPostingFromUJM();
        if (null !== $jp && !is_empty_value($jp->getDuplicatesJobPostingId())) {
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
}