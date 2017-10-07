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
namespace JobScooper\Manager;


use JobScooper\StageProcessor\NotifierJobAlerts;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\MailHandler;
use Monolog\Logger;

class ErrorEmailHandler extends MailHandler
{
    /**
     * {@inheritdoc}
     */
    protected function send($content, array $records)
    {
        $searchParams = $this->_getUserSearchRunContent();

        $subject = "JobScooper[" . gethostname() . "] Errors for " . getRunDateRange();

        $data = array(
            'daterange' => getRunDateRange(),
            'monolog_error_content' => $content,
            'search_parameters_content' => $searchParams,
            'app_version' => __APP_VERSION__,
            'app_run_id' => $GLOBALS['USERDATA']['configuration_settings']['app_run_id'],
            "server" => gethostname()
        );
        $renderer = loadTemplate(__ROOT__.'/assets/templates/html_email_error_alerts.tmpl');

        $htmlBody = $renderer($data);

        $notifier = new NotifierJobAlerts();
        return $notifier->sendEmail("", $htmlBody, null, $subject, "error");

    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter()
    {
        return new HtmlFormatter();
    }

    private function _getUserSearchRunContent()
    {
        $renderer = loadTemplate(__ROOT__.'/assets/templates/partials/html_email_body_search_config_details.tmpl');

        return $renderer($GLOBALS['USERDATA']['configuration_settings']);

    }
}
