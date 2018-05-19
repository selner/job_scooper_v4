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

use JobScooper\Builders\JobSitePluginBuilder;
use JobScooper\DataAccess\UserSearchSiteRunQuery;
use JobScooper\Logging\ErrorEmailLogHandler;
use JobScooper\Utils\JobsMailSender;
use JobScooper\Utils\SimpleHTMLHelper;
use Propel\Runtime\ActiveQuery\Criteria;
use JobScooper\DataAccess\Map\UserJobMatchTableMap;

/**
 * Class NotifierDevAlerts
 * @package JobScooper\StageProcessor
 */
class NotifierDevAlerts extends JobsMailSender
{
	/**
	 * NotifierDevAlerts constructor.
	 */
	function __construct()
	{
		parent::__construct(false);
	}

	/**
	 * @throws \Propel\Runtime\Exception\PropelException
	 * @throws \Exception
	 */
	function getRunResultData()
	{
		startLogSection("Processing run result summary for devs...");

		$countsByPlugin = getAllMatchesForUserNotification(
			[UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_MARKED_READY_TO_SEND, Criteria::EQUAL],
			null,
			2,
			null,
			$countsOnly = true
		);

		$includedSites = JobSitePluginBuilder::getIncludedJobSites(false);
		$arrPluginResults = array_fill_keys(array_keys($includedSites), array());
		foreach ($arrPluginResults as $k => $val) {
			$arrPluginResults[$k] = array(
				'JobSiteKey'             => $k,
				'TotalNewUserJobMatches' => "N/A",
				'TotalNewJobPostings'    => "N/A",
				'PlugInReference'        => $includedSites[$k]
			);

			if (in_array($k, $countsByPlugin)) {
				if (in_array($k, $countsByPlugin)) {
					$arrPluginResults[$k]['TotalNewUserJobMatches'] = $countsByPlugin[$k]['TotalNewUserJobMatches'];
					$arrPluginResults[$k]['TotalNewJobPostings'] = $countsByPlugin[$k]['TotalNewJobPostings'];
				}
			}
		}

		ksort($arrPluginResults);

		return $arrPluginResults;
	}

	function getRunResultHtml($arrPluginResults)
	{
		$renderer = loadTemplate(join(DIRECTORY_SEPARATOR, array(__ROOT__, "src", "assets", "templates", "partials", "html_email_run_counts.tmpl")));
		$subject = "JobScooper Run Result Counts[" . gethostname() . "]: for " . getRunDateRange(5);
		$data = array(
			"Email"         => array(
				"Headline" => $subject,
			),
			"PluginResults" => $arrPluginResults
		);

		return call_user_func($renderer, $data);
	}

