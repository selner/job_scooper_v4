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

namespace JobScooper\DataAccess;

use Exception;
use JobScooper\SitePlugins\AjaxSitePlugin;
use JobScooper\SitePlugins\Interfaces\IJobSitePlugin;
use JobScooper\Utils\Settings;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Class JobSiteManager
 * @package JobScooper\DataAccess
 */
class JobSiteManager
{

    /**
     * @return \JobScooper\DataAccess\JobSiteRecord[]|null
     * @throws \Exception
     */
    public static function getJobSitesByKeys($keys)
    {
    	if(is_empty_value($keys)) {
			return array();
    	}

    	return JobSiteRecordQuery::create()
    	    ->filterByJobSiteKey($keys, Criteria::IN)
    	    ->find()
    	    ->toKeyIndex('JobSiteKey');
    }

   /**
     * @param string $class
     * @return \JobScooper\DataAccess\JobSiteRecord|null
     * @throws \Exception
	*/
    public static function getJobSiteByPluginClass($class)
    {
        if (is_empty_value($class)) {
            throw new \InvalidArgumentException("Error: no job site plugin class specified.");
        }

        return JobSiteRecordQuery::create()
            ->findOneByPluginClassName($class);
    }

   /**
     * @param string $class
     * @return string
     * @throws \Exception
	*/
    public static function getJobSiteKeyByPluginClass($class)
    {
        if (is_empty_value($class)) {
            throw new \InvalidArgumentException("Error: no job site plugin class specified.");
        }

        $key = null;
        $site = self::getJobSiteByPluginClass($class);
        if($site !== null) {
        	$key = $site->getJobSiteKey();
        }
        $site = null;

        return $key;
    }

    /**
     * @param string $strJobSiteKey
     * @return \JobScooper\DataAccess\JobSiteRecord|null
     * @throws \Exception
     */
    public static function getJobSiteByKey($strJobSiteKey)
    {
        if (empty($strJobSiteKey)) {
            throw new \InvalidArgumentException("Error: no job site key specified.");
        }

        $sites = self::getJobSitesByKeys([$strJobSiteKey]);
		if(!is_empty_value($sites) && is_array($sites)) {
			return array_pop($sites);
		}

        return null;
    }

    /**
     * @param string $strJobSiteKey
     * @throws \Exception
     * @return \JobScooper\SitePlugins\Interfaces\IJobSitePlugin
     */
    public static function getJobSitePluginByKey($strJobSiteKey)
    {
        $site = JobSiteManager::getJobSiteByKey($strJobSiteKey);
        if(null !== $site) {
        	return $site->getPlugin();
        }

        return null;
    }

    /**
     *
     * @return array|mixed
     * @throws \Exception
     */
    public static function getIncludedJobSites()
    {
    	$includedKeys = self::getIncludedJobSiteKeys();
    	return self::getJobSitesByKeys($includedKeys);
	}

	/**
     *
     * @return array|null
     * @throws \Exception
     */
    public static function getIncludedJobSiteKeys()
    {
    	$siteKeys = Settings::getValue("included_jobsite_keys");
        if (null === $siteKeys)
        {
            $remainingEnabledSiteKeys = self::getJobSitesCmdLineIncludedInRun();
			if(null === $remainingEnabledSiteKeys)
			{
				return null;
			}
            $configExcludedSites = Settings::getValue('config_excluded_sites');
			if(null !== $configExcludedSites) {
	           $remainingEnabledSiteKeys = array_diff(array_values($remainingEnabledSiteKeys), $configExcludedSites);
			}

            $sites = self::getJobSitesByKeys($remainingEnabledSiteKeys);
			self::filterRecentlyRunJobSites($sites);

            $siteKeys = array_keys($sites);
            self::setIncludedJobSiteKeys($siteKeys);

        }

    	$siteKeys = Settings::getValue("included_jobsite_keys");

        return $siteKeys;
    }

    /**
     * @param array $sitesKeysInclude
     */
    public static function setIncludedJobSiteKeys($sitesKeysInclude)
    {
    	$keys = null;
    	if(!is_empty_value($sitesKeysInclude) && is_array($sitesKeysInclude))
        {
        	$keys = array_values($sitesKeysInclude);
        }

        Settings::setValue('included_jobsite_keys', $keys);
    }


    /**
	* @throws \Exception
	*/
    public static function getAllJobSiteKeys() {
        $allKeys = Settings::getValue('all_jobsite_keys');
        if($allKeys === null) {
	        $allKeys = Settings::getValue('all_jobsite_keys');
        }

        return $allKeys;

    }

