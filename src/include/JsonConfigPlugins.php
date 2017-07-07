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
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');


abstract class ClassJSONJobsitePlugin extends ClassClientHTMLJobSitePlugin
{
    protected $additionalFlags = [C__JOB_CLIENTSIDE_INFSCROLLPAGE_NOCONTROL, C__JOB_ITEMCOUNT_NOTAPPLICABLE__, C__JOB_SETTINGS_GET_ALL_JOBS_UNFILTERED, C__JOB_PAGECOUNT_NOTAPPLICABLE__, C__JOB_DAYS_VALUE_NOTAPPLICABLE__, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED, C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED  ];
}

class JSONPlugins
{
    function init()
    {
        return;
    }
    private function _loadPluginConfigFileData_()
    {
        $jsonconfigsdir = dirname(dirname(__FILE__)) . "/plugins/json_plugins";
        $arrAddedPlugins = null;
        print('Getting job site plugin list...'. PHP_EOL);
        $filelist = array_diff(scandir($jsonconfigsdir), array(".", ".."));
        $filelist = array_filter($filelist, function ($var) {
            if(strtolower(pathinfo($var, PATHINFO_EXTENSION)) == "json")
                return true;
            return false;
        });

        foreach($filelist as $f) {
            $text = file_get_contents($jsonconfigsdir . "/" . $f);
            $configData = json_decode($text);
            $this->pluginConfigs[$f] = $configData;
        }

    }

    private function _parsePluginConfig_($configData)
    {
        $pluginData = array(
            'siteName' => null,
            'siteBaseURL' => null,
            'arrListingTagSetup' => \Scooper\array_copy(ClassBaseHTMLJobSitePlugin::getEmptyListingTagSetup())
        );

        if(array_key_exists("AgentName", $configData))
        {
            $pluginData['siteName'] = $configData->AgentName;
        }

        if(array_key_exists("SourceURL", $configData))
        {
            $pluginData['siteBaseURL'] = $configData->SourceURL;
        }

        if(array_key_exists("Collections", $configData) && !is_null($configData->Collections) && is_array($configData->Collections) && count($configData->Collections) > 0 && array_key_exists("Fields", $configData->Collections[0]))
        {
            foreach($configData->Collections as $coll)
            {
                foreach($coll->Fields as $field)
                {
                    $keyFieldArray = "arrListingTagSetup";
                    
                    $select = $field->Selector;
                    $name = $field->Name;
                    $attrib = null;

                    if(!is_null($select)) {
                        if ((strcasecmp($field->Extract, "HTML") == 0) || (strcasecmp($field->Extract, "ATTR") == 0)) {
                            $attrib = $field->Attribute;
                        } elseif (strcasecmp($field->Extract, "TEXT") == 0) {
                            $attrib = "plaintext";
                        } elseif (!is_null($field->Attribute)) {
                            $attrib = $field->Attribute;
                        }

                        $pluginData['arrListingTagSetup'][$name] = array(
                            'selector' => $select,
                            'return_attribute' => $attrib,
                            'type' => 'CSS'
                        );

                        if(array_key_exists("Index", $field))
                        {
                            $pluginData[$keyFieldArray][$name]['index'] = $field->Index;
                        }
                    }
                    elseif (strcasecmp($field->Extract, "REGEX") == 0) {
                        $pluginData['arrListingTagSetup'][$name] = array(
                            'pattern' => $field->Pattern,
                            'field' => $field->Field,
                            'index' => $field->Index,
                            'type' => 'REGEX'
                        );
                    }
                }
            }
            if(isset($GLOBALS['logger']))
                $GLOBALS['logger']->logLine("Loaded " . countAssociativeArrayValues($pluginData) . " JSON configs for new plugins.", \Scooper\C__DISPLAY_ITEM_DETAIL__);

            return $pluginData;
        }

    }

    private function _instantiatePlugin_($pluginConfig)
    {
        $class = null;
        $className = "Plugin" . $pluginConfig['siteName'];
        $setup = var_export($pluginConfig['arrListingTagSetup'], true);
        $evalStmt = "class $className extends ClassJSONJobsitePlugin { 
            protected \$siteName = \"{$pluginConfig['siteName']}\";
            protected \$siteBaseURL = \"{$pluginConfig['siteBaseURL']}\";
            protected \$nJobListingsPerPage = C_JOB_MAX_RESULTS_PER_SEARCH;
            protected \$arrListingTagSetup = $setup;
            };";

        eval($evalStmt);
        $classinst = new $className(null, null);

        return $class;
    }
    function __construct($strBaseDir = null)
    {
        $this->_loadPluginConfigFileData_();
        $arrPluginSetups = array();

        foreach($this->pluginConfigs as $configData) {
            $retSetup = $this->_parsePluginConfig_($configData);
            $arrPluginSetups[$retSetup['siteName']] = $retSetup;

            if(isset($GLOBALS['logger']))
                print("Initializing JSON plugin for " . $retSetup['siteName'] . PHP_EOL);
            $this->_instantiatePlugin_($retSetup);

        }


    }
    protected $pluginConfigs = Array();
}
