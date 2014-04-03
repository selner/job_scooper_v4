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
require_once dirname(__FILE__) . '/../include/ClassJobsSiteBase.php';

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Class:  Pulling the Active Jobs from Amazon's site                                      ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
// $test = new ClassAmazonJobs();
// $test->getJobsFromNewSiteFiles();



class ClassAmazonJobs extends ClassJobsSiteBase
{
    protected $siteName = 'Amazon';

    private $arrSearches = array(
        'keyword-dir'=> array("name" => 'keyword-dir',  "baseURL" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=director&category=*&location=US%2C+WA%2C+Seattle&x=0&y=0&page="),
        'keyword-gm'=> array("name" => 'keyword-gm',  "baseURL" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=general+manager&category=*&location=US%2C+WA%2C+Seattle&x=25&y=10&page="),
        'pm-nontech' => array("name" => 'pm-nontech',  "baseURL" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=&category=Project%2FProgram%2FProduct+Management--NON-TECH&location=US%2C+WA%2C+Seattle&x=40&y=11&page="),
        'pm-tech' => array("name" => 'pm-tech',  "baseURL" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=&category=Project%2FProgram%2FProduct+Management--TECHNICAL&location=US%2C+WA%2C+Seattle&x=22&y=9&page="),
        'pm-newsite' => array("name" => 'pm-newsite', "baseURL" => "'http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=director&category=*&location=US%2C+WA%2C+Seattle&x=0&y=0&page="),
    );

/*    function downloadAllUpdatedJobs($nDays = -1)
    {
        return $this->getJobs($nDays );
    }
*/
    function getJobs($nDays = -1)
    {
        if($nDays > 1)
        {
            __debug__printLine($this->siteName ." jobs can only be pulled for, at most, 1 day.  Skipping.", C__DISPLAY_MOMENTARY_INTERUPPT__);
            return "";
        }

        $arrOutFiles[] = $this->getJobs_OldSite_Keywords();
        $arrOutFiles[] = $this->getJobs_NewSite();
        $arrOutFiles[] = $this->getJobs_OldSite_PMCategory();

        $strCombinedFileName = $this->getOutputFileFullPath("AllJobs");
        $this->combineMultipleJobsCSVs($strCombinedFileName, $arrOutFiles);
        return $strCombinedFileName;
    }

    function getJobs_OldSite_Keywords()
    {

        $arrFilesOut = array();


        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['keyword-dir']['name']."...", C__DISPLAY_ITEM_START__);
        $arrFilesOut[] = $this->__writeDataToCSV_Amazon_OldJobs_($this->arrSearches['keyword-dir']);

        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['keyword-gm']['name']."...", C__DISPLAY_ITEM_START__);
        $arrFilesOut[] = $this->__writeDataToCSV_Amazon_OldJobs_($this->arrSearches['keyword-gm']);

        $strCombinedFileName = $this->getOutputFileFullPath("Combined_OldSite_Keywords");
        $classCombined = new SimpleScooterCSVFileClass($strCombinedFileName, "w");
        $classCombined->combineMultipleCSVs($arrFilesOut);

        return $strCombinedFileName;
    }

    function getJobs_NewSite()
    {
        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['pm-newsite']['name']."...", C__DISPLAY_ITEM_START__);
        $strOutFileName = $this->getOutputFileFullPath($this->arrSearches['pm-newsite']['name']);

        $strOut = $this->getOutputFileFullPath($strOutFileName);
        $this->__processHTMLFiles_Amazon_NewJobs__($strOut);

        return $strOutFileName;
    }

    function getJobs_OldSite_PMCategory()
    {
        $arrFilesOut = array();

        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['pm-nontech']['name']."...", C__DISPLAY_ITEM_START__);
        $arrFilesOut[] = $this->__writeDataToCSV_Amazon_OldJobs_($this->arrSearches['pm-nontech']);

        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['pm-tech']['name']."...", C__DISPLAY_ITEM_START__);
        $arrFilesOut[] = $this->__writeDataToCSV_Amazon_OldJobs_($this->arrSearches['pm-tech']);

        $strCombinedFileName = $this->getOutputFileFullPath("Combined_OldSite_PMCategory");
        $classCombined = new SimpleScooterCSVFileClass($strCombinedFileName, "w");
        $classCombined->combineMultipleCSVs($arrFilesOut);

    }

