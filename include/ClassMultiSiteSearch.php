<?php
/**
 * Copyright 2014-17 Bryan Selner
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

class ClassMultiSiteSearch extends ClassJobsSiteCommon
{
    protected $siteName = 'Multisite';
    private $arrPluginClassesToRun = array();
    private $selenium = null;

    function __destruct()
    {
        if(!is_null($this->selenium) && ($GLOBALS['USERDATA']['selenium']['autostart'] == True)) {
            $this->selenium->terminate();
        }

        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }
    }

    function addMultipleSearches($arrSearches, $locSettingSets = null)
    {
        $this->arrSearchesToReturn = $arrSearches;
    }


    private function _setPluginClassDataForAllSearches_()
    {
        $this->arrPluginClassesToRun = array();

        $arrSearchSites = array_column($this->arrSearchesToReturn, "site_name");
        foreach(array_unique($arrSearchSites) as $sitename)
        {
            $this->arrPluginClassesToRun[$sitename] = array_merge_recursive($GLOBALS['JOBSITE_PLUGINS'][strtolower($sitename)], array('searches' =>array()));
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


        $GLOBALS['logger']->logLine("Searches loaded and configured for run: " . getArrayValuesAsString(array_column($this->arrPluginClassesToRun, 'name')) . PHP_EOL . PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    function updateJobsForAllPlugins()
    {
        $this->_setPluginClassDataForAllSearches_();


        $class = null;

        $retJobList = array();

        foreach($this->arrPluginClassesToRun as $classPluginForSearch)
        {
            $class = new $classPluginForSearch['class_name'](\Scooper\getFullPathFromFileDetails($this->detailsMyFileOut), $classPluginForSearch);
            try
            {

                $GLOBALS['logger']->logLine("Setting up " . count($classPluginForSearch['searches']) . " search(es) for ". $classPluginForSearch['name'] . "...", \Scooper\C__DISPLAY_SECTION_START__);
                $class->addSearches($classPluginForSearch['searches']);

                $arrResults = $class->getUpdatedJobsForAllSearches();
                addJobsToJobsList($retJobList, $arrResults);
                $class = null;
            }
            catch (Exception $classError)
            {
                $err = $classError;
                if (($classError->getCode() == 4096 || $classError->getCode() == 0) && $class->isBitFlagSet(C__JOB_USE_SELENIUM))
                {
                    if(is_null($this->selenium))
                    {
                        handleException($classError, "Plugin ". $classPluginForSearch['class_name'] . " requires Selenium but the service could not be started: %s", $raise = false);
                    }
                    else {
                        try {
                            $this->selenium->killAllAndRestartSelenium();
                            $arrResults = $class->getUpdatedJobsForAllSearches();
                            addJobsToJobsList($retJobList, $arrResults);
                        } catch (Exception $classError) {
                            $err = $classError;
                        }
                    }
                }
                else
                    handleException($classError, "Unable to run searches for ". $classPluginForSearch['class_name'] . ": %s", $raise = false);

                $GLOBALS['logger']->logLine('ERROR:  Plugin ' .$classPluginForSearch['name'] . ' failed due to an error:  ' . $err .PHP_EOL. 'Skipping it\'s remaining searches and continuing with other plugins.', \Scooper\C__DISPLAY_ERROR__);
                $arrFail = getFailedSearchesByPlugin();
                if(countAssociativeArrayValues($arrFail) > 2) {
                    $arrWebDriverFail = array_filter($arrFail, function ($var) {

                        $arrFailureKeywords = array("curl", "WebDriverException", "WebDriverCurlException", "connection refused", "timed out");
                        foreach ($arrFailureKeywords as $failWord) {
                            if (stristr($var['search_run_result']['details'], $failWord) != false)
                                return true;
                        }
                        return false;
                    });
                    if(count($arrWebDriverFail) > 2 && !is_null($this->selenium))
                    {
                        $this->selenium->killAllAndRestartSelenium();

                        // BUGBUG:  Add a counter so we don't infinitely loop and restart forever
                        $this->updateJobsForAllPlugins();
                    }
                }
            }
            finally
            {
                $classPluginForSearch = null;
                $class = null;
            }
        }


        return $retJobList;
    }

}
