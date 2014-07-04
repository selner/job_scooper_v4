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

/****************************************************************************************************************/
/***                                                                                                         ****/
/***                     Jobs Scooper Plugin:  Amazon.jobs                                                   ****/
/***                                                                                                         ****/
/****************************************************************************************************************/



class PluginAmazon extends ClassJobsSitePlugin
{
    protected $siteName = 'Amazon';
    protected $strFilePath_HTMLFileDownloadScript = "PluginAmazon_downloadjobs.applescript";

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = array();
        $nodesTR= $objSimpHTML->find('tr');

        $nTRIndex = 0;
        while($nTRIndex < count($nodesTR))
        {
            $item = $this->getEmptyJobListingRecord();

            $tds = $nodesTR[$nTRIndex]->find('td');

            if(isset($tds) && count($tds) > 0)
            {

                $titleLink = $tds[1]->find("a");

                $item['job_title'] = $titleLink[0]->plaintext;
                $item['job_post_url'] = $titleLink[0]->href;
                $item['company'] = 'Amazon';

                $item['job_site'] = 'Amazon';
                $item['date_pulled'] = \Scooper\getTodayAsString();

                $item['job_id'] = trim(explode("/", $item['job_post_url'])[4]);

/*
BUGBUG:  strip_punctuation in strScrub returns null for some reason.

                $teamDetails = combineTextAllChildren($tds[4]);
                $strTeamCat = preg_replace("/\bTeam:\s{2,}(.*)\s{2,}Team Category:\s{2,}(.*)\s{2,}Short Description:\s{2,}(.*)/", "$1; $2", $teamDetails );
                $strTeamCat = \Scooper\strScrub($strTeamCat, ADVANCED_TEXT_CLEANUP);

                $item['job_site_category'] =  $strTeamCat . "; ". $tds[2]->plaintext ;
 */
                $item['job_site_category'] =  $tds[2]->plaintext ;

                $item['location'] = $tds[3]->plaintext ;


                $ret[] = $this->normalizeItem($item);

            }

            $nTRIndex = $nTRIndex + 1;

        }
        return $ret;
    }
    function parseJobsListForPageOrig($objSimpHTML)
    {
        $ret = array();
        $nodesTD= $objSimpHTML->find('tr td[class="expand footable-first-column"]');

        $nTDIndex = 0;
        while($nTDIndex < count($nodesTD))
        {
            if($nodesTD[$nTDIndex])
            {
                $item = $this->getEmptyJobListingRecord();

                \SimpleHtmlDom\dump_html_tree($nodesTD[$nTDIndex]);

                $titleObj = $nodesTD[$nTDIndex]->nextSibling();

                $item['job_title'] = $titleObj->firstChild()->plaintext;

                $item['job_post_url'] =$titleObj->firstChild()->href;
                $item['company'] = 'Amazon';

                $item['job_site'] = 'Amazon';
                $item['date_pulled'] = \Scooper\getTodayAsString();

                $item['job_id'] = trim(explode("/", $item['job_post_url'])[4]);

                $GLOBALS['logger']->logLine("AZID=".$item['job_id'], \Scooper\C__DISPLAY_ITEM_DETAIL__);


                $catObj = $titleObj->nextSibling();
                $item['job_site_category'] = $catObj->plaintext;

                $locObj = $catObj ->nextSibling();
                $item['location'] = $locObj->plaintext;


                $ret[] = $this->normalizeItem($item);

            }
            $nTDIndex = $nTDIndex + 5;


        }
        return $ret;
    }


}

?>
