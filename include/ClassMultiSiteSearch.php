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


require_once dirname(__FILE__) . '/../include/ClassJobsSitePlugin.php';

class ClassJobsSitePluginNoActualSite extends ClassJobsSitePlugin
{
    protected $siteName = 'ClassNoJobsSite';

    function parseJobsListForPage($objSimpHTML)
    {
        throw new ErrorException("parseJobsListForPage not supported for class ClassNoJobsSite");
    }
    function parseTotalResultsCount($objSimpHTML)
    {
        throw new ErrorException("parseTotalResultsCount not supported for class ClassNoJobsSite");
    }
}

class ClassMultiSiteSearch extends ClassJobsSitePlugin
{
    protected $siteName = 'Multisite';
    protected $flagAutoMarkListings = false; // All the called classes do it for us already

    function parseJobsListForPage($objSimpHTML)
    {
        throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch");
    }
    function parseTotalResultsCount($objSimpHTML) { throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch"); }


    /**
     * TODO:  DOC
     *
     *
     * @param  string TODO DOC
     * @param  string TODO DOC
     * @return string TODO DOC
     */
    function getJobsForAllSearches($nDays = -1)
    {
        foreach($this->arrSearchesToReturn as $search)
        {
            $strIncludeKey = 'include_'.strtolower($search['site_name']);

            if($GLOBALS['OPTS'][$strIncludeKey] == null || $GLOBALS['OPTS'][$strIncludeKey] == 0)
            {
                __debug__printLine($search['site_name'] . " excluded, so skipping its '" . $search['search_name'] . "' search.", C__DISPLAY_WARNING__);

                continue;
            }

            $class = null;
            $nLastCount = count($this->arrLatestJobs);
            __debug__printLine("Running ". $search['site_name'] . " search '" . $search['search_name'], C__DISPLAY_ITEM_START__);

            $strSite = strtolower($search['site_name']);

            $strSiteClass = $GLOBALS['site_plugins'][$strSite]['class_name'];
            try
            {

                $class = new $strSiteClass;
                $class->getMyJobsForSearch($search, $nDays);
                $this->_addJobsToMyJobsList_($class->getMyJobsList());
            }
            catch (ErrorException $classError)
            {
                throw new ErrorException("Error: Plugin for site [" .$search['site_name'] . "] could not be found. [" .$classError['message']."]");
            }

        }
    }

}
