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

namespace JobScooper\BasePlugin\Classes;



use JBZoo\Utils\Url;
use JobScooper\Builders\JobSitePluginBuilder;
use JobScooper\DataAccess\GeoLocation;
use JobScooper\DataAccess\JobPostingQuery;
use JobScooper\DataAccess\Map\JobPostingTableMap;
use JobScooper\DataAccess\Map\UserJobMatchTableMap;
use JobScooper\DataAccess\User;
use JobScooper\DataAccess\UserJobMatchQuery;
use JobScooper\DataAccess\UserSearchSiteRun;
use JobScooper\BasePlugin\Interfaces\IJobSitePlugin;
use JobScooper\Manager\SeleniumManager;

const BASE_URL_TAG_LOCATION = "***LOCATION***";
const BASE_URL_TAG_KEYWORDS = "***KEYWORDS***";
use Exception;
use JobScooper\Utils\CurlWrapper;
use JobScooper\Utils\SimpleHTMLHelper;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Propel;
use Psr\Log\LogLevel;

/**
 * Class BaseSitePlugin
 * @package JobScooper\BasePlugin\Classes
 */
abstract class BaseSitePlugin implements IJobSitePlugin
{
	const VALUE_NOT_SUPPORTED = -1;

	/**
	 * BaseSitePlugin constructor.
	 *
	 * @throws \Exception
	 */
	function __construct()
	{
		$this->JobSiteKey = $this->getJobSiteKey();

		if (is_null($this->JobSiteName) || strlen($this->JobSiteName) == 0) {
			$classname = get_class($this);
			if (preg_match('/^Plugin(\w+)/', $classname, $matches) > 0) {
				$this->JobSiteName = $matches[1];
			}
		}

		$this->_otherPluginSettings = getConfigurationSetting('plugin_settings.' . $this->JobSiteKey);

		//
		// Set all the flag defaults to be not supported
		//
		$this->additionalBitFlags["LOCATION"] = C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED;
		$this->additionalBitFlags["KEYWORDS"] = C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED;
		$this->additionalBitFlags["NUMBER_DAYS"] = C__JOB_DAYS_VALUE_NOTAPPLICABLE__;

		//
		// Now based on what we find in the Search Format URL, unset the default
		// unsupported value (making them supported for this site)
		//
		$tokenFmtStrings = getUrlTokenList($this->SearchUrlFormat);
		if (!empty($tokenFmtStrings)) {
			foreach ($tokenFmtStrings as $token) {
				switch (strtoupper($token['type'])) {
					case "LOCATION":
					case "KEYWORDS":
					case "NUMBER_DAYS":
						unset($this->additionalBitFlags[strtoupper($token['type'])]);
						break;

					default:
						break;
				}
			}
		}

		if (is_array($this->additionalBitFlags)) {
			foreach ($this->additionalBitFlags as $flag) {
				// If the flag is already set, don't try to set it again or it will
				// actually unset that flag incorrectly
				if (!$this->isBitFlagSet($flag)) {
					$this->_flags_ = $this->_flags_ | $flag;
				}
			}
		}

		if (!is_null($this->selectorMoreListings) && strlen($this->selectorMoreListings) > 0)
			$this->selectorMoreListings = preg_replace("/\\\?[\"']/", "'", $this->selectorMoreListings);

		if (substr($this->JobPostingBaseUrl, strlen($this->JobPostingBaseUrl) - 1, strlen($this->JobPostingBaseUrl)) === "/")
			$this->JobPostingBaseUrl = substr($this->JobPostingBaseUrl, 0, strlen($this->JobPostingBaseUrl) - 1);

		if (empty($this->JobSiteName)) {
			$this->JobSiteName = str_replace("Plugin", "", get_class($this));
		}

		if (empty($this->JobPostingBaseUrl)) {
			$urlparts = parse_url($this->SearchUrlFormat);
			$this->JobPostingBaseUrl = "{$urlparts['scheme']}://{$urlparts['host']}";
		}
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	function setResultsFilterType()
	{
		if ($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
			if ($this->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED))
				$this->resultsFilterType = "all-only";
			else
				$this->resultsFilterType = "all-by-location";
		} else
			$this->resultsFilterType = "user-filtered";

		$key = $this->getJobSiteKey();
		$allSites = JobSitePluginBuilder::getAllJobSites();
		$thisSite = $allSites[$key];
		$thisSite->setResultsFilterType($this->resultsFilterType);
		$thisSite->save();

		return $this->resultsFilterType;
	}

	/**
	 * @return null|string
	 * @throws \Exception
	 */
	function getJobSiteKey()
	{
		if (empty($this->JobSiteKey)) {
			$arrSiteList = JobSitePluginBuilder::getAllJobSites();
			$className = get_class($this);
			$siteKey = strtolower(str_ireplace("Plugin", "", $className));
			if (array_key_exists($siteKey, $arrSiteList) === true)
				$this->JobSiteKey = $siteKey;
		}

		return $this->JobSiteKey;
	}


	//************************************************************************
	//
	//
	//
	//  Adding search parameters & downloading new job functions
	//
	//
	//
	//************************************************************************

	/**
	 * @param $flagToCheck
	 *
	 * @return bool
	 */
	public function isBitFlagSet($flagToCheck)
	{
		$ret = isBitFlagSet($this->_flags_, $flagToCheck);
		if ($ret == $flagToCheck) {
			return true;
		}

		return false;
	}

	/**
	 * @param UserSearchSiteRun[]         $arrSearches
	 *
	 * @param \JobScooper\DataAccess\User $user
	 *
	 * @throws \Exception
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	public function setSearches($arrSearches, User $user)
	{
		$this->arrSearchesToReturn = array(); // clear out any previous searches

		$this->_currentUserForSearches = $user;
		$this->setResultsFilterType();

		if ($this->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {
			$searchToKeep = null;
			foreach (array_keys($arrSearches) as $searchkey) {
				$search = $arrSearches[$searchkey];
				if (empty($searchToKeep))
					$searchToKeep = $search;
				else {
					$search->delete();
					unset($arrSearches[$searchkey]);
				}
			}
			$arrSearches = array($searchToKeep);
		}

		foreach ($arrSearches as $searchDetails) {
			$this->_addSearch_($searchDetails);
		}
	}

	/**
	 * @throws \Exception
	 */
	public function downloadLatestJobsForAllSearches()
	{
		$search = null;
		$boolSearchSuccess = null;

		if (count($this->arrSearchesToReturn) == 0) {
			LogMessage($this->JobSiteName . ": no searches set. Skipping...");

			return array();
		}

		try {
			/*
				Check to see if we should pull new job listings now.  If we ran too recently, this will skip the run
			*/
			foreach ($this->arrSearchesToReturn as $search) {
				$this->_curlWrapper = new CurlWrapper();

				try {
					if ($this->isBitFlagSet(C__JOB_USE_SELENIUM) && is_null($this->selenium)) {
						try {
							$this->selenium = new SeleniumManager();
						} catch (Exception $ex) {
							handleException($ex, "Unable to start Selenium to get jobs for plugin '" . $this->JobSiteName . "'", true);
						}
					}

					$this->_updateJobsDataForSearch_($search);
					$this->_addJobMatchesToUser($search);
					$this->_setSearchResult_($search, true);
				} catch (Exception $ex) {
					$this->_setSearchResult_($search, false, new Exception("Unable to download jobs: " . strval($ex)));
					handleException($ex, null, true, $extraData = $search->toArray());
				} finally {
					$search->save();
				}
			}

			/*
			 *  If this plugin is not user-filterable (aka no keywords filter), then any jobs from it can be applied
			 *  to all users.  If that is the case, update user matches to assets any jobs that were loaded previously
			 *  but the user is currently missing from their potential job matches.
			 */
			if ((strcasecmp($this->resultsFilterType, "all-only") == 0) || (strcasecmp($this->resultsFilterType, "all-by-location") == 0)) {
				try {
					LogMessage("Checking for missing " . $this->JobSiteKey . " jobs for user " . $this->_currentUserForSearches->getUserId() . ".");
					$dataExistingUserJobMatchIds = UserJobMatchQuery::create()
						->select("JobPostingId")
						->filterByUserId($this->_currentUserForSearches->getUserId())
						->useJobPostingFromUJMQuery()
						->filterByJobSiteKey($this->JobSiteKey)
						->endUse()
						->find()
						->getData();

					$queryAllJobsFromJobSite = JobPostingQuery::create()
						->filterByJobSiteKey($this->JobSiteKey)
						->select("JobPostingId")
						->find()
						->getData();

					$jobIdsToAddToUser = array_diff($queryAllJobsFromJobSite, $dataExistingUserJobMatchIds);

					if (!is_null($jobIdsToAddToUser) && count($jobIdsToAddToUser) > 0) {
						LogMessage("Found " . count($jobIdsToAddToUser) . " " . $this->JobSiteKey . " jobs not yet assigned to user " . $this->_currentUserForSearches->getUserSlug() . ".");
						$this->_addJobMatchIdsToUser($jobIdsToAddToUser, $search);
						LogMessage("Successfully added " . count($jobIdsToAddToUser) . " " . $this->JobSiteKey . " jobs to user " . $this->_currentUserForSearches->getUserSlug() . ".");
					} else {
						LogMessage("User " . $this->_currentUserForSearches->getUserSlug() . " had no missing previously loaded listings from " . $this->JobSiteKey . ".");
					}
				} catch (Exception $ex) {
					handleException($ex);
				}
			}

		} catch (Exception $ex) {
			throw $ex;
		} finally {
			try {
				if (!is_null($this->selenium)) {
					$this->selenium->done();
				}
			} catch (Exception $ex) {
				$this->log("Unable to shutdown Selenium remote webdriver successfully while closing down downloads for {$this->JobSiteName}: " . $ex->getMessage(), \Monolog\Logger::WARNING);
			} finally {
				unset($this->selenium);
			}
		}

	}

