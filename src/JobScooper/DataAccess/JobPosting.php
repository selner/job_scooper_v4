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
use Propel\Runtime\Propel;

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
        $company_portion = strtolower($company_portion);

        $keyval = cleanupTextValue("{$company_portion}~{$title_portion}");
        $this->setKeyCompanyAndTitle($keyval);
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
        $this->setJobSiteKey(cleanupSlugPart($this->getJobSiteKey()));

        $fields = $this->getModifiedColumnsPhpNames();
        if (!is_empty_value($fields)) {
            foreach ($fields as $phpField) {
            	if(in_array($phpField, ['JobSitePostId', 'Url'])) {
            		continue;
            	}
                $getFunc = "get{$phpField}";
                $setFunc = "set{$phpField}";
                $max = null;
                $tblmap = Propel::getServiceContainer()->getDatabaseMap(JobPostingTableMap::DATABASE_NAME)->getTable(JobPostingTableMap::TABLE_NAME);
                $colinfo = $tblmap->findColumnByName($phpField);
                if($colinfo != null) {
                    $max = $colinfo->getSize();
                }
                $val = call_user_func(array($this, $getFunc), null);
                if (!is_empty_value($val) && is_string($val)) {
                    $cleanVal = cleanupTextValue($val, null, null, $maxlength=$max);
                    call_user_func(array($this, $setFunc), $cleanVal);
                }
            }

            $this->updateAutoColumns();
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
     * @param string $v
     *
     * @return $this|\JobScooper\DataAccess\JobPosting|void
     * @throws \Exception
     */
    public function setTitle($v)
    {
        // Removes " NEW!", etc from the job title.  ZipRecruiter tends to occasionally
        // have that appended which then fails de-duplication. (Fixes issue #45) Glassdoor has "- easy apply" as well.
        $v = str_ireplace(" NEW!", "", $v);
        $v = str_ireplace("- new", "", $v);
        $v = str_ireplace("- easy apply", "", $v);
        $v = cleanupTextValue($v);

        if (is_empty_value($v)) {
            throw new \Exception($this->getJobSiteKey() . " posting's title string is empty.");
        }

        parent::setTitle($v);
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

        $v = cleanupTextValue($v, "(", ")", 255);

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
            $dv = cleanupTextValue($v);
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
     * @param string $v
     *
     * @return $this|\JobScooper\DataAccess\JobPosting|void
     */
    public function setCompany($v)
    {
        $v = cleanupTextValue($v, $maxLength=100);

        if (empty($v)) {
            return;
        }

        $v = strip_punctuation($v);

        // Remove common company name extensions like "Corporation" or "Inc." so we have
        // a higher match likelihood
        $v = preg_replace(array('/\s[Cc]orporat[e|ion]/', '/\s[Cc]orp\W{0,1}/', '/\.com/', '/\W{0,}\s[iI]nc/', '/\W{0,}\s[lL][lL][cC]/', '/\W{0,}\s[lL][tT][dD]/'), "", $v);

        switch (strScrub($v)) {
            case "amazon":
            case "amazon com":
            case "a2z":
            case "lab 126":
            case "amazon Web Services":
            case "amazon fulfillment services":
            case "amazonwebservices":
            case "amazon (seattle)":
                $v = "Amazon";
                break;

            case "market leader":
            case "market leader inc":
            case "market leader llc":
                $v = "Market Leader";
                break;


            case "walt disney parks &amp resorts online":
            case "walt disney parks resorts online":
            case "the walt disney studios":
            case "walt disney studios":
            case "the walt disney company corporate":
            case "the walt disney company":
            case "disney parks &amp resorts":
            case "disney parks resorts":
            case "walt disney parks resorts":
            case "walt disney parks &amp resorts":
            case "walt disney parks resorts careers":
            case "walt disney parks &amp resorts careers":
            case "disney":
                $v = "Disney";
                break;

        }
        parent::setCompany($v);
    }

    /**
     * @param string $v
     *
     * @return $this|\JobScooper\DataAccess\JobPosting|void
     */
    public function setDepartment($v)
    {
        $v = cleanupTextValue($v);
        parent::setDepartment($v);
    }

    /**
     * @param string $v
     *
     * @return $this|\JobScooper\DataAccess\JobPosting|void
     */
    public function setPayRange($v)
    {
        $v = cleanupTextValue($v, $maxLength=100);
        parent::setPayRange($v);
    }

    /**
     * @param string $v
     *
     * @return $this|\JobScooper\DataAccess\JobPosting|void
     */
    public function setEmploymentType($v)
    {
        $v = cleanupTextValue($v, $maxLength=100);
        parent::setEmploymentType($v);
    }

    /**
     * @param string $v
     *
     * @return $this|\JobScooper\DataAccess\JobPosting|void
     */
    public function setCategory($v)
    {
        $v = cleanupTextValue($v, $maxLength=100);
        parent::setCategory($v);
    }

    /**
     * @param mixed $v
     *
     * @return $this|\JobScooper\DataAccess\JobPosting|null
     */
    public function setPostedAt($v)
    {
        if (empty($v)) {
            return null;
        }

        $newV = null;

        if (strcasecmp($v, "Just posted") == 0) {
            $newV = getTodayAsString();
        }

        $v = strtolower(str_ireplace(array("Posted Date", "posted", "posted at"), "", $v));
        $v = cleanupTextValue($v);

        if (empty($newV)) {
            $dateVal = strtotime($v, $now = time());
            if (!($dateVal === false)) {
                $newV = $dateVal;
            }
        }

        if (empty($newV) && preg_match('/^\d+$/', $v)) {
            $vstr = (string) $v;
            if (strlen($vstr) == strlen("20170101")) {
                try {
                    $datestr = substr($vstr, 4, 2) . "/" . substr($vstr, 6, 2) . "/" . substr($vstr, 0, 4);
                    $dateVal = strtotime($datestr, $now = time());
                    if (!($dateVal === false)) {
                        $newV = $dateVal;
                    }
                } catch (Exception $ex) {
                    try {
                        $datestr = substr($vstr, 2, 2) . "/" . substr($vstr, 0, 2) . "/" . substr($vstr, 4, 4);
                        $dateVal = strtotime($datestr, $now = time());
                        if (!($dateVal === false)) {
                            $newV = $dateVal;
                        }
                    } catch (Exception $ex) {
                    }
                }
            }
        }

        if (empty($newV) && !empty($v)) {
            $info = date_parse($v);
            $date = "";
            foreach (array("month", "day", "year") as $dateval) {
                if ($info[$dateval] !== false) {
                    $date .= (string) $info[$dateval];
                } else {
                    $date .= (string) getdate()[$dateval];
                }
            }
            $newV = $date;
        }

        if (empty($newV)) {
            $newV = $v;
        }

        parent::setPostedAt($newV);
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
