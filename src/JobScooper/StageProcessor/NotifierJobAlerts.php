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

namespace JobScooper\StageProcessor;

use JobScooper\DataAccess\GeoLocation;
use JobScooper\DataAccess\GeoLocationQuery;
use JobScooper\DataAccess\Map\UserJobMatchTableMap;
use JobScooper\DataAccess\User;
use JobScooper\Utils\JobsMailSender;
use Exception;
use JobScooper\Utils\Settings;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Class NotifierJobAlerts
 * @package JobScooper\StageProcessor
 */
class NotifierJobAlerts extends JobsMailSender
{
    const SHEET_MATCHES = 'new matches';
    const KEYS_MATCHES = array(
        'JobSiteKey',
        'JobPostingId',
        'PostedAt',
        'Company',
        'Title',
        'LocationDisplayValue',
        'EmploymentType',
        'Category',
        'Url');

    const SHEET_ALL_JOBS = 'all jobs';

    const KEYS_EXCLUDED = array(
        'JobSiteKey',
        'JobPostingId',
        'PostedAt',
        'Company',
        'Title',
        'LocationDisplayValue',
        'IsJobMatch',
        'IsExcluded',
        'OutOfUserArea',
        'DuplicatesJobPostingId',
        'MatchedUserKeywords',
        'MatchedNegativeTitleKeywords',
        'MatchedNegativeCompanyKeywords',
        'Url'
    );

    CONST HEADER_STYLE = ['fill'=>'#0645AD', 'font-style'=>'bold',  'halign'=>'center','border'=>'bottom'];
    
