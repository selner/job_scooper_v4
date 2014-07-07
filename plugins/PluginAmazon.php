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
    protected $flagSettings = C__JOB_BASETYPE_HTML_DOWNLOAD_FLAGS;

    function parseJobsListForPage($objSimpHTML)
    {
        $ret = array();
        $nodesTR= $objSimpHTML->find('tr');

        $nTRIndex = 0;
        while($nTRIndex < count($nodesTR))
        {
            $strTeamDetailsRemain = "";
            $teamDetails = "";
            $strTeamName = "";
            $strTeamCat = "";

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


                $teamDetails = combineTextAllChildren($tds[4]);
                $arrTeamDetails = explode("\n", $teamDetails);
                $nCount = 0;
                while($nCount < count($arrTeamDetails))
                {
                    switch(trim($arrTeamDetails[$nCount]))
                    {
                        case "Team:":
                            $strTeamName = $arrTeamDetails[$nCount + 1];
                            $nCount = $nCount + 2;
                            break;

                        case "Team Category:":
                            $strTeamCat = $arrTeamDetails[$nCount + 1];
                            $nCount = $nCount + 2;
                            break;

                        default:
                            $nCount = $nCount + 1;
                            break;
                    }

                }
//                $teamDetails= trim(preg_replace("/Location:\W{1,}(.*)\W{1,}/", "", $teamDetails));
//                $teamDetails = trim(preg_replace("/Short Description:\W{1,}.*/", "", $teamDetails));
//                $strTeamName = trim(preg_replace("/Team:\W{1,}(.*)\W{2,}[\w\W]{1,}/", "$1", $teamDetails));
//                if(strlen($strTeamName) > 0)
//                {
//                    $arrStringItems = explode($strTeamName, $teamDetails);
//                    $strTeamDetailsRemain = trim($arrStringItems[1]);
//                }
//                else
//                {
//                    $strTeamDetailsRemain = trim($teamDetails);
//                }
//
//                $strTeamCat = preg_replace("/Team Category:\W{1,}(.*){1,}\W{1,}/", "$1", $strTeamDetailsRemain );

                $strTeamCat = \Scooper\strScrub($strTeamCat, SIMPLE_TEXT_CLEANUP);
                $strTeamName = \Scooper\strScrub($strTeamName, SIMPLE_TEXT_CLEANUP);

                $item['job_site_category'] =  (strlen($strTeamName) > 0 ? $strTeamName . "; " : "") . (strlen($strTeamCat ) > 0 ? $strTeamCat . "; " : "") . $tds[2]->plaintext ;

                $item['location'] = $tds[3]->plaintext ;


                $ret[] = $this->normalizeItem($item);

            }

            $nTRIndex = $nTRIndex + 1;

        }
        return $ret;
    }


}

?>
