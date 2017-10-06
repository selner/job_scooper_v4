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

$autoload = join(DIRECTORY_SEPARATOR, array(__ROOT__, 'vendor', 'autoload.php'));
if (file_exists($autoload)) {
    require_once($autoload);
} else {
    trigger_error("Composer required to run this app.");
}

const C__APPNAME__ = "jobs_scooper";
define('__APP_VERSION__', "v4.1.0-use-propel-orm");
define('MAX_FILE_SIZE', 5000000);
$lineEnding = ini_get('auto_detect_line_endings');
ini_set('auto_detect_line_endings', true);

$propelConfig = join(DIRECTORY_SEPARATOR, array(__ROOT__, 'Config', 'config.php'));
if (file_exists($propelConfig)) {
    require_once($propelConfig);
} else {
    trigger_error("Missing runtime configuration file at {$propelConfig} for your setup. ");
}

$classRunJobs = new \JobScooper\Manager\StageManager();
$classRunJobs->runAll();