	//************************************************************************
	//
	//
	//
	//  Protected and Private Class Members
	//
	//
	//
	//************************************************************************

	/**
	 * @var SeleniumManager|null
	 */
	protected $selenium = null;

	/**
	 * @var UserSearchSiteRun[]|null
	 */
	protected $arrSearchesToReturn = null;

	protected $JobListingsPerPage = 20;
	protected $additionalBitFlags = array();
	protected $PaginationType = null;
	protected $secsPageTimeout = null;
	protected $nextPageScript = null;
	protected $selectorMoreListings = null;

	/**
	 * @return null
	 */
	public function getSearchUrlFormat()
	{
		return $this->SearchUrlFormat;
	}

	protected $nMaxJobsToReturn = C_JOB_MAX_RESULTS_PER_SEARCH;
	protected $arrSearchReturnedJobs = array();
	protected $SearchUrlFormat = null;
	protected $JobPostingBaseUrl = null;
	protected $LocationType = null;
	protected $JobSiteName = null;
	protected $JobSiteKey = null;
	protected $_otherPluginSettings = null;


	protected $prevCookies = "";
	protected $prevURL = null;

	protected $resultsFilterType = "user-filtered";
	protected $additionalLoadDelaySeconds = 0;
	protected $_flags_ = null;
	protected $pluginResultsType = C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__;

	protected $CountryCodes = array("US");
	private $_curlWrapper = null;
	protected $searchResultsPageUrl = null;

	/**
	 * @var User/null
	 */
	protected $_currentUserForSearches = null;

	/**
	 * @param \JobScooper\DataAccess\GeoLocation|null $location
	 *
	 * @return null|string
	 */
	function getGeoLocationSettingType(GeoLocation $location = null)
	{
		return $this->LocationType;
	}

	/**
	 * @return string[]
	 */
	function getSupportedCountryCodes()
	{
		if (empty($this->CountryCodes)) {
			$this->CountryCodes = array("US");
		} else {
			foreach ($this->CountryCodes as $k => $code) {
				if (!empty($code) && array_key_exists(strtoupper($code), GeoLocation::$COUNTRY_CODE_REMAPPINGS))
					$this->CountryCodes[$k] = GeoLocation::$COUNTRY_CODE_REMAPPINGS[$code];
			}
		}

		return $this->CountryCodes;
	}

	/**
	 * @return \Facebook\WebDriver\Remote\RemoteWebDriver
	 * @throws \Exception
	 */
	protected function getActiveWebdriver()
	{
		if (!is_null($this->selenium)) {
			return $this->selenium->get_driver();
		} else
			throw new Exception("Error:  active webdriver for Selenium not found as expected.");
	}

	/**
	 * @param \JobScooper\Utils\SimpleHTMLHelper $objSimpHTML
	 *
	 * @return array|null
	 * @throws \Exception
	 */
	function parseJobsListForPage(SimpleHTMLHelper $objSimpHTML)
	{
		throw new \BadMethodCallException(sprintf("Not implemented method  " . __METHOD__ . " called on class \"%s \".", __CLASS__));

		return null;
	}

	/**
	 * @param $objSimpHTML
	 *
	 * @return int|string|null
	 *
	 */
	function parseTotalResultsCount(\JobScooper\Utils\SimpleHTMLHelper $objSimpHTML)
	{
		throw new \BadMethodCallException(sprintf("Not implemented method " . __METHOD__ . " called on class \"%s \".", __CLASS__));
	}


	/**
	 * @throws \Exception
	 */
	protected function moveDownOnePageInBrowser()
	{

		// Neat trick written up by http://softwaretestutorials.blogspot.in/2016/09/how-to-perform-page-scrolling-with.html.
		$driver = $this->getActiveWebdriver();

		$driver->executeScript("window.scrollTo(0,document.body.scrollHeight);");

		sleep($this->additionalLoadDelaySeconds + 1);

	}

	/**
	 * @param null $nTotalItems
	 *
	 * @throws \Exception
	 */
	protected function goToEndOfResultsSetViaPageDown($nTotalItems = null)
	{
		$this->moveDownOnePageInBrowser();
		$secs = $this->additionalLoadDelaySeconds * 1000;
		if ($secs <= 0)
			$secs = 1000;

		$js = "
            localStorage.setItem('startTime', Date.now());
            localStorage.setItem('prevHeight', 0);
            scroll = setTimeout(gotoPageBottom, 250);
            function getRunTime()
            {
                var startTime = localStorage.getItem('startTime');
                var endTime = Date.now();
                runtime = Math.floor((endTime-startTime)/(1000));
                return runtime;
            }

            function gotoPageBottom() 
            {
                runtime = getRunTime();
                prevHeight = localStorage.getItem('prevHeight');
                
                window.scrollTo(0,document.body.scrollHeight);
                if(prevHeight == null || (prevHeight < document.body.scrollHeight && runtime <= 60))
                {
                    localStorage.setItem('prevHeight', document.body.scrollHeight);
                    setTimeout(gotoPageBottom, " . $secs . ");
                }
                else
                {
                    console.log('Load more button no longer active; done paginating the results.');
                    console.log('Script needed a minimum of ' + runtime + ' seconds to load all the results.');
                    localStorage.removeItem('startTime');
                    localStorage.removeItem('prevHeight');

                }
            }  
        ";


		if (is_null($nTotalItems)) {
			$nTotalItems = $this->nMaxJobsToReturn;
		}

		if ($nTotalItems == C__TOTAL_ITEMS_UNKNOWN__) {
			$nSleepTimeToLoad = 30 + $this->additionalLoadDelaySeconds;
		} else {
			$nSleepTimeToLoad = ($nTotalItems / $this->JobListingsPerPage) * $this->additionalLoadDelaySeconds;
		}

		LogMessage("Sleeping for " . $nSleepTimeToLoad . " seconds to allow browser to page down through all the results");

		$this->runJavaScriptSnippet($js, false);

		sleep($nSleepTimeToLoad > 0 ? $nSleepTimeToLoad : 2);

		$this->moveDownOnePageInBrowser();

	}

