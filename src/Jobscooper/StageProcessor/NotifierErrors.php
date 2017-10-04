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


require_once __ROOT__ . "/bootstrap.php";

class NotifierErrors {

    function __construct()
    {

    }

    function __destruct()
    {
        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Destruct fired for instance of class " . get_class($this), \C__DISPLAY_ITEM_START__); }
    }



    function processAndAlertErrors()
    {
        $subject = "Jobscooper[" . gethostname() . "] Errors for " . getRunDateRange();

        $errs = $this->getErrorsEmailContent();


        if(strlen($errs['htmlbody']) > 0) {

            $renderer = loadTemplate(__ROOT__.'/assets/templates/html_email_error_alerts.tmpl');

            $data = Array(
                "email_content" =>  $errs['htmlbody']
            );

            $htmlemail = renderTemplate($renderer, $data);


            $notifier = new NotifierJobAlerts();

            return $notifier->sendEmail($errs['txtbody'], $htmlemail, $errs['attachments'], $subject, "error");
        }

        return null;

    }


    function getFailedSearchesNotificationBody()
    {
        $strErrorText = null;
        $attachments = array();
        $arrFailedPluginsReport = getFailedSearchesByPlugin();
        
        if(countAssociativeArrayValues($arrFailedPluginsReport) == 0)
        {
            if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("No error notification necessary:  no errors detected for the run searches.", \C__DISPLAY_NORMAL__); }
            return null;
        }

        $renderer = loadTemplate(__ROOT__.'/assets/templates/html_email_body_include_plugin_errors.tmpl');

        $data = Array(
            "subject" => "SUBJECT", 
            "server" => gethostname(),
            "app_version" => __APP_VERSION__,
            "plugins_with_errors" =>array_values($arrFailedPluginsReport)
        );

        $htmlContent = renderTemplate($renderer, $data);

        return $htmlContent;

    }


    function _appendConfigSetupContent_(&$htmlBody, &$txtBody, &$attachments)
    {
        $renderer = loadTemplate(__ROOT__.'/assets/templates/partials/html_email_body_search_config_details.tmpl');

        $htmlBody = $renderer($GLOBALS['USERDATA']['configuration_settings']);

    }
    function _appendGlobalErrorsContent_(&$htmlBody, &$txtBody, &$attachments)
    {
        if (array_key_exists('ERROR_REPORT_FILES', $GLOBALS['USERDATA']))
        {
            $htmlBody .= "<h2>Other Global Exceptions</h2>";
            $txtBody .= " *********  GLOBAL EXCEPTIONS   *********" . PHP_EOL;
            foreach($GLOBALS['USERDATA']['ERROR_REPORT_FILES'] as $errfile)
            {
                $attachments[] = $errfile;
                $objErr = loadJSON($errfile['full_file_path']);
                $htmlBody .=  "<h3>Error:  " . $objErr['exception_message'] . "</h3>";
                $txtBody .= PHP_EOL . "ERROR:  ". $objErr['exception_message'] . PHP_EOL;
                $jsonErr = json_encode($objErr, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
                $htmlBody .= "<pre>" . $jsonErr . "</pre><br/><br/>";
                $txtBody .= $jsonErr . PHP_EOL;
            }
        }
    }


    function getErrorsEmailContent()
    {
        $txtBody = "";
        $attachments = array();
        $ret = array('htmlbody' => "", 'txtbody' => "", 'attachments' => array());

        $content = "";
        $errGlobal = "";
        $errConfig = "";

        $this->_appendGlobalErrorsContent_($errGlobal, $txtBody, $attachments);
        $this->_appendConfigSetupContent_($errConfig, $txtBody, $attachments);

        $failedSearchHtml = $this->getFailedSearchesNotificationBody();
        if(!is_null($failedSearchHtml))
        {
            $content = $content . $failedSearchHtml;
        }

        if (strlen($errGlobal) > 0)
            $content = $content . $errGlobal;

        if (strlen($content) > 0)
            $content = $content . $errConfig;

        if(strlen($content) > 0)
        {
            $ret = array('htmlbody' => $content, 'txtbody' => $txtBody, 'attachments' => $attachments);
        }

        return $ret;
    }
}
