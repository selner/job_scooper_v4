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


if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');



class ClassMultiSiteSearch extends ClassJobsSitePlugin
{
    protected $siteName = 'Multisite';
    protected $flagSettings = C__JOB_BASETYPE_NONE_NO_LOCATION_OR_KEYWORDS;


    function parseJobsListForPage($objSimpHTML)
    {
        throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch");
    }
    function parseTotalResultsCount($objSimpHTML) { throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch"); }

    function addMultipleSearches($arrSearches, $locSettingSets = null)
    {
        $this->arrSearchLocationSetsToRun = $locSettingSets;
        $this->arrSearchesToReturn = $arrSearches;
    }


    function getJobsForMyMultipleSearches($nDays = -1, $keywordSet = null)
    {
        $arrPluginClassesToRun = null;

        if(count($this->arrSearchesToReturn) >= 0)
        {
            foreach($this->arrSearchesToReturn as $search)
            {
                $strIncludeKey = 'include_'.strtolower($search['site_name']);

                if($GLOBALS['OPTS'][$strIncludeKey] == null || $GLOBALS['OPTS'][$strIncludeKey] == false)
                {
                    $GLOBALS['logger']->logLine($search['site_name'] . " excluded, so skipping its '" . $search['name'] . "' search.", \Scooper\C__DISPLAY_ITEM_START__);

                    continue;
                }

                $strSiteClass = $GLOBALS['DATA']['site_plugins'][strtolower($search['site_name'])]['class_name'];
                if($arrPluginClassesToRun[$strSiteClass] == null)
                {
                    $arrPluginClassesToRun[$strSiteClass] = array('class_name'=>$strSiteClass, 'site_name'=>$search['site_name'], 'searches' =>null);
                }
                $arrPluginClassesToRun[$strSiteClass]['searches'][] = $search;
            }

            $class = null;

            foreach($arrPluginClassesToRun as $classSearches)
            {
                $class = new $classSearches['class_name']($this->detailsMyFileOut['directory']);
                try
                {
                    $GLOBALS['logger']->logLine("Setting up " . count($classSearches['searches']) . " search(es) for ". $classSearches['site_name'] . "...", \Scooper\C__DISPLAY_SECTION_START__);
                    $class->addSearches($classSearches['searches']);
                    $class->getJobsForAllSearches($nDays);
                    addJobsToJobsList($this->arrLatestJobs, $class->getMyJobsList());
                    $class = null;
                }
                catch (Exception $classError)
                {
                    $GLOBALS['logger']->logLine('ERROR:  Unable to load the class for ' .$classSearches['site_name'] . '. Skipping it\'s searches and continuing with any others.', \Scooper\C__DISPLAY_ERROR__);
                    $GLOBALS['logger']->logLine('ERROR:  Search failure reason:  '.$classError->getMessage(), \Scooper\C__DISPLAY_ERROR__);
                    if($GLOBALS['OPTS']['DEBUG']) { throw $classError; }
                }
            }
        }
    }

}

?>
