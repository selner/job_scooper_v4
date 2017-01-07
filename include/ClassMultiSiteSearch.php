<?php
/**
 * Copyright 2014-16 Bryan Selner
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
    protected $arrSearchLocationSetsToRun = null;
    private $arrPluginClassesToRun = array();
    function __destruct()
    {
        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }

        if(array_key_exists('selenium_started', $GLOBALS) && $GLOBALS['selenium_started'] === true)
        {
            try
            {
                // The only way to shutdown standalone server in 3.0 is by killing the local process.
                // Details: https://github.com/SeleniumHQ/selenium/issues/2852
                //
                $cmd='pid=`ps -eo pid,args | grep selenium-server | grep -v grep | cut -c1-6`; if [ "$pid" ]; then kill -9 $pid; echo "Killed Selenium process #"$pid; else echo "Selenium server is not running."; fi';
                if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Killing Selenium server process with command \"" .$cmd ."\"", \Scooper\C__DISPLAY_NORMAL__); }
                doExec($cmd);
                $GLOBALS['selenium_started'] = false;
            }
            catch (Exception $ex) {
                if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Failed to send shutdown to Selenium server.  You will need to manually shut it down.", \Scooper\C__DISPLAY_ERROR__); }
            }
        }
        else
            if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Skipping Selenium server shutdown since we did not start it.  You will need to manually shut it down.", \Scooper\C__DISPLAY_ERROR__); }

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


        $GLOBALS['logger']->logLine("Searches loaded and configured for run: " . getArrayValuesAsString($this->arrPluginClassesToRun) . PHP_EOL . PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);

    }

    function updateJobsForAllPlugins()
    {
        $this->_setPluginClassDataForAllSearches_();


        $class = null;

        $retJobList = array();

        foreach($this->arrPluginClassesToRun as $classPluginForSearch)
        {
            $class = new $classPluginForSearch['class_name']($GLOBALS['USERDATA']['directories']['stage1'], $classPluginForSearch);
            try
            {

                if($class->isBitFlagSet(C__JOB_USE_SELENIUM)) {
//                    $cmd = 'ps -eo pid,args | grep selenium-server | grep -v grep | echo `sed \'s/.*port \([0-9]*\).*/\1/\'`';
                    // $cmd = 'ps -eo pid,args | grep selenium-server | grep -v grep | ps -p `awk \'NR!=1 {print $2}\'` -o command=';
//                    $cmd = 'lsof -i tcp:' . $GLOBALS['USERDATA']['selenium']['port'] . '| ps -o command= -p `awk \'NR != 1 {print $2}\'` | sed -n 2p';
                    $cmd = 'lsof -i tcp:' . $GLOBALS['USERDATA']['selenium']['port'];

                    $seleniumStarted = false;
                    $pscmd = doExec($cmd);
                    if (!is_null($pscmd) && (is_array($pscmd) && count($pscmd) > 1))
                    {
                        $pidLine = preg_split('/\s+/', $pscmd[1]);
                        if(count($pidLine) >1)
                        {
                            $pid = $pidLine[1];
                            $cmd = 'ps -o command= -p ' . $pid;
                            $pscmd = doExec($cmd);

                            if(preg_match('/selenium/', $pscmd) !== false)
                            {
                                $seleniumStarted = true;
                                $GLOBALS['logger']->logLine("Selenium is already running on port " . $GLOBALS['USERDATA']['selenium']['port'] . ".  Skipping startup of server.", \Scooper\C__DISPLAY_WARNING__);
                            }
                            else
                            {
                                $msg = "Error: port " . $GLOBALS['USERDATA']['selenium']['port'] . " is being used by process other than Selenium (" . var_export($pscmd, true) . ").  Aborting.";
                                $GLOBALS['logger']->logLine($msg, \Scooper\C__DISPLAY_ERROR__);
                                throw new Exception($msg);

                            }
                        }
                    }

                    if($seleniumStarted === false)
                    {
                        if($GLOBALS['USERDATA']['selenium']['autostart'] == 1 && (array_key_exists('selenium_started', $GLOBALS) === false || $GLOBALS['selenium_started'] !== true))
                        {
                            $strCmdToRun = "java ";
                            if(array_key_exists('prefix_switches', $GLOBALS['USERDATA']['selenium']))
                                $strCmdToRun = $GLOBALS['USERDATA']['selenium']['prefix_switches'];

                            $strCmdToRun .= " -jar \"" . $GLOBALS['USERDATA']['selenium']['jar'] . "\" -port " . $GLOBALS['USERDATA']['selenium']['port'] . " ";
                            if(array_key_exists('prefix_switches', $GLOBALS['USERDATA']['selenium']))
                                $strCmdToRun .= $GLOBALS['USERDATA']['selenium']['postfix_switches'];

                                $strCmdToRun .= " >/dev/null &";

                            $GLOBALS['logger']->logLine("Starting Selenium with command: '" . $strCmdToRun . "'", \Scooper\C__DISPLAY_ITEM_RESULT__);
                            doExec($strCmdToRun);
                            $GLOBALS['selenium_started'] = true;
                            sleep(5);
                        }
                        else
                            throw new Exception("Selenium is not running and was not set to autostart. Cannot continue without an instance of Selenium running.");

                    }

                }

                $GLOBALS['logger']->logLine("Setting up " . count($classPluginForSearch['searches']) . " search(es) for ". $classPluginForSearch['name'] . "...", \Scooper\C__DISPLAY_SECTION_START__);
                $class->addSearches($classPluginForSearch['searches']);
                $arrResults = $class->getUpdatedJobsForAllSearches();
                addJobsToJobsList($retJobList, $arrResults);
                $class = null;
            }
            catch (Exception $classError)
            {
                $GLOBALS['logger']->logLine('ERROR:  Unable to load the class for ' .$classPluginForSearch['name'] . '. Skipping it\'s searches and continuing with any others.', \Scooper\C__DISPLAY_ERROR__);
                $GLOBALS['logger']->logLine('ERROR:  Search failure reason:  '.$classError->getMessage(), \Scooper\C__DISPLAY_ERROR__);
                if(isDebug()) { throw $classError; }
            }
        }


        return $retJobList;
    }

}

?>
