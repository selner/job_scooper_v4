<?php
/**
 * Copyright 2014-15 Bryan Selner
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

    function __destruct()
    {
        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }

        if(array_key_exists('selenium_sessionid', $GLOBALS) && isset($GLOBALS['selenium_sessionid']) && $GLOBALS['selenium_sessionid'] != -1)
        {
            $driver = RemoteWebDriver::createBySessionID($GLOBALS['selenium_sessionid']);
            $driver->quit();
            unset ($GLOBALS['selenium_sessionid']);
        }

        if(array_key_exists('selenium_started', $GLOBALS) && isset($GLOBALS['selenium_started']) && $GLOBALS['selenium_started'] == true)
        {
            if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Sending server shutdown call to Selenium server...", \Scooper\C__DISPLAY_ITEM_RESULT__); }
            $cmd = "curl \"http://localhost:4444/selenium-server/driver?cmd=shutDownSeleniumServer\" >/dev/null &";
            exec($cmd);
            unset ($GLOBALS['selenium_started']);
        }
    }

    function parseJobsListForPage($objSimpHTML)
    {
        throw new ErrorException("parseJobsListForPage not supported for class ClassMultiSiteSearch");
    }
    function parseTotalResultsCount($objSimpHTML) { throw new ErrorException("parseTotalResultsCount not supported for class ClassMultiSiteSearch"); }

    function addMultipleSearches($arrSearches, $locSettingSets = null)
    {
        $this->arrSearchLocationSetsToRun = $locSettingSets;
        $this->arrSearchesToReturn = $arrSearches;
    }

    function getJobsForMyMultipleSearches()
    {
        $arrPluginClassesToRun = null;

        $arrSearchesToReturn = $this->arrSearchesToReturn;
        if(count($arrSearchesToReturn) >= 0)
        {
            shuffle($arrSearchesToReturn);
            foreach($arrSearchesToReturn as $search)
            {
                $strIncludeKey = 'include_'.strtolower($search['site_name']);

                $valInclude = \Scooper\get_PharseOptionValue($strIncludeKey);

                if(!isset($valInclude) || $valInclude == 0)
                {
                    $GLOBALS['logger']->logLine($search['site_name'] . " excluded, so skipping its '" . $search['name'] . "' search.", \Scooper\C__DISPLAY_ITEM_START__);

                    continue;
                }

                $strSiteClass = $GLOBALS['DATA']['site_plugins'][strtolower($search['site_name'])]['class_name'];
                if(!isset($arrPluginClassesToRun[$strSiteClass]))
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

                    if($class->isBitFlagSet(C__JOB_USE_SELENIUM))
                    {
                        if(!array_key_exists('selenium_started', $GLOBALS) || $GLOBALS['selenium_started'] != true)
                            {
                                $strCmdToRun = "java -jar \"" . __ROOT__ . "/lib/selenium-server-standalone-3.0.0-beta4.jar\"  >/dev/null &";
                                exec($strCmdToRun);
                                $GLOBALS['selenium_started'] = true;
                                sleep(5);
                            }
                    }


                    $GLOBALS['logger']->logLine("Setting up " . count($classSearches['searches']) . " search(es) for ". $classSearches['site_name'] . "...", \Scooper\C__DISPLAY_SECTION_START__);
                    $class->addSearches($classSearches['searches']);
                    $class->getJobsForAllSearches();
                    addJobsToJobsList($this->arrLatestJobs, $class->getMyJobsList());
                    $class = null;
                }
                catch (Exception $classError)
                {
                    $GLOBALS['logger']->logLine('ERROR:  Unable to load the class for ' .$classSearches['site_name'] . '. Skipping it\'s searches and continuing with any others.', \Scooper\C__DISPLAY_ERROR__);
                    $GLOBALS['logger']->logLine('ERROR:  Search failure reason:  '.$classError->getMessage(), \Scooper\C__DISPLAY_ERROR__);
                    if(isDebug()) { throw $classError; }
                }
            }
        }
    }

}

?>
