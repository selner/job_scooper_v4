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

use JobScooper\DataAccess\Base\UserSearchSiteRun as BaseUserSearchSiteRun;
use JobScooper\DataAccess\Map\UserSearchSiteRunTableMap;
use JobScooper\Utils\SimpleHTMLHelper;
use Propel\Runtime\Map\TableMap;
use JobScooper\SitePlugins\Base\SitePlugin;

/**
 *
 * @method UserSearchPair get($relation) Adds a LEFT JOIN clause to the query
 *
 */
class UserSearchSiteRun extends BaseUserSearchSiteRun
{
    public $searchResultsPageUrl = null;
    private $_plugin = null;
    private $_SearchUrlFormat = null;
    private $_JobPostingBaseUrl = null;

    /**
     * @return string|null
     * @throws \Exception
     */
    public function getJobPostingBaseUrl()
    {
        if (is_empty_value($this->_JobPostingBaseUrl)) {
            $plugin = $this->getSitePlugin();
            if (null !== $plugin) {
                $this->_JobPostingBaseUrl = $plugin->getJobPostingBaseUrl();
            }
        }
        return $this->_JobPostingBaseUrl;
    }



    /**
     * @return \JobScooper\SitePlugins\Interfaces\IJobSitePlugin|null
     * @throws \Exception
     */
    public function getSitePlugin()
    {
        if (null === $this->_plugin) {
	        $this->_plugin = JobSiteManager::getJobSitePluginByKey($this->getJobSiteKey());
        }

        return $this->_plugin;
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    public function getSearchUrlFormat()
    {
        if (is_empty_value($this->_SearchUrlFormat)) {
            $plugin = $this->getSitePlugin();
            if (null !== $plugin) {
                $this->_SearchUrlFormat = $plugin->getSearchUrlFormat();
            }
        }

        return $this->_SearchUrlFormat;
    }

    /**
     * @param                                         $err
     * @param \JobScooper\Utils\SimpleHTMLHelper|null $objPageHtml
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function failRunWithErrorMessage($err, SimpleHTMLHelper $objPageHtml=null)
    {
        $arrV = '';
        if (is_a($err, "\Exception") || is_subclass_of($err, "\Exception")) {
            $arrV = array(strval($err));
        } elseif (is_object($err)) {
            $arrV = get_object_vars($err);
            $arrV["toString"] = strval($err);
        } elseif (is_string($err)) {
            $arrV = array($err);
        }

        $this->setRunResultCode("failed");
        if (null !== $objPageHtml) {
            try {
                $filepath = $objPageHtml->debug_dump_to_file();
                $this->setRunErrorPageHtml($filepath);
            } catch (\Exception $ex) {
                LogWarning("Failed to save HTML for page that generated the error.");
            }
        }
        $this->setRunErrorDetails($arrV);
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     * @return $this|\JobScooper\DataAccess\UserSearchSiteRun
     */
    public function setRunSucceeded()
    {
        return $this->setRunResultCode('successful');
    }

    /**
     * @param string $val
     *
     * @return $this|\JobScooper\DataAccess\UserSearchSiteRun
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setRunResultCode($val)
    {
        switch ($val) {
            case "failed":
                break;

            case 'successful':
                $this->removeRunErrorDetail(array());
                break;

            case "skipped":
                break;

            case "not-run":
            case "excluded":
            default:
                break;
        }

        $ret = parent::setRunResultCode($val);

        parent::setEndedAt(time());

        return $ret;
    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function __call($method, $params)
    {
        $user = null;
        $user_search = $this->getUserSearchPairFromUSSR();
        if (null !== $user_search) {
            $user = $user_search->getUserFromUS();
        }

        if (method_exists($this, $method)) {
            return call_user_func(
                array($this, $method),
                $params
            );
        } else {
            foreach (array($user_search, $user) as $relObject) {
                if (method_exists($relObject, $method)) {
                    return call_user_func(
                        array($relObject, $method),
                        $params
                    );
                }
            }
        }

        return false;
    }


    /**
     * @param bool $includeGeolocation
     *
     * @return array
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function toFlatArray($includeGeolocation = false)
    {
        $location = array();

        $searchRunFacts = $this->toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false);
        updateColumnsForCSVFlatArray($searchRunFacts, new UserSearchSiteRunTableMap());

        $searchPair = $this->getUserSearchPairFromUSSR();
        if (null !== $searchPair && null !== $searchPair->getGeoLocationId()) {
        	$searchRunFacts['GeoLocationId'] = $searchPair->getGeoLocationId();
	        if($includeGeolocation === true) {
	            $jobloc = $searchPair->getGeoLocationFromUS();
	            if (null !== $jobloc) {
	                $location = $jobloc->toFlatArrayForCSV();
	            }

	            $searchRunFacts = array_merge_recursive_distinct($searchRunFacts, $location);
	        }
        }
        $searchPair = null;

        $site = $this->getJobSiteFromUSSR();
		$searchRunFacts['ResultsFilterType'] = $site->getResultsFilterType();
		$site = null;

        $searchRunFacts['UserId'] = UserSearchSiteRunManager::getUserIdFromSearchFacts($searchRunFacts);

        return $searchRunFacts;
    }

    /**
     * @return array
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function toLoggedContext()
    {
        $arr = array_subset($this->toFlatArray(), array("UserSearchSiteRunKey", "GeoLocationId", "SearchStartUrl"));
        $arr['searchResultsPageUrl'] = $this->searchResultsPageUrl;

        return $arr;
    }



    /**
     * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
     * @param null                                     $nPage
     * @param null                                     $nItem
     *
     * @return string|null
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getPageURLfromBaseFmt($nPage = null, $nItem = null)
    {
        $ret = $this->_callPluginMethodIfExists("getPageURLfromBaseFmt", array($this, $nPage, $nItem));
        if ($ret !== false) {
            return $ret;
        }

        $strURL = $this->getSearchUrlFormat();

        $tokenFmtStrings = getUrlTokenList($strURL);
        //	    $count = preg_match_all("/\*{3}(\w+):?(.*?)\*{3}/", $strURL, $tokenlist);
        //	    if(!empty($tokenlist) && is_array($tokenlist) && count($tokenlist) >= 3)
        //	    {
        //		    $tokenFmtStrings = array_combine($tokenlist[1], $tokenlist[2]);
        if (null !== $tokenFmtStrings) {
            foreach ($tokenFmtStrings as $tokFound) {
                $replaceVal = '';
                $replaceStr = $tokFound['source_string'];
                switch ($tokFound['type']) {
                    case "LOCATION":
                        $replaceVal = $this->getGeoLocationURLValue($tokFound['format_value']);
                        break;

                    case "KEYWORDS":
                        $replaceVal = $this->getKeywordURLValue();

                        break;

                    case "PAGE_NUMBER":
                        $replaceVal = $this->getPageURLValue($nPage);
                        break;

                    case "ITEM_NUMBER":
                        $replaceVal = $this->getItemURLValue($nItem);
                        break;
                }

                $strURL = str_ireplace($replaceStr, $replaceVal, $strURL);
            }
        }

        return $strURL;
    }

    //************************************************************************
    //
    //
    //
    //  URL Functions
    //
    //
    //
    //************************************************************************

    /**
     * @param $method
     * @param $arr
     *
     * @return bool|mixed
     * @throws \Exception
     */
    private function _callPluginMethodIfExists($method, $arr)
    {
        $plugin = $this->getSitePlugin();
        if (null !== $plugin) {
            if (method_exists($plugin, $method)) {
                return call_user_func_array(array($plugin, $method), $arr);
            }
        }

        return false;
    }


    /**
     * @param null $nDays
     *
     * @throws \Exception
     * @return int
     */
    public function getDaysURLValue($nDays = null)
    {
        $ret = $this->_callPluginMethodIfExists("getDaysUrlValue", array($nDays));
        if ($ret !== false) {
            return $ret;
        }

        return ($nDays == null || $nDays == '') ? 1 : $nDays;
    }

    /**
     * @param $nPage
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getPageURLValue($nPage)
    {
        $ret = $this->_callPluginMethodIfExists("getPageURLValue", array($nPage));
        if ($ret !== false) {
            return $ret;
        }
        return ($nPage == null || $nPage == '') ? '' : $nPage;
    }

    /**
     * @param $nItem
     *
     * @throws \Exception
     *
     * @return int
     */
    public function getItemURLValue($nItem)
    {
        $ret = $this->_callPluginMethodIfExists("getItemURLValue", array($nItem));
        if ($ret !== false) {
            return $ret;
        }

        if ($this->isBitFlagSet(C__JOB_ITEMCOUNT_STARTSATZERO__) && $nItem > 0) {
            $nItem = $nItem - 1;
        }

        return ($nItem == null || $nItem == '') ? 0 : $nItem;
    }


    /**
     *
     * @throws \Exception
     * @return string
     */
    public function getKeywordURLValue()
    {
        $ret = $this->_callPluginMethodIfExists("getKeywordURLValue", array($this));
        if ($ret !== false) {
            return $ret;
        }

        if (!$this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
            return $this->getKeywordStringsForUrl();
        }

        return '';
    }



    /**
     * @param string                                   $fmt
     *
     * @return null|string
     * @throws \ErrorException
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getGeoLocationURLValue($fmt = null)
    {
        $ret = $this->_callPluginMethodIfExists("getGeoLocationURLValue", array($this, $fmt));
        if ($ret !== false) {
            return $ret;
        }

        if ($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED)) {
            throw new \ErrorException($this->getJobSiteKey() . " does not support the ***LOCATION*** replacement value in a base URL.  Please review and change your base URL format to remove the location value.  Aborting all searches for " . $this->getJobSiteKey());
        }

        $searchpair = $this->getUserSearchPairFromUSSR();
        $loc = $searchpair->getGeoLocationFromUS();
        if (is_empty_value($loc)) {
            LogMessage("Plugin for '" . $this->getJobSiteKey() . "' is missing the search location.   Skipping search '" . $this->getUserSearchSiteRunKey() . ".");

            return null;
        }

        $locTypeNeeded = null;
        if (!is_empty_value($fmt)) {
            $strLocationValue = replaceTokensInString($fmt, $loc->toArray());
        } else {
            $plugin = $this->getSitePlugin();
            if (null !== $plugin) {
                $locTypeNeeded = $plugin->getGeoLocationSettingType($loc);
            }
            if (is_empty_value($locTypeNeeded)) {
                LogMessage("Plugin for '" . $this->getJobSiteKey() . "' did not have the required location type of " . $locTypeNeeded . " set.   Skipping search '" . $this->getUserSearchSiteRunKey() . ".");

                return null;
            }

            $strLocationValue = $loc->formatLocationByLocationType($locTypeNeeded);
            if (is_empty_value($strLocationValue) || $strLocationValue == SitePlugin::VALUE_NOT_SUPPORTED) {
                LogMessage("Plugin for '" . $this->getJobSiteKey() . "' did not have the required location type of " . $locTypeNeeded . " set.   Skipping search '" . $this->getUserSearchSiteRunKey() . ".");

                return '';
            }
        }

        if ($this->isBitFlagSet(C__JOB_LOCATION_REQUIRES_LOWERCASE)) {
            $strLocationValue = strtolower($strLocationValue);
        }

        if (!isValueURLEncoded($strLocationValue)) {
            $strLocationValue = urlencode($strLocationValue);
        }

        return $strLocationValue;
    }


    /**
     * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getKeywordStringsForUrl()
    {
        $strRetCombinedKeywords = $this->getUserKeyword();

        // if we don't support keywords in the URL at all for this
        // plugin or we don't have any keywords, return empty string
        if (is_empty_value($strRetCombinedKeywords)
            ||
            $this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
            $strRetCombinedKeywords = '';
        } else {
            if ($this->isBitFlagSet(C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS)) {
                $strRetCombinedKeywords = "\"{$strRetCombinedKeywords}\"";
            }

            if (!isValueURLEncoded($strRetCombinedKeywords)) {
                if ($this->isBitFlagSet(C__JOB_KEYWORD_PARAMETER_SPACES_RAW_ENCODE)) {
                    $strRetCombinedKeywords = rawurlencode($strRetCombinedKeywords);
                } else {
                    $strRetCombinedKeywords = urlencode($strRetCombinedKeywords);
                }
            }

            if ($this->isBitFlagSet(C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES)) {
                $strRetCombinedKeywords = str_replace("%22", "-", $strRetCombinedKeywords);
                $strRetCombinedKeywords = str_replace("+", "-", $strRetCombinedKeywords);
            }

            if ($this->isBitFlagSet(C__JOB_KEYWORD_REQUIRES_LOWERCASE)) {
                $strRetCombinedKeywords = strtolower($strRetCombinedKeywords);
            }
        }

        return $strRetCombinedKeywords;
    }

    /**
     *
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setStartingUrlForSearch()
    {
        $searchStartURL = $this->getPageURLfromBaseFmt(1, 1);
        if (is_empty_value($searchStartURL)) {
            $searchStartURL = $this->getJobPostingBaseUrl();
        }

        $this->setSearchStartUrl($searchStartURL);
        $this->log("Setting start URL for " . $this->getJobSiteKey(). "[" . $this->getUserSearchSiteRunKey() . "] to: " . PHP_EOL . $this->getSearchStartUrl());
    }


    /**
     * @param $flagToCheck
     *
     * @throws \Exception
     * @return bool
     */
    public function isBitFlagSet($flagToCheck)
    {
        $plugin = $this->getSitePlugin();
        if (null !== $plugin) {
            return $plugin->isBitFlagSet($flagToCheck);
        }

        throw new \Exception("Error: could not get job site plugin object for {$this->getJobSiteKey()}.");
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     * @return integer
     */
    public function getUserId()
    {
    	$searchPair = $this->getUserSearchPairFromUSSR();
    	if(null !== $searchPair) {
    		return $searchPair->getUserId();
    	}

    	return null;
    }
}