    /**
	* @throws \Propel\Runtime\Exception\PropelException
	* @throws \Exception
	*/
    public static function filterRecentlyRunJobSites(&$sites)
    {
    	if(is_empty_value($sites)) {
    		return $sites;
        }

    	$stillIncluded = array_keys($sites);
        $siteWaitCutOffTime = date_sub(new \DateTime(), date_interval_create_from_date_string('23 hours'));
		$filteredSites = array();

		$completedSitesAllOnly = UserSearchSiteRunQuery::create()
            ->useJobSiteFromUSSRQuery()
            ->filterByResultsFilterType('all-only')
            ->withColumn('JobSiteFromUSSR.ResultsFilterType', 'ResultsFilterType')
            ->endUse()
            ->addAsColumn('LastCompleted', 'MAX(user_search_site_run.date_ended)')
            ->select(array('JobSiteKey', 'ResultsFilterType', 'LastCompleted'))
            ->filterByRunResultCode(array('successful', 'failed'), Criteria::IN)
            ->groupBy(array('JobSiteKey', 'ResultsFilterType'))
            ->find()
            ->getData();
        if (!empty($completedSitesAllOnly)) {
	        $completedSiteKeys = array_column($completedSitesAllOnly, null, 'JobSiteKey');
        	foreach($sites as $k => $site)
            {
	            if(array_key_exists($k, $completedSiteKeys) && !is_empty_value($completedSiteKeys[$k]['LastCompleted'])) {
                  if(new \DateTime($completedSiteKeys[$k]['LastCompleted']) >= $siteWaitCutOffTime) {
						$filteredSites[] = $k;
						unset($stillIncluded[$k]);
						$site = null;
		                $sites[$k] = null;
		                unset($sites[$k]);
                  }
                }
	        }
        }

        $sites = self::getJobSitesByKeys($stillIncluded);
        if(!is_empty_value($filteredSites)) {
        	LogMessage('Filtered ' . count($filteredSites) . ' job sites that were completed too recently:  ' . getArrayDebugOutput($filteredSites));
        }
    }


    /**
     * @return array|mixed
     * @throws \Exception
     */
    public static function getJobSitesCmdLineIncludedInRun()
    {

        $cmdLineSites = getConfigurationSetting('command_line_args.jobsite');
        if(is_empty_value($cmdLineSites)) {
        	return array();
        }

        // can't call GetAllJobSiteKeys here because we will be in a recursion loop if we do
        if (in_array('all', $cmdLineSites)) {
            return Settings::getValue('all_jobsite_keys');
        }

        return $cmdLineSites;
    }

    /**
     * @param array $setExcluded
     * @throws \Exception
     */
    public static function setSitesAsExcluded($setExcluded=array())
    {
        if (empty($setExcluded)) {
            return;
        }

        $includedSiteKeys = Settings::getValue("included_jobsite_keys");
		if(is_empty_value($includedSiteKeys)) {
			return;
		}

        $siteKeysStillIncluded  = array_diff($includedSiteKeys, array_keys($setExcluded));
        JobSiteManager::setIncludedJobSiteKeys($siteKeysStillIncluded);
    }

    /**
     * @param array &$sites
     * @param string[] $countryCodes
     *
	 * @return array
*    * @throws \Exception
     */
    public static function filterJobSitesByCountryCodes(&$sites, $countryCodes)
    {
    	if(is_empty_value($sites) || is_empty_value($countryCodes)) {
    		return $sites;
        }

        $ccRun = array_unique($countryCodes);
        $siteKeysOutOfSearchArea = array();

        if (null !== $sites) {
            foreach ($sites as $jobsiteKey => $site) {
                $ccSite = $site->getSupportedCountryCodes();
                $ccMatches = array_intersect($ccRun, $ccSite);
                if (is_empty_value($ccMatches)) {
		            self::setSitesAsExcluded(array($jobsiteKey));
		            $sites[$jobsiteKey] = null;
		            unset($sites[$jobsiteKey]);
                    $siteKeysOutOfSearchArea[$jobsiteKey] = $jobsiteKey;
                }
                $site = null;
            }
        }

        if (!is_empty_value($siteKeysOutOfSearchArea)) {
            LogMessage('Skipping JobSites without coverage in ' . implode(', ', $ccRun) .': ' . implode(', ', $siteKeysOutOfSearchArea) );
        }

    }

    /**
     * @var false|\LightnCandy\Closure|null
     */
    protected $_renderer = null;
    protected $_dirPluginsRoot = null;
    protected $_dirJsonConfigs = null;
    protected $_configJsonFiles = array();
    private $_jsonPluginSetups = array();

    private $_pluginsLoaded = false;

