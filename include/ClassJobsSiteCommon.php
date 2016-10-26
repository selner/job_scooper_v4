<?php
/**
 * Copyright 2014-15 Bryan Selner
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
require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/lib/array_column.php');
require_once(__ROOT__.'/include/JobListHelpers.php');


define('JOBS_SCOOPER_MAX_FILE_SIZE', 1024000);

class ClassJobsSiteCommon
{

    private $arrKeysForDeduping = array('key_jobsite_siteid');

    protected $detailsMyFileOut= "";
    protected $arrSearchesToReturn = null;

    function __construct($strOutputDirectory = null)
    {
        if($strOutputDirectory != null)
        {
            $this->detailsMyFileOut = \Scooper\parseFilePath($strOutputDirectory, false);
        }

    }

    function getEmptySearchDetailsRecord()
    {
        return array(
            'key' => null,
            'name' => null,
            'site_name' => null,
            'search_start_url' => null,
            'keywords_string_for_url' => null,
            'location_search_value' => null,
            'base_url_format' => null,
            'user_setting_flags' => C__USER_KEYWORD_MATCH_DEFAULT,
            'location_user_specified_override' => null,
            'location_set' => null,
            'keyword_search_override' => null,
            'keyword_set' => null,
        );
    }

    function is_OutputInterimFiles()
    {
        $valInterimFiles = \Scooper\get_PharseOptionValue('output_interim_files');

        if(isset($valInterimFiles) && $valInterimFiles == true)
        {
            return true;
        }

        return false;
    }

    function cloneSearchDetailsRecordExceptFor($srcDetails, $arrDontCopyTheseKeys = array())
    {
        $retDetails = $this->getEmptySearchDetailsRecord();
        $retDetails = array_merge($retDetails, $srcDetails);
        foreach($arrDontCopyTheseKeys as $key)
        {
            $retDetails[$key] = null;
        }

        return $retDetails;

    }

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

    protected  function _getKeywordMatchFlagFromString_($strMatchType)
    {
        $retFlag = null;

        if($strMatchType)
        {
            switch($strMatchType)
            {
                case C__USER_KEYWORD_MUST_BE_IN_TITLE_AS_STRING:
                    $retFlag = C__USER_KEYWORD_MUST_BE_IN_TITLE;
                    break;

                case C__USER_KEYWORD_MUST_EQUAL_TITLE_AS_STRING:
                    $retFlag = C__USER_KEYWORD_MUST_EQUAL_TITLE;
                    break;

                case C__USER_KEYWORD_ANYWHERE_AS_STRING:
                    $retFlag = C__USER_KEYWORD_ANYWHERE;
                    break;
            }
        }

        return $retFlag;
    }

    protected function _getKeywordMatchStringFromFlag_($flag)
    {
        $retString = null;

        if($flag)
        {
            switch($flag)
            {
                case C__USER_KEYWORD_MUST_BE_IN_TITLE:
                    $retString = C__USER_KEYWORD_MUST_BE_IN_TITLE_AS_STRING;
                    break;

                case C__USER_KEYWORD_MUST_EQUAL_TITLE:
                    $retString = C__USER_KEYWORD_MUST_EQUAL_TITLE_AS_STRING;
                    break;

                case C__USER_KEYWORD_ANYWHERE:
                    $retString = C__USER_KEYWORD_ANYWHERE_AS_STRING;
                    break;
            }
        }

        return $retString;
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

    protected function _logMemoryUsage_()
    {
        if(isDebug()) {

            $usage = getPhpMemoryUsage();

            if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("~~~~ PHP memory usage is ".$usage." ~~~~", \Scooper\C__DISPLAY_NORMAL__); }
        }
    }

    function normalizeItem($arrItem)
    {
        $retArrNormalized = $arrItem;

        // For reference, DEFAULT_SCRUB =  REMOVE_PUNCT | HTML_DECODE | LOWERCASE | REMOVE_EXTRA_WHITESPACE

        $retArrNormalized ['job_site'] = \Scooper\strScrub($retArrNormalized['job_site'], DEFAULT_SCRUB);
        $retArrNormalized ['job_id'] = \Scooper\strScrub($retArrNormalized['job_id'], SIMPLE_TEXT_CLEANUP);

        // Removes " NEW!", etc from the job title.  ZipRecruiter tends to occasionally
        // have that appended which then fails de-duplication. (Fixes issue #45) Glassdoor has "- easy apply" as well.
        $retArrNormalized ['job_title'] = str_ireplace(" NEW!", "", $retArrNormalized['job_title']);
        $retArrNormalized ['job_title'] = str_ireplace("- new", "", $retArrNormalized['job_title']);
        $retArrNormalized ['job_title'] = str_ireplace("- easy apply", "", $retArrNormalized['job_title']);
        $retArrNormalized ['job_title'] = \Scooper\strScrub($retArrNormalized['job_title'], SIMPLE_TEXT_CLEANUP);

        $retArrNormalized ['job_site_category'] = \Scooper\strScrub($retArrNormalized['job_site_category'], SIMPLE_TEXT_CLEANUP);
        $retArrNormalized ['job_site_date'] = \Scooper\strScrub($retArrNormalized['job_site_date'], REMOVE_EXTRA_WHITESPACE | LOWERCASE | HTML_DECODE );
        $retArrNormalized ['job_post_url'] = trim($retArrNormalized['job_post_url']); // DO NOT LOWER, BREAKS URLS
        $retArrNormalized ['location'] = \Scooper\strScrub($retArrNormalized['location'], SIMPLE_TEXT_CLEANUP);

        $retArrNormalized ['company'] = \Scooper\strScrub($retArrNormalized['company'], ADVANCED_TEXT_CLEANUP );

        // Remove common company name extensions like "Corporation" or "Inc." so we have
        // a higher match likelihood
//        $retArrNormalized ['company'] = str_replace(array(" corporation", " corp", " inc", " llc"), "", $retArrNormalized['company']);
        $retArrNormalized ['company'] = preg_replace(array("/\s[Cc]orporat[e|ion]/", "/\s[Cc]orp\W{0,1}/", "/.com/", "/\W{0,}\s[iI]nc/", "/\W{0,}\s[lL][lL][cC]/","/\W{0,}\s[lL][tT][dD]/"), "", $retArrNormalized['company']);

        switch(\Scooper\strScrub($retArrNormalized ['company']))
        {
            case "amazon":
            case "amazon com":
            case "a2z":
            case "lab 126":
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
            $retArrNormalized['key_company_role'] = \Scooper\strScrub($retArrNormalized['company'], FOR_LOOKUP_VALUE_MATCHING) . \Scooper\strScrub($retArrNormalized['job_title'], FOR_LOOKUP_VALUE_MATCHING);
        }

        if(strlen($retArrNormalized['key_jobsite_siteid']) <= 0)
        {
            // For craigslist, they change IDs on every post, so deduping that way doesn't help
            // much.  (There's almost never a company for Craiglist listings either.)
            // Instead for Craiglist listings, we'll dedupe using the role title and the jobsite name
            if(strcasecmp($retArrNormalized['job_site'], "craigslist") == 0)
            {
                $retArrNormalized['key_jobsite_siteid'] = \Scooper\strScrub($retArrNormalized['job_site'], FOR_LOOKUP_VALUE_MATCHING) . \Scooper\strScrub($retArrNormalized['job_title'], FOR_LOOKUP_VALUE_MATCHING) . \Scooper\strScrub($retArrNormalized['job_site_date'], FOR_LOOKUP_VALUE_MATCHING);
            }
            else
            {
                $retArrNormalized['key_jobsite_siteid'] = \Scooper\strScrub($retArrNormalized['job_site'], FOR_LOOKUP_VALUE_MATCHING) . \Scooper\strScrub($retArrNormalized['job_id'], FOR_LOOKUP_VALUE_MATCHING);
            }

        }

        if(strlen($retArrNormalized['date_last_updated']) <= 0)
        {
            $retArrNormalized['date_last_updated'] = $retArrNormalized['date_pulled'];
        }

        return $retArrNormalized;
    }




    private function _addBadTitlesFromJobsList_()
    {
        /*
                $GLOBALS['logger']->logLine(PHP_EOL.PHP_EOL."--TODO-- FINISH JOB TITLES LIST FILTER FROM LOADED JOBS".PHP_EOL.PHP_EOL, \Scooper\C__DISPLAY_ERROR__);

                $arrJobsBadTitles = array_filter($arrJobsList, "onlyBadTitlesAndRoles");

                $arrBadTitlesToAdd = array_column($arrJobsBadTitles, "job_title");

                $this->_addTitlesToFilterList_($arrBadTitlesToAdd);
         */
    }





    function markJobsList_withAutoItems(&$arrJobs, $strCallerDescriptor = "")
    {
        $this->markJobsList_SetAutoExcludedTitlesFromRegex($arrJobs, $strCallerDescriptor);
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
        if(isset($ret['lookup_value']) && isset($arr[$ret['lookup_value']]))
        {
            $ret['found_in_array'] = true;
        }


        return $ret;
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

    function markJobsList_SetLikelyDuplicatePosts(&$arrToMark, $strCallerDescriptor = null)
    {
        if(count($arrToMark) == 0) return;

        $nJobsMatched = 0;

        $arrKeys_CompanyAndRole = array_column ( $arrToMark, 'key_company_role');
        $arrKeys_JobSiteAndJobID = array_column ( $arrToMark, 'key_jobsite_siteid');

        $arrOneJobListingPerCompanyAndRole = array_unique(array_combine($arrKeys_JobSiteAndJobID, $arrKeys_CompanyAndRole));
        $arrLookup_JobListing_ByCompanyRole = array_flip($arrOneJobListingPerCompanyAndRole);

        $GLOBALS['logger']->logLine("Checking " . count($arrToMark) . " jobs for duplicates by company/role pairing. ".count($arrLookup_JobListing_ByCompanyRole)." previous roles are being used to seed the process." , \Scooper\C__DISPLAY_SECTION_START__);

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
                $arrToMark[$indexPrevListingForCompanyRole] ['date_last_updated'] = \Scooper\getTodayAsString();

                $arrToMark[$job['key_jobsite_siteid']]['notes'] = $this->getNotesWithDupeIDAdded($arrToMark[$job['key_jobsite_siteid']]['notes'], $indexPrevListingForCompanyRole );
                $arrToMark[$job['key_jobsite_siteid']]['interested'] =  C__STR_TAG_DUPLICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__;
                $arrToMark[$job['key_jobsite_siteid']]['date_last_updated'] = \Scooper\getTodayAsString();

                $nJobsMatched++;
            }

        }

        $strTotalRowsText = "/".count($arrToMark);
        $GLOBALS['logger']->logLine("Marked  ".$nJobsMatched .$strTotalRowsText ." roles as likely duplicates based on company/role. " , \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    function markJobsList_SetAutoExcludedCompaniesFromRegex(&$arrToMark, $strCallerDescriptor = null)
    {
        if(count($arrToMark) == 0) return;

        $nJobsNotMarked = 0;
        $nJobsMarkedAutoExcluded = 0;

        $GLOBALS['logger']->logLine("Excluding Jobs by Companies Regex Matches", \Scooper\C__DISPLAY_ITEM_START__);
        $GLOBALS['logger']->logLine("Checking ".count($arrToMark) ." roles against ". count($GLOBALS['DATA']['companies_regex_to_filter']) ." excluded companies.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
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
                    if(preg_match($rxInput, \Scooper\strScrub($job['company'], DEFAULT_SCRUB)))
                    {
                        $strJobIndex = getArrayKeyValueForJob($job);
                        $arrToMark[$strJobIndex]['interested'] = 'No (Wrong Company)' . C__STR_TAG_AUTOMARKEDJOB__;
                        if(strlen($arrToMark[$strJobIndex]['notes']) > 0) { $arrToMark[$strJobIndex]['notes'] = $arrToMark[$strJobIndex]['notes'] . " "; }
                        $arrToMark[$strJobIndex]['notes'] = "Matched regex[". $rxInput ."]". C__STR_TAG_AUTOMARKEDJOB__;
                        $arrToMark[$strJobIndex]['date_last_updated'] = \Scooper\getTodayAsString();
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
        $strTotalRowsText = "/".count($arrToMark);
        $GLOBALS['logger']->logLine("Jobs marked not interested via companies regex(".$nJobsMarkedAutoExcluded . $strTotalRowsText .") , skipped: " . $nJobsSkipped . $strTotalRowsText .", untouched: ". $nJobsNotMarked . $strTotalRowsText .")" , \Scooper\C__DISPLAY_ITEM_RESULT__);
    }



    function markJobsList_SetAutoExcludedTitlesFromRegex(&$arrToMark, $strCallerDescriptor = null)
    {
        if(count($arrToMark) == 0) return;

        $nJobsMarkedAutoExcluded = 0;

        $GLOBALS['logger']->logLine("Excluding Jobs by Title Regex Matches", \Scooper\C__DISPLAY_ITEM_START__);
        $GLOBALS['logger']->logLine("Checking ".count($arrToMark) ." roles against ". count($GLOBALS['DATA']['titles_regex_to_filter']) ." excluded titles.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $arrJobs_AutoUpdatable= array_filter($arrToMark, "isJobAutoUpdatable");
        $nJobsSkipped = count($arrToMark) - count($arrJobs_AutoUpdatable);

        if(count($arrJobs_AutoUpdatable) > 0)
        {
            try
            {
                foreach($arrJobs_AutoUpdatable as $job)
                {
                    if($GLOBALS['DATA']['titles_regex_to_filter'] == null) break;
                    $strScrubbedJobTitle = \Scooper\strScrub($job['job_title'], DEFAULT_SCRUB);

                    $arrMatches = array();
                    $arrMatchErrors = array();
                    $success = preg_match_multiple($GLOBALS['DATA']['titles_regex_to_filter'], $strScrubbedJobTitle, $arrMatches , null, $arrMatchErrors );

                    if($success == false)
                    {
                        $GLOBALS['logger']->logLine("Errors with title exclusion regexes: ". getArrayValuesAsString($arrMatchErrors), \Scooper\C__DISPLAY_WARNING__);
                    }
                    if(count($arrMatches) > 0)
                    {
                        $strTitleREMatches = getArrayValuesAsString(array_keys($arrMatches), "|", "", false );
                        $strJobIndex = getArrayKeyValueForJob($job);

                        $arrToMark[$strJobIndex]['interested'] = 'No (Title Excluded Via RegEx)' . C__STR_TAG_AUTOMARKEDJOB__;
                        if(strlen($arrToMark[$strJobIndex]['notes']) > 0) { $arrToMark[$strJobIndex]['notes'] = $arrToMark[$strJobIndex]['notes'] . " "; }
                        $arrToMark[$strJobIndex]['notes'] = "Title matched exclusion regex [". $strTitleREMatches  ."]". C__STR_TAG_AUTOMARKEDJOB__;
                        $arrToMark[$strJobIndex]['date_last_updated'] = \Scooper\getTodayAsString();
                    }
                }
            }
            catch (Exception $ex)
            {
                $GLOBALS['logger']->logLine('ERROR:  Failed to verify titles against regex strings due to error: '. $ex->getMessage(), \Scooper\C__DISPLAY_ERROR__);
                if(isDebug()) { throw $ex; }
            }
        }
        $strTotalRowsText = "/".count($arrToMark);
        $nAutoExcludedTitleRegex = count(array_filter($arrToMark, "isInterested_TitleExcludedViaRegex"));

        $GLOBALS['logger']->logLine("Marked not interested via regex(".$nAutoExcludedTitleRegex . $strTotalRowsText .") , skipped: " . $nJobsSkipped . $strTotalRowsText .", untouched: ". (count($arrToMark) - $nJobsSkipped - $nAutoExcludedTitleRegex) . $strTotalRowsText .")" , \Scooper\C__DISPLAY_ITEM_RESULT__);
    }



    function writeJobsListToFile($strOutFilePath, $arrJobsRecordsToUse, $fIncludeFilteredJobsInResults = true, $fFirstAutoMarkJobs = false, $strCallerDescriptor = "", $ext = "CSV", $keysToOutput=null, $detailsCSSToInclude = null)
    {

        if(!$strOutFilePath || strlen($strOutFilePath) <= 0)
        {
            $strOutFilePath = $this->getOutputFileFullPath();
            $GLOBALS['logger']->logLine("Warning: writeJobsListToFile was called without an output file name.  Using default value: " . $strOutFilePath, \Scooper\C__DISPLAY_ITEM_DETAIL__);

//            throw new ErrorException("Error: writeJobsListToFile called without an output file path to use.");
        }
        if(count($arrJobsRecordsToUse) == 0)
        {
            $GLOBALS['logger']->logLine("Warning: writeJobsListToFile had no records to write to  " . $strOutFilePath, \Scooper\C__DISPLAY_ITEM_DETAIL__);

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


        $classCombined = new \Scooper\ScooperSimpleCSV($strOutFilePath , "w");

        if ($keysToOutput == null) { $keysToOutput = array_keys($this->getEmptyJobListingRecord()); }

        if($ext == 'HTML')
        {
            $strCSS = null;
            if($detailsCSSToInclude['has_file'])
            {
                // $strCSS = file_get_contents(dirname(__FILE__) . '/../include/CSVTableStyle.css');
                $strCSS = file_get_contents($detailsCSSToInclude['full_file_path']);
            }
            $classCombined->writeArrayToHTMLFile($arrJobsRecordsToUse, $keysToOutput, $this->arrKeysForDeduping, $strCSS);

        }
        else
        {
            $classCombined->writeArrayToCSVFile($arrJobsRecordsToUse, $keysToOutput, $this->arrKeysForDeduping);
        }
        $GLOBALS['logger']->logLine($strCallerDescriptor . ($strCallerDescriptor  != "" ? " jobs" : "Jobs") ." list had  ". count($arrJobsRecordsToUse) . " jobs and was written to " . $strOutFilePath , \Scooper\C__DISPLAY_ITEM_START__);

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


        $GLOBALS['logger']->logLine("Loading jobs from " . count($arrFilesToLoad) . " CSV input files: " . var_export($arrFilesToLoad, true), \Scooper\C__DISPLAY_ITEM_START__);

        foreach($arrFilesToLoad as $fileInput)
        {
            $strFilePath = $fileInput['details']['full_file_path'];
            $classCombinedRead = new \Scooper\ScooperSimpleCSV($strFilePath , "r");
            $arrCurFileJobs = $classCombinedRead->readAllRecords(true, array_keys($this->getEmptyJobListingRecord()));
            $arrCurFileJobs = $arrCurFileJobs['data_rows'];
            $classCombinedRead = null;
            if($arrCurFileJobs != null)
            {
                $arrCurNormalizedJobs =  $this->normalizeJobList($arrCurFileJobs);

                addJobsToJobsList($arrRetJobsList, $arrCurNormalizedJobs);
            }
        }


        $GLOBALS['logger']->logLine("Loaded " .count($arrRetJobsList)." jobs from " . count($arrFilesToLoad) . " CSV input files.", \Scooper\C__DISPLAY_ITEM_RESULT__);

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


            $GLOBALS['logger']->logLine("Combining jobs into " . $strOutFilePath . " from " . count($arrMyRecordsToInclude) ." records and " . count($arrFilesToCombine) . " CSV input files: " . var_export($arrFilesToCombine, true), \Scooper\C__DISPLAY_ITEM_DETAIL__);



            if(count($arrFilesToCombine) > 1)
            {
                $classCombined = new \Scooper\ScooperSimpleCSV($strOutFilePath , "w");
                $arrRetJobs = $classCombined->readMultipleCSVsAndCombine($arrFilesToCombine, array_keys($this->getEmptyJobListingRecord()), $this->arrKeysForDeduping);

            }
            else if(count($arrFilesToCombine) == 1)
            {
                $classCombinedRead = new \Scooper\ScooperSimpleCSV($arrFilesToCombine[0], "r");
                $arrRetJobs = $classCombinedRead->readAllRecords(true, array_keys($this->getEmptyJobListingRecord()));
                $arrRetJobs = $arrRetJobs['data_rows'];
            }


            if(count($arrMyRecordsToInclude) > 1)
            {
                $arrRetJobs = \Scooper\my_merge_add_new_keys($arrMyRecordsToInclude, $arrRetJobs);
            }

            $this->writeJobsListToFile($strOutFilePath, $arrRetJobs, $fIncludeFilteredJobsInResults);
            $GLOBALS['logger']->logLine("Combined file has ". count($arrRetJobs) . " jobs and was written to " . $strOutFilePath , \Scooper\C__DISPLAY_ITEM_START__);

        }
        return $strOutFilePath;

    }



    function getSimpleObjFromPathOrURL($filePath = "", $strURL = "", $optTimeout = null)
    {
        $objSimpleHTML = null;

        if(!$objSimpleHTML && ($filePath && strlen($filePath) > 0))
        {
            $GLOBALS['logger']->logLine("Loading ALTERNATE results from ".$filePath, \Scooper\C__DISPLAY_ITEM_START__);
            $objSimpleHTML =  $this->getSimpleHTMLObjForFileContents($filePath);
        }


        if(!$objSimpleHTML && $strURL && strlen($strURL) > 0)
        {
            $class = new \Scooper\ScooperDataAPIWrapper();
            if(isVerbose()) $class->setVerbose(true);

            $retHTML = $class->curl($strURL, null, 'GET', null, null, null, null, $optTimeout);
            if(count(strlen($retHTML['output']) > 0))
            {
                $objSimpleHTML = SimpleHtmlDom\str_get_html($retHTML['output']);
            }
            else
            {
                $options  = array('http' => array( 'timeout' => 30, 'user_agent' => C__STR_USER_AGENT__));
                $context  = stream_context_create($options);
                $objSimpleHTML = SimpleHtmlDom\file_get_html($strURL, false, $context);
            }
        }

        if(!$objSimpleHTML)
        {
            throw new ErrorException('Error:  unable to get SimpleHtmlDom\SimpleHTMLDom object from file('.$filePath.') or '.$strURL);
        }

        return $objSimpleHTML;
    }

    function getOutputFileFullPath($strFilePrefix = "", $strBase = 'jobs', $strExtension = 'csv')
    {
        $strNewFileName = getDefaultJobsOutputFileName($strFilePrefix, $strBase , $strExtension);

        $detailsNewFile = \Scooper\parseFilePath($this->detailsMyFileOut['directory'] . $strNewFileName);

        return $detailsNewFile['full_file_path'];
    }




    function getSimpleHTMLObjForFileContents($strInputFileFullPath)
    {
        $objSimpleHTML = null;
        $GLOBALS['logger']->logLine("Loading HTML from ".$strInputFileFullPath, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        if(!file_exists($strInputFileFullPath) && !is_file($strInputFileFullPath))  return $objSimpleHTML;
        $fp = fopen($strInputFileFullPath , 'r');
        if(!$fp ) return $objSimpleHTML;

        $strHTML = fread($fp, JOBS_SCOOPER_MAX_FILE_SIZE);
        $dom = new SimpleHtmlDom\simple_html_dom(null, null, true, null, null, null, null);
        $objSimpleHTML = $dom->load($strHTML);
        fclose($fp);

        return $objSimpleHTML;
    }


}



?>
