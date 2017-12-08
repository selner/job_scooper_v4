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
use JobScooper\Utils\SimpleCSV;
use Propel\Runtime\ActiveQuery\Criteria;


class JobsAutoMarker
{
    protected $JobSiteName = "JobsAutoMarker";
    protected $arrLatestJobs_UnfilteredByUserInput = array();
    protected $arrMasterJobList = array();
    protected $_locmgr = null;
	protected $title_negative_keyword_tokens = null;
	protected $companies_regex_to_filter = null;

    function __construct($arrJobObjsToMark = array(), $strOutputDirectory = null)
    {
        if (!is_null($arrJobObjsToMark) && count($arrJobObjsToMark) > 0)
            $this->arrMasterJobList = $arrJobObjsToMark;

        $this->_locmgr = LocationManager::getLocationManager();

    }

    function __destruct()
    {
        LogLine("Closing ".$this->JobSiteName." instance of class " . get_class($this), \C__DISPLAY_ITEM_DETAIL__);

    }

    public function markJobs()
    {
        if (is_null($this->arrMasterJobList) || count($this->arrMasterJobList) <= 0)
            $this->arrMasterJobList = getAllMatchesForUserNotification();

        if(is_null($this->arrMasterJobList) || count($this->arrMasterJobList) <= 0)
        {
            LogLine("No new jobs found to auto-mark.", C__DISPLAY_WARNING__);
        }
        else
        {

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //
            // Filter the full jobs list looking for duplicates, etc.
            //
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            LogLine(PHP_EOL . "**************  Updating jobs list for known filters ***************" . PHP_EOL, \C__DISPLAY_NORMAL__);

            $arrJobs_AutoUpdatable = $this->arrMasterJobList;
            $this->_markJobsList_SearchKeywordsFound_($arrJobs_AutoUpdatable);

            $this->_markJobsList_SetLikelyDuplicatePosts_($arrJobs_AutoUpdatable);

            $this->_markJobsList_SetOutOfArea_($arrJobs_AutoUpdatable);

            $this->_markJobsList_UserExcludedKeywords_($arrJobs_AutoUpdatable);

            $this->_markJobsList_SetAutoExcludedCompaniesFromRegex_($arrJobs_AutoUpdatable);

        }
    }

    public function getMarkedJobs()
    {
        return $this->arrMasterJobList;
    }

    function _markJobsList_SetLikelyDuplicatePosts_(&$arrJobsList)
    {
        try
        {
            if(count($arrJobsList) == 0) return;
            $nJobDupes= 0;
            $nNonDupes = 0;

            LogLine("Gathering job postings that are already marked as duplicate...", \C__DISPLAY_ITEM_DETAIL__);
            $arrDupeMatches = array_filter($arrJobsList, function($v) {
                $posting = $v->getJobPostingFromUJM();
                return (!is_null($posting->getDuplicatesJobPostingId()));
            });

            $nJobDupes = count($arrDupeMatches);
            $arrRemainingJobs = array_diff_assoc($arrJobsList, $arrDupeMatches);

            LogLine("Finding and Marking Duplicate Job Roles" , \C__DISPLAY_ITEM_START__);
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

            LogLine($nJobDupes. "/" . countAssociativeArrayValues($arrJobsList) . " jobs have been marked as duplicate based on company/role pairing. " , \C__DISPLAY_ITEM_RESULT__);
        }
        catch (Exception $ex)
        {
            handleException($ex, "Error in SetLikelyDuplicatePosts: %s", true);
        }
    }

    private function _isGeoSpatialWorking()
    {
        try {
            loadSqlite3MathExtensions();
            LogLine("Successfully loaded the necessary math functions for SQLite to do geospatial filtering.", C__DISPLAY_NORMAL__);
            return true;

        } catch (\Exception $ex) {
            LogLine("Failed to load the necessary math functions for SQLite to do geospatial filtering.  Falling back to county-level instead.", C__DISPLAY_WARNING__);
        }

        return false;
    }

    private function _markJobsList_SetOutOfArea_(&$arrJobsList)
    {
        if (count($arrJobsList) == 0) return;

        LogLine("Marking Out of Area Jobs", \C__DISPLAY_ITEM_START__);

        if ($this->_isGeoSpatialWorking()) {
            $this->_markJobsList_OutOfArea_Geospatial($arrJobsList);
        }
        else {
            $this->_markJobsList_OutOfArea_CountyFiltered($arrJobsList);
        }
    }

