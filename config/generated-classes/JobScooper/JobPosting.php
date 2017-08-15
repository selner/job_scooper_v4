<?php

namespace JobScooper;

use JobScooper\Base\JobPosting as BaseJobPosting;
use Propel\Runtime\Map\TableMap;
/**
 * Skeleton subclass for representing a row from the 'jobposting' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class JobPosting extends \JobScooper\Base\JobPosting implements \ArrayAccess
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

    public function preInsert(\Propel\Runtime\Connection\ConnectionInterface $con = null)
    {
        $this->updateAutoColumns();

        if (is_callable('parent::preInsert')) {
            return parent::preInsert($con);
        }
        return true;
    }

    public function preUpdate(\Propel\Runtime\Connection\ConnectionInterface $con = null)
    {
        $this->updateAutoColumns();

        if (is_callable('parent::preUpdate')) {
            return parent::preUpdate($con);
        }
        return true;
    }


    protected function normalizeJobItem($arrItem)
    {
        //
        // If this listing has already been normalized, don't re-do the normalization or
        // errors might be introduced
        //
        if (array_key_exists('normalized', $arrItem) && $arrItem['normalized'] === true)
            return $arrItem;

        $normalizer = new Normalizer();

        // For reference, DEFAULT_SCRUB =  REMOVE_PUNCT | HTML_DECODE | LOWERCASE | REMOVE_EXTRA_WHITESPACE
        $arrItem['date_pulled'] = getTodayAsString();
        $arrItem['job_site'] = $this->siteName;

        if (is_null($arrItem['job_site']) || strlen($arrItem['job_site']) <= 0)
            $arrItem ['job_site'] = \Scooper\strScrub($this->siteName, DEFAULT_SCRUB);

        $arrItem ['job_post_url'] = trim($arrItem['job_post_url']); // DO NOT LOWER, BREAKS URLS

        if (!is_null($arrItem['job_post_url']) || strlen($arrItem['job_post_url']) > 0) {
            $arrMatches = array();
            $matchedHTTP = preg_match(REXPR_MATCH_URL_DOMAIN, $arrItem['job_post_url'], $arrMatches);
            if (!$matchedHTTP) {
                $sep = "";
                if (substr($arrItem['job_post_url'], 0, 1) != "/")
                    $sep = "/";
                $arrItem['job_post_url'] = $this->siteBaseURL . $sep . $arrItem['job_post_url'];
            }
        } else {
            $arrItem['job_post_url'] = "[UNKNOWN]";
        }

        if (is_null($arrItem['job_id']) || strlen($arrItem['job_id']) <= 0)
            $arrItem['job_id'] = $arrItem['job_post_url'];

        $arrItem['job_id'] = preg_replace(REXPR_MATCH_URL_DOMAIN, "", $arrItem['job_id']);
        $arrItem ['job_id'] = \Scooper\strScrub($arrItem['job_id'], FOR_LOOKUP_VALUE_MATCHING);
        if (is_null($arrItem['job_id']) || strlen($arrItem['job_id']) == 0) {
            if (isset($this->regex_link_job_id)) {
                $item['job_id'] = $this->getIDFromLink($this->regex_link_job_id, $arrItem['job_post_url']);
            }
        }


        // Removes " NEW!", etc from the job title.  ZipRecruiter tends to occasionally
        // have that appended which then fails de-duplication. (Fixes issue #45) Glassdoor has "- easy apply" as well.
        $arrItem ['job_title'] = str_ireplace(" NEW!", "", $arrItem['job_title']);
        $arrItem ['job_title'] = str_ireplace("- new", "", $arrItem['job_title']);
        $arrItem ['job_title'] = str_ireplace("- easy apply", "", $arrItem['job_title']);
        $arrItem ['job_title'] = \Scooper\strScrub($arrItem['job_title'], SIMPLE_TEXT_CLEANUP);

        $arrItem ['location'] = preg_replace('#(^\s*\(+|\)+\s*$)#', "", $arrItem['location']); // strip leading & ending () chars
        $arrItem ['location'] = \Scooper\strScrub($arrItem['location'], SIMPLE_TEXT_CLEANUP);

        //
        // Restructure locations like "US-VA-Richmond" to be "Richmond, VA"
        //
        $arrMatches = array();
        $matched = preg_match('/.*(\w{2})\s*[\-,]\s*.*(\w{2})\s*[\-,]s*([\w]+)/', $arrItem ['location'], $arrMatches);
        if ($matched !== false && count($arrMatches) == 4) {
            $arrItem['location'] = $arrMatches[3] . ", " . $arrMatches[2];
        }
        $stringToNormalize = "111 Bogus St, " . $arrItem['location'];
        $location = $normalizer->parse($stringToNormalize);
        if ($location !== false)
            $arrItem['location'] = $location['city'] . ", " . $location['state'];

        if (is_null($arrItem['company']) || strlen($arrItem['company']) == 0) {
            $arrItem ['company'] = '[UNKNOWN]';
        } else {
            $arrItem ['company'] = \Scooper\strScrub($arrItem['company'], ADVANCED_TEXT_CLEANUP);
            // Remove common company name extensions like "Corporation" or "Inc." so we have
            // a higher match likelihood
            $arrItem ['company'] = preg_replace(array('/\s[Cc]orporat[e|ion]/', '/\s[Cc]orp\W{0,1}/', '/\.com/', '/\W{0,}\s[iI]nc/', '/\W{0,}\s[lL][lL][cC]/', '/\W{0,}\s[lL][tT][dD]/'), "", $arrItem['company']);

            switch (\Scooper\strScrub($arrItem ['company'])) {
                case "amazon":
                case "amazon com":
                case "a2z":
                case "lab 126":
                case "amazon Web Services":
                case "amazon fulfillment services":
                case "amazonwebservices":
                case "amazon (seattle)":
                    $arrItem ['company'] = "Amazon";
                    break;

                case "market leader":
                case "market leader inc":
                case "market leader llc":
                    $arrItem ['company'] = "Market Leader";
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
                    $arrItem ['company'] = "Disney";
                    break;

            }
        }

        $arrItem ['job_site_category'] = \Scooper\strScrub($arrItem['job_site_category'], SIMPLE_TEXT_CLEANUP);

        $arrItem ['job_site_date'] = \Scooper\strScrub($arrItem['job_site_date'], REMOVE_EXTRA_WHITESPACE | LOWERCASE | HTML_DECODE);
        $dateVal = strtotime($arrItem ['job_site_date'], $now = time());
        if (!($dateVal === false)) {
            $arrItem['job_site_date'] = date('Y-m-d', $dateVal);
        }


        if (strlen($arrItem['key_company_role']) <= 0) {
            $compForKey = $arrItem['company'] . $arrItem['job_title'];
            if (strcasecmp($compForKey, "[UNKNOWN]") == 0)
                $compForKey = $compForKey . $arrItem['job_id'];
            $arrItem['key_company_role'] = \Scooper\strScrub(($compForKey), FOR_LOOKUP_VALUE_MATCHING);
        }

        if (strlen($arrItem['key_jobsite_siteid']) <= 0) {
            $arrItem['key_jobsite_siteid'] = \Scooper\strScrub($arrItem['job_site'], FOR_LOOKUP_VALUE_MATCHING) . \Scooper\strScrub($arrItem['job_id'], FOR_LOOKUP_VALUE_MATCHING);
        }

        if (strlen($arrItem['date_last_updated']) <= 0) {
            $arrItem['date_last_updated'] = $arrItem['date_pulled'];
        }


        //
        // And finally, lets scrub the returned data to make sure it's valid UTF-8.  If we don't,
        // we will end up with errors down the line such as when we try to save the results to file.
        //
        foreach (array_keys($arrItem) as $k) {
            if (is_string($arrItem[$k])) {
                $arrItem[$k] = clean_utf8($arrItem[$k]);
            }
        }


        $arrItem['normalized'] = true;

        return $arrItem;
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
            return $this->getByName($name, \JobScooper\Map\UserSearchAuditTableMap::TYPE_FIELDNAME);
        } catch (Exception $ex) {
            $throwEx = $ex;
        }

        try {
            return $this->getByName($name, \JobScooper\Map\UserSearchAuditTableMap::TYPE_COLNAME);
        } catch (Exception $ex) {
            $throwEx = $ex;
        }

        if(!is_null($throwEx))
            handleException($throwEx, "Warning: field " . $name . " was not found in job posting object so ignoring it.", false);

    }


    function getColumnMappingFromJobToDB() {
        return array(
            "job_title" => "Title",
            "job_site" => "JobSite",
            "job_title_tokenized"  => "JobTitleTokens",
            "job_post_url"  => "Url",
            "job_id"  => "JobSitePostID",
            "company"  => "Company",
            "location"  => "Location",
            "job_site_category"  => "Category",
            "employment_type"  => "EmploymentType",
            "date_last_updated"  => "UpdatedAt",
            "job_site_date"  => "PostedAt",
            "date_pulled"  => "FirstSeenAt"
        );
    }

    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        foreach(array_keys($arr) as $k)
            $this->set($k, $arr[$k]);

        parent::fromArray($arr, $keyType);
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
