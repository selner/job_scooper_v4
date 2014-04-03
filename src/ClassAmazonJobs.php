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

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Class:  Pulling the Active Jobs from Amazon's site                                      ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
// $test = new ClassAmazonJobs();
// $test->getJobsFromNewSiteFiles();



class ClassAmazonJobs extends ClassSiteExportBase
{
    protected $siteName = 'Amazon';

    private $arrSearches = array(
        'keyword-dir'=> array("name" => 'keyword-dir', 'output_file' => "OutputAMZNJobs-Keyword-Dir.csv", "baseURL" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=director&category=*&location=US%2C+WA%2C+Seattle&x=0&y=0&page="),
        'keyword-gm'=> array("name" => 'keyword-gm', 'output_file' => "OutputAMZNJobs-Keyword-GM.csv", "baseURL" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=general+manager&category=*&location=US%2C+WA%2C+Seattle&x=25&y=10&page="),
        'pm-nontech' => array("name" => 'pm-nontech', 'output_file' => "OutputAMZNJobs-PM-NonTech.csv", "baseURL" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=&category=Project%2FProgram%2FProduct+Management--NON-TECH&location=US%2C+WA%2C+Seattle&x=40&y=11&page="),
        'pm-tech' => array("name" => 'pm-tech', 'output_file' => "OutputAMZNJobs-PM-Tech.csv", "baseURL" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=&category=Project%2FProgram%2FProduct+Management--TECHNICAL&location=US%2C+WA%2C+Seattle&x=22&y=9&page="),
        'pm-newsite' => array("name" => 'pm-newsite', 'output_file' => "OutputAMZNJobs-PM-NewSite.csv", "baseURL" => "'http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=director&category=*&location=US%2C+WA%2C+Seattle&x=0&y=0&page="),
    );

    function downloadAllUpdatedJobs($nDays = -1)
    {
        return $this->getJobs($nDays );
    }

    function getJobs($nDays = -1)
    {
        if($nDays > 1)
        {
            __debug__printLine($this->siteName ." jobs can only be pulled for, at most, 1 day.  Skipping.", C__DISPLAY_MOMENTARY_INTERUPPT__);
            return "";
        }

        $this->getJobs_OldSite_Keywords();
        $this->getJobs_NewSite();
        $this->getJobs_OldSite_PMCategory();

        $strCombinedFileName = $this->getOutputFileFullPath("AllJobs");
        $classCombined = new SimpleScooterCSVFileClass($strCombinedFileName, "w");
        $classCombined->combineMultipleCSVs(array(
            $this->arrSearches['keyword-dir']['output_file'],
            $this->arrSearches['keyword-gm']['output_file'],
            $this->arrSearches['pm-nontech']['output_file'],
            $this->arrSearches['pm-tech']['output_file'],
            $this->arrSearches['pm-newsite']['output_file'],
        ));
        return $strCombinedFileName;
    }

    function getJobs_OldSite_Keywords()
    {
        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['keyword-dir']['name']."...", C__DISPLAY_ITEM_START__);
        $this->__writeDataToCSV_Amazon_OldJobs_($this->arrSearches['keyword-dir']);

        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['keyword-gm']['name']."...", C__DISPLAY_ITEM_START__);
        $this->__writeDataToCSV_Amazon_OldJobs_($this->arrSearches['keyword-gm']);

        $strCombinedFileName = $this->getOutputFileFullPath("Combined_OldSite_Keywords");
        $classCombined = new SimpleScooterCSVFileClass($strCombinedFileName, "w");
        $classCombined->combineMultipleCSVs(array($this->arrSearches['keyword-dir']['output_file'], $this->arrSearches['keyword-gm']['output_file'] ));
        return $strCombinedFileName;
    }

    function getJobs_NewSite()
    {
        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['pm-newsite']['name']."...", C__DISPLAY_ITEM_START__);
        $strOut = $this->getOutputFileFullPath($this->arrSearches['pm-newsite']['name']);
        $arrRet  = $this->__processHTMLFiles_Amazon_NewJobs__($strOut);
        $this->arr = array_merge($this->arrLatestJobs, $arrRet );

        return $strOut;
    }

    function getJobs_OldSite_PMCategory()
    {
        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['pm-nontech']['name']."...", C__DISPLAY_ITEM_START__);
         $this->__writeDataToCSV_Amazon_OldJobs_($this->arrSearches['pm-nontech']);

        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['pm-tech']['name']."...", C__DISPLAY_ITEM_START__);
         $this->__writeDataToCSV_Amazon_OldJobs_($this->arrSearches['pm-tech']);

