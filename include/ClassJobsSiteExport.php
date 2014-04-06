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

    if(strlen($strBase) > 0) $strFilename .= "_" . $strBase . "_";
    if(strlen($strExt) > 0) $strFilename .= "." . $strExt;

    return $strFilename;
}

class ClassJobsSiteExport
{

    private $_strFilePathInput_ExcludedTitles = null;

    private $arrKeysForDeduping = array('job_site', 'job_id');

    private $_strAlternateLocalFile = '';
    private $_bitFlags = null;
    protected $strOutputFolder = "";
    protected $arrTitlesToFilter = null;

    function getEmptyItemsArray()
    {
        return array(
            'job_site' => '',
            'job_id' => '',
            'company' => '',
            'job_title' => '',
            'interested' => '',
            'notes' => '',
            'status' => '',
            'last_status_update' => '',
            'date_pulled' => '',
            'job_post_url' => '',
            'brief_description' => '',
            'location' => '',
            'job_site_category' => '',
            'job_site_date' =>'',
//            'original_source' => '',
//            'job_source_url' => '',
//            'script_search_key' => '',
        );
    }

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

    function _getCurrentDateAsString_()
    {
        return date("Y-m-d");
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



    function filterNotInterestedJobs($arrJobsToFilter, $fIncludeFilteredJobsInResults = true)
    {
        if($fIncludeFilteredJobsInResults == true)
        {
            __debug__printLine("Not filtering results." , C__DISPLAY_MOMENTARY_INTERUPPT__);
            return $arrJobsToFilter;
        }
        else
        {
            __debug__printLine("Applying filters to " . count($arrJobsToFilter). " jobs.", C__DISPLAY_ITEM_DETAIL__);

        }

        $arrInterestLevelsToExclude = array(
        'No' => array('interested' => 'No', 'exclude' => true),
        'No (Bad Title & Role)]' => array('interested' => 'No (Bad Title & Role)', 'exclude' => true),
        'No (Bad Role, not Title)' => array('interested' => 'No (Bad Role, not Title)', 'exclude' => true),
        'No (Bad Title & Role)[auto-filtered]' => array('interested' => 'No (Bad Title & Role)[auto-filtered]', 'exclude' => true),
        'No (Bad Role, not Title)' => array('interested' => 'No (Bad Role, not Title)[auto-filtered]', 'exclude' => true),
    );


        $nJobsNotExcluded = 0;
        $nJobsExcluded = 0;
        $retArrayFilteredJobs = array();

        foreach($arrJobsToFilter as $job)
        {
//            __debug__printLine("Checking filter for '".$job['job_title'] ."' interest level of '".$job['interested'] ."'." , C__DISPLAY_ITEM_DETAIL__);

            if(strlen($job['interested']) <= 0)
            {
                // Interested value not set; always include in the results
                $retArrayFilteredJobs[] = $job;
                $nJobsNotExcluded++;

            }
            else
            {
                $strIntFirstPart = substr($job['interested'], 0, 2);

                if(strcasecmp($strIntFirstPart, 'No') == 0)
                {
                        $nJobsExcluded++;
                }
                else
                {
                    $retArrayFilteredJobs[] = $job;
                    $nJobsNotExcluded++;
                }

/*
 *
                 $arrCurJobInterest = $arrInterestLevelsToExclude[$job['interested']];


                if(!is_array($arrCurJobInterest))
                {
                    $retArrayFilteredJobs[] = $job;
                    $nJobsNotExcluded++;
                    // __debug__printLine("Job interest level of '".$job['interested'] ."' was not found in the exclusion list.  Keeping for review." , C__DISPLAY_ITEM_DETAIL__);
                }
                else
                {
//                    __debug__printLine("Filtering job '".$job['job_title'] ."' because interested = '".$job['interested'] ."'." , C__DISPLAY_ITEM_DETAIL__);
                    $nJobsExcluded++;
                }

*/
            }
        }

        __debug__printLine("Filtering complete:  ".$nJobsExcluded ." filtered; ". $nJobsNotExcluded . " not filtered; " . count($arrJobsToFilter) . " total records." , C__DISPLAY_ITEM_RESULT__);

        return $retArrayFilteredJobs;
    }


    function writeJobsListToFile($strOutFilePath, $arrJobsRecordsToUse, $fIncludeFilteredJobsInResults = true)
    {
        if(!$strOutFilePath || strlen($strOutFilePath) <= 0)
        {
            throw new ErrorException("Error: writeJobsListToFile called without an output file path to use.");
        }
        if(count($arrJobsRecordsToUse) == 0)
        {
            __debug__printLine("Warning: writeJobsListToFile had no records to write to  " . $strOutFilePath, C__DISPLAY_ITEM_DETAIL__);


        }

        if($fIncludeFilteredJobsInResults == false)
        {
            $arrJobsRecordsToUse = $this->filterNotInterestedJobs($arrJobsRecordsToUse, $fIncludeFilteredJobsInResults);

        }


        $classCombined = new SimpleScooterCSVFileClass($strOutFilePath , "w");
        $classCombined->writeArrayToCSVFile($arrJobsRecordsToUse, array_keys($this->getEmptyItemsArray()), $this->arrKeysForDeduping);
        __debug__printLine("Jobs list had  ". count($arrJobsRecordsToUse) . " jobs and was written to " . $strOutFilePath , C__DISPLAY_ITEM_START__);

        return $strOutFilePath;

    }

    function writeMergedJobsCSVFile($strOutFilePath, $arrFilesToCombine, $arrMyRecordsToInclude = null, $fIncludeFilteredJobsInResults = true)
    {
        $arrRetJobs = array();
        if(!$strOutFilePath || strlen($strOutFilePath) <= 0)
        {
            $strOutFilePath = $this->getOutputFileFullPath('writeMergedJobsCSVFile_');
        }

        if(!is_array($arrFilesToCombine) || strlen($arrFilesToCombine[0]) <= 0)
        {
            throw new ErrorException("Error: writeMergedJobsCSVFile called with an empty array of filenames to combine. ");
        }

        __debug__printLine("Combining jobs into " . $strOutFilePath . " from " . count($arrMyRecordsToInclude) ." records and " . count($arrFilesToCombine) . " CSV input files: " . var_export($arrFilesToCombine, true), C__DISPLAY_ITEM_DETAIL__);



        if(count($arrFilesToCombine) > 1)
        {
            $classCombined = new SimpleScooterCSVFileClass($strOutFilePath , "w");
            $arrRetJobs = $classCombined->readMultipleCSVsAndCombine($arrFilesToCombine, array_keys($this->getEmptyItemsArray()), $this->arrKeysForDeduping);

        }
        else if(count($arrFilesToCombine) == 1)
        {
            $classCombined = new SimpleScooterCSVFileClass($arrFilesToCombine[0], "r");
            $arrRetJobs = $classCombined->readAllRecords(true, array_keys($this->getEmptyItemsArray()));
        }


        if(count($arrMyRecordsToInclude) > 1)
        {
            $arrRetJobs = my_merge_add_new_keys($arrMyRecordsToInclude, $arrRetJobs);
        }

        $this->writeJobsListToFile($strOutFilePath, $arrRetJobs, $fIncludeFilteredJobsInResults);
        __debug__printLine("Combined file has ". count($arrRetJobs) . " jobs and was written to " . $strOutFilePath , C__DISPLAY_ITEM_START__);

        return $strOutFilePath;

    }



    function getSimpleObjFromPathOrURL($filePath = "", $strURL = "")
    {
//         __debug__printLine("getSimpleObjFromPathOrURL(".$filePath.', '.$strURL.")", C__DISPLAY_ITEM_DETAIL__);
        $objSimpleHTML = null;

        if(!$objSimpleHTML && ($filePath && strlen($filePath) > 0))
        {
            __debug__printLine("Loading ALTERNATE results from ".$filePath, C__DISPLAY_ITEM_START__);
            $objSimpleHTML =  $this->getSimpleHTMLObjForFileContents($filePath);
        }

        if(!$objSimpleHTML && $this->_strAlternateLocalFile  && strlen($this->_strAlternateLocalFile ) > 0)
        {
            __debug__printLine("Loading ALTERNATE results from ".$this->_strAlternateLocalFile , C__DISPLAY_ITEM_DETAIL__);
            $objSimpleHTML =  $this->getSimpleHTMLObjForFileContents($this->_strAlternateLocalFile );
        }

        if(!$objSimpleHTML && $strURL && strlen($strURL) > 0)
        {
//             __debug__printLine("Loading results from ".$strURL, C__DISPLAY_ITEM_DETAIL__);
            $objSimpleHTML = file_get_html($strURL);
        }

        if(!$objSimpleHTML)
        {
            throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$filePath.') or '.$strURL);
        }

        return $objSimpleHTML;
    }


    function getOutputFileFullPath($strFilePrefix = "", $strBase = 'jobs', $strExtension = 'csv')
    {
        $strFullPath = getDefaultJobsOutputFileName($strFilePrefix, $strBase , $strExtension);

        if(strlen($this->strOutputFolder) > 0)
        {
            $strFullPath = $this->strOutputFolder . $strFullPath;
        }
        return $strFullPath;
    }

    function getSimpleHTMLObjForFileContents($strInputFileFullPath)
    {
        $objSimpleHTML = null;
        __debug__printLine("Loading HTML from ".$strInputFileFullPath, C__DISPLAY_ITEM_DETAIL__);

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