    private $_sheetFilters = [
        [ 'sheet_name' => NotifierJobAlerts::SHEET_MATCHES,
          'sheet_filter' => 'isUserJobMatchAndNotExcluded',
          'sheet_header' => [
	        'JobSiteKey' => ['type' => 'string', 'col_width' => 15, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'JobPostingId' => ['type' => 'string', 'col_width' => 7, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'PostedAt' =>['type' => 'string', 'col_width' => 25, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'Company' =>['type' => 'string', 'col_width' => 30, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'Title' =>['type' => 'string', 'col_width' => 40, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'LocationDisplayValue' =>['type' => 'string', 'col_width' => 20, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'EmploymentType' =>['type' => 'string', 'col_width' => 15, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'Category' =>['type' => 'string', 'col_width' => 15, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'Url' =>['type' => 'string', 'col_width' => 100, 'style' => NotifierJobAlerts::HEADER_STYLE ]
            ]
        ],
        [ 'sheet_name' => NotifierJobAlerts::SHEET_ALL_JOBS,
          'sheet_filter' => 'all',
          'sheet_header' => [
	        'JobSiteKey' => ['type' => 'string', 'col_width' => 15, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'JobPostingId' => ['type' => 'string', 'col_width' => 7, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'PostedAt' =>['type' => 'string', 'col_width' => 25, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'Company' =>['type' => 'string', 'col_width' => 30, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'Title' =>['type' => 'string', 'col_width' => 40, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'LocationDisplayValue' =>['type' => 'string', 'col_width' => 20, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'IsJobMatch' =>['type' => 'string', 'col_width' => 5, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'IsExcluded' =>['type' => 'string', 'col_width' => 5, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'OutOfUserArea' =>['type' => 'string', 'col_width' => 5, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'DuplicatesJobPostingId' =>['type' => 'string', 'col_width' => 7, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'MatchedUserKeywords' =>['type' => 'string', 'col_width' => 20, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'MatchedNegativeTitleKeywords' =>['type' => 'string', 'col_width' => 20, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'MatchedNegativeCompanyKeywords' =>['type' => 'string', 'col_width' => 15, 'style' => NotifierJobAlerts::HEADER_STYLE ],
	        'Url' =>['type' => 'string', 'col_width' => 100, 'style' => NotifierJobAlerts::HEADER_STYLE ]
            ]
		]
    ];

    const SHEET_RUN_STATS = 'search run stats';
    const PLAINTEXT_EMAIL_DIRECTIONS = 'Unfortunately, this email requires an HTML-capable email client to be read.';

    /**
     * NotifierJobAlerts constructor.
     */
    public function __construct()
    {
        parent::__construct(false);
    }

    /**
     * @param \JobScooper\DataAccess\User $user
     *
     * @throws \Exception
     * @return GeoLocation[]
     */
    private function _getSearchPairLocations(User $user)
    {

        $locs = array();
        $searchPairs = $user->getActiveUserSearchPairs();
        foreach ($searchPairs as $searchPair) {
            $arrSp = $searchPair->toArray();
            $locs[$arrSp['GeoLocationId']] = GeoLocationQuery::create()->findOneByGeoLocationId($arrSp['GeoLocationId']);
        }
        unset($searchPairs);
        return $locs;
    }

    /**
     * @param array $userFacts
     * @return bool
     * @throws \Exception
     */
    public function processRunResultsNotifications(array $userFacts)
    {
        startLogSection('Processing user notification alerts');
        $matches = null;

        if(null === $userFacts || null === $userFacts['UserId']) {
        	throw new \InvalidArgumentException('Cannot send notifications for a null UserId.');
        }
        $user = User::getUserObjById($userFacts['UserId']);

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Output the full jobs list into a file and into files for different cuts at the jobs list data
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $class = null;
        if (!$user->canNotifyUser() && !Settings::is_in_setting_value('command_line_args.jobsite', 'all')) {
            LogMessage('Notification would violate the user\'s notification frequency.  Skipping send');

            return false;
        }

        //
        // Send a separate notification for each GeoLocation the user had set
        //
        $userSearchPairLocs = $this->_getSearchPairLocations($user);
        if(null === $userSearchPairLocs) {
            throw new \InvalidArgumentException('Could not find geolocations from the user\'s user_search_pair');
        }
        foreach ($userSearchPairLocs as $geoLocation) {

            if ($matches !== null) {
                unset($matches);
            }
            $matches = array( 'all' => array());

            LogMessage("Building job notification list for {$userFacts['UserSlug']}'s keyword matches in {$geoLocation->getDisplayName()}...");
            $place = $geoLocation->getDisplayName();
			$geoLocationId = $geoLocation->getGeoLocationId();
            $arrNearbyIds = getGeoLocationsNearby($geoLocation);
            unset($geoLocation);

            $dbMatches = getAllMatchesForUserNotification(
                [UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_MARKED_READY_TO_SEND, Criteria::EQUAL],
                $arrNearbyIds,
                null,
                $userFacts
            );
			
            $matches['all'] = $dbMatches;
            // dump the full list of matches/excludes to JSON
            //
            LogMessage('Exporting ' . countAssociativeArrayValues($dbMatches) . ' UserJobMatch objects to JSON for use in notifications...');
            $pathJsonMatches = getDefaultJobsOutputFileName('', 'user-job-matches', 'json', '_', 'debug');
            writeJson($matches, $pathJsonMatches);

            $matches['all'] = null;

            LogMessage('Converting ' . countAssociativeArrayValues($dbMatches) . ' UserJobMatch objects to array data for use in notifications...');

            foreach ($dbMatches as $userMatchId => $item) {
                $matches['all'][$userMatchId] = $dbMatches[$userMatchId]->toFlatArrayForCSV();
            }

            unset($dbMatches);

            $matches['isUserJobMatchAndNotExcluded'] = [];
            if(\is_array($matches['all'])) {
	            $matches['isUserJobMatchAndNotExcluded'] = array_filter($matches['all'], 'isUserJobMatchAndNotExcluded');
            }

            if (countAssociativeArrayValues($matches['isUserJobMatchAndNotExcluded']) === 0) {
                $subject = 'No New Job Postings Found for ' . getRunDateRange();
            } else {
                $subject = countAssociativeArrayValues($matches['isUserJobMatchAndNotExcluded']) . " New {$place} Job Postings: " . getRunDateRange();
            }


            $this->_sendResultsNotification($matches, $subject, $userFacts, $geoLocationId);

            unset($matches);
            
            $arrNearbyIds = null;
        }

		$userSearchPairLocs = null;
		$userFacts = null;
        $user = null;
    }


    /**
     * @param array $userFacts
     * @return bool
     * @throws \Exception
     */
    public function processWeekRecapNotifications(array $userFacts)
    {
        if(null === $userFacts || null === $userFacts['UserId']) {
        	throw new \InvalidArgumentException('Cannot send notifications for a null UserId.');
        }
        $user = User::getUserObjById($userFacts['UserId']);

        //
        // Send a separate notification for each GeoLocation the user had set
        //
        foreach ($this->_getSearchPairLocations($user) as $geoLocation) {
            startLogSection("Processing week recap notification for {$user->getUserSlug()} in {$geoLocation->getDisplayName()}...");

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //
            // Output the full jobs list into a file and into files for different cuts at the jobs list data
            //
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            $class = null;

            LogMessage('Building job match lists for past week');
            $matches = array();
            $arrNearbyIds = getGeoLocationsNearby($geoLocation);
            $place = $geoLocation->getPlace();
            $geoLocationId = $geoLocation->getGeoLocationId();
            unset($geoLocation);

            $matches['all'] = getAllMatchesForUserNotification(
                [UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_MARKED_READY_TO_SEND, Criteria::EQUAL],
                $arrNearbyIds,
                7,
                $user
            );

            if (empty($matches['all'])) {
                $matches['all'] = array();
            } else {
                LogMessage('Converting ' . countAssociativeArrayValues($matches['all']) . ' UserJobMatch objects to array data for use in notifications...');

                foreach ($matches['all'] as $userMatchId => $item) {
                    $item = $matches['all'][$userMatchId]->toFlatArrayForCSV();
                    unset($matches['all'][$userMatchId]);
                    $matches['all'][$userMatchId] = $item;
                }
            }
            $matches['isUserJobMatchAndNotExcluded'] = array_filter($matches['all'], 'isUserJobMatchAndNotExcluded');

            $subject = "Weekly {$place} Roundup for " . getRunDateRange(7);
            $this->_sendResultsNotification($matches, $subject, $userFacts, $geoLocationId);

            unset($matches);
            $user = null;
        }
    }

    /**
     * @param array $userFacts
     * @return bool
     * @throws \Exception
     */
    private function _sendResultsNotification(&$matches, $resultsTitle, $userFacts, $geoLocationId=null)
    {
        $ret = false;

        if(!is_array($matches) || !array_key_exists('all', $matches) ){
        	LogWarning("No results were found to notify user about.");
        	return $ret;
        }
        $allJobMatchIds = array_keys($matches['all']);

        //
        // Output the final files we'll send to the user
        //
        $arrFilesToAttach = array();
        startLogSection('Generating Excel file for user\'s job match results...');
        try {

           $pathExcelResults = $this->_generateMatchResultsExcelFile($matches);
           $arrFilesToAttach[] = $pathExcelResults;

        } catch (\Exception $ex) {
            handleException($ex);
        } finally {
            unset($ss);
            endLogSection('Excel file generated.');
            unset($matches['all']);
        }

        //
        // For our final output, we want the jobs to be sorted by company and then role name.
        // Create a copy of the jobs list that is sorted by that value.
        //
        startLogSection('Generating HTML & text email content for user ');

        try {
            $messageHtml = $this->_generateHTMLEmailContent($resultsTitle, $matches, count($allJobMatchIds), $userFacts, $geoLocationId);
            if (empty($messageHtml)) {
                throw new Exception("Failed to generate email notification content for email {$resultsTitle} to sent to user {$userFacts['UserSlug']}.");
            }

            endLogSection('Email content ready to send.');

            //
            // Send the email notification out for the completed job
            //
            startLogSection("Sending email to user {$userFacts['EmailAddress']}...");

            $sendToUser = User::getUserObjById($userFacts['UserId']);
            $ret = $this->sendEmail(self::PLAINTEXT_EMAIL_DIRECTIONS, $messageHtml, $arrFilesToAttach, $resultsTitle, 'results', $sendToUser);
            if ($ret !== false && $ret !== null) {

                //
                // Mark the user job matches we just notified the user about as sent unless
                // we are in debug/developer mode OR we didn't run all job sites.  (The latter is so that
                // we will notify the users again in a full report run.  Otherwise they would get annoyingly small
                // separate reports instead.
                //
                if (!isDebug() && Settings::is_in_setting_value('command_line_args.jobsite', 'all') &&
                    !is_empty_value($allJobMatchIds)) {
                    $now = new \DateTime();
                    $sendToUser->setLastNotifiedAt($now);
                    $sendToUser->save();
                    updateUserJobMatchesStatus($allJobMatchIds, UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_SENT);
                }
            }
            endLogSection(' Email send completed...');
        } catch (Exception $ex) {
            endLogSection(' Email send failed.');
            handleException($ex);
        } finally {
            $sendToUser = null;
            unset($messageHtml, $matches);
        }

        //
        // We only keep interim files around in debug mode, so
        // after we're done processing, delete the interim HTML file
        //
        if (isDebug() !== true) {
            foreach ($arrFilesToAttach as $filepath) {
                if (file_exists($filepath) && is_file($filepath)) {
                    LogMessage('Deleting local attachment file ' . $filepath . PHP_EOL);
                    unlink($filepath);
                }
            }
        }

        endLogSection(' User Results Notification.');

        return $ret;
    }

    /**
     * @param $arrJobsToNotify
     * @return string
     */
    private function _generateMatchResultsExcelFile(&$arrJobsToNotify)
    {
        $ss = new \XLSXWriter();
        $pathExcelResults = getDefaultJobsOutputFileName('', 'JobMatches', 'XLSX', '_', 'debug');
	    LogMessage("Writing final workbook for user notifications to {$pathExcelResults}...");
        foreach ($this->_sheetFilters as $sheetParams) {
            $sheetName = $sheetParams['sheet_name'];
            $index = $sheetParams['sheet_filter'];
            $header_facts = $sheetParams['sheet_header'];
            $keys = array_keys($header_facts);
            $data = $arrJobsToNotify[$index];

            LogMessage("Writing jobs to {$sheetName} worksheet...");

            LogDebug('Reordering the data array to match our column output order...');

            $headerFields = array_combine(array_keys($header_facts), array_column($header_facts, 'type'));
            $col_widths = array_column($header_facts, 'col_width');
            
            $ss->writeSheetHeader($sheetName, $headerFields, $col_options = [ 'freeze_rows' => 1, 'auto_filter' => true, 'widths' => $col_widths ] );
            if(!is_empty_value($data)) {
                array_change_key_order($data, $keys);
                foreach($data as $row)
                    $ss->writeSheetRow($sheetName, $row);
            }
        }
 
        $ss->writeToFile($pathExcelResults);
        return $pathExcelResults;
    }

    /**
     * @param $subject
     * @param $matches
     * @param array $userFacts
     *
     * @return mixed
     * @throws \Exception
     */
    private function _generateHTMLEmailContent($subject, &$matches, $totalJobs, $userFacts, $geoLocationId=null)
    {
        try {
            $renderer = loadTemplate(implode(DIRECTORY_SEPARATOR, array(__ROOT__, 'src', 'assets', 'templates', 'html_email_results_responsive.tmpl')));

            assert(array_key_exists('isUserJobMatchAndNotExcluded', $matches));

            $data = array(
                'Email'      => array(
                    'Subject'                => $subject,
                    'BannerText'             => 'JobScooper',
                    'Headline'               => $subject,
                    'IntroText'              => '',
                    'PreHeaderText'          => '',
                    'TotalJobMatchCount'     => countAssociativeArrayValues($matches['isUserJobMatchAndNotExcluded']),
                    'TotalJobsReviewedCount' => $totalJobs,
                    'PostFooterText'         => 'generated by ' . __APP_VERSION__ . ' on ' . gethostname()
                ),
                'Search'     => array(
                    'Locations' => null,
                    'Keywords'  => null
                ),
                'JobMatches' => $matches['isUserJobMatchAndNotExcluded']
            );

            $data['Search']['Keywords'] = implode(', ', $userFacts['SearchKeywords']);

            if (!empty($geoLocationId)) {
                $geoLocation = GeoLocationQuery::create()
                    ->findOneByGeoLocationId($geoLocationId);
                if (null !== $geoLocation) {
                    $data['Search']['Locations'] = $geoLocation->getDisplayName();
                } else {
                    $data['Search']['Locations'] = '[unknown]';
                }
            } else {
	            $user = User::getUserObjById($userFacts['UserId']);
                $locations = $user->getSearchGeoLocations();
                $user = null;
                $searchLocNames = array();
                if (!empty($locations)) {
                    foreach ($locations as $loc) {
                        $searchLocNames[] = $loc->getDisplayName();
                    }

                    $data['Search']['Locations'] = implode(', ', $searchLocNames);
                    $locations = null;
                }
            }

            LogMessage('Generating HTML for user email notification...');
            $html = call_user_func($renderer, $data);
            file_put_contents(getDefaultJobsOutputFileName('email', 'notification', 'html', '_', 'debug'), $html);

            unset($renderer, $data);

            return $html;
        } catch (Exception $ex) {
            handleException($ex);
        } finally {
            unset($renderer);
        }
    }


}
