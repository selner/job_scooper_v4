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


require_once __ROOT__ . "/bootstrap.php";

//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use JobScooper\DataAccess\JobPosting as JobPosting;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Worksheet;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Fill;
use ErrorException;
use InvalidArgumentException;

class NotifierJobAlerts
{
    protected $siteName = "NotifierJobAlerts";
    protected $arrAllUnnotifiedJobs = array();
    private $_arrJobSitesForRun = null;

    function __construct()
    {
    }

    function __destruct()
    {
        if(isset($GLOBALS['logger'])) { logLine("Closing ".$this->siteName." instance of class " . get_class($this), \C__DISPLAY_ITEM_START__); }
    }

    private function _combineCSVsToExcel($outfileDetails, $arrCSVFiles)
    {
        $spreadsheet = new PHPExcel();
        $objWriter = PHPExcel_IOFactory::createWriter($spreadsheet, "Excel2007");
        logLine("Creating output XLS file '" . $outfileDetails['full_file_path'] . "'." . PHP_EOL, \C__DISPLAY_ITEM_RESULT__);
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
                $colCount = count($this->getKeysForUserCSVOutput());
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


                logLine("Added data from CSV '" . $csvFile['full_file_path'] . "' to output XLS file." . PHP_EOL, \C__DISPLAY_ITEM_RESULT__);
            }
        }

        $spreadsheet->removeSheetByIndex(0);
        $objWriter->save($outfileDetails['full_file_path']);


        return $outfileDetails;

    }


    protected function _isIncludedJobSite($var)
    {
        $sites = $this->_getJobSitesRunRecently();

        return in_array(cleanupSlugPart($var->getJobPosting()->getJobSite()), $sites);

    }

    function processNotifications()
    {
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Output the full jobs list into a file and into files for different cuts at the jobs list data
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $GLOBALS['logger']->logSectionHeader("Writing Results Files", \C__DISPLAY_SECTION_START__, \C__NAPPFIRSTLEVEL__);
        $GLOBALS['logger']->logSectionHeader("Files Sent To User", \C__DISPLAY_SECTION_START__, \C__NAPPSECONDLEVEL__);
        $class = null;


        //
        // Output the final files we'll send to the user
        //

        // Output all records that match the user's interest and are still active
        $detailsMainResultsXLSFile = getFilePathDetailsFromString(generateOutputFileName("results", "xls", true, 'notifications', true), \C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);;
        $arrFilesToAttach = array();
        $arrResultFilesToCombine = array();

        $this->arrAllUnnotifiedJobs = getAllUserMatchesNotNotified();
        if(is_null($this->arrAllUnnotifiedJobs) || count($this->arrAllUnnotifiedJobs) <= 0)
        {
            LogLine("No new jobs found to notify user about.", C__DISPLAY_WARNING__);
            return false;
        }

        $arrJobsToNotify = array_filter($this->arrAllUnnotifiedJobs, array($this, '_isIncludedJobSite') );
        $detailsHTMLFile = null;

        //
        // For our final output, we want the jobs to be sorted by company and then role name.
        // Create a copy of the jobs list that is sorted by that value.
        //
        $arrMatchedJobs = array_filter($arrJobsToNotify, "isSuccessfulUserJobMatch");
        $arrExcludedJobs = array_filter($arrJobsToNotify, "isNotUserJobMatch");

        logLine(PHP_EOL . "Writing final list of " . count($arrJobsToNotify) . " jobs to output files." . PHP_EOL, \C__DISPLAY_NORMAL__);

        // Output only new records that haven't been looked at yet
        $detailsCSVFile = parseFilePath($this->_filterAndWriteListToFile_($arrMatchedJobs, null, "-finalmatchedjobs", "CSV"));
        $detailsHTMLFile = parseFilePath($this->_filterAndWriteListToFile_($arrMatchedJobs, null, "-finalmatchedjobs", "HTML"));

        $arrResultFilesToCombine[] = $detailsCSVFile;
        $arrFilesToAttach[] = $detailsCSVFile;
        $arrFilesToAttach[] =  $detailsHTMLFile;


        $detailsExcludedCSVFile = parseFilePath($this->_filterAndWriteListToFile_($arrExcludedJobs, "isNotUserJobMatch", "-finalexcludedjobs", "CSV"));

        if ((filesize($detailsExcludedCSVFile['full_file_path']) < 10 * 1024 * 1024) || isDebug()) {
            $arrFilesToAttach[] = $detailsExcludedCSVFile;
        }

        $GLOBALS['logger']->logSectionHeader("" . PHP_EOL, \C__SECTION_END__, \C__NAPPSECONDLEVEL__);

        $xlsOutputFile = $this->_combineCSVsToExcel($detailsMainResultsXLSFile, $arrResultFilesToCombine);
        array_push($arrFilesToAttach, $xlsOutputFile);


        $GLOBALS['logger']->logSectionHeader("Generating text email content for user" . PHP_EOL, \C__SECTION_BEGIN__, \C__NAPPSECONDLEVEL__);

        $strResultCountsText = $this->getListingCountsByPlugin("text", $arrJobsToNotify, $arrExcludedJobs);
        $strResultText = "Job Scooper Results for ". getRunDateRange() . PHP_EOL . $strResultCountsText . PHP_EOL;

        $GLOBALS['logger']->logSectionHeader("Generating html email content for user" . PHP_EOL, \C__SECTION_BEGIN__, \C__NAPPSECONDLEVEL__);


        $messageHtml = $this->getListingCountsByPlugin("html", $arrMatchedJobs, $arrExcludedJobs, $detailsHTMLFile);

        $this->_wrapCSSStyleOnHTML_($messageHtml);

        $messageHtml = $this->_wrapCSSStyleOnHTML_($messageHtml);
        $subject = "New Job Postings: " . getRunDateRange();

        $GLOBALS['logger']->logSectionHeader("Generating text html content for user" . PHP_EOL, \C__SECTION_BEGIN__, \C__NAPPSECONDLEVEL__);

        LogPlainText($strResultText, \C__DISPLAY_SUMMARY__);

        //
        // Send the email notification out for the completed job
        //
        $GLOBALS['logger']->logSectionHeader("Sending email to user..." . PHP_EOL, \C__SECTION_BEGIN__, \C__NAPPSECONDLEVEL__);

        try {
            $ret = $this->sendEmail($strResultText, $messageHtml, $arrFilesToAttach, $subject, "results");
            if($ret !== false || $ret !== null)
            {
                if(!isDebug()) {
                    $arrToMarkNotified = array_from_orm_object_list_by_array_keys($arrJobsToNotify, array("JobPostingId"));
                    $ids = array_column($arrToMarkNotified, "JobPostingId");
                    $rowsAffected = 0;
                    foreach(array_chunk($ids, 100) as $arrChunkIds)
                    {
                        $results = \JobScooper\DataAccess\UserJobMatchQuery::create()
                            ->filterByJobPostingId($arrChunkIds)
                            ->update(array('UserNotificationState' => 'sent'), null, true);
                        $rowsAffected .= count($results);
                    }
                    if ($rowsAffected != count($arrToMarkNotified))
                        LogLine("Warning:  marked only {count($rowsAffected)} of {count($arrToMarkNotified)} UserJobMatch records as notified.");
                }
            }

        } catch (Exception $ex)
        {
            throw $ex;
        }

        //
        // We only keep interim files around in debug mode, so
        // after we're done processing, delete the interim HTML file
        //
        if (isDebug() !== true) {
            foreach ($arrFilesToAttach as $fileDetail) {
                if (file_exists($fileDetail['full_file_path']) && is_file($fileDetail ['full_file_path'])) {
                    logLine("Deleting local attachment file " . $fileDetail['full_file_path'] . PHP_EOL, \C__DISPLAY_NORMAL__);
                    unlink($fileDetail['full_file_path']);
                }
            }
        }
        $GLOBALS['logger']->logSectionHeader("" . PHP_EOL, \C__SECTION_END__, \C__NAPPSECONDLEVEL__);

        logLine(PHP_EOL."**************  DONE.  Cleaning up.  **************  ".PHP_EOL, \C__DISPLAY_NORMAL__);

        return $ret;
    }


    private function writeRunsJobsToFile($strFileOut, $arrJobsToOutput, $strExt = "CSV")
    {
        if($strExt == "HTML")
            $keysToOutput = $this->getKeysForHTMLOutput();
        else
            $keysToOutput = $this->getKeysForUserCSVOutput();

        if(is_null($keysToOutput))
            $keysToOutput = array();

        if(!$strFileOut || strlen($strFileOut) <= 0)
        {
            throw new ErrorException("Error: writeJobsListToFile called without an output file path to use.");
        }

        if(count($strFileOut) == 0)
        {
            logLine("Warning: writeJobsListToFile had no records to write to  " . $strFileOut, \C__DISPLAY_ITEM_DETAIL__);

        }

        $arrRecordsToOutput = $this->_convertToJobsArrays($arrJobsToOutput);

        $classCombined = new \SimpleCSV($strFileOut , "w");
        if (!is_array($arrRecordsToOutput))
        {
            $arrRecordsToOutput = array();
        }
        else
        {
            $this->sortJobsCSVArrayByCompanyRole($arrRecordsToOutput);
        }

        if ($keysToOutput == null && count($arrRecordsToOutput) > 0)
        {
            $exampleRec = $arrRecordsToOutput[array_keys($arrRecordsToOutput)[0]];

            $arrKeys = array_keys($exampleRec);
            $arrKeysInOrder = array();
            $tmpKeyOrderWithDupes = array_merge($keysToOutput, $arrKeys);
            foreach($tmpKeyOrderWithDupes as $key)
            {
                if(!in_array($key, $arrKeysInOrder))
                    $arrKeysInOrder[] = $key;
            }
            $keysToOutput = $arrKeysInOrder;
        }
        elseif($keysToOutput == null)
        {
            $keysToOutput = getEmptyJobListingRecord();
        }

        if($arrRecordsToOutput != null && count($arrRecordsToOutput) > 0)
        {
            foreach($arrRecordsToOutput as $reckey => $rec)
            {
                $out = array();
                foreach($keysToOutput as $k)
                {
                    $out[$k] = $rec[$k];
                }
                $arrRecordsToOutput[$reckey] = array_copy($out);
            }
        }

        if($strExt == 'HTML')
        {
            $classCombined->writeArrayToHTMLFile($arrRecordsToOutput, $keysToOutput, null);

        }
        else
        {
            array_unshift($arrRecordsToOutput, $keysToOutput);
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->getActiveSheet()->fromArray($arrRecordsToOutput, null, 'A1');
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "CSV");

//            $spreadsheet->removeSheetByIndex(0);
            $objWriter->save($strFileOut);

//            $classCombined->writeArrayToCSVFile($arrJobsRecordsToUse, $keysToOutput, $this->arrKeysForDeduping);
        }
        logLine("Jobs list had  ". count($arrRecordsToOutput) . " jobs and was written to " . $strFileOut , \C__DISPLAY_ITEM_START__);

        if($strExt == "HTML")
            $this->_addCSSStyleToHTMLFile_($strFileOut);

        return $arrJobsToOutput;

    }

    private function _filterAndWriteListToFile_($arrJobsList, $strFilterToApply, $strFileNameBase, $strExt = "CSV")
    {
        $filePath = getDefaultJobsOutputFileName($strFileNameBase, $strFilterToApply, $strExt, "_", 'notifications');


        if(countJobRecords($arrJobsList) == 0) return $arrJobsList;

        if($strFilterToApply == null || function_exists($strFilterToApply) === false)
        {
            logLine("No filter function supplied; outputting all results...", \C__DISPLAY_WARNING__);
            $arrJobs = $arrJobsList;
        }
        else
            $arrJobs = array_filter($arrJobsList, $strFilterToApply);

        $this->writeRunsJobsToFile($filePath, $arrJobs, $strExt);

        logLine($strFilterToApply . " " . count($arrJobs). " job listings output to  " . $filePath, \C__DISPLAY_ITEM_RESULT__);

        return $filePath;

    }

    function _getEmailAddressesByEmailType_($emailKind=null)
    {

        $settings = $GLOBALS['USERDATA']['configuration_settings']['email'];

        if(is_null($emailKind) || empty($emailKind))
            $emailKind = 'results';

        $retEmails = array (
            'to' => array(),
            'from' => array(),
            'bcc' => array()
        );

        foreach($settings['email_addresses'] as $emailaddy)
        {
            if((!array_key_exists('emailkind', $emailaddy) && $emailKind == "results") || strcasecmp($emailaddy['emailkind'], $emailKind) == 0)
            {
                if(!array_key_exists('name', $emailaddy))
                    $emailaddy['name'] = $emailaddy['address'];

                $retEmails[$emailaddy['type']][] = $emailaddy;
            }
        }

        if(!isset($retEmails["to"]) || count($retEmails["to"]) < 1 || strlen(current($retEmails["to"])['address']) <= 0)
        {
            $msg = "Could not find 'to:' email address in configuration file. Notification will not be sent.";
            logLine($msg, \C__DISPLAY_ERROR__);
            throw new InvalidArgumentException($msg);
        }

        if(count($retEmails['from']) > 1)
        {
            logLine("Multiple 'from:' email addresses found. Notification will be from first one only (" . $retEmails['from']['address'][0] . ").", \C__DISPLAY_WARNING__);
        }
        elseif(count($retEmails['from']) != 1)
        {
            $msg = "Could not find 'from:' email address in configuration file. Notification will not be sent.";
            logLine($msg, \C__DISPLAY_ERROR__);
            throw new InvalidArgumentException($msg);
        }
        $retEmails['from'] = $retEmails['from'][0];

        return $retEmails;

    }


    function sendEmail($strBodyText = null, $strBodyHTML = null, $arrDetailsAttachFiles = array(), $subject="No subject", $emailKind='results')
    {
        if (!isset($GLOBALS['OPTS']['send_notifications']) || $GLOBALS['OPTS']['send_notifications'] != 1) {
            logLine(PHP_EOL . "User set -send_notifications = false so skipping email notification.)" . PHP_EOL, \C__DISPLAY_NORMAL__);
            logLine("Mail contents would have been:" . PHP_EOL . $strBodyText, \C__DISPLAY_NORMAL__);
            return null;
        }


        $settings = $GLOBALS['USERDATA']['configuration_settings']['email'];

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


        //
        // Add initial email address header values
        //
        $emailAddrs = $this->_getEmailAddressesByEmailType_($emailKind);
        $strToAddys = "";
        $strBCCAddys = "";
        foreach($emailAddrs["to"] as $to)
        {
            $mail->addAddress($to['address'], $to['name']);
            $strToAddys .= (strlen($strToAddys) <= 0 ? "" : ", ") . $to['address'];
        }
        foreach($emailAddrs['bcc'] as $bcc)
        {
            $mail->addBCC($bcc['address'], $bcc['name']);
            $strBCCAddys .= ", " . $bcc['address'];
        }
        $mail->setFrom($emailAddrs['from']['address'], $emailAddrs['from']['name']);

        $mail->addReplyTo("dev@bryanselner.com", "dev@bryanselner.com" );
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        logLine("Email to:\t" . $strToAddys , \C__DISPLAY_NORMAL__);
        logLine("Email from:\t" . $emailAddrs['from']['address'], \C__DISPLAY_NORMAL__);
        logLine("Email bcc:\t" . $strBCCAddys, \C__DISPLAY_NORMAL__);


        $mail->WordWrap = 120;                                          // Set word wrap to 120 characters
        if(!is_null($arrDetailsAttachFiles) && is_array($arrDetailsAttachFiles))
        {
            foreach($arrDetailsAttachFiles as $detailsAttach)
            {
                if(isset($detailsAttach) && isset($detailsAttach['full_file_path']))
                {
                    $mail->addAttachment($detailsAttach['full_file_path']);
                }
            }
        }        // Add attachments

        $mail->isHTML(true);                                            // Set email format to HTML


        $mail->Body    = $strBodyHTML;
        $mail->AltBody = $strBodyText;
        $mail->Subject = $subject;

        $ret = $mail->send();
        if($ret !== true)
        {
            //
            // If sending the email fails, try again but this time with SMTP debug
            // enabled so we have any idea what the issue might be in the log.
            // If we don't do this, we just get "failed" without any useful details.
            //
            $msg = "Failed to send notification email with error = ".$mail->ErrorInfo . PHP_EOL . "Retrying email send with debug enabled to log error details...";
            logLine($msg, \C__DISPLAY_ERROR__);
            $mail->SMTPDebug = 1;
            $ret = $mail->send();
            if($ret === true) return $ret;

            $msg = "Failed second attempt to send notification email.  Debug error details should be logged above.  Error: " . PHP_EOL .$mail->ErrorInfo;
            logLine($msg, \C__DISPLAY_ERROR__);
            throw new Exception($msg);

        }
        else
        {
            logLine("Email notification sent to '" . $strToAddys . "' from '" . $emailAddrs['from']['address'] . "' with BCCs to '" . $strBCCAddys ."'", \C__DISPLAY_ITEM_RESULT__);
        }
        return $ret;

    }


    private function _getFullFileContents_($detailsFile)
    {
        $content = null;
        $filePath = $detailsFile['full_file_path'];

        if(strlen($filePath) < 0)
        {
            logLine("Unable to get contents from '". var_export($detailsFile, true) ."' to assets in email.  Failing notification.", \C__DISPLAY_ERROR__);
            return null;
        }

        # Open a file
        $file = fopen( $filePath, "r" );
        if( $file == false )
        {
            logLine("Unable to open file '". $filePath ."' for to get contents for notification mail.  Failing notification.", \C__DISPLAY_ERROR__);
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

    private function _getJobSitesRunRecently()
    {
        if(is_null($this->_arrJobSitesForRun)) {
            $this->_arrJobSitesForRun = getAllJobSitesThatWereLastRun();

            $sites = array_map(function ($var) {
                return $var->getJobPosting()->getJobSite();
            }, $this->arrAllUnnotifiedJobs);
            $uniqSites = array_unique($sites);

            $this->_arrJobSitesForRun = array_merge($this->_arrJobSitesForRun, $uniqSites);
        }

        return $this->_arrJobSitesForRun;

    }
    private function getListingCountsByPlugin($fLayoutType, $arrMatchedJobs = null, $arrExcludedJobs = null, $detailsHTMLBodyInclude = null)
    {

        $arrCounts = array();
        $arrExcluded = null;

        $strOut = "                ";
        $arrHeaders = array("For Review", "Auto-Filtered", "New Listings");

        $arrFailedPluginsReport = getFailedSearchesByPlugin();


        foreach($this->_getJobSitesRunRecently() as $plugin) {

            $arrPluginJobMatches  = array();
            if ($arrMatchedJobs != null && is_array($arrMatchedJobs) && countJobRecords($arrMatchedJobs) > 0) {
                $arrPluginJobMatches = array_filter($arrMatchedJobs, function ($var) use ($plugin) { return (strcasecmp($var->getJobPosting()->getJobSite(), $plugin) == 0); } );
            }

            $arrPluginExcludesJobs  = array();
            if ($arrExcludedJobs != null && is_array($arrExcludedJobs) && countJobRecords($arrExcludedJobs) > 0) {
                $arrPluginExcludesJobs = array_filter($arrExcludedJobs, function ($var) use ($plugin) { return (strcasecmp($var->getJobPosting()->getJobSite(), $plugin) == 0); } );
            }

            $arrPluginJobs = $arrPluginJobMatches + $arrPluginExcludesJobs;

            $arrCounts[$plugin]['name'] = $plugin;
            $arrCounts[$plugin]['for_review'] = count(array_filter($arrPluginJobs, "isSuccessfulUserJobMatch"));
            $arrCounts[$plugin]['total_not_interested'] = count(array_filter($arrPluginJobs, "isNotUserJobMatch"));
            $arrCounts[$plugin]['total_listings'] = count($arrPluginJobs);
            $arrCounts[$plugin]['had_error'] = false;

            //
            // if the plugin also errored, then add an asterisk to the name
            // for refernce in the email
            //
            if(!is_null($arrFailedPluginsReport) && in_array($plugin, array_keys($arrFailedPluginsReport)) === true)
            {
                $arrCounts[$plugin]['name'] = "**" . $plugin;
                $arrCounts[$plugin]['had_error'] = true;
            }
        }

        usort($arrCounts, "sortByErrorThenCount");


        switch ($fLayoutType)
        {
            case "html":
                $content = $this->_getResultsTextHTML_($arrHeaders, $arrCounts, $detailsHTMLBodyInclude);
                break;

            default:
            case "text":
                $content = $this->_getResultsTextPlain_($arrHeaders, $arrCounts);
                break;

        }

        return $content;
    }

    private function _printResultsLine_($arrRow, $strType="TEXT")
    {
        if($arrRow == null || !isset($arrRow) || !is_array($arrRow)) return "";

        $strOut = "";
        $fFirstCol = true;

        $style = 'class="job_scooper"';

        if ($arrRow['had_error'] == true)
        {
            $style = ' class="job_scooper jobsite_error" style="color=Grey;"';
            unset($arrRow['had_error']);
        }

        if($strType == "HTML")
        {
            $strOut .=  PHP_EOL . "<tr " . $style .">". PHP_EOL;
        }

        foreach($arrRow as $value)
        {
            switch ($strType)
            {
                case "HTML":
                    if($fFirstCol == true)
                    {
                        $strOut .= "<td width='20%' align='left'><span " . $style . " >" . $value . "</span></td>" . PHP_EOL;
                        $fFirstCol = false;
                    }
                    else
                        $strOut .= "<td width='10%' align='center'><span " . $style . " >" . $value . "</span></td>" . PHP_EOL;
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

    private function _getResultsTextPlain_($arrHeaders, $arrCounts)
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

            foreach($arrCounts as $site)
            {
                $strOut .= $this->_printResultsLine_($site, "TEXT");
            }


            $strOut .= sprintf("%'=100s","") . PHP_EOL;
            $strOut .= $this->_printResultsLine_($arrCounts_TotalUser);
            $strOut .= $this->_printResultsLine_($arrCounts_TotalAll);
            $strOut .= PHP_EOL;
        }

        if($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'] != null && count($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']) > 0)
        {
            sort($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']);
            $strExcluded = getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'], ", ", "Sites excluded by user or settings: ", false);
            $strOut .= $strExcluded;
        }


        return $strOut;
    }


    private function _getResultsTextHTML_($arrHeaders, $arrCounts, $detailsHTMLBodyInclude = null)
    {
        $arrCounts_TotalAll = null;
        $arrCounts_TotalUser = null;
        $strOut = "<div class='job_scooper outer'>";

        $strOut  .= "<H1>Job Postings to Review for " . getRunDateRange() . "</H1>".PHP_EOL;
        $strOut  .= "<div style=\"width: 100%; text-align: right;\"><span style=\"font-size: xx-small; color: #49332D;\">Generated by " . gethostname() . " running " . __APP_VERSION__. " on " . getTodayAsString() . ".</span></div>" . PHP_EOL;

        //
        // Include the contents of the HTML file if passed
        //
        if(!is_null($detailsHTMLBodyInclude) && array_key_exists('has_file', $detailsHTMLBodyInclude) && $detailsHTMLBodyInclude['has_file'] == true ) {
            $strOut .= PHP_EOL . "<div class=\"job_scooper section\">" . PHP_EOL;
            $strOut .= $this->_getFullFileContents_($detailsHTMLBodyInclude);
            $strOut .= PHP_EOL . PHP_EOL;
            $strOut .= "</div>";
            $strOut .= "<br>" . PHP_EOL . "<br>" . PHP_EOL;
        }
        else
        {
            $strOut .= PHP_EOL . "<div class=\"job_scooper section\">" . PHP_EOL;
            $strOut  .= "No new jobs were found that matched your search terms.". PHP_EOL. PHP_EOL;
            $strOut .= PHP_EOL . PHP_EOL;
            $strOut .= "</div>";
            $strOut .= "<br>" . PHP_EOL . "<br>" . PHP_EOL;
        }


        if($arrCounts != null && count($arrCounts) > 0)
        {
            $strOut  .= "<H2>Search Results by Job Site</H2>".PHP_EOL. PHP_EOL;
            $strOut .= "<table id='resultscount' class='job_scooper'>" . PHP_EOL . "<thead>". PHP_EOL;
            $strOut .= "<th class='job_scooper' width='20%' align='left'>Job Site</td>" . PHP_EOL;

            foreach($arrHeaders as $value)
            {
                $strOut .= "<th class='job_scooper' width='10%' align='center'>" . $value . "</th>" . PHP_EOL;
            }
            $strOut .=  PHP_EOL . "</thead>". PHP_EOL;

            foreach($arrCounts as $site)
            {
                $strOut .= $this->_printResultsLine_($site, "HTML");
            }

            $strOut .=  PHP_EOL . "<tr class='job_scooper totaluser'>". PHP_EOL;
            $strOut .= $this->_printResultsLine_($arrCounts_TotalUser, "HTML");
            $strOut .=  PHP_EOL . "</tr><tr class='job_scooper totalall'>". PHP_EOL;
            $strOut .= $this->_printResultsLine_($arrCounts_TotalAll, "HTML");
            $strOut .=  PHP_EOL . "</tr>". PHP_EOL;

            $strOut .=  PHP_EOL . "</table>". PHP_EOL. PHP_EOL;
        }


        if($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'] != null && count($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']) > 0)
        {
            $strOut .=  PHP_EOL . "<div class=\"job_scooper section\">". PHP_EOL;
            sort($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']);

            $strExcluded = getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'], ", ", "", false);
            $strOut  .= "<H2>Excluded Job Sites</H2>".PHP_EOL. PHP_EOL;

            $strOut .=  PHP_EOL .  "<span style=\"font-size: xx-small; \">Excluded sites for this run:" . $strExcluded . "</span>" . PHP_EOL;
            $strOut .= "</div>";
            $strOut .= "<br>" . PHP_EOL . "<br>" . PHP_EOL;
        }


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
        $cssData = array(
            'color-primary' => "#49332D",
            'color-secondary' => "#e7dcd7",
            'color-level-1' => '#6d4d41',
            'color-level-2' => '#61443a',
            'color-level-3' => '#553c33',
            'color-level-4' => '#49332c',
            'color-level-5' => '#3d2b24'
        );


        $renderer = loadTemplate(__ROOT__."/assets/templates/html_notification_email_css.tmpl");

        $css = renderTemplate($renderer, $cssData);

        $cssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
        $cssToInlineStyles->setHTML($strHTML);
        $cssToInlineStyles->setCSS($css);
        return $cssToInlineStyles->convert();
    }


    private function getKeysForHTMLOutput()
    {
        return array(
            'Company',
//            'job_title',
            'JobTitleLinked',
//            'job_post_url',
//            'job_site_date' =>'',
//            'interested',
//            'match_notes',
//            'status',
            'Location',
//            'job_site_category',
//            'job_site',
//            'job_id',
//            'key_jobsite_siteid',
//            'key_company_role',
//            'date_last_updated',
        );
    }

    private function getKeysForUserCSVOutput($optimizedView=true)
    {
        $match = new \JobScooper\DataAccess\UserJobMatch();
        $jobPost = new JobPosting();
        $allKeys = array_merge(array_keys($jobPost->toArray()), array_keys($match->toArray()));


        if($optimizedView) {
            $retKeys = array_diff($allKeys, array('AppRunId', 'UserJobMatchId', 'UserNotificationState', 'TitleTokens', 'JobTitleLinked', "FirstSeenAt", "RemovedAt", "UpdatedAt", "KeySiteAndPostID", "KeyCompanyAndTitle"));
        }
        else {
            $retKeys = $allKeys;
        }
        return array_unique($retKeys);
    }

    private function _convertToJobsArrays($arrJobObjects)
    {
        $arrRet = array();
        foreach($arrJobObjects as $jobMatch)
        {
            $item = $jobMatch->toFlatArray();
            $arrRet[$item['KeySiteAndPostID']] = $item;
        }

        return $arrRet;

    }



    function sortJobsCSVArrayByCompanyRole(&$arrJobList)
    {

        if (countJobRecords($arrJobList) > 0) {
            $arrFinalJobIDs_SortedByCompanyRole = array();
            $finalJobIDs_CompanyRole = array_column($arrJobList, 'KeyCompanyAndTitle', 'KeySiteAndPostID');
            foreach (array_keys($finalJobIDs_CompanyRole) as $key) {
                // Need to add uniq key of job site id to the end or it will collapse duplicate job titles that
                // are actually multiple open posts
                $arrFinalJobIDs_SortedByCompanyRole[$finalJobIDs_CompanyRole[$key] . "-" . $key] = $key;
            }

            ksort($arrFinalJobIDs_SortedByCompanyRole);
            $arrFinalJobs_SortedByCompanyRole = array();
            foreach ($arrFinalJobIDs_SortedByCompanyRole as $jobid) {
                $arrFinalJobs_SortedByCompanyRole[$jobid] = $arrJobList[$jobid];
            }
            $arrJobList = $arrFinalJobs_SortedByCompanyRole;
        }

    }




}