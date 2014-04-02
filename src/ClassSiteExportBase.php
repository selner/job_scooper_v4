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



class ClassSiteExportBase
{
    function getEmptyItemsArray()
    {
        return array(
            'job_site' => '',
            'job_id' => '',
            'job_title' => '',
            'company' => '',
            'notes' => '',
            'interested' => '',
            'location' => '',
            'job_post_url' => '',
            'date_pulled' => '',
            'brief_description' => '',
            'full_description' => '',
            'job_site_category' => '',
            'job_site_date' =>'',
            'original_source' => '',
            'job_source_url' => '',
            'script_search_key' => '',
        );
    }

    private $_extendClassSiteName_ = '';
    private $_strExtendsAlternateLocalFile = '';
    private $_bitFlags = null;


    function __construct($name, $strAltFilePath = null, $bitFlags = null)
    {
        $this->_strExtendsAlternateLocalFile = $strAltFilePath;
        $this->_extendClassSiteName_ = $name;
        $this->_bitFlags = $bitFlags;
        __debug__printLine("Starting ".$name." processing...", C__DISPLAY_ITEM_START__);
    }

    function __destruct()
    {
        __debug__printLine("Done with ".$this->_extendClassSiteName_." processing.", C__DISPLAY_ITEM_START__);
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
        $notVal = $this->_bitFlags & C_EXCLUDE_BRIEF;
        // __debug__printLine('ExcludeBrief/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);
        return false;
    }

    function is_IncludeActualURL()
    {
        $val = $this->_bitFlags & C_EXCLUDE_GETTING_ACTUAL_URL;
        $notVal = $this->_bitFlags & C_EXCLUDE_GETTING_ACTUAL_URL;
        // __debug__printLine('ExcludeActualURL/not = ' . $val .', '. $notVal, C__DISPLAY_ITEM_START__);

        return false;
    }

    function getOutputFileName($strFilePrefix = '', $strBase = '', $strExt = '')
    {
        $strFilename = '';
        if(strlen($strFilePrefix) > 0) $strFilename .= $strFilePrefix . "_";
        $strFilename .= date("Ymd-Hms");
        if(strlen($strBase) > 0) $strFilename .= "_" . $strBase . "_";
        if(strlen($strExt) > 0) $strFilename .= "." . $strExt;

        return $strFilename;
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