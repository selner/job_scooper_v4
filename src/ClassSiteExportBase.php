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


    abstract function getJobs($strAlternateLocalHTMLFile = null);


    protected $siteName = 'NAME-NOT-SET';
    private $_strAlternateLocalFile = '';
    private $_bitFlags = null;
    protected $strOutputFolder = "";
    protected $arrLatestJobs = "";

    function setOutputFolder($strPath)
    {
        $this->strOutputFolder = $strPath;
    }

    function __construct($strAltFilePath = null, $bitFlags = null)
    {
        $this->_strAlternateLocalFile = $strAltFilePath;
        $this->_bitFlags = $bitFlags;
    }

    function __destruct()
    {
        __debug__printLine("Done with ".$this->siteName." processing.", C__DISPLAY_ITEM_START__);
    }


    function downloadAllUpdatedJobs($nDays = -1 )
    {
        $retFilePath = '';

        // Now go download and output the latest jobs from this site
        __debug__printLine("Downloading new ". $this->siteName ." jobs...", C__DISPLAY_ITEM_START__);
        $strJobsDownloadPath = $this->getJobs($nDays);
        $retFilePath = $strJobsDownloadPath;

        __debug__printLine("Downloaded ". count($this->arrLatestJobs) ." new ". $this->siteName ." jobs to " . $strJobsDownloadPath , C__DISPLAY_ITEM_START__);


        // If we had a source file, then we need to merge it
        if($strSourceFilePath != null && strlen($strSourceFilePath) > 0)
        {
            __debug__printLine("Including source data from " .$strSourceFilePath, C__DISPLAY_ITEM_START__);

            $arrFilesToCombine = array($strSourceFilePath, $strJobsDownloadPath);
            $strOutFilePath = $this->getOutputFileFullPath('FinalCombined');

            __debug__printLine("Merging input & downloaded jobs data and writing to " . $strOutFilePath , C__DISPLAY_ITEM_START__);

            $classCombined = new SimpleScooterCSVFileClass($strOutFilePath , "w");
            $arrRetJobs = $classCombined->combineMultipleCSVs($arrFilesToCombine);
            $retFilePath = $strOutFilePath;
            __debug__printLine("Combined file has ". count($arrRetJobs) . " jobs." . $strOutFilePath , C__DISPLAY_ITEM_START__);
        }


        //
        // return the output file path to the caller so they know where to find them
        //
        return $retFilePath;

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