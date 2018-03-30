<?php

namespace JobScooper\DataAccess;

use JobScooper\Builders\JobSitePluginBuilder;
use JobScooper\DataAccess\Base\UserSearchSiteRun as BaseUserSearchSiteRun;
use JobScooper\DataAccess\Map\UserSearchSiteRunTableMap;
use JobScooper\Utils\SimpleHTMLHelper;
use Propel\Runtime\Map\TableMap;
use JobScooper\BasePlugin\Classes\BaseSitePlugin;
/**
 *
 * @method UserSearchPair get($relation) Adds a LEFT JOIN clause to the query
 *
 */
class UserSearchSiteRun extends BaseUserSearchSiteRun
{
	public $nextResultsPageUrl = null;
	private $_plugin = null;
	private $_SearchUrlFormat = null;
	private $_JobPostingBaseUrl = null;

	/**
	 * @return string|null
	 * @throws \Exception
	 */
	public function getJobPostingBaseUrl()
	{
		if(empty($this->_JobPostingBaseUrl))
		{
			$plugin = $this->getSitePlugin();
			if(!empty($plugin))
				$this->_JobPostingBaseUrl = $plugin->getJobPostingBaseUrl();
		}
		return $this->_JobPostingBaseUrl;
	}



	/**
	 * @return \JobScooper\BasePlugin\Classes\BaseSitePlugin|null
	 * @throws \Exception
	 */
	function getSitePlugin()
	{
		if (!empty($this->_plugin))
			return $this->_plugin;

		$this->_plugin = JobSitePluginBuilder::getJobSitePlugin($this->getJobSiteKey());
		return $this->_plugin;
	}

	/**
	 * @return string|null
	 * @throws \Exception
	 */
	function getSearchUrlFormat()
	{
		if (empty($this->_SearchUrlFormat))
		{
			$plugin = $this->getSitePlugin();
			if(!empty($plugin))
				$this->_SearchUrlFormat = $plugin->getSearchUrlFormat();
		}

		return $this->_SearchUrlFormat;
	}

	/**
	 * @param                                         $err
	 * @param \JobScooper\Utils\SimpleHTMLHelper|null $objPageHtml
	 *
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	function failRunWithErrorMessage($err, SimpleHTMLHelper $objPageHtml=null)
	{
		$arrV = "";
		if(is_a($err, "\Exception") || is_subclass_of($err, "\Exception"))
		{
			$arrV = array(strval($err));
		}
		elseif(is_object($err))
		{
			$arrV = get_object_vars($err);
			$arrV["toString"] = strval($err);
		}
		elseif(is_string($err))
			$arrV = array($err);

		$this->setRunResultCode("failed");
		if(!empty($objPageHtml))
		{
			try
			{
				$filepath = $objPageHtml->debug_dump_to_file();
				$this->setRunErrorPageHtml($filepath);
			} catch (\Exception $ex)
			{
				LogWarning("Failed to save HTML for page that generated the error.");
			}
		}
		$this->setRunErrorDetails($arrV);
	}

	/**
	 * @throws \Propel\Runtime\Exception\PropelException
	 * @return $this|\JobScooper\DataAccess\UserSearchSiteRun
	 */
	function setRunSucceeded()
	{
		return $this->setRunResultCode('successful');
	}

