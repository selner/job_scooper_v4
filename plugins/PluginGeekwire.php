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
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');






class PluginGeekwire extends ClassJobsSitePlugin
{
    protected $siteName = 'Geekwire';
    protected $siteBaseURL = 'http://www.geekwork.com/';


    function getDaysURLValue($days)
    {
        // __debug__printLine($this->site_name . " Day Parsing Not Yet Implemented!", C__DISPLAY_WARNING__);
        return "";
    }



    function parseTotalResultsCount($nDays)  { return -1; }


/*

    function parseJobsListForPage($xmlResult)
    {
        $ret = null;
        $elem = $xmlResult;

        foreach ($xmlResult->channel->item as $job)
        {

            var_dump($xmlResult->channel->item[0]->asXML())
            $md = new MicrodataPhp();
            $data = $md->obj();
            var_dump($data);
            exit();


            $dc = $item->children("http://purl.org/dc/elements/1.1/");
            echo $dc->creator;
            foreach ($job->description->children() as $child)
            {
                var_dump('Child=', $child->getName());
            }

            $desc = $job->description;
            var_dump($desc);
            var_dump($job->{"job_listing:location"}[0]);

                $item = parent::getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;
            $item['job_post_url'] = (string)$job->link;
            $arrURLParts = explode("/", (string)$job->link);
            var_dump($arrURLParts );
            $item['company'] = explode("-", $arrURLParts[4])[0];
            $item['job_title'] =  (string)$job->title;
//            $item['location'] =  $job->job_listing:location);
            $item['location'] = (string)$job->{'job_listing:location'};

            $item['job_id'] = (string)explode("/", (string)$job->guid)[3];
//            if($item['job_title'] == '') continue;

            $item['job_site_date'] = (string)$job->pubDate;
//            $item['company'] = $this->siteName;
            $item['date_pulled'] = getTodayAsString();

            var_dump($job);
            var_dump($job->description);
            var_dump($item);
            $ret[] = $this->normalizeItem($item);
        }
exit();
        return $ret;
    }*/
    function getMyJobsForSearch($url, $nDays = -1)
    {
        $srcList = $this->getMySearches();
        foreach($srcList as $search)
        {
            $this->getMyJobsFromHTMLFiles($this->siteName, $search);
        }
    }


    function parseJobsListForPage($objSimpHTML)
    {

        $ret = null;

        $nodesJobs = $objSimpHTML->find("ul[class='job_listings'] li[class='type-job_listing']");

        $nCounter = -1;

        foreach($nodesJobs as $node)
        {
   /*         $nCounter += 1;
            if($nCounter < 2)
            {
                continue;
            }
*/
            $item = parent::getEmptyJobListingRecord();
            $item['job_site'] = $this->siteName;

            $item['job_title'] = $node->find("h3")[0]->plaintext;
            $item['job_post_url'] = $node->find("a")[0]->href;
            //if($item['job_title'] == '') continue;

            $item['location'] = $node->find("div[class='location']")[0]->plaintext;

            $item['company'] = $node->find("div[class='company'] span")[0]->plaintext;
            $item['date_pulled'] = getTodayAsString();
            $item['job_site_date'] = $node->find("li[class='date']")[0]->plaintext;
            $item['job_site_category'] = $node->find("ul[class='meta'] li")[0]->plaintext;


            $arrLIParts = explode(" ", $node->attr['class']);
            $item['job_id'] = str_replace("post-", "", $arrLIParts[0]);


            $ret[] = $this->normalizeItem($item);

        }

        return $ret;
    }

}
/****************************************************************************************************************/
/**************                                                                                                         ****/
/**************          Helper Class:  Pulling the StartupLists from Geekwire                                          ****/
/**************                                                                                                         ****/
/****************************************************************************************************************/

$test = new ClassGeekwire();

// $test->_getGeekwireStartupList();

// $test->outputGeekwireList();

//$results = $test->getSeattlePMJobsList("http://www.amazon.com/gp/jobs/ref=j_sr_pag_2_next?ie=UTF8&category=*&jobSearchKeywords=director%20product%20management&location=US%2C%20WA%2C%20Seattle&page=3");

// http://www.geekwork.com/jobs/?type=full-time&search_location=Seattle&search_keywords=director

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

/*            if(is_array($ret))
                $classFileOut->writeArrayToCSVFilew
*/
            // clean up memory
            $html->clear();
            unset($html);

            $strHTML = fread($fpGeek,MAX_FILE_SIZE);
        }
        return $ret;
    }
} 


?>
