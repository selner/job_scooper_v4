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
require_once 'plugin-base.php';
require_once 'common.php';
require_once dirname(__FILE__) . './include/scooter_utils_common.php';

/****************************************************************************************************************/
/**************                                                                                                         ****/
/**************          Helper Class:  Pulling the StartupLists from Geekwire                                          ****/
/**************                                                                                                         ****/
/****************************************************************************************************************/

$test = new ClassGeekwire();

// $test->_getGeekwireStartupList();

// $test->outputGeekwireList();

//$results = $test->getSeattlePMJobsList("http://www.amazon.com/gp/jobs/ref=j_sr_pag_2_next?ie=UTF8&category=*&jobSearchKeywords=director%20product%20management&location=US%2C%20WA%2C%20Seattle&page=3");


class ClassGeekwire
{
    protected $siteName = 'Geekwire';
    function outputGeekwireList()
    {
        $classFileOut = new SimpleScooterCSVFileClass("Geekwire200.csv", "w");
        $classFileOut->writeArrayToCSVFile($this->_getGeekwireStartupIndex());
    }

    private function _getGeekwireStartupIndex()
    {
        // $html = file_get_html('http://www.geekwire.com/geekwire-200/');
        $fpGeek = fopen('/Users/bryan/Code/scooper/GeekWire.html', 'r');
        $strHTML = fread($fpGeek,4000000);
        $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
        $html= $dom->load($strHTML, $lowercase, $stripRN);


        $nodesTR= $html->find('tr[class="show"]');



        foreach($nodesTR as $node)
        {
            $infoDiv= $node->find('div[class="info"]')[0];
            $category = $node->find('div[class="category"]')[0]->plaintext;

            $item['company_name'] = trim($infoDiv->find('a')[0]->plaintext);
            $item['url'] = $infoDiv->find('a')[0]->href;
            $item['geekwire_category'] = trim($category);

            $nodesTDValues= $node->find('td[class="value"]');
            $item['employees_on_linkedin_bucket'] = $nodesTDValues[2]->plaintext;

            $nodesTDValues= $node->find('td[class="value"] span');
            $item['employees_on_linkedin'] = $nodesTDValues[2]->plaintext;

            $ret[] = $this->normalizeItem($item);
        }

        // clean up memory
        $html->clear();
        unset($html);

        return $ret;
    }



    function _getGeekwireStartupList()
    {
        // $html = file_get_html('http://www.geekwire.com/geekwire-200/');
        $fpGeek = fopen('/Users/bryan/Code/scooper/GeekwireStartupList.html', 'r');
        $classFileOut = new SimpleScooterCSVFileClass("GeekwireStartupList.csv", "w");

        $ret = array();

        $strHTML = fread($fpGeek,MAX_FILE_SIZE);
        while($strHTML)
        {

            $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
            $html= $dom->load($strHTML, $lowercase, $stripRN);

            if(!$html)
            {
                exit('$html = false.  failed.');

            }

            $nodesTR= $html->find('table[class="startups"] tr');



            foreach($nodesTR as $node)
            {
                $item = array();

                $infoDiv= $node->find('div[class="info"] div[class="title"] a')[0];
                $item['company_name'] = trim($infoDiv->plaintext);
                $item['url'] = trim($infoDiv->href);

                $category = $node->find('div[class="category"]')[0]->plaintext;
                $item['geekwire_category'] = str_replace("&nbsp;", " ", trim($category));


                $ret[] = $this->normalizeItem($item);
            }

            if(is_array($ret))            $classFileOut->writeArrayToCSVFilew

            // clean up memory
            $html->clear();
            unset($html);

            $strHTML = fread($fpGeek,MAX_FILE_SIZE);
        }
        return $ret;
    }
} 


?>