    /**
     * JobSiteManager constructor.
     * @throws \Exception
     */
    public function __construct()
    {
	    $is_init = Settings::getValue('JobSiteManager.initialized');

        if (is_empty_value($is_init) || $is_init !== true) {
            $pathPluginDirectory = join(DIRECTORY_SEPARATOR, array(__ROOT__, 'Plugins'));
            if (empty($pathPluginDirectory)) {
                throw new Exception('Path to plugins source directory was not set.');
            }

            if (!is_dir($pathPluginDirectory)) {
                throw new Exception("Unable to access the plugin directory '{$pathPluginDirectory}'");
            }

            $this->_dirPluginsRoot = realpath($pathPluginDirectory . DIRECTORY_SEPARATOR);

            $this->_dirJsonConfigs = join(DIRECTORY_SEPARATOR, array($pathPluginDirectory, 'json-based'));

            $this->_renderer = loadTemplate(__ROOT__ . '/src/assets/templates/eval_jsonplugin.tmpl');


            $this->_loadPHPPluginFiles_();
            $this->_loadJsonPluginConfigFiles_();
            $this->_initializeAllJsonPlugins();

            $this->_syncDatabaseJobSitePluginClassList();

            Settings::setValue('JobSiteManager.initialized', true);
       }
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
	*/
    private function _syncDatabaseJobSitePluginClassList()
    {
        LogMessage('Adding any missing declared jobsite plugins and jobsite records in database...', null, null, null, $channel='plugins');
        $allDBJobSitesByKey = \JobScooper\DataAccess\JobSiteRecordQuery::create()
            ->find()
            ->toArray('JobSiteKey');
        if(is_empty_value($allDBJobSitesByKey)) {
			$allDBJobSitesByKey = array();
        }

        $prevDisabledSitesByKey = array_filter($allDBJobSitesByKey, function($v) {
        	return $v['isDisabled'] === true;
        });
        $enabledSitesByKey = array_diff_key($allDBJobSitesByKey, $prevDisabledSitesByKey);

        $declaredPluginsBySiteKey = $this->_getAllDeclaredPluginClassesByJobSiteKey();
		$sitesToReEnable = array_intersect_key($prevDisabledSitesByKey, $declaredPluginsBySiteKey);

		$sitesToAdd = array_diff_key($declaredPluginsBySiteKey, $allDBJobSitesByKey);
        if (!is_empty_value($sitesToAdd)) {
            LogMessage('Adding ' . getArrayValuesAsString($sitesToAdd), null, null, null, $channel='plugins');

            foreach ($sitesToAdd as $jobSiteKey => $pluginClass) {
                $dbrec = JobSiteRecordQuery::create()
                    ->filterByJobSiteKey($jobSiteKey)
                    ->findOneOrCreate();

                $dbrec->setJobSiteKey($jobSiteKey);
                $dbrec->setPluginClassName($pluginClass);
                $dbrec->setDisplayName(str_replace('Plugin', '', $pluginClass));
                $dbrec->save();
                $dbrec = null;
                $enabledSitesByKey[$jobSiteKey] = $jobSiteKey;
            }
        }

        $sitesToDisable = array_diff_key($enabledSitesByKey, $declaredPluginsBySiteKey);
        if(!is_empty_value($sitesToDisable) && is_array($sitesToDisable)) {
			$sitesToSetDisabled = JobSiteManager::getJobSitesByKeys(array_keys($sitesToDisable));
            LogMessage('Disabling ' . count($sitesToSetDisabled) . ' site plugins...', null, null, null, $channel='plugins');
			foreach($sitesToSetDisabled as $key => $site)
            {
				LogWarning("{$jobSiteKey} did not have a matching Plugin class that was declared so it cannot be used.  Marking as disabled in the database and ignoring it.");
	            $site->setisDisabled(true);
	            $site->save();
                $enabledSites[$key] = null;
                unset($enabledSites[$key]);
                $site = null;
			}
		}

        if (!empty($sitesToReEnable) && is_array($sitesToReEnable)) {
            $sitesToEnable = JobSiteManager::getJobSitesByKeys(array_keys($sitesToReEnable));
            LogMessage('Re-enabling previously disabled sites that now have plugin classes: ' . implode(', ', array_keys($sitesToEnable)), null, null, null, $channel='plugins');
            foreach ($sitesToEnable as $jobSiteKey => $site) {
                $site->setisDisabled(false);
                $site->setPluginClassName($declaredPluginsBySiteKey[$jobSiteKey]);
                $site->save();

                $site = null;
                $sitesToEnable[$jobSiteKey] = null;
                unset($sitesToEnable[$jobSiteKey]);
                $enabledSitesByKey[$jobSiteKey] = $jobSiteKey;
			}
			$sitesToEnable = null;
        }

        $nEnabledSites = count($enabledSitesByKey);
        LogMessage("Loaded {$nEnabledSites} enabled jobsite plugins.");
        $dbJobSitesByKey = null;

	    Settings::setValue('all_jobsite_keys', $enabledSites);

    }

    /**
     * @throws \Exception
     */
    private function _initializeAllJsonPlugins()
    {
        $arrAddedPlugins = null;
        LogMessage('Initializing all job site plugins...', null, null, null, $channel='plugins');

        LogMessage('Generating classes for ' . count($this->_jsonPluginSetups) . ' JSON-loaded plugins...', null, null, null, $channel='plugins');
        foreach (array('Abstract', 'Plugin') as $type) {
            $plugins = array_filter($this->_jsonPluginSetups, function ($val) use ($type) {
                $matched = preg_match('/^' . $type . '/', $val['PhpClassName']);
                return ($matched > 0);
            });

            foreach (array_keys($plugins) as $agentkey) {
                LogDebug('Running eval statement for class ' . $plugins[$agentkey]['PhpClassName'], null, $channel='plugins');
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
                    handleException($ex);
                }
            }

            LogMessage('Added ' . count($plugins) . ' ' . ($type === 'Abstract' ? $type : 'json') . ' plugins: ', null, null, null, $channel='plugins');
        }
    }


