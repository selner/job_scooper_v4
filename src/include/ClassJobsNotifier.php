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

if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/SitePlugins.php');
require_once(__ROOT__.'/include/JobListFilters.php');

const C__RESULTS_INDEX_ALL = '***TOTAL_ALL***';
const C__RESULTS_INDEX_USER = '***TOTAL_USER***';

class ClassJobsNotifier extends ClassJobsSiteCommon
{
    protected $siteName = "ClassJobsNotifier";
    protected $arrLatestJobs = array();

    function __construct($arrJobs, $strOutputDirectory)
    {
        parent::__construct($strOutputDirectory);
        if(!is_null($arrJobs)) {
            $this->arrLatestJobs = \Scooper\array_copy($arrJobs);
        }
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

        $detailsMainResultsCSVFile = \Scooper\getFilePathDetailsFromString(join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['results'], getDefaultJobsOutputFileName("results", "", "csv"))), \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
        $this->_filterAndWriteListToFile_($arrFinalJobs_SortedByCompanyRole, "isMarked_InterestedOrBlank", $detailsMainResultsCSVFile);

        // Output only new records that haven't been looked at yet
//        $allupdatedjobsfile = $this->_outputFilteredJobsListToFile_($arrFinalJobs_SortedByCompanyRole, "isJobUpdatedToday", "-allchangedjobs", "CSV");

        $detailsMainResultsXLSFile = \Scooper\array_copy($detailsMainResultsCSVFile);
        $detailsMainResultsXLSFile['file_extension'] = "xls";
        $detailsMainResultsXLSFile = \Scooper\parseFilePath(\Scooper\getFullPathFromFileDetails($detailsMainResultsXLSFile));

        // Output all records that were automatically excluded
        $fileExcludedJobs = $this->_filterAndWriteListToAlternateFile_($arrFinalJobs_SortedByCompanyRole, "isMarked_NotInterested", "ExcludedJobs", "CSV", "excluded jobs", true);
//        $this->_filterAndWriteListToAlternateFile_($arrFinalJobs_SortedByCompanyRole, "isMarkedBlank", "NotMarkedJobs", "CSV", "NotMarkedJobs", true);

        // Output only new records that haven't been looked at yet
//        $this->_outputFilteredJobsListToFile_($arrFinalJobs_SortedByCompanyRole, "isMarked_InterestedOrBlank", "-AllUnmarkedJobs", "CSV");
        $detailsHTMLFile = \Scooper\parseFilePath($this->_outputFilteredJobsListToFile_($arrFinalJobs_SortedByCompanyRole, "isMarked_InterestedOrBlank", "-AllUnmarkedJobs", "HTML"));

        $arrResultFilesToCombine = array($detailsMainResultsCSVFile, \Scooper\parseFilePath($fileExcludedJobs));
        $arrFilesToAttach = array($detailsMainResultsXLSFile, $detailsHTMLFile, $detailsMainResultsCSVFile);

        //
        // In a debug run, include the full details of the keywords we used for auto-matching
        //
        if(isDebug()) {
            foreach ($GLOBALS['USERDATA']['user_input_files_details'] as $inputfile) {
                array_push($arrResultFilesToCombine, $inputfile['details']);
            }
        }

        $xlsOutputFile = $this->_combineCSVsToExcel($detailsMainResultsXLSFile, $arrResultFilesToCombine);
        array_push($arrFilesToAttach, $xlsOutputFile);

        $GLOBALS['logger']->logSectionHeader("" . PHP_EOL, \Scooper\C__SECTION_END__, \Scooper\C__NAPPSECONDLEVEL__);


        //
        // Output debugging / interim files if asked to
        //

        if(isDebug() === true) {
            $GLOBALS['logger']->logSectionHeader("DEBUG ONLY:  Writing out interim, developer files (user does not ever see these)..." . PHP_EOL, \Scooper\C__SECTION_BEGIN__, \Scooper\C__NAPPSECONDLEVEL__);

            //
            // Now, output the various subsets of the total jobs list
            //

            $this->_filterAndWriteListToAlternateFile_($arrFinalJobs_SortedByCompanyRole, "isJobUpdatedTodayOrIsInterestedOrBlank", "", "CSV", "updated today", false);

            // Output all job records and their values
            $this->_filterAndWriteListToAlternateFile_($arrFinalJobs_SortedByCompanyRole, null, "-AllJobs", "CSV", "all jobs", false);

            $GLOBALS['logger']->logSectionHeader("" . PHP_EOL, \Scooper\C__SECTION_END__, \Scooper\C__NAPPSECONDLEVEL__);
        }