	/**
	 * @param $nTotalItems
	 *
	 * @throws \Exception
	 */
	protected function goToEndOfResultsSetViaLoadMore($nTotalItems)
	{
		if (empty($this->selectorMoreListings))
			throw new Exception("Plugin set to paginate via Load More but no selector was set for the load more control on the page.");
		$this->moveDownOnePageInBrowser();
		$secs = $this->additionalLoadDelaySeconds * 1000;
		if ($secs <= 0)
			$secs = 1000;

		$js = "
            scroll = setTimeout(doLoadMore, 250);
            function getRunTime()
            {
                var startTime = localStorage.getItem(\"startTime\");
                var endTime = Date.now();
                runtime = Math.floor((endTime-startTime)/(1000));
                return (runtime + ' seconds');
            }

            function doLoadMore() 
            {
                var startTime = localStorage.getItem(\"startTime\");
                if(startTime == null) 
                {
                    localStorage.setItem(\"startTime\", Date.now());
                    localStorage.setItem(\"pageNum\", 1);
                }

                window.scrollTo(0,document.body.scrollHeight);
                console.log('paged-down-before-click');

                var loadmore = document.querySelector(\"" . $this->selectorMoreListings . "\");
                if(loadmore != null && !typeof(loadmore.click) !== \"function\" && loadmore.length >= 1) {
                    loadmore = loadmore[0];
                } 
    
                runtime = getRunTime();
                if(loadmore != null && loadmore.style.display === \"\") 
                { 
                    var pageNum = parseInt(localStorage.getItem(\"pageNum\"));
                    if (pageNum != null)
                    {   
                        console.log('Results for page # ' + pageNum + ' loaded.  Time spent so far:  ' + runtime + ' Going to next page...');
                        localStorage.setItem(\"pageNum\", pageNum + 1);
                    }
                    loadmore.click();  
                    console.log(\"Clicked load more control...\");
                        
                    scroll = setTimeout(doLoadMore, " . $secs . ");
                    window.scrollTo(0,document.body.scrollHeight);
                    console.log('paged-down-after-click');
                }
                else
                {
                    console.log('Load more button no longer active; done paginating the results.');
                    console.log('Script needed a minimum of ' + runtime + ' seconds to load all the results.');
                    localStorage.removeItem(\"startTime\");

                }
            }  
        ";


		if (is_null($nTotalItems)) {
			$nTotalItems = $this->nMaxJobsToReturn;
		}

		if ($nTotalItems == C__TOTAL_ITEMS_UNKNOWN__) {
			$nSleepTimeToLoad = 30 + $this->additionalLoadDelaySeconds;
		} else {
			$nSleepTimeToLoad = ($nTotalItems / $this->JobListingsPerPage) * $this->additionalLoadDelaySeconds;
		}

		LogMessage("Sleeping for " . $nSleepTimeToLoad . " seconds to allow browser to page down through all the results");

		$this->runJavaScriptSnippet($js, false);

		sleep($nSleepTimeToLoad > 0 ? $nSleepTimeToLoad : 2);

		$this->moveDownOnePageInBrowser();

	}


	/**
	 * @return bool
	 * @throws \Exception
	 */
	protected function goToNextPageOfResultsViaNextButton()
	{
		$secs = $this->additionalLoadDelaySeconds * 1000;
		if ($secs <= 0)
			$secs = 1000;

		$this->log("Clicking button [" . $this->selectorMoreListings . "] to go to the next page of results...");

		$js = "
            scroll = setTimeout(doNextPage, " . $secs . ");
            function doNextPage() 
            {
                var loadnext = document.querySelector(\"" . $this->selectorMoreListings . "\");
                if(loadnext != null && !typeof(loadnext .click) !== \"function\" && loadnext.length >= 1) {
                    loadnext = loadnext[0];
                } 
    
                if(loadnext != null && loadnext.style.display === \"\") 
                { 
                    loadnext.click();  
                    console.log(\"Clicked load next results control " . $this->selectorMoreListings . "...\");
                }
            }  
        ";

		$this->runJavaScriptSnippet($js, false);

		sleep($this->additionalLoadDelaySeconds > 0 ? $this->additionalLoadDelaySeconds : 2);
		$this->log("Page Url is now " . $this->getActiveWebdriver()->getCurrentURL());

		return true;
	}

	/**
	 * @param $var
	 *
	 * @return string|null
	 * @throws \Exception
	 */
	static function splitValue($var)
	{

		if(count($var) < 2)
			throw new \Exception("splitValue was not passed enough callback parameters to continue. " . getArrayDebugOutput($var));

		$val = $var[0];

		if(empty($val))
			return null;

		if(is_array($var[1]) && count($var[1]) >= 2) {
			$delim = $var[1][0];
			$index = $var[1][1];
			$parts = preg_split("/{$delim}/", $val,  -1, PREG_SPLIT_NO_EMPTY);
			if(count($parts) >= $index)
				return $parts[$index];
		}

		return null;
	}


	//************************************************************************
	//
	//
	//
	//  Functions for Adding Searches to Plugin Instance
	//
	//
	//
	//************************************************************************


	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 *
	 * @throws \Exception
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	private function _addSearch_(UserSearchSiteRun $searchDetails)
	{
		$searchDetails->setStartingUrlForSearch();

		$searchDetails->save();

		//
		// Add the search to the list of ones to run
		//
		$this->arrSearchesToReturn[$searchDetails->getUserSearchSiteRunKey()] = $searchDetails;
		$this->log($this->JobSiteName . ": added search (" . $searchDetails->getUserSearchSiteRunKey() . ")");

	}

	/**
	 * @return null|string
	 */
	public function getJobPostingBaseUrl()
	{
		return $this->JobPostingBaseUrl;
	}


	/**
	 * @param $var
	 *
	 * @return string
	 */
	function hashValue($var)
	{
		return md5($var);
	}

	/**
	 * @param $var
	 *
	 * @return string
	 */
	function combineTextAllNodes($var)
	{
		$delim = " ";
		if (count($var) > 1) {
			$var = $var[0];
			$delim = $var[1];
		}

		return combineTextAllNodes($var, $delim);
	}

	/**
	 * @param $var
	 *
	 * @return string
	 */
	protected function combineTextAllChildren($var)
	{
		$recursed = true;
		$delim = "";
		if (count($var) > 1) {
			$param = $var[1];
			$var = $var[0];
		}
		if (!empty($param) && is_array($param)) {
			if (array_key_exists("delimiter", $param))
				$delim = $param['delimiter'];
			if (array_key_exists("recursed", $param))
				$recursed = $param['recursed'];
		}
		return combineTextAllChildren($var, $recursed, $delim);
	}

	/**
	 * @param $var
	 *
	 * @return string
	 */
	protected function parseOneOrMoreLocations($var)
	{
		$reDelim = "[&,;\|~]|(\s+-+)";
//		$reDelim = "[[:punct:]]+";
		if (is_array($var) && count($var) > 1) {
			$param = $var[1];
			$var = $var[0];
		}
		if (!empty($param) && is_array($param)) {
			if (array_key_exists("delimiter", $param))
				$reDelim = $param['delimiter'];
		}

		$splitLocs = preg_split("/{$reDelim}/", $var);
		foreach($splitLocs as $k => $v)
			$splitLocs[$k] = trim($v);
		return join("|~", $splitLocs);
	}


	//************************************************************************
	//
	//
	//
	//  Job listing download methods
	//
	//
	//
	//************************************************************************

	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 *
	 * @throws \Exception
	 */
	private function _updateJobsDataForSearch_(UserSearchSiteRun $searchDetails)
	{
		$ex = null;

		try {

			// get the url for the first page/items in the results
			if ($this->_checkInvalidURL_($searchDetails, $searchDetails->getSearchStartUrl()) == self::VALUE_NOT_SUPPORTED) return;
			startLogSection("Starting data pull for " . $this->JobSiteName . "[" . $searchDetails->getUserSearchSiteRunKey() . "]");

			if ($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__) {
				$this->_getMyJobsForSearchFromJobsAPI_($searchDetails);
			} elseif ($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__) {
				$this->_getMyJobsForSearchFromWebpage_($searchDetails);
			} elseif ($this->pluginResultsType == C__JOB_SEARCH_RESULTS_TYPE_CLIENTSIDE_WEBPAGE__) {
				$this->_getMyJobsForSearchFromWebpage_($searchDetails);
			} else {
				throw new \ErrorException("Class " . get_class($this) . " does not have a valid setting for parser.  Cannot continue.");
			}
		} catch (Exception $ex) {
			$strError = "Failed to download jobs from " . $this->JobSiteName . " jobs for search '" . $searchDetails->getUserSearchSiteRunKey() . "[URL=" . $searchDetails->getSearchStartUrl() . "]. Exception Details: ";
			$this->_setSearchResult_($searchDetails, false, new Exception($strError . strval($ex)));
			handleException($ex, $strError, false);
		} finally {
			endLogSection("Finished data pull for " . $this->JobSiteName . "[" . $searchDetails->getUserSearchSiteRunKey() . "]");
		}

		if (!is_null($ex)) {
			throw $ex;
		}

	}

	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $details
	 * @param                                          $strURL
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function _checkInvalidURL_(UserSearchSiteRun $details, $strURL)
	{
		if ($strURL == null) throw new \ErrorException("Skipping " . $this->JobSiteName . " search '" . $details->getUserSearchSiteRunKey() . "' because a valid URL could not be set.");

		return $strURL;
	}

	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 * @param null                                     $success
	 * @param null                                     $except
	 * @param bool                                     $runWasSkipped
	 * @param SimpleHTMLHelper                         $objPageHtml
	 *
	 * @throws \Propel\Runtime\Exception\PropelException
	 * @throws \Exception
	 */
	private function _setSearchResult_(UserSearchSiteRun $searchDetails, $success = null, $except = null, $runWasSkipped = false, $objPageHtml = null)
	{
		if (empty($searchDetails) || !($searchDetails instanceof UserSearchSiteRun))
			throw new \Exception("Invalid user search site run object passed to method.");

		if (!is_null($runWasSkipped) && is_bool($runWasSkipped) && $runWasSkipped === true) {
			$searchDetails->setRunResultCode("skipped");
		} elseif (!is_null($success) && is_bool($success)) {
			if ($success === true)
				$searchDetails->setRunSucceeded();
			else {
				$searchDetails->failRunWithErrorMessage($except, $objPageHtml);
			}
		}
		$searchDetails->save();
	}


	/**
	 * @param UserSearchSiteRun &$searchDetails
	 * @param string $filePath
	 * @param string $strURL
	 * @param null   $optTimeout
	 * @param null   $referrer
	 * @param null   $cookies
	 *
	 * @return \JobScooper\Utils\SimpleHTMLHelper|null
	 * @throws \Exception
	 */
	function getSimpleObjFromPathOrURL(UserSearchSiteRun &$searchDetails, $filePath = "", $strURL = "", $optTimeout = null, $referrer = null, $cookies = null)
	{
		try {
			if (!empty($strURL))
				$searchDetails->searchResultsPageUrl = $strURL;

			$objSimpleHTML = null;

			if (isDebug() == true) {

				$this->log("URL        = " . $strURL);
				$this->log("Referrer   = " . $referrer);
				$this->log("Cookies    = " . $cookies);
			}

			if (!$objSimpleHTML && ($filePath && strlen($filePath) > 0)) {
				$this->log("Loading ALTERNATE results from " . $filePath);
				$objSimpleHTML = null;
				$this->log("Loading HTML from " . $filePath);

				if (!file_exists($filePath) && !is_file($filePath)) return $objSimpleHTML;
				$fp = fopen($filePath, 'r');
				if (!$fp) return $objSimpleHTML;

				$strHTML = fread($fp, MAX_FILE_SIZE);
				$objSimpleHTML = new SimpleHtmlHelper($strHTML);
				$objSimpleHTML->setSource($filePath);
				fclose($fp);
			}


			if (!$objSimpleHTML && $strURL && strlen($strURL) > 0) {
				if (isDebug()) $this->_curlWrapper->setDebug(true);

				$retObj = $this->_curlWrapper->cURL($strURL, $json = null, $action = 'GET', $content_type = null, $pagenum = null, $onbehalf = null, $fileUpload = null, $secsTimeout = $optTimeout, $cookies = $cookies, $referrer = $referrer);
				if (!is_null($retObj) && array_key_exists("output", $retObj) && strlen($retObj['output']) > 0) {
					$objSimpleHTML = new SimpleHtmlHelper($retObj['output']);
					$objSimpleHTML->setSource($strURL);
					$this->prevCookies = $retObj['cookies'];
					$this->prevURL = $strURL;
				} else {
					$objSimpleHTML = new SimpleHTMLHelper($strURL);
					$objSimpleHTML->setSource($strURL);
				}
			}
			if (!$objSimpleHTML) {
				throw new \Exception("Unable to get SimpleHTMLDom object from " . strlen($filePath) > 0 ? $filePath : $strURL);
			}

			return $objSimpleHTML;
		} catch (Exception $ex) {
			handleException($ex, null, true);
		}

	}


	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 *
	 * @return null|array
	 * @throws \Exception
	 */
	protected function _getMyJobsForSearchFromJobsAPI_(UserSearchSiteRun $searchDetails)
	{
		$nItemCount = 0;

		$this->log("Downloading count of " . $this->JobSiteName . " jobs for search '" . $searchDetails->getUserSearchSiteRunKey() . "'");

		$pageNumber = 1;
		$noMoreJobs = false;
		while ($noMoreJobs != true) {
			$arrPageJobsList = [];
			$apiJobs = $this->getSearchJobsFromAPI($searchDetails);
			if (is_null($apiJobs)) {
				$this->log("Warning: " . $this->JobSiteName . "[" . $searchDetails->getUserSearchSiteRunKey() . "] returned zero jobs from the API." . PHP_EOL, \Monolog\Logger::WARNING);

				return;
			}

			foreach ($apiJobs as $job) {
				$item = getEmptyJobListingRecord();
				$item['Title'] = $job->name;
				$item['JobSitePostId'] = $job->sourceId;
				if ($item['JobSitePostId'] == null)
					$item['JobSitePostId'] = $job->url;

				if (strlen(trim($item['Title'])) == 0 || strlen(trim($item['JobSitePostId'])) == 0) {
					continue;
				}
				$item['Location'] = $job->location;
				$item['Company'] = $job->company;
				if ($job->datePosted != null)
					$item['PostedAt'] = $job->datePosted->format('Y-m-d');
				$item['Url'] = $job->url;

				$strCurrentJobIndex = cleanupSlugPart($this->JobSiteName) . cleanupSlugPart($item['JobSitePostId']);
				$arrPageJobsList[$strCurrentJobIndex] = $item;
				$nItemCount += 1;
			}
			$this->saveSearchReturnedJobs($arrPageJobsList, $searchDetails);
			if (count($arrPageJobsList) < $this->JobListingsPerPage) {
				$noMoreJobs = true;
			}
			$pageNumber++;
		}

		$this->log($this->JobSiteName . "[" . $searchDetails->getUserSearchSiteRunKey() . "]" . ": " . $nItemCount . " jobs found." . PHP_EOL);

	}


	/**
	 * @param string $jscript
	 * @param bool   $wrap_in_func
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	protected function runJavaScriptSnippet($jscript = "", $wrap_in_func = true)
	{
		$driver = $this->getActiveWebdriver();

		if ($wrap_in_func === true) {
			$jscript = "function call_from_php() { " . $jscript . " }; call_from_php();";
		}

		$this->log("Executing JavaScript in browser:  " . $jscript);

			$ret = $driver->executeScript($jscript);

		sleep(5);

		return $ret;
	}

	/**
	 * @param $arrItem
	 *
	 * @throws \Exception
	 * @return array
	 */
	function cleanupJobItemFields($arrItem)
	{
		if ($this->isBitFlagSet(C__JOB_USE_SITENAME_AS_COMPANY)) {
			if (!array_key_exists('Company', $arrItem) || empty($ret['Company']))
				$arrItem['Company'] = $this->getJobSiteKey();
		}

		$keys = array_keys($arrItem);
		foreach ($keys as $key) {
			$arrItem[$key] = cleanupTextValue($arrItem[$key]);
		}

		if (is_null($arrItem['JobSiteKey']) || strlen($arrItem['JobSiteKey']) == 0)
			$arrItem['JobSiteKey'] = $this->JobSiteName;

		$arrItem['JobSiteKey'] = cleanupSlugPart($arrItem['JobSiteKey']);

		$arrItem ['Url'] = trim($arrItem['Url']); // DO NOT LOWER, BREAKS URLS

		try {
			if (empty($arrItem['Url'])) {
				$arrItem['Url'] = "[UNKNOWN]";
			} else {
				$urlParts = parse_url($arrItem['Url']);
				if ($urlParts == false || stristr($urlParts['scheme'], "http") == false) {
					$sep = "";
					if (substr($arrItem['Url'], 0, 1) != "/")
						$sep = "/";
					$arrItem['Url'] = $this->JobPostingBaseUrl . $sep . $arrItem['Url'];
				}
			}
		} catch (\Exception $ex) {
			$this->log($ex->getMessage(), \Monolog\Logger::WARNING);
		}
		if (empty($arrItem['JobSitePostId'])) {
			$urlparts = parse_url($arrItem['url']);
			$arrItem['JobSitePostId'] = $this->hashValue("{$urlparts['path']}?{$urlparts['query']}");
		}

		$arrItem['JobSitePostId'] = preg_replace(REXPR_MATCH_URL_DOMAIN, "", $arrItem['JobSitePostId']);
		$arrItem ['JobSitePostId'] = strScrub($arrItem['JobSitePostId'], FOR_LOOKUP_VALUE_MATCHING);


		if ($this->isBitFlagSet(C__JOB_SUPPORTS_MULTIPLE_LOCS_PER_JOB)) {
			if (array_key_exists("Location", $arrItem)) {
				$splitLocs = preg_split("/\|~/", $arrItem['Location']);
				if (count($splitLocs) > 1) {
					$arrItem["Location"] = array_shift($splitLocs);
					foreach ($splitLocs as $loc) {
						$newJob = array_copy($arrItem);
						$newJob["Location"] = $loc;
						$locSlug = cleanupSlugPart($loc);
						$newJob["JobSitePostId"] = "{$arrItem["JobSitePostId"]}-{$locSlug}";
						updateOrCreateJobPosting($newJob);
					}
				}
			}
		}

		return $arrItem;

	}

	/**
	 * @param                   $arrJobList
	 * @param UserSearchSiteRun $searchDetails
	 * @param int               $nCountNewJobs Returns number of jobs that were new database records.
	 *
	 * @throws \Exception
	 */
	function saveSearchReturnedJobs($arrJobList, UserSearchSiteRun $searchDetails, &$nCountNewJobs = 0)
	{

		if(empty($arrJobList))
			return;

		foreach($arrJobList as $k => $item)
			$arrJobList[$k] = $this->cleanupJobItemFields($item);

		$arrJobsToAdd = array_column($arrJobList, null, "JobSitePostId");
		$siteRunKey = $searchDetails->getUserSearchSiteRunKey();
		try {
			$nCountNewJobs = 0;
			if (!array_key_exists($siteRunKey, $this->arrSearchReturnedJobs))
				$this->arrSearchReturnedJobs[$siteRunKey] = array();

			$arrJobSitePostIds = array_column($arrJobsToAdd, "JobSitePostId", "JobSitePostId");

			$alreadyExist = JobPostingQuery::create()
				->filterByJobSiteKey($this->getJobSiteKey())
				->filterByJobSitePostId($arrJobSitePostIds, Criteria::IN)
				->find()
				->toKeyIndex("JobSitePostId");
			if (!empty($alreadyExist)) {
				$arrExistJobCols = array_from_orm_object_list_by_array_keys($alreadyExist, array("JobPostingId", "JobSitePostId", "FirstSeenAt"), "JobPostingId");
				$arrExistJobSitePostIds = array_column($arrExistJobCols, "JobSitePostId", "JobSitePostId");
				$this->arrSearchReturnedJobs[$siteRunKey] = $this->arrSearchReturnedJobs[$siteRunKey] + $arrExistJobCols;

				$arrJobsToAdd = array_filter($arrJobsToAdd, function ($var) use ($arrExistJobSitePostIds) {
					return !(in_array($var['JobSitePostId'], $arrExistJobSitePostIds));
				});
			}

			if(!empty($arrJobsToAdd)) {
				$conWrite = Propel::getServiceContainer()->getWriteConnection(JobPostingTableMap::DATABASE_NAME);

				$bulkUpsert = new ObjectCollection();
				$bulkUpsert->setModel('\JobScooper\DataAccess\JobPosting');
				$bulkUpsert->fromArray($arrJobsToAdd);
				$bulkUpsert->save($conWrite);

				$insertedJobs = $bulkUpsert->toKeyIndex("JobPostingId");
				$arrJobCols = array_from_orm_object_list_by_array_keys($insertedJobs, array("JobPostingId", "JobSitePostId", "FirstSeenAt"), "JobPostingId");

				$this->arrSearchReturnedJobs[$siteRunKey] = $this->arrSearchReturnedJobs[$siteRunKey] + $arrJobCols;
			}

			$nCountNewJobs = count($arrJobsToAdd);
		} catch (Exception $ex) {
			handleException($ex, "Unable to save job search results to database.", true);
		}
	}

	/**
	 * @param                                          $arrJobIds
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 *
	 * @throws \Exception
	 */
	private function _addJobMatchIdsToUser($arrJobIds, UserSearchSiteRun $searchDetails)
	{
		$userId = $this->_currentUserForSearches->getUserId();

		$alreadyMatched = UserJobMatchQuery::create()
			->filterByUserId($userId)
			->filterByJobPostingId($arrJobIds, Criteria::IN)
			->find()
			->toKeyIndex("JobPostingId");

		$arrTemp = array_combine($arrJobIds, $arrJobIds);
		if(!empty($alreadyMatched))
		{
			$arrIdsToAdd = array_diff($arrJobIds, array_keys($alreadyMatched));
			$arrTemp = array_combine($arrIdsToAdd, $arrIdsToAdd);
		}

		if(!empty($arrTemp)) {
			$arrUserMatches = array_map(function ($value) use ($userId) {
				return array('JobPostingId' => $value, 'UserId' => $userId);
			}, $arrTemp);
			$conWrite = Propel::getServiceContainer()->getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);

			$bulkUpsert = new ObjectCollection();
			$bulkUpsert->setModel('\JobScooper\DataAccess\UserJobMatch');
			$bulkUpsert->fromArray($arrUserMatches);
			$bulkUpsert->save($conWrite);
		}
	}

	/**
	 * /**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 *
	 * @throws \Exception
	 */
	private function _addJobMatchesToUser(UserSearchSiteRun $searchDetails)
	{
		if (array_key_exists($searchDetails->getUserSearchSiteRunKey(), $this->arrSearchReturnedJobs) && !is_null($this->arrSearchReturnedJobs[$searchDetails->getUserSearchSiteRunKey()]) && is_array($this->arrSearchReturnedJobs[$searchDetails->getUserSearchSiteRunKey()]))
			$this->_addJobMatchIdsToUser(array_keys($this->arrSearchReturnedJobs[$searchDetails->getUserSearchSiteRunKey()]), $searchDetails);
	}


	/**
	 * @param \JobScooper\DataAccess\JobPosting[] $arrJobs
	 *
	 * @return array
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	protected function getJobsDbIds($arrJobs)
	{
		$arrIds = array_column($arrJobs, 'JobSitePostId', 'JobSitePostId');
		$queryData = JobPostingQuery::create()
			->select(array("JobPostingId", "JobSitePostId", "JobSiteKey", "KeySiteAndPostId"))
			->filterByJobSiteKey($this->JobSiteName)
			->filterByJobSitePostId(array_values($arrIds))
			->find();
		$jobResults = $queryData->toArray();

		return $jobResults;
	}

	public function getObjectProperties() {
		return $this->getArray();
	}

	public function getArray() {
		return get_object_vars($this);
	}

	/**
	 * @param string $apiUri
	 * @param UserSearchSiteRun &$searchDetails
	 * @param string $hostPageUri
	 *
	 * @return mixed|null
	 * @throws \Exception
	 */
	protected function getJsonApiResult($apiUri, $searchDetails, $hostPageUri)
	{
		if ($this->isBitFlagSet(C__JOB_USE_SELENIUM) && is_null($this->selenium)) {
			try {
				$this->selenium = new SeleniumManager();
			} catch (Exception $ex) {
				handleException($ex, "Unable to start Selenium to get jobs for plugin '" . $this->JobSiteName . "'", true);
			}
		}

		try {
			$driver = $this->getActiveWebdriver();
			$this->log("Getting host page for JSON query {$hostPageUri}");
			$driver->get($hostPageUri);
			$apiNodeId = "jobs_api_data";

			$this->log("Downloading JSON data from {$apiUri} using page at {$hostPageUri} ...");

			$jsCode = /** @lang javascript */ <<<JSCODE
			window.JSCOOP_API_RETURN = null;
			var callback = arguments[arguments.length-1], // webdriver async script callback
			
			nIntervalId; // setInterval id to stop polling

			function checkDone() {
			  if( window.JSCOOP_API_RETURN ) {
			    window.clearInterval(nIntervalId); // stop polling
			    callback(window.JSCOOP_API_RETURN );
			  }
			}


			function setScriptDataObject(jsonText) {
				window.JSCOOP_API_RETURN = jsonText;
				
				API_ELEM_ID = "jobs_api_data";

				var myScriptTag = null;
				try {
					myScriptTag = document.getElementById(API_ELEM_ID);
					myScriptTag.text = jsonText;
				}
				catch (err) {
				}

				if (!myScriptTag) {
					myScriptTag = document.createElement("script");
					var bd = document.getElementsByTagName('body')[0];
					bd.appendChild(myScriptTag);
				}

				myScriptTag.id = API_ELEM_ID;
				myScriptTag.text = jsonText;
			}

			// The parameters we are gonna pass to the fetch function
			fetchData = { 
			    method: 'GET', 
			    headers: new Headers({'Content-Type' : 'application/json'})
			};
			
			fetch('{$apiUri}', fetchData)
				.then( 
					function(resp) {
						setScriptDataObject(resp.text());  
					}
				);
				
				
			nIntervalId = window.setInterval( checkDone, 150 ); // start polling

JSCODE;

			$this->log("Executing JavaScript: ".PHP_EOL ." {$jsCode}");
//			$driver->manage()->timeouts()->setScriptTimeout(30);
			$driver->executeAsyncScript($jsCode);

			$response = $driver->executeScript("return window.JSCOOP_API_RETURN;");
			if (empty($response)) {
				$simpHtml = $this->getSimpleHtmlDomFromSeleniumPage($searchDetails);
				$node = $simpHtml->find("script#{$apiNodeId}");
				if (!empty($node)) {
					$response = $node[0]->text();

				}
			}
			try
			{
				$data= json_decode($response);
			}
			catch (\Exception $ex)
			{
				$data = $response;
			}
			return $data;

		} catch (Exception $ex) {
			$this->log("Failed to download JSON data from API call {$apiUri}.  Error:  " . $ex->getMessage(), \Monolog\Logger::ERROR, $ex);
		}

		return null;
	}

	/**
	 * @param $msg
	 * @param $logLevel
	 * @param array $extras
	 * @param Exception $ex
	 */
	function log($msg, $logLevel=\Monolog\Logger::INFO, $extras=array(), $ex=null)
	{
		LogMessage($msg, $logLevel, $extras, $ex, $channel="plugins");
	}


	/**
	 * @param UserSearchSiteRun &$searchDetails
	 * @param string|null $url
	 * @throws \Exception
	 * @return SimpleHTMLHelper
	 */
	protected function getSimpleHtmlDomFromSeleniumPage(UserSearchSiteRun &$searchDetails, $url=null)
    {
        $objSimpleHTML = null;
        try {
            if(!empty($url))
            {
	            $searchDetails->searchResultsPageUrl = $url;
                $this->getActiveWebdriver()->get($url);
            }

            $this->log("... sleeping " . $this->additionalLoadDelaySeconds . " seconds while the page results load for " . $this->JobSiteName);
            sleep($this>$this->additionalLoadDelaySeconds);

            $html = $this->getActiveWebdriver()->getPageSource();
            $objSimpleHTML = new SimpleHtmlHelper($html);
            $objSimpleHTML->setSource($this->getActiveWebdriver()->getCurrentUrl());
        } catch (Exception $ex) {
            $strError = "Failed to get dynamic HTML via Selenium due to error:  " . $ex->getMessage();
            handleException(new Exception($strError), null, true);
        }
        return $objSimpleHTML;
    }


	/**
	 * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
	 *
	 * @return null
	 * @throws \Exception
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	private function _getMyJobsForSearchFromWebpage_(UserSearchSiteRun $searchDetails)
    {
	    $objSimpleHTML = null;
        try {
            $nItemCount = 1;
            $nPageCount = 1;
	        $this->log("Starting first page load for " . $this->JobSiteName . " job search '" . $searchDetails->getUserSearchSiteRunKey() . "': " . $searchDetails->getSearchStartUrl());


	        if ($this->isBitFlagSet(C__JOB_USE_SELENIUM)) {
                try {
	                if (is_null($this->selenium)) {
		                $this->selenium = new SeleniumManager($this->additionalLoadDelaySeconds);
	                } else {
		                // Close out any previous webdriver sessions before we start anew
		                $this->selenium->done();
	                }

	                if (method_exists($this, "doFirstPageLoad") && $nPageCount == 1) {
		                $html = $this->doFirstPageLoad($searchDetails);
		                if(empty($html) && $this->getActiveWebdriver()->getCurrentURL() === "about:blank")
			                $html = $this->selenium->getPageHTML($searchDetails->getSearchStartUrl());
	                }
                    else
                        $html = $this->selenium->getPageHTML($searchDetails->getSearchStartUrl());
                    $objSimpleHTML = $this->getSimpleHtmlDomFromSeleniumPage($searchDetails);
                } catch (Exception $ex) {
                    $strError = "Failed to get dynamic HTML via Selenium due to error:  " . $ex->getMessage();
                    handleException(new Exception($strError), null, true, $extraData=$searchDetails->toLoggedContext());
                }
            } else {
                $objSimpleHTML = $this->getSimpleObjFromPathOrURL($searchDetails,null, $searchDetails->getSearchStartUrl(), $this->secsPageTimeout, $referrer = $this->prevURL, $cookies = $this->prevCookies);
            }
            if (!$objSimpleHTML) {
                throw new \ErrorException("Error:  unable to get SimpleHTML object for " . $searchDetails->getSearchStartUrl());
            }

            $totalPagesCount = C__TOTAL_ITEMS_UNKNOWN__;
            $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__; // placeholder because we don't know how many are on the page
            if ($this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) && $this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__)) {
                switch ($this->PaginationType) {

                    case C__PAGINATION_INFSCROLLPAGE_NOCONTROL:
                    case C__PAGINATION_INFSCROLLPAGE_PAGEDOWN:
                    case C__PAGINATION_INFSCROLLPAGE_VIALOADMORE:
                    case C__PAGINATION_PAGE_VIA_NEXTBUTTON:
                    case C__PAGINATION_INFSCROLLPAGE_VIA_JS:
                    case C__PAGINATION_PAGE_VIA_CALLBACK:
                        $totalPagesCount = C__TOTAL_ITEMS_UNKNOWN__;
                        $nTotalListings = C__TOTAL_ITEMS_UNKNOWN__;
                        break;

                    default:
                        // if we can't get a number of pages AND we can't get a number of items,
                        // we must assume there is, at most, only one page of results.
                        $totalPagesCount = 1;
                        $nTotalListings = $this->JobListingsPerPage;
                        break;
                }
            }

	        $this->log("Getting count of " . $this->JobSiteName . " jobs for search '" . $searchDetails->getUserSearchSiteRunKey() . "': " . $searchDetails->getSearchStartUrl());

	        if (!$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) || !$this->isBitFlagSet(C__JOB_PAGECOUNT_NOTAPPLICABLE__)) {
                $strTotalResults = $this->parseTotalResultsCount($objSimpleHTML);
                $nTotalListings = intval(str_replace(",", "", $strTotalResults));
                if ($nTotalListings == 0) {
                    $totalPagesCount = 0;
                } elseif ($nTotalListings != C__TOTAL_ITEMS_UNKNOWN__) {
                    if ($nTotalListings > $this->nMaxJobsToReturn) {
                        $this->log("Search '" . $searchDetails->getUserSearchSiteRunKey() . "' returned more results than allowed.  Only retrieving the first " . $this->nMaxJobsToReturn . " of  " . $nTotalListings . " job listings.", \Monolog\Logger::WARNING);
                        $nTotalListings = $this->nMaxJobsToReturn;
                    }
                    $totalPagesCount = intceil($nTotalListings / $this->JobListingsPerPage); // round up always
                    if ($totalPagesCount < 1) $totalPagesCount = 1;
                }
            }

            if ($nTotalListings <= 0) {
                $this->log("No new job listings were found on " . $this->JobSiteName . " for search '" . $searchDetails->getUserSearchSiteRunKey() . "'.");
                return array();
            } else {
                $nJobsFound = 0;

                $this->log("Querying " . $this->JobSiteName . " for " . $totalPagesCount . " pages with " . ($nTotalListings == C__TOTAL_ITEMS_UNKNOWN__ ? "an unknown number of" : $nTotalListings) . " jobs:  " . $searchDetails->getSearchStartUrl());

                $strURL = $searchDetails->getSearchStartUrl();
                $searchDetails->searchResultsPageUrl = $strURL;
                while ($nPageCount <= $totalPagesCount) {

                    $arrPageJobsList = null;

                    //
                    // First, if this is an infinite page or a single page of listings, we
                    // need to make the calls to load the full results set into the page HTML
                    // We do this only for certain pagination types (INFSCROLLPAGE)
                    //
                    if ($this->isBitFlagSet(C__JOB_USE_SELENIUM)) {
                        try {
                            switch (strtoupper($this->PaginationType)) {

                                case C__PAGINATION_NONE:
                                    $totalPagesCount = 1;
                                    $this->selenium->loadPage($strURL);
                                    break;

                                case C__PAGINATION_INFSCROLLPAGE_PAGEDOWN:
                                    $this->selenium->loadPage($strURL);
                                    //
                                    // If we dont know how many pages to go down,
                                    // call the method to go down to the very end so we see the whole page
                                    // and whole results set
                                    //
                                    $this->goToEndOfResultsSetViaPageDown($nTotalListings);
                                    $totalPagesCount = 1;
                                    break;

                                case C__PAGINATION_INFSCROLLPAGE_VIALOADMORE:
                                    $this->selenium->loadPage($strURL);
                                    //
                                    // If we dont know how many pages to go down,
                                    // call the method to go down to the very end so we see the whole page
                                    // and whole results set
                                    //
                                    $this->goToEndOfResultsSetViaLoadMore($nTotalListings);
                                    $totalPagesCount = 1;
                                    break;

                                case C__PAGINATION_INFSCROLLPAGE_NOCONTROL:
                                    $this->selenium->loadPage($strURL);
                                    //
                                    // if we know how many pages to do do, call the page down method
                                    // until we get to the right number of pages
                                    //
                                    while ($nPageCount <= $totalPagesCount) {
                                        if (isDebug() == true) {
                                            $this->log("... getting infinite results page #" . $nPageCount . " of " . $totalPagesCount);
                                        }
                                        $this->moveDownOnePageInBrowser();
                                        $nPageCount = $nPageCount + 1;
                                    }
                                    $totalPagesCount = $nPageCount;
                                    break;

                                case C__PAGINATION_INFSCROLLPAGE_VIA_JS:
                                    if (is_null($this->nextPageScript)) {
                                        throw new Exception("Plugin " . $this->JobSiteName . " is missing nextPageScript settings for the defined pagination type.");

                                    }
                                    $this->selenium->loadPage($strURL);

                                    if ($nPageCount > 1 && $nPageCount <= $totalPagesCount) {
                                        $this->runJavaScriptSnippet($this->nextPageScript, true);
                                        sleep($this->additionalLoadDelaySeconds + 1);
                                    }
                                    break;
                            }

                            $objSimpleHTML = $this->getSimpleHtmlDomFromSeleniumPage($searchDetails);

                        } catch (Exception $ex) {
                            handleException($ex, "Failed to get dynamic HTML via Selenium due to error:  %s", true, $extraData=$searchDetails->toLoggedContext());
                        }
                    } else {
                        $strURL = $this->setResultPageUrl($searchDetails, $nPageCount, $nItemCount);
                        if ($this->_checkInvalidURL_($searchDetails, $strURL) == self::VALUE_NOT_SUPPORTED)
                            return null;

                        $objSimpleHTML = $this->getSimpleObjFromPathOrURL($searchDetails, null, $strURL, $this->secsPageTimeout, $referrer = $this->prevURL, $cookies = $this->prevCookies);
                    }
                    if (!$objSimpleHTML) {
                        throw new \ErrorException("Error:  unable to get SimpleHTML object for " . $strURL);
                    }

                    $this->log("Getting jobs page # " . $nPageCount . " of " . $totalPagesCount . " from " . $strURL . ".  Total listings loaded:  " . ($nItemCount == 1 ? 0 : $nItemCount) . "/" . $nTotalListings . ".");
                    try {
						$arrJsonLDJobs = $this->parseJobsFromLdJson($objSimpleHTML);

                        $arrPageJobsList = $this->parseJobsListForPage($objSimpleHTML);
                        if (!is_array($arrPageJobsList)) {
                            // we likely hit a page where jobs started to be hidden.
                            // Go ahead and bail on the loop here
                            $strWarnHiddenListings = "Could not get all job results back from " . $this->JobSiteName . " for this search starting on page " . $nPageCount . ".";
                            if ($nPageCount < $totalPagesCount)
                                $strWarnHiddenListings .= "  They likely have hidden the remaining " . ($totalPagesCount - $nPageCount) . " pages worth. ";

                            $this->log($strWarnHiddenListings);
                            $nPageCount = $totalPagesCount;
                        }
                        else {
	                        if (!empty($arrJsonLDJobs)) {
		                        foreach ($arrPageJobsList as $k => $v) {
			                        if (!empty($v) && array_key_exists("JobSitePostId", $v) && array_key_exists($v["JobSitePostId"], $arrJsonLDJobs))
				                        $arrPageJobsList[$k] = array_merge($v, $arrJsonLDJobs[$v["JobSitePostId"]]);
		                        }
		                        unset($arrJsonLDJobs);
	                        }
                        }

                        if (is_array($arrPageJobsList)) {

	                        $arrPreviouslyLoadedJobs = $this->arrSearchReturnedJobs[$searchDetails->getUserSearchSiteRunKey()];
	                        if(!empty($arrPreviouslyLoadedJobs)) {
		                        $arrPreviouslyLoadedJobSiteIds = array_column($arrPreviouslyLoadedJobs, 'JobSitePostId');
		                        $newJobThisPage = array_diff(array_column($arrPageJobsList, 'JobSitePostId'), $arrPreviouslyLoadedJobSiteIds);
		                        if (empty($newJobThisPage)) {
			                        $site = $this->getJobSiteKey();
			                        throw new Exception("{$site} returned the same jobs for page {$nPageCount}.  We likely aren't paginating successfully to new results; aborting to prevent infinite results parsing.");
		                        }
	                        }

                            $nCountNewJobsInDb = 0;
	                        $cntPageJobsReturned = countAssociativeArrayValues($arrPageJobsList);
                            $this->saveSearchReturnedJobs($arrPageJobsList, $searchDetails, $nCountNewJobsInDb);
                            $nJobsFound = count($this->arrSearchReturnedJobs[$searchDetails->getUserSearchSiteRunKey()]);

                            if ($nItemCount == 1) {
                                $nItemCount = 0;
                            }
                            $nItemCount += ($nJobsFound < $this->JobListingsPerPage) ? $nJobsFound : $this->JobListingsPerPage;



                            // If we don't know the total number of listings we will get, we can guess that we've got them all
                            // if we did not get the max number of job listings from the last page.  Basically, if we couldn't
                            // fill up a page with our search, then they must not be that many listings avaialble.
                            //
                            if ($totalPagesCount > 1 && $nTotalListings == C__TOTAL_ITEMS_UNKNOWN__ && $cntPageJobsReturned  < $this->JobListingsPerPage) {
                                $totalPagesCount = $nPageCount;
                                $nTotalListings = countAssociativeArrayValues($this->arrSearchReturnedJobs[$searchDetails->getUserSearchSiteRunKey()]);
                            }

                            $this->log("Loaded " . countAssociativeArrayValues($this->arrSearchReturnedJobs[$searchDetails->getUserSearchSiteRunKey()]) . " of " . $nTotalListings . " job listings from " . $this->JobSiteName);


                            //
                            // PERFORMANCE OPTIMIZATION
                            //
                            // If we returned a page where all jobs were the jobs were seen before in the database
                            // and the site always returns jobs in date descending order, then we can assume we will
                            // only download more jobs we already know about and can skip the rest of them.
                            //
                            if($nCountNewJobsInDb === 0 &&
                                $this->isBitFlagSet(C__JOB_RESULTS_SHOWN_IN_DATE_DESCENDING_ORDER) &&
                                $nJobsFound < $nTotalListings)
                            {
                                $this->log("All {$cntPageJobsReturned} job listings downloaded for this page have been seen before.  Skipping remaining job downloads since they are likely to be repeats.");
                                return;

                            }

                        }
                    } catch (Exception $ex) {

                    	# Attempt to save the jobs we already had grabbed before the error occurred
	                    try
	                    {
	                    	$this->saveSearchReturnedJobs($arrPageJobsList, $searchDetails, $nCountNewJobsInDb);
	                    }
	                    catch (Exception $tempEx)
	                    {
	                    	// do nothing here since we already had a previous error we are handling.
	                    }

                        throw $ex;
                    }

                    //
                    // Look check for plugin errors that are not caught.  If we have looped through one page of results,
                    // we should either have returned at least 1 listing of the total count OR if we have retrieved fewer
                    // listings than are expected on a page, then we should our page count should be the same as the last page.
                    //
                    // If either is not true, then we're likely in an error condition and about to go a bit wacky, possibly in a major loop.
                    // Throw an error for this search instead and move on.
                    //
                    $err = null;
                    $marginOfErrorAllowed = .05;
                    if ($nTotalListings > 0 && $nItemCount == 0) // We got zero listings but should have found some
                        $err = "Retrieved 0 of the expected " . $nTotalListings . " listings for " . $this->JobSiteName . " (search = " . $searchDetails->getUserSearchSiteRunKey() . ")";
                    elseif ($nItemCount < $this->JobListingsPerPage && $nPageCount < $totalPagesCount)
                        $err = "Retrieved only " . $nItemCount . " of the " . $this->JobListingsPerPage . " job listings on page " . $nPageCount . " for " . $this->JobSiteName . " (search = " . $searchDetails->getUserSearchSiteRunKey() . ")";
                    elseif ($nJobsFound < $nTotalListings * (1 - $marginOfErrorAllowed) && $nPageCount == $totalPagesCount && !$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__))
                        $err = "Retrieved only " . $nJobsFound . " of the " . $nTotalListings . " listings that we expected for " . $this->JobSiteName . " (search = " . $searchDetails->getUserSearchSiteRunKey() . ")";
                    elseif ($nJobsFound > $nTotalListings * (1 + $marginOfErrorAllowed) && $nPageCount == $totalPagesCount && !$this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__)) {
                        $warnMsg = "Warning:  Downloaded " . ($nJobsFound - $nTotalListings) . " jobs more than the " . $nTotalListings . " expected for " . $this->JobSiteName . " (search = " . $searchDetails->getUserSearchSiteRunKey() . ")";
                        $this->log($warnMsg, \Monolog\Logger::WARNING);
                    }

                    if (!is_null($err)) {
                        if ($this->isBitFlagSet(C__JOB_IGNORE_MISMATCHED_JOB_COUNTS) || $this->isBitFlagSet(C__JOB_ITEMCOUNT_NOTAPPLICABLE__) === true) {
                            $this->log("Warning: " . $err, \Monolog\Logger::WARNING);
                        } else {
                            $err = "Error: " . $err . "  Aborting job site plugin to prevent further errors.";
                            $this->log($err, \Monolog\Logger::ERROR);
                            handleException(new Exception($err), null, true, $extraData=$searchDetails->toLoggedContext());
                        }
                    }

                    $nPageCount++;

                    //
                    // OK, we're done loading the results set from that page.  Now we need to
                    // move the browser session to the next page of results. (Unless we were on
                    // an infinite scroll page, if we were, then there isn't another page to load.)
                    //
                    if ($this->isBitFlagSet(C__JOB_USE_SELENIUM)) {
                        try {
                            switch (strtoupper($this->PaginationType)) {
                                case C__PAGINATION_PAGE_VIA_URL:
                                    $strURL = $this->setResultPageUrl($searchDetails, $nPageCount, $nItemCount);
                                    if (empty($strURL)) {
	                                    throw(new Exception("Plugin " . $this->JobSiteName . " did not generate url for next page result."));
                                    }
                                    $this->selenium->loadPage($strURL);
                                    break;

                                case C__PAGINATION_PAGE_VIA_NEXTBUTTON:
                                    if (is_null($this->selectorMoreListings)) {
                                        throw(new Exception("Plugin " . $this->JobSiteName . " is missing selectorMoreListings setting for the defined pagination type."));

                                    }
                                    $this->selenium->loadPage($strURL);

                                    if ($nPageCount > 1 && ($totalPagesCount == C__TOTAL_ITEMS_UNKNOWN__ || $nPageCount <= $totalPagesCount)) {
                                        $ret = $this->goToNextPageOfResultsViaNextButton();
                                        if ($ret == false)
                                            $totalPagesCount = $nPageCount;
                                    }
	                                $strURL = $this->getActiveWebdriver()->getCurrentURL();
                                    break;

                                case C__PAGINATION_PAGE_VIA_CALLBACK:
                                    if (!method_exists($this, 'takeNextPageAction')) {
                                        throw new Exception("Plugin " . $this->JobSiteName . " is missing takeNextPageAction method definiton required for its pagination type.");
                                    }

                                    if ($nPageCount > 1 && $nPageCount <= $totalPagesCount) {
                                        //
                                        // if we got a driver instance back, then we got a new page
                                        // otherwise we're out of results so end the loop here.
                                        //
                                        try {
                                            $this->takeNextPageAction($searchDetails->getItemURLValue($nItemCount), $searchDetails->getPageURLValue($nPageCount));
                                            sleep($this->additionalLoadDelaySeconds + 2);
                                        } catch (Exception $ex) {
                                            handleException($ex, ("Failed to take nextPageAction on page " . $nPageCount . ".  Error:  %s"), true, $extraData=$searchDetails->toLoggedContext());
                                        }
                                    }
	                                $strURL = $this->getActiveWebdriver()->getCurrentURL();
                                    break;

                            }
	                        unset($objSimpleHTML);

                        } catch (Exception $ex) {
                            handleException($ex, "Failed to get dynamic HTML via Selenium due to error:  %s", true, $extraData=$searchDetails->toLoggedContext());
                        }
                        finally
                        {
	                        unset($arrPageJobsList);
                        }
                    }
                }

            }

            $this->log($this->JobSiteName . "[" . $searchDetails->getUserSearchSiteRunKey() . "]" . ": " . $nJobsFound . " jobs found." . PHP_EOL);

        } catch (Exception $ex) {
            $this->_setSearchResult_($searchDetails, false, $ex, false, $objSimpleHTML);
            handleException($ex, null, true, $extraData=$searchDetails->toLoggedContext());
	        $this->log("Failed to download new job postings for search run " . $searchDetails->getUserSearchSiteRunKey() . ".  Continuing to next search.   Error details: " . $ex->getMessage(), \Monolog\Logger::WARNING);
        }
        finally
        {
	        unset($objSimpleHTML);
        }

        return null;
    }

	/**
	 * @param $searchDetails
	 */
	protected function getSearchJobsFromAPI($searchDetails)
    {
        throw new \BadMethodCallException(sprintf("Not implemented method " . __METHOD__ . " called on class \"%s \".", __CLASS__));
    }

	/**
	 * @param $searchDetails
	 * @param $nPageCount
	 * @param $nItemCount
	 *
	 * @return null|string
	 * @throws \Exception
	 */
	protected function setResultPageUrl($searchDetails, $nPageCount, $nItemCount)
    {
	    $searchDetails->searchResultsPageUrl = $searchDetails->getPageURLfromBaseFmt($nPageCount, $nItemCount);
	    if ($this->_checkInvalidURL_($searchDetails, $searchDetails->searchResultsPageUrl) == self::VALUE_NOT_SUPPORTED)
		    return $searchDetails->searchResultsPageUrl;

	    return $searchDetails->searchResultsPageUrl;
    }



	/**
	 * /**
	 * getJobsFromLdJson
	 *
	 * This does the heavy lifting of parsing job records from
	 * LD+JSON found in the page
	 *
	 * @param $objSimpHTML
	 * @throws \Exception
	 */
	function parseJobsFromLdJson($objSimpHTML)
	{
		$ret = array();

		if(empty($objSimpHTML) || !method_exists($objSimpHTML, "find"))
			return null;

		$jsonNodes = $objSimpHTML->find("script[type='application/ld+json']");
		if(!empty($jsonNodes) && is_array($jsonNodes)) {
			$item = array();
			foreach ($jsonNodes as $node) {
				$jsonText = $node->text();
				try
				{
					$jsonData = decodeJSON($jsonText);
					if(!empty($jsonData) && is_array($jsonData))
					{
						if(!array_key_exists("@type",$jsonData) || $jsonData["@type"] != "JobPosting")
							return null;

						foreach($jsonData as $key => $value) {
							switch ($key)
							{
								case "datePosted":
									$item['PostedAt'] = $value;
									break;

								case "@id":
									$item['JobSitePostId'] = $value;
									break;

								case "title":
									$item['Title'] = $value;
									break;

								case "occupationalCategory":
									$item['Category'] = $value;
									break;

								case "hiringOrganization":
									if(array_key_exists("name",$value))
										$item['Company'] = $value['name'];
									break;

								case "jobLocation":
									if(array_key_exists(0, $value))
										$value = $value[0];
									if(array_key_exists("@type", $value) && $value["@type"] === "Place" &&
										array_key_exists("address", $value))
									{
										$address = $value["address"];
										if(array_key_exists("addressLocality", $address) && $address["addressLocality"] != "not set")
											$item['Location'] = $address["addressLocality"];

										if(array_key_exists("addressRegion", $address) && $address["addressRegion"] != "not set")
											$item['Location'] .= " " . $address["addressRegion"];

										if(array_key_exists("addressCountry", $address) && $address["addressCountry"] != "not set")
											$item['Location'] .= " " . $address["addressCountry"];
									}
									break;
							}
						}
						$ret[$item['JobSitePostId']] = $item;

					}

				} catch (Exception $ex)
				{
					$this->log("Error parsing LD+JSON for " . $this->getJobSiteKey() . ": " . $ex->getMessage(), \Monolog\Logger::DEBUG);
				}
			}
		}
		return $ret;
	}


}


