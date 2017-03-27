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
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/lib/Linkify.php');
require_once(__ROOT__.'/include/CSimpleHTMLHelper.php');
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');
require_once(__ROOT__ .'/include/AbstractClassBaseJobsPlugin.php');
require_once(__ROOT__ .'/include/JobSitePluginsTypes.php');
require_once(__ROOT__ . '/include/SimpleJobPlugins.php');
require_once(__ROOT__.'/include/ClassConfig.php');
require_once(__ROOT__.'/include/StageManager.php');
require_once (__ROOT__.'/include/ClassMultiSiteSearch.php');

$files = glob(__ROOT__.'/plugins/' . '/*.php');
foreach ($files as $file) {
    require_once($file);
}

$files = glob(__ROOT__.'/plugins/ats_platforms/' . '/*.php');
foreach ($files as $file) {
    require_once($file);
}

const C_JOB_MAX_RESULTS_PER_SEARCH = 1000;
const C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__  = "SERVER_HTML";
const C__JOB_SEARCH_RESULTS_TYPE_CLIENTSIDE_WEBPAGE__  = "CLIENT_HTML";
const C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__ = "JOBAPI";
const C__JOB_SEARCH_RESULTS_TYPE_UNKNOWN__ = "ERROR_UNKNOWN_TYPE";

const C__JOB_USE_SELENIUM = 0x1;
const C__JOB_CLIENTSIDE_INFSCROLLPAGE= 0x2;
const C__JOB_SINGLEPAGE_RESULTS = 0x4;
const C__JOB_PAGE_VIA_URL = 0x8;

const C__JOB_PAGECOUNT_NOTAPPLICABLE__= 0x10;
const C__JOB_DAYS_VALUE_NOTAPPLICABLE__ = 0x20;
const C__JOB_ITEMCOUNT_NOTAPPLICABLE__ = 0x40;
const C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED = 0x80;
const C__JOB_LOCATION_REQUIRES_LOWERCASE = 0x100;
const C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED = 0x200;
const C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES = 0x400;
const C__JOB_KEYWORD_PARAMETER_SPACES_RAW_ENCODE = 0x800;

const C__JOB_KEYWORD_MULTIPLE_TERMS_SUPPORTED = 0x1000;
const C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS = 0x2000;
const C__JOB_KEYWORD_SUPPORTS_PLUS_PREFIX = 0x4000;
const C__JOB_SETTINGS_URL_VALUE_REQUIRED = 0x8000;

//const C__JOB_HTTP_POST = 0x10000;
const C__JOB_IGNORE_MISMATCHED_JOB_COUNTS = 0x40000;

const C__USER_KEYWORD_ANYWHERE = 0x100000;
const C__USER_KEYWORD_ANYWHERE_AS_STRING = "any";
const C__USER_KEYWORD_MUST_EQUAL_TITLE = 0x200000;
const C__USER_KEYWORD_MUST_EQUAL_TITLE_AS_STRING = "exact-title";
const C__USER_KEYWORD_MUST_BE_IN_TITLE = 0x400000;
const C__USER_KEYWORD_MUST_BE_IN_TITLE_AS_STRING = "in-title";
const C__USER_KEYWORD_MATCH_DEFAULT = C__USER_KEYWORD_MUST_BE_IN_TITLE;

define('C__JOB_BASETYPE_WEBPAGE_FLAGS', 0);

$GLOBALS['DATA']['location_types'] = array('location-city', 'location-city-comma-statecode', 'location-city-dash-statecode', 'location-city-comma-nospace-statecode', 'location-city-comma-statecode-underscores-and-dashes', 'location-city-comma-state', 'location-city-comma-state-country', 'location-statecode', 'location-state', 'location-city-comma-state-country-no-commas', 'location-countrycode');
const C__TOTAL_ITEMS_UNKNOWN__ = 1111;


function setupPlugins()
{
    $arrAddedPlugins = null;
    $classList = get_declared_classes();
    print('Getting job site plugin list...'. PHP_EOL);
    foreach($classList as $class)
    {
        if(preg_match('/^Plugin/', $class) > 0)
        {

            $classinst = new $class(null, null);
            $name = strtolower($classinst->getName());
            $GLOBALS['JOBSITE_PLUGINS'][$name] = array('name'=> $name, 'class_name' => $class, 'include_in_run' => false, 'other_settings' => [] );
            $classinst=null;
        }
    }
    $strLog = "Added " . count($GLOBALS['JOBSITE_PLUGINS']) ." plugins: " . getArrayValuesAsString(array_column($GLOBALS['JOBSITE_PLUGINS'], "name"), ", ", null, false). ".";
    if(isset($GLOBALS['logger']))
        $GLOBALS['logger']->logLine($strLog , \Scooper\C__DISPLAY_ITEM_DETAIL__);
    else
         print($strLog . PHP_EOL);

}



?>