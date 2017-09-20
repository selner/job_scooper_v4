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
require_once dirname(dirname(__FILE__))."/bootstrap.php";
use \Khartnett\Normalization as Normalize;



const C__STR_TAG_AUTOMARKEDJOB__ = "[auto-marked]";
const C__STR_TAG_DUPLICATE_POST__ = "Duplicate Job Post " . C__STR_TAG_AUTOMARKEDJOB__;
const C__STR_TAG_BAD_TITLE_POST__ = "Bad Title & Role " . C__STR_TAG_AUTOMARKEDJOB__;
const C__STR_TAG_NOT_A_KEYWORD_TITLE_MATCH__ = "Not a Keyword Title Match " . C__STR_TAG_AUTOMARKEDJOB__;
const C__STR_TAG_NOT_EXACT_TITLE_MATCH__ = "Not an Exact Title Match " . C__STR_TAG_AUTOMARKEDJOB__;


class JobsAutoMarker
{
    protected $siteName = "JobsAutoMarker";
    protected $arrLatestJobs_UnfilteredByUserInput = array();
    protected $arrMasterJobList = array();
    protected $cbsaList  = array();
    protected $normalizer  = null;
    protected $userMatchedCBSAPlaces  = array();
    protected $cbsaLocSetMapping = array();
    protected $locationCities = array();
    protected $validCityValues = array();

    function __construct($arrJobObjsToMark = array(), $strOutputDirectory = null)
    {
        if (!is_null($arrJobObjsToMark) && count($arrJobObjsToMark) > 0)
            $this->arrMasterJobList = $arrJobObjsToMark;

        $this->normalizer = new Normalize();

        $this->cbsaList = loadJSON(__ROOT__.'/include/static/cbsa_list.json');
        $cbsaCityMapping = loadCSV(__ROOT__.'/include/static/us_place_to_csba_mapping.csv', 'PlaceKey');

        foreach($GLOBALS['USERDATA']['configuration_settings']['location_sets'] as $locset)
        {
            if(array_key_exists('location-city', $locset) === true && (array_key_exists('location-statecode', $locset)))
            {
                $cityName = $this->_normalizeLocation_($locset['location-city'])."_".strtoupper($locset['location-statecode']);
                $placekey = strtoupper($cityName);
                $cbsa = $cbsaCityMapping[$placekey];
                $this->cbsaLocSetMapping[$locset['key']] = $cbsa['CBSA'];
            }
            elseif(array_key_exists('location-city', $locset) === true)
            {
                $cityName = $this->_normalizeLocation_($locset['location-city']);
                $this->locationCities[$this->getLocationLookupKey($cityName)] = $cityName;
            }
        }

        $this->validCities = array();

        $cbsaInUsa = array_unique($this->cbsaLocSetMapping);
        foreach($cbsaCityMapping as $place)
        {
            $placeName = $this->_normalizeLocation_($place['Place'] . ", " . $place['StateCode'] );
            $placeKey = $this->getLocationLookupKey($placeName);
            $this->validCityValues[$placeKey] = $placeName;
            if(array_search($place['CBSA'], $cbsaInUsa) !== false)
            {
                $this->userMatchedCBSAPlaces[$place['PlaceKey']] = $place;
            }
        }

        unset($locset);
        unset($cbsaCityMapping);
        
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
            throw new Exception("No jobs found to auto-mark.");

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        LogLine(PHP_EOL . "**************  Updating jobs list for known filters ***************" . PHP_EOL, \C__DISPLAY_NORMAL__);

        $masterCopy = array_copy($this->arrMasterJobList);

        $arrJobs_AutoUpdatable = array_filter($masterCopy, "isJobAutoUpdatable");
        $this->_markJobsList_SetLikelyDuplicatePosts_($arrJobs_AutoUpdatable);
        $this->_markJobsList_SetOutOfArea_($arrJobs_AutoUpdatable);
        $this->_markJobsList_SearchKeywordsNotFound_($arrJobs_AutoUpdatable);
        $this->_markJobsList_UserExcludedKeywords_($arrJobs_AutoUpdatable);
        $this->_markJobsList_SetAutoExcludedCompaniesFromRegex_($arrJobs_AutoUpdatable);


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

    private function _normalizeLocation_($locString)
    {
        $stringToNormalize = "111 Bogus St, " . $locString;
        $location = $this->normalizer->parse($stringToNormalize);
        if ($location !== false)
        {
            $locString = $location['city'];
            if (strlen($location['state']) > 0)
                 $locString .= ", " . $location['state'];
        }

        return $locString;
    }

    private function getLocationLookupKey($locString)
    {
        $citystate = $this->_normalizeLocation_($locString);
        return strtoupper(str_replace(" ", "", str_replace("Greater", "", str_replace(", ", "_", $citystate))));

    }

    private function _doesLocationMatchUserSearch($locationKey)
    {
        if(in_array("US", $GLOBALS['USERDATA']['configuration_settings']['country_codes']))
        {

            if (in_array($locationKey, array_keys($this->userMatchedCBSAPlaces))) {
                return true;
            }

            $placeKey = array_find_closest_key_match($locationKey, array_keys($this->userMatchedCBSAPlaces));
            if (!is_null($placeKey) && (strncmp($placeKey, $locationKey, 5) == 0 || !array_key_exists($locationKey, array_keys($this->validCityValues)))) {
                return true;
            }
        }
        else
        {
            if(substr_count_multi($this->getLocationLookupKey($locationKey), array_keys($this->locationCities), $matches) == true)
                return true;
        }

        return false;

    }
    private function _markJobsList_SetOutOfArea_(&$arrJobsList)
    {
        try
        {
            if (count($arrJobsList) == 0) return;

            LogLine("Marking Out of Area Jobs", \C__DISPLAY_ITEM_START__);

            $nJobsSkipped = 0;
            $nJobsMarkedAutoExcluded = 0;
            $nJobsNotMarked = 0;

            LogLine("Building jobs by locations list and excluding failed matches...", \C__DISPLAY_ITEM_DETAIL__);
            foreach ($arrJobsList as $jobMatch) {
                $locValue = $jobMatch->getJobPosting()->getLocation();
                $locKey = $this->getLocationLookupKey($locValue);

                if (in_array($jobMatch->getUserMatchStatus(), array("exclude-match")) == 1) {
                    $nJobsSkipped += 1;
                } elseif ($this->_doesLocationMatchUserSearch($locKey)) {
                    $nJobsNotMarked++;
                } else {
                    $jobMatch->setUserMatchStatus("exclude-match");
                    $jobMatch->setUserMatchReason("Out of Search Area ");
                    $jobMatch->updateMatchNotes($locValue . " not in user's search area.");
                    $jobMatch->save();
                    $nJobsMarkedAutoExcluded++;
                }
            }

            assert(count($arrJobsList) == $nJobsMarkedAutoExcluded + $nJobsSkipped + $nJobsNotMarked);

            LogLine("Jobs marked as out of area: marked ".$nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) .", skipped " . $nJobsSkipped . "/" . countAssociativeArrayValues($arrJobsList) .", not marked ". $nJobsNotMarked . "/" . countAssociativeArrayValues($arrJobsList).")" , \C__DISPLAY_ITEM_RESULT__);
        }
        catch (Exception $ex)
        {
            handleException($ex, "Error in SetOutOfArea: %s", true);
        }
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
                if(in_array($jobMatch->getUserMatchStatus(), array("exclude-match")) == 1) {
                    $nJobsSkipped += 1;
                }
                else
                {
                    $matched_exclusion = false;
                    foreach($GLOBALS['USERDATA']['companies_regex_to_filter'] as $rxInput )
                    {
                        if(preg_match($rxInput, strScrub($jobMatch->getJobPosting()->getCompany(), DEFAULT_SCRUB)))
                        {
                            $jobMatch->setUserMatchStatus("exclude-match");
                            $jobMatch->setUserMatchReason('Wrong Company ' . C__STR_TAG_AUTOMARKEDJOB__);
                            $jobMatch->updateMatchNotes("Matched company exclusion regex [". $rxInput ."]");
                            $nJobsMarkedAutoExcluded++;
                            $matched_exclusion = true;
                            break;
                        }
                    }

                    if($matched_exclusion !== true)
                        $nJobsNotMarked += 1;
                }
            }

