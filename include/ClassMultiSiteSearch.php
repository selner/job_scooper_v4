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
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');

const SELENIUM_STANDALONE_SERVER_JAR = "selenium-server-standalone-3.0.1.jar";

class ClassMultiSiteSearch extends ClassJobsSiteCommon
{
    protected $siteName = 'Multisite';
    protected $flagSettings = C__JOB_BASETYPE_NONE_NO_LOCATION_OR_KEYWORDS;
    protected $arrSearchLocationSetsToRun = null;
    private $arrPluginClassesToRun = array();
    function __destruct()
    {
        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }

        if(array_key_exists('selenium_sessionid', $GLOBALS) && isset($GLOBALS['selenium_sessionid']) && $GLOBALS['selenium_sessionid'] != -1)
        {
            try
            {
                $driver = RemoteWebDriver::createBySessionID($GLOBALS['selenium_sessionid']);
                $driver->quit();
                unset ($GLOBALS['selenium_sessionid']);
            }
            catch (Exception $ex) {
                if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Failed to terminate Selenium webdriver session.  You will need to manually shut it down.", \Scooper\C__DISPLAY_ERROR__); }
            }
        }

        if(array_key_exists('selenium_started', $GLOBALS) && isset($GLOBALS['selenium_started']) && $GLOBALS['selenium_started'] == true)
        {
            try
            {
                if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Sending server shutdown call to Selenium server...", \Scooper\C__DISPLAY_ITEM_RESULT__); }
                $cmd = "curl \"http://localhost:4444/selenium-server/driver?cmd=shutDownSeleniumServer\"";
                exec($cmd);

                unset ($GLOBALS['selenium_started']);
            }
            catch (Exception $ex) {
                if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Failed to send shutdown to Selenium server.  You will need to manually shut it down.", \Scooper\C__DISPLAY_ERROR__); }
            }
        }
    }

    function addMultipleSearches($arrSearches, $locSettingSets = null)
    {
        $this->arrSearchLocationSetsToRun = $locSettingSets;
        $this->arrSearchesToReturn = $arrSearches;
    }


    private function _setPluginClassDataForAllSearches_()
    {
        $this->arrPluginClassesToRun = array();

        $arrSearchSites = array_column($this->arrSearchesToReturn, "site_name");
        foreach(array_unique($arrSearchSites) as $sitename)
        {
            $this->arrPluginClassesToRun[$sitename] = array('class_name'=>$GLOBALS['JOBSITE_PLUGINS'][strtolower($sitename)]['class_name'], 'site_name'=>$sitename, 'searches' =>array());
        }

        if(count($arrSearchSites) >= 0)
        {
            foreach($this->arrSearchesToReturn as $searchDetails)
            {
                $strIncludeKey = 'include_'.strtolower($searchDetails['site_name']);

                $valInclude = \Scooper\get_PharseOptionValue($strIncludeKey);

                if(!isset($valInclude) || $valInclude == 0)
                {
                    $GLOBALS['logger']->logLine($searchDetails['site_name'] . " excluded, so skipping its searches.", \Scooper\C__DISPLAY_ITEM_START__);
                    if(array_key_exists($this->arrPluginClassesToRun, $searchDetails['site_name']))
                        unset($this->arrPluginClassesToRun[$searchDetails['site_name']]);
                    continue;
                }

                $this->arrPluginClassesToRun[$searchDetails['site_name']]['searches'][] = $searchDetails;
            }
        }


        $GLOBALS['logger']->logLine("Searches loaded and configured for run: " . getArrayValuesAsString($this->arrPluginClassesToRun) . PHP_EOL . PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    function updateJobsForAllPlugins()
    {
        $this->_setPluginClassDataForAllSearches_();


        $class = null;

        $retJobList = array();

        foreach($this->arrPluginClassesToRun as $classPluginForSearch)
        {
            $class = new $classPluginForSearch['class_name']($this->detailsMyFileOut['directory']);
            try
            {

                if($class->isBitFlagSet(C__JOB_USE_SELENIUM))
                {
                    if(!array_key_exists('selenium_started', $GLOBALS) || $GLOBALS['selenium_started'] != true)
                        {
                            $strCmdToRun = "java -jar \"" . __ROOT__ . "/lib/" . SELENIUM_STANDALONE_SERVER_JAR . "\" -role standalone  >/dev/null &";
                            $GLOBALS['logger']->logLine("Starting Selenium with command: '" . $strCmdToRun . "'", \Scooper\C__DISPLAY_ITEM_RESULT__);
                            exec($strCmdToRun);
                            $GLOBALS['selenium_started'] = true;
                            sleep(5);
                        }
                }


                $GLOBALS['logger']->logLine("Setting up " . count($classPluginForSearch['searches']) . " search(es) for ". $classPluginForSearch['site_name'] . "...", \Scooper\C__DISPLAY_SECTION_START__);
                $class->addSearches($classPluginForSearch['searches']);
                $arrResults = $class->getUpdatedJobsForAllSearches();
                addJobsToJobsList($retJobList, $arrResults);
                $class = null;
            }
            catch (Exception $classError)
            {
                $GLOBALS['logger']->logLine('ERROR:  Unable to load the class for ' .$classPluginForSearch['site_name'] . '. Skipping it\'s searches and continuing with any others.', \Scooper\C__DISPLAY_ERROR__);
                $GLOBALS['logger']->logLine('ERROR:  Search failure reason:  '.$classError->getMessage(), \Scooper\C__DISPLAY_ERROR__);
                if(isDebug()) { throw $classError; }
            }
        }

        return $retJobList;
    }

}

?>
