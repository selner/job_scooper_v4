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
require_once(__ROOT__.'/include/S3Manager.php');
require_once(__ROOT__.'/include/ClassJobsNotifier.php');
require_once(__ROOT__.'/include/JobsAutoMarker.php');

class StageManager extends ClassJobsSiteCommon
{
    protected $siteName = "StageManager";
    protected $classConfig = null;
    private $arrLatestJobs_UnfilteredByUserInput = array();
    private $arrMarkedJobs = array();

    private $_arrSearchesToRun_ = array();

    function __construct()
    {
        $this->classConfig = new ClassConfig();
        $this->classConfig->initialize();
    }

    function __destruct()
    {
        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }

    }


    public function runAll()
    {
        $this->doStage1();
        $this->doStage2();
        $this->doStage3();
        $this->doStage4();
    }


    public function doStage1()
    {

        $GLOBALS['logger']->logLine(PHP_EOL."Setting up searches for this specific run.".PHP_EOL, \Scooper\C__DISPLAY_SECTION_START__);


        //
        // let's start with the searches specified with the details in the the config.ini
        //
        $arrSearchesToRun = $this->classConfig->getSearchConfiguration('searches');

        if(isset($arrSearchesToRun))
        {
            if(count($arrSearchesToRun) > 0)
            {

                //
                // Remove any sites that were excluded in this run from the searches list
                //
                for($z = 0; $z < count($arrSearchesToRun) ; $z++)
                {
                    $curSearch = $arrSearchesToRun[$z];

                    $strIncludeKey = 'include_'.$curSearch['site_name'];

                    $valInclude = \Scooper\get_PharseOptionValue($strIncludeKey);

                    if(!isset($valInclude) || $valInclude == 0)
                    {
                        $GLOBALS['logger']->logLine($curSearch['site_name'] . " excluded, so dropping its searches from the run.", \Scooper\C__DISPLAY_ITEM_START__);
                        unset($arrSearchesToRun[$z]);
                    }
                }
            }

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //
            // OK, now we have our list of searches & sites we are going to actually run
            // Let's go get the jobs for those searches
            //
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            if ($arrSearchesToRun != null)
            {
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                //
                // Download all the job listings for all the users searches
                //
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $GLOBALS['logger']->logLine(PHP_EOL."**************  Starting Run of " . count($arrSearchesToRun) . " Searches  **************  ".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);


                //
                // the Multisite class handles the heavy lifting for us by executing all
                // the searches in the list and returning us the combined set of new jobs
                // (with the exception of Amazon for historical reasons)
                //

                $classMulti = new ClassMultiSiteSearch($this->classConfig->getFileDetails('output_subfolder')['directory']);
                $classMulti->addMultipleSearches($arrSearchesToRun, null);
                $arrUpdatedJobs = $classMulti->updateJobsForAllPlugins();
                $this->arrLatestJobs_UnfilteredByUserInput = \Scooper\array_copy($arrUpdatedJobs);

                if($this->is_OutputInterimFiles() == true) {

                    //
                    // Let's save off the unfiltered jobs list in case we need it later.  The $this->arrLatestJobs
                    // will shortly have the user's input jobs applied to it
                    //
                    $strRawJobsListOutput = \Scooper\getFullPathFromFileDetails($this->classConfig->getFileDetails('output_subfolder'), "", "_rawjobslist_preuser_filtering");
                    $this->writeRunsJobsToFile($strRawJobsListOutput, $this->arrLatestJobs_UnfilteredByUserInput, "RawJobsList_PreUserDataFiltering");
                    $GLOBALS['logger']->logLine(count($this->arrLatestJobs_UnfilteredByUserInput). " raw, latest job listings from " . count($arrSearchesToRun) . " search(es) downloaded to " . $strRawJobsListOutput, \Scooper\C__DISPLAY_SUMMARY__);
                }
            } else {
                throw new ErrorException("No searches have been set to be run.");
            }

            // TODO:  Remove this local copy when we refactor the output section later
            $GLOBALS['USERDATA']['searches_for_run'] = \Scooper\array_copy($arrSearchesToRun);
        }
    }

    public function doStage2()
    {
        $PYTHONPATH = realpath(__DIR__ ."/../python/pyJobNormalizer/normalizeS3JobListings.py");
        $cmd = "python " . $PYTHONPATH . " -b " . $GLOBALS['USERDATA']['AWS']['S3']['bucket'] . " -k job_title --index key_jobsite_siteid";
        $GLOBALS['logger']->logLine("Running command: " . $cmd   , \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $cmdOutput = array();
        $cmdRet = "";
        exec($cmd, $cmdOutput, $cmdRet);
        foreach($cmdOutput as $resultLine)
            $GLOBALS['logger']->logLine($resultLine, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $prefix = "staging/" . STAGE2_PATHKEY ."/";
        $s3 = new S3Manager($GLOBALS['USERDATA']['AWS']['S3']['bucket'], $GLOBALS['USERDATA']['AWS']['S3']['region']);
        $details = \Scooper\getFilePathDetailsFromString($GLOBALS['USERDATA']['directories']['stage2'], \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);

        $s3->downloadObjectsToFile($prefix, $details['directory']);

        $this->arrLatestJobs_UnfilteredByUserInput = null;

        $filesToLoad = array_filter(scandir($details['directory']), function($file) { return (strcasecmp(substr($file, strlen($file)-5, 5), ".json") == 0); });
        foreach($filesToLoad as $file)
        {
            $fileFullPath = $details['directory'] . DIRECTORY_SEPARATOR . $file;
            $jsonText = file_get_contents($fileFullPath, FILE_TEXT);
            $arrJobs = json_decode($jsonText, true, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
            addJobsToJobsList($this->arrLatestJobs_UnfilteredByUserInput, $arrJobs);
        }

    }

    public function doStage3()
    {


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $GLOBALS['logger']->logLine(PHP_EOL . "**************  Updating jobs list for known filters ***************" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
        $marker = new JobsAutoMarker($this->arrLatestJobs_UnfilteredByUserInput);
        $marker->markJobsList();
        $this->arrMarkedJobs = $marker->getMarkedJobs();

    }

    public function doStage4()
    {
        $notifier = new ClassJobsNotifier($this->arrLatestJobs_UnfilteredByUserInput, $this->arrMarkedJobs);
        $notifier->processNotifications();

    }




    private function _markJobsList_withAutoItems_()
    {
        $this->arrLatestJobs = \Scooper\array_copy($this->arrLatestJobs_UnfilteredByUserInput);
        $this->_markJobsList_SetLikelyDuplicatePosts_();
        $this->_markJobsList_SearchKeywordsNotFound_();
        $this->_markJobsList_SetAutoExcludedTitles_();
        $this->_markJobsList_SetAutoExcludedCompaniesFromRegex_();
    }




    private function _markJobsList_SetLikelyDuplicatePosts_()
    {
        if(count($this->arrLatestJobs) == 0) return;

        $nJobsMatched = 0;

        $arrKeys_CompanyAndRole = array_column ( $this->arrLatestJobs, 'key_company_role');
        $arrKeys_JobSiteAndJobID = array_column ( $this->arrLatestJobs, 'key_jobsite_siteid');


        $arrUniqIds = array_unique($arrKeys_CompanyAndRole);
        $nUniqJobs = countAssociativeArrayValues($arrUniqIds);
        $arrOneJobListingPerCompanyAndRole = array_unique_multidimensional(array_combine($arrKeys_JobSiteAndJobID, $arrKeys_CompanyAndRole));
        $arrLookup_JobListing_ByCompanyRole = array_flip($arrOneJobListingPerCompanyAndRole);

        $GLOBALS['logger']->logLine("Marking Duplicate Job Roles" , \Scooper\C__DISPLAY_SECTION_START__);
        $GLOBALS['logger']->logLine("Auto-marking" . $nUniqJobs . " duplicated froms from " . countAssociativeArrayValues($this->arrLatestJobs) . " total jobs based on company/role pairing. " , \Scooper\C__DISPLAY_ITEM_DETAIL__);

        foreach($this->arrLatestJobs as $job)
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
                appendJobColumnData($this->arrLatestJobs[$indexPrevListingForCompanyRole], 'match_notes', "|", $this->getNotesWithDupeIDAdded($this->arrLatestJobs[$indexPrevListingForCompanyRole]['match_notes'], $job['key_jobsite_siteid'] ));
                $this->arrLatestJobs[$indexPrevListingForCompanyRole] ['date_last_updated'] = getTodayAsString();

                $this->arrLatestJobs[$strCurrentJobIndex]['interested'] =  C__STR_TAG_DUPLICATE_POST__ . " " . C__STR_TAG_AUTOMARKEDJOB__;
                appendJobColumnData($this->arrLatestJobs[$strCurrentJobIndex], 'match_notes', "|", $this->getNotesWithDupeIDAdded($this->arrLatestJobs[$strCurrentJobIndex]['match_notes'], $indexPrevListingForCompanyRole ));
                $this->arrLatestJobs[$strCurrentJobIndex]['date_last_updated'] = getTodayAsString();

                $nJobsMatched++;
            }

        }

        $strTotalRowsText = "/".count($this->arrLatestJobs);
        $GLOBALS['logger']->logLine("Marked  ".$nJobsMatched .$strTotalRowsText ." roles as likely duplicates based on company/role. " , \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    private function _markJobsList_SetAutoExcludedCompaniesFromRegex_()
    {
        if(count($this->arrLatestJobs) == 0) return;

        $nJobsNotMarked = 0;
        $nJobsMarkedAutoExcluded = 0;

        $GLOBALS['logger']->logLine("Excluding Jobs by Companies Regex Matches", \Scooper\C__DISPLAY_ITEM_START__);
        $GLOBALS['logger']->logLine("Checking ".count($this->arrLatestJobs) ." roles against ". count($GLOBALS['USERDATA']['companies_regex_to_filter']) ." excluded companies.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
        $arrJobs_AutoUpdatable= array_filter($this->arrLatestJobs, "isJobAutoUpdatable");
        $nJobsSkipped = count($this->arrLatestJobs) - count($arrJobs_AutoUpdatable);

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
                        $this->arrLatestJobs[$strJobIndex]['interested'] = 'No (Wrong Company)' . C__STR_TAG_AUTOMARKEDJOB__;
                        appendJobColumnData($this->arrLatestJobs[$strJobIndex], 'match_notes', "|", "Matched regex[". $rxInput ."]");
                        appendJobColumnData($this->arrLatestJobs[$strJobIndex], 'match_details',"|", "excluded_company");
                        $this->arrLatestJobs[$strJobIndex]['date_last_updated'] = getTodayAsString();
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
            $arrTitlesTokened = tokenizeMultiDimensionArray($arrTitlesWithBlanks,  "jobList", "job_title", "key_jobsite_siteid");

            foreach($arrTitlesTokened as $job)
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


    private function _markJobsList_SearchKeywordsNotFound_()
    {
        $arrKwdSet = array();
        $arrJobsStillActive = array_filter($this->arrLatestJobs, "isMarkedBlank");
        $nStartingBlankCount = countAssociativeArrayValues($arrJobsStillActive);
        foreach($this->_arrSearchesToRun_ as $search)
        {
            foreach($search['tokenized_keywords'] as $kwdset)
            {
                $arrKwdSet[$kwdset] = explode(" ", $kwdset);
            }
            $arrKwdSet = \Scooper\my_merge_add_new_keys($arrKwdSet, $arrKwdSet);
        }

        $ret = $this->_getJobsList_MatchingJobTitleKeywords_($arrJobsStillActive, $arrKwdSet, "TitleKeywordSearchMatch");
        foreach($ret['notmatched'] as $job)
        {
            $strJobIndex = getArrayKeyValueForJob($job);
            $this->arrLatestJobs[$strJobIndex]['interested'] = NO_TITLE_MATCHES;
            $this->arrLatestJobs[$strJobIndex]['date_last_updated'] = getTodayAsString();
            appendJobColumnData($this->arrLatestJobs[$strJobIndex], 'match_notes', "|", "title keywords not matched to terms [". getArrayValuesAsString($arrKwdSet, "|", "", false)  ."]");
            appendJobColumnData($this->arrLatestJobs[$strJobIndex], 'match_details',"|", NO_TITLE_MATCHES);
        }

        $nEndingBlankCount = countAssociativeArrayValues(array_filter($this->arrLatestJobs, "isMarkedBlank"));
        $GLOBALS['logger']->logLine("Processed " . $nStartingBlankCount . "/" . countAssociativeArrayValues($this->arrLatestJobs) . " jobs marking if did not match title keyword search:  updated ". ($nStartingBlankCount - $nEndingBlankCount) . "/" . $nStartingBlankCount  . ", still active ". $nEndingBlankCount . "/" . $nStartingBlankCount, \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    private function _markJobsList_SetAutoExcludedTitles_()
    {
        $arrJobsStillActive = array_filter($this->arrLatestJobs, "isMarkedBlank");
        $nStartingBlankCount = countAssociativeArrayValues($arrJobsStillActive);

        $ret = $this->_getJobsList_MatchingJobTitleKeywords_($arrJobsStillActive, $GLOBALS['USERDATA']['title_negative_keyword_tokens'], "TitleNegativeKeywords");
        foreach($ret['matched'] as $job)
        {
            $strJobIndex = getArrayKeyValueForJob($job);
            $this->arrLatestJobs[$strJobIndex]['interested'] = TITLE_NEG_KWD_MATCH;
            $this->arrLatestJobs[$strJobIndex]['date_last_updated'] = getTodayAsString();
            appendJobColumnData($this->arrLatestJobs[$strJobIndex], 'match_notes', "|", "matched negative keyword title[". getArrayValuesAsString($job['keywords_matched'], "|", "", false)  ."]");
            appendJobColumnData($this->arrLatestJobs[$strJobIndex], 'match_details',"|", TITLE_NEG_KWD_MATCH);
        }
        $nEndingBlankCount = countAssociativeArrayValues(array_filter($this->arrLatestJobs, "isMarkedBlank"));
        $GLOBALS['logger']->logLine("Processed " . $nStartingBlankCount . "/" . countAssociativeArrayValues($this->arrLatestJobs) . " jobs marking negative keyword matches:  updated ". ($nStartingBlankCount - $nEndingBlankCount) . "/" . $nStartingBlankCount  . ", still active ". $nEndingBlankCount . "/" . $nStartingBlankCount, \Scooper\C__DISPLAY_ITEM_RESULT__);


    }



} 