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

use JobScooper\SitePlugins\SitePluginFactory;
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
     * @return array
     * @throws \Exception
     */
    public static function getIncludedSitesByCountry()
    {
        $arrIncludeSitesByCountryCode = Settings::getValue('jobsitemanager.included_sites_by_country_code');
        if(!is_empty_value($arrIncludeSitesByCountryCode)) {
            return $arrIncludeSitesByCountryCode;
        }

        $arrIncludeSitesByCountryCode = array('US' => []);

        $includedSites = self::getJobSitesIncludedInRun();
        foreach($includedSites as $site) {
            $ccJobSite = $site->getSupportedCountryCodes();
            if(!is_empty_value($ccJobSite)) {
                foreach ($ccJobSite as $cc) {
                    if (!array_key_exists($cc, $arrIncludeSitesByCountryCode)) {
                        $arrIncludeSitesByCountryCode[$cc] = [];
                    }
                    $arrIncludeSitesByCountryCode[$cc][$site->getJobSiteKey()] = $site->getJobSiteKey();
                }
            }
        }

        Settings::setValue('jobsitemanager.included_sites_by_country_code', $arrIncludeSitesByCountryCode);

        return $arrIncludeSitesByCountryCode;
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
     * @return array
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
					LogMessage('The following job sites have been set as excluded in the config file: ' . implode(", ", $cfgExcluded));
		            $siteKeysToRun = array_diff($siteKeysToRun, $cfgExcluded);
	            }
	        }
	
	        /** @noinspection NotOptimalIfConditionsInspection */
	        if(is_empty_value($siteKeysToRun)) {
	        	LogWarning('No job sites set to run.  They were all excluded or marked disabled.');
	        }
	        Settings::setValue('included_jobsite_keys', $siteKeysToRun);
			
        }

		sort($siteKeysToRun);
    	return $siteKeysToRun;
    }
    
    /**
     * @return array|mixed
     * @throws \Exception
     */
    public static function getJobSitesCmdLineIncludedInRun()
    {

        $cmdLineSites = Settings::getValue('command_line_args.jobsite');
        if(is_empty_value($cmdLineSites)) {
        	return array();
        }

        // can't call GetAllJobSiteKeys here because we will be in a recursion loop if we do
        if ($cmdLineSites == "all" || in_array('all', $cmdLineSites)) {
            return Settings::getValue('all_jobsite_keys');
        }

        return $cmdLineSites;
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
        LogMessage('Synchronizing database JobSite records with JobSite plugin installation status...', null, null, null, $log_topic='plugins');
        $declaredPluginsBySiteKey = SitePluginFactory::getInstalledPlugins();

        $allDBJobSitesByKey = JobSiteRecordQuery::create()
            ->find()
            ->toArray('JobSiteKey');
        if(is_empty_value($allDBJobSitesByKey)) {
			$allDBJobSitesByKey = array();
        }

        ksort($allDBJobSitesByKey);
        foreach($allDBJobSitesByKey as $jobSiteKey => $jobSiteFacts) {
			$objJobSite = self::getJobSiteByKey($jobSiteKey);
            if(null === $objJobSite)
                throw new \Exception("{$jobSiteKey} database object could not be instantiated.");

            if($objJobSite->getisDisabled() === true) {
                LogWarning("{$jobSiteKey}: is currently marked disabled in the database.  Skipping {$jobSiteKey}.");
            }
            else {


                if (!array_key_exists($jobSiteKey, $declaredPluginsBySiteKey)) {
                    if ($objJobSite->getisDisabled() === false) {
                        LogWarning("{$jobSiteKey}: plugin class was not found; disabling job site.");
                    }
                    $objJobSite->setisDisabled(true);
                } else if (!array_key_exists('PhpClassName', $jobSiteFacts) || is_empty_value($jobSiteFacts['PhpClassName'])) {
                    $objJobSite->setisDisabled(false);
                    $objJobSite->setPluginClassName($declaredPluginsBySiteKey[$jobSiteKey]);
                }
                $objJobSite->save();
            }
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

        $allEnabledJobSites = JobSiteRecordQuery::create()
            ->findByisDisabled(false)
            ->toArray('JobSiteKey');
        if(is_empty_value($allEnabledJobSites)) {
			throw new \Exception("Error:  all JobSites are disabled due to plugin installation issues.  Cannot continue.");
        }

        ksort($allEnabledJobSites);
        $nEnabledSites = \count($allEnabledJobSites);
        LogMessage("Loaded {$nEnabledSites} enabled jobsite plugins.");
	    Settings::setValue(self::class . '.enabled_sites', array_keys($allEnabledJobSites));

        $dbJobSitesByKey = null;
		$allDBJobSitesByKey = null;
		unset($allEnabledJobSites);
    }

}
