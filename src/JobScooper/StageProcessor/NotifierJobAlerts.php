<?php
/**
 * Copyright 2014-17 Bryan Selner
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
use JobScooper\Utils\JobsMailSender;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

/**
 * Class NotifierJobAlerts
 * @package JobScooper\StageProcessor
 */
class NotifierJobAlerts extends JobsMailSender
{
	const SHEET_MATCHES = "new matches";
	const KEYS_MATCHES = array(
		"JobSiteKey",
		"JobPostingId",
		"PostedAt",
		"Company",
		"Title",
		"LocationDisplayValue",
		"EmploymentType",
		"Category",
		"Url");

	const SHEET_EXCLUDED_MATCHES = "excluded job matches";
	const SHEET_EXCLUDED_ALL = "all excluded jobs";
	const KEYS_EXCLUDED = array(
		"JobSiteKey",
		"JobPostingId",
		"PostedAt",
		"Company",
		"Title",
		"LocationDisplayValue",
		"IsJobMatch",
		"IsExcluded",
		"OutOfUserArea",
		"DuplicatesJobPostingId",
		"MatchedUserKeywords",
		"MatchedNegativeTitleKeywords",
		"MatchedNegativeCompanyKeywords",
		"Url"
	);
	const SHEET_RUN_STATS = "search run stats";
	const PLAINTEXT_EMAIL_DIRECTIONS = "Unfortunately, this email requires an HTML-capable email client to be read.";
	static $styleHyperlink = array(
		'font'      => array(
			'underline' => Font::UNDERLINE_SINGLE,
			'color'     => array(
				'rgb' => '0645AD'
			)
		),
		'alignment' => array(
			'horizontal' => Alignment::HORIZONTAL_LEFT,
			'wrapText'   => false
		)
	);
	protected $arrAllUnnotifiedJobs = array();
	private $_arrJobSitesForRun = null;

	/**
	 * NotifierJobAlerts constructor.
	 */
	function __construct()
	{
		parent::__construct(false);
	}

	/**
	 * @return bool
	 * @throws \Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Style\Exception
	 */
	function processNotifications()
	{

		startLogSection("Processing user notification alerts");


		////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//
		// Output the full jobs list into a file and into files for different cuts at the jobs list data
		//
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$class = null;


		//
		// Output the final files we'll send to the user
		//

		$this->arrAllUnnotifiedJobs = getAllMatchesForUserNotification();
		if (is_null($this->arrAllUnnotifiedJobs) || count($this->arrAllUnnotifiedJobs) <= 0) {
			LogMessage("No new jobs found to notify user about.");
			endLogSection(" User results notification.");

			return false;
		}

		$arrJobsToNotify = array_filter($this->arrAllUnnotifiedJobs, array($this, '_isIncludedJobSite'));
		$detailsHTMLFile = null;
		$pathExcelResults = null;
		$arrFilesToAttach = array();

		startLogSection("Generating Excel file for user's job match results...");

		try {
			$spreadsheet = $this->_generateMatchResultsExcelFile($arrJobsToNotify);

			$writer = IOFactory::createWriter($spreadsheet, "Xlsx");

			$pathExcelResults = getDefaultJobsOutputFileName("", "JobMatches", "XLSX", "_", 'notifications');
			$writer->save($pathExcelResults);
			$arrFilesToAttach[] = $pathExcelResults;
		} catch (\Exception $ex) {
			handleException($ex);
		} finally {
			endLogSection("Generating Excel file.");
		}

		//
		// For our final output, we want the jobs to be sorted by company and then role name.
		// Create a copy of the jobs list that is sorted by that value.
		//
		startLogSection("Generating HTML & text email content for user ");
		$arrMatchedJobs = array_filter($arrJobsToNotify, "isUserJobMatchAndNotExcluded");

		LogMessage("Generating html email content for user" . PHP_EOL);

		$messageHtml = $this->_generateHTMLEmailContent("JobScooper for " . getRunDateRange(), getConfigurationSetting("user_keyword_sets"), $arrMatchedJobs);
		$subject = "New Job Postings: " . getRunDateRange();

		endLogSection("Email content ready to send.");

		//
		// Send the email notification out for the completed job
		//
		startLogSection("Sending email to user...");

		try {
			$ret = $this->sendEmail(NotifierJobAlerts::PLAINTEXT_EMAIL_DIRECTIONS, $messageHtml, $arrFilesToAttach, $subject, "results");
			if ($ret !== false || $ret !== null) {
				if (!isDebug()) {
					$arrToMarkNotified = array_from_orm_object_list_by_array_keys($arrJobsToNotify, array("JobPostingId"));
					$ids = array_column($arrToMarkNotified, "JobPostingId");
					$rowsAffected = 0;
					foreach (array_chunk($ids, 100) as $arrChunkIds) {
						$results = \JobScooper\DataAccess\UserJobMatchQuery::create()
							->filterByJobPostingId($arrChunkIds)
							->update(array('UserNotificationState' => 'sent'), null, true);
						$rowsAffected .= count($results);
					}
					if ($rowsAffected != count($arrToMarkNotified))
						LogMessage("Warning:  marked only " . $rowsAffected . " of " . count($arrToMarkNotified) . " UserJobMatch records as notified.");
				}
			}
			endLogSection(" Email send completed...");

		} catch (Exception $ex) {
			endLogSection(" Email send failed.");
			handleException($ex);
		}

		//
		// We only keep interim files around in debug mode, so
		// after we're done processing, delete the interim HTML file
		//
		if (isDebug() !== true) {
			foreach ($arrFilesToAttach as $filepath) {
				if (file_exists($filepath) && is_file($filepath)) {
					LogMessage("Deleting local attachment file " . $filepath . PHP_EOL);
					unlink($filepath);
				}
			}
		}

		endLogSection(" User Results Notification.");

		return $ret;
	}

