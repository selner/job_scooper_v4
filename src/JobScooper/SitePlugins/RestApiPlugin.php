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

namespace JobScooper\SitePlugins;

use JobScooper\DataAccess\UserSearchSiteRun;
use JobScooper\SitePlugins\Base\SitePlugin;

/**
 * Class ApiPlugin
 * @package JobScooper\BasePlugin\Classes
 */
class RestApiPlugin extends SitePlugin
{
    /**
     * ApiPlugin constructor.
     *
     * @param null $strBaseDir
     * @throws \Exception
     */
    public function __construct($strBaseDir = null)
    {
        $this->additionalBitFlags[] = C__JOB_PAGECOUNT_NOTAPPLICABLE;
        $this->pluginResultsType = C__JOB_SEARCH_RESULTS_TYPE_RESTSAPI__;

        parent::__construct();
    }

    /**
     * @param \JobScooper\DataAccess\UserSearchSiteRun $searchDetails
     *
     * @throws \Exception
     */
    protected function _getMyJobsForSearchFromJobsAPI_(UserSearchSiteRun $searchDetails)
    {
        $nItemCount = 0;

        $this->log('Downloading count of ' . $this->JobSiteName . ' jobs for search ' . $searchDetails->getUserSearchSiteRunKey());

        $pageNumber = 1;
        $noMoreJobs = false;
        while ($noMoreJobs != true) {
            $arrPageJobsList = [];
            $apiJobs = $this->getSearchJobsFromAPI($searchDetails);
            if (null === $apiJobs) {
                $this->log('Warning: ' . $this->JobSiteName . '[' . $searchDetails->getUserSearchSiteRunKey() . '] returned zero jobs from the API.' . PHP_EOL, \Monolog\Logger::WARNING);

                return;
            }

            $this->saveSearchReturnedJobs($arrPageJobsList, $searchDetails);
            if (count($arrPageJobsList) < $this->JobListingsPerPage) {
                $noMoreJobs = true;
            }
            $pageNumber++;
        }

        $this->log($this->JobSiteName . '[' . $searchDetails->getUserSearchSiteRunKey() . ']' . ': ' . $nItemCount . ' jobs found.' . PHP_EOL);
    }

}