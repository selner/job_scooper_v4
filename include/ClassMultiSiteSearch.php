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


require_once dirname(dirname(__FILE__))."/bootstrap.php";

class ClassMultiSiteSearch extends ClassJobsSiteCommon
{
    protected $siteName = 'Multisite';
    private $arrPluginClassesToRun = array();
    private $arrSearchesByPlugin = array();
    private $selenium = null;

    function __destruct()
    {
        if(!is_null($this->selenium) && ($GLOBALS['USERDATA']['selenium']['autostart'] == True)) {
            $this->selenium->terminate();
        }

        LogLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__);
    }

    function addMultipleSearches($arrSearches)
    {

        foreach($arrSearches as $search)
        {
            $this->arrSearchesToReturn[] = $search;
            $pluginClass = $search->getPluginClass();
            if($search->isSearchIncludedInRun())
                $this->arrSearchesByPlugin[$pluginClass][$search->getKey()] = $search->copy();
            else
            {
                LogLine($search->getJobSite() . " excluded, so skipping its searches.", \Scooper\C__DISPLAY_ITEM_START__);
                $srr = array('success' => true, 'details' => 'Search was excluded from run by a command line setting.', 'error_files' => array());
                $search->setSearchRunResult($srr);
            }
            $search->save();
        }


    }


    function updateJobsForAllPlugins()
    {
        $class = null;

        $retJobList = array();


        foreach(array_keys($this->arrSearchesByPlugin) as $className)
        {
            $searches = $this->arrSearchesByPlugin[$className];
            $class = new $className(\Scooper\getFullPathFromFileDetails($this->detailsMyFileOut), $searches);
            try
            {
                LogLine("Setting up " . count($searches) . " search(es) for ". $className . "...", \Scooper\C__DISPLAY_SECTION_START__);
                $class->addSearches($searches);

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
                        handleException($classError, $className . " requires Selenium but the service could not be started: %s", $raise = false);
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
                    handleException($classError, "Unable to run searches for ". $className . ": %s", $raise = false);

                $GLOBALS['logger']->logLine('ERROR:  ' . $className . ' failed due to an error:  ' . $err .PHP_EOL. 'Skipping it\'s remaining searches and continuing with other plugins.', \Scooper\C__DISPLAY_ERROR__);
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