	/**
	 * @param $arrJobsToNotify
	 *
	 * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Style\Exception
	 */
	private function _generateMatchResultsExcelFile($arrJobsToNotify)
	{

		$spreadsheet = IOFactory::load(__ROOT__ . '/src/assets/templates/results.xlsx');

		$sheetFilters = array(
			[NotifierJobAlerts::SHEET_MATCHES, "isUserJobMatch", NotifierJobAlerts::KEYS_MATCHES],
			[NotifierJobAlerts::SHEET_EXCLUDED_MATCHES, "isUserJobMatchAndNotExcluded", NotifierJobAlerts::KEYS_EXCLUDED],
			[NotifierJobAlerts::SHEET_EXCLUDED_ALL, "isExcluded", NotifierJobAlerts::KEYS_EXCLUDED]
		);

		foreach ($sheetFilters as $sheetParams) {
			if ($spreadsheet->sheetNameExists($sheetParams[0]))
			{
				$spreadsheet->setActiveSheetIndexByName($sheetParams[0]);
				$spreadsheet->getActiveSheet()->getCell("F1")->setValue(getRunDateRange());
				$arrFilteredJobs = array_filter($arrJobsToNotify, $sheetParams[1]);
				if (!empty($arrFilteredJobs))
					$this->_writeJobMatchesToSheet($spreadsheet, $sheetParams[0], $arrFilteredJobs, $sheetParams[2]);
				$arrFilteredJobs = null;
			}
		}
		$spreadsheet->setActiveSheetIndexByName($sheetFilters[0][0]);

		return $spreadsheet;
	}

