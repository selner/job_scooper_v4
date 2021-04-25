<?php

namespace Jobscooper;

use function countAssociativeArrayValues;
use function getArrayItem;
use function loadJSON;

class JSONPlugins
{
    function init()
    {
        return;
    }

    private function _loadPluginConfigFileData_()
    {
        $jsonconfigsdir = __DIR__ . "/Plugins/json_plugins";
        $arrAddedPlugins = null;
        print('Getting job site plugin list...' . PHP_EOL);
        $filelist = array_diff(scandir($jsonconfigsdir), array(".", ".."));
        $filelist = array_filter($filelist, function ($var) {
            if (preg_match("/json$/", pathinfo($var, PATHINFO_EXTENSION)))
                return true;
            return false;
        });

        foreach ($filelist as $f) {
            $this->pluginConfigs[$f] = loadJSON($jsonconfigsdir . "/" . $f);
        }

    }

    private function _parsePluginConfig_($arrConfigData)
    {

        $pluginData = array(
            'siteName' => null,
            'siteBaseURL' => null,
            'strBaseURLFormat' => null,
            'PageLimit' => null,
            'LocationType' => null,
            'AdditionalFlags' => array(),
            'nJobListingsPerPage' => C_JOB_MAX_RESULTS_PER_SEARCH,
            'arrListingTagSetup' => \Scooper\array_copy(\Jobscooper\BasePlugin\HTMLJobSitePlugin::getEmptyListingTagSetup())
        );

        foreach (array_keys($arrConfigData) as $datakey) {
            switch ($datakey) {

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
                    $pluginData[$datakey] = $arrConfigData[$datakey];
                    break;
            }
        }

        if (array_key_exists("Pagination", $arrConfigData)) {

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
                            'selector' => getArrayValue($arrConfigData['Pagination'], 'Selector'),
                            'index' => getArrayValue($arrConfigData['Pagination'], 'Index'),
                            'type' => 'CSS'
                        );
                        break;

                    case 'LOAD-MORE':
                        $pluginData['arrListingTagSetup']['tag_load_more'] = array(
                            'selector' => getArrayValue($arrConfigData['Pagination'], 'Selector'),
                            'index' => getArrayValue($arrConfigData['Pagination'], 'Index'),
                            'type' => 'CSS'
                        );
                        break;

                    default:
                        break;
                }
            }
        }

        if (array_key_exists("Collections", $arrConfigData) && !is_null($arrConfigData['Collections']) && is_array($arrConfigData['Collections']) && count($arrConfigData['Collections']) > 0 && array_key_exists("Fields", $arrConfigData['Collections'][0])) {
            foreach ($arrConfigData['Collections'] as $coll) {
                foreach ($coll['Fields'] as $field) {
                    $select = getArrayItem('Selector', $field);
                    $name = getArrayItem('Name', $field);
                    $attrib = null;

                    $index = getArrayItem('Index', $field);
                    $type = getArrayItem('Type', $field);

                    $extract = getArrayValue($field, 'Extract');

                    if ((strcasecmp($extract, "HTML") == 0) || (strcasecmp($extract, "ATTR") == 0)) {
                        $attrib = getArrayItem('Attribute', $field);
                        $type = "CSS";
                    } elseif (strcasecmp($extract, "TEXT") == 0) {
                        $attrib = "plaintext";
                        $type = "CSS";
                    } elseif (!is_empty_key($field, 'Attribute')) {
                        $attrib = getArrayItem('Attribute', $field);
                    }

                    $pluginData['arrListingTagSetup'][$name] = array(
                        'selector' => $select,
                        'index' => $index,
                        'return_attribute' => $attrib,
                        'type' => $type,
                        'field' => getArrayItem('Field', $field),
                        'return_value_regex' => getArrayItem('Pattern', $field),
                        'return_value_callback' => getArrayItem('Callback', $field),
                        'callback_parameter' => getArrayItem('CallbackParameter', $field)
                    );
                }
            }
            if (isset($GLOBALS['logger']))
                $GLOBALS['logger']->logLine("Loaded " . countAssociativeArrayValues($pluginData) . " JSON configs for new Plugins.", \Scooper\C__DISPLAY_ITEM_DETAIL__);

            return $pluginData;
        }

    }


    private function _instantiatePlugin_($pluginConfig)
    {
        $className = "JobSite" . $pluginConfig['siteName'];
        $setup = var_export($pluginConfig['arrListingTagSetup'], true);

        $extendsClass = "\Jobscooper\\BasePlugin\JSONJobsitePlugin";
        if (array_key_exists("PluginExtendsClassName", $pluginConfig) && !is_null($pluginConfig['PluginExtendsClassName']) && strlen($pluginConfig['PluginExtendsClassName'])) {
            $extendsClass = $pluginConfig['PluginExtendsClassName'];
        }

        $flags = "null";
        if (array_key_exists('AdditionalFlags', $pluginConfig))
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

        foreach ($this->pluginConfigs as $configData) {
            $retSetup = $this->_parsePluginConfig_($configData);
            $arrPluginSetups[$retSetup['siteName']] = $retSetup;

            if (isset($GLOBALS['logger']))
                print("Initializing JSON plugin for " . $retSetup['siteName'] . PHP_EOL);
            $this->_instantiatePlugin_($retSetup);

        }


    }

    protected $pluginConfigs = array();

}