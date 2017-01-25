<?php
/**
 * Copyright 2014-16 Bryan Selner
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

if (!strlen(__ROOT__) > 0) {
    define('__ROOT__', dirname(dirname(__FILE__)));
}
require_once(__ROOT__ . '/include/SitePlugins.php');
require_once(__ROOT__ . '/include/ClassJobsNotifier.php');

// Use composer autoloader
require_once(__ROOT__ . '/vendor/autoload.php');

use LightnCandy\LightnCandy;


class ErrorManager {

    function __construct()
    {

    }

    function __destruct()
    {
        if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Destruct fired for instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); }
    }



    function processAndAlertErrors()
    {
        $subject = "JobScooper[" . gethostname() . "] Errors for " . getRunDateRange();

        $htmlBody = "<HTML><BODY>";
        $txtBody = "";
        $attachments = array();

        $this->_appendGlobalErrorsContent_($htmlBody, $txtBody, $attachments);
        $this->_appendSearchErrorsContent_($htmlBody, $txtBody, $attachments);

        $htmlBody .= "</BODY></HTML>";

        $notifier = new ClassJobsNotifier(null,null);

        return $notifier->sendEmail($txtBody, $htmlBody, $attachments, $subject, "error");


    }

    private function _getFailedSearchesNotification_Content()
    {
        $strErrorText = null;
        $attachments = array();
        $arrFailedPluginsReport = getFailedSearchesByPlugin();

        if(countAssociativeArrayValues($arrFailedPluginsReport) > 0)
        {
            $jsonFailedSearches = json_encode($arrFailedPluginsReport, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);

            $strErrorText = "The following site plugins returned an error or had zero listings found unexpectedly:  " . PHP_EOL . $jsonFailedSearches . PHP_EOL . PHP_EOL . "App version = " . __APP_VERSION__;
            if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("Errors detected for the run searches.  attempting to send error notification email:  " . PHP_EOL . $strErrorText, \Scooper\C__DISPLAY_NORMAL__); }
        }
        else
        {
            if(isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine("No error notification necessary:  no errors detected for the run searches.", \Scooper\C__DISPLAY_NORMAL__); }
        }

        if(!is_null($arrFailedPluginsReport) && is_array($arrFailedPluginsReport)) {
            foreach (array_keys($arrFailedPluginsReport) as $plugin) {
                foreach (array_keys($arrFailedPluginsReport[$plugin]) as $reportKey) {

                    if (array_key_exists("search_run_result", $arrFailedPluginsReport[$plugin][$reportKey]) && array_key_exists("error_files", $arrFailedPluginsReport[$plugin][$reportKey]['search_run_result'])) {
                        foreach ($arrFailedPluginsReport[$plugin][$reportKey]['search_run_result']['error_files'] as $file) {
                            $attachments[$file] = \Scooper\getFilePathDetailsFromString($file);
                        }
                    }
                }
            }
        }
        return array('body' => $strErrorText, 'attachments' => $attachments);

    }


    function _appendGlobalErrorsContent_(&$htmlBody, &$txtBody, &$attachments)
    {

        if (array_key_exists('ERROR_REPORT_FILES', $GLOBALS['USERDATA']))
        {
            $htmlBody .= "<h2>Global Exceptions</h2>";
            $txtBody .= " *********  GLOBAL EXCEPTIONS   *********" . PHP_EOL;
            foreach($GLOBALS['USERDATA']['ERROR_REPORT_FILES'] as $errfile)
            {
                $attachments[] = $errfile;
                $objErr = loadJSON($errfile['full_file_path']);
                $htmlBody .=  "<h3>Error:  " . $objErr['error_message'] . "</h3>";
                $txtBody .= PHP_EOL . "ERROR:  ". $objErr['error_message'] . PHP_EOL;
                $jsonErr = json_encode($objErr, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
                $htmlBody .= "<pre>" . $jsonErr . "</pre><br/><br/>";
                $txtBody .= $jsonErr . PHP_EOL;
            }
        }
    }


    function getErrorsEmailContent()
    {
        $htmlBody = "<HTML><BODY>";
        $txtBody = "";
        $attachments = array();

        $this->_appendGlobalErrorsContent_($htmlBody, $txtBody, $attachments);
        $this->_appendSearchErrorsContent_($htmlBody, $txtBody, $attachments);

        $htmlBody = "</BODY></HTML>";

        $content = (is_null($errGlobal) ? null : $errGlobal) . (is_null($errSearches) ? null : $errSearches);

        return ($content == "") ? null : $content;
    }

    function _appendSearchErrorsContent_(&$htmlBody, &$txtBody, &$attachments)
    {
        $failedReports = $this->_getFailedSearchesNotification_Content();
        $strBodyText = $failedReports['body'];
        if(strlen($strBodyText) == 0)
            return null;

        $attachments = $failedReports['attachments'];


        //
        // Setup the plaintext content
        //
        if($strBodyText != null && strlen($strBodyText) > 0)
        {
            $htmlBody .= "<h2>Exceptions for Specific Searches</h2>";
            $txtBody .= PHP_EOL . PHP_EOL . " *********  Exceptions for Specific Searches *********" . PHP_EOL;

            //
            // Setup the plaintext message text value
            //
            $txtBody .= $strBodyText;
            $htmlBody = '<pre>' . $strBodyText . "</pre>";
            $htmlBody .= PHP_EOL ;

        }
    }

}

/*
        {
            errors: {
            "badthing" : {
                "error_time"  : "<ERROR TIMESTAMP>",
        "error_message" : "<ERROR MESSAGE>",
        "exception" : "<EXCEPTION AS ARRAY>",
        "object_properties" : "<OBJECT PROPERTIES>",
        "debug_backtrace" : "<DEBUG BACKTRACE>",
            "exception_stack_trace" : "<EXCEPTION STACK>"
        },
            "differentbadthing" : {
                "error_time"  : "<ERROR TIMESTAMP>",
        "error_message" : "<ERROR MESSAGE>",
        "exception" : "<EXCEPTION AS ARRAY>",
        "object_properties" : "<OBJECT PROPERTIES>",
        "debug_backtrace" : "<DEBUG BACKTRACE>",
            "exception_stack_trace" : "<EXCEPTION STACK>"
        }
        },

            title: "This is my first post!"
        }
        */
