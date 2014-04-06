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
require_once dirname(__FILE__) . '/../include/ClassJobsSite.php';

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Class:  Pulling the Active Jobs from Amazon's site                                      ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
// $test = new ClassAmazonJobs();
// $test->getJobsFromNewSiteFiles();



class ClassAmazonJobs extends ClassJobsSite
{
    protected $siteName = 'Amazon';

    public $arrSearches = array(
        'keyword-dir'=> array("name" => 'keyword-dir',  "base_url" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=director&category=*&location=US%2C+WA%2C+Seattle&x=0&y=0&page="),
        'keyword-gm'=> array("name" => 'keyword-gm',  "base_url" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=general+manager&category=*&location=US%2C+WA%2C+Seattle&x=25&y=10&page="),
        'pm-nontech' => array("name" => 'pm-nontech',  "base_url" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=&category=Project%2FProgram%2FProduct+Management--NON-TECH&location=US%2C+WA%2C+Seattle&x=40&y=11&page="),
        'pm-tech' => array("name" => 'pm-tech',  "base_url" => "http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=&category=Project%2FProgram%2FProduct+Management--TECHNICAL&location=US%2C+WA%2C+Seattle&x=22&y=9&page="),
        'pm-newsite' => array("name" => 'pm-newsite', "base_url" => "http://www.amazon.jobs/results?sjid=68,83&checklid=@'US, WA, Seattle'&cname='US, WA, Seattle'"),
    );

    function getMyJobs($nDays = -1, $fIncludeFilteredJobsInResults = true)
    {
        if($nDays > 1)
        {
            __debug__printLine($this->siteName ." jobs can only be pulled for, at most, 1 day.  Ignoring number of days value and just pulling current listings.", C__DISPLAY_MOMENTARY_INTERUPPT__);

        }

        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['pm-nontech']['name']."...", C__DISPLAY_ITEM_START__);
        $this->_getMyJobsFromOldSiteSearchURL_($this->arrSearches['pm-nontech']);

        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['pm-tech']['name']."...", C__DISPLAY_ITEM_START__);
        $this->_getMyJobsFromOldSiteSearchURL_($this->arrSearches['pm-tech']);

        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['keyword-dir']['name']."...", C__DISPLAY_ITEM_START__);
        $this->_getMyJobsFromOldSiteSearchURL_($this->arrSearches['keyword-dir']);

        __debug__printLine("Adding Amazon jobs for " . $this->arrSearches['keyword-gm']['name']."...", C__DISPLAY_ITEM_START__);
        $this->_getMyJobsFromOldSiteSearchURL_($this->arrSearches['keyword-gm']);

        __debug__printLine("Skipping new Amazon jobs site ", C__DISPLAY_ITEM_START__);

        __debug__printLine("Adding Amazon jobs from new Amazon site...", C__DISPLAY_ITEM_START__);
        $this->__getMyJobsFrom_Amazon_NewJobs_HTMLFiles__($this->arrSearches['pm-newsite'], $fIncludeFilteredJobsInResults);

        $strCombinedFileName = $this->getOutputFileFullPath("AllJobs");
        $this->writeMyJobsListToFile($strCombinedFileName, $fIncludeFilteredJobsInResults );

        return $strCombinedFileName;
    }


    function getMyJobs_UseForSingleSearchTestingOnly($arrSearchSettings, $fIncludeFilteredJobsInResults = true)
    {
        __debug__printLine("Adding Amazon jobs for " . $arrSearchSettings['name']."...", C__DISPLAY_ITEM_START__);
        $this->_getMyJobsFromOldSiteSearchURL_($arrSearchSettings);


        $strTestFile = $this->getOutputFileFullPath($arrSearchSettings['name']."SingleSearchTestingOnly");
        $this->writeMyJobsListToFile($strTestFile, $fIncludeFilteredJobsInResults );

        return $strTestFile;
    }


// http://www.amazon.jobs/results?sjid=68,sjid=83&checklid=@'US, WA, Seattle'&cname='US, WA, Seattle'
    private function __getMyJobsFrom_Amazon_NewJobs_HTMLFiles__($arrSettings, $fIncludeFilteredJobsInResults)
    {


        $nItemCount = 1;

        $strFileName = $this->strOutputFolder . "amazon-newjobs-page-".$nItemCount.".html";


        while (file_exists($strFileName) && is_file($strFileName))
        {
            $objSimpleHTML = $this->getSimpleHTMLObjForFileContents($strFileName);

            $arrNewJobs = $this->_getParseJobsData_Amazon_NewJobs_($objSimpleHTML, $this->arrSearches['pm-newsite']);

            $objSimpleHTML->clear();
            unset($objSimpleHTML);

            $this->addJobsToList($arrNewJobs);

            $nItemCount++;
            $strOutputJobsFile = $this->getOutputFileFullPath($arrSettings['name']);
            $this->writeMyJobsListToFile($strOutputJobsFile, $fIncludeFilteredJobsInResults );

            return $strOutputJobsFile;

        }


    }


