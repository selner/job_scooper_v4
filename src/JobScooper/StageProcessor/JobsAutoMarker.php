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
namespace JobScooper\StageProcessor;

use JobScooper\DataAccess\GeoLocationQuery;
use JobScooper\DataAccess\Map\GeoLocationTableMap;
use Exception;
use JobScooper\Manager\LocationManager;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Class JobsAutoMarker
 * @package JobScooper\StageProcessor
 */
class JobsAutoMarker
{
    protected $JobSiteName = "JobsAutoMarker";
    protected $arrLatestJobs_UnfilteredByUserInput = array();
    protected $arrMasterJobList = array();
    protected $_locmgr = null;
	protected $title_negative_keyword_tokens = null;
	protected $companies_regex_to_filter = null;

	/**
	 * JobsAutoMarker constructor.
	 *
	 * @param array $arrJobObjsToMark
	 * @param null  $strOutputDirectory
	 */
	function __construct($arrJobObjsToMark = array(), $strOutputDirectory = null)
    {
        if (!is_null($arrJobObjsToMark) && count($arrJobObjsToMark) > 0)
            $this->arrMasterJobList = $arrJobObjsToMark;

        $this->_locmgr = LocationManager::getLocationManager();

    }

	/**
	 *
	 * @throws \Exception
	 */
	public function markJobs()
    {
        if (empty($this->arrMasterJobList))
            $this->arrMasterJobList = getAllMatchesForUserNotification();

        if(is_null($this->arrMasterJobList) || count($this->arrMasterJobList) <= 0)
        {
            LogMessage("No new jobs found to auto-mark.");
        }
        else
        {

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //
            // Filter the full jobs list looking for duplicates, etc.
            //
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            LogMessage(PHP_EOL . "**************  Updating jobs list for known filters ***************" . PHP_EOL);

            $arrJobs_AutoUpdatable = $this->arrMasterJobList;
            $this->_markJobsList_SearchKeywordsFound_($arrJobs_AutoUpdatable);

            $this->_markJobsList_SetLikelyDuplicatePosts_($arrJobs_AutoUpdatable);

            $this->_markJobsList_SetOutOfArea_($arrJobs_AutoUpdatable);

            $this->_markJobsList_UserExcludedKwdsViaQuery_($arrJobs_AutoUpdatable);

            $this->_markJobsList_SetAutoExcludedCompaniesFromRegex_($arrJobs_AutoUpdatable);

        }
    }

	/**
	 * @return array of UserJobMatch
	 */
	public function getMarkedJobs()
    {
        return $this->arrMasterJobList;
    }

	/**
	 * @param $arrJobsList
	 *
	 * @throws \Exception
	 */
	function _markJobsList_SetLikelyDuplicatePosts_(&$arrJobsList)
    {
        try
        {
            if(count($arrJobsList) == 0) return;
            $nJobDupes= 0;
            $nNonDupes = 0;

            LogMessage("Gathering job postings that are already marked as duplicate...");
            $arrDupeMatches = array_filter($arrJobsList, function($v) {
                $posting = $v->getJobPostingFromUJM();
                return (!is_null($posting->getDuplicatesJobPostingId()));
            });

            $nJobDupes = count($arrDupeMatches);
            $arrRemainingJobs = array_diff_assoc($arrJobsList, $arrDupeMatches);

            LogMessage("Finding and Marking Duplicate Job Roles" );
            foreach($arrRemainingJobs as $jobMatch)
            {
                $posting = $jobMatch->getJobPostingFromUJM();
                $dupeId = $posting->checkAndMarkDuplicatePosting();
                if(!is_null($dupeId) && $dupeId !== false)
                {
                    $nJobDupes += 1;
                    $posting->save();
                }
                else
                    $nNonDupes += 1;

            }

            LogMessage($nJobDupes. "/" . countAssociativeArrayValues($arrJobsList) . " jobs have been marked as duplicate based on company/role pairing. " );
        }
        catch (Exception $ex)
        {
            handleException($ex, "Error in SetLikelyDuplicatePosts: %s", true);
        }
    }

