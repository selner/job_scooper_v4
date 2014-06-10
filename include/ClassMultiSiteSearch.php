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
    protected $flagAutoMarkListings = false; // All the called classes do it for us already

    function parseJobsListForPage($objSimpHTML)
    {
        throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch");
    }
    function parseTotalResultsCount($objSimpHTML) { throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch"); }



    function getJobsForAllSearches($nDays = -1)
    {
        foreach($this->arrSearchesToReturn as $search)
        {
            $strIncludeKey = 'include_'.strtolower($search['site_name']);

            if($GLOBALS['OPTS'][$strIncludeKey] == null || $GLOBALS['OPTS'][$strIncludeKey] == 0)
            {
                __debug__printLine($search['site_name'] . " excluded, so skipping its '" . $search['search_name'] . "' search.", C__DISPLAY_ITEM_START__);

                continue;
            }

            $class = null;
            __debug__printLine("Running ". $search['site_name'] . " search: '" . $search['search_name']."'", C__DISPLAY_SECTION_START__);

            $strSiteClass = $GLOBALS['site_plugins'][strtolower($search['site_name'])]['class_name'];
            $class = new $strSiteClass($this->getMyBitFlags(), $this->strOutputFolder);
            try
            {
                $class->addSearch($search);
                $class->getMyJobsForSearch($search, $nDays);
                $this->_addJobsToMyJobsList_($class->getMyJobsList());
            }
            catch (ErrorException $classError)
            {
                __debug__printLine('ERROR:  Unable to load the search for ' .$search['site_name'] . '. Skipping '. $search['search_name'] .' search and continuing with any others.', C__DISPLAY_ERROR__);
                __debug__printLine('ERROR:  Search failure reason:  '.$classError->getMessage(), C__DISPLAY_ERROR__);
            }

        }
    }

}

?>
