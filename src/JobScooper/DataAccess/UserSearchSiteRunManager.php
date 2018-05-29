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

use JobScooper\DataAccess\Map\JobSiteRecordTableMap;use JobScooper\Utils\SimpleHTMLHelper;
use Propel\Runtime\ActiveQuery\Criteria;use Propel\Runtime\ActiveQuery\ModelCriteria;


class UserSearchSiteRunManager {


    /**
	* @param $searchRunFacts
	*
	* @return \JobScooper\DataAccess\UserSearchSiteRun
	*/
    static function getSearchRunObjFromFacts($searchRunFacts) {
	    if(is_empty_value($searchRunFacts) || !is_array($searchRunFacts) || !array_key_exists('UserSearchSiteRunId', $searchRunFacts) ||
    	    is_empty_value($searchRunFacts['UserSearchSiteRunId'])) {
        	throw new \InvalidArgumentException('Invalid UserSearchSiteRun details were passed to mark as failed.');
        }

		return self::getSearchRunObjectById($searchRunFacts['UserSearchSiteRunId']);
    }

    /**
	* @param $searchRunId
	*
	* @return \JobScooper\DataAccess\UserSearchSiteRun
	*/
    static function getSearchRunObjectById($searchRunId) {
      $searchRun = UserSearchSiteRunQuery::create()
            ->findOneByUserSearchSiteRunId($searchRunId);

        if(null === $searchRun) {
        	throw new \InvalidArgumentException("Could not find UserSearchSiteRun with UserSearchSiteRunId = {$searchRunId}");
        }
        return $searchRun;
    }

    /**
	* @param $searchRunFacts
	*
	* @throws \Propel\Runtime\Exception\PropelException
	*/
    static function setRunSucceededByFacts($searchRunFacts)
    {
    	self::setRunResultCodeByFacts($searchRunFacts, 'successful');
    }

    /**
	* @param $searchRunFacts
	* @param $code
	*
	* @throws \Propel\Runtime\Exception\PropelException
	*/
    static function setRunResultCodeByFacts($searchRunFacts, $code)
    {
        $searchRun = self::getSearchRunObjFromFacts($searchRunFacts);

        switch ($code) {
            case 'failed':
                break;

            case 'successful':
                $searchRun->removeRunErrorDetail(array());
                break;

            case 'skipped':
                break;

            case 'not-run':
            case 'excluded':
            default:
                break;
        }

        $searchRun->setRunResultCode($code);

        $searchRun->setEndedAt(time());
		$searchRun->save();
		$searchRun = null;
    }

    /**
	* @param $searchFacts
	*
	* @return integer|null
	* @throws \Propel\Runtime\Exception\PropelException
	*/
    static function getUserIdFromSearchFacts($searchFacts)
    {
    	$ret = null;
    	$searchRun = self::getSearchRunObjFromFacts($searchFacts);
    	if(null !== $searchRun) {
	        $searchPair = $searchRun->getUserSearchPairFromUSSR();
	        if(null !== $searchPair) {
	            $ret = $searchPair->getUserId();
	        }
    		$searchPair = null;
    	}
		$searchRun = null;
    	return $ret;
    }


    /**
	* @param $searchRunFacts
	* @param $err
	* @param \JobScooper\Utils\SimpleHTMLHelper|null $objPageHtml
	*
	* @throws \Propel\Runtime\Exception\PropelException
	*/
    static function failRunWithErrorMessage($searchRunFacts, $err, SimpleHTMLHelper $objPageHtml=null)
    {
        $arrV = '';
        if (is_a($err, \Exception::class) || is_subclass_of($err, \Exception::class)) {
            $arrV = array((string)$err);
        } elseif (is_object($err)) {
            $arrV = get_object_vars($err);
            $arrV['toString'] = (string)$err;
        } elseif (is_string($err)) {
            $arrV = array($err);
        }

        $searchRun = self::getSearchRunObjFromFacts($searchRunFacts);
        $searchRun->setRunResultCode('failed');
        if (null !== $objPageHtml) {
            try {
                $filepath = $objPageHtml->debug_dump_to_file();
                $searchRun->setRunErrorPageHtml($filepath);
            } catch (\Exception $ex) {
                LogWarning('Failed to save HTML for page that generated the error.');
            }
        }
        $searchRun->setRunErrorDetails($arrV);
        $searchRun->save();
        $searchRun = null;
    }