	/**
	 * @param string $val
	 *
	 * @return $this|\JobScooper\DataAccess\UserSearchSiteRun
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	function setRunResultCode($val)
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
		if(!empty($user_search))
			$user = $user_search->getUserFromUS();

		if(method_exists($this, $method)) {
			return call_user_func(
				array($this, $method),
				$params
			);
		}
		else {
			foreach(array($user_search, $user) as $relObject)
			{
				if(method_exists($relObject, $method)) {
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
		$arrItem = null;

		$arrJobPosting = $this->toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false);
		updateColumnsForCSVFlatArray($arrJobPosting, new UserSearchSiteRunTableMap());
		if ($includeGeolocation === true) {
			$searchPair = $this->getUserSearchPairFromUSSR();
			if(!empty($searchPair) && !empty($searchPair->getGeoLocationId())) {
				$jobloc = $searchPair->getGeoLocationFromUS();
				if (!is_null($jobloc))
					$location = $jobloc->toFlatArrayForCSV();

				$arrItem = array_merge_recursive_distinct($arrJobPosting, $location);
			}
		} else
			$arrItem = $arrJobPosting;

		return $arrItem;
	}

	/**
	 * @return array
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	public function toLoggedContext()
	{
		return array_subset($this->toFlatArray(), array("UserSearchSiteRunKey", "GeoLocationId", "SearchStartUrl"));
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
	function getPageURLfromBaseFmt($nPage = null, $nItem = null)
	{
		$ret = $this->_callPluginMethodIfExists("getPageURLfromBaseFmt", array($this, $nPage, $nItem));
		if($ret !== false)
			return $ret;

		$strURL = $this->getSearchUrlFormat();

		$tokenFmtStrings = getUrlTokenList($strURL);
//	    $count = preg_match_all("/\*{3}(\w+):?(.*?)\*{3}/", $strURL, $tokenlist);
//	    if(!empty($tokenlist) && is_array($tokenlist) && count($tokenlist) >= 3)
//	    {
//		    $tokenFmtStrings = array_combine($tokenlist[1], $tokenlist[2]);
		if (!empty($tokenFmtStrings)) {
			foreach ($tokenFmtStrings as $tokFound) {
				$replaceVal = "";
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
		if(!empty($plugin))
			if (method_exists($plugin, $method))
				return call_user_func_array(array($plugin, $method), $arr);

		return false;
	}


	/**
	 * @param null $nDays
	 * @throws \Exception
	 * @return int
	 */
	function getDaysURLValue($nDays = null)
	{
		$ret = $this->_callPluginMethodIfExists("getDaysUrlValue", array($nDays));
		if($ret !== false)
			return $ret;

		return ($nDays == null || $nDays == "") ? 1 : $nDays;
	}

	/**
	 * @param $nPage
	 * @throws \Exception
	 *
	 * @return string
	 */
	function getPageURLValue($nPage)
	{
		$ret = $this->_callPluginMethodIfExists("getPageURLValue", array($nPage));
		if($ret !== false)
			return $ret;
		return ($nPage == null || $nPage == "") ? "" : $nPage;
	}

	/**
	 * @param $nItem
	 * @throws \Exception
	 *
	 * @return int
	 */
	function getItemURLValue($nItem)
	{
		$ret = $this->_callPluginMethodIfExists("getItemURLValue", array($nItem));
		if($ret !== false)
			return $ret;

		if ($this->isBitFlagSet(C__JOB_ITEMCOUNT_STARTSATZERO__) && $nItem > 0) {
			$nItem = $nItem - 1;
		}

		return ($nItem == null || $nItem == "") ? 0 : $nItem;
	}


	/**
	 *
	 * @throws \Exception
	 * @return string
	 */
	function getKeywordURLValue()
	{
		$ret = $this->_callPluginMethodIfExists("getKeywordURLValue", array($this));
		if($ret !== false)
			return $ret;

		if (!$this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
			return $this->getKeywordStringsForUrl();
		}

		return "";
	}



