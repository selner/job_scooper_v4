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

use JobScooper\Builders\JobSitePluginBuilder;
use JobScooper\Utils\JobsMailSender;
use JobScooper\Utils\SimpleCSV;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Worksheet;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Fill;
use ErrorException;
use Exception;

class NotifierJobAlerts extends JobsMailSender
{
    protected $JobSiteName = "NotifierJobAlerts";
    protected $arrAllUnnotifiedJobs = array();
    private $_arrJobSitesForRun = null;

    function __construct()
    {
        parent::__construct(false);
    }

    function __destruct()
    {
        LogMessage("Closing ".$this->JobSiteName." instance of class " . get_class($this));
    }

    private function _combineCSVsToExcel($outfile, $arrCSVFiles)
    {
        $spreadsheet = new PHPExcel();
        $objWriter = PHPExcel_IOFactory::createWriter($spreadsheet, "Excel2007");
        LogMessage("Creating output XLS file '" . $outfile . "'." . PHP_EOL);
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

        foreach($arrCSVFiles as $attachFile)
        {
        	$csvFile = new \SplFileInfo($attachFile);
            if(strcasecmp($csvFile->getExtension(), "csv") == 0)
            {
                $objPHPExcelFromCSV = PHPExcel_IOFactory::createReaderForFile($attachFile);
                $srcFile = $objPHPExcelFromCSV->load($attachFile);
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

                $nameParts = explode("-", $csvFile->getBasename());
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


                LogMessage("Added data from CSV '" . $attachFile . "' to output XLS file." . PHP_EOL);
            }
        }

        $spreadsheet->removeSheetByIndex(0);
        $objWriter->save($outfile);


        return $outfile;

    }


    protected function _isIncludedJobSite($var)
    {
        $sites = $this->_getJobSitesRunRecently();

        return in_array(cleanupSlugPart($var->getJobPostingFromUJM()->getJobSiteKey()), $sites);

    }