    private function _getParseJobsData_Amazon_NewJobs_($objSimpleHTML, $arrSettings)
    {


        $ret = array();
        $nodesTD= $objSimpleHTML->find('tr td[class="expand footable-first-column"]');

        $nTDIndex = 0;
        while($nTDIndex < count($nodesTD))
        {
            if($nodesTD[$nTDIndex])
            {
                $item = $this->getEmptyItemsArray();

                $titleObj = $nodesTD[$nTDIndex]->nextSibling();

                $item['job_title'] = $titleObj->firstChild()->plaintext;

                $item['job_post_url'] =$titleObj->firstChild()->href;
                $item['company'] = 'Amazon';

                $item['job_site'] = 'Amazon';
                $item['date_pulled'] = $this->_getCurrentDateAsString_();

//                $item['script_search_key'] = $arrSettings['name'];
//                $item['job_source_url'] = $item['job_post_url'];
//                 $item['original_source'] = 'Amazon';

                $item['job_id'] = trim(explode("/", $item['job_post_url'])[4]);

                $catObj = $titleObj->nextSibling();
                $item['job_site_category'] = $catObj->plaintext;

                $locObj = $catObj ->nextSibling();
                $item['location'] = $locObj->plaintext;

                $briefObj = $locObj ->nextSibling();

                if(true)
//                if($this->is_IncludeBrief() == true)
                {
                    $brief  = trim($briefObj->plaintext);
                    $arrBrief = explode("Short Description", $brief);
                    $item['brief_description'] = $arrBrief[1];
                }

                $ret[] = $item;

            }
            $nTDIndex = $nTDIndex + 5;


        }
        return $ret;
    }


    private function _getMyJobsFromOldSiteSearchURL_($arrSettings, $strAlternateLocalHTMLFile = "")
    {

        $objSimpleHTML = $this->getSimpleObjFromPathOrURL($strAlternateLocalHTMLFile, $arrSettings['base_url']);
        if(!$objSimpleHTML) throw new ErrorException('Error:  unable to get SimpleHTML object from file('.$strAlternateLocalHTMLFile.') or '.$arrSettings['base_url']);


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
            $strURL = $arrSettings['base_url'].$nItemCount;
            __debug__printLine("Querying jobs page #".$nItemCount." from ".$strURL, C__DISPLAY_ITEM_START__);
            $arrNewJobs = $this->_scrapeJobsFromHTML($strURL, $arrSettings, $strAlternateLocalHTMLFile);

            $arrAllJobs = array_merge($arrAllJobs, $arrNewJobs);

            $nItemCount++;
        }

        $this->addJobsToList($arrAllJobs);
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
//            $item['script_search_key'] = $arrSettings['name'];

            $item['job_post_url'] = "http://www.amazon.com" . $firstPart->find('a[class="title"]')[0]->href;
//            $item['job_source_url'] = $item['job_post_url'];
            $item['location'] = trim($firstPart->find('span[class="details"]')[0]->plaintext);
            $id = trim($firstPart->find('span[class="id"]')[0]->plaintext);
            $item['job_id'] = trim(str_replace(array("&nbsp;", "(", ")", "ID"), " ", $id));

            $item['company'] = 'Amazon';
//             $item['original_source'] = 'Amazon';
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
        }

        // clean up memory
        $objSimpleHTML ->clear();
        unset($objSimpleHTML );

        return $ret;
    }



}

?>