/*
//
//
//        $template = "Welcome {{name}} , You win \${{value}} dollars!!\n";
//
//
//        $phpStr = LightnCandy::compile($template);  // set compiled PHP code into $phpStr
//        $detailsRen = \Scooper\parseFilePath(join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories']['stage4'], "template-render.php")), false);
//        $pathRenderer = \Scooper\getFullPathFromFileDetails($detailsRen);
//        // Save the compiled PHP code into a php file
//        file_put_contents($pathRenderer, '<?php ' . $phpStr . '?>');
//
//        // Get the render function from the php file
//        $renderer = include($pathRenderer);
//
//        // Render by different data
//        echo "Template is:\n$template\n";
//        echo $renderer(array('name' => 'John', 'value' => 10000));
//        echo $renderer(array('name' => 'Peter', 'value' => 1000));
//


//        $template = "Hello! {{name}} is {{gender}}.
//            Test1: {{@root.name}}
//            Test2: {{@root.gender}}
//            Test3: {{../test3}}
//            Test4: {{../../test4}}
//            Test5: {{../../.}}
//            Test6: {{../../[test'6]}}
//            {{#each .}}
//            each Value: {{.}}
//            {{/each}}
//            {{#.}}
//            section Value: {{.}}
//            {{/.}}
//            {{#if .}}IF OK!{{/if}}
//            {{#unless .}}Unless not OK!{{/unless}}
//            ";
//
//                // compile to debug version
//                $php = LightnCandy::compile($template, Array(
//                    'flags' => LightnCandy::FLAG_RENDER_DEBUG | LightnCandy::FLAG_HANDLEBARSJS
//                ));
//
//                // Get the render function
//                $renderer = LightnCandy::prepare($php);
//
//                // error_log() when missing data:
//                //   LightnCandy\Runtime: [gender] is not exist
//                //   LightnCandy\Runtime: ../[test] is not exist
//                $renderer(Array('name' => 'John'), array('debug' => LightnCandy\Runtime::DEBUG_ERROR_LOG));
//
//                // Output visual debug template with ANSI color:
//                echo $renderer(Array('name' => 'John'), array('debug' => LightnCandy\Runtime::DEBUG_TAGS_ANSI));
//
//                // Output debug template with HTML comments:
//                echo $renderer(Array('name' => 'John'), array('debug' => LightnCandy\Runtime::DEBUG_TAGS_HTML));
*/