            LogLine("Jobs marked not interested via companies regex: marked ".$nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) .", skipped " . $nJobsSkipped . "/" . countAssociativeArrayValues($arrJobsList) .", not marked ". $nJobsNotMarked . "/" . countAssociativeArrayValues($arrJobsList).")" , \C__DISPLAY_ITEM_RESULT__);
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
            $titleKeywords = $this->_getTitleKeywords();
            if (count($arrJobsList) == 0 || is_null($titleKeywords)) return null;
            $negKeywords = array_diff_assoc($GLOBALS['USERDATA']['title_negative_keyword_tokens'], array_values($titleKeywords) );

            LogLine("Excluding Jobs by Negative Title Keyword Token Matches", \C__DISPLAY_ITEM_START__);
            LogLine("Checking ".count($arrJobsList) ." roles against ". count($negKeywords) ." negative title keywords to be excluded.", \C__DISPLAY_ITEM_DETAIL__);


            try {


                foreach ($arrJobsList as $jobMatch)
                {
                    $arrKeywordsMatched = array();
                    if(in_array($jobMatch->getUserMatchStatus(), array("exclude-match")) == 1) {
                        $nJobsSkipped += 1;
                    }
                    else
                    {
                        foreach ($negKeywords as $kywdtoken) {
                            $matched = substr_count_multi($jobMatch->getJobPosting()->getTitleTokens(), $kywdtoken, $kwdTokenMatches, true);
                            if ($matched === true) {
                                $strTitleTokenMatches = getArrayValuesAsString(array_values($kwdTokenMatches), " ", "", false);

                                if (count($kwdTokenMatches) === count($kywdtoken)) {
                                    $arrKeywordsMatched[$strTitleTokenMatches] = $kwdTokenMatches;
                                }
                            }
                        }

                        if(count($arrKeywordsMatched) > 0)
                        {
                            $jobMatch->setUserMatchStatus("exclude-match");
                            $jobMatch->setUserMatchReason(TITLE_NEG_KWD_MATCH);
                            $jobMatch->updateMatchNotes("Title matched negative user keywords [" . getArrayValuesAsString($arrKeywordsMatched) . "]");
                            $nJobsMarkedAutoExcluded += 1;
                        }
                        else
                        {
                            $nJobsNotMarked += 1;
                        }
                    }
                    $jobMatch->save();

                }
            } catch (Exception $ex) {
                handleException($ex, 'ERROR:  Failed to verify titles against negative keywords due to error: %s', isDebug());
            }
            LogLine("Processed " . countAssociativeArrayValues($arrJobsList) . " titles for auto-marking against negative title keywords: " . $nJobsSkipped . "/" . count($arrJobsList) . " skipped; " . $nJobsNotMarked. "/" . countAssociativeArrayValues($arrJobsList) . " marked included; " . $nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) . " marked excluded.", \C__DISPLAY_ITEM_RESULT__);
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

    private function _getTitleKeywords()
    {
        $keywordsToMatch = array();
        foreach ($GLOBALS['USERDATA']['configuration_settings']['searches'] as $searchDetails) {
            $search = $searchDetails->getSearchSettings();
            if(is_null($search))
                return null;
            elseif (array_key_exists('keywords_array_tokenized', $search)) {
                foreach ($search['keywords_array_tokenized'] as $kwdset) {
                    $arrKwdSet[$kwdset] = explode(" ", $kwdset);
                }
                $keywordsToMatch = my_merge_add_new_keys($keywordsToMatch, $arrKwdSet);
            }
        }
        return $keywordsToMatch;
    }

    private function _markJobsList_SearchKeywordsNotFound_(&$arrJobsList)
    {
        $nJobsSkipped = 0;
        $nJobsMarkedAutoExcluded = 0;
        $nJobsNotMarked = 0;

        try {
            $titleKeywords = $this->_getTitleKeywords();
            if (count($arrJobsList) == 0 || is_null($titleKeywords)) return null;

            LogLine("Checking " . count($arrJobsList) . " roles against " . count($titleKeywords) . " keywords in titles...", \C__DISPLAY_ITEM_DETAIL__);

            try {

                foreach ($arrJobsList as $jobMatch)
                {
                    $arrKeywordsMatched = array();
                    if(in_array($jobMatch->getUserMatchStatus(), array("exclude-match")) == 1) {
                        $nJobsSkipped += 1;
                    }
                    else
                    {
                       foreach ($titleKeywords as $kywdtoken) {
                            $matched = substr_count_multi($jobMatch->getJobPosting()->getTitleTokens(), $kywdtoken, $kwdTokenMatches, true);
                            if ($matched === true) {
                                $strTitleTokenMatches = getArrayValuesAsString(array_values($kwdTokenMatches), " ", "", false);

                                if (count($kwdTokenMatches) === count($kywdtoken)) {
                                    $arrKeywordsMatched[$strTitleTokenMatches] = $kwdTokenMatches;
                                }
                            }
                        }

                        if(count($arrKeywordsMatched) == 0)
                        {
                                $jobMatch->setUserMatchStatus("exclude-match");
                                $jobMatch->setUserMatchReason(NO_TITLE_MATCHES);
                                $jobMatch->updateMatchNotes("title keywords not matched to terms [" . getArrayValuesAsString($keywordsToMatch) . "]");
                                $nJobsMarkedAutoExcluded += 1;
                        }
                        else
                        {
//                            $jobMatch->updateMatchNotes("matched title keywords [" . getArrayValuesAsString($arrKeywordsMatched) . "]");
                            $nJobsNotMarked += 1;
                        }
                    }
                    $jobMatch->save();

                }
            } catch (Exception $ex) {
                handleException($ex, 'ERROR:  Failed to verify titles against keywords due to error: %s', isDebug());
            }
            LogLine("Processed " . countAssociativeArrayValues($arrJobsList) . " titles for auto-marking against search title keywords: " . $nJobsSkipped . "/" . count($arrJobsList) . " skipped; " . $nJobsNotMarked. "/" . countAssociativeArrayValues($arrJobsList) . " marked included; " . $nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobsList) . " marked excluded.", \C__DISPLAY_ITEM_RESULT__);
        }
        catch (Exception $ex)
        {
            handleException($ex, "Error in SearchKeywordsNotFound: %s", true);
        }

    }

} 