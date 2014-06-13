<?php
/**
 * Copyright 2014 Bryan Selner
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
require_once dirname(__FILE__) . '/../include/Options.php';
require_once dirname(__FILE__) . '/../include/JobListHelpers.php';
require_once dirname(__FILE__) . '/../lib/array_column.php';



class ClassJobsSitePluginCommon
{

//    private $arrKeysForDeduping = array('job_site', 'job_id');
    private $arrKeysForDeduping = array('key_jobsite_siteid');

    private $_bitFlags = null;
    protected $detailsMyFileOut= "";


    function __construct($bitFlags = null)
    {
        $this->_bitFlags = $bitFlags;
    }

    function getMyBitFlags() { return $this->_bitFlags; }
    function setMyBitFlags($bitFlags) { $this->_bitFlags = $bitFlags; }


    function getEmptyJobListingRecord()
    {
        return array(
            'job_site' => '',
            'job_id' => '',
            'company' => '',
            'job_title' => '',
            'interested' => '',
            'notes' => '',
            'status' => '',
            'last_status_update' => '',
            'date_pulled' => '',
            'job_post_url' => '',
            'location' => '',
            'job_site_category' => '',
            'job_site_date' =>'',
            'key_jobsite_siteid' => '',
            'key_company_role' => '',
            'date_last_updated' => '',
         );
    }

    function normalizeJobList($arrJobList)
    {
        $arrRetList = null;

        if($arrJobList == null) return null;

        foreach($arrJobList as $job)
        {
            $jobNorm = $this->normalizeItem($job);
            addJobToJobsList($arrRetList, $jobNorm);
        }
        return $arrRetList;
    }

    function removeKeyColumnsFromJobList($arrJobList)
    {
        $arrRetList = null;

        if($arrJobList == null) return null;

        foreach($arrJobList as $job)
        {
            // if the first item is the site/site-id key, remove it from the list
            $tempJob = array_pop($job);

            // if the second item is the company/role-name key, remove it from the list
            $tempJob = array_pop($tempJob);

            $arrJobList[] = $tempJob;
        }

    }

    function normalizeItem($arrItem)
    {
        $retArrNormalized = $arrItem;

        // For reference, DEFAULT_SCRUB =  REMOVE_PUNCT | HTML_DECODE | LOWERCASE | REMOVE_EXTRA_WHITESPACE

        $retArrNormalized ['job_site'] = strScrub($retArrNormalized['job_site'], DEFAULT_SCRUB);
        $retArrNormalized ['job_id'] = strScrub($retArrNormalized['job_id'], SIMPLE_TEXT_CLEANUP);
        $retArrNormalized ['job_title'] = strScrub($retArrNormalized['job_title'], SIMPLE_TEXT_CLEANUP);

        $retArrNormalized ['job_site_category'] = strScrub($retArrNormalized['job_site_category'], SIMPLE_TEXT_CLEANUP);
        $retArrNormalized ['job_site_date'] = strScrub($retArrNormalized['job_site_date'], REMOVE_EXTRA_WHITESPACE | LOWERCASE | HTML_DECODE );
        $retArrNormalized ['job_post_url'] = trim($retArrNormalized['job_post_url']); // DO NOT LOWER, BREAKS URLS
        $retArrNormalized ['location'] = strScrub($retArrNormalized['location'], SIMPLE_TEXT_CLEANUP);

        $retArrNormalized ['company'] = strScrub($retArrNormalized['company'], ADVANCED_TEXT_CLEANUP );

        // Remove common company name extensions like "Corporation" or "Inc." so we have
        // a higher match likelihood
        $retArrNormalized ['company'] = str_replace(array(" corporation", " corp", " inc", " llc"), "", $retArrNormalized['company']);

        switch(strScrub($retArrNormalized ['company']))
        {
            case "amazon":
            case "amazon com":
            case "a2z":
            case "amazon corporate llc":
            case "amazon Web Services":
            case "amazon fulfillment services":
            case "amazonwebservices":
            case "amazon (seattle)":
                $retArrNormalized ['company'] = "Amazon";
                break;

            case "market leader":
            case "market leader inc":
            case "market leader llc":
                $retArrNormalized ['company'] = "Market Leader";
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
                $retArrNormalized ['company'] = "Disney";
                break;

        }



        if(is_null($retArrNormalized['company']) || strlen($retArrNormalized['company']) <= 0 ||
                substr_count(strtolower($retArrNormalized['company']), "company-unknown") >= 1) // substr check is to clean up records pre 6/9/14.
        {
            $retArrNormalized['company'] = "unknown";
        }

        if(strlen($retArrNormalized['key_company_role']) <= 0)
        {
            $retArrNormalized['key_company_role'] = strScrub($retArrNormalized['company'], FOR_LOOKUP_VALUE_MATCHING) . strScrub($retArrNormalized['job_title'], FOR_LOOKUP_VALUE_MATCHING);
        }

        if(strlen($retArrNormalized['key_jobsite_siteid']) <= 0)
        {
            // For craigslist, they change IDs on every post, so deduping that way doesn't help
            // much.  (There's almost never a company for Craiglist listings either.)
            // Instead for Craiglist listings, we'll dedupe using the role title and the jobsite name
            if(strcasecmp($retArrNormalized['job_site'], "craigslist") == 0)
            {
                $retArrNormalized['key_jobsite_siteid'] = strScrub($retArrNormalized['job_site'], FOR_LOOKUP_VALUE_MATCHING) .strScrub($retArrNormalized['job_title'], FOR_LOOKUP_VALUE_MATCHING) . strScrub($retArrNormalized['job_site_date'], FOR_LOOKUP_VALUE_MATCHING);
            }
            else
            {
                $retArrNormalized['key_jobsite_siteid'] = strScrub($retArrNormalized['job_site'], FOR_LOOKUP_VALUE_MATCHING) . strScrub($retArrNormalized['job_id'], FOR_LOOKUP_VALUE_MATCHING);
            }

        }

        if(strlen($retArrNormalized['date_last_updated']) <= 0)
        {
            $retArrNormalized['date_last_updated'] = $retArrNormalized['date_pulled'];
        }

        return $retArrNormalized;
    }




    private function _addTitlesToFilterList_($arrTitlesToAdd)
    {
        foreach($arrTitlesToAdd as $titleRecord)
        {
            $strTitleKey = strScrub($titleRecord['job_title']);
            $titleRecord['job_title'] = strScrub($titleRecord['job_title']);

            $GLOBALS['DATA']['titles_to_filter'][$strTitleKey] = $titleRecord;
        }

    }

    private function _addBadTitlesFromJobsList_()
    {
        __debug__printLine(PHP_EOL.PHP_EOL."--TODO-- FINISH JOB TITLES LIST FILTER FROM LOADED JOBS".PHP_EOL.PHP_EOL, C__DISPLAY_ERROR__);
/*
        $arrJobsBadTitles = array_filter($arrJobsList, "onlyBadTitlesAndRoles");

        $arrBadTitlesToAdd = array_column($arrJobsBadTitles, "job_title");

        $this->_addTitlesToFilterList_($arrBadTitlesToAdd);
 */
    }


    /**
     * Initializes the global list of titles we will automatically mark
     * as "not interested" in the final results set.
     */
    function _loadTitlesToFilter_()
    {
        $arrTitleFileDetails = $GLOBALS['OPTS']['titles_file_details'];
        $strFileName = "";

        if($GLOBALS['DATA']['titles_to_filter'] != null && count($GLOBALS['DATA']['titles_to_filter']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            __debug__printLine("Using previously loaded " . count($GLOBALS['DATA']['titles_to_filter']) . " titles to exclude." , C__DISPLAY_ITEM_DETAIL__);
            return;
        }

        if($arrTitleFileDetails != null)
        {
            $strFileName = $arrTitleFileDetails ['full_file_path'];
            if(file_exists($strFileName ) && is_file($strFileName ))
            {
                __debug__printLine("Loading job titles to filter from ".$strFileName."." , C__DISPLAY_ITEM_DETAIL__);
                $classCSVFile = new SimpleScooperCSVClass($strFileName , 'r');
                $arrTitlesTemp = $classCSVFile->readAllRecords(true);
                __debug__printLine(count($arrTitlesTemp) . " titles found in the source file that will be automatically filtered from job listings." , C__DISPLAY_ITEM_DETAIL__);

                //
                // Add each title we found in the file to our list in this class, setting the key for
                // each record to be equal to the job title so we can do a fast lookup later
                //
                $GLOBALS['DATA']['titles_to_filter'] = array();
                $this->_addTitlesToFilterList_($arrTitlesTemp);
            }
        }

        $this->_addBadTitlesFromJobsList_();

        if(count($GLOBALS['DATA']['titles_to_filter']) <= 0)
        {
            __debug__printLine("Could not load the list of titles to exclude from '" . $strFileName . "'.  Final list will not be filtered." , C__DISPLAY_WARNING__);
        }
        else
        {
            __debug__printLine("Loaded " . count($GLOBALS['DATA']['titles_to_filter']) . " titles to exclude from '" . $strFileName . "'." , C__DISPLAY_WARNING__);

        }
    }

    /**
     * Initializes the global list of titles we will automatically mark
     * as "not interested" in the final results set.
     */
    function _loadCompaniesRegexesToFilter_()
    {
        $classCSVFile=null;
        $fCompaniesLoaded = false;
        $arrCompanyFileDetails = $GLOBALS['OPTS']['companies_regex_file_details'];
        $strFileName = "";

        if($GLOBALS['DATA']['companies_regex_to_filter'] != null && count($GLOBALS['DATA']['companies_regex_to_filter']) > 0)
        {
            // We've already loaded the companies; go ahead and return right away
            $fCompaniesLoaded = true;
            __debug__printLine("Using previously loaded " . count($GLOBALS['DATA']['companies_regex_to_filter']) . " regexed company strings to exclude." , C__DISPLAY_ITEM_DETAIL__);
            return;
        }
        else if($arrCompanyFileDetails != null && $arrCompanyFileDetails ['full_file_path'] != '')
        {
            $strFileName = $arrCompanyFileDetails ['full_file_path'];
            if(file_exists($strFileName ) && is_file($strFileName ))
            {
                __debug__printLine("Loading job Company regexes to filter from ".$strFileName."." , C__DISPLAY_ITEM_DETAIL__);
                $classCSVFile = new SimpleScooperCSVClass($strFileName , 'r');
                $arrCompaniesTemp = $classCSVFile->readAllRecords(true);
                __debug__printLine(count($arrCompaniesTemp) . " companies found in the source file that will be automatically filtered from job listings." , C__DISPLAY_ITEM_DETAIL__);

                //
                // Add each Company we found in the file to our list in this class, setting the key for
                // each record to be equal to the job Company so we can do a fast lookup later
                //
                $GLOBALS['DATA']['companies_regex_to_filter'] = array();
                foreach($arrCompaniesTemp as $CompanyRecord)
                {
                    $arrRXInput = explode("|", strtolower($CompanyRecord['match_regex']));

                    foreach($arrRXInput as $rxItem)
                    {
                        $rx = '/'.$rxItem.'/';

                        $GLOBALS['DATA']['companies_regex_to_filter'][] = $rx;
                    }
                }
                $fCompaniesLoaded = true;
            }
        }

        if($fCompaniesLoaded == false)
        {
            if($arrCompanyFileDetails['full_file_path'] == '')
                __debug__printLine("No file specified for companies regexes to exclude from '" . $strFileName . "'.  Final list will not be filtered." , C__DISPLAY_WARNING__);
            else
                __debug__printLine("Could not load regex list for companies to exclude from '" . $strFileName . "'.  Final list will not be filtered." , C__DISPLAY_WARNING__);
        }
        else
        {
//            var_dump('$GLOBALS[companies_regex_to_filter]', $GLOBALS['DATA']['companies_regex_to_filter']);
            __debug__printLine("Loaded " . count($GLOBALS['DATA']['companies_regex_to_filter']). " regexes to use for filtering companies." , C__DISPLAY_WARNING__);

        }
    }



    function _loadTitlesRegexesToFilter_()
    {
        $fTitlesLoaded = false;
        $arrTitleFileDetails = $GLOBALS['OPTS']['titles_regex_file_details'];
        $strFileName = "";

        if($GLOBALS['DATA']['titles_regex_to_filter'] != null && count($GLOBALS['DATA']['titles_regex_to_filter']) > 0)
        {
            // We've already loaded the titles; go ahead and return right away
            $fTitlesLoaded = true;
            __debug__printLine("Using previously loaded " . count($GLOBALS['DATA']['titles_regex_to_filter']) . " regexed title strings to exclude." , C__DISPLAY_ITEM_DETAIL__);
            return;
        }
        else if($arrTitleFileDetails != null && $arrTitleFileDetails ['full_file_path'] != '')
        {
            $strFileName = $arrTitleFileDetails ['full_file_path'];
            if(file_exists($strFileName ) && is_file($strFileName ))
            {
                __debug__printLine("Loading job title regexes to filter from ".$strFileName."." , C__DISPLAY_ITEM_DETAIL__);
                $classCSVFile = new SimpleScooperCSVClass($strFileName , 'r');
                $arrTitlesTemp = $classCSVFile->readAllRecords(true);
                __debug__printLine(count($arrTitlesTemp) . " titles found in the source file that will be automatically filtered from job listings." , C__DISPLAY_ITEM_DETAIL__);

                //
                // Add each title we found in the file to our list in this class, setting the key for
                // each record to be equal to the job title so we can do a fast lookup later
                //
                $GLOBALS['DATA']['titles_regex_to_filter'] = array();
                foreach($arrTitlesTemp as $titleRecord)
                {
                    $arrRXInput = explode("|", strtolower($titleRecord['match_regex']));

                    foreach($arrRXInput as $rxItem)
                    {
                        $rx = '/'.$rxItem.'/';

                        $GLOBALS['DATA']['titles_regex_to_filter'][] = $rx;
                    }
                }
                $fTitlesLoaded = true;
            }
        }

        if($fTitlesLoaded == false)
        {
            if($arrTitleFileDetails['full_file_path'] == '')
                __debug__printLine("No file specified for title regexes to exclude from '" . $strFileName . "'.  Final list will not be filtered." , C__DISPLAY_WARNING__);
            else
                __debug__printLine("Could not load regex list for titles to exclude from '" . $strFileName . "'.  Final list will not be filtered." , C__DISPLAY_WARNING__);
        }
        else
        {
//            var_dump('$GLOBALS[titles_regex_to_filter]', $GLOBALS['DATA']['titles_regex_to_filter']);
            __debug__printLine("Loaded regexes to use for filtering titles from '" . $strFileName . "'." , C__DISPLAY_WARNING__);

        }
    }


    function markJobsList_withAutoItems(&$arrJobs, $strCallerDescriptor = "")
    {
        $this->markJobsList_SetAutoExcludedTitlesFromRegex($arrJobs, $strCallerDescriptor);
        $this->markJobsList_SetAutoExcludedTitles($arrJobs, $strCallerDescriptor);
        $this->markJobsList_SetAutoExcludedCompaniesFromRegex($arrJobs, $strCallerDescriptor);
        $this->markJobsList_SetLikelyDuplicatePosts($arrJobs, $strCallerDescriptor);
    }




    // returns an array with the lookup value and true/false for whether found
    //
    private function _getJobFromArrayByKeyPair_($arr, $param1, $param2)
    {
        $ret = array('lookup_value' => null, 'found_in_array' => false );

        // remove spaces from any of the input strings
        $param1= str_replace( ' ', '', $param1);
        $param2= str_replace( ' ', '', $param2);

        if(strlen($param1) >= 0 && strlen($param1) >= 0)
        {
            $ret['lookup_value'] = strtolower(trim($param1)) . '-'. strtolower(trim($param2));
        }
        else
        {
            // Since we're missing one of the values, we can't really create a good lookup.  Instead
            // let's just make sure there's a unique one for the pairing and return that.
            //
            $ret['lookup_value'] = strtolower(trim($param1)) . '-'. strtolower(trim($param2)) . "[ID-" . uniqid(). "]";

        }

        // Did we find a match in the array?
        if($arr[$ret['lookup_value']] != null)
        {
            $ret['found_in_array'] = true;
        }


        return $ret;
    }

    private function getNotesWithDupeIDAdded($strNote, $strNewDupe)
    {
        $retNote = "";
        $strDupeNotes = null;

        $strDupeMarker_Start = "<dupe>";
        $strDupeMarker_End = "</dupe>";

        if(substr_count($strNote, $strDupeMarker_Start)>0)
        {
            $arrNote = explode($strDupeMarker_Start, $strNote);
            $strUserNotePart = $arrNote[0];
            $strDupeNotes = $arrNote[1];
            $strDupeNotes = str_replace($strDupeMarker_End, "", $strDupeNotes);
            $strDupeNotes .= $strDupeNotes ."; ";
        }
        else
        {
            if(strlen($strNote) > 0)
            {
                $strUserNotePart = $strNote;
            }
        }

        return (strlen($strUserNotePart) > 0 ? $strUserNotePart . " " . PHP_EOL : "") . $strDupeMarker_Start . $strDupeNotes . $strNewDupe . $strDupeMarker_End;

    }

    function markJobsList_SetLikelyDuplicatePosts(&$arrToMark, $strCallerDescriptor = null)
    {
        if(count($arrToMark) == 0) return;

        $nJobsMatched = 0;
        $nUniqueRoles = 0;
        $nProblemRolesSkipped= 0;

        $arrKeys_CompanyAndRole = array_column ( $arrToMark, 'key_company_role');
        $arrKeys_JobSiteAndJobID = array_column ( $arrToMark, 'key_jobsite_siteid');

        $arrOneJobListingPerCompanyAndRole = array_unique(array_combine($arrKeys_JobSiteAndJobID, $arrKeys_CompanyAndRole));
        $arrLookup_JobListing_ByCompanyRole = array_flip($arrOneJobListingPerCompanyAndRole);

        __debug__printLine("Checking " . count($arrToMark) . " jobs for duplicates by company/role pairing. ".count($arrLookup_JobListing_ByCompanyRole)." previous roles are being used to seed the process." , C__DISPLAY_SECTION_START__);

        foreach($arrToMark as $job)
        {
            if(!isMarkedInterested_IsBlank($job))
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

                $arrToMark[$indexPrevListingForCompanyRole]['notes'] = $this->getNotesWithDupeIDAdded($arrToMark[$indexPrevListingForCompanyRole]['notes'], $job['key_jobsite_siteid'] );
                $arrToMark[$indexPrevListingForCompanyRole] ['date_last_updated'] = getTodayAsString();

                $arrToMark[$job['key_jobsite_siteid']]['notes'] = $this->getNotesWithDupeIDAdded($arrToMark[$job['key_jobsite_siteid']]['notes'], $indexPrevListingForCompanyRole );
                $arrToMark[$job['key_jobsite_siteid']]['interested'] =  C__STR_TAG_DUPLICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__;
                $arrToMark[$job['key_jobsite_siteid']]['date_last_updated'] = getTodayAsString();

                $nJobsMatched++;
            }

        }

        $strTotalRowsText = "/".count($arrToMark);
        __debug__printLine("Marked  ".$nJobsMatched .$strTotalRowsText ." roles as likely duplicates based on company/role. " , C__DISPLAY_ITEM_RESULT__);

    }


    function markJobsList_SetAutoExcludedTitles(&$arrToMark, $strCallerDescriptor = null)
    {
        if(count($arrToMark) == 0) return;
        __debug__printLine("Excluding Jobs by Exact Title Matches", C__DISPLAY_ITEM_START__);
        $this->_loadTitlesToFilter_();

        $nJobsSkipped = 0;
        $nJobsNotMarked = 0;
        $nJobsMarkedAutoExcluded = 0;


        __debug__printLine("Checking ".count($arrToMark) ." roles against ". count($GLOBALS['DATA']['titles_to_filter']) ." excluded titles.", C__DISPLAY_ITEM_DETAIL__);

        foreach($arrToMark as $job)
        {
            $strJobIndex = getArrayKeyValueForJob($job);
            // First, make sure we don't already have a value in the interested column.
            // if we do, skip it and move to the next one
//            if($job['interested'] == null || strlen($job['interested']) <= 0)
            if(!isJobAutoUpdatable($job))
            {
                $nJobsSkipped++;
                continue;
            }

            $strJobKeyToMatch = strScrub($job['job_title'], REPLACE_SPACES_WITH_HYPHENS | DEFAULT_SCRUB );

            // Look for a matching title in our list of excluded titles
            $varValMatch =  $GLOBALS['DATA']['titles_to_filter'][$strJobKeyToMatch];

            // Look for a matching title in our list of excluded titles
//            __debug__printLine("Matching listing job title '".$job['job_title'] ."' and found " . (!$varValMatch  ? "nothing" : var_export($varValMatch, true)) . " for " . $this->arrTitlesToFilter[$job['job_title']], C__DISPLAY_ITEM_DETAIL__);

            // if we got a match, we'll get an array back with that title and some other data
            // such as the reason it's excluded
            //


            if($varValMatch != null && $varValMatch['exclude_reason'] != null)
            {
                if(strlen($varValMatch['exclude_reason']) > 0)
                {
                    $arrToMark[$strJobIndex]['interested'] = $varValMatch['exclude_reason'] . C__STR_TAG_AUTOMARKEDJOB__;
                }
                else
                {
                    $arrToMark[$strJobIndex]['interested'] = 'No (EXCLUDED TITLE BUT UNKNOWN REASON VALUE)';
                    $arrToMark[$strJobIndex]['date_last_updated'] = getTodayAsString();
                    __debug__printLine("Excluded title " . $job['job_title'] . " did not have an exclude reason.  Cannot mark.", C__DISPLAY_ERROR__);
                }
                $nJobsMarkedAutoExcluded++;
            }
            else              // we're ignoring the Excluded column fact for the time being. If it's in the list, it's excluded
            {
                $nJobsNotMarked++;
//                __debug__printLine("Job title '".$job['job_title'] ."' was not found in the exclusion list.  Keeping for review." , C__DISPLAY_ITEM_DETAIL__);
            }


        }

        $strTotalRowsText = "/".count($arrToMark);
        __debug__printLine("Automatically marked ".$nJobsMarkedAutoExcluded .$strTotalRowsText ." roles " . ($strCallerDescriptor != null ? "from " . $strCallerDescriptor : "") . " as 'No/Not Interested' because the job title was in the exclusion list. (Skipped: " . $nJobsSkipped . $strTotalRowsText ."; Untouched: ". $nJobsNotMarked . $strTotalRowsText .")" , C__DISPLAY_ITEM_RESULT__);
    }
    function markJobsList_SetAutoExcludedCompaniesFromRegex(&$arrToMark, $strCallerDescriptor = null)
    {
        if(count($arrToMark) == 0) return;
        $this->_loadCompaniesRegexesToFilter_();
        $fMatched = false;

        $nJobsNotMarked = 0;
        $nJobsMarkedAutoExcluded = 0;

        __debug__printLine("Excluding Jobs by Companies Regex Matches", C__DISPLAY_ITEM_START__);
        __debug__printLine("Checking ".count($arrToMark) ." roles against ". count($GLOBALS['DATA']['companies_regex_to_filter']) ." excluded companies.", C__DISPLAY_ITEM_DETAIL__);
        $arrJobs_AutoUpdatable= array_filter($arrToMark, "isJobAutoUpdatable");
        $nJobsSkipped = count($arrToMark) - count($arrJobs_AutoUpdatable);

        if(count($arrJobs_AutoUpdatable) > 0 && count($GLOBALS['DATA']['companies_regex_to_filter']) > 0)
        {
            foreach($arrJobs_AutoUpdatable as $job)
            {
                $fMatched = false;
                // get all the job records that do not yet have an interested value

                foreach($GLOBALS['DATA']['companies_regex_to_filter'] as $rxInput )
                {
                    if(preg_match($rxInput, strScrub($job['company'], DEFAULT_SCRUB)))
                    {
                        $strJobIndex = getArrayKeyValueForJob($job);
                        $arrToMark[$strJobIndex]['interested'] = 'No (Wrong Company)' . C__STR_TAG_AUTOMARKEDJOB__;
                        if(strlen($arrToMark[$strJobIndex]['notes']) > 0) { $arrToMark[$strJobIndex]['notes'] = $arrToMark[$strJobIndex]['notes'] . " "; }
                        $arrToMark[$strJobIndex]['notes'] = "Matched regex[". $rxInput ."]". C__STR_TAG_AUTOMARKEDJOB__;
                        $arrToMark[$strJobIndex]['date_last_updated'] = getTodayAsString();
                        $nJobsMarkedAutoExcluded++;
                        $fMatched = true;
                        break;
                    }
                    else              // we're ignoring the Excluded column fact for the time being. If it's in the list, it's excluded
                    {
                        $nJobsNotMarked++;
                    }
                    if($fMatched == true) break;
                }

//                if($fMatched == false)
//                  __debug__printLine("Company '".$job['company'] ."' was not found in the companies exclusion regex list.  Keeping for review." , C__DISPLAY_ITEM_DETAIL__);

            }
        }
        $strTotalRowsText = "/".count($arrToMark);
        __debug__printLine("Jobs marked not interested via companies regex(".$nJobsMarkedAutoExcluded . $strTotalRowsText .") , skipped: " . $nJobsSkipped . $strTotalRowsText .", untouched: ". $nJobsNotMarked . $strTotalRowsText .")" , C__DISPLAY_ITEM_RESULT__);
    }

    function markJobsList_SetAutoExcludedTitlesFromRegex(&$arrToMark, $strCallerDescriptor = null)
    {
        if(count($arrToMark) == 0) return;

        $this->_loadTitlesRegexesToFilter_();

        $nJobsNotMarked = 0;
        $nJobsMarkedAutoExcluded = 0;

        __debug__printLine("Excluding Jobs by Title Regex Matches", C__DISPLAY_ITEM_START__);
        __debug__printLine("Checking ".count($arrToMark) ." roles against ". count($GLOBALS['DATA']['titles_regex_to_filter']) ." excluded titles.", C__DISPLAY_ITEM_DETAIL__);
        $arrJobs_AutoUpdatable= array_filter($arrToMark, "isJobAutoUpdatable");
        $nJobsSkipped = count($arrToMark) - count($arrJobs_AutoUpdatable);

        if(count($arrJobs_AutoUpdatable) > 0)
        {
            foreach($arrJobs_AutoUpdatable as $job)
            {
                $fMatched = false;
                // get all the job records that do not yet have an interested value

                if($GLOBALS['DATA']['titles_regex_to_filter'] == null) break;

                foreach($GLOBALS['DATA']['titles_regex_to_filter'] as $rxInput )
                {
                    if(preg_match($rxInput, strScrub($job['job_title'], DEFAULT_SCRUB)))
                    {
                        $strJobIndex = getArrayKeyValueForJob($job);
                        $arrToMark[$strJobIndex]['interested'] = 'No (Title Excluded Via RegEx)' . C__STR_TAG_AUTOMARKEDJOB__;
                        if(strlen($arrToMark[$strJobIndex]['notes']) > 0) { $arrToMark[$strJobIndex]['notes'] = $arrToMark[$strJobIndex]['notes'] . " "; }
                        $arrToMark[$strJobIndex]['notes'] = "Matched regex[". $rxInput ."]". C__STR_TAG_AUTOMARKEDJOB__;
                        $arrToMark[$strJobIndex]['date_last_updated'] = getTodayAsString();
                        $nJobsMarkedAutoExcluded++;
                        $fMatched = true;
                        break;
                    }
                    else              // we're ignoring the Excluded column fact for the time being. If it's in the list, it's excluded
                    {
                        $nJobsNotMarked++;
                    }
                    if($fMatched == true) break;
                }

//                if($fMatched == false)
//                    __debug__printLine("Job title '".$job['job_title'] ."' was not found in the title exclusion regex list.  Keeping for review." , C__DISPLAY_ITEM_DETAIL__);

            }
        }
        $strTotalRowsText = "/".count($arrToMark);
        __debug__printLine("Marked not interested via regex(".$nJobsMarkedAutoExcluded . $strTotalRowsText .") , skipped: " . $nJobsSkipped . $strTotalRowsText .", untouched: ". $nJobsNotMarked . $strTotalRowsText .")" , C__DISPLAY_ITEM_RESULT__);
    }



    function writeJobsListToFile($strOutFilePath, $arrJobsRecordsToUse, $fIncludeFilteredJobsInResults = true, $fFirstAutoMarkJobs = false, $strCallerDescriptor = "", $ext = "CSV", $keysToOutput=null)
    {

        if(!$strOutFilePath || strlen($strOutFilePath) <= 0)
        {
            $strOutFilePath = $this->getOutputFileFullPath();
            __debug__printLine("Warning: writeJobsListToFile was called without an output file name.  Using default value: " . $strOutFilePath, C__DISPLAY_ITEM_DETAIL__);

//            throw new ErrorException("Error: writeJobsListToFile called without an output file path to use.");
        }
        if(count($arrJobsRecordsToUse) == 0)
        {
            __debug__printLine("Warning: writeJobsListToFile had no records to write to  " . $strOutFilePath, C__DISPLAY_ITEM_DETAIL__);

        }

        if($fFirstAutoMarkJobs == true)
        {
            $this->markJobsList_withAutoItems($arrJobsRecordsToUse);
        }

        if($fIncludeFilteredJobsInResults == false)
        {
            $arrJobsRecordsToUse = array_filter($arrJobsRecordsToUse, "includeJobInFilteredList");
//            $arrJobsRecordsToUse = $this->filterOutUninterestedJobs($arrJobsRecordsToUse, $fIncludeFilteredJobsInResults);

        }


        $classCombined = new SimpleScooperCSVClass($strOutFilePath , "w");

        if ($keysToOutput == null) { $keysToOutput = array_keys($this->getEmptyJobListingRecord()); }

        if($ext == 'HTML')
        {
            $strCSS = file_get_contents(dirname(__FILE__) . '/../include/CSVTableStyle.css');
            $classCombined->writeArrayToHTMLFile($arrJobsRecordsToUse, $keysToOutput, $this->arrKeysForDeduping, $strCSS);

        }
        else
        {
            $classCombined->writeArrayToCSVFile($arrJobsRecordsToUse, $keysToOutput, $this->arrKeysForDeduping);
        }
        __debug__printLine($strCallerDescriptor . ($strCallerDescriptor  != "" ? " jobs" : "Jobs") ." list had  ". count($arrJobsRecordsToUse) . " jobs and was written to " . $strOutFilePath , C__DISPLAY_ITEM_START__);

        return $strOutFilePath;

    }


    /**
     * Merge multiple lists of jobs from memory and from file into a new single CSV file of jobs
     *
     *
     * @param  string $strOutFilePath The file to output the jobs list to
     * @param  Array $arrFilesToCombine An array of optional jobs CSV files to combine into the file output CSV
     * @param  Array $arrMyRecordsToInclude An array of optional job records to combine into the file output CSV
     * @param  integer $fIncludeFilteredJobsInResults False if you do not want jobs marked as interested = "No *" excluded from the results
     * @return string $strOutFilePath The file the jobs was written to or null if failed.
     */
    function loadJobsListFromCSVs($arrFilesToLoad)
    {
        $arrRetJobsList = null;

        if(!is_array($arrFilesToLoad) || count($arrFilesToLoad) == 0)
        {
            throw new ErrorException("Error: loadJobsListFromCSVs called with an empty array of file names to load. ");

        }


        __debug__printLine("Loading jobs from " . count($arrFilesToLoad) . " CSV input files: " . var_export($arrFilesToLoad, true), C__DISPLAY_ITEM_START__);

        foreach($arrFilesToLoad as $fileInput)
        {
            $strFilePath = $fileInput['details']['full_file_path'];
            $classCombinedRead = new SimpleScooperCSVClass($strFilePath , "r");
            $arrCurFileJobs = $classCombinedRead->readAllRecords(true, array_keys($this->getEmptyJobListingRecord()));
            $classCombinedRead = null;
            if($arrCurFileJobs != null)
            {
                $arrCurNormalizedJobs =  $this->normalizeJobList($arrCurFileJobs);

                addJobsToJobsList($arrRetJobsList, $arrCurNormalizedJobs);
            }
        }


        __debug__printLine("Loaded " .count($arrRetJobsList)." jobs from " . count($arrFilesToLoad) . " CSV input files.", C__DISPLAY_ITEM_RESULT__);

        return $arrRetJobsList;

    }


    /**
     * Merge multiple lists of jobs from memory and from file into a new single CSV file of jobs
     *
     *
     * @param  string $strOutFilePath The file to output the jobs list to
     * @param  Array $arrFilesToCombine An array of optional jobs CSV files to combine into the file output CSV
     * @param  Array $arrMyRecordsToInclude An array of optional job records to combine into the file output CSV
     * @param  integer $fIncludeFilteredJobsInResults False if you do not want jobs marked as interested = "No *" excluded from the results
     * @return string $strOutFilePath The file the jobs was written to or null if failed.
     */
    function writeMergedJobsCSVFile($strOutFilePath, $arrFilesToCombine, $arrMyRecordsToInclude = null, $fIncludeFilteredJobsInResults = true)
    {
        $arrRetJobs = array();
        if(!$strOutFilePath || strlen($strOutFilePath) <= 0)
        {
            $strOutFilePath = $this->getOutputFileFullPath('writeMergedJobsCSVFile_');
        }


        if(!is_array($arrFilesToCombine) || count($arrFilesToCombine) == 0)
        {
            if(count($arrMyRecordsToInclude) > 0)
            {
                $this->writeJobsListToFile($strOutFilePath, $arrRetJobs, $fIncludeFilteredJobsInResults);
            }
            else
            {
                throw new ErrorException("Error: writeMergedJobsCSVFile called with an empty array of filenames to combine. ");

            }

        }
        else
        {


            __debug__printLine("Combining jobs into " . $strOutFilePath . " from " . count($arrMyRecordsToInclude) ." records and " . count($arrFilesToCombine) . " CSV input files: " . var_export($arrFilesToCombine, true), C__DISPLAY_ITEM_DETAIL__);



            if(count($arrFilesToCombine) > 1)
            {
                $classCombined = new SimpleScooperCSVClass($strOutFilePath , "w");
                $arrRetJobs = $classCombined->readMultipleCSVsAndCombine($arrFilesToCombine, array_keys($this->getEmptyJobListingRecord()), $this->arrKeysForDeduping);

            }
            else if(count($arrFilesToCombine) == 1)
            {
                $classCombinedRead = new SimpleScooperCSVClass($arrFilesToCombine[0], "r");
                $arrRetJobs = $classCombinedRead->readAllRecords(true, array_keys($this->getEmptyJobListingRecord()));
            }


            if(count($arrMyRecordsToInclude) > 1)
            {
                $arrRetJobs = my_merge_add_new_keys($arrMyRecordsToInclude, $arrRetJobs);
            }

            $this->writeJobsListToFile($strOutFilePath, $arrRetJobs, $fIncludeFilteredJobsInResults);
            __debug__printLine("Combined file has ". count($arrRetJobs) . " jobs and was written to " . $strOutFilePath , C__DISPLAY_ITEM_START__);

        }
        return $strOutFilePath;

    }



    function getSimpleObjFromPathOrURL($filePath = "", $strURL = "")
    {
//         __debug__printLine("getSimpleObjFromPathOrURL(".$filePath.', '.$strURL.")", C__DISPLAY_ITEM_DETAIL__);
        $objSimpleHTML = null;

        if(!$objSimpleHTML && ($filePath && strlen($filePath) > 0))
        {
            __debug__printLine("Loading ALTERNATE results from ".$filePath, C__DISPLAY_ITEM_START__);
            $objSimpleHTML =  $this->getSimpleHTMLObjForFileContents($filePath);
        }


        if(!$objSimpleHTML && $strURL && strlen($strURL) > 0)
        {
//             __debug__printLine("Loading results from ".$strURL, C__DISPLAY_ITEM_DETAIL__);
            $class = new APICallWrapperClass();
            $retHTML = $class->curl($strURL, null, 'GET');
            if(count(strlen($retHTML['output']) > 0))
            {
                $objSimpleHTML = str_get_html($retHTML['output']);
            }
            else
            {
                $options  = array('http' => array( 'timeout' => 30, 'user_agent' => C__STR_USER_AGENT__));
                $context  = stream_context_create($options);
                $objSimpleHTML = file_get_html($strURL, false, $context);
            }
        }

        if(!$objSimpleHTML)
        {
            throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$filePath.') or '.$strURL);
        }

        return $objSimpleHTML;
    }


    function getOutputFileFullPath($strFilePrefix = "", $strBase = 'jobs', $strExtension = 'csv')
    {
        $strNewFileName = getDefaultJobsOutputFileName($strFilePrefix, $strBase , $strExtension);

        $detailsNewFile = parseFilePath($this->detailsMyFileOut['directory'] . $strNewFileName);

        return $detailsNewFile['full_file_path'];
    }


    function getSimpleHTMLObjForFileContents($strInputFileFullPath)
    {
        $objSimpleHTML = null;
        __debug__printLine("Loading HTML from ".$strInputFileFullPath, C__DISPLAY_ITEM_DETAIL__);

        if(!file_exists($strInputFileFullPath) && !is_file($strInputFileFullPath))  return $objSimpleHTML;
        $fp = fopen($strInputFileFullPath , 'r');
        if(!$fp ) return $objSimpleHTML;

        $strHTML = fread($fp, MAX_FILE_SIZE);
        $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
        $objSimpleHTML = $dom->load($strHTML, $lowercase, $stripRN);
        fclose($fp);

        return $objSimpleHTML;
    }

}


?>
