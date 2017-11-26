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

class JobSitePluginBuilder
{
    protected $_renderer = null;
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

        $this->_renderer = loadTemplate(__ROOT__.'/assets/templates/eval_jsonplugin.tmpl');


        $this->_loadJsonPluginConfigFiles_();
        $this->_initializeAllPlugins();


    }
    
    
    function _initializeAllPlugins()
    {
        $arrAddedPlugins = null;
        LogLine('Initializing all job site plugins...', C__DISPLAY_ITEM_START__);

        LogLine('Generating classes for ' . count($this->_jsonPluginSetups) .' JSON-loaded plugins...', C__DISPLAY_ITEM_DETAIL__);

        foreach(array('Abstract', 'Plugin') as $type)
        {
            $plugins = array_filter($this->_jsonPluginSetups, function ($val) use ($type) {
                $matched = preg_match("/^" . $type . "/", $val['phpClassName']);
                return ($matched > 0);
            });

            foreach (array_keys($plugins) as $agentkey) {
                LogLine("Running eval statement for class " . $plugins[$agentkey]['phpClassName'], C__DISPLAY_ITEM_DETAIL__);
                try {
                    $evalStmt = $this->_getClassInstantiationCode($plugins[$agentkey]);
                    $success = eval($evalStmt);
                    if ($success === false)
                        throw new \Exception("Failed to initialize the plugin eval code for " . $agentkey . ": " . error_get_last()['message']);
                } catch (\Exception $ex) {
                    handleException($ex);
                }
                //            return new $className(null, null);
            }
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
            $dataPlugins = loadJSON($f, null, true);
            $plugsToInit = array();
            if(array_key_exists('jobsite_plugins', $dataPlugins))
            {
                $plugsToInit = array_values($dataPlugins['jobsite_plugins']);
            }
            else
            {
                $plugsToInit[] = $dataPlugins;

            }

            foreach($plugsToInit as $config)
            {
                $jsonPlugin = $this->_parsePluginConfig_($config);
                // replace non letter or digits with separator

                $this->_jsonPluginSetups[$jsonPlugin['phpClassName']] = $jsonPlugin;
            }
        }

    }

    private function _parsePluginConfig_($arrConfigData)
    {

        $pluginData = array();

        setArrayItem($pluginData,'phpClassName', $arrConfigData, 'PhpClassName');
        setArrayItem($pluginData,'siteName', $arrConfigData, 'AgentName');
        if(empty($pluginData['phpClassName']))
            $pluginData['phpClassName'] = "Plugin". $pluginData['siteName'];

        setArrayItem($pluginData,'siteBaseURL', $arrConfigData, 'BaseURL');
        setArrayItem($pluginData,'strBaseURLFormat', $arrConfigData, 'SourceURL');
        setArrayItem($pluginData,'countryCodes', $arrConfigData, 'CountryCodes');

        if(array_key_exists("Pagination", $arrConfigData)) {
            setArrayItem($pluginData,'nJobListingsPerPage', $arrConfigData['Pagination'], 'PageLimit');
            setArrayItem($pluginData,'additionalLoadDelaySeconds', $arrConfigData['Pagination'], 'PageDelaySeconds');


            if (array_key_exists("Type", $arrConfigData['Pagination'])) {

                $pluginData['paginationType'] = strtoupper($arrConfigData['Pagination']['Type']);
                switch (strtoupper($arrConfigData['Pagination']['Type'])) {
                    case 'NEXT-BUTTON':
                        $pluginData['arrListingTagSetup']['NextButton'] = array(
                            'selector' => $arrConfigData['Pagination']['Selector'],
                            'index' => $arrConfigData['Pagination']['Index'],
                            'type' => 'CSS'
                        );
                        break;

                    case 'LOAD-MORE':
                        $pluginData['arrListingTagSetup']['LoadMoreControl'] = array(
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
                setArrayItem($pluginData,$datakey, $arrConfigData, $datakey);
        }


        if(array_key_exists("Collections", $arrConfigData) && !is_null($arrConfigData['Collections']) && is_array($arrConfigData['Collections']) && count($arrConfigData['Collections']) > 0 && array_key_exists("Fields", $arrConfigData['Collections'][0]))
        {


//            $pluginData['arrListingTagSetup'] = \JobScooper\Plugins\lib\SimplePlugin::getEmptyListingTagSetup();
            $pluginData['arrListingTagSetup'] = array();
            foreach($arrConfigData['Collections'] as $coll)
            {
                foreach($coll['Fields'] as $field) {

                    if ((strcasecmp($field['Extract'], "HTML") == 0) || (strcasecmp($field['Extract'], "ATTR") == 0)) {
                        $field['Type'] = 'CSS';
                    } elseif (strcasecmp($field['Extract'], "TEXT") == 0) {
                        $field['Type'] = 'CSS';
                        $field['Attribute'] = 'plaintext';
                    }


                    $name = getArrayItem('Name', $field);

                    $pluginData['arrListingTagSetup'][$name] = array();
                    $MAP_VALUES = array(
                        ['selector', 'Selector'],
                        ['index', 'Index'],
                        ['return_attribute', 'Attribute'],
                        ['type', 'Type'],
                        ['field', 'Field'],
                        ['value', 'Value'],
                        ['return_value_regex', 'Pattern'],
                        ['return_value_callback', 'Callback'],
                        ['callback_parameter', 'CallbackParameter']
                    );

                    foreach ($MAP_VALUES as $mapping) {
                        setArrayItem($pluginData['arrListingTagSetup'][$name], $mapping[0], $field, $mapping[0]);
                    }

                    foreach ($MAP_VALUES as $mapping) {
                        setArrayItem($pluginData['arrListingTagSetup'][$name], $mapping[0], $field, $mapping[1]);
                    }
                }
            }
        }

        if(isset($GLOBALS['logger']))
            $GLOBALS['logger']->logLine("Loaded JSON config for new plugin: " . $pluginData['siteName'], \C__DISPLAY_ITEM_DETAIL__);

        return $pluginData;

    }


    private function _getArrayItemForEval($pluginConfig, $key, $quoteItems = true)
    {
        $flags = null;
        if (array_key_exists($key, $pluginConfig) && !is_null($pluginConfig[$key]) && is_array($pluginConfig[$key]) && count($pluginConfig[$key]) >= 1)
        {
            $flags = "array()";

            $start = "[";
            $glue = ", ";
            $end = "]";

            if($quoteItems === true) {
                $start = "[\"";
                $glue = "\", \"";
                $end = "\"]";
            }

            $flags = $start . join($glue, array_values($pluginConfig[$key])) . $end;
        }
        return $flags;

    }
    private function _getClassInstantiationCode($pluginConfig)
    {

        $pluginConfig['extendsClass'] = "JobScooper\Plugins\lib\AjaxHtmlSimplePlugin";
        if(array_key_exists("PluginExtendsClassName", $pluginConfig) && !is_null($pluginConfig['PluginExtendsClassName']) && strlen($pluginConfig['PluginExtendsClassName']))
        {
            $pluginConfig['extendsClass']  = $pluginConfig['PluginExtendsClassName'];
        }

        $pluginConfig['additionalFlags'] = $this->_getArrayItemForEval($pluginConfig, 'AdditionalFlags', $quoteItems = false);
        $pluginConfig['countryCodes'] = $this->_getArrayItemForEval($pluginConfig, 'CountryCodes', $quoteItems = true);

        if(count($pluginConfig['arrListingTagSetup']) > 0)
            $pluginConfig['tagSetup'] = var_export($pluginConfig['arrListingTagSetup'], true);
        $evalStmt = call_user_func_array($this->_renderer, array($pluginConfig));

        return $evalStmt;
    }
}