	/**
	 * @throws \Exception
	 */
	function getPluginErrorData()
	{

		startLogSection("Processing plugin error alerts...");
		$reportJobSites = array();

		$startDate = new \DateTime();
		$strMod = "-5 days";
		$dateDaysAgo = $startDate->modify($strMod);

		$errFields = array("JobSiteKey", "RunResultCode");
		$returnedJobSites = UserSearchSiteRunQuery::create()
			->select($errFields)
			->addAsColumn('LastStartedAt', 'max(date_started)')
			->addAsColumn('LastCompletedAt', 'max(date_ended)')
			->addAsColumn('CountFailedRuns', 'count(*)')
			->addAsColumn('LastRunId', 'max(user_search_site_run_id)')
			->groupBy($errFields)
			->filterByRunResultCode("failed", Criteria::EQUAL)
			->filterByEndedAt($dateDaysAgo, Criteria::GREATER_EQUAL)
			->having('CountFailedRuns >= ?', 3, \PDO::PARAM_INT)
			->find()
			->toArray("JobSiteKey");

		$lastJobsiteSuccess = UserSearchSiteRunQuery::create()
			->select($errFields)
			->addAsColumn('LastCompletedAt', 'max(date_ended)')
			->groupBy($errFields)
			->filterByRunResultCode("successful", Criteria::EQUAL)
			->filterByEndedAt($dateDaysAgo, Criteria::GREATER_EQUAL)
			->find()
			->toArray("JobSiteKey");

		$failedJobSites = array();
		foreach ($returnedJobSites as $jobsite) {
			$jsKey = $jobsite['JobSiteKey'];
			if (!(array_key_exists($jsKey, $lastJobsiteSuccess) && $jobsite['LastCompletedAt'] < $lastJobsiteSuccess[$jsKey]['LastCompletedAt'])) {
				if($jobsite['CountFailedRuns'] >= 3) {
					$failedJobSites[$jsKey] = $jobsite;
				}
			}
		}

		if (!empty($failedJobSites)) {
			$searchRunIds = array_column($failedJobSites, "LastRunId");
			$errFields = array("JobSiteKey", "RunResultCode", "RunErrorDetails", "RunErrorPageHtml");
			$reportJobSites = UserSearchSiteRunQuery::create()
				->select($errFields)
				->filterByUserSearchSiteRunId($searchRunIds, Criteria::IN)
				->filterByJobSiteKey(array_keys($failedJobSites), Criteria::IN)
				->find()
				->toArray("JobSiteKey");

			foreach (array_keys($failedJobSites) as $jsKey) {
				if (!array_key_exists($jsKey, $reportJobSites))
					$reportJobSites[$jsKey] = array();

				$reportJobSites[$jsKey] = array_merge($reportJobSites[$jsKey], $failedJobSites[$jsKey]);
				$reportJobSites[$jsKey]['RunErrorDetails'] = nl2br(htmlentities($reportJobSites[$jsKey]['RunErrorDetails']));
			}
			ksort($reportJobSites);
		}

		if(is_empty_value($reportJobSites) )
			$reportJobSites = array();
		return $reportJobSites;
	}

	/**
	 * @throws \Exception
	 */
	function processPluginErrorAlert()
	{
		// TODO:  finish this email alert for plugin errors.  (Currently reports empty error values.)

		/*
		$reportJobSites = $this->getPluginErrorData();
		$countsJobSites = $this->getRunResultData();
		$monolog_error_content = ErrorEmailLogHandler::getEmailErrorLogContent();
		$jobSiteReports = array_merge_recursive_distinct($countsJobSites, $reportJobSites);

		$renderer = loadTemplate(join(DIRECTORY_SEPARATOR, array(__ROOT__, "src", "assets", "templates", "html_email_plugin_error_alert.tmpl")));
		$subject = "Plugin Results for [" . gethostname() . "]: for " . getRunDateRange(5);
		$data = array(
			"Email"                 => array(
				"Subject"       => $subject,
				"BannerText"    => "",
				"Headline"      => $subject,
				"IntroText"     => "",
				"PreHeaderText" => ""
			),
			"Plugins"          => $jobSiteReports,
			"monolog_error_content" => $monolog_error_content
		);

		$html = call_user_func($renderer, $data);

//			$objPageHtml = new SimpleHTMLHelper($html);
//			$filepath = $objPageHtml->debug_dump_to_file();
//			LogMessage("Debug template for html error email saved to: {$filepath}");

		$attach = array();
		if (!empty($GLOBALS['logger']) && isset($GLOBALS['logger'])) {
			$logpath = $GLOBALS['logger']->getMainLogFilePath();
			if (!empty($logpath))
				$attach[] = $logpath;
		}

		foreach ($reportJobSites as $jobsite) {
			if (array_key_exists("RunErrorPageHtml", $jobsite) && is_file($jobsite["RunErrorPageHtml"]))
				$attach[] = $jobsite['RunErrorPageHtml'];
		}

		$ret = false;
		try {
			$mailer = new JobsMailSender(true);
			$ret = $mailer->sendEmail("", $html, $attach, $subject, "errors");
		} catch (\Exception $ex) {
			handleException($ex);
		} finally {
			if ($ret !== true || isDebug())
			{
				file_put_contents(getDefaultJobsOutputFileName("dev_error_email", "notification", "html", "_", "debug"), $html);
			}
			unset($mailer);
		}
		unset($renderer);
		unset($reportJobSites);
		unset($returnedJobSites);

		endLogSection(" Dev Plugin Failure Notification.");
*/
	}
}