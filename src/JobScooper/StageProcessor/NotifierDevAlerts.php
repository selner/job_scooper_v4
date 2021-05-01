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

use JobScooper\DataAccess\JobSiteManager;
use JobScooper\DataAccess\UserSearchSiteRunQuery;
use JobScooper\Utils\JobsMailSender;
use JobScooper\Utils\PythonRunner;
use JobScooper\Utils\Settings;
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
    public function __construct()
    {
        parent::__construct(false);
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
     */
    public function getRunResultData()
    {
        startLogSection("Processing run result summary for devs...");

        $countsByPlugin = getAllUserNotificationCounts(
            [UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE_READY_TO_SEND, Criteria::EQUAL],
            null,
            2,
            null
        );

        $includedSites = JobSiteManager::getJobSiteKeysIncludedInRun();
        $arrPluginResults = array_fill_keys(array_keys($includedSites), array());
        foreach ($arrPluginResults as $k => $val) {
            $arrPluginResults[$k] = array(
                'JobSiteKey'             => $k,
                'TotalNewUserJobMatches' => "N/A",
                'TotalNewJobPostings'    => "N/A",
                'PlugInReference'        => $includedSites[$k]
            );

            if (in_array($k, $countsByPlugin)) {
                    $arrPluginResults[$k]['TotalNewUserJobMatches'] = $countsByPlugin[$k]['TotalNewUserJobMatches'];
                    $arrPluginResults[$k]['TotalNewJobPostings'] = $countsByPlugin[$k]['TotalNewJobPostings'];
            }
        }

        ksort($arrPluginResults);

        return $arrPluginResults;
    }

    /**
     * @param $arrPluginResults
     * @return mixed
     * @throws \Exception
     */
    public function getRunResultHtml($arrPluginResults)
    {
        $renderer = loadTemplate(implode(DIRECTORY_SEPARATOR, array(__ROOT__, "src", "assets", "templates", "partials", "html_email_run_counts.tmpl")));
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
    public function getPluginErrorData()
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
                if ($jobsite['CountFailedRuns'] >= 3) {
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
                if (!array_key_exists($jsKey, $reportJobSites)) {
                    $reportJobSites[$jsKey] = array();
                }

                $reportJobSites[$jsKey] = array_merge($reportJobSites[$jsKey], $failedJobSites[$jsKey]);
                $reportJobSites[$jsKey]['RunErrorDetails'] = nl2br(htmlentities($reportJobSites[$jsKey]['RunErrorDetails']));
            }
            ksort($reportJobSites);
        }

        if (is_empty_value($reportJobSites)) {
            $reportJobSites = array();
        }
        return $reportJobSites;
    }

    /**
     * @throws \Exception
     */
    public function processPluginErrorAlert()
    {
        // Only send the alert if we ran all jobsites (aka not debugging a single one)
        // and if not in debug mode
        //
        if(!isDebug() && Settings::is_in_setting_value('command_line_args.jobsite', 'all')) {

            startLogSection("Generating plugin errors alerts email to developers...");

            try {
                $outputReportFile = generateOutputFileName('broken_plugins_report', 'html', false, 'debug');
                $runFile = 'pyJobNormalizer/cmd_generate_plugins_report.py';
                $params = [
                    '--dsn' => Settings::get_db_dsn(),
                    '--output' => $outputReportFile
                ];

                $resultcode = PythonRunner::execScript($runFile, $params);


                $plugins_report = file_get_contents($outputReportFile);


                $renderer = loadTemplate(join(DIRECTORY_SEPARATOR, array(__ROOT__, "src", "assets", "templates", "html_email_error_alerts.tmpl")));

                $subject = "Broken Jobsite Plugins on [" . gethostname() . "]: for " . getRunDateRange(5);
                $data = array(
                    "Email"                 => array(
                        "Subject"       => $subject,
                        "BannerText"    => "",
                        "Headline"      => $subject,
                        "IntroText"     => "",
                        "PreHeaderText" => ""
                    ),
                    "alert_content"          => $plugins_report
                );

                $html = call_user_func($renderer, $data);

                $ret = false;
                try {
                    $mailer = new JobsMailSender(true);
                    $ret = $mailer->sendEmail("", $html, [], $subject, "errors");
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


            } catch (\Exception $ex) {
                handleException($ex, 'ERROR:  Failed to generate broken plugins report');
            } finally {
                endLogSection("Finished sending broken plugins error alert");
            }

        }
        
    }
}
