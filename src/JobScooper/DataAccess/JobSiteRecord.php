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

use JobScooper\DataAccess\Base\JobSiteRecord as BaseJobSiteRecord;
use JobScooper\DataAccess\Map\JobSiteRecordTableMap;
use JobScooper\DataAccess\Map\UserSearchSiteRunTableMap;
use JobScooper\SitePlugins\SitePluginFactory;
use JobScooper\Utils\Settings;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Skeleton subclass for representing a row from the 'job_site' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * magic methods:
 *
 * @method getSupportedCountryCodes() Gets the country codes from the JobSite plugin
 */
class JobSiteRecord extends BaseJobSiteRecord
{
    /**
     * @var \JobScooper\SitePlugins\Base\SitePlugin
     */
    private $_pluginObject = null;

    /**
	 * @return \JobScooper\SitePlugins\Base\SitePlugin
     * @throws \Exception
	*/
	public function getPlugin(){
	    if(null === $this->_pluginObject) {
            $this->instantiatePlugin();
	    }

	    return $this->_pluginObject;
	}

	/**
    * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
    * @return bool
    * @throws \Propel\Runtime\Exception\PropelException
    */
	public function preSave(ConnectionInterface $con = null)
    {
        if(null === $this->getResultsFilterType() && null !== $this->getPluginClassName()) {
            try {
                $plugin = SitePluginFactory::create($this->getJobSiteKey());
                if(null !== $plugin) {
                    $type = $plugin->getPluginResultsFilterType();
                    $this->setResultsFilterType($type);
                }
            }
            catch (\Exception $ex ) { }
            finally {
                $plugin = null;
            }
        }
        return parent::preSave($con);
    }


    /**
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    private function instantiatePlugin()
    {
        if (null !== $this->_pluginObject) {
            return;
        }

        $class = $this->getPluginClassName();
        try {
            if (is_empty_value($class)) {
            	$class = "Plugin{$this->getJobSiteKey()}";
	            if (!in_array($class, get_declared_classes())) {
	                throw new \InvalidArgumentException("Unable to find declared plugin class {$class} for {$this->getJobSiteKey()}.");
	            }
            }
            $this->_pluginObject = new $class();
        } catch (\Exception $ex) {
            LogError("Error instantiating jobsite {$this->getJobSiteKey()} plugin object by class [{$class}]:  {$ex->getMessage()}");
            $this->_pluginObject = null;
            unset($this->_pluginObject);
        }
    }

    /**
     * Derived method to catches calls to undefined methods.
     *
     *
     * @param string $method
     * @param mixed  $params
     *
     * @return array|string
     * @throws \Exception
    */
    public function __call($method, $params)
    {
    	$obj = null;
        if (method_exists($this, $method)) {
            $obj = $this;
        } else {
            $plugin = $this->getPlugin();
            if (null !== $plugin && method_exists($plugin, $method)) {
            	$obj = $plugin;
	        }
        }

        if(null !== $obj) {
           return call_user_func_array(array($obj, $method), $params);
        }
        $class = self::class;
        LogError("{$method} not found for class {$class}.");
        return false;
    }
    
    /**
     * @param string $countryCode
     *
	 * @return bool
*    * @throws \Exception
     */
    public function servicesCountryCode($countryCode)
    {
    	if(is_empty_value($countryCode)) {
    		return false;
        }

        if(is_array($countryCode) && \count($countryCode) >= 1) {
    		$countryCode = array_pop($countryCode);
        }

        $ccSite = $this->getSupportedCountryCodes();
        $ccMatches = array_intersect(array($countryCode), $ccSite);
        return (!is_empty_value($ccMatches));
    }

    private $_searchRunsForUsers = array();

