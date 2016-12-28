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
require_once(__ROOT__.'/include/S3Manager.php');
require_once(__ROOT__.'/include/JobListFilters.php');

const C__RESULTS_INDEX_ALL = '***TOTAL_ALL***';
const C__RESULTS_INDEX_USER = '***TOTAL_USER***';

class ClassJobsNotifier extends ClassJobsSiteCommon
{
    protected $siteName = "ClassJobsNotifier";
    protected $arrUserInputJobs = null;
    protected $arrUserInputJobs_Active = null;
    protected $arrUserInputJobs_Inactive = null;
    protected $arrLatestJobs_UnfilteredByUserInput = array();
    protected $arrLatestJobs = array();

    function __construct($arrJobs_Unfiltered, $arrJobs_AutoMarked)
    {
        if(!is_null($arrJobs_Unfiltered))
            $this->arrLatestJobs_UnfilteredByUserInput = \Scooper\array_copy($arrJobs_Unfiltered);
        if(!is_null($arrJobs_AutoMarked))
            $this->arrLatestJobs = \Scooper\array_copy($arrJobs_AutoMarked);
    }

    function __destruct()
    {
        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }
    }

    private function _combineCSVsToExcel($outfileDetails, $arrCSVFiles)
    {
        $spreadsheet = new PHPExcel();
        $objWriter = PHPExcel_IOFactory::createWriter($spreadsheet, "Excel2007");
        $GLOBALS['logger']->logLine("Creating output XLS file '" . $outfileDetails['full_file_path'] . "'." . PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
        $style_all = array(
            'alignment' => array(
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP,
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                'wrap' => true
            ),
            'font' => array(
                'size' => 10.0,
            )
        );

        $style_header = array_replace_recursive($style_all, array(
            'alignment' => array(
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_BOTTOM,
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb'=>'E1E0F7'),
            ),
            'font' => array(
                'bold' => true,
            )
        ));
        $spreadsheet->getDefaultStyle()->applyFromArray($style_all);

        foreach($arrCSVFiles as $csvFile)
        {
            if(strcasecmp($csvFile['file_extension'], "csv") == 0)
            {
                $objPHPExcelFromCSV = PHPExcel_IOFactory::createReaderForFile($csvFile['full_file_path']);
                $srcFile = $objPHPExcelFromCSV->load($csvFile['full_file_path']);
                $colCount = count($this->getEmptyJobListingRecord());
                $lastCol = ord("A") + $colCount - 1;
                $lastColLetter = chr($lastCol);
                $headerRange = "A" . 1 . ":" . $lastColLetter . "1";

                $sheet = $srcFile->getSheet(0);
                $sheet->getDefaultColumnDimension()->setWidth(50);
                foreach($sheet->getColumnIterator("a", $lastColLetter) as $col)
                {
                    $sheet->getColumnDimension($col->getColumnIndex())->setWidth(40);
                }

                $nameParts = explode("-", $csvFile['file_name_base']);
                $name = "unknown";
                foreach($nameParts as $part) {
                    $int = intval($part);
//                    print $name . " | " . $part . " | " . $int . " | " . (is_integer($int) && $int != 0) . PHP_EOL;
                    if(!(is_integer($int) && $int != 0))
                    {
                        if($name == "unknown")
                            $name = $part;
                        else
                            $name = $name . "-" . $part;
                    }
                }
                $name = substr($name, max([strlen($name)-31, 0]), 31);

//                $name = $nameParts[count($nameParts)-1];
                $n = 1;
                while($spreadsheet->getSheetByName($name) != null)
                {
                    $n++;
                    $name = $name . $n;
                }
                $sheet->setTitle($name);
                $sheet->getStyle($headerRange)->applyFromArray( $style_header );

                $newSheet = $spreadsheet->addExternalSheet($sheet);
                if($spreadsheet->getSheetCount() > 3)
                {
                    $newSheet->setSheetState(PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
                }


                $GLOBALS['logger']->logLine("Added data from CSV '" . $csvFile['full_file_path'] . "' to output XLS file." . PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
            }
        }

        $spreadsheet->removeSheetByIndex(0);
        $objWriter->save($outfileDetails['full_file_path']);


        return $outfileDetails;

    }



    function processNotifications()
    {




        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Output the full jobs list into a file and into files for different cuts at the jobs list data
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $GLOBALS['logger']->logSectionHeader("Ouputing Results Files", \Scooper\C__DISPLAY_SECTION_START__, \Scooper\C__NAPPFIRSTLEVEL__);
        $GLOBALS['logger']->logSectionHeader("Files Sent To User", \Scooper\C__DISPLAY_SECTION_START__, \Scooper\C__NAPPSECONDLEVEL__);
        $class = null;


        //
        // Output the final files we'll send to the user
        //

        // Output all records that match the user's interest and are still active

        //
        // For our final output, we want the jobs to be sorted by company and then role name.
        // Create a copy of the jobs list that is sorted by that value.
        //

        //
        // For our final output, we want the jobs to be sorted by company and then role name.
        // Create a copy of the jobs list that is sorted by that value.
        //
        $arrFinalJobs_SortedByCompanyRole = array();
        if (countJobRecords($this->arrLatestJobs) > 0) {
            foreach ($this->arrLatestJobs as $job) {
                // Need to add uniq key of job site id to the end or it will collapse duplicate job titles that
                // are actually multiple open posts
                $arrFinalJobs_SortedByCompanyRole [$job['key_company_role'] . "-" . $job['key_jobsite_siteid']] = $job;
            }
        }
        ksort($arrFinalJobs_SortedByCompanyRole);
        $GLOBALS['logger']->logLine(PHP_EOL . "Writing final list of " . count($arrFinalJobs_SortedByCompanyRole) . " jobs to output files." . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);

        $detailsMainResultsCSVFile = \Scooper\getFilePathDetailsFromString(join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['stage4'], getDefaultJobsOutputFileName("results", "", "csv"))), \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
        $this->_filterAndWriteListToFile_($arrFinalJobs_SortedByCompanyRole, "isMarked_InterestedOrBlank", $detailsMainResultsCSVFile);

        // Output only new records that haven't been looked at yet
        $allupdatedjobsfile = $this->_outputFilteredJobsListToFile_($arrFinalJobs_SortedByCompanyRole, "isJobUpdatedToday", "-allchangedjobs", "CSV");

        $detailsMainResultsXLSFile = \Scooper\array_copy($detailsMainResultsCSVFile);
        $detailsMainResultsXLSFile['file_extension'] = "xls";
        $detailsMainResultsXLSFile = \Scooper\parseFilePath(\Scooper\getFullPathFromFileDetails($detailsMainResultsXLSFile));

        // Output all records that were automatically excluded
        $dataExcludedJobs = $this->_filterAndWriteListToAlternateFile_($arrFinalJobs_SortedByCompanyRole, "isMarked_NotInterested", "ExcludedJobs", "CSV", "excluded jobs", true);
        $this->_filterAndWriteListToAlternateFile_($arrFinalJobs_SortedByCompanyRole, "isMarkedBlank", "NotMarkedJobs", "CSV", "NotMarkedJobs", true);

        // Output only new records that haven't been looked at yet
        $this->_outputFilteredJobsListToFile_($arrFinalJobs_SortedByCompanyRole, "isMarked_InterestedOrBlank", "-AllUnmarkedJobs", "CSV");
        $detailsHTMLFile = \Scooper\parseFilePath($this->_outputFilteredJobsListToFile_($arrFinalJobs_SortedByCompanyRole, "isMarked_InterestedOrBlank", "-AllUnmarkedJobs", "HTML"));

        $arrResultFilesToCombine = array($detailsMainResultsCSVFile, \Scooper\parseFilePath($dataExcludedJobs));
        $arrFilesToAttach = array($detailsMainResultsXLSFile, $detailsHTMLFile, $detailsMainResultsCSVFile);

        foreach($GLOBALS['USERDATA']['user_input_files_details'] as $inputfile)
        {
            array_push($arrResultFilesToCombine, $inputfile['details']);

        }

        $xlsOutputFile = $this->_combineCSVsToExcel($detailsMainResultsXLSFile, $arrResultFilesToCombine);
        array_push($arrFilesToAttach, $xlsOutputFile);

        $GLOBALS['logger']->logSectionHeader("" . PHP_EOL, \Scooper\C__SECTION_END__, \Scooper\C__NAPPSECONDLEVEL__);


        //
        // Output debugging / interim files if asked to
        //

        if($this->is_OutputInterimFiles() == true) {
            $GLOBALS['logger']->logSectionHeader("DEBUG ONLY:  Writing out interim, developer files (user does not ever see these)..." . PHP_EOL, \Scooper\C__SECTION_BEGIN__, \Scooper\C__NAPPSECONDLEVEL__);

            //
            // Now, output the various subsets of the total jobs list
            //

            $this->_filterAndWriteListToAlternateFile_($arrFinalJobs_SortedByCompanyRole, "isJobUpdatedTodayOrIsInterestedOrBlank", "", "CSV", "updated today", false);

            // Output all job records and their values
            $this->_filterAndWriteListToAlternateFile_($arrFinalJobs_SortedByCompanyRole, null, "-AllJobs", "CSV", "all jobs", false);

            $GLOBALS['logger']->logSectionHeader("" . PHP_EOL, \Scooper\C__SECTION_END__, \Scooper\C__NAPPSECONDLEVEL__);
        }


        $GLOBALS['logger']->logSectionHeader("Generating email content for user" . PHP_EOL, \Scooper\C__SECTION_BEGIN__, \Scooper\C__NAPPSECONDLEVEL__);

        $strResultCountsText = $this->getListingCountsByPlugin("text", $arrFinalJobs_SortedByCompanyRole);
        $strErrs = $GLOBALS['logger']->getCumulativeErrorsAsString();
        $strErrsResult = "";
        if ($strErrs != "" && $strErrs != null) {
            $strErrsResult = $strErrsResult . PHP_EOL . "------------ ERRORS FOUND ------------" . PHP_EOL . $strErrs . PHP_EOL . PHP_EOL . "----------------------------------------" . PHP_EOL . PHP_EOL;
        }

        $strResultText = "Job Scooper Results for ". $this->_getRunDateRange_() . PHP_EOL . $strResultCountsText . PHP_EOL . $strErrsResult;

        $GLOBALS['logger']->logLine($strResultText, \Scooper\C__DISPLAY_SUMMARY__);

        $strResultCountsHTML = $this->getListingCountsByPlugin("html", $arrFinalJobs_SortedByCompanyRole);
        $strErrHTML = preg_replace("/\n/", ("<br>" . chr(10) . chr(13)), $strErrsResult);
        $strResultHTML = $strResultCountsHTML . PHP_EOL . "<pre>" . $strErrHTML . "</pre>" . PHP_EOL;

        $GLOBALS['logger']->logSectionHeader("" . PHP_EOL, \Scooper\C__SECTION_END__, \Scooper\C__NAPPSECONDLEVEL__);

        //
        // Send the email notification out for the completed job
        //
        $this->sendJobCompletedEmail($strResultText, $strResultHTML, $detailsHTMLFile, $arrFilesToAttach);

//        if(isset($GLOBALS['USERDATA']['AWS']['S3']) && !is_null($GLOBALS['USERDATA']['AWS']['S3']['bucket']) && !is_null($GLOBALS['USERDATA']['AWS']['S3']['region']))
//        {
//            $s3 = new S3Manager($GLOBALS['USERDATA']['AWS']['S3']['bucket'], $GLOBALS['USERDATA']['AWS']['S3']['region']);
//            $s3->publishOutputFiles($GLOBALS['USERDATA']['directories']['stage4']);
//        }

        //
        // If the user has not asked us to keep interim files around
        // after we're done processing, then delete the interim HTML file
        //
        if ($this->is_OutputInterimFiles() != true) {
            foreach ($arrFilesToAttach as $fileDetail) {
                if (file_exists($fileDetail['full_file_path']) && is_file($fileDetail ['full_file_path'])) {
                    $GLOBALS['logger']->logLine("Deleting local attachment file " . $fileDetail['full_file_path'] . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
                    unlink($fileDetail['full_file_path']);
                }
            }
        }

        $GLOBALS['logger']->logLine(PHP_EOL."**************  DONE.  Cleaning up.  **************  ".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
    }


    private function writeRunsJobsToFile($strFileOut, $arrJobsToOutput, $strLogDescriptor, $strExt = "CSV")
    {
        $keys = array_keys($this->getEmptyJobListingRecord());
        if($strExt == "HTML")
            $keys = $this->getKeysForHTMLOutput();

        $this->writeJobsListToFile($strFileOut, $arrJobsToOutput, true, "ClassJobRunner-".$strLogDescriptor, $strExt, $keys);

        if($strExt == "HTML")
            $this->_addCSSStyleToHTMLFile_($strFileOut);

        return $arrJobsToOutput;

    }

    private function __getAlternateOutputFileDetails__($strNamePrepend = "results", $strNameAppend = "", $ext = "")
    {
        $fileName = getDefaultJobsOutputFileName($strNamePrepend, $strNameAppend, $ext, "");
        $detailsRet = \Scooper\parseFilePath(join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['stage4'], $fileName)), false);
        return $detailsRet;
    }

    private function _outputFilteredJobsListToFile_($arrJobsList, $strFilterToApply, $strFileNameBase, $strExt = "CSV")
    {
        if($strFileNameBase == null && isset($strFilterToApply))
        {
            $strFileNameBase = $strFilterToApply;
        }

        $details = $this->__getAlternateOutputFileDetails__("results", $strFileNameBase, $strExt );

        return $this->_filterAndWriteListToFile_($arrJobsList, $strFilterToApply, $details);
    }

    private function _filterAndWriteListToFile_($arrJobsList, $strFilterToApply = null, $fileDetails)
    {
        assert(strlen($fileDetails['full_file_path'])>0);
        if(countJobRecords($arrJobsList) == 0) return $arrJobsList;

        if($strFilterToApply == null || function_exists($strFilterToApply) === false)
        {
            throw new Exception("Error:  array filter function " . $strFilterToApply . " does not exist");
        }

        $arrJobs = array_filter($arrJobsList, $strFilterToApply);

        if(strcasecmp($fileDetails['file_extension'], "HTML") == 0)
        {
            foreach(array_keys($arrJobs) as $jobKey)
            {
                $arrJobs[$jobKey]['job_title_linked'] = '<a href="'.$arrJobs[$jobKey]['job_post_url'].'" target="new">'.$arrJobs[$jobKey]['job_title'].'</a>';
            }
        }

        $this->writeRunsJobsToFile($fileDetails['full_file_path'], $arrJobs, $strFilterToApply, $fileDetails['file_extension']);

        return $fileDetails['full_file_path'];

    }

    private function _filterAndWriteListToAlternateFile_($arrJobsList, $strFilterToApply, $strFileNameAppend, $strFileNameExt, $strFilterDescription = null, $fOverrideInterimFileOption = false)
    {
        if($strFileNameAppend == null && isset($strFilterToApply))
        {
            $strFileNameAppend = $strFilterToApply;
        }

        $details = $this->__getAlternateOutputFileDetails__("", $strFileNameAppend, $strFileNameExt);

        $dataRet = $this->_filterAndWriteListToFile_($arrJobsList, $strFilterToApply, $details);

        //
        // If the user hasn't asked for interim files to be written,
        // just return the filtered jobs.  Don't write the file.
        //
        if($fOverrideInterimFileOption == false && $this->is_OutputInterimFiles() != true)
        {
            unlink($details['full_file_path']);
            return $dataRet;
        }

        $GLOBALS['logger']->logLine($strFilterDescription . " " . count($dataRet). " job listings output to  " . $details['full_file_path'], \Scooper\C__DISPLAY_ITEM_RESULT__);
        return $dataRet;
    }




    function sendJobCompletedEmail($strBodyText = null, $strBodyHTML = null, $detailsHTMLBodyInclude = null, $arrDetailsAttachFiles = array())
    {
        if(!isset($GLOBALS['OPTS']['send_notifications']) || $GLOBALS['OPTS']['send_notifications'] != 1)
        {
            $GLOBALS['logger']->logLine(PHP_EOL."User set -send_notifications = false so skipping email notification.)".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
            return null;
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
            $content = $this->_getFullFileContents_($detailsHTMLBodyInclude);
            $messageHtml  .= $content . PHP_EOL. PHP_EOL. "</body></html>";

            $this->_wrapCSSStyleOnHTML_($messageHtml);
        }


        //
        // Add initial email address header values
        //
        $settings = $GLOBALS['USERDATA']['configuration_settings']['email'];

        if(!isset($settings['email_addresses']["to"]) || count($settings['email_addresses']["to"]) < 1 || strlen(current($settings['email_addresses']["to"])['address']) <= 0)
        {
            $GLOBALS['logger']->logLine("Could not find 'to:' email address in configuration file. Notification will not be sent.", \Scooper\C__DISPLAY_ERROR__);
            return false;
        }
        if(array_key_exists("bcc", $settings['email_addresses']))
            $bccEmails =$settings['email_addresses']["bcc"];
        if(array_key_exists("from", $settings['email_addresses']))
            $fromEmails =$settings['email_addresses']["from"];

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

        $smtpSettings = $settings['PHPMailer_SMTPSetup'];

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

        $strToAddys = "<none>";
        if(isset($settings['email_addresses']["to"]) && count($settings['email_addresses']["to"]) > 0)
        {
            $strToAddys = "";
            foreach($settings['email_addresses']["to"] as $to)
            {
                $mail->addAddress($to['address'], $to['name']);
                $strToAddys .= (strlen($strToAddys) <= 0 ? "" : ", ") . $to['address'];
            }
        }

        $mail->addBCC("dev@bryanselner.com", 'Jobs for ' . $strToAddys);
        $strBCCAddys = "dev@bryanselner.com";
        if(isset($bccEmails) && count($bccEmails) > 0)
        {
            foreach($bccEmails as $bcc)
            {
                $mail->addBCC($bcc['address'], $bcc['name']);
                $strBCCAddys .= ", " . $bcc['address'];
            }
        }

        $mail->addReplyTo("dev@bryanselner.com", "dev@bryanselner.com" );
        $mail->setFrom(current($fromEmails)['address'], current($fromEmails)['name']);
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );


        $mail->WordWrap = 120;                                          // Set word wrap to 120 characters
        foreach($arrDetailsAttachFiles as $detailsAttach)
            if(isset($detailsAttach) && isset($detailsAttach['full_file_path']))
                $mail->addAttachment($detailsAttach['full_file_path']);        // Add attachments

        $mail->isHTML(true);                                            // Set email format to HTML

        $mail->Subject = "New Job Postings: " . $this->_getRunDateRange_();

        $mail->Body    = $messageHtml;
        $mail->AltBody = $messageText;

        $ret = $mail->send();
        if($ret != true)
        {
            $GLOBALS['logger']->logLine("Failed to send notification email with error = ".$mail->ErrorInfo, \Scooper\C__DISPLAY_ERROR__);
        }
        else
        {
            $GLOBALS['logger']->logLine("Email notification sent to '" . $strToAddys . "' from '" . $strFromAddys . "' with BCCs to '" . $strBCCAddys ."'", \Scooper\C__DISPLAY_ITEM_RESULT__);
        }
        return $ret;

    }

    function sendErrorEmail($strBodyText = null, $arrDetailsAttachFiles = array())
    {
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

        }




        //
        // Add initial email address header values
        //
        $settings = $GLOBALS['USERDATA']['configuration_settings']['email'];

        if(array_key_exists("from", $settings['email_addresses']))
            $fromEmails =$settings['email_addresses']["from"];

        if(isset($fromEmails) && count($fromEmails) >= 1)
        {
            reset($fromEmails);
            $strFromAddys = current($fromEmails)['address'];
            if(count($fromEmails) > 1) $GLOBALS['logger']->logLine("Multiple 'from:' email addresses found. Notification will be from first one only (" . $strFromAddys . ").", \Scooper\C__DISPLAY_MOMENTARY_INTERUPPT__);
        }
        else
        {
            $GLOBALS['logger']->logLine("Could not find 'from:' email address in configuration file. Notification will not be sent.", \Scooper\C__DISPLAY_ERROR__);
            throw new InvalidArgumentException("Error:  Unable to send error notifications.  The email from address value is missing in settings.");
        }


        $mail = new PHPMailer();

        $smtpSettings = $settings['PHPMailer_SMTPSetup'];

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

        $strToAddys = $strFromAddys;
        if(isset($settings['email_addresses']["to"]) && count($settings['email_addresses']["to"]) > 0)
        {
            $strToAddys = "";
            foreach($settings['email_addresses']["to"] as $to)
            {
                $mail->addAddress($to['address'], $to['name']);
                $strToAddys .= (strlen($strToAddys) <= 0 ? "" : ", ") . $to['address'];
            }
        }

        $mail->addBCC("dev@bryanselner.com", __APP_VERSION__ . "Error Notification");
        $strBCCAddys = "dev@bryanselner.com";
        if(isset($bccEmails) && count($bccEmails) > 0)
        {
            foreach($bccEmails as $bcc)
            {
                $mail->addBCC($bcc['address'], $bcc['name']);
                $strBCCAddys .= ", " . $bcc['address'];
            }
        }

        $mail->addReplyTo("dev@bryanselner.com", "dev@bryanselner.com" );
        $mail->setFrom(current($fromEmails)['address'], current($fromEmails)['name']);
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );


        $mail->WordWrap = 120;                                          // Set word wrap to 120 characters
        if(is_array($arrDetailsAttachFiles))     // Add attachments
        {
            foreach($arrDetailsAttachFiles as $detailsAttach)
                if(isset($detailsAttach) && isset($detailsAttach['full_file_path']))
                    $mail->addAttachment($detailsAttach['full_file_path']);
        }

        $mail->isHTML(true);                                            // Set email format to HTML

        $mail->Subject = "JobsScooper Error(s)Notification for Run Dated" . $this->_getRunDateRange_();

        $mail->Body    = "<html><body><pre>". $messageText . "</pre></body>";
        $mail->AltBody = $messageText;

        $ret = $mail->send();
        if($ret != true)
        {
            $msg = "Failed to send notification email with error = ".$mail->ErrorInfo;
            $GLOBALS['logger']->logLine($msg, \Scooper\C__DISPLAY_ERROR__);
            throw new Exception($msg);
        }
        else
        {
            $GLOBALS['logger']->logLine("Email notification sent to '" . $strToAddys . "' from '" . $strFromAddys . "' with BCCs to '" . $strBCCAddys ."'", \Scooper\C__DISPLAY_ITEM_RESULT__);
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
        $arrHeaders = array("New", "Updated", "Auto-Filtered", "For Review" , "Total");

        $arrSitesSearched = null;
        //
        // First, build an array of all the possible job sites
        // and set them to "false", meaning they weren't searched
        //
        foreach( $GLOBALS['JOBSITE_PLUGINS'] as $plugin_setup)
        {
            $arrSitesSearched[$plugin_setup['name']] = false;
        }

        //
        // Now go through the list of searches that were run and
        // set the value to "true" for any job sites that were run
        //
        foreach($GLOBALS['USERDATA']['configuration_settings']['searches'] as $searchDetails)
        {
            $arrSitesSearched[strtolower($searchDetails['site_name'])] = true;
        }

        if($arrPluginJobsUnfiltered == null || !isset($arrPluginJobsUnfiltered) || !is_array($arrPluginJobsUnfiltered))
            $arrPluginJobsUnfiltered = $this->arrLatestJobs_UnfilteredByUserInput;

        foreach( $GLOBALS['JOBSITE_PLUGINS'] as $plugin_setup)
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
                $content = $this->_getResultsTextHTML_($arrHeaders, $arrCounts, $arrNoJobUpdates);
                break;

            default:
            case "text":
                $content = $this->_getResultsTextPlain_($arrHeaders, $arrCounts, $arrNoJobUpdates);
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

    private function _getResultsTextPlain_($arrHeaders, $arrCounts, $arrNoJobUpdates)
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
            $strOut = $strOut . PHP_EOL .  "No jobs were updated for " . getTodayAsString() . " on these sites: " . PHP_EOL;

            foreach($arrNoJobUpdates as $site)
            {
                $strOut = $strOut . "     - ". $site .PHP_EOL;
            }

        }

        if($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'] != null && count($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']) > 0)
        {
            sort($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']);
            $strExcluded = getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'], ", ", "Sites excluded by user or settings: ", false);
            $strOut .= $strExcluded;
        }


        return $strOut;
    }

    private function _getRunDateRange_()
    {
        $startDate = new DateTime();
        $strMod = "-".$GLOBALS['USERDATA']['configuration_settings']['number_days']." days";
        $startDate = $startDate->modify($strMod);
        $today = new DateTime();
        $strDateRange = $startDate->format('D, M d') . " - " . $today->format('D, M d');
        return $strDateRange;
    }

    private function _getResultsTextHTML_($arrHeaders, $arrCounts, $arrNoJobUpdates)
    {
        $arrCounts_TotalAll = null;
        $arrCounts_TotalUser = null;
        $strOut = "<div class='job_scooper outer'>";

        $strOut  .= "<H2>New Job Postings for " . $this->_getRunDateRange_() . "</H2>".PHP_EOL. PHP_EOL;

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
            $strOut .=  PHP_EOL .  "No updated jobs for " . getTodayAsString() . " on these sites: " . PHP_EOL;
            $strOut .=  PHP_EOL . "<ul class='job_scooper'>". PHP_EOL;

            foreach($arrNoJobUpdates as $site)
            {
                $strOut .=  "<li>". $site . "</li>". PHP_EOL;
            }

            $strOut .=  PHP_EOL . "</ul></div><br><br>". PHP_EOL;
        }

        $strOut .=  PHP_EOL . "<div class=\"job_scooper section\">". PHP_EOL;

        if($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'] != null && count($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']) > 0)
        {
            sort($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']);

            $strExcluded = getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'], ", ", "", false);

            $strOut .=  PHP_EOL .  "<span style=\"font-size: xx-small; color: #8e959c;\">Excluded sites for this run:" . PHP_EOL;
            $strOut .= $strExcluded;
            $strOut .= "</span>" . PHP_EOL;
        }

        $strOut .=  PHP_EOL . "<div class=\"job_scooper section\">". PHP_EOL;
        $strOut .=  PHP_EOL .  "<p style=\"min-height: 15px;\">&nbsp;</p><span style=\"font-size: xx-small; color: SlateBlue; padding-top: 15px;\">Generated by " . __APP_VERSION__. " on " . \Scooper\getTodayAsString() . "." . PHP_EOL;
        $strOut .= "</span>" . PHP_EOL;

        $strOut .= "</div>";

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
//            'match_notes',
//            'status',
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