    /**
     * @param array $searchRuns
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public static function filterRecentlyRunUserSearchRuns(&$searchRuns)
    {
        $skippedSearchRuns = array();
        $dtSiteWaitPrevRunCutOff = date_sub(new \DateTime(), date_interval_create_from_date_string('23 hours'));
        $srKeys = array_keys($searchRuns);
        $user = User::getUserObjById($searchRuns[$srKeys[0]]['UserId']);
		$searchPairIds = $user->getActiveUserSearchPairIds();
		$user = null;

        // Remove any that ran before the cache cut off time, not since that time.
        // We are left with only those we should skip, aka the ones that
        // ran after our cutoff time
        //
        $runsToSkip = UserSearchSiteRunQuery::create()
            ->addAsColumn('LastCompleted', 'MAX(user_search_site_run.date_ended)')
            ->select(array('JobSiteKey', 'UserSearchPairId', 'EndedAt', 'JobSiteFromUSSR.ResultsFilterType', 'UserSearchPairFromUSSR.GeoLocationId'))
			->join('UserSearchPairFromUSSR')
		        ->addRelationSelectColumns('UserSearchPairFromUSSR')
			->join("JobSiteFromUSSR")
		        ->addRelationSelectColumns('JobSiteFromUSSR')
            ->filterByUserSearchPairId($searchPairIds, Criteria::IN)
            ->filterByRunResultCode(array('successful', 'failed'))
            ->filterByEndedAt($dtSiteWaitPrevRunCutOff, Criteria::GREATER_EQUAL)
            ->groupBy(array('JobSiteKey', 'UserSearchPairId', 'EndedAt'))
            ->find()
            ->toArray();

        // Filter jobsite/searchpair combos that can be skipped given the lastcompleted date.
        //
        if (!empty($runsToSkip)) {
        	$resultsFilterEnums = JobSiteRecordTableMap::getValueSet(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE);
	        foreach($runsToSkip as $skip) {
	        	foreach($searchRuns as $runKey => $run) {
					if($skip['JobSiteKey'] === $run['JobSiteKey']) {
						if($resultsFilterEnums[$skip['JobSiteFromUSSR.ResultsFilterType']] === JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE_ALL_BY_LOCATION &&
							(int)$skip['UserSearchPairFromUSSR.GeoLocationId'] === (int)$run['GeoLocationId'])
					    {
							$skippedSearchRuns[$runKey] = $run;
						} elseif((int)$skip['UserSearchPairId'] === (int)$run['UserSearchPairId']) {
							$skippedSearchRuns[$runKey] = $run;
						}
					}
	        	}
            }
        }
        
        foreach($searchRuns as $runKey => $run) {
			if(!is_empty_value($run['GeoLocationId'])) {
				$geolocation = GeoLocationQuery::create()->findOneByGeoLocationId($run['GeoLocationId']);
				if(null !== $geolocation) {
					$ccode = $geolocation->getCountryCode();
					$jobSite = JobSiteRecordQuery::create()->findOneByJobSiteKey($run['JobSiteKey']);
					if($jobSite->servicesCountryCode($ccode) === false) {
						$skippedSearchRuns[$runKey] = $run;
					}
				}
				
			}
        
        }

        if (!is_empty_value($skippedSearchRuns)) {
        	foreach($skippedSearchRuns as $runKey => $run) {
                $search = self::getSearchRunObjFromFacts($run);
                $search->setRunResultCode('skipped');
                $search->save();
                $search = null;

                $searchRuns[$runKey] = null;
                unset($searchRuns[$runKey]);
			}

            LogMessage('Skipping ' . count(array_keys($skippedSearchRuns)) . ' searches because they have run since ' . $dtSiteWaitPrevRunCutOff->format('Y-m-d H:i') . ': ' . implode(', ', array_keys($skippedSearchRuns)));
        }

    }


}

