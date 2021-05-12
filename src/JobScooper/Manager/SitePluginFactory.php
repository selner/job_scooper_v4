<?php
/**
 * Copyright 2014-18 Bryan Selner
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
use JobScooper\DataAccess\JobSiteRecordQuery;
use JobScooper\SitePlugins\AjaxSitePlugin;
use JobScooper\SitePlugins\Base\SitePlugin;
use JobScooper\SitePlugins\IJobSitePlugin;
use JobScooper\Utils\Settings;

class SitePluginFactory
{
    /**
    * @param $jobSiteKey
    * @return IJobSitePlugin|null
    */
    public static function create($jobSiteKey)
    {
    	if(is_empty_value($jobSiteKey)) {
			throw new \InvalidArgumentException('Failed to create SitePlugin instance:  no JobSiteKey was specified.');
    	}

    	$objJobSiteRec = JobSiteRecordQuery::create()
    	    ->findOneByJobSiteKey($jobSiteKey);
    	if(null !== $objJobSiteRec) {
    	    $className = $objJobSiteRec->getPluginClassName();
            if(is_empty_value($className)) {
                throw new \InvalidArgumentException("Failed to instantiate {$jobSiteKey} SitePlugin: no plugin class defined for {$jobSiteKey}.");
            }
            if(!class_exists($className)) {
                throw new \InvalidArgumentException("Failed to instantiate {$jobSiteKey} SitePlugin: {$className} does not exist.");
            }

            if(!is_empty_value(class_implements($className)) && !in_array(IJobSitePlugin::class, class_implements($className))) {
                throw new \InvalidArgumentException("Failed to instantiate {$jobSiteKey} SitePlugin: {$className} does not implement " . IJobSitePlugin::class . " interface.");
            }

            return new $className();
    	}

    	return null;
    }



    protected $_renderer = null;
    protected $_dirPluginsRoot = null;
    protected $_dirJsonConfigs = null;
    protected $_configJsonFiles = array();
    private $_jsonPluginSetups = array();


    public static function loadAndVerifyInstalledPlugins()
    {
        $is_init = Settings::getValue( self::class .'.loadAndVerifyInstalledPlugins.loaded');

        if (null === $is_init && $is_init !== true) {
            $pluginFactoryInst = new SitePluginFactory();
        }

    }

    public function __construct() {

            try {
                $pathPluginDirectory = implode(DIRECTORY_SEPARATOR, array(__ROOT__, 'Plugins'));
                if (empty($pathPluginDirectory)) {
                    throw new Exception('Path to plugins source directory was not set.');
                }

                if (!is_dir($pathPluginDirectory)) {
                    throw new Exception("Unable to access the plugin directory '{$pathPluginDirectory}'");
                }

                $this->_dirPluginsRoot = realpath($pathPluginDirectory . DIRECTORY_SEPARATOR);

                $this->_dirJsonConfigs = implode(DIRECTORY_SEPARATOR, array($pathPluginDirectory, 'json-based'));

                $this->_renderer = loadTemplate(__ROOT__ . '/src/assets/templates/eval_jsonplugin.tmpl');


                $this->_loadPHPPluginFiles_();
                $this->_loadJsonPluginConfigFiles_();
                $this->_initializeAllJsonPlugins();

            } catch (Exception $ex) {
                throw new Exception('Failed to load and verify plugin code for installed JobSite plugins: %s', $ex->getCode(), $ex);
            }
    }


    /**
     * @throws \Exception
     */
    private function _initializeAllJsonPlugins()
    {
        $arrAddedPlugins = null;
        LogMessage('Initializing all job site plugins...', null, null, null, $log_topic='plugins');

        LogMessage('Generating classes for ' . \count($this->_jsonPluginSetups) . ' JSON-loaded plugins...', null, null, null, $log_topic='plugins');
        foreach (array('Abstract', 'Plugin') as $type) {
            $plugins = array_filter($this->_jsonPluginSetups, function ($val) use ($type) {
                $matched = preg_match('/^' . $type . '/', $val['PhpClassName']);
                return ($matched > 0);
            });

            foreach (array_keys($plugins) as $agentkey) {
                LogDebug('Running eval statement for class ' . $plugins[$agentkey]['PhpClassName'], null, $log_topic='plugins');
                try {
                    if ($type === 'Abstract' && array_key_exists('arrListingTagSetup', $plugins[$agentkey])) {
                        $plugins[$agentkey]['arrBaseListingTagSetup'] = $plugins[$agentkey]['arrListingTagSetup'];
//                        unset($plugins[$agentkey]['arrListingTagSetup'], $plugins[$agentkey]['JobSiteName'], $plugins[$agentkey]['JobSiteKey']);
                    }
                    if (!in_array($plugins[$agentkey]['PhpClassName'], get_declared_classes())) {
                        $evalStmt = $this->_getClassInstantiationCode($plugins[$agentkey]);

                        try {
                            eval($evalStmt);
                        } catch (\ParseError $ex) {
                            throw new \ParseError('Failed to initialize the plugin eval code for ' . $agentkey . ': ' . $ex->getMessage(). PHP_EOL . 'EvalStatement = ' . PHP_EOL . $evalStmt, $ex->getCode(), $ex);
                        }
                    }
                } catch (\Exception $ex) {
                    handleThrowable($ex);
                }
            }


            LogMessage(count($plugins) . ' ' . ($type === 'Abstract' ? $type : 'json') . ' plugin classes installed:  ' . implode(", ", array_keys($plugins)), null, null, null, $log_topic='plugins');
        }
    }


    /**
     * @throws \Exception
     */
    private function _loadJsonPluginConfigFiles_()
    {
        $this->_configJsonFiles = glob($this->_dirJsonConfigs . DIRECTORY_SEPARATOR . '*.json');
        LogMessage('Loading JSON-based, jobsite plugin configurations from ' . \count($this->_configJsonFiles) . " files under {$this->_dirJsonConfigs}...", null, null, null, $log_topic='plugins');

        sort($this->_configJsonFiles);


        foreach ($this->_configJsonFiles as $f) {
            $dataPlugins = loadJson($f, null, true);
            if (empty($dataPlugins)) {
                throw new \Exception('Unable to load JSON plugin data file from ' . $f . ': ' . json_last_error_msg());
            }
            $plugsToInit = array();

            if (array_key_exists('jobsite_plugins', $dataPlugins)) {
                $plugsToInit = array_values($dataPlugins['jobsite_plugins']);
            } else {
                $plugsToInit[] = $dataPlugins;
            }

            foreach ($plugsToInit as $config) {
                $jsonPlugin = $this->_parsePluginConfig_($config);
                $this->_jsonPluginSetups[$jsonPlugin['PhpClassName']] = $jsonPlugin;

                if(array_key_exists('child_jobsites', $jsonPlugin) && $jsonPlugin['child_jobsites'] != null &&
                    count($jsonPlugin['child_jobsites']) > 0) {
                    LogDebug(sprintf("Loading %s child jobsite plugin configurations for %s...", \count($jsonPlugin['child_jobsites']), $jsonPlugin['PhpClassName']), null, $log_topic='plugins');
                    $extendsClass = $jsonPlugin['PhpClassName'];
                    $thisPlugin = null;

                    foreach($jsonPlugin['child_jobsites'] as $childsitekey) {
                        $childConfig = array(
                            "JobSiteName" => $childsitekey,
                            "PluginExtendsClassName" => $extendsClass
                        );

                        $thisPlugin = $this->_parsePluginConfig_($childConfig);
                        $this->_jsonPluginSetups[$thisPlugin['PhpClassName']] = $thisPlugin;
                    }
                }
            }

        }
    }



    /**
     * @throws \Exception
     */
    private function _loadPhpPluginFiles_()
    {
        $files = glob_recursive($this->_dirPluginsRoot . DIRECTORY_SEPARATOR . '*.php');
		sort($files);
        foreach ($files as $file) {
            require_once $file;
        }
    }

    /**
     * @param $arrConfigData
     *
     * @return array
     */
    private function _parsePluginConfig_($arrConfigData)
    {
        $pluginData = array();
        foreach (array_keys($arrConfigData) as $datakey) {
            if (!array_key_exists($datakey, $pluginData) && !in_array($datakey, array('Collections', 'Fields'))) {
                setArrayItem($pluginData, $datakey, $arrConfigData, $datakey);
            }
        }

        setArrayItem($pluginData, 'PhpClassName', $arrConfigData, 'PhpClassName');
        if (empty($pluginData['PhpClassName'])) {
            $pluginData['PhpClassName'] = 'Plugin' . $arrConfigData['JobSiteName'];
        }
        if (empty($pluginData['JobSiteName'])) {
            $pluginData['JobSiteName'] = preg_replace('/^Plugin/', '', $arrConfigData['PhpClassName']);
        }
        $pluginData['JobSiteKey'] = strtolower($pluginData['JobSiteName']);


        $listingTagBucket = 'arrListingTagSetup';
        $pluginExtendsClass = "";
        if(array_key_exists('PluginExtendsClassName', $arrConfigData)) {
            $pluginExtendsClass = $arrConfigData['PluginExtendsClassName'];
        }
        if (!is_empty_value($pluginExtendsClass) && false === stripos($pluginExtendsClass, 'Abstract'))
        {
            setArrayItem($pluginData, 'PluginExtendsClassName', $arrConfigData, 'PluginExtendsClassName');
        }
        elseif(false !== stripos($pluginExtendsClass, 'Abstract'))
        {
            $listingTagBucket = 'arrBaseListingTagSetup';
        }
        if(!array_key_exists($listingTagBucket, $pluginData)) {
            $pluginData[$listingTagBucket] = array();
        }

        if(array_key_exists('PluginExtendsClassName', $pluginData)) {
            $pluginData['PluginExtendsClassName'] = str_replace('\\\\', '\\', $pluginData['PluginExtendsClassName']);
        }

        setArrayItem($pluginData, 'JobPostingBaseUrl', $arrConfigData, 'BaseURL');
        setArrayItem($pluginData, 'SearchUrlFormat', $arrConfigData, 'SourceURL');
//        setArrayItem($pluginData,'CountryCodes', $arrConfigData, 'CountryCodes');

        if (array_key_exists('Pagination', $arrConfigData)) {
            setArrayItem($pluginData, 'JobListingsPerPage', $arrConfigData['Pagination'], 'PageLimit');
            setArrayItem($pluginData, 'additionalLoadDelaySeconds', $arrConfigData['Pagination'], 'PageDelaySeconds');


            if (array_key_exists('Type', $arrConfigData['Pagination'])) {
                $pluginData['PaginationType'] = strtoupper($arrConfigData['Pagination']['Type']);
                switch (strtoupper($arrConfigData['Pagination']['Type'])) {
                    case 'NEXT-BUTTON':
                        $pluginData[$listingTagBucket]['NextButton'] = array(
                            'Selector' => getArrayItem('Selector', $arrConfigData['Pagination']),
                            'Index' => getArrayItem('Index', $arrConfigData['Pagination']),
                            'type' => 'CSS'
                        );
                        break;

                    case 'LOAD-MORE':
                        $pluginData[$listingTagBucket]['LoadMoreControl'] = array(
                            'Selector' => getArrayItem('Selector', $arrConfigData['Pagination']),
                            'Index' => getArrayItem('Index', $arrConfigData['Pagination']),
                            'type' => 'CSS'
                        );
                        break;

                    default:
                        break;
                }
            }
        }


        if (array_key_exists('Collections', $arrConfigData) && null !== $arrConfigData['Collections'] && is_array($arrConfigData['Collections']) &&
            \count($arrConfigData['Collections']) > 0) {
            foreach ($arrConfigData['Collections'] as $coll) {
            	if(array_key_exists('Fields', $coll)) {
					foreach ($coll['Fields'] as $field) {
	                    $name = getArrayItem('Name', $field);
						$pluginData[$listingTagBucket][$name] = $field;
	                }
                }
            }
        }

        if (array_key_exists('ChildJobSites', $arrConfigData)) {
            $pluginData['child_jobsites'] = $arrConfigData['ChildJobSites'];
        }


            LogDebug('Loaded JSON config for new plugin: ' . $pluginData['JobSiteName'], null, $log_topic='plugins');

        return $pluginData;
    }


    /**
     * @param      $pluginConfig
     * @param      $key
     * @param bool $quoteItems
     *
     * @return null|string
     */
    private function _getArrayItemForEval($pluginConfig, $key, $quoteItems = true)
    {
        $flags = '';
        if (array_key_exists($key, $pluginConfig) && !empty($pluginConfig[$key]) && is_array($pluginConfig[$key])) {
            $flags = 'array()';

            $start = '[';
            $glue = ', ';
            $end = ']';

            if ($quoteItems === true) {
                $start = "[\"";
                $glue = "\", \"";
                $end = "\"]";
            }

            $flags = $start . implode($glue, array_values($pluginConfig[$key])) . $end;
        }

        return $flags;
    }

    /**
     * @param $pluginConfig
     *
     * @return mixed
     */
    private function _getClassInstantiationCode($pluginConfig)
    {
        $evalConfig = array();
        $PhpClassName = 'Plugin' . $pluginConfig['JobSiteName'];

        $arrayProps = array();
        $stringProps = array();
        $numericProps = array();
        $otherProps = array();
        $stringProps['JobSiteKey'] = cleanupSlugPart($pluginConfig['JobSiteName']);


        foreach (array_keys($pluginConfig) as $key) {
            switch ($key) {
                case 'AdditionalFlags':
                    $arrayProps['additionalBitFlags'] = $this->_getArrayItemForEval($pluginConfig, 'AdditionalFlags', $quoteItems = false);
                    break;

                case 'PluginExtendsClassName':
                    $PluginExtendsClassName = $pluginConfig[$key];
                    unset($pluginConfig[$key]);
                    break;

                case 'PhpClassName':
                    $PhpClassName = $pluginConfig[$key];
                    unset($pluginConfig[$key]);
                    break;

                case is_numeric($pluginConfig[$key]):
                    $numericProps[$key] = $pluginConfig[$key];
                    break;

                case is_string($pluginConfig[$key]):
                    $stringProps[$key] = $pluginConfig[$key];
                    break;

                case is_array($pluginConfig[$key]):
                    if (!empty($pluginConfig[$key])) {
                        $arrayProps[$key] = var_export($pluginConfig[$key], true);
                    }
                    break;

                default:
                    $otherProps = $pluginConfig[$key];
                    break;
            }
        }

        $implements = null;
        if (empty($PluginExtendsClassName)) {
            $PluginExtendsClassName = AjaxSitePlugin::class;
        }

        $data = array(
            'PhpClassName' => $PhpClassName,
            'PluginExtendsClassName' => $PluginExtendsClassName,
            'string_properties' => $stringProps,
            'numeric_properties' => $numericProps,
            'array_properties' => $arrayProps,
            'other_properties' => $otherProps,
        );

        $evalStmt = call_user_func($this->_renderer, $data);

        return $evalStmt;
    }

    /**
     * @return array
     */
    public static function getInstalledPlugins()
    {
        $classListBySite = array();
        $classList = get_declared_classes();
        sort($classList);

        $pluginClasses = array_filter($classList, function ($class) {
            return preg_match('/^Plugin/', $class) !== 0;
        });

        foreach ($pluginClasses as $class) {
            $jobsitekey = cleanupSlugPart(str_replace('Plugin', '', $class));
            $classListBySite[$jobsitekey] = $class;
            if (in_array(IJobSitePlugin::class, class_implements($class)) ||
               (in_array(SitePlugin::class, class_parents($class)))) {
                $classListBySite[$jobsitekey] = $class;
            } else {
                LogWarning("{$jobsitekey} does not support iJobSitePlugin nor has the base SitePlugin as a parent: " . getArrayDebugOutput(class_implements($class)));
            }
        }

        ksort($classListBySite);

        return $classListBySite;
    }

    public static function getJobSiteKeyForPluginClass($className)
    {
        $allSites = self::getInstalledPlugins();
        if(is_empty_value($allSites)) {
            return null;
        }

        $allClasses = array_flip($allSites);

        if(array_key_exists($className, $allClasses)) {
            return $allClasses[$className];
        }
		
        return null;
    }



}
