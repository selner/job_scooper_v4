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
require_once dirname(__FILE__) . '/../include/scooter_utils_common.php';

function getDefaultJobsOutputFileName($strFilePrefix = '', $strBase = '', $strExt = '')
{
    $strFilename = '';
    if(strlen($strFilePrefix) > 0) $strFilename .= $strFilePrefix . "_";
    $date=date_create(null);
    $strFilename .= date_format($date,"Y-m-d_Hi");
//    $strFilename .= date("Y-m-d_h-m");
    if(strlen($strBase) > 0) $strFilename .= "_" . $strBase . "_";
    if(strlen($strExt) > 0) $strFilename .= "." . $strExt;

    return $strFilename;
}



abstract class ClassSiteExportBase
{

//    function call_child_method(){
//        if(method_exists($this, 'child_method')){
//            $this->child_method();
//        }
//    }

    private $_strFilePathInput_ExcludedTitles = null;

    abstract function getJobs($strAlternateLocalHTMLFile = null);


    protected $siteName = 'NAME-NOT-SET';
    private $_strAlternateLocalFile = '';
    private $_bitFlags = null;
    protected $strOutputFolder = "";
    protected $arrLatestJobs = "";
    protected $arrTitlesToFilter = null;

    function setOutputFolder($strPath)
    {
        $this->strOutputFolder = $strPath;
    }

    function __construct($strAltFilePath = null, $bitFlags = null)
    {
        $this->_strAlternateLocalFile = $strAltFilePath;
        $this->_bitFlags = $bitFlags;
        $this->_strFilePathInput_ExcludedTitles = C_STR_DATAFOLDER  . "bryans_list_exclude_titles.csv";
    }

    function __destruct()
    {
        __debug__printLine("Done with ".$this->siteName." processing.", C__DISPLAY_ITEM_START__);
    }

    function _loadTitlesToFilter_()
    {
        if(!is_array($this->arrTitlesToFilter) && $this->_strFilePathInput_ExcludedTitles && file_exists($this->_strFilePathInput_ExcludedTitles) && is_file($this->_strFilePathInput_ExcludedTitles))
        {
            __debug__printLine("Loading job titles to filter from ".$this->_strFilePathInput_ExcludedTitles."." , C__DISPLAY_ITEM_DETAIL__);
            $classCSVFile = new SimpleScooterCSVFileClass($this->_strFilePathInput_ExcludedTitles, 'r');
            $arrTitlesTemp = $classCSVFile->readAllRecords(true);
            __debug__printLine(count($arrTitlesTemp) . " titles found in the source file that will be automatically filtered from job listings." , C__DISPLAY_ITEM_DETAIL__);

            //
            // Add each title we found in the file to our list in this class, setting the key for
            // each record to be equal to the job title so we can do a fast lookup later
            //
            $this->arrTitlesToFilter = array();
            foreach($arrTitlesTemp as $titleRecord)
            {
                $this->arrTitlesToFilter[$titleRecord['job_title']] = $titleRecord;
            }

        }
        else
        {
            __debug__printLine("Could not load the list of titles to exclude from '" . $this->_strFilePathInput_ExcludedTitles . "'.  Final list will not be filtered." , C__DISPLAY_MOMENTARY_INTERUPPT__);
        }
    }

    function filterExcludedTitles($arrJobsToFilter)
    {
        $this->_loadTitlesToFilter_();
        if(!is_array($this->arrTitlesToFilter))
        {
            __debug__printLine("No titles found to exclude. Skipping results filtering." , C__DISPLAY_MOMENTARY_INTERUPPT__);
            return $arrJobsToFilter;
        }

        $nJobsNotExcluded = 0;
        $nJobsExcluded = 0;

        $nIndex = 0;
        foreach($arrJobsToFilter as $job)
        {
            $varValMatch = $this->arrTitlesToFilter[$job['job_title']];

 //           __debug__printLine("Matching listing job title '".$job['job_title'] ."' and found " . (!$varValMatch  ? "nothing" : $varValMatch ) . " for " . $this->arrTitlesToFilter[$job['job_title']], C__DISPLAY_ITEM_DETAIL__);
            if(!is_array($varValMatch))  // we're ignoring the Excluded column fact for the time being. If it's in the list, it's excluded
            {
                $nJobsNotExcluded++;
                __debug__printLine("Job title '".$job['job_title'] ."' was not found in the exclusion list.  Keeping for review." , C__DISPLAY_ITEM_DETAIL__);
            }
            else
            {
                $arrJobsToFilter[$nIndex]['interested'] = "No (Auto Excluded)";
                $nJobsExcluded++;
            }
            $nIndex++;
        }


        __debug__printLine("Filtering complete:  '".$nJobsExcluded ."' were automatically filtered and ". $nJobsNotExcluded . " jobs were not." , C__DISPLAY_ITEM_RESULT__);



        return $arrJobsToFilter;
    }


