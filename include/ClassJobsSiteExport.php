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

    private $arrInterestLevelsToExclude = array(
        'No' => array('interested' => 'No', 'exclude' => true),
        'Yes' => array('interested' => 'Yes', 'exclude' => false),
        'Maybe' => array('interested' => 'Maybe', 'exclude' => false),
        'No (Auto Excluded)' => array('interested' => 'No (Auto Excluded)', 'exclude' => true),
    );


    private $_strAlternateLocalFile = '';
    private $_bitFlags = null;
    protected $strOutputFolder = "";
    protected $arrLatestJobs = "";
    protected $arrTitlesToFilter = null;


    function addJobsToList($arrAdd)
    {
        if(!is_array($arrAdd) || count($arrAdd) == 0)
        {
            // skip. no jobs to add
            return;
        }

        if(is_array($this->arrLatestJobs))
        {
            $this->arrLatestJobs = array_merge($this->arrLatestJobs, $arrAdd );
        }
        else
        {
            $this->arrLatestJobs = array_copy( $arrAdd );

        }
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

    function setExcludedTitles($arrJobsToFilter)
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


        __debug__printLine("Completed marking auto-excluded titles:  '".$nJobsExcluded ."' were marked as auto excluded and ". $nJobsNotExcluded . " jobs were not." , C__DISPLAY_ITEM_RESULT__);


        return $arrJobsToFilter;
    }


    function filterNotInterestedJobs($arrJobsToFilter, $fIncludeFilteredJobsInResults = true)
    {
        if($fIncludeFilteredJobsInResults == true)
        {
            __debug__printLine("Including filtered jobs in results." , C__DISPLAY_MOMENTARY_INTERUPPT__);
            return $arrJobsToFilter;
        }

        $nJobsNotExcluded = 0;
        $nJobsExcluded = 0;
        $retArrayFilteredJobs = array();

        foreach($arrJobsToFilter as $job)
        {
            if(strlen($job['interested']) <= 0)
            {
                // Interested value not set; always include in the results
                $retArrayFilteredJobs[] = $job;

            }
            else
            {
                $arrCurJobInterest = $this->arrInterestLevelsToExclude[$job['interested']];


                if(!is_array($arrCurJobInterest ))  // we're ignoring the Excluded column fact for the time being. If it's in the list, it's excluded
                {
                    $retArrayFilteredJobs[] = $job;
                    $nJobsNotExcluded++;
                    __debug__printLine("Job interest level of '".$job['interested'] ."' was not found in the exclusion list.  Keeping for review." , C__DISPLAY_ITEM_DETAIL__);
                }
                else if($arrCurJobInterest['exclude'] == false)
                {
                    $retArrayFilteredJobs[] = $job;
                    $nJobsNotExcluded++;
                }
                else
                {
                    $nJobsExcluded++;
                }
            }
        }

        __debug__printLine("Filtering complete:  '".$nJobsExcluded ."' were automatically filtered and ". $nJobsNotExcluded . " jobs were not." , C__DISPLAY_ITEM_RESULT__);

        return $retArrayFilteredJobs;
    }



    function combineMultipleJobsCSVs($strOutFilePath, $arrFilesToCombine, $fIncludeFilteredJobsInResults = true)
    {
        if(!$strOutFilePath || strlen($strOutFilePath) <= 0)
        {
            $strOutFilePath = $this->getOutputFileFullPath('CombineMultipleJobsCSVs_');
        }

        if(!$arrFilesToCombine)
        {
            if(!is_array($arrFilesToCombine) && is_string($arrFilesToCombine))
            {
                $arrFilesToCombine = array($arrFilesToCombine);
            }
            else
            {
                throw new ErrorException("Cannot combine files into a single CSF because there were no filenames set to combine.");
            }

        }

        $retOutFilePathWritten = $strOutFilePath;
        $arrKeysForDeduping = array('job_site', 'job_id');
        $arrRetJobs = null;

        $classCombined = new SimpleScooterCSVFileClass($strOutFilePath , "w");


        __debug__printLine("Merging input & downloaded jobs data and writing to " . $strOutFilePath , C__DISPLAY_ITEM_START__);
        __debug__printLine("Including source data from " .var_export($arrFilesToCombine, true), C__DISPLAY_ITEM_DETAIL__);


        if($fIncludeFilteredJobsInResults == false)
        {
            __debug__printLine("Filtering final results data...", C__DISPLAY_ITEM_DETAIL__);
            $arrRetJobs = $classCombined->readMultipleCSVsAndCombine($arrFilesToCombine);
            $arrRetJobs = $this->filterNotInterestedJobs($arrRetJobs, $fIncludeFilteredJobsInResults);
            if(is_array($arrRetJobs) && count($arrRetJobs) > 0)
            {
                $classCombined->writeArrayToCSVFile($arrRetJobs);
                __debug__printLine("Combined file has ". count($arrRetJobs) . " jobs and was written to " . $strOutFilePath , C__DISPLAY_ITEM_START__);
            }
            else
            {
                __debug__printLine("All records were filtered, so not writing output to file " . $strOutFilePath, C__DISPLAY_ITEM_DETAIL__);
                $retOutFilePathWritten = null;
            }

        }
        else
        {
            $arrRetJobs = $classCombined->combineMultipleCSVs($arrFilesToCombine, null, $arrKeysForDeduping);
            __debug__printLine("Combined file has ". count($arrRetJobs) . " jobs and was written to " . $strOutFilePath , C__DISPLAY_ITEM_START__);
        }


        return $retOutFilePathWritten;

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

        if(!$objSimpleHTML && $this->_strAlternateLocalFile  && strlen($this->_strAlternateLocalFile ) > 0)
        {
            __debug__printLine("Loading ALTERNATE results from ".$this->_strAlternateLocalFile , C__DISPLAY_ITEM_START__);
            $objSimpleHTML =  $this->getSimpleHTMLObjForFileContents($this->_strAlternateLocalFile );
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

