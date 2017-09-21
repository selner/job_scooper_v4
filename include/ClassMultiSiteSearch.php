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

class ClassMultiSiteSearch
{
    protected $siteName = 'Multisite';
    private $arrSearchesByJobSite = array();
    private $selenium = null;
    private $arrSearchesToReturn = array();

    function __destruct()
    {
        if(!is_null($this->selenium) && ($GLOBALS['USERDATA']['selenium']['autostart'] == True)) {
            $this->selenium->terminate();
        }

        LogLine("Closing ".$this->siteName." instance of class " . get_class($this), \C__DISPLAY_ITEM_START__);
    }

    function updateJobsForAllJobSites($arrSearchesByJobSite)
    {
        $arrResults = array();

        $this->arrSearchesByJobSite = $arrSearchesByJobSite;
        foreach(array_keys($this->arrSearchesByJobSite) as $sitename)
        {
            $searches = $this->arrSearchesByJobSite[$sitename];
            $plugin = getPluginObjectForJobSite($sitename);
            try
            {
                LogLine("Setting up " . count($searches) . " search(es) for ". $sitename . "...", \C__DISPLAY_SECTION_START__);
                $plugin->addSearches($searches);

                $arrResults = $plugin->getUpdatedJobsForAllSearches();
            }
            catch (Exception $classError)
            {
                $err = $classError;
                if (($classError->getCode() == 4096 || $classError->getCode() == 0) && $plugin->isBitFlagSet(C__JOB_USE_SELENIUM))
                {
                    if(is_null($this->selenium))
                    {
                        handleException($classError, $GLOBALS['JOBSITE_PLUGINS'][$sitename]['class_name'] . " requires Selenium but the service could not be started: %s", $raise = false);
                    }
                    else {
                        try {
                            $this->selenium->killAllAndRestartSelenium();
                            $arrResults = $plugin->getUpdatedJobsForAllSearches();
                        } catch (Exception $classError) {
                            $err = $classError;
                        }
                    }
                }
                else
                    handleException($classError, "Unable to run searches for ". $sitename . ": %s", $raise = false);

//                LogLine('ERROR:  ' . $sitename . ' failed due to an error:  ' . $err .PHP_EOL. 'Skipping it\'s remaining searches and continuing with other plugins.', \C__DISPLAY_ERROR__);
//                $arrFail = getFailedSearchesByPlugin();
//                if(countAssociativeArrayValues($arrFail) > 2) {
//                    $arrWebDriverFail = array_filter($arrFail, function ($var) {
//
//                        $arrFailureKeywords = array("curl", "WebDriverException", "WebDriverCurlException", "connection refused", "timed out");
//                        foreach ($arrFailureKeywords as $failWord) {
//                            if (stristr($var['run_error_details']['details'], $failWord) != false)
//                                return true;
//                        }
//                        return false;
//                    });
//                    if(count($arrWebDriverFail) > 2 && !is_null($this->selenium))
//                    {
//                        $this->selenium->killAllAndRestartSelenium();
//
//                        // BUGBUG:  Add a counter so we don't infinitely loop and restart forever
//                        $this->updateJobsForAllPlugins();
//                    }
//                }
            }
        }


        return $arrResults;
    }

}
