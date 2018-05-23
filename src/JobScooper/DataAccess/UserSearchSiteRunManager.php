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

use JobScooper\Utils\SimpleHTMLHelper;
use Propel\Runtime\ActiveQuery\Criteria;


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
        $siteWaitCutOffTime = date_sub(new \DateTime(), date_interval_create_from_date_string('23 hours'));
        $srKeys = array_keys($searchRuns);
        $user = User::getUserObjById($searchRuns[$srKeys[0]]['UserId']);
		$searchPairIds = $user->getActiveUserSearchPairIds();

        $potentialRunsToSkip = UserSearchSiteRunQuery::create()
            ->addAsColumn('LastCompleted', 'MAX(user_search_site_run.date_ended)')
            ->select(array('JobSiteKey', 'UserSearchPairId', 'LastCompleted'))
            ->filterByUserSearchPairId($searchPairIds, Criteria::IN)
            ->filterByRunResultCode(array('successful', 'failed'))
            ->groupBy(array('JobSiteKey', 'UserSearchPairId'))
            ->find()
            ->toArray();

        // Filter jobsite/searchpair combos that can be skipped given the lastcompleted date.
        //
        if (!empty($potentialRunsToSkip)) {
            // Remove any that ran before the cache cut off time, not since that time.
            // We are left with only those we should skip, aka the ones that
            // ran after our cutoff time
            //
            foreach ($potentialRunsToSkip as $potentialSkip) {
            	$jobSiteKey = $potentialSkip['JobSiteKey'];
                if (new \DateTime($potentialSkip['LastCompleted']) >= $siteWaitCutOffTime) {
                    $filteredSiteSearchIdPairs["{$jobSiteKey}-{$potentialSkip['UserSearchPairId']}"] = $potentialSkip;
                }

            }
            $filterRuns = array_filter($searchRuns, function ($v) use ($filteredSiteSearchIdPairs) {
                if(array_key_exists('JobSiteKey', $v) && array_key_exists('UserSearchPairId', $v))
                {
                    $sitePairKey = "{$v['JobSiteKey']}-{$v['UserSearchPairId']}";
                    if(array_key_exists($sitePairKey, $filteredSiteSearchIdPairs)) {
                        return true;
                    }
                }
                return false;
            });

            if(!is_empty_value($filterRuns)) {
                foreach($filterRuns as $runKey => $searchFacts) {
                    $searchRun = self::getSearchRunObjFromFacts($searchFacts);
                    $searchRun->setRunResultCode('skipped');
                    $searchRun->save();
                    $searchRun = null;
                    $searchRuns[$runKey] = null;
                    unset($searchRuns[$runKey]);
                    $skippedSearchRuns[] = $runKey;
               }
            }
		}

        if (!empty($skippedSearchRuns)) {
            LogMessage('Skipping the following searches because they have run since ' . $siteWaitCutOffTime->format('Y-m-d H:i') . ': ' . implode(', ', $skippedSearchRuns));
        }

    }



}

