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

use JobScooper\DataAccess\Map\JobPostingTableMap;
use Propel\Runtime\Map\TableMap;
use Exception;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Connection\ConnectionInterface;
use \JobScooper\DataAccess\Base\JobPosting as BaseJobPosting;

/**
 * Class JobPosting
 * @package JobScooper\DataAccess
 */
class JobPosting extends BaseJobPosting implements \ArrayAccess
{
    private $_searchLocId = null;


    /**
     * @param $geoLocId
     */
    public function setSearchLocation($geoLocId)
    {
        $this->_searchLocId = $geoLocId;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return int
     * @throws \Exception
     */
    public function save(ConnectionInterface $con = null)
    {
        try {
            return parent::save($con);
        } catch (PropelException $ex) {
            handleException($ex, "Failed to save JobPosting: %s", true);
        }
    }

    /**
     * @param bool $includeGeolocation
     * @param array $limitToKeys
     *
     * @return array
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function toFlatArrayForCSV($includeGeolocation = false, $limitToKeys=null)
    {
        $location = array();
        $arrJobPosting = $this->toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false);
        updateColumnsForCSVFlatArray($arrJobPosting, new JobPostingTableMap());
        if ($includeGeolocation === true) {
            $jobloc = $this->getGeoLocationFromJP();
            if (null !== $jobloc) {
                $location = $jobloc->toFlatArrayForCSV();
            }

            $arrItem = array_merge_recursive_distinct($arrJobPosting, $location);
        } else {
            $arrItem = $arrJobPosting;
        }

        if (!empty($limitToKeys) && is_array($limitToKeys)) {
            return array_subset_keys($arrItem, $limitToKeys);
        }

        return $arrItem;
    }

    protected function getTitleTokenArray() {
        $title_toks = $this->getTitleTokens();
        if(!is_empty_value($title_toks)) {
            $toks = explode(DATABASE_STRING_ARRAY_SEPARATOR, $title_toks );
            if(!is_empty_value($toks)) {
                return $toks;
            }
        }

        return null;
    }

    protected function updateKeyCompanyTitle() {

        $title_portion = "";
        $title_toks = $this->getTitleTokenArray();
        if(!is_empty_value($title_toks)) {
            $title_portion = implode("_", $title_toks );
        }
        else {
            $title_portion = strScrub($this->getTitle(), FOR_LOOKUP_VALUE_MATCHING);
        }
        $title_portion = strtolower($title_portion);

        $company_portion = strScrub($this->getCompany(), FOR_LOOKUP_VALUE_MATCHING);
        $title_portion = strtolower($company_portion);

        $this->setKeyCompanyAndTitle("{$company_portion}~{$title_portion}");
    }
    /**
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function updateAutoColumns()
    {
        //
        // Null out fields that are derived from Title and Company so that
        // later processors in JobNormalizer know to reset the value to a
        // new one
        //
        if($this->wasTitleModified() === true) {
            $this->setTitleTokens(null);
            $this->updateKeyCompanyTitle();
        }

        if($this->wasCompanyModified() === true) {
            $this->updateKeyCompanyTitle();
        }

        if(is_empty_value($this->getKeyCompanyAndTitle()))
        {
            $this->updateKeyCompanyTitle();
        }

        //
        // If location was changed somehow, we need to
        // re-denormalize the related facts back into this JobPosting record
        //
        if($this->wasLocationModified()) {
            $this->_updateAutoLocationColumns();
        }
    }

    /**
     * @param $method
     * @param $v
     * @throws \Propel\Runtime\Exception\PropelException
     * @return mixed
     */
    public function setAutoColumnRelatedProperty($method, $v)
    {
        if (null === $v || strlen($v) <= 0) {
            $v = "_VALUENOTSET_";
        }
        $ret = parent::$method($v);
        $this->updateAutoColumns();

        return $ret;
    }

    /**
     * @return array
     * @throws PropelException
     */
    public function getModifiedColumnsPhpNames()
    {
        $cols = parent::getModifiedColumns();
        if (!is_empty_value($cols)) {
            $phpCols = array();
            foreach ($cols as $col) {
                $phpCols[$col]=JobPostingTableMap::translateFieldName($col, TableMap::TYPE_COLNAME, TableMap::TYPE_PHPNAME);
            }
            return $phpCols;
        }
    }

    /**
     * @param $arrColsToCheck
     * @return bool
     * @throws PropelException
     */
    private function wasColumnModified($arrColsToCheck)
    {
        $colsChanged = $this->getModifiedColumnsPhpNames();
        $colsOverlap = array_intersect_assoc($colsChanged, $arrColsToCheck);
        return !is_empty_value($colsOverlap);

    }

    /**
     * @return bool
     * @throws PropelException
     */
    private function wasLocationModified() {
        return $this->wasColumnModified(['GeoLocation', 'Location']);
    }

    /**
     * @return bool
     * @throws PropelException
     */
    private function wasTitleModified() {
        return $this->wasColumnModified(['Title']);
    }

    /**
     * @return bool
     * @throws PropelException
     */
    private function wasCompanyModified() {
        return $this->wasColumnModified(['Company']);
    }

    /**
     *
     * @throws \Exception
     */
    public function normalizeJobRecord()
    {
        $fields = $this->getModifiedColumnsPhpNames();
        if (!is_empty_value($fields)) {
            foreach ($fields as $phpField) {
            	if(in_array($phpField, ['JobSitePostId', 'Url', 'KeyCompanyAndTitle'])) {
            		continue;
            	}
                $getFunc = "get{$phpField}";
                $setFunc = "set{$phpField}";
                $val = $this->$getFunc;
                if (!is_empty_value($val) && is_string($val)) {
                    $cleanVal = $this->_cleanupTextValue($val);
                    call_user_func(array($this, $setFunc), $cleanVal);
                }
            }

            $this->updateAutoColumns();
            $this->setJobSiteKey(cleanupSlugPart($this->getJobSiteKey()));
        }
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @throws \Exception
     * @return bool
     */
    public function preSave(\Propel\Runtime\Connection\ConnectionInterface $con = null)
    {
        $this->normalizeJobRecord();

        if (is_callable('parent::preSave')) {
            return parent::preSave($con);
        }

        return true;
    }

    /**
     * @param     $v
     * @param int $maxLength
     *
     * @return bool|null|string|string[]
     */
    private function _cleanupTextValue($v, $maxLength=255, $prefix=null, $postfix=null)
    {
        $ret = cleanupTextValue($v, $prefix, $postfix);
        if (!empty($ret) && is_string($ret)) {
            $ret = substr($ret, 0, min($maxLength-1, strlen($ret)));
        }

        return $ret;
    }



    /**
     * @param string $v
     * @throws Exception
     *
     * @return $this|\JobScooper\DataAccess\JobPosting|void
     */
    public function setLocation($v)
    {
        $oldVal = $this->getLocation();

        // clear any previous location mapping to geolocation
        // when we set a new location string.  It will get set
        // again later during data normalization
        //
        $this->setGeoLocationFromJP(null);
        $this->setGeoLocationId(null);

        $v = $this->_cleanupTextValue($v, 255);

        //
        // Restructure locations like "US-VA-Richmond" to be "Richmond, VA"
        //
        $arrMatches = preg_split("/[\-]/", $v);
        if (strlen($v) == 3) {
            $v = sprintf("%s %s %s", $arrMatches[2], $arrMatches[1], $arrMatches[0]);
        }

        parent::setLocation(trim($v));

        $dv = null;
        if (!is_empty_value($v)) {
            $dv = $this->_cleanupTextValue($v);
        }

        $this->setLocationDisplayValue($dv);
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     */
    private function _updateAutoLocationColumns()
    {
        if (true !== $this->wasLocationModified()) {
            return;
        }

        $loc_str = $this->getLocation();
        if (is_empty_value($loc_str)) {
            // clear any previous job location ID when we set a new location string
            $this->setGeoLocationFromJP(null);
            $this->setGeoLocationId(null);
            $this->setLocationDisplayValue(null);

            return;
        }
    }

    /**
     * @return null|string
     */
    public function getKeySiteAndPostId()
    {
        if (!empty($this->getJobSiteKey()) && !empty($this->getJobSitePostId())) {
            return cleanupSlugPart(sprintf("%s_%s", $this->getJobSiteKey(), $this->getJobSitePostId()), $replacement = '_', $fDoNotLowercase=false);
        }

        return null;
    }


    /**
     * JobPosting constructor.
     *
     * @param null $arrJobFacts
     *
     * @throws \Exception
     */
    public function __construct($arrJobFacts = null)
    {
        parent::__construct();
        if (!is_empty_value($arrJobFacts) && \count($arrJobFacts) > 1) {
            foreach (array_keys($arrJobFacts) as $key) {
                $this->set($key, $arrJobFacts[$key]);
            }
            $this->save();
        }
    }

    /**
     * @param string $keyType
     * @param bool   $includeLazyLoadColumns
     * @param array  $alreadyDumpedObjects
     * @param bool   $includeForeignObjects
     *
     * @return array
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        $ret = parent::toArray($keyType, $includeLazyLoadColumns, $alreadyDumpedObjects, $includeForeignObjects);
        if (!is_empty_value($ret) && is_array($ret)) {
            $ret['KeySiteAndPostId'] = $this->getKeySiteAndPostId();
        }

        return $ret;
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
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
