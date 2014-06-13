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


    function getDaysURLValue($nDays)
    {
        if($nDays > 1)
        {
            __debug__printLine($this->siteName ." jobs can only be pulled for, at most, 1 day.  Ignoring number of days value and just pulling current listings.", C__DISPLAY_WARNING__);

        }
        return C__JOB_PAGECOUNT_NOTAPPLICABLE__;

    }

    function parseTotalResultsCount($objSimpHTML)
    {
        return C__JOB_ITEMCOUNT_UNKNOWN__;
    }



    function parseJobsListForPage($objSimpHTML)

    {
        $ret = array();
        $nodesTD= $objSimpHTML->find('tr td[class="expand footable-first-column"]');

        $nTDIndex = 0;
        while($nTDIndex < count($nodesTD))
        {
            if($nodesTD[$nTDIndex])
            {
                $item = $this->getEmptyJobListingRecord();

                $titleObj = $nodesTD[$nTDIndex]->nextSibling();

                $item['job_title'] = $titleObj->firstChild()->plaintext;

                $item['job_post_url'] =$titleObj->firstChild()->href;
                $item['company'] = 'Amazon';

                $item['job_site'] = 'Amazon';
                $item['date_pulled'] = getTodayAsString();

                $item['job_id'] = trim(explode("/", $item['job_post_url'])[4]);

                $catObj = $titleObj->nextSibling();
                $item['job_site_category'] = $catObj->plaintext;

                $locObj = $catObj ->nextSibling();
                $item['location'] = $locObj->plaintext;

                $briefObj = $locObj ->nextSibling();

                if($this->is_IncludeBrief() == true)
                {
                    $brief  = trim($briefObj->plaintext);
                    $arrBrief = explode("Short Description", $brief);
                    $item['brief_description'] = $arrBrief[1];
                }

                $ret[] = $this->normalizeItem($item);

            }
            $nTDIndex = $nTDIndex + 5;


        }
        return $ret;
    }


}

?>
