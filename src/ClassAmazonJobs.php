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
$test = new ClassAmazonJobs();


$test->getJobsFromNewSiteFiles();



class ClassAmazonJobs
{


    // New site
    //	$.ajax({
    //	type: 'POST',
    //	url: 'http://www.amazon.jobs/ajax/ajax_results.php',
    //	data: 'type_of_pagination='+type_of_jobs+'&rows_per_page='+rows_per_page+'&id='+id +"&nextpage="+nextpage+"&rid="+rid+"&cid="+cid+"&jid="+jid+"&lid="+lid+"&tid="+tid+"&flag="+flag+"&sjid="+sjid+"&country="+country+"&slid="+slid+"&scid="+scid+"&stid="+stid+"&str="+str+"&cname="+cname+"&search="+searc+"&uid="+uid,
// http://www.amazon.jobs/results?cname=%27US,%20WA,%20Seattle%27

// http://www.amazon.jobs/results?sjid=68,sjid=83&checklid=@'US, WA, Seattle'&cname='US, WA, Seattle'
    function getJobsFromNewSiteFiles()
    {

        $classFileOut = new SimpleScooterCSVFileClass("OutputAMZNJobs-NewSite.csv", "a");

        $arrAllJobs = array('job_title', 'bryans_notes', 'interest', 'link', 'location', 'job_id', 'jobs_category', 'brief');
        $nPageCount = 1;

        $strFileName = '/Users/bryan/Code/data/amzn_jobs/page'.$nPageCount.'.html';


        while (file_exists($strFileName))
        {
            $strFileName = '/Users/bryan/Code/data/amzn_jobs/page'.$nPageCount.'.html';
            $fpGeek = fopen($strFileName , 'r');
            if(!$fpGeek) break;
            $strHTML = fread($fpGeek,4000000);

            $arrNewJobs = $this->getJobsFromSearch_NewAmazonJobsSite($strHTML, 'new site');
            $classFileOut->writeArrayToCSVFile($arrNewJobs);

//            var_dump($arrAllJobs);
            $nPageCount++;

        }
        var_export($arrAllJobs);

        return $arrAllJobs;
    }
    function getJobsFromSearch_NewAmazonJobsSite($strHTML)
    {
        $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
        $html= $dom->load($strHTML, $lowercase, $stripRN);

        // # of pages to parse
        $tableResults= $html->find('table[id="teamjobs"]');
        $ret = array();
        $tableResults = $tableResults[0];
        $nodesTD= $tableResults->find('tr td');
        $nTDIndex =2;
        while($nTDIndex < count($nodesTD))
        {
            if($nodesTD[$nTDIndex])
            {
                $item = array();
                $item['job_title'] = $nodesTD[$nTDIndex]->previousSibling()->first_child()->plaintext;

                $item['bryans_notes'] = '';
                $item['interest'] = '';
                $item['link'] = $nodesTD[$nTDIndex]->previousSibling()->first_child()->href;
                $item['location'] = '';
                $item['job_id'] = explode("/", $item['link'])[4];
                $item['jobs_category'] = $nodesTD[$nTDIndex]->plaintext;
                $nTDIndex++;
                $item['location'] = $nodesTD[$nTDIndex]->plaintext;
                $nTDIndex++;
                $brief  = trim($nodesTD[$nTDIndex]->plaintext);

                $arrBrief = explode("Short Description", $brief);
                $item['brief'] = $arrBrief[1];
            }
            $nTDIndex = $nTDIndex + 3;

            $ret[] = $item;

        }

        $html->clear();
        unset($html);

        return $ret;
    }


