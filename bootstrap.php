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

define('__APP_VERSION__', "Job Scooper v4.1.0-use-propel-orm");
define('MAX_FILE_SIZE', 5000000);
ini_set('auto_detect_line_endings', true);

//use Doctrine\ORM\Tools\Setup;
if (file_exists(dirname(__FILE__).'/vendor/autoload.php')) {
    require_once(dirname(__FILE__).'/vendor/autoload.php');
} else {
    trigger_error("Composer required to run this app.");
}


// setup Propel
require_once(dirname(__FILE__).'/config/generated-conf/config.php');

require_once(dirname(__FILE__) . '/include/Helpers.php');

require_once(dirname(__FILE__).'/lib/pharse.php');
require_once(dirname(__FILE__).'/lib/Linkify.php');
require_once(dirname(__FILE__).'/lib/AddressNormalization.php');

require_once(dirname(__FILE__).'/include/JobListFilters.php');
require_once(dirname(__FILE__).'/include/ErrorManager.php');

require_once(dirname(__FILE__).'/include/SeleniumSession.php');
require_once(dirname(__FILE__).'/include/CmdLineOptions.php');
require_once(dirname(__FILE__).'/include/ClassMultiSiteSearch.php');
require_once(dirname(__FILE__).'/include/PluginsAbstractJobsBaseClass.php');

require_once(dirname(__FILE__).'/include/PluginClassTypes.php');
require_once(dirname(__FILE__).'/include/ClassConfig.php');
require_once(dirname(__FILE__).'/include/StageManager.php');
require_once(dirname(__FILE__).'/include/PluginSimpleJobClasses.php');
require_once(dirname(__FILE__).'/include/PluginJsonConfigSupport.php');
require_once(dirname(__FILE__).'/include/JobsAutoMarker.php');
require_once(dirname(__FILE__).'/include/ClassJobsNotifier.php');
require_once(dirname(__FILE__).'/include/PluginOptions.php');

