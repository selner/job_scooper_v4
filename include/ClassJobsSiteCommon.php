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

define('TITLE_NEG_KWD_MATCH', 'No (Title Excluded Via Negative Keyword)');
define('NO_TITLE_MATCHES', 'No (Title Did Not Match Search Keywords))');

define('JOBS_SCOOPER_MAX_FILE_SIZE', 1024000);

define('REXPR_PARTIAL_MATCH_URL_DOMAIN', '^https*.{3}[^\/]*');
define('REXPR_MATCH_URL_DOMAIN', '/^https*.{3}[^\/]*/');
class ClassJobsSiteCommon
{

    private $arrKeysForDeduping = array('key_jobsite_siteid');

    protected $detailsMyFileOut= "";
    protected $arrSearchesToReturn = null;
    protected $siteBaseURL = null;

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
            'job_post_url' => '',
            'match_details' => '',
            'match_notes' => '',
            'status' => '',
            'last_status_update' => '',
            'date_pulled' => '',
            'location' => '',
            'job_site_category' => '',
            'job_site_date' =>'',
            'date_last_updated' => '',
            'key_jobsite_siteid' => '',
            'key_company_role' => '',
            'job_title_tokenized' => '',
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

        if(!is_null($retArrNormalized['job_post_url']) || strlen($retArrNormalized['job_post_url']) > 0)
        {
            $arrMatches = array();
            $matchedHTTP = preg_match(REXPR_MATCH_URL_DOMAIN, $retArrNormalized['job_post_url'], $arrMatches);
            if(!$matchedHTTP)
                $retArrNormalized['job_post_url'] = $this->siteBaseURL . $retArrNormalized['job_post_url'];
        }
        else
        {
            $retArrNormalized['job_post_url'] = "unknown";
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









    function writeJobsListToFile($strOutFilePath, $arrJobsRecordsToUse, $fIncludeFilteredJobsInResults = true, $fFirstAutoMarkJobs = false, $loggedFileType = null, $ext = "CSV", $keysToOutput=null, $detailsCSSToInclude = null)
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
            $arrRecordsToOutput = array_unique_multidimensional($arrJobsRecordsToUse);
            sort($arrRecordsToOutput);
            if (!is_array($arrRecordsToOutput)) $arrRecordsToOutput = array();
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
                $this->writeJobsListToFile($strOutFilePath, $arrRetJobs, $fIncludeFilteredJobsInResults, false, "writeMergedJobsCSVFile");
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

            $this->writeJobsListToFile($strOutFilePath, $arrRetJobs, $fIncludeFilteredJobsInResults, false, "writeMergedJobsCSVFile2");
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
