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


require_once dirname(__FILE__) . '/../include/ClassJobsSite.php';

class ClassJobsSiteNoActualSite extends ClassJobsSite
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

class ClassMultiSiteSearch extends ClassJobsSite
{
    protected $siteName = 'Multisite';
    protected $flagAutoMarkListings = false; // All the called classes do it for us already

    function parseJobsListForPage($objSimpHTML)
    {
        throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch");
    }
    function parseTotalResultsCount($objSimpHTML) { throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch"); }

    function __construct($bitFlags = null, $strOutputDirectory = null, $arrSearches = null)
    {
        parent::__construct($bitFlags, $strOutputDirectory);
        if($arrSearches != null)
        {
           foreach($arrSearches as $search)
           {
                $class = null;
                $strSite = strtolower($search['site_name']);
                $strSiteClass = $this->arrSiteClasses[$strSite];
                $class = new $strSiteClass($bitFlags, $strOutputDirectory);
           }

        }
    }

}