	/**
	 * @return bool
	 */
	private function _isGeoSpatialWorking()
    {
	    $sqlType = \Propel\Runtime\Propel::getServiceContainer()->getAdapterClass();
	    switch($sqlType)
	    {
		    case "mysql":
			    return true;
				break;

		    default:
		    return false;
			    break;

		    case "sqlite":
			    try {
				    $ret = loadSqlite3MathExtensions();
				    if($ret)
					    LogMessage("Successfully loaded the necessary math functions for SQLite to do geospatial filtering.");
				    return $ret;

			    } catch (\Exception $ex) {
				    LogWarning("Failed to load the necessary math functions for SQLite to do geospatial filtering.  Falling back to county-level instead.");
			    }
			    break;
	    }

        return false;
    }

	/**
	 * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
	 */
	private function _markJobsList_SetOutOfArea_(&$arrJobsList)
    {
        if (count($arrJobsList) == 0) return;

        LogMessage("Marking Out of Area Jobs");

        if ($this->_isGeoSpatialWorking()) {
            $this->_markJobsList_OutOfArea_Geospatial($arrJobsList);
        }
        else {
            $this->_markJobsList_OutOfArea_CountyFiltered($arrJobsList);
        }
    }

	/**
	 * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
	 */
    private function _markJobsList_OutOfArea_CountyFiltered(&$arrJobsList)
    {
        $searchLocations = getConfigurationSetting('search_locations');

        $arrIncludeCounties = array();

        /* Find all locations that are within 50 miles of any of our search locations */

        LogMessage("Auto-marking postings not in same counties as the search locations...");
        foreach($searchLocations as $searchloc)
        {
            if(!empty($searchloc))
            {
                    $arrIncludeCounties[] = $searchloc->getCounty() . "~" .$searchloc->getRegion();
            }
        }

        LogMessage("Finding job postings not in the following counties & states: " . getArrayValuesAsString($arrIncludeCounties) . " ...");
        $arrJobsOutOfArea = array_filter($arrJobsList, function($v) use ($arrIncludeCounties) {
            $posting = $v->getJobPostingFromUJM();
            $locId = $posting->getGeoLocationId();
            if(is_null($locId))
                return false;  // if we don't have a location, assume nearby

            $location = $posting->getGeoLocationFromJP();
            $county = $location->getCounty();
            $state = $location->getRegion();
            if(!is_null($county) && !is_null($state)) {
                $match = $county . "~" . $state;
                if (!in_array($match, $arrIncludeCounties))
                    return true;
            }
            return false;
        });

        LogMessage("Marking user job matches as out of area for " . count($arrJobsOutOfArea) . " matches ...");

        foreach ($arrJobsOutOfArea as $jobOutofArea) {
            $jobOutofArea->setOutOfUserArea(true);
            $jobOutofArea->save();
        }


        $nJobsMarkedAutoExcluded = count($arrJobsOutOfArea);
        $nJobsNotMarked = count($arrJobsList) - $nJobsMarkedAutoExcluded;


        LogMessage("Jobs excluded as out of area: marked ". $nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) .";  not marked " . $nJobsNotMarked . " / " . countAssociativeArrayValues($arrJobsList) );
    }

	/**
	 * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
	 * @throws \Exception
	 */
	private function _markJobsList_OutOfArea_Geospatial(&$arrJobsList)
    {
        $searchLocations = getConfigurationSetting('search_locations');

        $arrNearbyIds = array();

        /* Find all locations that are within 50 miles of any of our search locations */

        LogMessage("Getting locationIDs within 50 miles of search locations...");
        foreach($searchLocations as $searchloc)
        {
            if(!empty($searchloc))
            {
                $nearbyLocations = GeoLocationQuery::create()
                    ->filterByDistanceFrom($searchloc->getLatitude(), $searchloc->getLongitude(), 50, GeoLocationTableMap::MILES_UNIT, Criteria::LESS_THAN)
                    ->find();

                if(!empty($nearbyLocations))
                {
                    foreach($nearbyLocations as $near)
                        $arrNearbyIds[] = $near->getGeoLocationId();
                }
            }
        }

        LogMessage("Gathering job postings not in those areas...");
        $arrJobsOutOfArea = array_filter($arrJobsList, function($v) use ($arrNearbyIds) {
	        $posting = $v->getJobPostingFromUJM();
	        if (!empty($posting)) {
		        $locId = $posting->getGeoLocationId();
		        if (is_null($locId))
			        return true;  // if we don't have a location, assume nearby

		        return in_array($locId, $arrNearbyIds);
	        }
        });

        LogMessage("Marking user job matches as out of area for " . count($arrJobsOutOfArea) . " matches ...");

        foreach ($arrJobsOutOfArea as $jobOutofArea) {
            $jobOutofArea->setOutOfUserArea(true);
            $jobOutofArea->save();
        }


        $nJobsMarkedAutoExcluded = count($arrJobsOutOfArea);
        $nJobsNotMarked = count($arrJobsList) - $nJobsMarkedAutoExcluded;


       LogMessage("Jobs excluded as out of area:  marked ". $nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) ."; not marked = " . $nJobsNotMarked . "/" . countAssociativeArrayValues($arrJobsList) );
    }

	/**
	 * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
	 */
	private function _markJobsList_SetAutoExcludedCompaniesFromRegex_(&$arrJobsList)
    {
        //
        // Load the exclusion filter and other user data from files
        //
        $this->_loadCompanyRegexesToFilter();

        $nJobsSkipped = 0;
        $nJobsMarkedAutoExcluded = 0;
        $nJobsNotMarked = 0;

        try
        {
            if(count($arrJobsList) == 0 || is_null($this->companies_regex_to_filter) || count($this->companies_regex_to_filter) == 0) return;

            LogMessage("Excluding Jobs by Companies Regex Matches");
            LogMessage("Checking ".count($arrJobsList) ." roles against ". count($this->companies_regex_to_filter) ." excluded companies.");

            foreach ($arrJobsList as $jobMatch) {
                $matched_exclusion = false;
                foreach($this->companies_regex_to_filter as $rxInput )
                {
                    if(preg_match($rxInput, strScrub($jobMatch->getJobPostingFromUJM()->getCompany(), DEFAULT_SCRUB)))
                    {
                        $jobMatch->setMatchedNegativeCompanyKeywords(array($rxInput));
                        $jobMatch->save();
                        $nJobsMarkedAutoExcluded++;
                        $matched_exclusion = true;
                        break;
                    }
                }

                if($matched_exclusion !== true)
                    $nJobsNotMarked += 1;
            }

            LogMessage("Jobs marked with excluded companies: ".$nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) ." marked as excluded; not marked ". $nJobsNotMarked . "/" . countAssociativeArrayValues($arrJobsList) );
        }
        catch (Exception $ex)
        {
            handleException($ex, "Error in SetAutoExcludedCompaniesFromRegex: %s", true);
        }
    }


	/**
	 * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
	 */
	private function _markJobsList_UserExcludedKeywords_(&$arrJobsList)
	{
		//
		// Load the exclusion filter and other user data from files
		//
		$this->_loadTitlesTokensToFilter();

		$nJobsSkipped = 0;
		$nJobsMarkedAutoExcluded = 0;
		$nJobsNotMarked = 0;

		try
		{
			if(count($arrJobsList) == 0 || is_null($this->title_negative_keyword_tokens) || count($this->title_negative_keyword_tokens) == 0) return;

			LogMessage("Excluding Jobs by Negative Title Keyword Token Matches");
			LogMessage("Checking ".count($arrJobsList) ." roles against ". count($this->title_negative_keyword_tokens) ." negative title keywords to be excluded.");

			try {
				$nCounter = 1;
				$nCounterEnd = $nCounter + 9;
				foreach(array_chunk($this->title_negative_keyword_tokens, 10) as $subsetNegKwds)
				{
					LogMessage("Checking negative keywords {$nCounter}-{$nCounterEnd}...");
					$arrJobsWithTokenMatches = getAllMatchesForUserNotification(null, $subsetNegKwds);
					foreach($arrJobsWithTokenMatches as $jobMatch)
					{
						$jobMatch->setMatchedNegativeTitleKeywords($subsetNegKwds);
						$jobMatch->setIsExcluded(true);
						$jobMatch->save();
						$arrJobsMarkedNegMatch[$jobMatch->getJobPostingId()] = $jobMatch->getJobPostingFromUJM()->getTitleTokens();
					}
					$nCounter = $nCounterEnd;
					$nCounterEnd = $nCounterEnd + count($subsetNegKwds);
				}
				$nJobsMarkedAutoExcluded = countAssociativeArrayValues($arrJobsMarkedNegMatch);
				$nJobsNotMarked = countAssociativeArrayValues($arrJobsList) - countAssociativeArrayValues($arrJobsMarkedNegMatch);
			} catch (Exception $ex) {
				handleException($ex, 'ERROR:  Failed to verify titles against negative keywords due to error: %s', isDebug());
			}
			LogMessage("Processed " . countAssociativeArrayValues($arrJobsList) . " titles for auto-marking against negative title keywords: ". $nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) . " marked excluded; " . $nJobsNotMarked. "/" . countAssociativeArrayValues($arrJobsList) . " not marked.");
		}
		catch (Exception $ex)
		{
			handleException($ex, "Error in SearchKeywordsNotFound: %s", true);
		}

	}


	/**
	 * @return array
	 */
	private function _getUserSearchTitleKeywords()
    {
	    $keywordTokens = array();
        $keywordSets = getConfigurationSetting("user_keyword_sets");
	    foreach($keywordSets as $kwdset)
	    {
	    	$setKwdTokens = $kwdset->getKeywordTokens();
		    $keywordTokens = array_merge($keywordTokens, $setKwdTokens);
	    }
        return $keywordTokens;
    }

	/**
	 * @param \JobScooper\DataAccess\UserJobMatch[] $arrJobsList
	 */
    private function _markJobsList_SearchKeywordsFound_(&$arrJobsList)
    {
        $nJobsMarkedInclude = 0;
        $nJobsNotMarked = 0;

        try {
            $usrSearchKeywords = $this->_getUserSearchTitleKeywords();
            if (count($arrJobsList) == 0 || is_null($usrSearchKeywords)) return null;

            LogMessage("Checking " . count($arrJobsList) . " roles against " . count($usrSearchKeywords) . " keyword phrases in titles...");

            try {
	            $arrJobsWithTokenMatches = getAllMatchesForUserNotification(null, $usrSearchKeywords);
				foreach($arrJobsWithTokenMatches as $jobMatch)
				{
					$jobMatch->setIsJobMatch(true);
					$jobMatch->setMatchedUserKeywords($usrSearchKeywords);
					$jobMatch->save();
				}
				$arrNotMatchedJobs = array_diff_key($arrJobsList,$arrJobsWithTokenMatches);
				foreach($arrNotMatchedJobs as $notMatchedJob)
				{
					$notMatchedJob->setIsJobMatch(false);
					$notMatchedJob->save();
				}
	            $nJobsMarkedInclude = countAssociativeArrayValues($arrJobsWithTokenMatches);
	            $nJobsNotMarked = countAssociativeArrayValues($arrNotMatchedJobs);
            } catch (Exception $ex) {
                handleException($ex, 'ERROR:  Failed to verify titles against keywords due to error: %s', isDebug());
            }
            LogMessage("Processed " . countAssociativeArrayValues($arrJobsList) . " titles for auto-marking against search title keywords: " . $nJobsMarkedInclude . "/" . count($arrJobsList) . " marked as matches; " . $nJobsNotMarked . "/" . count($arrJobsList) . " not marked.");
        }
        catch (Exception $ex)
        {
            handleException($ex, "Error in SearchKeywordsNotFound: %s", true);
        }


    }


	/**
	 * @throws \Exception
	 */
	private function _loadTitlesTokensToFilter()
    {
	    $inputfiles = getConfigurationSetting("user_data_files.negative_title_keywords");

	    $this->title_negative_keyword_tokens = array();

        if(isset($this->title_negative_keyword_tokens) && count($this->title_negative_keyword_tokens) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            LogDebug("Using previously loaded " . countAssociativeArrayValues($this->title_negative_keyword_tokens) . " tokenized title strings to exclude." );
            return;
        }

        if(!is_array($inputfiles))
        {
            // No files were found, so bail
            LogDebug("No input files were found with title token strings to exclude." );
            return;
        }

        $arrNegKwds = array();

        foreach($inputfiles as $fileItem)
        {

            $arrRecs = loadCSV($fileItem);
            foreach($arrRecs as $arrRec)
            {
                if(array_key_exists('negative_keywords', $arrRec)) {
                    $kwd = strtolower($arrRec['negative_keywords']);
                    $arrNegKwds[$kwd] = $kwd;
                }
            }
        }
	    $negKwdForTokens = array_unique($arrNegKwds, SORT_REGULAR);

        $arrTitlesTemp = tokenizeSingleDimensionArray($negKwdForTokens, 'userNegKwds', 'negative_keywords', 'negative_keywords');

        if(count($arrTitlesTemp) <= 0)
        {
            LogWarning("Warning: No title negative keywords were found in the input source files " . getArrayValuesAsString($inputfiles) . " to be filtered from job listings." );
        }
        else
        {

	        //
            // Add each title we found in the file to our list in this class, setting the key for
            // each record to be equal to the job title so we can do a fast lookup later
            //
	        $negKeywords = array();
	        foreach($arrTitlesTemp as $titleRecord)
            {
                $tokens = explode("|", $titleRecord['negative_keywordstokenized']);
                $negKeywords[] = $tokens;
            }

	        //
	        // override the negativity of any keywords that are actually also our specific search terms
	        //
	        $usrSearchKeywords = $this->_getUserSearchTitleKeywords();
	        $this->title_negative_keyword_tokens = array_diff(array_values($negKeywords), array_values($usrSearchKeywords) );

            LogMessage("Loaded " . countAssociativeArrayValues($this->title_negative_keyword_tokens) . " tokens to use for filtering titles from '" . getArrayValuesAsString($inputfiles) . "'." );

        }

    }


	/**
	 * @param $pattern
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function _scrubRegexSearchString($pattern)
    {
        $delim = '~';
        if(strpos($pattern, $delim) != false)
        {
            $delim = '|';
        }

        $rx = $delim.preg_quote(trim($pattern), $delim).$delim.'i';
        try
        {
            $testMatch = preg_match($rx, "empty");
        }
        catch (\Exception $ex)
        {
            LogError($ex->getMessage());
            if(isDebug() == true) { throw $ex; }
        }
        return $rx;
    }



    /**
     * Initializes the global list of titles we will automatically mark
     * as "not interested" in the final results set.
     */
    function _loadCompanyRegexesToFilter()
    {
        if(isset($this->companies_regex_to_filter) && count($this->companies_regex_to_filter) > 0)
        {
            // We've already loaded the companies; go ahead and return right away
            LogDebug("Using previously loaded " . count($this->companies_regex_to_filter) . " regexed company strings to exclude." );
            return;
        }
	    $inputfiles = getConfigurationSetting("user_data_files.regex_filter_companies");

        if(!isset($inputfiles) ||  !is_array($inputfiles)) { return; }

	    $regexList = array();
        foreach($inputfiles as $fileItem) {
	        LogDebug("Loading job Company regexes to filter from " . $inputfiles . ".");
	        $loadedCompaniesRegex = loadCSV($fileItem, "match_regex");
	        if (!empty($loadedCompaniesRegex)) {
		        //	        $classCSVFile = new SimpleCSV($fileItem, 'r');
		        //	        $loadedCompaniesRegex= $classCSVFile->readAllRecords(true, array('match_regex'));
		        $regexList = array_merge($regexList, array_column($loadedCompaniesRegex, "match_regex"));
		        LogDebug(count($loadedCompaniesRegex) . " companies found in the file {$fileItem} that will be automatically filtered from job listings.");
	        }
        }
	    $regexList = array_unique($regexList);

        //
        // Add each Company we found in the file to our list in this class, setting the key for
        // each record to be equal to the job Company so we can do a fast lookup later
        //
        if(!empty($regexList) && is_array($regexList))
        {
            foreach($regexList as $rxItem)
            {
                try
                {
                    $rx = $this->_scrubRegexSearchString($rxItem);
                    $this->companies_regex_to_filter[] = $rx;

                }
                catch (\Exception $ex)
                {
                    $strError = "Regex test failed on company regex pattern " . $rxItem .".  Skipping.  Error: '".$ex->getMessage();
                    LogError($strError);
                    if(isDebug() == true) { throw new \ErrorException( $strError); }
                }
            }
        }

        if(count($inputfiles) == 0)
            LogDebug("No file specified for companies regexes to exclude from '" . getArrayValuesAsString($inputfiles) . "'.  Final list will not be filtered." );
        elseif(empty($this->companies_regex_to_filter))
            LogDebug("Could not load regex list for companies to exclude from '" . getArrayValuesAsString($inputfiles) . "'.  Final list will not be filtered." );

        LogMessage("Loaded " . count($this->companies_regex_to_filter). " regexes to use for filtering companies from " . getArrayValuesAsString($inputfiles)  );
    }
}