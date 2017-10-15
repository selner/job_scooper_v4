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

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\TableMap;
use Exception;

class JobPosting extends \JobScooper\DataAccess\Base\JobPosting implements \ArrayAccess
{

    public function toFlatArray()
    {
        $location = array();
        $arrJobPosting = $this->toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false);
        $jobloc = $this->getLocation();
        if(!is_null($jobloc))
            $location = $jobloc->toArray();
        $arrItem = array_merge_recursive_distinct($arrJobPosting, $location);

        foreach(array_keys($arrItem) as $key)
            if(is_array($arrItem[$key]))
                $arrItem[$key] = join("|", flattenWithKeys(array($key => $arrItem[$key])));

        return $arrItem;

    }
    public function checkAndMarkDuplicatePosting()
    {
        if(is_null($this->getDuplicatesJobPostingId())) {
            $this->updateAutoColumns();
            $sinceWhen = date_add(new \DateTime(), date_interval_create_from_date_string('7 days ago'));

            $masterPost = JobPostingQuery::create()
                ->filterByDuplicatesJobPostingId(null)
                ->filterByKeyCompanyAndTitle($this->getKeyCompanyAndTitle())
                ->filterByPostedAt(array('max' => $sinceWhen))
                ->filterByJobPostingId($this->getJobPostingId(), Criteria::NOT_EQUAL)
                ->orderByPostedAt('asc')
                ->findOne();

            if (!is_null($masterPost) && $masterPost !== false) {
                $this->setDuplicatesJobPostingId($masterPost->getJobPostingId());
                return $masterPost->getJobPostingId();
            }
        }
    }

    public function setPostingAsDuplicateOf($jobPosting)
    {

    }

    protected function updateAutoColumns()
    {
        $this->setKeyCompanyAndTitle(cleanupSlugPart($this->getCompany() . $this->getTitle()));
        $this->setKeySiteAndPostID(cleanupSlugPart($this->getJobSite() . $this->getJobSitePostID()));
    }

    public function setAutoColumnRelatedProperty($method, $v)
    {
        if (is_null($v) || strlen($v) <= 0)
            $v = "_VALUENOTSET_";
        $ret = parent::$method($v);
        $this->updateAutoColumns();
        return $ret;
    }

    private function _setDenormalizedLocationDisplayValue_()
    {
        $val = "";

        $location = $this->getLocation();
        if(!is_null($location))
        {
            $val = $location->getDisplayName();
        }

        $val = $this->_cleanupTextValue($val);
        if(is_null($val) || strlen($val) == 0)
            $val = $this->getLocationFromSource();

        $this->setLocationDisplayValue($val);
    }

    public function normalizeJobRecord()
    {
        $this->updateAutoColumns();
        $this->setJobSite(cleanupSlugPart($this->getJobSite()));

        $this->setJobTitleLinked('<a href="'.$this->getUrl().'" target="new">'.$this->getTitle().'</a>');


    }
    public function preSave(\Propel\Runtime\Connection\ConnectionInterface $con = null)
    {
        $this->normalizeJobRecord();

        if (is_callable('parent::preSave')) {
            return parent::preSave($con);
        }
        return true;
    }

    private function _cleanupTextValue($v)
    {
        $v = html_entity_decode($v);
        $v = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $v);
        $v = clean_utf8($v);
        $v = trim($v);

        return $v;
    }
    public function setTitle($v)
    {
        // Removes " NEW!", etc from the job title.  ZipRecruiter tends to occasionally
        // have that appended which then fails de-duplication. (Fixes issue #45) Glassdoor has "- easy apply" as well.
        $v = str_ireplace(" NEW!", "", $v);
        $v = str_ireplace("- new", "", $v);
        $v = str_ireplace("- easy apply", "", $v);
        $v = $this->_cleanupTextValue($v);
        parent::setTitle($v);
    }

    public function setLocationFromSource($v)
    {
        // clear any previous job location ID when we set a new location string
        $this->setLocation(null);

        $v = preg_replace('#(^\s*\(+|\)+\s*$)#', "", $v); // strip leading & ending () chars
        $v = $this->_cleanupTextValue($v);

        //
        // Restructure locations like "US-VA-Richmond" to be "Richmond, VA"
        //
        $arrMatches = array();
        $matched = preg_match('/.*(\w{2})\s*[\-,]\s*.*(\w{2})\s*[\-,]s*([\w]+)/', $v, $arrMatches);
        if ($matched !== false && count($arrMatches) == 4) {
            $v = $arrMatches[3] . ", " . $arrMatches[2];
        }

        parent::setLocationFromSource(trim($v));

        if(!is_null($v) && strlen($v) > 0)
            $this->_findAndSetLocationRelation_();
    }

    private function _findAndSetLocationRelation_()
    {
        $orig_loc_str = $this->getLocationFromSource();

        $loclookup = findOrCreateLocationLookupFromName($orig_loc_str);
        if(!is_null($loclookup)) {
            $this->setLocationId($loclookup->getLocationId());
            $this->_setDenormalizedLocationDisplayValue_();
        }
    }

    public function setCompany($v)
    {
        $v = $this->_cleanupTextValue($v);

        if (is_null($v) || strlen($v) == 0) {
            $v = '[Listed on ' . $this->getJobSite() .']';
        } else {
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
        }
        parent::setCompany($v);

    }

    public function setDepartment($v)
    {
        $v = $this->_cleanupTextValue($v);
        parent::setDepartment($v);
    }

    public function setEmploymentType($v)
    {
        $v = $this->_cleanupTextValue($v);
        parent::setEmploymentType($v);
    }

    public function setCategory($v)
    {
        $v = $this->_cleanupTextValue($v);
        parent::setCategory($v);
    }

    public function setPostedAt($v)
    {
        $v = strtolower($this->_cleanupTextValue($v));
        $dateVal = strtotime($v, $now = time());
        if (!($dateVal === false)) {
            $v = date('Y-m-d', $dateVal);
        }
        else
        {
            $info = date_parse($v);
            $date = "";
            foreach(array("month", "day", "year") as $dateval)
            {
                if($info[$dateval] !== false)
                {
                    $date .= strval($info[$dateval]);
                }
                else
                {
                    $date .= strval(getdate()[$dateval]);
                }
            }
            $v = $date;
        }

        parent::setPostedAt($v);
    }

    function __construct($arrJobFacts = null)
    {
        parent::__construct();
        if(!is_null($arrJobFacts) && count($arrJobFacts) > 1)
        {
            foreach(array_keys($arrJobFacts) as $key)
                $this->set($key, $arrJobFacts[$key]);
            $this->save();
        }
    }

    public function &get($name)
    {

        $throwEx = null;
        try {
            return $this->{$name};
        } catch (Exception $ex) {
            $throwEx = $ex;
        }

        try {
            return $this->getByName($name, \JobScooper\DataAccess\Map\UserSearchRunTableMap::TYPE_FIELDNAME);
        } catch (Exception $ex) {
            $throwEx = $ex;
        }

        try {
            return $this->getByName($name, \JobScooper\DataAccess\Map\UserSearchRunTableMap::TYPE_COLNAME);
        } catch (Exception $ex) {
            $throwEx = $ex;
        }

        if(!is_null($throwEx))
            handleException($throwEx, "Warning: field " . $name . " was not found in job posting object so ignoring it.", false);

        return null;
    }


    function getColumnMappingFromJobToDB() {
        return array(
            "job_title" => "Title",
            "job_site" => "JobSite",
            "job_title_tokenized"  => "JobTitleTokens",
            "job_post_url"  => "Url",
            "job_id"  => "JobSitePostID",
            "company"  => "Company",
            "location"  => "LocationFromSource",
            "job_site_category"  => "Category",
            "employment_type"  => "EmploymentType",
            "date_last_updated"  => "UpdatedAt",
            "job_site_date"  => "PostedAt",
            "date_pulled"  => "FirstSeenAt"
        );
    }

    public function fromArray($arr, $keyType = \Propel\Runtime\Map\TableMap::TYPE_PHPNAME)
    {
        try
        {
            $jobPostingKeys = \JobScooper\DataAccess\Map\JobPostingTableMap::getFieldNames($keyType);
            $arrJobPostingFields = array();
            foreach($jobPostingKeys as $k)
            {
                if(array_key_exists($k, $arr))
                {
                    $arrJobPostingFields[$k] = $arr[$k];
                    unset($arr[$k]);
                }
            }
            parent::fromArray($arrJobPostingFields, $keyType);
        }
        catch (\Exception $ex)
        {
            print $ex;
        }

        foreach(array_keys($arr) as $k)
            $this->set($k, $arr[$k]);

    }

    public function set($name, $value)
    {

        switch($name)
        {
            case "normalized":
            case "interested":
                break;

            case array_key_exists($name, $this->getColumnMappingFromJobToDB()):
                $newKey = $this->getColumnMappingFromJobToDB()[$name];
                $method = "set" . $newKey;
                $this->$method($value);
                break;

            default:
                $throwEx = null;
                try {
                    $this->{$name} = $value;
                    $throwEx = null;
                } catch (\Exception $ex) {
                    $throwEx = $ex;
                }

                try {
                    $this->setByName($name, \JobScooper\DataAccess\Map\UserSearchRunTableMap::TYPE_FIELDNAME, $value);
                    $throwEx = null;
                } catch (\Exception $ex) {
                    $throwEx = $ex;
                }

                try {
                    $this->setByName($name, \JobScooper\DataAccess\Map\UserSearchRunTableMap::TYPE_COLNAME, $value);
                    $throwEx = null;
                } catch (\Exception $ex) {
                    $throwEx = $ex;
                }

                try {
                    $this->setByName($name, \JobScooper\DataAccess\Map\UserSearchRunTableMap::TYPE_CAMELNAME, $value);
                    $throwEx = null;
                } catch (\Exception $ex) {
                    $throwEx = $ex;
                }

                if(!is_null($throwEx))
                    handleException($throwEx, "Warning: field " . $name . " was not found in job posting object so ignoring it.", false);

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