        $strCombinedFileName = $this->getOutputFileFullPath("Combined_OldSite_PMCategory");
        $classCombined = new SimpleScooterCSVFileClass($strCombinedFileName, "w");
        $classCombined->combineMultipleCSVs(array($this->arrSearches['pm-nontech']['output_file'], $this->arrSearches['pm-tech']['output_file'] ));
        return $strCombinedFileName;

    }

// http://www.amazon.jobs/results?sjid=68,sjid=83&checklid=@'US, WA, Seattle'&cname='US, WA, Seattle'
    private function __processHTMLFiles_Amazon_NewJobs__($strOutfilePath )
    {

        $classFileOut = new SimpleScooterCSVFileClass($this->arrSearches['pm-newsite']['output_file'], "a");

        $arrAllJobs = $this->getEmptyItemsArray();
        $nItemCount = 1;

        $strFileName = '/Users/bryan/Code/data/amzn_jobs/page-'.$nItemCount.'.html';


        while (file_exists($strFileName) && is_file($strFileName))
        {
            $fpGeek = fopen($strFileName , 'r');
            if(!$fpGeek) break;
            $strHTML = fread($fpGeek,4000000);

            $arrNewJobs = $this->_getParseJobsData_Amazon_NewJobs_($strHTML, $this->arrSearches['pm-newsite']);
            $classFileOut->writeArrayToCSVFile($arrNewJobs);

            $nItemCount++;
            $strFileName = '/Users/bryan/Code/data/amzn_jobs/page-'.$nItemCount.'.html';

        }

        return $arrAllJobs;
    }


    private function _getParseJobsData_Amazon_NewJobs_($strHTML, $arrSettings)
    {

        $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
        $objSimpleHTML= $dom->load($strHTML, $lowercase, $stripRN);
        if(!$objSimpleHTML) throw new ErrorException('Error:  unable to get SimpleHTML object from string HTML');


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
                $item['job_post_url'] = "http://www.amazon.jobs" . $nodesTD[$nTDIndex]->previousSibling()->first_child()->href;
                $item['job_source_url'] = $item['job_post_url'];
                $item['company'] = 'Amazon';

                $item['job_site'] = 'Amazon';
                $item['date_pulled'] = parent::getPostDateString();


                $item['original_source'] = 'Amazon';
                $item['job_id'] = trim(explode("/", $item['link'])[4]);
                $item['job_site_category'] = $nodesTD[$nTDIndex]->plaintext;
                $nTDIndex++;
                $item['location'] = $nodesTD[$nTDIndex]->plaintext;
                $nTDIndex++;
                $brief  = trim($nodesTD[$nTDIndex]->plaintext);
                $arrBrief = explode("Short Description", $brief);
                $item['brief_description'] = $arrBrief[1];

                  $ret[] = $item;

            }
            $nTDIndex = $nTDIndex + 3;


        }

        $objSimpleHTML->clear();
        unset($objSimpleHTML);

        return $ret;
    }

    private function __writeDataToCSV_Amazon_OldJobs_($arrSearchSettings, $strAlternateLocalHTMLFile = "")
    {

        $arrRet = $this->__getJobsFromSearch__($arrSearchSettings['baseURL'], $arrSearchSettings, $strAlternateLocalHTMLFile );
        if(is_array($this->arrLatestJobs))
        {
            $this->arrLatestJobs = array_merge($this->arrLatestJobs, $arrRet);

        }
        else
        {
            $this->arr = array_copy( $arrRet );
        }

        $classFileOut = new SimpleScooterCSVFileClass($arrSearchSettings['output_file'], "w");
        $classFileOut->writeArrayToCSVFile($arrRet);
        $classFileOut = null;

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
            $item['date_pulled'] = parent::getPostDateString();



            $secPart = $firstPart->nextSibling();

            if($secPart)
            {
                $nodeDesc = $secPart->find('div[class="shortDescription"] div')[0];
                if($nodeDesc)
                {
                    $item['brief_description'] = trim($nodeDesc->plaintext);
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