    function downloadAllUpdatedJobs($nDays = -1, $varPathToInputFilesToMergeWithResults = null )
    {
        $retFilePath = '';

        // Now go download and output the latest jobs from this site
        __debug__printLine("Downloading new ". $this->siteName ." jobs...", C__DISPLAY_ITEM_START__);

        //
        // Call the child classes getJobs function to update the object's array of job listings
        // and output the results to a single CSV
        //
        $retCSVFilePathJobsWrittenTo = $this->getJobs($nDays);

        //
        // Now, filter those jobs and mark any rows that are titles we want to automatically
        // exclude
        //
        $this->arrLatestJobs = $this->filterExcludedTitles($this->arrLatestJobs);

        //
        // Write the resulting array of the latest job postings from this site to
        // a CSV file for the user
        //
        $this->writeJobsToCSV($retCSVFilePathJobsWrittenTo, $this->arrLatestJobs);

        __debug__printLine("Downloaded ". count($this->arrLatestJobs) ." new ". $this->siteName ." jobs to " . $retCSVFilePathJobsWrittenTo, C__DISPLAY_ITEM_DETAIL__);

        //
        // Lastly, if had we input files of job listings from the user,
        // we need to merge them with the latest jobs.
        //
        if($varPathToInputFilesToMergeWithResults != null)
        {
            $arrFilePathsToCombine = array($retCSVFilePathJobsWrittenTo);

            if(is_string($varPathToInputFilesToMergeWithResults) && strlen($varPathToInputFilesToMergeWithResults) > 0)
            {
                $arrFilePathsToCombine[] = $varPathToInputFilesToMergeWithResults;
            }
            else if(is_array($varPathToInputFilesToMergeWithResults))
            {
                foreach($varPathToInputFilesToMergeWithResults as $inputfile)
                {
                    $arrFilePathsToCombine[] = $inputfile;
                }
            }

            //
            // Make sure we didn't have bad input file names and only ended up with our results file
            // from this class as the only file to combine
            //
            if(count($arrFilePathsToCombine) > 1)
            {
                $strOutFilePath = $this->getOutputFileFullPath('Final_CombinedWithUserInput');
                __debug__printLine("Merging input & downloaded jobs data and writing to " . $strOutFilePath , C__DISPLAY_ITEM_START__);
                __debug__printLine("Including source data from " .var_export($arrFilePathsToCombine, true), C__DISPLAY_ITEM_DETAIL__);

                $classCombined = new SimpleScooterCSVFileClass($strOutFilePath , "w");
                $arrRetJobs = $classCombined->combineMultipleCSVs($arrFilePathsToCombine);
                $retCSVFilePathJobsWrittenTo = $strOutFilePath;
                __debug__printLine("Combined file has ". count($arrRetJobs) . " jobs." . $strOutFilePath , C__DISPLAY_ITEM_START__);
            }
        }


        //
        // return the output file path to the caller so they know where to find them
        //
        return $retCSVFilePathJobsWrittenTo;

    }




    function getEmptyItemsArray()
    {
        return array(
            'job_site' => '',
            'job_id' => '',
            'company' => '',
            'job_title' => '',
            'interested' => '',
            'notes' => '',
            'date_pulled' => '',
            'job_post_url' => '',
            'brief_description' => '',
            // 'full_description' => '',
            'location' => '',
            'job_site_category' => '',
            'job_site_date' =>'',
            'original_source' => '',
            'job_source_url' => '',
            'script_search_key' => '',
        );
    }

