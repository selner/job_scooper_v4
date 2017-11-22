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
define('__ROOT__', dirname(__FILE__));
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
define('MAX_FILE_SIZE', 5000000);
const C__APPNAME__ = "jobs_scooper";
const C__SRC_LOCATION = "https://github.com/selner/job_scooper_v4";
define('__APP_VERSION__', "v4.1.0-use-propel-orm");
$lineEnding = ini_get('auto_detect_line_endings');
ini_set('auto_detect_line_endings', true);

$autoload = join(DIRECTORY_SEPARATOR, array(__ROOT__, 'vendor', 'autoload.php'));
if (file_exists($autoload)) {
    require_once($autoload);
} else {
    trigger_error("Composer required to run this app.");
}

$GLOBALS['logger'] = new \JobScooper\Manager\LoggingManager(C__APPNAME__);

$propelConfig = join(DIRECTORY_SEPARATOR, array(__ROOT__, 'Config', 'config.php'));
if (file_exists($propelConfig)) {
    require_once($propelConfig);
} else {
    trigger_error("Missing runtime configuration file at {$propelConfig} for your setup. ");
}

$classRunJobs = new \JobScooper\Manager\StageManager();
$classRunJobs->runAll();


