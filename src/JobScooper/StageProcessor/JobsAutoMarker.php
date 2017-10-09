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


use JobScooper\DataAccess\LocationQuery;
use JobScooper\DataAccess\Map\LocationTableMap;

use Exception;
use Propel\Runtime\ActiveQuery\Criteria;


class JobsAutoMarker
{
    protected $siteName = "JobsAutoMarker";
    protected $arrLatestJobs_UnfilteredByUserInput = array();
    protected $arrMasterJobList = array();

    function __construct($arrJobObjsToMark = array(), $strOutputDirectory = null)
    {
        if (!is_null($arrJobObjsToMark) && count($arrJobObjsToMark) > 0)
            $this->arrMasterJobList = $arrJobObjsToMark;

        $this->normalizer = new Normalize();

    }

    function __destruct()
    {
        LogLine("Closing ".$this->siteName." instance of class " . get_class($this), \C__DISPLAY_ITEM_DETAIL__);

    }

    public function markJobs()
    {
        if (is_null($this->arrMasterJobList) || count($this->arrMasterJobList) <= 0)
            $this->arrMasterJobList = getAllUserMatchesNotNotified();

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

            //BUGBUG/TODO DEBUG AND PUT BACK
            //        $this->_markJobsList_SetLikelyDuplicatePosts_($arrJobs_AutoUpdatable);

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

            $origJobMatchForEachCompanyRole = array();
            $dupeJobMatchesForEachCompanyRole = array();

            LogLine("Finding Duplicate Job Roles" , \C__DISPLAY_ITEM_START__);
            foreach($arrJobsList as $job)
            {
                $indexKey = $job->getJobPosting()->getKeySiteAndPostID();
                $compKey = $job->getJobPosting()->getKeyCompanyAndTitle();
                if(!array_key_exists($compKey, $origJobMatchForEachCompanyRole))
                    $origJobMatchForEachCompanyRole[$compKey] = $job;
                else
                    $dupeJobMatchesForEachCompanyRole[$indexKey] = $job;
            }

            LogLine("Marking jobs as duplicate..." , \C__DISPLAY_ITEM_DETAIL__);

            foreach($dupeJobMatchesForEachCompanyRole as $dupeJobMatch)
            {
                $compKey = $dupeJobMatch->getJobPosting()->getKeyCompanyAndTitle();

                //
                // Add a note to the previous listing that it had a new duplicate
                //
                $origJobMatch = $origJobMatchForEachCompanyRole[$compKey];
                $origJobMatch->updateMatchNotes($this->getNotesWithDupeIDAdded($origJobMatch->getMatchNotes(), $dupeJobMatch->getJobPosting()->getKeySiteAndPostID()));
                $origJobMatch->save();

                //
                // Add a note to the duplicate listing that tells user which is the original post
                //
                $dupeJob = $dupeJobMatch->getJobPosting();
//                $dupeJob->setDuplicatesJobPostingId($origJobMatch->getJobPostingId());
                $dupeJobMatch->setUserMatchStatus("exclude-match");
                $dupeJobMatch->setUserMatchReason(C__STR_TAG_DUPLICATE_POST__);
                $dupeJobMatch->updateMatchNotes($this->getNotesWithDupeIDAdded($dupeJobMatch->getMatchNotes(), $origJobMatch->getJobPosting()->getKeySiteAndPostID() ));
                $dupeJobMatch->save();
            }
            
            LogLine(count($dupeJobMatchesForEachCompanyRole). "/" . countAssociativeArrayValues($arrJobsList) . " jobs have immediately been marked as duplicate based on company/role pairing. " , \C__DISPLAY_ITEM_RESULT__);

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
        $searchLocations = getConfigurationSettings('search_location');

        $arrIncludeCounties = array();

        /* Find all locations that are within 50 miles of any of our search locations */

        LogLine("Auto-marking postings not in same counties as the search locations...", \C__DISPLAY_ITEM_DETAIL__);
        foreach($searchLocations as $searchLocation)
        {
            $locLookup = $searchLocation['location_name_lookup'];
            if(!is_null($locLookup))
            {
                $location = $locLookup->getLocation();
                if(!is_null($location))
                {
                    $arrIncludeCounties[] = array("county" => $location->getCounty(), "state" => $location->getState());
                }
            }
        }

        LogLine("Gathering job postings not in the following counties & states ...", \C__DISPLAY_ITEM_DETAIL__);
        $arrJobsOutOfArea = array_filter($arrJobsList, function($v) use ($arrIncludeCounties) {
            $posting = $v->getJobPosting();
            $locId = $posting->getLocationId();
            if(is_null($locId))
                return true;  // if we don't have a location, assume nearby

            $location = $posting->getLocation();
            $county = $location->getCounty();
            $state = $location->getState();
            if(!is_null($county) && in_array($county, array_column($arrIncludeCounties, "county")) && in_array($state, array_column($arrIncludeCounties, "state")))
                return true;
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
        $searchLocations = getConfigurationSettings('search_location');

        $arrNearbyIds = array();

        /* Find all locations that are within 50 miles of any of our search locations */

        LogLine("Getting locationIDs within 50 miles of search locations...", \C__DISPLAY_ITEM_DETAIL__);
        foreach($searchLocations as $searchLocation)
        {
            $locLookup = $searchLocation['location_name_lookup'];
            if(!is_null($locLookup))
            {
                $location = $locLookup->getLocation();
                if(!is_null($location))
                {
                    $nearbyLocations = LocationQuery::create()
                        ->filterByDistanceFrom($location->getLatitude(), $location->getLongitude(), 50, LocationTableMap::MILES_UNIT, Criteria::LESS_THAN)
                        ->find();

                    if(!is_null($nearbyLocations))
                    {
                        foreach($nearbyLocations as $near)
                            $arrNearbyIds[] = $near->getLocationId();
                    }
                }
            }
        }

        LogLine("Gathering job postings not in those areas...", \C__DISPLAY_ITEM_DETAIL__);
        $arrJobsOutOfArea = array_filter($arrJobsList, function($v) use ($arrNearbyIds) {
            $posting = $v->getJobPosting();
            $locId = $posting->getLocationId();
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
        $nJobsSkipped = 0;
        $nJobsMarkedAutoExcluded = 0;
        $nJobsNotMarked = 0;

        try
        {
            if(count($arrJobsList) == 0 || is_null($GLOBALS['USERDATA']['companies_regex_to_filter']) || count($GLOBALS['USERDATA']['companies_regex_to_filter']) == 0) return;

            LogLine("Excluding Jobs by Companies Regex Matches", \C__DISPLAY_ITEM_START__);
            LogLine("Checking ".count($arrJobsList) ." roles against ". count($GLOBALS['USERDATA']['companies_regex_to_filter']) ." excluded companies.", \C__DISPLAY_ITEM_DETAIL__);

            foreach ($arrJobsList as $jobMatch) {
                $matched_exclusion = false;
                foreach($GLOBALS['USERDATA']['companies_regex_to_filter'] as $rxInput )
                {
                    if(preg_match($rxInput, strScrub($jobMatch->getJobPosting()->getCompany(), DEFAULT_SCRUB)))
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
        $nJobsSkipped = 0;
        $nJobsMarkedAutoExcluded = 0;
        $nJobsNotMarked = 0;

        try
        {
            if(count($arrJobsList) == 0 || is_null($GLOBALS['USERDATA']['title_negative_keyword_tokens']) || count($GLOBALS['USERDATA']['title_negative_keyword_tokens']) == 0) return;

            $usrSearchKeywords = $this->_getUserSearchTitleKeywords();
            $negKeywords = array_diff_assoc($GLOBALS['USERDATA']['title_negative_keyword_tokens'], array_values($usrSearchKeywords) );
            LogLine("Excluding Jobs by Negative Title Keyword Token Matches", \C__DISPLAY_ITEM_START__);
            LogLine("Checking ".count($arrJobsList) ." roles against ". count($negKeywords) ." negative title keywords to be excluded.", \C__DISPLAY_ITEM_DETAIL__);

            try {
                foreach ($arrJobsList as $jobMatch) {
                    $foundNegKeywordItem = false;
                    $strJobTitleTokens = $jobMatch->getJobPosting()->getTitleTokens();
                    foreach ($negKeywords as $negKeywordItem) {
                        $foundNegKeywordItem = in_string_array($strJobTitleTokens, $negKeywordItem);
                        if ($foundNegKeywordItem === true) {
                            $jobMatch->setMatchedNegativeTitleKeywords($negKeywordItem);
                            $jobMatch->save();
                            $nJobsMarkedAutoExcluded += 1;
                            break;
                        }
                    }
                    if($foundNegKeywordItem !== true)
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
    private function getNotesWithDupeIDAdded($strNote, $strNewDupe)
    {
        $strDupeNotes = null;

        $strDupeMarker_Start = "<dupe>";
        $strDupeMarker_End = "</dupe>";
        $strUserNotePart = "";

        if(substr_count($strNote, $strDupeMarker_Start)>0)
        {
            $arrNote = explode($strDupeMarker_Start, $strNote);
            $strUserNotePart = $arrNote[0];
            $strDupeNotes = $arrNote[1];
            $arrDupesListed = explode(";", $strDupeNotes);
            if(count($arrDupesListed) > 3)
            {
                $strDupeNotes = $arrDupesListed[0] . "; " . $arrDupesListed[1] . "; " . $arrDupesListed[2] . "; " . $arrDupesListed[3] . "; and more";
            }

            $strDupeNotes = str_replace($strDupeMarker_End, "", $strDupeNotes);
            $strDupeNotes .= $strDupeNotes ."; ";
        }
        elseif(strlen($strNote) > 0)
        {
            $strUserNotePart = $strNote;
        }

        return (strlen($strUserNotePart) > 0 ? $strUserNotePart . " " . PHP_EOL : "") . $strDupeMarker_Start . $strDupeNotes . $strNewDupe . $strDupeMarker_End;

    }

    private function _getUserSearchTitleKeywords()
    {
        $keywordsToMatch = array();
        $runSearches = getAllSearchesThatWereIncluded();

        foreach ($runSearches as $searchDetails)
        {
            $kwd_tokenized = $searchDetails->getSearchParameter('keywords_array_tokenized');
            if (!is_null($kwd_tokenized) && is_array($kwd_tokenized)) {
                foreach ($kwd_tokenized as $kwdset) {
                    $arrKwdSet[$kwdset] = explode(" ", $kwdset);
                }
                $keywordsToMatch = my_merge_add_new_keys($keywordsToMatch, $arrKwdSet);
            }
        }
        return $keywordsToMatch;
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
                    $foundAllUserKeywords = false;
                    $strJobTitleTokens = $jobMatch->getJobPosting()->getTitleTokens();
                    $jobId = $jobMatch->getJobPostingId();
                    if(is_null($strJobTitleTokens) || strlen($strJobTitleTokens) == 0 )
                        throw new Exception("Cannot match user search keywords against job title token.  JobTitleTokens column for job_posting id#{$jobId} is null.");
                    foreach ($usrSearchKeywords as $usrSearchKeywordItem) {
                        $foundAllUserKeywords = in_string_array($strJobTitleTokens, $usrSearchKeywordItem);
                        if ($foundAllUserKeywords !== false) {
                            $jobMatch->setMatchedUserKeywords($usrSearchKeywordItem);
                            $jobMatch->save();
                            $nJobsMarkedInclude += 1;
                            break;
                        }
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

} 