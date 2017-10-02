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
            LogLine("Setting up " . count($searches) . " search(es) for ". $sitename . "...", \C__DISPLAY_SECTION_START__);
            $plugin = getPluginObjectForJobSite($sitename);
            try
            {
                $plugin->addSearches($searches);
            }
            catch (Exception $classError)
            {
                handleException($classError, "Unable to add searches to {$GLOBALS['JOBSITE_PLUGINS'][$sitename]['class_name']} plugin: %s", $raise = false);
            }
            finally
            {
                LogLine("Search(es) added ". $sitename . ".", \C__DISPLAY_SECTION_END__);
            }

            try
            {
                LogLine("Downloading updated jobs on " . count($searches) . " search(es) for ". $sitename . "...", \C__DISPLAY_SECTION_START__);

                $arrResults = $plugin->getUpdatedJobsForAllSearches();
            }
            catch (Exception $classError)
            {
                handleException($classError, $GLOBALS['JOBSITE_PLUGINS'][$sitename]['class_name'] . " failed to download job postings: %s", $raise = false);
            }
            finally
            {
                LogLine("Downloads complete for ". $sitename . ".", \C__DISPLAY_SECTION_END__);
            }
        }


        return $arrResults;
    }

}