    /**
     * @param array $userFacts
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
     * @return UserSearchSiteRun[]
     */
    private function addSiteRunsForUser($userFacts)
    {

        $siteCCs = $this->getSupportedCountryCodes();
        $user = User::getUserObjById($userFacts['UserId']);
        $searchPairs = $user->getActiveUserSearchPairs();

        foreach($searchPairs as $pair) {
            $pairCC = $pair->getCountryCode();
            $pairId = $pair->getUserSearchPairId();

            if (!is_empty_value($pairCC) && in_array($pairCC, $siteCCs)) {
                $plugin = $this->getPlugin();

                #
                # TODO/PERFORMANCE:  Need to add check for KWD not supported but LOC is supported.
                ##
                if($plugin->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED) && $plugin->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED)) {
                    $this->setResultsFilterType(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE_ALL_ONLY);

                    if (\count($this->_searchRunsForUsers) === 0) {

                        $searchrun = new UserSearchSiteRun();
                        $searchrun->setUserSearchPairId($pairId);
                        $searchrun->setJobSiteKey($this->getJobSiteKey());
                        $searchrun->setAppRunId(Settings::getValue('app_run_id'));
                        $searchrun->setStartedAt(time());
                        $searchrun->save();

                        $this->_searchRunsForUsers[$searchrun->getUserSearchSiteRunKey()] = $searchrun->toFlatArray();
                        $searchrun = null;

                        return $this->_searchRunsForUsers;
                    }
                }

                $searchrun = new UserSearchSiteRun();
                $searchrun->setUserSearchPairId($pairId);
                $searchrun->setJobSiteKey($this->getJobSiteKey());
                $searchrun->setAppRunId(Settings::getValue('app_run_id'));
                $searchrun->setStartedAt(time());
                $searchrun->save();

                $this->_searchRunsForUsers[$searchrun->getUserSearchSiteRunKey()] = $searchrun->toFlatArray();

                $searchrun = null;
            }
        }

        return $this->_searchRunsForUsers;
    }


    /**
     * @param $usersToRun
     * @return array
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
     */
    public function generateUserSiteRuns($usersToRun)
    {
        $totalRuns = 0;
        $totalSkipped = 0;

        if(!is_empty_value($this->_searchRunsForUsers)) {
            return $this->_searchRunsForUsers;
        }

        foreach($usersToRun as $userFacts) {

            $this->addSiteRunsForUser($userFacts);
        }


        $plugin = $this->getPlugin();

        if($plugin->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED)) {

            if ($plugin->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED)) {
                $this->setResultsFilterType(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE_ALL_ONLY);
            }
            if($plugin->isBitFlagSet(C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED) && !$plugin->isBitFlagSet(C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED)) {
                $this->setResultsFilterType(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE_ALL_BY_LOCATION);
            }

            if(\count($this->_searchRunsForUsers) > 1) {
                $keepSearch = array_pop($this->_searchRunsForUsers);
                foreach($this->_searchRunsForUsers as $key=>$search) {
                    $search['RunResultCode'] = UserSearchSiteRunTableMap::COL_RUN_RESULT_CODE_SKIPPED;
                    $obj = UserSearchSiteRunQuery::create()
                        ->findOneByUserSearchSiteRunId($search['UserSearchSiteRunId']);
                    $obj->fromArray($search);
                    $obj->save();
                    unset($obj);
                }
                unset($this->_searchRunsForUsers);
                $this->_searchRunsForUsers = array($keepSearch['UserSearchSiteRunId'] => $keepSearch);
            }
        }

        if (\count($this->_searchRunsForUsers) > 0) {
            $totalRuns = \count($this->_searchRunsForUsers);
            if (!is_empty_value($this->_searchRunsForUsers)) {
                UserSearchSiteRunManager::filterRecentlyRunUserSearchRuns($this->_searchRunsForUsers);
                if (!is_empty_value($this->_searchRunsForUsers)) {
                    $totalSkipped = $totalRuns - \count($this->_searchRunsForUsers);
                }
            }
        }
        LogMessage("{$totalRuns} search runs configured for {$this->getJobSiteKey()}; {$totalSkipped} searches were skipped.");

        $searchPairs = null;
        $sites = null;

        return $this->_searchRunsForUsers;
    }


}
