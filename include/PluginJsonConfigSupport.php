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

abstract class ClassJSONJobsitePlugin extends ClassClientHTMLJobSitePlugin
{
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
            if(preg_match("/json$/", pathinfo($var, PATHINFO_EXTENSION)))
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
        $arrConfigData = get_object_vars($configData);

        $pluginData = array(
            'siteName' => null,
            'siteBaseURL' => null,
            'strBaseURLFormat' => null,
            'PageLimit' => null,
            'LocationType' => null,
            'AdditionalFlags' => array(),
            'nJobListingsPerPage' => C_JOB_MAX_RESULTS_PER_SEARCH,
            'arrListingTagSetup' => \Scooper\array_copy(ClassBaseHTMLJobSitePlugin::getEmptyListingTagSetup())
        );

        foreach(array_keys($arrConfigData) as $datakey)
        {
            switch($datakey)
        {

                case "AgentName":
                    $pluginData['siteName'] = $arrConfigData['AgentName'];
                    break;

                case "SourceURL":
                    $pluginData['strBaseURLFormat'] = $arrConfigData['SourceURL'];
                    break;

                case "BaseURL":
                    $pluginData['siteBaseURL'] = $arrConfigData['BaseURL'];
                    break;

                default:
                    if(array_key_exists($datakey, $pluginData))
                        $pluginData[$datakey] = $arrConfigData[$datakey];
                    break;
            }
        }

        if(array_key_exists("Pagination", $configData)) {

            if (array_key_exists("PageLimit", $configData->Pagination)) {
                $pluginData['nJobListingsPerPage'] = $configData->Pagination->PageLimit;
            }

            if (array_key_exists("PageDelaySeconds", $configData->Pagination)) {
                $pluginData['additionalLoadDelaySeconds'] = $configData->Pagination->PageLimit;
            }


            $pluginData['paginationType'] = strtoupper($configData->Pagination->Type);
                switch (strtoupper($configData->Pagination->Type))
                {
                    case 'NEXT-BUTTON':
                        $pluginData['arrListingTagSetup']['tag_next_button'] = array(
                            'selector' => $configData->Pagination->Selector,
                            'index' => $configData->Pagination->Index,
                            'type' => 'CSS'
                        );
                        break;

                    case 'LOAD-MORE':
                        $pluginData['arrListingTagSetup']['tag_load_more'] = array(
                            'selector' => $configData->Pagination->Selector,
                            'index' => $configData->Pagination->Index,
                            'type' => 'CSS'
                        );
                        break;

                    default:
                        break;
                }
        }


        if(array_key_exists("Collections", $configData) && !is_null($configData->Collections) && is_array($configData->Collections) && count($configData->Collections) > 0 && array_key_exists("Fields", $configData->Collections[0]))
        {
            foreach($configData->Collections as $coll)
            {
                foreach($coll->Fields as $field)
                {
                    $select = $field->Selector;
                    $name = $field->Name;
                    $attrib = null;
                    $index = null;

                    if(array_key_exists("Index", $field))
                    {
                        $index = $field->Index;
                    }


                    $type = $field->Type;
                    if ((strcasecmp($field->Extract, "HTML") == 0) || (strcasecmp($field->Extract, "ATTR") == 0)) {
                        $attrib = $field->Attribute;
                        $type = "CSS";
                    } elseif (strcasecmp($field->Extract, "TEXT") == 0) {
                        $attrib = "plaintext";
                        $type = "CSS";
                    } elseif (!is_null($field->Attribute)) {
                        $attrib = $field->Attribute;
                    }

                    $pluginData['arrListingTagSetup'][$name] = array(
                        'selector' => $select,
                        'index' => $index,
                        'return_attribute' => $attrib,
                        'type' => $type,
                        'field' => $field->Field,
                        'return_value_regex' => $field->Pattern,
                        'return_value_callback' => $field->Callback,
                        'callback_parameter' => $field->CallBackParameter
                    );
                }
            }
            if(isset($GLOBALS['logger']))
                $GLOBALS['logger']->logLine("Loaded " . countAssociativeArrayValues($pluginData) . " JSON configs for new plugins.", \Scooper\C__DISPLAY_ITEM_DETAIL__);

            return $pluginData;
        }

    }


    private function _instantiatePlugin_($pluginConfig)
    {
        $className = "Plugin" . $pluginConfig['siteName'];
        $setup = var_export($pluginConfig['arrListingTagSetup'], true);

        $extendsClass = "ClassJSONJobsitePlugin";
        if(array_key_exists("PluginExtendsClassName", $pluginConfig) && !is_null($pluginConfig['PluginExtendsClassName']) && strlen($pluginConfig['PluginExtendsClassName']))
        {
            $extendsClass = $pluginConfig['PluginExtendsClassName'];
        }

        $flags = "[" . join(", ", array_values($pluginConfig['AdditionalFlags'])) . "]";

        $evalStmt = "class $className extends {$extendsClass} { 
            protected \$siteName = \"{$pluginConfig['siteName']}\";
            protected \$siteBaseURL = \"{$pluginConfig['siteBaseURL']}\";
            protected \$strBaseURLFormat = \"{$pluginConfig['strBaseURLFormat']}\";
            protected \$typeLocationSearchNeeded = \"{$pluginConfig['LocationType']}\";
            protected \$additionalFlags = {$flags};
            protected \$additionalLoadDelaySeconds = 2;
            protected \$nJobListingsPerPage = \"{$pluginConfig['nJobListingsPerPage']}\";
            protected \$paginationType = \"{$pluginConfig['paginationType']}\";
            protected \$arrListingTagSetup = $setup;
            };
            
            ";

        eval($evalStmt);
        return new $className(null, null);
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