    function processNotifications()
    {

	    startLogSection("Processing user notification alerts");


	    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Output the full jobs list into a file and into files for different cuts at the jobs list data
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $class = null;


        //
        // Output the final files we'll send to the user
        //

        // Output all records that match the user's interest and are still active
        $detailsMainResultsXLSFile = generateOutputFileName("results", "xls", true, 'notifications');
        $arrFilesToAttach = array();
        $arrResultFilesToCombine = array();

        $this->arrAllUnnotifiedJobs = getAllMatchesForUserNotification();
        if(is_null($this->arrAllUnnotifiedJobs) || count($this->arrAllUnnotifiedJobs) <= 0)
        {
            LogWarning("No new jobs found to notify user about.");
	        endLogSection(" User results notification.");
            return false;
        }

        $arrJobsToNotify = array_filter($this->arrAllUnnotifiedJobs, array($this, '_isIncludedJobSite') );
        $detailsHTMLFile = null;

        //
        // For our final output, we want the jobs to be sorted by company and then role name.
        // Create a copy of the jobs list that is sorted by that value.
        //
        $arrMatchedJobs = array_filter($arrJobsToNotify, "isUserJobMatch");

	    startLogSection("Writing Results Files to Send User");

        LogMessage(PHP_EOL . "Writing final list of " . count($arrJobsToNotify) . " jobs to output files." . PHP_EOL);

        $arrExcludedJobs = array_filter($arrJobsToNotify, "isExcluded");
        $arrMatchedAndNotExcludedJobs = array_filter($arrMatchedJobs, "isUserJobMatchAndNotExcluded");

        $detailsMatchOnlyCSV = $this->_filterAndWriteListToFile_($arrMatchedAndNotExcludedJobs, "Matches", "CSV");
	    if(!empty($detailsMatchOnlyCSV))
		    $arrResultFilesToCombine[] = $detailsMatchOnlyCSV;
	    $detailsExcludedCSVFile = $this->_filterAndWriteListToFile_($arrExcludedJobs, "ExcludedJobs", "CSV");
	    if(!empty($detailsExcludedCSVFile) && (filesize($detailsExcludedCSVFile) < 10 * 1024 * 1024) || isDebug()) {
			    $arrResultFilesToCombine[] = $detailsExcludedCSVFile;
	    }
        $detailsHTMLFile = $this->_filterAndWriteListToFile_($arrMatchedAndNotExcludedJobs, "Matches", "HTML");

        $xlsOutputFile = $this->_combineCSVsToExcel($detailsMainResultsXLSFile, $arrResultFilesToCombine);
        array_unshift($arrFilesToAttach, $xlsOutputFile);

	    endLogSection(" Results file generation.");




	    startLogSection("Generating notification email contents...");

        LogMessage("Generating text email content for user");

        $strResultCountsText = $this->getListingCountsByPlugin("text", $arrJobsToNotify, $arrExcludedJobs);
        $strResultText = "Job Scooper Results for ". getRunDateRange() . PHP_EOL . $strResultCountsText . PHP_EOL;
	    LogPlainText($strResultText);

        LogMessage("Generating html email content for user" . PHP_EOL);

        $messageHtml = $this->getListingCountsByPlugin("html", $arrMatchedJobs, $arrExcludedJobs, $detailsHTMLFile);

        $messageHtml = $this->addMailCssToHTML($messageHtml);
        $subject = "New Job Postings: " . getRunDateRange();

	    endLogSection("Email content ready to send.");

        //
        // Send the email notification out for the completed job
        //
        startLogSection("Sending email to user...");

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
                        LogMessage("Warning:  marked only " . $rowsAffected ." of " . count($arrToMarkNotified) ." UserJobMatch records as notified.");
                }
            }
	        endLogSection(" Email send completed...");

        } catch (Exception $ex)
        {
	        endLogSection(" Email send failed.");
            handleException($ex);
        }

        //
        // We only keep interim files around in debug mode, so
        // after we're done processing, delete the interim HTML file
        //
        if (isDebug() !== true) {
            foreach ($arrFilesToAttach as $filepath) {
                if (file_exists($filepath) && is_file($filepath)) {
                    LogMessage("Deleting local attachment file " . $filepath . PHP_EOL);
                    unlink($filepath);
                }
            }
        }


	    endLogSection(" User Results Notification.");

        return $ret;
    }


    public function writeRunsJobsToFile($strFileOut, $arrJobsToOutput)
    {
	    if(empty($strFileOut))
	    {
		    throw new ErrorException("Error: writeJobsListToFile called without an output file path to use.");
	    }

	    $fileDetails = parsePathDetailsFromString($strFileOut);

        if($fileDetails->getExtension() == "HTML")
            $keysToOutput = $this->getKeysForHTMLOutput();
        else
            $keysToOutput = $this->getKeysForUserCSVOutput();

        if(is_null($keysToOutput))
            $keysToOutput = array();

        if(count($arrJobsToOutput) == 0)
        {
            LogMessage("Warning: writeJobsListToFile had no records to write to  " . $strFileOut);

        }

        $arrRecordsToOutput = $this->_convertToJobsArrays($arrJobsToOutput);

        $classCombined = new SimpleCSV($strFileOut , "w");
        if (!is_array($arrRecordsToOutput))
        {
            $arrRecordsToOutput = array();
        }
        else
        {
            $this->sortJobsCSVArrayByCompanyRole($arrRecordsToOutput);
        }

        if (empty($keysToOutput))
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
            $keysToOutput = $this->getKeysForUserCSVOutput();
        }

        if(!empty($arrRecordsToOutput))
        {
            foreach($arrRecordsToOutput as $reckey => $rec)
            {
                $arrRecordsToOutput[$reckey] = array_intersect_key(
                    $rec,
                    array_flip($keysToOutput)
                );
            }
        }

        if($fileDetails->getExtension() == 'HTML')
        {
            $classCombined->writeArrayToHTMLFile($arrRecordsToOutput, $keysToOutput, null);

        }
        else
        {
            array_unshift($arrRecordsToOutput, $keysToOutput);
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->getActiveSheet()->fromArray($arrRecordsToOutput, null, 'A1');
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "CSV");
            $objWriter->save($strFileOut);

        }
        LogMessage("Jobs list had  ". count($arrRecordsToOutput) . " jobs and was written to " . $strFileOut );

        if($fileDetails->getExtension() == "HTML")
            $this->addMailCssToHTMLFile($strFileOut);
    }

    private function _filterAndWriteListToFile_($arrJobsList, $strFileNameBase, $strExt = "CSV")
    {
	    if(countAssociativeArrayValues($arrJobsList) == 0) return null;

	    $filePath = getDefaultJobsOutputFileName("", $strFileNameBase, $strExt, "_", 'notifications');


        $this->writeRunsJobsToFile($filePath, $arrJobsList);

        LogMessage("Wrote " . count($arrJobsList). " job listings output to  " . $filePath);

        return $filePath;

    }

    private function _getFullFileContents_($filePath)
    {
        $content = null;

        if(empty($filePath))
        {
            LogError("Unable to get file contents to include in email.  Failing notification.");
            return null;
        }

        # Open a file
        $file = fopen( $filePath, "r" );
        if( $file == false )
        {
            LogError("Unable to open file '". $filePath ."' for to get contents for notification mail.  Failing notification.");
            return null;
        }

        # Read the file into a variable
        $size = filesize($filePath);
        $content = fread( $file, $size);

        return $content;
    }


    private function _getJobSitesRunRecently()
    {
        if(is_null($this->_arrJobSitesForRun)) {
            $this->_arrJobSitesForRun = JobSitePluginBuilder::getIncludedJobSites();

            $sites = array_map(function ($var) {
                return $var->getJobPostingFromUJM()->getJobSiteKey();
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
        $arrHeaders = array("New Matches to Review", "Matches Auto-Excluded", "Total Jobs");

        //
        // if the plugin also errored, then add an asterisk to the name
        // for reference in the email
        //
        $arrFailedJobsites = \JobScooper\DataAccess\UserSearchSiteRunQuery::create()
            ->select("JobSiteKey")
            ->filterByRunResultCode("failed")
            ->find()
            ->getData();


        //
        // if the plugin also errored, then add an asterisk to the name
        // for reference in the email
        //
        $arrJobsitesRecentlyUpdated = \JobScooper\DataAccess\UserSearchSiteRunQuery::create()
            ->select("JobSiteKey")
            ->filterByStartedAt(date_sub(new \DateTime(), date_interval_create_from_date_string('40hours')))
            ->find()
            ->getData();

        foreach($arrJobsitesRecentlyUpdated as $plugin) {
            $jobsiteKey = $plugin->getJobSiteKey();
            $arrPluginJobMatches  = array();
            if ($arrMatchedJobs != null && is_array($arrMatchedJobs) && countAssociativeArrayValues($arrMatchedJobs) > 0) {
                $arrPluginJobMatches = array_filter($arrMatchedJobs, function ($var) use ($jobsiteKey) { return (strcasecmp($var->getJobPostingFromUJM()->getJobSiteKey(), $jobsiteKey) == 0); } );
            }

            $arrPluginExcludesJobs  = array();
            if ($arrExcludedJobs != null && is_array($arrExcludedJobs) && countAssociativeArrayValues($arrExcludedJobs) > 0) {
                $arrPluginExcludesJobs = array_filter($arrExcludedJobs, function ($var) use ($jobsiteKey) { return (strcasecmp($var->getJobPostingFromUJM()->getJobSiteKey(), $jobsiteKey) == 0); } );
            }

            $arrPluginJobs = $arrPluginJobMatches + $arrPluginExcludesJobs;

            $arrCounts[$jobsiteKey]['had_error'] = in_array($jobsiteKey, $arrFailedJobsites);
            $arrCounts[$jobsiteKey]['name'] = $plugin->getDisplayName();
            if($arrCounts[$jobsiteKey]['had_error'] === true)
                $arrCounts[$jobsiteKey]['name'] .= "**";
            $arrCounts[$jobsiteKey]['matches_to_review'] = count(array_filter($arrPluginJobs, "isUserJobMatchAndNotExcluded"));
            $arrCounts[$jobsiteKey]['matches_excluded'] = count(array_filter($arrPluginJobs, "isUserJobMatchButExcluded"));
            $arrCounts[$jobsiteKey]['total_listings'] = count($arrPluginJobs);
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

        return $strOut;
    }


    private function _getResultsTextHTML_($arrHeaders, $arrCounts, $detailsHTMLBodyInclude = null)
    {
        $arrCounts_TotalAll = null;
        $arrCounts_TotalUser = null;
        $strOut = "<div class='job_scooper outer'>";

        $strOut  .= "<H1>Job Postings to Review for " . getRunDateRange() . "</H1>".PHP_EOL;

        //
        // Include the contents of the HTML file if passed
        //
        if(!is_null($detailsHTMLBodyInclude) && is_file($detailsHTMLBodyInclude)) {
            $strOut .= PHP_EOL . "<div class=\"job_scooper section\">" . PHP_EOL;
            $strOut .= $this->_getFullFileContents_($detailsHTMLBodyInclude);
            $strOut .= PHP_EOL . PHP_EOL;
            $strOut .= "</div>";
            $strOut .= "<br>" . PHP_EOL . "<br>" . PHP_EOL;
        }
        else
        {
            $strOut .= PHP_EOL . "<hr width='100%'><br><div class=\"job_scooper section\">" . PHP_EOL;
            $strOut  .= "No new jobs were found that matched your search terms.". PHP_EOL. PHP_EOL;
            $strOut .= PHP_EOL . PHP_EOL;
            $strOut .= "</div>";
            $strOut .= "<br>" . PHP_EOL . "<br>" . PHP_EOL;
        }

        if($arrCounts != null && count($arrCounts) > 0)
        {
            $strOut  .= "<hr width='100%'><H2>Search Results by Job Site</H2>".PHP_EOL. PHP_EOL;
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

        $strOut  .= "<br><hr width='100%'>";
        $strOut  .= "<div style=\"width: 100%; margin:  5px; text-align: center;\"><span style=\"font-size: xx-small; color: #49332D;\">Generated by " . gethostname() . " running " . __APP_VERSION__. " on " . getTodayAsString() . ".</span></div>" . PHP_EOL;
        $strOut  .= "<hr width='100%'><br>";

        return $strOut;
    }

    private function getKeysForHTMLOutput()
    {
        return array(
            'Company',
            'JobTitleLinked',
            'LocationDisplayValue'
        );
    }

    private function getKeysForUserCSVOutput()
    {
        $match = new \JobScooper\DataAccess\UserJobMatch();
        $allKeys = array_keys($match->toFlatArrayForCSV());


        $retKeys = array_diff($allKeys, array('AppRunId', 'UserJobMatchId', 'UserNotificationState', 'TitleTokens', 'JobTitleLinked', "FirstSeenAt", "RemovedAt", "UpdatedAt", "KeySiteAndPostId", "KeyCompanyAndTitle", "AlternateNames", "Location"));

//        if(isDebug()) {
//            $retKeys = $allKeys;
//        }
        return array_unique($retKeys);
    }

    private function _convertToJobsArrays($arrJobObjects)
    {
        $arrRet = array();
        foreach($arrJobObjects as $jobMatch)
        {
            $item = $jobMatch->toFlatArrayForCSV();
            $arrRet[$item['KeySiteAndPostId']] = $item;
        }

        return $arrRet;

    }



    function sortJobsCSVArrayByCompanyRole(&$arrJobList)
    {

        if (countAssociativeArrayValues($arrJobList) > 0) {
            $arrFinalJobIDs_SortedByCompanyRole = array();
            $finalJobIDs_CompanyRole = array_column($arrJobList, 'KeyCompanyAndTitle', 'KeySiteAndPostId');
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