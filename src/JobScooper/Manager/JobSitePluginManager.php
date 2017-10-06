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
namespace JobScooper\Manager;



use Exception;

class JobSitePluginManager
{
    protected $_dirPluginsRoot = null;
    protected $_dirJsonConfigs = null;
    protected $_configJsonFiles = array();
    protected $_jsonPluginSetups = array();

    function __construct($pathPluginDirectory)
    {
        if(is_null($pathPluginDirectory) || strlen($pathPluginDirectory) == 0)
            throw new Exception("Path to plugins source directory was not set.");
        
        $this->_dirPluginsRoot = realpath($pathPluginDirectory . DIRECTORY_SEPARATOR);

        $this->_dirJsonConfigs = realpath($this->_dirPluginsRoot . DIRECTORY_SEPARATOR . "json_plugins" . DIRECTORY_SEPARATOR);

        $this->_loadJsonPluginConfigFiles_();
        $this->_initializeAllPlugins();


    }
    
    
    function _initializeAllPlugins()
    {
        $arrAddedPlugins = null;
        LogLine('Initializing all job site plugins...', C__DISPLAY_ITEM_START__);

        LogLine('Generating classes for ' . count($this->_jsonPluginSetups) .' JSON-loaded plugins...', C__DISPLAY_ITEM_DETAIL__);
        foreach(array_keys($this->_jsonPluginSetups) as $agentkey) {
            LogLine("Generating plugin class for " . $agentkey, C__DISPLAY_ITEM_DETAIL__);
            eval($this->_jsonPluginSetups[$agentkey]['evalcode']);
//            return new $className(null, null);
        }

        LogLine('Instantiating objects for all job site plugins...', C__DISPLAY_ITEM_DETAIL__);
        $classList = get_declared_classes();
        sort($classList);
        $pluginClasses = array_filter($classList, function ($class) {
            return (stripos($class, "Plugin") !== false) && stripos($class, "\\Lib\\") === false && in_array("JobScooper\Plugins\Interfaces\IJobSitePlugin", class_implements($class));
        });

        foreach($pluginClasses as $class)
        {
            $namekey = strtolower(str_replace("Plugin", "", $class));
            findOrCreateJobSitePlugin($namekey);
        }


        LogLine("Added " . count($GLOBALS['JOBSITE_PLUGINS']) ." plugins: " . getArrayValuesAsString(array_column($GLOBALS['JOBSITE_PLUGINS'], "name"), ", ", null, false). ".", C__DISPLAY_ITEM_DETAIL__);
    }

    
    
    private function _loadJsonPluginConfigFiles_()
    {
        $this->_configJsonFiles = glob($this->_dirJsonConfigs . DIRECTORY_SEPARATOR . "*.json");
        foreach($this->_configJsonFiles as $f) {
            $jsonPlugin = array("file" => $this->_dirJsonConfigs . DIRECTORY_SEPARATOR . $f);
            $jsonPlugin['configdata'] = loadJSON($f, null, true);
            $jsonPlugin['plugindata'] = $this->_parsePluginConfig_($jsonPlugin['configdata']);
            $jsonPlugin['evalcode'] = $evalCode = $this->_getClassInstantiationCode($jsonPlugin['plugindata']);
            $jsonPlugin['classname'] = "Plugin" . $jsonPlugin['configdata']['AgentName'];
            $this->_jsonPluginSetups[$jsonPlugin['classname']] = $jsonPlugin;
        }

    }

    private function _parsePluginConfig_($arrConfigData)
    {

        $pluginData = array(
            'siteName' => getArrayItem('AgentName', $arrConfigData),
            'siteBaseURL' => getArrayItem('BaseURL', $arrConfigData),
            'strBaseURLFormat' => getArrayItem('SourceURL', $arrConfigData),
            'countryCodes' => getArrayItem('CountryCodes', $arrConfigData),
            'arrListingTagSetup' => \JobScooper\Plugins\lib\SimplePlugin::getEmptyListingTagSetup()
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


    private function _getClassInstantiationCode($pluginConfig)
    {
        $className = "Plugin" . $pluginConfig['siteName'];
        $setup = var_export($pluginConfig['arrListingTagSetup'], true);

        $extendsClass = "JobScooper\Plugins\lib\AjaxHtmlSimplePlugin";
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

        return $evalStmt;
    }
}