	/**
	 * @param string                                   $fmt
	 *
	 * @return null|string
	 * @throws \ErrorException
	 * @throws \Exception
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	function getGeoLocationURLValue($fmt = null)
	{
		$ret = $this->_callPluginMethodIfExists("getGeoLocationURLValue", array($this, $fmt));
		if($ret !== false)
			return $ret;

		if ($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED)) {
			throw new \ErrorException($this->getJobSiteKey() . " does not support the ***LOCATION*** replacement value in a base URL.  Please review and change your base URL format to remove the location value.  Aborting all searches for " . $this->getJobSiteKey());
		}

		$searchpair = $this->getUserSearchPairFromUSSR();
		$loc = $searchpair->getGeoLocationFromUS();
		if (empty($loc)) {
			LogMessage("Plugin for '" . $this->getJobSiteKey() . "' is missing the search location.   Skipping search '" . $this->getUserSearchSiteRunKey() . ".");

			return null;
		}

		$locTypeNeeded = null;
		if (!empty($fmt)) {
			$strLocationValue = replaceTokensInString($fmt, $loc->toArray());
		} else {
			$plugin = $this->getSitePlugin();
			if(!empty($plugin))
				$locTypeNeeded = $plugin->getGeoLocationSettingType($loc);
			if (empty($locTypeNeeded)) {
				LogMessage("Plugin for '" . $this->getJobSiteKey() . "' did not have the required location type of " . $locTypeNeeded . " set.   Skipping search '" . $this->getUserSearchSiteRunKey() . ".");

				return null;
			}

			$strLocationValue = $loc->formatLocationByLocationType($locTypeNeeded);
			if (empty($strLocationValue) || $strLocationValue == BaseSitePlugin::VALUE_NOT_SUPPORTED) {
				LogMessage("Plugin for '" . $this->getJobSiteKey() . "' did not have the required location type of " . $locTypeNeeded . " set.   Skipping search '" . $this->getUserSearchSiteRunKey() . ".");

				return "";
			}
		}

		if ($this->isBitFlagSet(C__JOB_LOCATION_REQUIRES_LOWERCASE))
			$strLocationValue = strtolower($strLocationValue);

		if (!isValueURLEncoded($strLocationValue)) {
			$strLocationValue = urlencode($strLocationValue);
		}

		return $strLocationValue;
	}


	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 * @throws \Exception
	 *
	 * @return string
	 */
	function getKeywordStringsForUrl()
	{
		$strRetCombinedKeywords = $this->getUserKeyword();

		// if we don't support keywords in the URL at all for this
		// plugin or we don't have any keywords, return empty string
		if ($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED) ||
			empty($strRetCombinedKeywords)) {
			$strRetCombinedKeywords = "";
		} else {
			if ($this->isBitFlagSet(C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS)) {
				$strRetCombinedKeywords = "\"{$strRetCombinedKeywords}\"";
			}

			if (!isValueURLEncoded($strRetCombinedKeywords)) {
				if ($this->isBitFlagSet(C__JOB_KEYWORD_PARAMETER_SPACES_RAW_ENCODE))
					$strRetCombinedKeywords = rawurlencode($strRetCombinedKeywords);
				else
					$strRetCombinedKeywords = urlencode($strRetCombinedKeywords);
			}

			if ($this->isBitFlagSet(C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES)) {
				$strRetCombinedKeywords = str_replace("%22", "-", $strRetCombinedKeywords);
				$strRetCombinedKeywords = str_replace("+", "-", $strRetCombinedKeywords);
			}

			if($this->isBitFlagSet(C__JOB_KEYWORD_REQUIRES_LOWERCASE))
				$strRetCombinedKeywords = strtolower($strRetCombinedKeywords);
		}

		return $strRetCombinedKeywords;
	}

	/**
	 *
	 * @throws \Exception
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	function setStartingUrlForSearch()
	{

		$searchStartURL = $this->getPageURLfromBaseFmt(1, 1);
		if (is_null($searchStartURL) || strlen($searchStartURL) == 0)
			$searchStartURL = $this->getJobPostingBaseUrl();

		$this->setSearchStartUrl($searchStartURL);
		$this->log("Setting start URL for " . $this->getJobSiteKey(). "[" . $this->getUserSearchSiteRunKey() . "] to: " . PHP_EOL . $this->getSearchStartUrl());
	}


	/**
	 * @param $flagToCheck
	 * @throws \Exception
	 * @return bool
	 */
	function isBitFlagSet($flagToCheck)
	{
		$plugin = $this->getSitePlugin();
		if (!empty($plugin))
			return $plugin->isBitFlagSet($flagToCheck);

		throw new \Exception("Error: could not get job site plugin object for {$this->getJobSiteKey()}.");
	}

}