	/**
	 * @param $subject
	 * @param $searches
	 * @param $arrMatchedJobs
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	private function _generateHTMLEmailContent($subject, $searches, $arrMatchedJobs)
	{
		$renderer = loadTemplate(join(DIRECTORY_SEPARATOR, array(__ROOT__, "src", "assets", "templates", "html_email_results_responsive.tmpl")));

		$data = array(
			"Email"      => array(
				"Subject"            => $subject,
				"BannerText"         => "JobScooper",
				"Headline"           => "Jobs for " . getRunDateRange(),
				"IntroText"          => "",
				"PreHeaderText"      => "",
				"TotalJobMatchCount" => countAssociativeArrayValues($arrMatchedJobs)
			),
			"Search"     => array(
				"Locations" => array(),
				"Keywords"  => array()
			),
			"JobMatches" => $this->_convertToJobsArrays($arrMatchedJobs)
		);


		foreach ($searches as $search) {
			$kwds = $search->getKeywords();
			if (!is_array($search->getKeywords()))
				$kwds = preg_split("/\s?,\s?/", $kwds);

			$data['Search']['Keywords'] = join(", ", $kwds);
		}

		foreach (getConfigurationSetting("search_locations") as $loc)
			$data['Search']['Locations'][] = $loc->getDisplayName();

		$data['Search']['Locations'][] = join(", ", $data['Search']['Locations']);

		$html = $renderer($data);

		if(isDebug())
			file_put_contents(getDefaultJobsOutputFileName("email", "notification", "html", "_", "debug"), $html);

		return $html;

	}

	/**
	 * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
	 * @param                                       $sheetName
	 * @param                                       $arrResults
	 * @param                                       $keys
	 *
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Style\Exception
	 */
	private function _writeJobMatchesToSheet(Spreadsheet &$spreadsheet, $sheetName, $arrResults, $keys)
	{

		foreach ($arrResults as $k => $v) {
			$rowData = $v->toFlatArrayForCSV($keys);
			$rowOrder = array_fill_keys($keys, null);
			$arrResults[$k] = array_replace($rowOrder, $rowData);;

		}

		$spreadsheet->setActiveSheetIndexByName($sheetName);

		$dataSheet = $spreadsheet->getActiveSheet();

		$nFirstDataRow = 3;
		$startCell = "A" . strval($nFirstDataRow);

		$dataSheet->fromArray(
			$arrResults,    // The data to set
			null,  // Array values with this value will not be set
			$startCell      // Top left coordinate of the worksheet range where
		//    we want to set these values (default is A1)
		);

		//
		// If we had a Url or Title array key, then we need to iterate over
		// all the rows in that column and set the hyperlink
		//
		$nUrlColIndex = array_search("Url", $keys);
		$nTitleColIndex = array_search("Title", $keys);
		if ($nUrlColIndex >= 0 && $nUrlColIndex !== false) {
			$nNumRows = count($arrResults);
			for ($rc = 0; $rc < $nNumRows; $rc++) {
				$cellUrl = $dataSheet->getCellByColumnAndRow(1 + $nUrlColIndex, $rc + $nFirstDataRow);
				$urlVal = $cellUrl->getValue();
				$scheme = parse_url($urlVal, PHP_URL_SCHEME);
				if ($scheme !== false && strncasecmp($scheme, "http", 4) == 0) {
					$cellUrl->getHyperlink()->setUrl($urlVal);
					$cellUrl->getStyle()->applyFromArray(NotifierJobAlerts::$styleHyperlink);
					if ($nTitleColIndex >= 0 && $nTitleColIndex !== false) {
						$cellTitle = $dataSheet->getCellByColumnAndRow(1 + $nTitleColIndex, $rc + $nFirstDataRow);
						$cellTitle->getHyperlink()->setUrl($urlVal);
						$cellTitle->getStyle()->applyFromArray(NotifierJobAlerts::$styleHyperlink);
					}
				}
			}
		}


	}

	/**
	 * @param $arrJobObjects
	 *
	 * @return array
	 */
	private function _convertToJobsArrays($arrJobObjects)
	{
		$arrRet = array();
		foreach ($arrJobObjects as $jobMatch) {
			$item = $jobMatch->toFlatArrayForCSV();
			$arrRet[$item['KeySiteAndPostId']] = $item;
		}

		return $arrRet;

	}

	/**
	 * @param $var
	 *
	 * @return bool
	 */
	protected function _isIncludedJobSite($var)
	{
		$sites = $this->_getJobSitesRunRecently();

		return in_array(cleanupSlugPart($var->getJobPostingFromUJM()->getJobSiteKey()), $sites);

	}

	/**
	 * @return array|null
	 */
	private function _getJobSitesRunRecently()
	{
		if (is_null($this->_arrJobSitesForRun)) {
			$this->_arrJobSitesForRun = JobSitePluginBuilder::getIncludedJobSites();

			$sites = array_map(function ($var) {
				return $var->getJobPostingFromUJM()->getJobSiteKey();
			}, $this->arrAllUnnotifiedJobs);
			$uniqSites = array_unique($sites);

			$this->_arrJobSitesForRun = array_merge($this->_arrJobSitesForRun, $uniqSites);
		}

		return $this->_arrJobSitesForRun;

	}

}