        $GLOBALS['logger']->logSectionHeader("Generating text email content for user" . PHP_EOL, \Scooper\C__SECTION_BEGIN__, \Scooper\C__NAPPSECONDLEVEL__);

        $strResultCountsText = $this->getListingCountsByPlugin("text", $arrFinalJobs_SortedByCompanyRole);
        $strResultText = "Job Scooper Results for ". getRunDateRange() . PHP_EOL . $strResultCountsText . PHP_EOL;

        $GLOBALS['logger']->logSectionHeader("Generating text html content for user" . PHP_EOL, \Scooper\C__SECTION_BEGIN__, \Scooper\C__NAPPSECONDLEVEL__);


        $messageHtml = $this->getListingCountsByPlugin("html", $arrFinalJobs_SortedByCompanyRole, $detailsHTMLFile);

        $this->_wrapCSSStyleOnHTML_($messageHtml);
        $subject = "New Job Postings: " . getRunDateRange();

        $GLOBALS['logger']->logSectionHeader("Generating text html content for user" . PHP_EOL, \Scooper\C__SECTION_BEGIN__, \Scooper\C__NAPPSECONDLEVEL__);

        $GLOBALS['logger']->logLine($strResultText, \Scooper\C__DISPLAY_SUMMARY__);

        //
        // Send the email notification out for the completed job
        //
        $GLOBALS['logger']->logSectionHeader("Sending email to user..." . PHP_EOL, \Scooper\C__SECTION_BEGIN__, \Scooper\C__NAPPSECONDLEVEL__);
        $ret = $this->sendEmail($strResultText, $messageHtml, $arrFilesToAttach, $subject, "results");

        //
        // We only keep interim files around in debug mode, so
        // after we're done processing, delete the interim HTML file
        //
        if (isDebug() !== true) {
            foreach ($arrFilesToAttach as $fileDetail) {
                if (file_exists($fileDetail['full_file_path']) && is_file($fileDetail ['full_file_path'])) {
                    $GLOBALS['logger']->logLine("Deleting local attachment file " . $fileDetail['full_file_path'] . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
                    unlink($fileDetail['full_file_path']);
                }
            }
        }
        $GLOBALS['logger']->logSectionHeader("" . PHP_EOL, \Scooper\C__SECTION_END__, \Scooper\C__NAPPSECONDLEVEL__);

        $GLOBALS['logger']->logLine(PHP_EOL."**************  DONE.  Cleaning up.  **************  ".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);

        return $ret;
    }


    private function writeRunsJobsToFile($strFileOut, $arrJobsToOutput, $strLogDescriptor, $strExt = "CSV")
    {
        if($strExt == "HTML")
            $keys = $this->getKeysForHTMLOutput();
        else
            $keys = $this->getKeysForUserCSVOutput();

        $this->writeJobsListToFile($strFileOut, $arrJobsToOutput, true, "ClassJobRunner-".$strLogDescriptor, $strExt, $keys);

        if($strExt == "HTML")
            $this->_addCSSStyleToHTMLFile_($strFileOut);

        return $arrJobsToOutput;

    }