    private function _markJobsList_OutOfArea_CountyFiltered(&$arrJobsList)
    {
        $searchLocations = getConfigurationSettings('search_locations');

        $arrIncludeCounties = array();

        /* Find all locations that are within 50 miles of any of our search locations */

        LogLine("Auto-marking postings not in same counties as the search locations...", \C__DISPLAY_ITEM_DETAIL__);
        foreach($searchLocations as $searchloc)
        {
            if(!empty($searchloc))
            {
                    $arrIncludeCounties[] = $searchloc->getCounty() . "~" .$searchloc->getRegion();
            }
        }

        LogLine("Finding job postings not in the following counties & states: " . getArrayValuesAsString($arrIncludeCounties) . " ...", \C__DISPLAY_ITEM_DETAIL__);
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

        LogLine("Marking user job matches as out of area for " . count($arrJobsOutOfArea) . " matches ...", \C__DISPLAY_ITEM_DETAIL__);

        foreach ($arrJobsOutOfArea as $jobOutofArea) {
            $jobOutofArea->setOutOfUserArea(true);
            $jobOutofArea->save();
        }


        $nJobsMarkedAutoExcluded = count($arrJobsOutOfArea);
        $nJobsNotMarked = count($arrJobsList) - $nJobsMarkedAutoExcluded;


        LogLine("Jobs excluded as out of area: ". $nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) ." marked; " . $nJobsNotMarked . "/" . countAssociativeArrayValues($arrJobsList).", not marked " , \C__DISPLAY_ITEM_RESULT__);
    }

    private function _markJobsList_OutOfArea_Geospatial(&$arrJobsList)
    {
        $searchLocations = getConfigurationSettings('search_locations');

        $arrNearbyIds = array();

        /* Find all locations that are within 50 miles of any of our search locations */

        LogLine("Getting locationIDs within 50 miles of search locations...", \C__DISPLAY_ITEM_DETAIL__);
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

        LogLine("Gathering job postings not in those areas...", \C__DISPLAY_ITEM_DETAIL__);
        $arrJobsOutOfArea = array_filter($arrJobsList, function($v) use ($arrNearbyIds) {
            $posting = $v->getJobPostingFromUJM;
            $locId = $posting->getGeoLocationId();
            if(is_null($locId))
                return true;  // if we don't have a location, assume nearby

            return in_array($locId, $arrNearbyIds);
        });

        LogLine("Marking user job matches as out of area for " . count($arrJobsOutOfArea) . " matches ...", \C__DISPLAY_ITEM_DETAIL__);

        foreach ($arrJobsOutOfArea as $jobOutofArea) {
            $jobOutofArea->setOutOfUserArea(true);
            $jobOutofArea->save();
        }


        $nJobsMarkedAutoExcluded = count($arrJobsOutOfArea);
        $nJobsNotMarked = count($arrJobsList) - $nJobsMarkedAutoExcluded;


       LogLine("Jobs excluded as out of area: ". $nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) ." marked; " . $nJobsNotMarked . "/" . countAssociativeArrayValues($arrJobsList).", not marked " , \C__DISPLAY_ITEM_RESULT__);
    }

    private function _markJobsList_SetAutoExcludedCompaniesFromRegex_(&$arrJobsList)
    {
        //
        // Load the exclusion filter and other user data from files
        //
        $this->_loadCompanyRegexesToFilter_();

        $nJobsSkipped = 0;
        $nJobsMarkedAutoExcluded = 0;
        $nJobsNotMarked = 0;

        try
        {
            if(count($arrJobsList) == 0 || is_null($this->companies_regex_to_filter) || count($this->companies_regex_to_filter) == 0) return;

            LogLine("Excluding Jobs by Companies Regex Matches", \C__DISPLAY_ITEM_START__);
            LogLine("Checking ".count($arrJobsList) ." roles against ". count($this->companies_regex_to_filter) ." excluded companies.", \C__DISPLAY_ITEM_DETAIL__);

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

            LogLine("Jobs marked with excluded companies: ".$nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) ." marked as excluded; not marked ". $nJobsNotMarked . "/" . countAssociativeArrayValues($arrJobsList) , \C__DISPLAY_ITEM_RESULT__);
        }
        catch (Exception $ex)
        {
            handleException($ex, "Error in SetAutoExcludedCompaniesFromRegex: %s", true);
        }
    }



    private function _markJobsList_UserExcludedKeywords_(&$arrJobsList)
    {
        //
        // Load the exclusion filter and other user data from files
        //
        $this->_loadTitlesTokensToFilter_();

        $nJobsSkipped = 0;
        $nJobsMarkedAutoExcluded = 0;
        $nJobsNotMarked = 0;

        try
        {
            if(count($arrJobsList) == 0 || is_null($this->title_negative_keyword_tokens) || count($this->title_negative_keyword_tokens) == 0) return;

            $usrSearchKeywords = $this->_getUserSearchTitleKeywords();
            $negKeywords = array_diff_assoc($this->title_negative_keyword_tokens, array_values($usrSearchKeywords) );
            LogLine("Excluding Jobs by Negative Title Keyword Token Matches", \C__DISPLAY_ITEM_START__);
            LogLine("Checking ".count($arrJobsList) ." roles against ". count($negKeywords) ." negative title keywords to be excluded.", \C__DISPLAY_ITEM_DETAIL__);

            try {
                foreach ($arrJobsList as $jobMatch) {
                    $strJobTitleTokens = $jobMatch->getJobPostingFromUJM()->getTitleTokens();
                    $arrTitleTokens = preg_split("/[\s|\|]/", $strJobTitleTokens);
	                $matchedNegTokens = array_intersect($arrTitleTokens, $negKeywords);
                    if (!empty($matchedNegTokens)) {
                        $jobMatch->setMatchedNegativeTitleKeywords($matchedNegTokens);
                        $jobMatch->save();
                        $nJobsMarkedAutoExcluded += 1;
                    }
                    else
                        $nJobsNotMarked += 1;
                }
            } catch (Exception $ex) {
                handleException($ex, 'ERROR:  Failed to verify titles against negative keywords due to error: %s', isDebug());
            }
            LogLine("Processed " . countAssociativeArrayValues($arrJobsList) . " titles for auto-marking against negative title keywords: ". $nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) . " marked excluded; " . $nJobsNotMarked. "/" . countAssociativeArrayValues($arrJobsList) . " not marked.", \C__DISPLAY_ITEM_RESULT__);
        }
        catch (Exception $ex)
        {
            handleException($ex, "Error in SearchKeywordsNotFound: %s", true);
        }

    }

    private function _getUserSearchTitleKeywords()
    {
	    $keywordTokens = array();
        $keywordSets = getConfigurationSetting("user_keyword_sets");
	    foreach($keywordSets as $kwdset)
	    {
	    	$setKwdTokens = $kwdset->getKeywordTokens();
		    $keywordTokens = array_merge($keywordTokens, $setKwdTokens);
	    }
//        $keywordTokenSets = flattenWithKeys(array_column($keywordSets, "keywords_array_tokenized"));
//        $keywordTokens = preg_split("/ /", join(" ", array_values($keywordTokenSets)));
        return $keywordTokens;
    }

    private function _markJobsList_SearchKeywordsFound_(&$arrJobsList)
    {
        $nJobsMarkedInclude = 0;
        $nJobsNotMarked = 0;

        try {
            $usrSearchKeywords = $this->_getUserSearchTitleKeywords();
            if (count($arrJobsList) == 0 || is_null($usrSearchKeywords)) return null;

            LogLine("Checking " . count($arrJobsList) . " roles against " . count($usrSearchKeywords) . " keyword phrases in titles...", \C__DISPLAY_ITEM_DETAIL__);

            try {
                foreach ($arrJobsList as $jobMatch) {
                    $strJobTitleTokens = $jobMatch->getJobPostingFromUJM()->getTitleTokens();
                    $jobId = $jobMatch->getJobPostingId();
                    if(is_null($strJobTitleTokens) || strlen($strJobTitleTokens) == 0 )
                        throw new Exception("Cannot match user search keywords against job title token.  JobTitleTokens column for job_posting id#{$jobId} is null.");
                    $foundAllUserKeywords = in_string_array($strJobTitleTokens, $usrSearchKeywords);
                    if ($foundAllUserKeywords !== false) {
                        $jobMatch->setMatchedUserKeywords($usrSearchKeywords);
                        $jobMatch->save();
                        $nJobsMarkedInclude += 1;
                    }
                    if($foundAllUserKeywords !== true)
                        $nJobsNotMarked += 1;
                }
            } catch (Exception $ex) {
                handleException($ex, 'ERROR:  Failed to verify titles against keywords due to error: %s', isDebug());
            }
            LogLine("Processed " . countAssociativeArrayValues($arrJobsList) . " titles for auto-marking against search title keywords: " . $nJobsMarkedInclude . "/" . count($arrJobsList) . " marked as matches; " . $nJobsNotMarked . "/" . count($arrJobsList) . " not marked.", \C__DISPLAY_ITEM_RESULT__);
        }
        catch (Exception $ex)
        {
            handleException($ex, "Error in SearchKeywordsNotFound: %s", true);
        }

    }



    private function _loadTitlesTokensToFilter_()
    {
	    $inputfiles = getConfigurationSetting("user_data_files.negative_title_keywords");

	    $this->title_negative_keyword_tokens = array();

        if(isset($this->title_negative_keyword_tokens) && count($this->title_negative_keyword_tokens) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            LogDebug("Using previously loaded " . countAssociativeArrayValues($this->title_negative_keyword_tokens) . " tokenized title strings to exclude." , \C__DISPLAY_ITEM_DETAIL__);
            return;
        }

        if(!is_array($inputfiles))
        {
            // No files were found, so bail
            LogDebug("No input files were found with title token strings to exclude." , \C__DISPLAY_ITEM_DETAIL__);
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
	    $this->title_negative_keyword_tokens = array_unique($arrNegKwds, SORT_REGULAR);

        $arrTitlesTemp = tokenizeSingleDimensionArray($this->title_negative_keyword_tokens, 'userNegKwds', 'negative_keywords', 'negative_keywords');

        if(count($arrTitlesTemp) <= 0)
        {
            LogLine("Warning: No title negative keywords were found in the input source files " . getArrayValuesAsString($inputfiles) . " to be filtered from job listings." , \C__DISPLAY_WARNING__);
        }
        else
        {
            //
            // Add each title we found in the file to our list in this class, setting the key for
            // each record to be equal to the job title so we can do a fast lookup later
            //
            foreach($arrTitlesTemp as $titleRecord)
            {
                $tokens = explode("|", $titleRecord['negative_keywordstokenized']);
                $this->title_negative_keyword_tokens[] = $tokens;
            }
	
            LogLine("Loaded " . countAssociativeArrayValues($this->title_negative_keyword_tokens) . " tokens to use for filtering titles from '" . getArrayValuesAsString($inputfiles) . "'." , \C__DISPLAY_ITEM_RESULT__);

        }


    }


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
            $GLOBALS['logger']->logLine($ex->getMessage(), \C__DISPLAY_ERROR__);
            if(isDebug() == true) { throw $ex; }
        }
        return $rx;
    }



    /**
     * Initializes the global list of titles we will automatically mark
     * as "not interested" in the final results set.
     */
    function _loadCompanyRegexesToFilter_()
    {
        if(isset($this->companies_regex_to_filter) && count($this->companies_regex_to_filter) > 0)
        {
            // We've already loaded the companies; go ahead and return right away
            LogDebug("Using previously loaded " . count($this->companies_regex_to_filter) . " regexed company strings to exclude." , \C__DISPLAY_ITEM_DETAIL__);
            return;
        }
	    $inputfiles = getConfigurationSetting("user_data_files.regex_filter_companies");

        if(!isset($inputfiles) ||  !is_array($inputfiles)) { return; }

	    $regexList = array();
        foreach($inputfiles as $fileItem) {
	        LogDebug("Loading job Company regexes to filter from " . $inputfiles . ".", \C__DISPLAY_ITEM_DETAIL__);
	        $classCSVFile = new SimpleCSV($fileItem, 'r');
	        $loadedCompaniesRegex= $classCSVFile->readAllRecords(true, array('match_regex'));
	        $regexList = array_merge($regexList, array_column($loadedCompaniesRegex['data_rows'], "match_regex"));
	        LogDebug(count($loadedCompaniesRegex) . " companies found in the source file that will be automatically filtered from job listings.", \C__DISPLAY_ITEM_DETAIL__);
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
                    LogError($strError, \C__DISPLAY_ERROR__);
                    if(isDebug() == true) { throw new \ErrorException( $strError); }
                }
            }
        }

        if(count($inputfiles) == 0)
            LogDebug("No file specified for companies regexes to exclude from '" . getArrayValuesAsString($inputfiles) . "'.  Final list will not be filtered." , \C__DISPLAY_WARNING__);
        elseif(empty($this->companies_regex_to_filter))
            LogDebug("Could not load regex list for companies to exclude from '" . getArrayValuesAsString($inputfiles) . "'.  Final list will not be filtered." , \C__DISPLAY_WARNING__);

        LogLine("Loaded " . count($this->companies_regex_to_filter). " regexes to use for filtering companies from " . getArrayValuesAsString($inputfiles)  , \C__DISPLAY_NORMAL__);
    }
}