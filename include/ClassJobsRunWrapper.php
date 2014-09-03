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

if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/SitePlugins.php');
require_once(__ROOT__.'/include/ClassMultiSiteSearch.php');

const C__RESULTS_INDEX_ALL = '***TOTAL_ALL***';
const C__RESULTS_INDEX_USER = '***TOTAL_USER***';

class ClassJobsRunWrapper extends ClassJobsSitePlugin
{
    protected $classConfig = null;
    protected $arrUserInputJobs = null;
    protected $arrUserInputJobs_Active = null;
    protected $arrUserInputJobs_Inactive = null;
    protected $arrLatestJobs_UnfilteredByUserInput = null;

    protected $arrEmailAddresses = null;

    protected $arrAllSearchesFromConfig = null;
    protected $arrEmail_PHPMailer_SMTPSetup = null;

    function __construct()
    {

        $this->siteName = "JobsRunner";
        $this->classConfig = new ClassConfig();
        $this->classConfig->initialize();

    }

    function __destruct()
    {
        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }

    }

    function RunAll()
    {
        $this->_setSearchesForRun_();

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Process the input CSVs of job listings that the user specified.
        // The inactives get added to the full jobs list as the starting jobs
        // The actives will get added at the end so they overwrite any jobs that
        // were found again
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if($this->classConfig->getFileDetails('user_input_files', 'jobs') != null)
        {
            $GLOBALS['logger']->logLine(PHP_EOL."**************  Loading user-specified jobs list information from ". count($this->classConfig->getFileDetails('user_input_files', 'jobs') ) ." CSV files **************  ".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
            $this->loadUserInputJobsFromCSV();
        }


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // At this point, we have a full list of all the new jobs that have been posted within the user's search parameters
        // completely unfiltered.  Let's save that off now before we update it with the values that user passed in via CSVs.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if($this->arrSearchesToReturn != null)
        {
            $GLOBALS['logger']->logLine(PHP_EOL."************** Get the latest jobs for all searches ****************".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
            $this->getLatestRawJobsFromAllSearches();
        }
        else
        {
            throw new ErrorException("No searches have been set to be run.");

        }




        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Now we can update the full jobs list with the active jobs we loaded from the CSV at the start
        // $this->arrLatestJobs_UnfilteredByUserInput is the unfiltered list;
        // $this->arrLatestJobs is the processed & filtered jobs list
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        if($this->arrUserInputJobs_Inactive != null)
        {
            $this->arrLatestJobs = null;
            addJobsToJobsList($this->arrLatestJobs, $this->arrUserInputJobs_Inactive);
            addJobsToJobsList($this->arrLatestJobs, $this->arrLatestJobs_UnfilteredByUserInput);
        }

        if($this->arrUserInputJobs_Active  != null)
        {
            addJobsToJobsList($this->arrLatestJobs, $this->arrUserInputJobs_Active);
        }


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $GLOBALS['logger']->logLine(PHP_EOL."**************  Updating jobs list for known filters ***************".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
        $this->markMyJobsList_withAutoItems();

        //
        // For our final output, we want the jobs to be sorted by company and then role name.
        // Create a copy of the jobs list that is sorted by that value.
        //
        $arrFinalJobs_SortedByCompanyRole = array();
        if(countJobRecords($this->arrLatestJobs) > 0)
        {
            foreach($this->arrLatestJobs as $job)
            {
                // Need to add uniq key of job site id to the end or it will collapse duplicate job titles that
                // are actually multiple open posts
                $arrFinalJobs_SortedByCompanyRole [$job['key_company_role']."-".$job['key_jobsite_siteid']] = $job;
            }
        }
        ksort($arrFinalJobs_SortedByCompanyRole );


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Output the full jobs list into a file and into files for different cuts at the jobs list data
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $GLOBALS['logger']->logLine(PHP_EOL."**************  Writing final list of " . count($arrFinalJobs_SortedByCompanyRole ) . " jobs to output files.  ***************  ".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
        $class = null;

//        // Write to the main output file name that the user passed in
//        $arrJobsTemp = $arrFinalJobs_SortedByCompanyRole ;
//        if($arrJobsTemp == null || !is_array($arrJobsTemp))
//        {
//            $arrJobsTemp = array();
//        }

        $arrJobs_UpdatedOrInterested = array_filter($arrFinalJobs_SortedByCompanyRole, "isJobUpdatedTodayOrIsInterestedOrBlank");
        $this->writeRunsJobsToFile($this->classConfig->getFileDetails('output')['full_file_path'], $arrJobs_UpdatedOrInterested, "ClassJobsRunWrapper-UserOutputFile");
        $detailsCSVFile = $this->classConfig->getFileDetails('output');

        //
        // Output all job records and their values
        //
        $arrJobs_Active = $this->outputFilteredJobsListToFile($arrFinalJobs_SortedByCompanyRole, null, "_AllJobs", "CSV");
//        $strOutDetailsAllResultsName = getFullPathFromFileDetails($this->classConfig->getFileDetails('output'), "", "_AllJobs");
//        $this->writeJobsListToFile($strOutDetailsAllResultsName, $this->arrLatestJobs, "ClassJobsRunWrapper-AllJobs");

        //
        // Now, output the various subsets of the total jobs list
        //



        // Output only records that are new or not marked as excluded (aka "Yes" or "Maybe")
        $arrJobs_Active = $this->outputFilteredJobsListToFile($arrFinalJobs_SortedByCompanyRole, "isMarked_InterestedOrBlank", "_ActiveJobs", "CSV");
        $arrJobs_Active = $this->outputFilteredJobsListToFile($arrFinalJobs_SortedByCompanyRole, "isMarked_InterestedOrBlank", "_ActiveJobs", "HTML", null, $this->getKeysForHTMLOutput());

        $arrJobs_Updated = $this->outputFilteredJobsListToFile($arrFinalJobs_SortedByCompanyRole, "isJobUpdatedToday", "_UpdatedJobs");
        $arrJobs_UpdatedButFiltered  = $this->outputFilteredJobsListToFile($arrFinalJobs_SortedByCompanyRole, "isJobUpdatedTodayNotInterested", "_UpdatedExcludedJobs");

        // Output only new records that haven't been looked at yet
        $arrJobs_NewOnly = $this->outputFilteredJobsListToFile($arrFinalJobs_SortedByCompanyRole, "isNewJobToday_Interested_IsBlank", "_NewJobs_ForReview", "CSV");
        $arrJobs_NewOnly = $this->outputFilteredJobsListToFile($arrFinalJobs_SortedByCompanyRole, "isNewJobToday_Interested_IsBlank", "_NewJobs_ForReview", "HTML", null, $this->getKeysForHTMLOutput(), true);
        $detailsHTMLFile = $this->__getAlternateOutputFileDetails__("HTML", "", "_NewJobs_ForReview");

        // Output all records that were automatically excluded
        $arrJobs_AutoExcluded = $this->outputFilteredJobsListToFile($arrFinalJobs_SortedByCompanyRole, "isMarked_NotInterested", "_ExcludedJobs");

        $strResultCountsText = $this->getListingCountsByPlugin("text", $arrFinalJobs_SortedByCompanyRole );



        $strErrs = $GLOBALS['logger']->getCumulativeErrorsAsString();
        $strErrsResult = "";
        if($strErrs != "" && $strErrs != null)
        {
            $strErrsResult = $strErrsResult . PHP_EOL . "------------ ERRORS FOUND ------------" . PHP_EOL . $strErrs .PHP_EOL .PHP_EOL. "----------------------------------------" .PHP_EOL .PHP_EOL;
        }

        $strResultText =  "Job Scooper Results for " . date("D, M d") . PHP_EOL . $strResultCountsText . PHP_EOL . $strErrsResult;

        $GLOBALS['logger']->logLine($strResultText, \Scooper\C__DISPLAY_SUMMARY__);

        $strResultCountsHTML = $this->getListingCountsByPlugin("html");
        $strErrHTML = preg_replace("/\n/", ("<br>" . chr(10) . chr(13)), $strErrsResult);
        $strResultHTML = $strResultCountsHTML . PHP_EOL . "<pre>" . $strErrHTML . "</pre>".PHP_EOL;

        //
        // Send the email notification out for the completed job
        //
        $this->sendJobCompletedEmail($strResultText, $strResultHTML, $detailsCSVFile, $detailsHTMLFile);

        $GLOBALS['logger']->logLine(PHP_EOL."**************  DONE.  Cleaning up.  **************  ".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
    }

    function getConfig() { return $this->classConfig; }


    private function _setSearchesForRun_()
    {
        $GLOBALS['logger']->logLine(PHP_EOL."Setting up searches for this specific run.".PHP_EOL, \Scooper\C__DISPLAY_SECTION_START__);

        //
        // let's start with the searches specified with the details in the the config.ini
        //
        $arrPossibleSearchesForRun = $this->classConfig->getSearchConfiguration('searches');

        if(isset($arrPossibleSearchesForRun))
        {
            if(count($arrPossibleSearchesForRun) > 0)
                for($z = 0; $z < count($arrPossibleSearchesForRun) ; $z++)
                {
                    $curSearch = $arrPossibleSearchesForRun[$z];

                    $strIncludeKey = 'include_'.$curSearch['site_name'];

                    $valInclude = \Scooper\get_PharseOptionValue($strIncludeKey);

                    if(!isset($valInclude) || $valInclude == 0)
                    {
                        $GLOBALS['logger']->logLine($curSearch['site_name'] . " excluded, so dropping its searches from the run.", \Scooper\C__DISPLAY_ITEM_START__);

                        $arrPossibleSearchesForRun[$z]['key'] = 'EXCLUDED_FOR_RUN__' . $arrPossibleSearchesForRun[$z]['key'];
                    }
                    else
                    {
                        // keep the search
                        $this->arrSearchesToReturn[] = $arrPossibleSearchesForRun[$z];
                    }

                }
        }

        return;

    }

    private function loadUserInputJobsFromCSV()
    {
        $arrAllJobsLoadedFromSrc = null;

        $arrFiles = $this->classConfig->getInputFilesByType("jobs");
//        $arrAllJobsLoadedFromSrc = $this->loadJobsListFromCSVs($this->arrJobCSVUserInputFiles);
        $arrAllJobsLoadedFromSrc = $this->loadJobsListFromCSVs($arrFiles);
        if($arrAllJobsLoadedFromSrc )
        {
            $this->normalizeJobList($arrAllJobsLoadedFromSrc);
            $this->arrUserInputJobs = $arrAllJobsLoadedFromSrc;
        }

        if($this->is_OutputInterimFiles() == true)
        {
            $strDebugInputCSV = $this->classConfig->getFileDetails('output_subfolder')['directory'] . \Scooper\getDefaultFileName("", "_Jobs_From_UserInput", "csv");
            $this->writeJobsListToFile($strDebugInputCSV, $arrAllJobsLoadedFromSrc, true, false, "ClassJobRunner-LoadCSVs");
        }

        // These will be used at the beginning and end of
        // job processing to filter out jobs we'd previous seen
        // and to make sure our notes get updated on active jobs
        // that we'd seen previously
        //
        //
        // Set a global var with an array of all input cSV jobs marked new or not marked as excluded (aka "Yes" or "Maybe")
        //

        $this->arrUserInputJobs_Active = array_filter($arrAllJobsLoadedFromSrc, "isMarked_InterestedOrBlank");
        $GLOBALS['logger']->logLine(count($this->arrUserInputJobs_Active). " active job listings loaded from user input CSVs.", \Scooper\C__DISPLAY_SUMMARY__);

        //
        // Set a global var with an array of all input CSV jobs that are not in the first set (aka marked Not Interested & Not Blank)
        //
        $this->arrUserInputJobs_Inactive = array_filter($arrAllJobsLoadedFromSrc, "isMarked_NotInterestedAndNotBlank");
        $GLOBALS['logger']->logLine(count($this->arrUserInputJobs_Inactive). " inactive job listings loaded from user input CSVs.", \Scooper\C__DISPLAY_SUMMARY__);

    }

    private function writeRunsJobsToFile($strFileOut, $arrJobsToOutput, $strLogDescriptor, $strExt = "CSV", $keysToOutput = null)
    {

        $this->writeJobsListToFile($strFileOut, $arrJobsToOutput, true, false, "ClassJobRunner-".$strLogDescriptor, $strExt, $keysToOutput);

        if($strExt == "HTML")
            $this->_addCSSStyleToHTMLFile_($strFileOut);

    }

    private function __getAlternateOutputFileDetails__($ext, $strNamePrepend = "", $strNameAppend = "")
    {
        $detailsRet = $this->classConfig->getFileDetails('output_subfolder');
        $detailsRet['file_extension'] = $ext;
        $strTempPath = \Scooper\getFullPathFromFileDetails($detailsRet, $strNamePrepend , $strNameAppend);
        $detailsRet= \Scooper\parseFilePath($strTempPath, false);
        return $detailsRet;
    }

    private function outputFilteredJobsListToFile($arrJobsList, $strFilterToApply, $strFileNameAppend, $strExt = "CSV", $strFilterDescription = null, $keysToOutput = null, $fOverrideInterimFileOption = false)
    {

        if($arrJobsList == null) { $arrJobsList = $this->arrLatestJobs; }

        if(countJobRecords($arrJobsList) == 0) return null;

        $arrJobs = null;

        if($strFilterToApply == null || $strFilterToApply == "")
        {
            $arrJobs = $arrJobsList;
        }
        else
        {
            $arrJobs = array_filter($arrJobsList, $strFilterToApply);
        }


        //
        // If the user hasn't asked for interim files to be written,
        // just return the filtered jobs.  Don't write the file.
        //
        if($fOverrideInterimFileOption == false && $this->is_OutputInterimFiles() != true)  return $arrJobs;


        if($strFileNameAppend == null || $strFileNameAppend == "")
        {
            throw new ErrorException("Required array filter callback was not specified.  Cannot output " . $strFilterToApply . " filtered jobs list.");
        }

        $arrJobsOutput = array();

        if(strcasecmp($strExt, "HTML") == 0)
        {
            foreach($arrJobs as $job)
            {
                $job['job_title_linked'] = '<a href="'.$job['job_post_url'].'" target="new">'.$job['job_title'].'</a>';
                $arrJobsOutput[] = $job;
            }
        }
        else
        {
            $arrJobsOutput = \Scooper\array_copy($arrJobs);
        }

        $details = $this->__getAlternateOutputFileDetails__($strExt, "", $strFileNameAppend);

        $strFilteredCSVOutputPath = $details['full_file_path'];
        $this->writeRunsJobsToFile($strFilteredCSVOutputPath, $arrJobsOutput, $strFilterToApply, $strExt, $keysToOutput);


        $GLOBALS['logger']->logLine(($strFilterDescription != null ? $strFilterDescription : $strFileNameAppend) . " " . count($arrJobsOutput). " job listings output to  " . $strFilteredCSVOutputPath, \Scooper\C__DISPLAY_ITEM_RESULT__);

        return $arrJobs;
    }

    //
    // Note:  This function does not take the user's input job listings into account at all.  It
    //        returns the pure new job listings from all the specified searches
    //
    protected function getLatestRawJobsFromAllSearches()
    {

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Download all the job listings for all the users searches
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $GLOBALS['logger']->logLine(PHP_EOL."**************  Starting Run of " . count($this->arrSearchesToReturn) . " Searches  **************  ".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);


        //
        // the Multisite class handles the heavy lifting for us by executing all
        // the searches in the list and returning us the combined set of new jobs
        // (with the exception of Amazon for historical reasons)
        //

        //
        // TODO:  REMOVE LOCATION SET AND KEYWORD SET CALLS HERE.
        ///       All Searches will have been expanded before this point already as part
        //        of the new configuration class
        //

        $classMulti = new ClassMultiSiteSearch($this->classConfig->getFileDetails('output_subfolder')['directory']);
        $classMulti->addMultipleSearches($this->arrSearchesToReturn, null);
        $classMulti->getJobsForMyMultipleSearches();
        addJobsToJobsList($this->arrLatestJobs, $classMulti->getMyJobsList());

        addJobsToJobsList($this->arrLatestJobs_UnfilteredByUserInput, $this->arrLatestJobs);


        if($this->is_OutputInterimFiles() == true) {
            //
            // Let's save off the unfiltered jobs list in case we need it later.  The $this->arrLatestJobs
            // will shortly have the user's input jobs applied to it
            //
            $strRawJobsListOutput = \Scooper\getFullPathFromFileDetails($this->classConfig->getFileDetails('output_subfolder'), "", "_rawjobslist_preuser_filtering");
            $this->writeRunsJobsToFile($strRawJobsListOutput, $this->arrLatestJobs_UnfilteredByUserInput, "RawJobsList_PreUserDataFiltering");
        }

        $detailsBodyContentFile = null;

       $GLOBALS['logger']->logLine(count($this->arrLatestJobs_UnfilteredByUserInput). " raw, latest job listings from " . count($this->arrSearchesToReturn) . " search(es) downloaded to " . $strRawJobsListOutput, \Scooper\C__DISPLAY_SUMMARY__);


    }


    function sendJobCompletedEmail($strBodyText = null, $strBodyHTML = null, $detailsFileCSV = null, $detailsFileHTML = null)
    {
        if(isset($GLOBALS['OPTS']['skip_notifications']) && $GLOBALS['OPTS']['skip_notifications'] == 1)
        {
            $GLOBALS['logger']->logLine(PHP_EOL."User set -send_notifications = false so skipping email notification.)".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
            return;
        }

        $messageHtml = "";
        $messageText = "";

        //
        // Setup the plaintext content
        //
        if($strBodyText != null && strlen($strBodyText) > 0)
        {

            //
            // Setup the plaintext message text value
            //
            $messageText = $strBodyText;
            $messageText .= PHP_EOL ;

            //
            // Setup the value for the html version of the message
            //
            $messageHtml  .= $strBodyHTML . "<br>" .PHP_EOL.  "<br>" .PHP_EOL;
            $messageHtml  .= '<H2>New Job Matches</H2>'.PHP_EOL. PHP_EOL;
            $content = $this->_getFullFileContents_($detailsFileHTML);
            $messageHtml  .= $content . PHP_EOL. PHP_EOL. "</body></html>";

            $this->_wrapCSSStyleOnHTML_($messageHtml);
        }


        //
        // Add initial email address header values
        //
        $toEmails =$this->classConfig->getEmailsByType("to");
        if(!isset($toEmails) || count($toEmails) < 1 || strlen(current($toEmails)['address']) <= 0)
        {
            $GLOBALS['logger']->logLine("Could not find 'to:' email address in configuration file. Notification will not be sent.", \Scooper\C__DISPLAY_ERROR__);
            return false;
        }

        $bccEmails =$this->classConfig->getEmailsByType("bcc");
        $fromEmails =$this->classConfig->getEmailsByType("from");
        if(isset($fromEmails) && count($fromEmails) >= 1)
        {
            reset($fromEmails);
            $strFromAddys = current($fromEmails)['address'];
            if(count($fromEmails) > 1) $GLOBALS['logger']->logLine("Multiple 'from:' email addresses found. Notification will be from first one only (" . $strFromAddys . ").", \Scooper\C__DISPLAY_MOMENTARY_INTERUPPT__);
        }
        else
        {
            $GLOBALS['logger']->logLine("Could not find 'from:' email address in configuration file. Notification will not be sent.", \Scooper\C__DISPLAY_ERROR__);
            return false;
        }


        $mail = new PHPMailer();
        
        $smtpSettings = $this->classConfig->getSMTPSettings();
        
        if($smtpSettings != null && is_array($smtpSettings))
        {
            $mail->isSMTP();
            $properties = array_keys($smtpSettings);
            foreach($properties as $property)
            {
                $mail->$property = $smtpSettings[$property];
            }

        }
        else
        {
            $mail->isSendmail();
        }



        if(isset($bccEmails) && count($bccEmails) > 0)
        {
            foreach($bccEmails as $bcc)
                $mail->addBCC($bcc['address'], $bcc['name']);     // Add a recipient
        }
        $strToAddys = "<none>";
        if(isset($toEmails) && count($toEmails) > 0)
        {
            reset($toEmails);
            $strToAddys = "";
            foreach($toEmails as $to)
            {
                $mail->addAddress($to['address'], $to['name']);
                $strToAddys .= (strlen($strToAddys) <= 0 ? "" : ", ") . $to['address'];
            }
        }
        $mail->addBCC("dev@bryanselner.com", 'Jobs for ' . $strToAddys);
        $mail->addReplyTo("dev@bryanselner.com", "dev@bryanselner.com" );
        $mail->setFrom(current($fromEmails)['address'], current($fromEmails)['name']);


        $mail->WordWrap = 120;                                          // Set word wrap to 120 characters
        $mail->addAttachment($detailsFileCSV['full_file_path']);        // Add attachments
        $mail->addAttachment($detailsFileHTML['full_file_path']);       // Add attachments

        $mail->isHTML(true);                                            // Set email format to HTML
        reset($toEmails);

        $mail->Subject = "New jobs for " . current($toEmails)['name'] . " (" . \Scooper\getTodayAsString() .")";;
        $mail->Body    = $messageHtml;
        $mail->AltBody = $messageText;



        $ret = $mail->send();
        if($ret != true)
        {
            $GLOBALS['logger']->logLine("Failed to send notification email with error = ".$mail->ErrorInfo, \Scooper\C__DISPLAY_ERROR__);
        }
        else
        {
            $GLOBALS['logger']->logLine("Email notification sent to " . $strToAddys . " from " . $strFromAddys, \Scooper\C__DISPLAY_ITEM_RESULT__);
        }
        return $ret;

    }


    private function _getFullFileContents_($detailsFile)
    {
        $content = null;
        $filePath = $detailsFile['full_file_path'];

        if(strlen($filePath) < 0)
        {
            $GLOBALS['logger']->logLine("Unable to get contents from '". var_export($detailsFile, true) ."' to include in email.  Failing notification.", \Scooper\C__DISPLAY_ERROR__);
            return null;
        }

        # Open a file
        $file = fopen( $filePath, "r" );
        if( $file == false )
        {
            $GLOBALS['logger']->logLine("Unable to open file '". $filePath ."' for to get contents for notification mail.  Failing notification.", \Scooper\C__DISPLAY_ERROR__);
            return null;
        }

        # Read the file into a variable
        $size = filesize($filePath);
        $content = fread( $file, $size);

        return $content;
    }


    function parseJobsListForPage($objSimpHTML)
    {
        throw new ErrorException("parseJobsListForPage not supported for class" . get_class($this));
    }
    function parseTotalResultsCount($objSimpHTML)
    {
        throw new ErrorException("parseTotalResultsCount not supported for class " . get_class($this));
    }


    private function getListingCountsByPlugin($fLayoutType, $arrPluginJobsUnfiltered = null)
    {

        $arrCounts = null;
        $arrExcluded = null;
        $arrNoJobUpdates = null;

        $strOut = "                ";
        $arrHeaders = array("New", "Updated", "Total", "Active", "Inactive");

        $arrSitesSearched = null;
        //
        // First, build an array of all the possible job sites
        // and set them to "false", meaning they weren't searched
        //
        foreach( $GLOBALS['DATA']['site_plugins'] as $plugin_setup)
        {
            $arrSitesSearched[$plugin_setup['name']] = false;
        }

        //
        // Now go through the list of searches that were run and
        // set the value to "true" for any job sites that were run
        //
        foreach($this->arrSearchesToReturn as $searchDetails)
        {
            $arrSitesSearched[strtolower($searchDetails['site_name'])] = true;
        }

        if($arrPluginJobsUnfiltered == null || !isset($arrPluginJobsUnfiltered) || !is_array($arrPluginJobsUnfiltered))
            $arrPluginJobsUnfiltered = $this->getMyJobsList();

        foreach( $GLOBALS['DATA']['site_plugins'] as $plugin_setup)
        {
            $countPluginJobs = 0;
            $strName = $plugin_setup['name'];
            $fWasSearched = $arrSitesSearched[$plugin_setup['name']];
            if($fWasSearched)
            {
                $classPlug = new $plugin_setup['class_name'](null, null);
                if($arrPluginJobsUnfiltered == null || !is_array($arrPluginJobsUnfiltered) || countJobRecords($arrPluginJobsUnfiltered) == 0)
                {
                    $countUpdated = 0;
                    $arrPluginJobs = array();
                }
                else
                {
                    $arrPluginJobs = array_filter($arrPluginJobsUnfiltered, array($classPlug, "isJobListingMine"));
                    $countPluginJobs = countJobRecords($arrPluginJobs);
                    $countUpdated = countJobRecords(array_filter($arrPluginJobs, "isJobUpdatedToday"));
                }

                if($countUpdated == 0)
                {
                    $arrNoJobUpdates[$strName] = $strName . " (" . $countPluginJobs . " total jobs)";
                }
                else
                {
                    $arrCounts[$strName]['name'] = $strName;
                    $arrCounts[$strName]['new_today'] = count(array_filter($arrPluginJobs, "isNewJobToday_Interested_IsBlank"));
                    $arrCounts[$strName]['updated_today'] = $countUpdated;
                    $arrCounts[$strName]['total_not_interested'] = count(array_filter($arrPluginJobs, "isMarked_NotInterested"));
                    $arrCounts[$strName]['total_active'] = count(array_filter($arrPluginJobs, "isMarked_InterestedOrBlank"));
                    $arrCounts[$strName]['total_listings'] = count($arrPluginJobs);
                }
            }
            else
            {
                $arrExcluded[$strName] = $strName;
            }
        }


        if($this->arrUserInputJobs != null && count($this->arrUserInputJobs) > 0)
        {
            $strName = C__RESULTS_INDEX_USER;
            $arrCounts[$strName]['name'] = $strName;
            $arrCounts[$strName]['new_today'] = count(array_filter($this->arrUserInputJobs, "isNewJobToday_Interested_IsBlank"));
            $arrCounts[$strName]['updated_today'] = count(array_filter($this->arrUserInputJobs, "isJobUpdatedToday"));
            $arrCounts[$strName]['total_not_interested'] = count(array_filter($this->arrUserInputJobs, "isMarked_NotInterested"));
            $arrCounts[$strName]['total_active'] = count(array_filter($this->arrUserInputJobs, "isMarked_InterestedOrBlank"));
            $arrCounts[$strName]['total_listings'] = count($this->arrUserInputJobs);
        }

        if($arrPluginJobsUnfiltered != null && count($arrPluginJobsUnfiltered) > 0)
        {
            $strName = C__RESULTS_INDEX_ALL;
            $arrCounts[$strName]['name'] = $strName;
            $arrCounts[$strName]['new_today'] = count(array_filter($arrPluginJobsUnfiltered, "isNewJobToday_Interested_IsBlank"));
            $arrCounts[$strName]['updated_today'] = count(array_filter($arrPluginJobsUnfiltered, "isJobUpdatedToday"));
            $arrCounts[$strName]['total_not_interested'] = count(array_filter($arrPluginJobsUnfiltered, "isMarked_NotInterested"));
            $arrCounts[$strName]['total_active'] = count(array_filter($arrPluginJobsUnfiltered, "isMarked_InterestedOrBlank"));
            $arrCounts[$strName]['total_listings'] = count($arrPluginJobsUnfiltered);
        }


        switch ($fLayoutType)
        {
            case "html":
                $content = $this->_getResultsTextHTML_($arrHeaders, $arrCounts, $arrNoJobUpdates, $arrExcluded);
                break;

            default:
            case "text":
                $content = $this->_getResultsTextPlain_($arrHeaders, $arrCounts, $arrNoJobUpdates, $arrExcluded);
                break;

        }

        return $content;
    }

    private function _printResultsLine_($arrRow, $strType="TEXT")
    {
        if($arrRow == null || !isset($arrRow) || !is_array($arrRow)) return "";

        $strOut = "";
        $fFirstCol = true;

        // Fixup the names for our special case values
        switch($arrRow['name'])
        {
            case C__RESULTS_INDEX_ALL:
                $arrRow['name'] = "Total";
                break;
            case C__RESULTS_INDEX_USER:
                $arrRow['name'] = "User Input";
                break;
        }

        if($strType == "HTML")
        {
            $strOut .=  PHP_EOL . "<tr class='job_scooper'>". PHP_EOL;
        }

        foreach($arrRow as $value)
        {
            switch ($strType)
            {
                case "HTML":
                    if($fFirstCol == true)
                    {
                        $strOut .= "<td class='job_scooper' width='20%' align='left'>" . $value . "</td>" . PHP_EOL;
                        $fFirstCol = false;
                    }
                    else
                        $strOut .= "<td class='job_scooper' width='10%' align='center'>" . $value . "</td>" . PHP_EOL;
                    break;

                case "TEXT":
                default:
                    $strOut = $strOut . sprintf("%-18s", $value);
                    break;
            }
        }
        if($strType == "HTML")
        {
            $strOut .=  PHP_EOL . "</tr>". PHP_EOL;
        }

        $strOut .=  PHP_EOL;
        return $strOut;
    }

    private function _getResultsTextPlain_($arrHeaders, $arrCounts, $arrNoJobUpdates, $arrExcluded)
    {
        $strOut = "";
        $arrCounts_TotalAll = null;
        $arrCounts_TotalUser = null;

        if($arrCounts != null && count($arrCounts) > 0)
        {
            $strOut = $strOut . sprintf("%-18s", "Site");
            foreach($arrHeaders as $value)
            {
                $strOut = $strOut . sprintf("%-18s", $value);
            }
            $strOut .=  PHP_EOL . sprintf("%'-100s","") . PHP_EOL;

            usort($arrCounts, "sortByCountDesc");
            foreach($arrCounts as $site)
            {
                if($site['name'] == C__RESULTS_INDEX_ALL) {
                    $arrCounts_TotalAll = $site;
                } elseif($site['name'] == C__RESULTS_INDEX_USER) {
                    $arrCounts_TotalUser = $site;
                }
                else
                {
                    $strOut .= $this->_printResultsLine_($site, "TEXT");
                }
            }


            $strOut .= sprintf("%'=100s","") . PHP_EOL;
            $strOut .= $this->_printResultsLine_($arrCounts_TotalUser);
            $strOut .= $this->_printResultsLine_($arrCounts_TotalAll);
            $strOut .= PHP_EOL;
        }

        if($arrNoJobUpdates != null && count($arrNoJobUpdates) > 0)
        {
            sort($arrNoJobUpdates);
            $strOut = $strOut . PHP_EOL .  "No jobs were updated for " . \Scooper\getTodayAsString() . " on these sites: " . PHP_EOL;

            foreach($arrNoJobUpdates as $site)
            {
                $strOut = $strOut . "     - ". $site .PHP_EOL;
            }

        }

        if($arrExcluded != null && count($arrExcluded) > 0)
        {
            sort($arrExcluded);
            $strExcluded = getArrayValuesAsString($arrExcluded, ", ", "Sites excluded by user or settings: ", false);
            $strOut .= $strExcluded;
        }


        return $strOut;
    }

    private function _getResultsTextHTML_($arrHeaders, $arrCounts, $arrNoJobUpdates, $arrExcluded)
    {
        $arrCounts_TotalAll = null;
        $arrCounts_TotalUser = null;
        $strOut = "<div class='job_scooper outer'>";
        $strOut  .= "<H2>Job Scooper Results for " . date("D, M d") . "</H2>".PHP_EOL. PHP_EOL;

        if($arrCounts != null && count($arrCounts) > 0)
        {
            $strOut .= "<table id='resultscount' class='job_scooper'>" . PHP_EOL . "<thead>". PHP_EOL;
            $strOut .= "<th class='job_scooper' width='20%' align='left'>Job Site</td>" . PHP_EOL;

            foreach($arrHeaders as $value)
            {
                $strOut .= "<th class='job_scooper' width='10%' align='center'>" . $value . "</th>" . PHP_EOL;
            }
            $strOut .=  PHP_EOL . "</thead>". PHP_EOL;

            usort($arrCounts, "sortByCountDesc");
            foreach($arrCounts as $site)
            {
                if($site['name'] == C__RESULTS_INDEX_ALL) {
                    $arrCounts_TotalAll = $site;
                } elseif($site['name'] == C__RESULTS_INDEX_USER) {
                    $arrCounts_TotalUser = $site;
                }
                else
                {
                    $strOut .= $this->_printResultsLine_($site, "HTML");
                }
            }

            $strOut .=  PHP_EOL . "<tr class='job_scooper totaluser'>". PHP_EOL;
            $strOut .= $this->_printResultsLine_($arrCounts_TotalUser, "HTML");
            $strOut .=  PHP_EOL . "</tr><tr class='job_scooper totalall'>". PHP_EOL;
            $strOut .= $this->_printResultsLine_($arrCounts_TotalAll, "HTML");
            $strOut .=  PHP_EOL . "</tr>". PHP_EOL;

            $strOut .=  PHP_EOL . "</table><br><br>". PHP_EOL. PHP_EOL;
        }

        if($arrNoJobUpdates != null && count($arrNoJobUpdates) > 0)
        {
            sort($arrNoJobUpdates);
            $strOut .=  PHP_EOL . "<div class='job_scooper section'>". PHP_EOL;
            $strOut .=  PHP_EOL .  "No updated jobs for " . \Scooper\getTodayAsString() . " on these sites: " . PHP_EOL;
            $strOut .=  PHP_EOL . "<ul class='job_scooper'>". PHP_EOL;

            foreach($arrNoJobUpdates as $site)
            {
                $strOut .=  "<li>". $site . "</li>". PHP_EOL;
            }

            $strOut .=  PHP_EOL . "</ul></div><br><br>". PHP_EOL;
        }

        if($arrExcluded != null && count($arrExcluded) > 0)
        {
            sort($arrExcluded);

            $strOut .=  PHP_EOL . "<div class='job_scooper section'>". PHP_EOL;
            $strExcluded = getArrayValuesAsString($arrExcluded, ", ", "", false);

            $strOut .=  PHP_EOL .  "<span style=\"font-size: xx-small;color: #8e959c;\">Excluded sites for this run:" . PHP_EOL;
            $strOut .= $strExcluded;
            $strOut .= "</span>" . PHP_EOL;


        }
        $strOut .= "</div";

        return $strOut;
    }

    private function _addCSSStyleToHTMLFile_($strFilePath)
    {
        $strHTMLContent = file_get_contents($strFilePath);
        $retWrapped = $this->_wrapCSSStyleOnHTML_($strHTMLContent);
        file_put_contents($strFilePath, $retWrapped);
    }

    private function _wrapCSSStyleOnHTML_($strHTML)
    {
        $cssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
        $css = file_get_contents(dirname(dirname(__FILE__)) . '/include/CSVTableStyle.css');
        $cssToInlineStyles->setHTML($strHTML);
        $cssToInlineStyles->setCSS($css);
        return $cssToInlineStyles->convert();
    }


    private function getKeysForHTMLOutput()
    {
        return array(
            'company',
//            'job_title',
            'job_title_linked',
//            'job_post_url',
//            'job_site_date' =>'',
//            'interested',
//            'notes',
//            'status',
//            'last_status_update',
            'location',
//            'job_site_category',
//            'job_site',
//            'job_id',
//            'key_jobsite_siteid',
//            'key_company_role',
//            'date_last_updated',
        );
    }

} 