    function getSimpleObjFromPathOrURL($filePath = "", $strURL = "")
    {
        __debug__printLine("getSimpleObjFromPathOrURL(".$filePath.', '.$strURL.")", C__DISPLAY_ITEM_START__);
        $objSimpleHTML = null;

        if(!$objSimpleHTML && ($filePath && strlen($filePath) > 0))
        {
            __debug__printLine("Loading ALTERNATE results from ".$filePath, C__DISPLAY_ITEM_START__);
            $objSimpleHTML =  $this->getSimpleHTMLObjForFileContents($filePath);
        }

        if(!$objSimpleHTML && $this->_strExtendsAlternateLocalFile && strlen($this->_strExtendsAlternateLocalFile) > 0)
        {
            __debug__printLine("Loading ALTERNATE results from ".$this->_strExtendsAlternateLocalFile, C__DISPLAY_ITEM_START__);
            $objSimpleHTML =  $this->getSimpleHTMLObjForFileContents($this->_strExtendsAlternateLocalFile);
        }

        if(!$objSimpleHTML && $strURL && strlen($strURL) > 0)
        {
            __debug__printLine("Loading results from ".$strURL, C__DISPLAY_ITEM_START__);
            $objSimpleHTML = file_get_html($strURL);
        }

        if(!$objSimpleHTML)
        {
            throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$filePath.') or '.$strURL);
        }

        return $objSimpleHTML;
    }

    function writeJobsToCSV($outputFileFullPath, $arrJobsToOutput)
    {
        $classFileOut = new SimpleScooterCSVFileClass($outputFileFullPath, "w");
        __debug__printLine("Writing results to ".$outputFileFullPath, C__DISPLAY_ITEM_START__);
        $classFileOut->writeArrayToCSVFile($arrJobsToOutput);
    }

    function getActualPostURL($strSrcURL)
    {
        $retURL = null;

        $classAPI = new APICallWrapperClass();
        __debug__printLine("Getting source URL for ".$strSrcURL, C__DISPLAY_ITEM_START__);
        try
        {
            $curlObj = $classAPI->cURL($strSrcURL);
            if($curlObj && !$curl_object['error_number'] && $curl_object['error_number'] == 0 )
            {
                $retURL  =  $curlObj['actual_site_url'];
            }
        }
        catch(ErrorException $err)
        {
                // do nothing
        }
        return $retURL;
    }

    function getPostDateString()
    {
        return date("Y-m-d");
    }

    function is_IncludeBrief()
    {
        $val = $this->_bitFlags & C_EXCLUDE_BRIEF;
        $notVal = !($this->_bitFlags & C_EXCLUDE_BRIEF);
        // __debug__printLine('ExcludeBrief/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);
        return true;
    }

    function is_IncludeActualURL()
    {
        $val = $this->_bitFlags & C_EXCLUDE_GETTING_ACTUAL_URL;
        $notVal = !($this->_bitFlags & C_EXCLUDE_GETTING_ACTUAL_URL);
        // __debug__printLine('ExcludeActualURL/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);

        return !$notVal;
    }

    private function _getOutputFileName_($strFilePrefix = '', $strBase = '', $strExt = '')
    {
        $strFilename = $this->siteName;
        if(strlen($strFilePrefix) > 0) $strFilename .= $strFilePrefix . "_";
        $strFilename .= date("Ymd-Hms");
        if(strlen($strBase) > 0) $strFilename .= "_" . $strBase . "_";
        if(strlen($strExt) > 0) $strFilename .= "." . $strExt;

        return $strFilename;
    }

    function getOutputFileFullPath($strFilePrefix = "", $strBase = 'jobs', $strExtension = 'csv')
    {
        $strFullPath =  getDefaultJobsOutputFileName($this->siteName . "_".$strFilePrefix, $strBase , $strExtension);

        if(strlen($this->strOutputFolder) > 0)
        {
            $strFullPath = $this->strOutputFolder . "/" . $strFullPath;
        }
        return $strFullPath;
    }

    function getSimpleHTMLObjForFileContents($strInputFileFullPath)
    {
        $objSimpleHTML = null;
        __debug__printLine("Loading HTML from ".$strInputFileFullPath, C__DISPLAY_ITEM_START__);

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