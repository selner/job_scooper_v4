<?php
/**
 * Copyright 2014 Bryan Selner
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
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/include/SitePlugins.php');


const C__APPNAME__ = "jobs_scooper";

function testEmailNotification()
{
    $classRunJobs = new ClassJobsRunWrapper();
    $fileAttachDetails = \Scooper\getFilePathDetailsFromString(__FILE__);

    $config = $classRunJobs->getConfig();
    $SMTP = $config->getSMTPSettings();

    print("SMTP Settings" . PHP_EOL);
    print(getArrayValuesAsString($SMTP));
    print(PHP_EOL);

    $classRunJobs->sendJobCompletedEmail("Test Notification Body:  Text", "<h1>Test Notification Body:  HTML.  Test File attached.</h1>", null, $fileAttachDetails);

}

testEmailNotification();

?>
