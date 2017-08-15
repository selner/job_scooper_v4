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

define('TITLE_NEG_KWD_MATCH', 'No (Title Excluded Via Negative Keyword)');
define('NO_TITLE_MATCHES', 'No (Title Did Not Match Search Keywords))');

define('JOBS_SCOOPER_MAX_FILE_SIZE', 1024000);

define('REXPR_PARTIAL_MATCH_URL_DOMAIN', '^https*.{3}[^\/]*');
define('REXPR_MATCH_URL_DOMAIN', '/^https*.{3}[^\/]*/');

class ClassJobsSiteCommon
{

    private $arrKeysForDeduping = array('key_jobsite_siteid');

    protected $detailsMyFileOut= "";
    protected $regex_link_job_id = null;
    protected $prevCookies = "";
    protected $prevURL = null;


    function __construct($strOutputDirectory = null)
    {
        if($strOutputDirectory != null)
        {
            $this->detailsMyFileOut = \Scooper\parseFilePath($strOutputDirectory, false);
        }

    }


    function getEmptyJobListingRecord()
    {
        return array(
            'normalized' => false,
            'job_site' => '',
            'job_id' => '',
            'company' => '',
            'job_title' => '',
            'interested' => '',
            'job_post_url' => '',
            'location' => '',
//            'status' => '',
            'job_site_category' => '',
            'job_site_date' =>'',
            'employment_type' => '',
            'match_notes' => '',
            'date_pulled' => '',
            'date_last_updated' => '',
            'key_jobsite_siteid' => '',
            'key_company_role' => '',
            'job_title_tokenized' => '',
         );
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
        if(isDebug() == true) {

            $usage = getPhpMemoryUsage();

            if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("~~~~ PHP memory usage is ".$usage." ~~~~", \Scooper\C__DISPLAY_NORMAL__); }
        }
    }






    function writeJobsListToFile($strOutFilePath, $arrJobsRecordsToUse, $fIncludeFilteredJobsInResults = true, $loggedFileType = null, $ext = "CSV", $keysToOutput=null, $detailsCSSToInclude = null)
    {
        if(is_null($keysToOutput))
            $keysToOutput = array();

        if(!$strOutFilePath || strlen($strOutFilePath) <= 0)
        {
            throw new ErrorException("Error: writeJobsListToFile called without an output file path to use.");
        }

        if(count($arrJobsRecordsToUse) == 0)
        {
            $GLOBALS['logger']->logLine("Warning: writeJobsListToFile had no records to write to  " . $strOutFilePath, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        }

        if($fIncludeFilteredJobsInResults == false)
        {
            $arrJobsRecordsToUse = array_filter($arrJobsRecordsToUse, "includeJobInFilteredList");
//            $arrJobsRecordsToUse = $this->filterOutUninterestedJobs($arrJobsRecordsToUse, $fIncludeFilteredJobsInResults);

        }


        $classCombined = new \Scooper\ScooperSimpleCSV($strOutFilePath , "w");

        $arrRecordsToOutput = array_unique_multidimensional($arrJobsRecordsToUse);
        if (!is_array($arrRecordsToOutput))
        {
            $arrRecordsToOutput = array();
        }
        else
        {
            sortJobsListByCompanyRole($arrRecordsToOutput);
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
            $keysToOutput = $this->getEmptyJobListingRecord();
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
                $arrRecordsToOutput[$reckey] = \Scooper\array_copy($out);
            }
        }

        if($ext == 'HTML')
        {
            $strCSS = null;
            if($detailsCSSToInclude['has_file'])
            {
                // $strCSS = file_get_contents(dirname(__FILE__) . '/../include/CSVTableStyle.css');
                $strCSS = file_get_contents($detailsCSSToInclude['full_file_path']);
            }
            $classCombined->writeArrayToHTMLFile($arrRecordsToOutput, $keysToOutput, $this->arrKeysForDeduping, $strCSS);

        }
        else
        {
            array_unshift($arrRecordsToOutput, $keysToOutput);
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->getActiveSheet()->fromArray($arrRecordsToOutput, null, 'A1');
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "CSV");

//            $spreadsheet->removeSheetByIndex(0);
            $objWriter->save($strOutFilePath);

//            $classCombined->writeArrayToCSVFile($arrJobsRecordsToUse, $keysToOutput, $this->arrKeysForDeduping);
        }
        $GLOBALS['logger']->logLine($loggedFileType . ($loggedFileType  != "" ? " jobs" : "Jobs") ." list had  ". count($arrJobsRecordsToUse) . " jobs and was written to " . $strOutFilePath , \Scooper\C__DISPLAY_ITEM_START__);

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
            $arrCurFileJobs = loadCSV($strFilePath, 'key_jobsite_siteid');
//            $classCombinedRead = new \Scooper\ScooperSimpleCSV($strFilePath , "r");
//            $arrCurFileJobs = $classCombinedRead->readAllRecords(true, array_keys($this->getEmptyJobListingRecord()));
//            $arrCurFileJobs = $arrCurFileJobs['data_rows'];
//            $classCombinedRead = null;
            if($arrCurFileJobs != null)
            {
                $this->saveJobList($arrCurFileJobs);

                addJobsToJobsList($arrRetJobsList, $arrCurFileJobs);
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
                $this->writeJobsListToFile($strOutFilePath, $arrRetJobs, $fIncludeFilteredJobsInResults, "writeMergedJobsCSVFile");
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

            $this->writeJobsListToFile($strOutFilePath, $arrRetJobs, $fIncludeFilteredJobsInResults, "writeMergedJobsCSVFile2");
            $GLOBALS['logger']->logLine("Combined file has ". count($arrRetJobs) . " jobs and was written to " . $strOutFilePath , \Scooper\C__DISPLAY_ITEM_START__);

        }
        return $strOutFilePath;

    }



    function getSimpleObjFromPathOrURL($filePath = "", $strURL = "", $optTimeout = null, $referrer = null, $cookies = null)
    {
        $objSimpleHTML = null;

        if(isDebug()==true) {

            $GLOBALS['logger']->logLine("URL        = " . $strURL, \Scooper\C__DISPLAY_NORMAL__);
            $GLOBALS['logger']->logLine("Referrer   = " . $referrer, \Scooper\C__DISPLAY_NORMAL__);
            $GLOBALS['logger']->logLine("Cookies    = " . $cookies, \Scooper\C__DISPLAY_NORMAL__);
        }

        if(!$objSimpleHTML && ($filePath && strlen($filePath) > 0))
        {
            $GLOBALS['logger']->logLine("Loading ALTERNATE results from ".$filePath, \Scooper\C__DISPLAY_ITEM_START__);
            $objSimpleHTML =  $this->getSimpleHTMLObjForFileContents($filePath);
        }


        if(!$objSimpleHTML && $strURL && strlen($strURL) > 0)
        {
            $class = new \Scooper\ScooperDataAPIWrapper();
            if(isVerbose()) $class->setVerbose(true);

            $retObj = $class->cURL($strURL, $json = null, $action = 'GET', $content_type = null, $pagenum = null, $onbehalf = null, $fileUpload = null, $secsTimeout = $optTimeout, $cookies = $cookies, $referrer = $referrer);
            if(!is_null($retObj) && array_key_exists("output", $retObj) && strlen($retObj['output']) > 0)
            {
                $objSimpleHTML = SimpleHtmlDom\str_get_html($retObj['output']);
                $this->prevCookies = $retObj['cookies'];
                $this->prevURL = $strURL;
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

