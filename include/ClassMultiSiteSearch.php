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



class ClassMultiSiteSearch extends ClassJobsSitePlugin
{
    protected $siteName = 'Multisite';
    protected $flagSettings = C__JOB_BASETYPE_NONE_NO_LOCATION_OR_KEYWORDS;


    function parseJobsListForPage($objSimpHTML)
    {
        throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch");
    }
    function parseTotalResultsCount($objSimpHTML) { throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch"); }

    function addSearches($arrSearches, $locSettingSets = null)
    {
        $this->arrSearchesToReturn = $arrSearches;
        $this->arrSearchLocationSetsToRun = $locSettingSets;
    }


    function getJobsForAllSearches($nDays = -1)
    {
        foreach($this->arrSearchesToReturn as $search)
        {
            $strIncludeKey = 'include_'.strtolower($search['site_name']);

            if($GLOBALS['OPTS'][$strIncludeKey] == null || $GLOBALS['OPTS'][$strIncludeKey] == 0)
            {
                $GLOBALS['logger']->logLine($search['site_name'] . " excluded, so skipping its '" . $search['search_name'] . "' search.", \Scooper\C__DISPLAY_ITEM_START__);

                continue;
            }

            $class = null;
            $GLOBALS['logger']->logLine("Running ". $search['site_name'] . " search: '" . $search['search_name']."'", \Scooper\C__DISPLAY_SECTION_START__);

            $strSiteClass = $GLOBALS['DATA']['site_plugins'][strtolower($search['site_name'])]['class_name'];
            $class = new $strSiteClass($this->detailsMyFileOut['full_file_path']);
            try
            {
                $class->addSearch($search, $this->arrSearchLocationSetsToRun);
                $class->getMyJobsForSearch($search, $nDays);
                $this->_addJobsToMyJobsList_($class->getMyJobsList());
                $class = null;
            }
            catch (ErrorException $classError)
            {
                $GLOBALS['logger']->logLine('ERROR:  Unable to load the search for ' .$search['site_name'] . '. Skipping '. $search['search_name'] .' search and continuing with any others.', \Scooper\C__DISPLAY_ERROR__);
                $GLOBALS['logger']->logLine('ERROR:  Search failure reason:  '.$classError->getMessage(), \Scooper\C__DISPLAY_ERROR__);
                if($GLOBALS['OPTS']['DEBUG']) { throw $classError; }
            }

        }
    }

}

?>