    private function __getAlternateOutputFileDetails__($strNamePrepend = "results", $strNameAppend = "", $ext = "")
    {
        $fileName = getDefaultJobsOutputFileName($strNamePrepend, $strNameAppend, $ext, "");
        $detailsRet = \Scooper\parseFilePath(join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['results'], $fileName)), false);
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
            $GLOBALS['logger']->logLine("No filter function supplied; outputting all results...", \Scooper\C__DISPLAY_WARNING__);
            $arrJobs = $arrJobsList;
        }
        else
            $arrJobs = array_filter($arrJobsList, $strFilterToApply);



        if(strcasecmp($fileDetails['file_extension'], "HTML") == 0)
        {
            foreach(array_keys($arrJobs) as $jobKey)
            {
                $arrJobs[$jobKey]['job_title_linked'] = '<a href="'.$arrJobs[$jobKey]['job_post_url'].'" target="new">'.$arrJobs[$jobKey]['job_title'].'</a>';
            }
        }

        $this->writeRunsJobsToFile($fileDetails['full_file_path'], $arrJobs, $strFilterToApply, $fileDetails['file_extension']);

        $GLOBALS['logger']->logLine($strFilterToApply . " " . count($arrJobs). " job listings output to  " . $fileDetails['full_file_path'], \Scooper\C__DISPLAY_ITEM_RESULT__);

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
        if($fOverrideInterimFileOption == false && isDebug() !== true)
        {
            unlink($details['full_file_path']);
            return $dataRet;
        }

        return $dataRet;
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
            $GLOBALS['logger']->logLine($msg, \Scooper\C__DISPLAY_ERROR__);
            throw new InvalidArgumentException($msg);
        }

        if(count($retEmails['from']) > 1)
        {
            $GLOBALS['logger']->logLine("Multiple 'from:' email addresses found. Notification will be from first one only (" . $retEmails['from']['address'][0] . ").", \Scooper\C__DISPLAY_WARNING__);
        }
        elseif(count($retEmails['from']) != 1)
        {
            $msg = "Could not find 'from:' email address in configuration file. Notification will not be sent.";
            $GLOBALS['logger']->logLine($msg, \Scooper\C__DISPLAY_ERROR__);
            throw new InvalidArgumentException($msg);
        }
        $retEmails['from'] = $retEmails['from'][0];

        return $retEmails;

    }


    function sendEmail($strBodyText = null, $strBodyHTML = null, $arrDetailsAttachFiles = array(), $subject="No subject", $emailKind='results')
    {
        if (!isset($GLOBALS['OPTS']['send_notifications']) || $GLOBALS['OPTS']['send_notifications'] != 1) {
            $GLOBALS['logger']->logLine(PHP_EOL . "User set -send_notifications = false so skipping email notification.)" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
            $GLOBALS['logger']->logLine("Mail contents would have been:" . PHP_EOL . $strBodyText, \Scooper\C__DISPLAY_NORMAL__);
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
            $GLOBALS['logger']->logLine($msg, \Scooper\C__DISPLAY_ERROR__);
            $mail->SMTPDebug = 1;
            $ret = $mail->send();
            if($ret === true) return $ret;

            $msg = "Failed second attempt to send notification email.  Debug error details should be logged above.  Error: " . PHP_EOL .$mail->ErrorInfo;
            $GLOBALS['logger']->logLine($msg, \Scooper\C__DISPLAY_ERROR__);
            throw new Exception($msg);

        }
        else
        {
            $GLOBALS['logger']->logLine("Email notification sent to '" . $strToAddys . "' from '" . $emailAddrs['from']['address'] . "' with BCCs to '" . $strBCCAddys ."'", \Scooper\C__DISPLAY_ITEM_RESULT__);
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


    private function getListingCountsByPlugin($fLayoutType, $arrPluginJobsUnfiltered = null, $detailsHTMLBodyInclude = null)
    {

        $arrCounts = null;
        $arrExcluded = null;

        $strOut = "                ";
        $arrHeaders = array("For Review", "Auto-Filtered", "Active Jobs", "Downloaded Listings");

        $arrFailedPluginsReport = getFailedSearchesByPlugin();

        foreach($GLOBALS['USERDATA']['configuration_settings']['included_sites'] as $plugin) {
            if ($arrPluginJobsUnfiltered == null || !is_array($arrPluginJobsUnfiltered) || countJobRecords($arrPluginJobsUnfiltered) == 0) {
                $arrPluginJobs = array();
            } else {
                $arrPluginJobs = array_filter($arrPluginJobsUnfiltered, function ($var) use ($plugin) { return (strcasecmp($var['job_site'], $plugin) == 0); } );
            }

            $arrCounts[$plugin]['name'] = $plugin;
            $arrCounts[$plugin]['for_review'] = count(array_filter($arrPluginJobs, "isMarkedBlank"));
            $arrCounts[$plugin]['total_not_interested'] = count(array_filter($arrPluginJobs, "isMarked_NotInterested"));
            $arrCounts[$plugin]['total_active'] = count(array_filter($arrPluginJobs, "isMarked_InterestedOrBlank"));
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
                $content = $this->_getResultsTextHTML_($arrHeaders, $arrCounts, $arrFailedPluginsReport, $detailsHTMLBodyInclude);
                break;

            default:
            case "text":
                $content = $this->_getResultsTextPlain_($arrHeaders, $arrCounts, $arrFailedPluginsReport);
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

    private function _getResultsTextPlain_($arrHeaders, $arrCounts, $arrFailedPlugins = null)
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

        if(!is_null($arrFailedPlugins) && is_array($arrFailedPlugins) && count($arrFailedPlugins) > 0)
        {
            sort($arrFailedPlugins);
            $strOut .=  PHP_EOL .  PHP_EOL .  "The following job site plugins failed due to unexpected errors:" . PHP_EOL;

            foreach($arrFailedPlugins as $site)
            {
                foreach($site as $search) {
                    $strOut .= "* " . $search['site_name'] . ": \t" . $search['search_run_result']['details'] . PHP_EOL;
                }
            }

            $strOut .=  PHP_EOL . PHP_EOL;
        }


        if($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'] != null && count($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']) > 0)
        {
            sort($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']);
            $strExcluded = getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'], ", ", "Sites excluded by user or settings: ", false);
            $strOut .= $strExcluded;
        }


        return $strOut;
    }


    private function _getResultsTextHTML_($arrHeaders, $arrCounts, $arrFailedPlugins = null, $detailsHTMLBodyInclude = null)
    {
        $arrCounts_TotalAll = null;
        $arrCounts_TotalUser = null;
        $strOut = "<div class='job_scooper outer'>";

        $strOut  .= "<H2>New Job Postings for " . getRunDateRange() . "</H2>".PHP_EOL. PHP_EOL;

        if($arrCounts != null && count($arrCounts) > 0)
        {
            $strOut .= "<table id='resultscount' class='job_scooper'>" . PHP_EOL . "<thead>". PHP_EOL;
            $strOut .= "<th class='job_scooper' width='20%' align='left'>Job Site</td>" . PHP_EOL;

            foreach($arrHeaders as $value)
            {
                $strOut .= "<th class='job_scooper' width='10%' align='center'>" . $value . "</th>" . PHP_EOL;
            }
            $strOut .=  PHP_EOL . "</thead>". PHP_EOL;

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

            $strOut .=  PHP_EOL . "</table>". PHP_EOL. PHP_EOL;
        }



        if($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'] != null && count($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']) > 0)
        {
            $strOut .=  PHP_EOL . "<div class=\"job_scooper section\">". PHP_EOL;
            sort($GLOBALS['USERDATA']['configuration_settings']['excluded_sites']);

            $strExcluded = getArrayValuesAsString($GLOBALS['USERDATA']['configuration_settings']['excluded_sites'], ", ", "", false);

            $strOut .=  PHP_EOL .  "<span style=\"font-size: xx-small; color: #8e959c;\">Excluded sites for this run:" . $strExcluded . "</span>" . PHP_EOL;
            $strOut .= "</div>";
            $strOut .= "<br>" . PHP_EOL . "<br>" . PHP_EOL;
        }


        //
        // Include the contents of the HTML file if passed
        //
        if(!is_null($detailsHTMLBodyInclude)) {
            $strOut .= PHP_EOL . "<div class=\"job_scooper section\">" . PHP_EOL;
            $strOut .= "<br>" . PHP_EOL . "<br>" . PHP_EOL;
            $strOut .= '<H2>New Job Matches</H2>' . PHP_EOL . PHP_EOL;
            $strOut .= $this->_getFullFileContents_($detailsHTMLBodyInclude);
            $strOut .= PHP_EOL . PHP_EOL;
            $strOut .= "</div>";
            $strOut .= "<br>" . PHP_EOL . "<br>" . PHP_EOL;
        }


        if(!is_null($arrFailedPlugins) && is_array($arrFailedPlugins) && count($arrFailedPlugins) > 0)
        {
            $strOut .=  PHP_EOL . "<div class=\"job_scooper section\">". PHP_EOL;
            sort($arrFailedPlugins);
            $strOut .=  PHP_EOL . "<div class='job_scooper section' style=' color: DarkRed; '>". PHP_EOL;
            $strOut .=  PHP_EOL .  "* The following job site plugins failed due to unexpected errors:" . PHP_EOL;
            $strOut .=  PHP_EOL . "<ul class='job_scooper'>". PHP_EOL;

            foreach($arrFailedPlugins as $site)
            {
                foreach($site as $search) {
                    $strOut .= "<li>" . $search['site_name'] . ": <span style=\"color: Grey\">" . $search['search_run_result']['details'] . "</span></li>" . PHP_EOL;
                }
            }

            $strOut .=  PHP_EOL . "</ul>". PHP_EOL;
            $strOut .= "</div>";
            $strOut .= "<br>" . PHP_EOL . "<br>" . PHP_EOL;
        }


        $strOut .=  PHP_EOL . "<div class=\"job_scooper section\">". PHP_EOL;
        $strOut .=  PHP_EOL .  "<p style=\"min-height: 15px;\">&nbsp;</p><span style=\"font-size: xx-small; color: SlateBlue;\">Generated by " . __APP_VERSION__. " (" . gethostname() . ") " . \Scooper\getTodayAsString() . ".</span>" . PHP_EOL;
        $strOut .= "</div>";
        $strOut .= "<br>" . PHP_EOL . "<br>" . PHP_EOL;

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

    private function getKeysForUserCSVOutput()
    {
        $arr = $this->getEmptyJobListingRecord();

        $allKeys  = array_diff(array_keys($arr), array('job_title_tokenized', 'normalized', 'job_title_linked', 'job_id', 'date_last_updated', 'key_company_role', 'match_notes'));
        return $allKeys;

    }





} 