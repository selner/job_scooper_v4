<?php
/**
 * Copyright 2014-16 Bryan Selner
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

if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/SitePlugins.php');
require_once(__ROOT__.'/include/ClassMultiSiteSearch.php');
require_once(__ROOT__ . '/include/S3Manager.php');
require_once(__ROOT__.'/include/ClassJobsNotifier.php');

class JobsAutoMarker extends ClassJobsSiteCommon
{
    protected $siteName = "JobsAutoMarker";
    protected $arrLatestJobs_UnfilteredByUserInput = array();
    protected $arrMasterJobList = array();
    protected $cbsaList  = null;
    protected $cbsaCityMapping  = null;
    protected $cbsaLocSetMapping = null;

    function __construct($arrJobs_Unfiltered)
    {
        $this->arrMasterJobList = \Scooper\array_copy($arrJobs_Unfiltered);

        $this->cbsaList = loadJSON(__ROOT__ . '/static/cbsa_list.json');
        $this->cbsaCityMapping = loadJSON(__ROOT__ . '/static/city_to_cbsa_mapping.json');
        $this->cbsaLocSetMapping = array();

        foreach($GLOBALS['USERDATA']['configuration_settings']['location_sets'] as $locset)
        {
            if(array_key_exists('location-city', $locset) === true && array_key_exists($locset['location-city'], $this->cbsaCityMapping))
            {
                $cbsa = $this->cbsaCityMapping[$locset['location-city']];
                if(strcasecmp($locset['location-statecode'], $cbsa['CBSA State Code'] ) == 0)
                {
                    $this->cbsaLocSetMapping[$locset['key']] = $cbsa['CBSACode'];

                }
            }

        }
    }

    function __destruct()
    {
        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }

    }


    public function markJobsList()
    {
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $GLOBALS['logger']->logLine(PHP_EOL . "**************  Updating jobs list for known filters ***************" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);

        $this->_markJobsList_SetLikelyDuplicatePosts_($this->arrMasterJobList);
        $this->_markJobsList_SetOutOfArea_($this->arrMasterJobList);

        $arrJobs_AutoUpdatable = array_filter($this->arrMasterJobList, "isJobAutoUpdatable");
        $this->_markJobsList_withAutoItems_($arrJobs_AutoUpdatable);
        $masterCopy = $this->arrMasterJobList;
        $this->arrMasterJobList= array_replace_recursive($masterCopy, $arrJobs_AutoUpdatable);
    }

    public function getMarkedJobs()
    {
        return $this->arrMasterJobList;
    }


    private function _markJobsList_withAutoItems_(&$arrJobsList)
    {
        $this->_markJobsList_SearchKeywordsNotFound_($arrJobsList);
        $this->_markJobsList_SetAutoExcludedTitles_($arrJobsList);
        $this->_markJobsList_SetAutoExcludedCompaniesFromRegex_($arrJobsList);
    }




//    private function _markJobsList_WithPreviouslyReviewedPostNotes_()
//    {
//        if(count($this->arrLatestJobs) == 0) return;
//
//        $nJobsMatched = 0;
//
//        $arrKeys_CompanyAndRole = array_column ( $this->arrLatestJobs, 'key_company_role');
//        $arrKeys_JobSiteAndJobID = array_column ( $this->arrLatestJobs, 'key_jobsite_siteid');
//
//
//        $arrUniqIds = array_unique($arrKeys_CompanyAndRole);
//        $nUniqJobs = countAssociativeArrayValues($arrUniqIds);
//        $arrOneJobListingPerCompanyAndRole = array_unique_multidimensional(array_combine($arrKeys_JobSiteAndJobID, $arrKeys_CompanyAndRole));
//        $arrLookup_JobListing_ByCompanyRole = array_flip($arrOneJobListingPerCompanyAndRole);
//
//        $GLOBALS['logger']->logLine("Updating Jobs with Previously Made Notes..." , \Scooper\C__DISPLAY_SECTION_START__);
//
//
//
//
//        foreach($this->arrLatestJobs as $job)
//        {
//            $strCurrentJobIndex = getArrayKeyValueForJob($job);
//            if(!isMarkedBlank($job))
//            {
//                continue;  // only mark dupes that haven't yet been marked with anything
//            }
//
//            $indexPrevListingForCompanyRole = $arrLookup_JobListing_ByCompanyRole[$job['key_company_role']];
//            // Another listing already exists with that title at that company
//            // (and we're not going to be updating the record we're checking)
//            if($indexPrevListingForCompanyRole != null && strcasecmp($indexPrevListingForCompanyRole, $job['key_jobsite_siteid'])!=0)
//            {
//
//                //
//                // Add a note to the previous listing that it had a new duplicate
//                //
//                appendJobColumnData($this->arrLatestJobs[$indexPrevListingForCompanyRole], 'match_notes', "|", $this->getNotesWithDupeIDAdded($this->arrLatestJobs[$indexPrevListingForCompanyRole]['match_notes'], $job['key_jobsite_siteid'] ));
//                $this->arrLatestJobs[$indexPrevListingForCompanyRole] ['date_last_updated'] = getTodayAsString();
//
//                $this->arrLatestJobs[$strCurrentJobIndex]['interested'] =  C__STR_TAG_DUPLICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__;
//                appendJobColumnData($this->arrLatestJobs[$strCurrentJobIndex], 'match_notes', "|", $this->getNotesWithDupeIDAdded($this->arrLatestJobs[$strCurrentJobIndex]['match_notes'], $indexPrevListingForCompanyRole ));
//                $this->arrLatestJobs[$strCurrentJobIndex]['date_last_updated'] = getTodayAsString();
//
//                $nJobsMatched++;
//            }
//
//        }
//
//        $strTotalRowsText = "/".count($this->arrLatestJobs);
//        $GLOBALS['logger']->logLine("Marked  ".$nJobsMatched .$strTotalRowsText ." roles as likely duplicates based on company/role. " , \Scooper\C__DISPLAY_ITEM_RESULT__);
//
//    }


    private function _markJobsList_SetLikelyDuplicatePosts_(&$arrJobsList)
    {
        if(count($arrJobsList) == 0) return;

        $nJobsMatched = 0;

        $arrKeys_CompanyAndRole = array_column ( $arrJobsList, 'key_company_role');
        $arrKeys_JobSiteAndJobID = array_column ( $arrJobsList, 'key_jobsite_siteid');


        $arrUniqIds = array_unique($arrKeys_CompanyAndRole);
        $nUniqJobs = countAssociativeArrayValues($arrUniqIds);
        $arrOneJobListingPerCompanyAndRole = array_unique_multidimensional(array_combine($arrKeys_JobSiteAndJobID, $arrKeys_CompanyAndRole));
        $arrLookup_JobListing_ByCompanyRole = array_flip($arrOneJobListingPerCompanyAndRole);

        $GLOBALS['logger']->logLine("Marking Duplicate Job Roles" , \Scooper\C__DISPLAY_SECTION_START__);
        $GLOBALS['logger']->logLine($nUniqJobs . "/" . countAssociativeArrayValues($arrJobsList) . " jobs have immediately been marked as non-duplicate based on company/role pairing. " , \Scooper\C__DISPLAY_ITEM_DETAIL__);

        foreach($arrJobsList as $job)
        {
            $strCurrentJobIndex = getArrayKeyValueForJob($job);
            if(!isMarkedBlank($job))
            {
                continue;  // only mark dupes that haven't yet been marked with anything
            }

            $indexPrevListingForCompanyRole = $arrLookup_JobListing_ByCompanyRole[$job['key_company_role']];
            // Another listing already exists with that title at that company
            // (and we're not going to be updating the record we're checking)
            if($indexPrevListingForCompanyRole != null && strcasecmp($indexPrevListingForCompanyRole, $job['key_jobsite_siteid'])!=0)
            {

                //
                // Add a note to the previous listing that it had a new duplicate
                //
                appendJobColumnData($arrJobsList[$indexPrevListingForCompanyRole], 'match_notes', "|", $this->getNotesWithDupeIDAdded($arrJobsList[$indexPrevListingForCompanyRole]['match_notes'], $job['key_jobsite_siteid'] ));
                $arrJobsList[$indexPrevListingForCompanyRole] ['date_last_updated'] = getTodayAsString();

                $arrJobsList[$strCurrentJobIndex]['interested'] =  C__STR_TAG_DUPLICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__;
                appendJobColumnData($arrJobsList[$strCurrentJobIndex], 'match_notes', "|", $this->getNotesWithDupeIDAdded($arrJobsList[$strCurrentJobIndex]['match_notes'], $indexPrevListingForCompanyRole ));
                $arrJobsList[$strCurrentJobIndex]['date_last_updated'] = getTodayAsString();

                $nJobsMatched++;
            }

        }

        $strTotalRowsText = "/".count($arrJobsList);
        $GLOBALS['logger']->logLine("Marked  ".$nJobsMatched .$strTotalRowsText ." roles as likely duplicates based on company/role. " , \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    private function _markJobsList_SetOutOfArea_(&$arrJobsList)
    {
        if(count($arrJobsList) == 0) return;

        $nJobsNotMarked = 0;
        $nJobsMarkedAutoExcluded = 0;

        $lookupcbsa = array();

        $arrKeys_Locations = array_column ( $arrJobsList, 'location');
        array_unique($arrKeys_Locations);
        $citycbsaKeys = array_keys($this->cbsaCityMapping);
        foreach($arrKeys_Locations as $locstr)
        {
            $cbsaResult = null;
            if(in_array($locstr, $citycbsaKeys ))
            {
                $cbsaResult = $this->cbsaCityMapping[$locstr];
                if($cbsaResult !== false)
                {
                    $lookupcbsa[$locstr] = $cbsaResult;
                }
            }
        }

        $arrJobs_AutoUpdatable= array_filter($arrJobsList, "isJobAutoUpdatable");
        $nJobsSkipped = count($arrJobsList) - count($arrJobs_AutoUpdatable);

        $GLOBALS['logger']->logLine("Marking Out of Area Jobs" , \Scooper\C__DISPLAY_SECTION_START__);

        foreach($arrJobs_AutoUpdatable as $job)
        {
            $matched = false;
            $strJobIndex = getArrayKeyValueForJob($job);
            if(array_key_exists('location', $job))
            {
                $locStr = trim(explode(",", $job['location'])[0]);

                if(array_key_exists($locStr, $this->cbsaCityMapping))
                {
                    $cbsa = $this->cbsaCityMapping[$locStr];

                    if(!in_array($cbsa['CBSACode'], $this->cbsaLocSetMapping))
                    {
                        $arrJobsList[$strJobIndex]['interested'] = 'No (Out of Search Area)' . C__STR_TAG_AUTOMARKEDJOB__;
                        appendJobColumnData($arrJobsList[$strJobIndex], 'match_notes', "|", "Matched " . getArrayValuesAsString($cbsa));
                        $arrJobsList[$strJobIndex]['date_last_updated'] = getTodayAsString();
                        $nJobsMarkedAutoExcluded++;
                        $matched = true;
                    }
                }
                if($matched === false)
                {
                    $nJobsNotMarked = $nJobsNotMarked + 1;
                }

            }

        }


        $GLOBALS['logger']->logLine("Jobs marked as out of area: marked ".$nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobs_AutoUpdatable) .", skipped " . $nJobsSkipped . "/" . countAssociativeArrayValues($arrJobs_AutoUpdatable) .", not marked ". $nJobsNotMarked . "/" . countAssociativeArrayValues($arrJobs_AutoUpdatable).")" , \Scooper\C__DISPLAY_ITEM_RESULT__);
        return;
    }

    private function _markJobsList_SetAutoExcludedCompaniesFromRegex_(&$arrJobsList)
    {
        if(count($arrJobsList) == 0) return;

        $nJobsNotMarked = 0;
        $nJobsMarkedAutoExcluded = 0;

        $GLOBALS['logger']->logLine("Excluding Jobs by Companies Regex Matches", \Scooper\C__DISPLAY_ITEM_START__);
        $GLOBALS['logger']->logLine("Checking ".count($arrJobsList) ." roles against ". count($GLOBALS['USERDATA']['companies_regex_to_filter']) ." excluded companies.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $arrJobs_AutoUpdatable= array_filter($arrJobsList, "isJobAutoUpdatable");
        $nJobsSkipped = count($arrJobsList) - count($arrJobs_AutoUpdatable);

        if(count($arrJobs_AutoUpdatable) > 0 && count($GLOBALS['USERDATA']['companies_regex_to_filter']) > 0)
        {
            foreach($arrJobs_AutoUpdatable as $job)
            {
                $fMatched = false;
                // get all the job records that do not yet have an interested value

                foreach($GLOBALS['USERDATA']['companies_regex_to_filter'] as $rxInput )
                {
                    if(preg_match($rxInput, \Scooper\strScrub($job['company'], DEFAULT_SCRUB)))
                    {
                        $strJobIndex = getArrayKeyValueForJob($job);
                        $arrJobsList[$strJobIndex]['interested'] = 'No (Wrong Company)' . C__STR_TAG_AUTOMARKEDJOB__;
                        appendJobColumnData($arrJobsList[$strJobIndex], 'match_notes', "|", "Matched regex[". $rxInput ."]");
                        $arrJobsList[$strJobIndex]['date_last_updated'] = getTodayAsString();
                        $nJobsMarkedAutoExcluded++;
                        $fMatched = true;
                        break;
                    }
                    if($fMatched == true) break;
                }
                if($fMatched == false)
                {
                    $nJobsNotMarked++;
                }

//                if($fMatched == false)
//                  $GLOBALS['logger']->logLine("Company '".$job['company'] ."' was not found in the companies exclusion regex list.  Keeping for review." , \Scooper\C__DISPLAY_ITEM_DETAIL__);

            }
        }
        $GLOBALS['logger']->logLine("Jobs marked not interested via companies regex: marked ".$nJobsMarkedAutoExcluded . "/" . countAssociativeArrayValues($arrJobs_AutoUpdatable) .", skipped " . $nJobsSkipped . "/" . countAssociativeArrayValues($arrJobs_AutoUpdatable) .", not marked ". $nJobsNotMarked . "/" . countAssociativeArrayValues($arrJobs_AutoUpdatable).")" , \Scooper\C__DISPLAY_ITEM_RESULT__);
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


    private function _getJobsList_MatchingJobTitleKeywords_($arrJobs, $keywordsToMatch, $logTagString = "UNKNOWN")
    {
        $ret = array("skipped" => array(), "matched" => array(), "notmatched" => array());
        if(count($arrJobs) == 0) return $ret;

        $GLOBALS['logger']->logLine("Checking ".count($arrJobs) ." roles against ". count($keywordsToMatch) ." keywords in titles. [_getJobsList_MatchingJobTitleKeywords_]", \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $arrMatchedTitles = array();
        $arrNotMatchedTitles = array();
        $arrTitlesWithBlanks= array_filter($arrJobs, "isMarkedBlank");
        $ret["skipped"] = array_filter($arrJobs, "isMarkedNotBlank");

        try
        {

            foreach($arrTitlesWithBlanks as $job)
            {
                $arrKeywordsMatched = array();
                $strJobIndex = getArrayKeyValueForJob($job);

                foreach($keywordsToMatch as $kywdtoken)
                {
                    $kwdTokenMatches = array();

                    $matched = substr_count_multi($job['job_title_tokenized'], $kywdtoken, $kwdTokenMatches, true);
                    if(count($kwdTokenMatches) > 0)
                    {
                        $strTitleTokenMatches = getArrayValuesAsString(array_values($kwdTokenMatches), " ", "", false );

                        if(count($kwdTokenMatches) === count($kywdtoken))
                        {
                            $arrKeywordsMatched[$strTitleTokenMatches] = $kwdTokenMatches;
                        }
                        else
                        {
                            // do nothing
                        }
                    }
                }

                if(countAssociativeArrayValues($arrKeywordsMatched) > 0)
                {
                    $job['keywords_matched'] = $arrKeywordsMatched;
                    $ret['matched'][$strJobIndex] = $job;
                }
                else
                {
                    $job['keywords_matched'] = $arrKeywordsMatched;
                    $ret['notmatched'][$strJobIndex] = $job;
                }
            }
        }
        catch (Exception $ex)
        {
            $GLOBALS['logger']->logLine('ERROR:  Failed to verify titles against keywords [' . $logTagString . '] due to error: '. $ex->getMessage(), \Scooper\C__DISPLAY_ERROR__);
            if(isDebug()) { throw $ex; }
        }
        $GLOBALS['logger']->logLine("Processed " . countAssociativeArrayValues($arrJobs) . " titles for auto-marking [" . $logTagString . "]: skipped " . countAssociativeArrayValues($ret['skipped']). "/" . countAssociativeArrayValues($arrJobs) ."; matched ". countAssociativeArrayValues($ret['matched']) . "/" . countAssociativeArrayValues($arrJobs) ."; not matched " . countAssociativeArrayValues($ret['notmatched']). "/" . countAssociativeArrayValues($arrJobs)  , \Scooper\C__DISPLAY_ITEM_RESULT__);

        return $ret;
    }


    private function _markJobsList_SearchKeywordsNotFound_(&$arrJobsList)
    {
        $arrKwdSet = array();
        $arrJobsStillActive = array_filter($arrJobsList, "isMarkedBlank");
        $nStartingBlankCount = countAssociativeArrayValues($arrJobsStillActive);
        foreach($GLOBALS['USERDATA']['configuration_settings']['searches'] as $search)
        {
            if(array_key_exists('keywords_array_tokenized', $search))
            {
                foreach($search['keywords_array_tokenized'] as $kwdset)
                {
                    $arrKwdSet[$kwdset] = explode(" ", $kwdset);
                }
                $arrKwdSet = \Scooper\my_merge_add_new_keys($arrKwdSet, $arrKwdSet);
            }
        }

        $ret = $this->_getJobsList_MatchingJobTitleKeywords_($arrJobsStillActive, $arrKwdSet, "TitleKeywordSearchMatch");
        foreach($ret['notmatched'] as $job)
        {
            $strJobIndex = getArrayKeyValueForJob($job);
            $arrJobsList[$strJobIndex]['interested'] = NO_TITLE_MATCHES;
            $arrJobsList[$strJobIndex]['date_last_updated'] = getTodayAsString();
            appendJobColumnData($arrJobsList[$strJobIndex], 'match_notes', "|", "title keywords not matched to terms [". getArrayValuesAsString($arrKwdSet, "|", "", false)  ."]");
        }

        $nEndingBlankCount = countAssociativeArrayValues(array_filter($arrJobsList, "isMarkedBlank"));
        $GLOBALS['logger']->logLine("Processed " . $nStartingBlankCount . "/" . countAssociativeArrayValues($arrJobsList) . " jobs marking if did not match title keyword search:  updated ". ($nStartingBlankCount - $nEndingBlankCount) . "/" . $nStartingBlankCount  . ", still active ". $nEndingBlankCount . "/" . $nStartingBlankCount, \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    private function _markJobsList_SetAutoExcludedTitles_(&$arrJobsList)
    {
        $arrJobsStillActive = array_filter($arrJobsList, "isMarkedBlank");
        $nStartingBlankCount = countAssociativeArrayValues($arrJobsStillActive);

        $ret = $this->_getJobsList_MatchingJobTitleKeywords_($arrJobsStillActive, $GLOBALS['USERDATA']['title_negative_keyword_tokens'], "TitleNegativeKeywords");
        foreach($ret['matched'] as $job)
        {
            $strJobIndex = getArrayKeyValueForJob($job);
            $arrJobsList[$strJobIndex]['interested'] = TITLE_NEG_KWD_MATCH;
            $arrJobsList[$strJobIndex]['date_last_updated'] = getTodayAsString();
            appendJobColumnData($arrJobsList[$strJobIndex], 'match_notes', "|", "matched negative keyword title[". getArrayValuesAsString($job['keywords_matched'], "|", "", false)  ."]");
        }
        $nEndingBlankCount = countAssociativeArrayValues(array_filter($arrJobsList, "isMarkedBlank"));
        $GLOBALS['logger']->logLine("Processed " . $nStartingBlankCount . "/" . countAssociativeArrayValues($arrJobsList) . " jobs marking negative keyword matches:  updated ". ($nStartingBlankCount - $nEndingBlankCount) . "/" . $nStartingBlankCount  . ", still active ". $nEndingBlankCount . "/" . $nStartingBlankCount, \Scooper\C__DISPLAY_ITEM_RESULT__);


    }



} 