// http://www.amazon.jobs/results?sjid=68,sjid=83&checklid=@'US, WA, Seattle'&cname='US, WA, Seattle'
    private function __processHTMLFiles_Amazon_NewJobs__($strOutFile )
    {

        $classFileOut = new SimpleScooterCSVFileClass($strOutFile, "a");

        $nItemCount = 1;

        $strFileName = C_STR_DATAFOLDER . "/amazon_jobs/page-".$nItemCount.".html";


        while (file_exists($strFileName) && is_file($strFileName))
        {
            $objSimpleHTML = $this->getSimpleHTMLObjForFileContents($strFileName);
            $arrNewJobs = $this->_getParseJobsData_Amazon_NewJobs_($objSimpleHTML, $this->arrSearches['pm-newsite']);

            $objSimpleHTML->clear();
            unset($objSimpleHTML);


            $this->addJobsToList($arrNewJobs);

            $classFileOut->writeArrayToCSVFile($this->arrLatestJobs);

            $nItemCount++;
            $strFileName = C_STR_DATAFOLDER . "/amazon_jobs/page-".$nItemCount.".html";

        }


    }


    private function _getParseJobsData_Amazon_NewJobs_($objSimpleHTML, $arrSettings)
    {


        // # of pages to parse
        $tableResults= $objSimpleHTML->find('table[id="teamjobs"]');
        $ret = array();
        $tableResults = $tableResults[0];
        $nodesTD= $tableResults->find('tr td');
        $nTDIndex =2;
        while($nTDIndex < count($nodesTD))
        {
            if($nodesTD[$nTDIndex])
            {
                $item = $this->getEmptyItemsArray();
                $item['job_title'] = $nodesTD[$nTDIndex]->previousSibling()->first_child()->plaintext;
                $item['script_search_key'] = $arrSettings['name'];

                $item['notes'] = '';
                $item['interested'] = '';
                $item['job_post_url'] = $nodesTD[$nTDIndex]->previousSibling()->first_child()->href;
                $item['job_source_url'] = $item['job_post_url'];
                $item['company'] = 'Amazon';

                $item['job_site'] = 'Amazon';
                $item['date_pulled'] = $this->_getCurrentDateAsString_();


                $item['original_source'] = 'Amazon';
                $item['job_id'] = trim(explode("/", $item['link'])[4]);
                $item['job_site_category'] = $nodesTD[$nTDIndex]->plaintext;
                $nTDIndex++;
                $item['location'] = $nodesTD[$nTDIndex]->plaintext;
                $nTDIndex++;

                if($this->is_IncludeBrief() == true)
                {
                    $brief  = trim($nodesTD[$nTDIndex]->plaintext);
                    $arrBrief = explode("Short Description", $brief);
                    $item['brief_description'] = $arrBrief[1];
                }

                $ret[] = $item;

            }
            $nTDIndex = $nTDIndex + 3;


        }

        return $ret;
    }

    private function __writeDataToCSV_Amazon_OldJobs_($arrSearchSettings, $strAlternateLocalHTMLFile = "")
    {
        $strOutFileName = $this->getOutputFileFullPath($this->arrSearches['pm-newsite']['name']);

        $arrRet = $this->__getJobsFromSearch__($arrSearchSettings['baseURL'], $arrSearchSettings, $strAlternateLocalHTMLFile );


        $this->addJobsToList($arrRet);

        $classFileOut = new SimpleScooterCSVFileClass($strOutFileName, "w");
        $classFileOut->writeArrayToCSVFile($this->arrLatestJobs);
        $classFileOut = null;

        return $strOutFileName;

    }

    private function __getJobsFromSearch__($strBaseURL, $arrSettings, $strAlternateLocalHTMLFile = "")
    {

        $objSimpleHTML = parent::getSimpleObjFromPathOrURL($strAlternateLocalHTMLFile, $strBaseURL);
        if(!$objSimpleHTML) throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$strAlternateLocalHTMLFile.') or '.$strBaseURL);



        // # of pages to parse
        $pageDiv= $objSimpleHTML->find('div[class="pagination"]');
        $pageDiv = $pageDiv[0];
        $pageText = $pageDiv->plaintext;
        $arrItemItems = explode(" ", trim($pageText));
        $maxItem = $arrItemItems[9];
        // clean up memory
        $objSimpleHTML->clear();
        unset($objSimpleHTML);

        $arrAllJobs = array();



        $nItemCount = 1;
        while ($nItemCount <= $maxItem)
        {
            $strURL = $strBaseURL.$nItemCount;
            __debug__printLine("Querying jobs page #".$nItemCount." from ".$strURL, C__DISPLAY_ITEM_START__);
            $arrNewJobs = $this->_scrapeJobsFromHTML($strURL, $arrSettings, $strAlternateLocalHTMLFile);

            $arrAllJobs = array_merge($arrAllJobs, $arrNewJobs);

            $nItemCount++;
        }

        return $arrAllJobs;
    }

    private function _scrapeJobsFromHTML($url, $arrSettings, $strAlternateLocalHTMLFile = null)
    {


        $objSimpleHTML = parent::getSimpleObjFromPathOrURL($strAlternateLocalHTMLFile, $url);
        if(!$objSimpleHTML) throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$strAlternateLocalHTMLFile.') or '.$url);

        $ret=null;
        $resultsDiv= $objSimpleHTML->find('div[class="searchResultsWrapper"]');
        $resultsDiv = $resultsDiv[0];

        $nodesTR = $resultsDiv->find('tr');
        foreach($nodesTR as $firstPart)
        {
            $item = $this->getEmptyItemsArray();
            $item['job_title'] = $firstPart->find('a[class="title"]')[0]->plaintext;
            if($item['job_title'] == '') continue;
            $item['script_search_key'] = $arrSettings['name'];

            $item['notes'] = '';
            $item['job_post_url'] = "http://www.amazon.com" . $firstPart->find('a[class="title"]')[0]->href;
            $item['job_source_url'] = $item['job_post_url'];
            $item['location'] = trim($firstPart->find('span[class="details"]')[0]->plaintext);
            $id = trim($firstPart->find('span[class="id"]')[0]->plaintext);
            $item['job_id'] = trim(str_replace(array("&nbsp;", "(", ")", "ID"), " ", $id));
            $item['job_site_category'] = $arrSettings['name'];


            $item['notes'] = '';
            $item['interested'] = '';
            $item['company'] = 'Amazon';
            $item['original_source'] = 'Amazon';
            $item['job_site'] = 'Amazon';
            $item['date_pulled'] = $this->_getCurrentDateAsString_();


            if($this->is_IncludeBrief() == true)
            {
                $secPart = $firstPart->nextSibling();

                if($secPart)
                {
                    $nodeDesc = $secPart->find('div[class="shortDescription"] div')[0];
                    if($nodeDesc)
                    {
                        $item['brief_description'] = trim($nodeDesc->plaintext);
                    }
                }
            }
            $ret[] = $item;
//            $ret[ $item['job_site']."-".$item['job_id']] = $item;
        }

        // clean up memory
        $objSimpleHTML ->clear();
        unset($objSimpleHTML );

        return $ret;
    }



}

?>
