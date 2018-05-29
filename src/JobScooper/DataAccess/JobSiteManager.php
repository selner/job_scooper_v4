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

use JobScooper\SitePlugins\SitePluginFactory;use JobScooper\Utils\Settings;
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
            throw new \InvalidArgumentException(
            	'Error: no job site key specified.');
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
     * @return \JobScooper\SitePlugins\IJobSitePlugin
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
    public static function getJobSitesIncludedInRun()
    {
    	$includedKeys = self::getJobSiteKeysIncludedInRun();
    	return self::getJobSitesByKeys($includedKeys);
	}

	/**
     *
     * @return array|null
     * @throws \Exception
     */
    public static function getAllEnabledJobSiteKeys()
    {
	    return Settings::getValue(self::class . '.enabled_sites');
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
     * @param array $sitesKeysInclude
     * @throws \Exception
	*/
    public static function getJobSiteKeysIncludedInRun()
    {
        $siteKeysToRun = Settings::getValue('included_jobsite_keys');
        if(is_empty_value($siteKeysToRun)) {
	        $sitesKeysInclude = self::getAllEnabledJobSiteKeys();
	        if(!is_empty_value($sitesKeysInclude) && is_array($sitesKeysInclude))
	        {
	            $siteKeysToRun = array_values($sitesKeysInclude);
	            $cmdIncluded = self::getJobSitesCmdLineIncludedInRun();
	            if(!is_empty_value($cmdIncluded)) {
		            $siteKeysToRun = array_intersect($siteKeysToRun, $cmdIncluded);
	            }
		        $cfgExcluded = Settings::getValue('config_excluded_sites');
	            if(!is_empty_value($cfgExcluded)) {
		            $siteKeysToRun = array_diff($siteKeysToRun, $cfgExcluded);
	            }
	        }
	
	        Settings::setValue('included_jobsite_keys', $siteKeysToRun);
		}
    	return $siteKeysToRun;
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
     * @param array &$sites
     * @param string[] $countryCodes
     *
	 * @return array
*    * @throws \Exception
     */
    public static function doesCoverCountryCode($countryCode)
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
            LogMessage('Skipped ' . count($siteKeysOutOfSearchArea) .' JobSites not covering ' . implode(', ', $ccRun) .': ' . implode(', ', $siteKeysOutOfSearchArea) );
        }

    }

    /**
     * @var false|\LightnCandy\Closure|null
     */
    protected $_renderer = null;
    protected $_dirPluginsRoot = null;
    protected $_dirJsonConfigs = null;
    protected $_configJsonFiles = array();

    /**
     * JobSiteManager constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        SitePluginFactory::loadAndVerifyInstalledPlugins();

	    $is_init = Settings::getValue(self::class . '.initialized');

        if (null === $is_init || $is_init !== true) {

            $this->_syncDatabaseJobSitePluginClassList();

            Settings::setValue(self::class . '.initialized', true);
       }
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
	*/
    private function _syncDatabaseJobSitePluginClassList()
    {
        LogMessage('Synchronizing database JobSite records with JobSite plugin installation status...', null, null, null, $channel='plugins');
        $declaredPluginsBySiteKey = SitePluginFactory::getInstalledPlugins();

        $allDBJobSitesByKey = \JobScooper\DataAccess\JobSiteRecordQuery::create()
            ->find()
            ->toArray('JobSiteKey');
        if(is_empty_value($allDBJobSitesByKey)) {
			$allDBJobSitesByKey = array();
        }

        foreach($allDBJobSitesByKey as $jobSiteKey => $jobSiteFacts) {
			$objJobSite = self::getJobSiteByKey($jobSiteKey);
			if(null === $objJobSite)
			    throw new \Exception("{$jobSiteKey} database object could not be instantiated.");

            if(!array_key_exists($jobSiteKey, $declaredPluginsBySiteKey)) {
                if($objJobSite->getisDisabled() === false) {
                    LogWarning("{$jobSiteKey}: plugin class was not found; disabling job site.");
                }
                $objJobSite->setisDisabled(true);
            }
            else if(is_empty_value($jobSiteFacts['PhpClassName'])) {
                if($objJobSite->getisDisabled() === true) {
                    LogMessage("{$jobSiteKey}: new site plugin class was detected; enabling job site.");
                }
                $objJobSite->setisDisabled(false);
                $objJobSite->setPluginClassName($declaredPluginsBySiteKey[$jobSiteKey]);
            }
            $objJobSite->save();
            $objJobSite = null;
        }

        $missingJobSites = array_diff_key($declaredPluginsBySiteKey, $allDBJobSitesByKey);
        foreach ($missingJobSites as $jobSiteKey => $pluginClass) {
				LogMessage("{$jobSiteKey}: adding new jobsite for {$pluginClass} and enabling.");
                $dbrec = JobSiteRecordQuery::create()
                    ->filterByJobSiteKey($jobSiteKey)
                    ->findOneOrCreate();

                $dbrec->setJobSiteKey($jobSiteKey);
                $dbrec->setPluginClassName($pluginClass);
                $dbrec->setisDisabled(false);
                $dbrec->setDisplayName(str_replace('Plugin', '', $pluginClass));
                $dbrec->save();
                $dbrec = null;
        }

        $allEnabledJobSites = \JobScooper\DataAccess\JobSiteRecordQuery::create()
            ->findByisDisabled(false)
            ->toArray('JobSiteKey');
        if(is_empty_value($allEnabledJobSites)) {
			throw new \Exception("Error:  all JobSites are disabled due to plugin installation issues.  Cannot continue.");
        }

        $nEnabledSites = count($allEnabledJobSites);
        LogMessage("Loaded {$nEnabledSites} enabled jobsite plugins.");
        $dbJobSitesByKey = null;

	    Settings::setValue(self::class . '.enabled_sites', array_keys($allEnabledJobSites));

    }

}
