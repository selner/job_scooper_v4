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
namespace Jobscooper\Manager;

require_once __ROOT__ . "/bootstrap.php";

class JsonSitePluginManager
{
    function init()
    {
        return;
    }
    private function _loadPluginConfigFileData_()
    {
        $jsonconfigsdir = __ROOT__ . "/plugins/json_plugins";
        $arrAddedPlugins = null;
        print('Getting JSON plugin list...'. PHP_EOL);
        $filelist = array_diff(scandir($jsonconfigsdir), array(".", ".."));
        $filelist = array_filter($filelist, function ($var) {
            if(preg_match("/json$/", pathinfo($var, PATHINFO_EXTENSION)))
                return true;
            return false;
        });

        foreach($filelist as $f) {
            $this->pluginConfigs[$f] = loadJSON($jsonconfigsdir . "/" . $f, null, true);
        }

    }

    private function _parsePluginConfig_($arrConfigData)
    {

        $pluginData = array(
            'siteName' => getArrayItem('AgentName', $arrConfigData),
            'siteBaseURL' => getArrayItem('BaseURL', $arrConfigData),
            'strBaseURLFormat' => getArrayItem('SourceURL', $arrConfigData),
            'countryCodes' => getArrayItem('CountryCodes', $arrConfigData),
            'arrListingTagSetup' => \Jobscooper\Plugins\Base\SimplePlugin::getEmptyListingTagSetup()
        );

        if(array_key_exists("Pagination", $arrConfigData)) {

            if (array_key_exists("PageLimit", $arrConfigData['Pagination'])) {
                $pluginData['nJobListingsPerPage'] = $arrConfigData['Pagination']['PageLimit'];
            }

            if (array_key_exists("PageDelaySeconds", $arrConfigData['Pagination'])) {
                $pluginData['additionalLoadDelaySeconds'] = $arrConfigData['Pagination']['PageLimit'];
            }

            if (array_key_exists("Type", $arrConfigData['Pagination'])) {

                $pluginData['paginationType'] = strtoupper($arrConfigData['Pagination']['Type']);
                switch (strtoupper($arrConfigData['Pagination']['Type'])) {
                    case 'NEXT-BUTTON':
                        $pluginData['arrListingTagSetup']['tag_next_button'] = array(
                            'selector' => $arrConfigData['Pagination']['Selector'],
                            'index' => $arrConfigData['Pagination']['Index'],
                            'type' => 'CSS'
                        );
                        break;

                    case 'LOAD-MORE':
                        $pluginData['arrListingTagSetup']['tag_load_more'] = array(
                            'selector' => $arrConfigData['Pagination']['Selector'],
                            'index' => $arrConfigData['Pagination']['Index'],
                            'type' => 'CSS'
                        );
                        break;

                    default:
                        break;
                }
            }
        }
        foreach(array_keys($arrConfigData) as $datakey)
        {
            if(!array_key_exists($datakey, $pluginData) && !in_array($datakey, array("Collections", "Fields")))
                $pluginData[$datakey] = getArrayItem($datakey, $arrConfigData);
        }


        if(array_key_exists("Collections", $arrConfigData) && !is_null($arrConfigData['Collections']) && is_array($arrConfigData['Collections']) && count($arrConfigData['Collections']) > 0 && array_key_exists("Fields", $arrConfigData['Collections'][0]))
        {
            foreach($arrConfigData['Collections'] as $coll)
            {
                foreach($coll['Fields'] as $field)
                {
                    $select = getArrayItem('Selector', $field);
                    $name = getArrayItem('Name', $field);
                    $attrib = null;

                    $index = getArrayItem('Index', $field);
                    $type = getArrayItem('Type', $field);


                    if ((strcasecmp($field['Extract'], "HTML") == 0) || (strcasecmp($field['Extract'], "ATTR") == 0)) {
                        $attrib = getArrayItem('Attribute', $field);
                        $type = "CSS";
                    } elseif (strcasecmp($field['Extract'], "TEXT") == 0) {
                        $attrib = "plaintext";
                        $type = "CSS";
                    } elseif (!is_null($field['Attribute'])) {
                        $attrib = getArrayItem('Attribute', $field);
                    }

                    $pluginData['arrListingTagSetup'][$name] = array(
                        'selector' => $select,
                        'index' => $index,
                        'return_attribute' => $attrib,
                        'type' => $type,
                        'field' => getArrayItem('Field', $field),
                        'value' => getArrayItem('Value', $field),
                        'return_value_regex' => getArrayItem('Pattern', $field),
                        'return_value_callback' => getArrayItem('Callback', $field),
                        'callback_parameter' => getArrayItem('CallbackParameter', $field)
                    );
                }
            }
        }

        if(isset($GLOBALS['logger']))
            $GLOBALS['logger']->logLine("Loaded JSON config for new plugin: " . $pluginData['siteName'], \C__DISPLAY_ITEM_DETAIL__);

        return $pluginData;

    }


    private function _instantiatePlugin_($pluginConfig)
    {
        $className = "Plugin" . $pluginConfig['siteName'];
        $setup = var_export($pluginConfig['arrListingTagSetup'], true);

        $extendsClass = "Jobscooper\Plugins\Base\AjaxHtmlSimplePlugin";
        if(array_key_exists("PluginExtendsClassName", $pluginConfig) && !is_null($pluginConfig['PluginExtendsClassName']) && strlen($pluginConfig['PluginExtendsClassName']))
        {
            $extendsClass = $pluginConfig['PluginExtendsClassName'];
        }

        $flags = "null";
        if(array_key_exists('AdditionalFlags', $pluginConfig))
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
            protected \$arrListingTagSetup = {$setup};
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
            $arrPluginSetups[cleanupSlugPart($configData['AgentName'])] = $retSetup;

            if(isset($GLOBALS['logger']))
                print("Initializing JSON plugin for " . $retSetup['siteName'] . PHP_EOL);
            $this->_instantiatePlugin_($retSetup);

        }


    }
    protected $pluginConfigs = Array();
}