    /**
     * @throws \Exception
     */
    private function _loadJsonPluginConfigFiles_()
    {
        $this->_configJsonFiles = glob($this->_dirJsonConfigs . DIRECTORY_SEPARATOR . '*.json');
        LogMessage('Loading JSON-based, jobsite plugin configurations from ' . count($this->_configJsonFiles) . ' files under {$this->_dirJsonConfigs}...', null, null, null, $channel='plugins');

        foreach ($this->_configJsonFiles as $f) {
            $dataPlugins = loadJSON($f, null, true);
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
                // replace non letter or digits with separator

                $this->_jsonPluginSetups[$jsonPlugin['PhpClassName']] = $jsonPlugin;
            }
        }
    }



    /**
     * @throws \Exception
     */
    private function _loadPhpPluginFiles_()
    {
        $files = glob_recursive($this->_dirPluginsRoot . DIRECTORY_SEPARATOR . '*.php');
        foreach ($files as $file) {
            require_once($file);
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
        $jobsitekey = strtolower($pluginData['JobSiteName']);


        $listingTagBucket = 'arrListingTagSetup';
        if (!empty($arrConfigData['PluginExtendsClassName']) && false === stripos($arrConfigData['PluginExtendsClassName'], 'Abstract'))
        {
            setArrayItem($pluginData, 'PluginExtendsClassName', $arrConfigData, 'PluginExtendsClassName');
        }
        elseif(false !== stripos($arrConfigData['PluginExtendsClassName'], 'Abstract'))
        {
            $listingTagBucket = 'arrBaseListingTagSetup';
        }

        $pluginData['PluginExtendsClassName'] = str_replace('\\\\', '\\', $pluginData['PluginExtendsClassName']);

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
                            'selector' => $arrConfigData['Pagination']['Selector'],
                            'index' => $arrConfigData['Pagination']['Index'],
                            'type' => 'CSS'
                        );
                        break;

                    case 'LOAD-MORE':
                        $pluginData[$listingTagBucket]['LoadMoreControl'] = array(
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


        if (array_key_exists('Collections', $arrConfigData) && !is_null($arrConfigData['Collections']) && is_array($arrConfigData['Collections']) && count($arrConfigData['Collections']) > 0 && array_key_exists('Fields', $arrConfigData['Collections'][0])) {
            if (!is_array($pluginData[$listingTagBucket])) {
                $pluginData[$listingTagBucket] = array();
            }
            foreach ($arrConfigData['Collections'] as $coll) {
                foreach ($coll['Fields'] as $field) {
                    $name = getArrayItem('Name', $field);

                    $pluginData[$listingTagBucket][$name] = array();
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
                        setArrayItem($pluginData[$listingTagBucket][$name], $mapping[0], $field, $mapping[0]);
                    }

                    foreach ($MAP_VALUES as $mapping) {
                        setArrayItem($pluginData[$listingTagBucket][$name], $mapping[0], $field, $mapping[1]);
                    }
                }
            }
        }

        LogDebug('Loaded JSON config for new plugin: ' . $pluginData['JobSiteName'], null, $channel='plugins');

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
    private function _getAllDeclaredPluginClassesByJobSiteKey()
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
            if (in_array(IJobSitePlugin::class, class_implements($class))) {
                $classListBySite[$jobsitekey] = $class;
            } else {
                LogWarning("{$jobsitekey} does not support iJobSitePlugin: " . getArrayDebugOutput(class_implements($class)));
            }
        }

        return $classListBySite;
    }
}