    function getSeattlePMJobsList()
    {

        $arrAllPMJobs = array();

        // Technical PM jobs in SEA
        $baseURL = 'http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=&category=Project%2FProgram%2FProduct+Management--TECHNICAL&location=US%2C+WA%2C+Seattle&x=22&y=9&page=';
        $arrRet = $this->getJobsFromSearch($baseURL, 'Technical PM (SEA)' );
        $arrAllPMJobs = array_merge($arrAllPMJobs ,$arrRet);

        $classFileOut = new SimpleScooterCSVFileClass("OutputAMZNJobs-TechPM.csv", "w");
        $classFileOut->writeArrayToCSVFile($arrAllPMJobs);
        $classFileOut = null;
        $arrAllPMJobs = array();
/*

        // all jobs with "director" in them in SEA
        $baseURL = 'http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=director&category=*&location=US%2C+WA%2C+Seattle&x=0&y=0&page=';
        $arrRet = $this->getJobsFromSearch($baseURL, 'keyword=director (SEA)' );
        $arrAllPMJobs = array_merge($arrAllPMJobs ,$arrRet);

        $classFileOut = new SimpleScooterCSVFileClass("OutputAMZNJobs-KeywordDir.csv", "w");
        $classFileOut->writeArrayToCSVFile($arrAllPMJobs);
        $classFileOut = null;
        $arrAllPMJobs = array();
        /*


                // all jobs with "general manager" in them in SEA
                $baseURL = 'http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=general+manager&category=*&location=US%2C+WA%2C+Seattle&x=25&y=10&page=';
                $arrRet = $this->getJobsFromSearch($baseURL, 'general manager PM (SEA)' );
                $arrAllPMJobs = array_merge($arrAllPMJobs ,$arrRet);

                $classFileOut = new SimpleScooterCSVFileClass("OutputAMZNJobs-KeywordGM.csv", "w");
                $classFileOut->writeArrayToCSVFile($arrAllPMJobs);
                $classFileOut = null;
                $arrAllPMJobs = array();



                // Non-Technical PM jobs in SEA
                $baseURL = 'http://www.amazon.com/gp/jobs/ref=j_sq_btn?jobSearchKeywords=&category=Project%2FProgram%2FProduct+Management--NON-TECH&location=US%2C+WA%2C+Seattle&x=40&y=11&page=';
                $arrRet = $this->getJobsFromSearch($baseURL, 'Non-Technical PM (SEA)' );
                $arrAllPMJobs = array_merge($arrAllPMJobs ,$arrRet);

                $classFileOut = new SimpleScooterCSVFileClass("OutputAMZNJobs-NonTechPM.csv", "w");
                $classFileOut->writeArrayToCSVFile($arrAllPMJobs);
                $classFileOut = null;
                $arrAllPMJobs = array();


        */

    }


    function getJobsFromSearch($strBaseURL, $category)
    {

        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

        $classAPIWrap = new APICallWrapperClass();
        // create HTML DOM
        $html = file_get_html($strBaseURL."1");


        // # of pages to parse
        $pageDiv= $html->find('div[class="pagination"]');
        $pageDiv = $pageDiv[0];
        $pageText = $pageDiv->plaintext;
        $arrPageItems = explode(" ", trim($pageText));
        $maxPage = $arrPageItems[9];
        // clean up memory
        $html->clear();
        unset($html);

        $arrAllJobs = array();



        $nPageCount = 1;
        while ($nPageCount <= $maxPage)
        {
            $strURL = $strBaseURL.$nPageCount;
            __debug__printLine("Querying jobs from ".$strURL, C__DISPLAY_ITEM_START__);
            $arrNewJobs = $this->_scrapeJobsFromHTML($strURL, $category);

            $arrAllJobs = array_merge($arrAllJobs, $arrNewJobs);

            $nPageCount++;
        }

        return $arrAllJobs;
    }

    private function _scrapeJobsFromHTML($url, $category)
    {
        $html = file_get_html($url);

        $resultsDiv= $html->find('div[class="searchResultsWrapper"]');
        $resultsDiv = $resultsDiv[0];

        $nodesTR = $resultsDiv->find('tr');
        foreach($nodesTR as $firstPart)
        {
            $item['job_title'] = $firstPart->find('a[class="title"]')[0]->plaintext;
            if($item['job_title'] == '') continue;

            $item['bryans_notes'] = '';
            $item['link'] = $firstPart->find('a[class="title"]')[0]->href;
            $item['location'] = trim($firstPart->find('span[class="details"]')[0]->plaintext);
            $id = trim($firstPart->find('span[class="id"]')[0]->plaintext);
            $item['job_id'] = str_replace("&nbsp;", " ", trim($id));
            $item['jobs_category'] = $category;

            $secPart = $firstPart->nextSibling();

            if($secPart)
            {
                $nodeDesc = $secPart->find('div[class="shortDescription"] div')[0];
                if($nodeDesc)
                {
                    $item['brief'] = trim($nodeDesc->plaintext);
                }
            }
            $ret[] = $item;
        }

        // clean up memory
        $html->clear();
        unset($html);

        return $ret;
    }



}