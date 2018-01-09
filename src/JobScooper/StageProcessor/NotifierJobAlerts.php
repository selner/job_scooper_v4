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
use JobScooper\DataAccess\Map\UserJobMatchTableMap;
use JobScooper\DataAccess\UserJobMatchQuery;
use JobScooper\Utils\JobsMailSender;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;

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

	const SHEET_ALL_JOBS = "all jobs";

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

		$detailsHTMLFile = null;
		$pathExcelResults = null;
		$arrFilesToAttach = array();

		LogMessage("Building job match lists for notifications");
		$matches = array();
		$matches["all"] = getAllMatchesForUserNotification(
			[UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_MARKED_READY_TO_SEND, Criteria::EQUAL],
			null
		);

		if(empty($matches["all"]))
			$matches["all"] = array();
		else {
			LogMessage("Converting " . countAssociativeArrayValues($matches["all"]) . " UserJobMatch objects to array data for use in notifications...");
			foreach ($matches["all"] as $userMatchId => $item) {
				$item = $matches["all"][$userMatchId]->toFlatArrayForCSV();
				$matches["all"][$userMatchId] = $item;
			}
		}
		$matches["isUserJobMatchAndNotExcluded"] = array_filter($matches["all"], "isUserJobMatchAndNotExcluded");
		startLogSection("Generating Excel file for user's job match results...");
		try {
			$spreadsheet = $this->_generateMatchResultsExcelFile($matches);

			$writer = IOFactory::createWriter($spreadsheet, "Xlsx");

			$pathExcelResults = getDefaultJobsOutputFileName("", "JobMatches", "XLSX", "_", 'notifications');
			$writer->save($pathExcelResults);
			$arrFilesToAttach[] = $pathExcelResults;

		} catch (\PhpOffice\PhpSpreadsheet\Style\Exception $ex) {
			handleException($ex, "Error writing results to Excel spreadsheet: %s", true);
		} catch (\PhpOffice\PhpSpreadsheet\Exception $ex) {
			handleException($ex, "Error writing results to Excel spreadsheet: %s", true);
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

		$messageHtml = $this->_generateHTMLEmailContent("JobScooper for " . getRunDateRange(), $matches);
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
					if (!empty($matches['all'])) {
						$ids = array_keys($matches['all']);
						updateUserJobMatchesStatus($ids, UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_SENT);
					}
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
	private function _generateMatchResultsExcelFile(&$arrJobsToNotify)
	{

		$spreadsheet = IOFactory::load(__ROOT__ . '/src/assets/templates/results.xlsx');

		$sheetFilters = array(
			[NotifierJobAlerts::SHEET_MATCHES, "isUserJobMatchAndNotExcluded", NotifierJobAlerts::KEYS_MATCHES],
			[NotifierJobAlerts::SHEET_ALL_JOBS, "all", NotifierJobAlerts::KEYS_EXCLUDED]
		);

		foreach ($sheetFilters as $sheetParams) {
			if (!$spreadsheet->sheetNameExists($sheetParams[0])) {
				LogWarning("No template sheet exists named {$sheetParams[0]} so creating it from blank sheet.");
				$newSheet = $spreadsheet->createSheet();
				$newSheet->setTitle($sheetParams[0]);
			}
			$spreadsheet->setActiveSheetIndexByName($sheetParams[0]);
			$spreadsheet->getActiveSheet()->getCell("F1")->setValue(getRunDateRange());
			$this->_writeJobMatchesToSheet($spreadsheet, $sheetParams[0], $arrJobsToNotify[$sheetParams[1]], $sheetParams[2]);
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
	private function _generateHTMLEmailContent($subject, &$matches)
	{
		$renderer = loadTemplate(join(DIRECTORY_SEPARATOR, array(__ROOT__, "src", "assets", "templates", "html_email_results_responsive.tmpl")));

		assert(array_key_exists("isUserJobMatchAndNotExcluded", $matches));

		$data = array(
			"Email"      => array(
				"Subject"            => $subject,
				"BannerText"         => "JobScooper",
				"Headline"           => "Jobs for " . getRunDateRange(),
				"IntroText"          => "",
				"PreHeaderText"      => "",
				"TotalJobMatchCount" => countAssociativeArrayValues($matches["isUserJobMatchAndNotExcluded"]),
				"TotalJobsReviewedCount" => countAssociativeArrayValues($matches["all"]),
				"PostFooterText"     => "generated by " . __APP_VERSION__. " on " . gethostname()
			),
			"Search"     => array(
				"Locations" => null,
				"Keywords"  => null
			),
			"JobMatches" => $matches["isUserJobMatchAndNotExcluded"]
		);

		$keywordSets = getConfigurationSetting("user_keyword_sets");
		$allKwds = array();
		if(!empty($keywordSets)) {
			foreach ($keywordSets as $set) {
				$kwds = $set->getKeywords();
				if (!is_array($set->getKeywords()))
					$kwds = preg_split("/\s?,\s?/", $kwds);
				$allKwds = array_merge($allKwds, $kwds);

			$data['Search']['Keywords'] = join(", ", $allKwds);
			}
		}

		$locations = getConfigurationSetting("search_locations");
		$searchLocNames = array();
		if(!empty($locations)) {
			foreach ($locations as $loc)
				$searchLocNames[] = $loc->getDisplayName();

			$data['Search']['Locations'] = join(", ", $searchLocNames);
		}

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
			$rowOrder = array_fill_keys($keys, null);
			$arrResults[$k] = array_replace($rowOrder, $v);

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
}