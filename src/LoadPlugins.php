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
require_once __ROOT__ . "/bootstrap.php";

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

//************************************************************************
//
//
//
//  Supported Job Site Plugin Types
//
//
//
//************************************************************************
const C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__  = "SERVER_HTML";
const C__JOB_SEARCH_RESULTS_TYPE_CLIENTSIDE_WEBPAGE__  = "CLIENT_HTML";
const C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__ = "JOBAPI";
const C__JOB_SEARCH_RESULTS_TYPE_UNKNOWN__ = "ERROR_UNKNOWN_TYPE";

//************************************************************************
//
//
//
//  Supported Pagination Types
//
//
//
//************************************************************************
const C__PAGINATION_INFSCROLLPAGE_VIALOADMORE = "LOAD-MORE";
const C__PAGINATION_INFSCROLLPAGE_NOCONTROL= "INFINITE-SCROLL-NO-CONTROL";
const C__PAGINATION_INFSCROLLPAGE_VIA_JS = "INFINITE-SCROLL-VIA-JAVASCRIPT";
const C__PAGINATION_PAGE_VIA_CALLBACK = "PAGE-CALLBACK";
const C__PAGINATION_PAGE_VIA_NEXTBUTTON = "NEXT-BUTTON";
const C__PAGINATION_PAGE_VIA_URL = "PAGE-VIA-URL";
const C__PAGINATION_NONE = "NONE";

//************************************************************************
//
//
//
//  Supported Location Types
//
//
//
//************************************************************************

// TODO: refactor into more of a sprintf style definition (e.g. "%c,%S" would be "City, STATECODE")
$GLOBALS['DATA']['location_types'] = array(
    'location-city',
    'location-city-comma-statecode',
    'location-city-dash-statecode',
    'location-city-comma-nospace-statecode',
    'location-city-comma-statecode-underscores-and-dashes',
    'location-city-comma-state',
    'location-city-comma-state-country',
    'location-city-comma-state-comma-country',
    'location-city-comma-state-country-no-commas',
    'location-city-comma-state-comma-country',
    'location-city-comma-state-comma-countrycode',
    'location-city-comma-country',
    'location-city--comma-countrycode',
    'location-city-comma-statecode-comma-country',
    'location-city-comma-statecode-comma-countrycode',
    'location-city-country-no-commas',
    'location-state',
    'location-statecode',
    'location-countrycode');

//************************************************************************
//
//
//
//  Supported Plugin Configuration Flags
//
//
//
//************************************************************************

const C__JOB_USE_SELENIUM = 0x1;
const C__JOB_IGNORE_MISMATCHED_JOB_COUNTS = 0x2;

const C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED = 0x10;
const C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED = 0x20;
const C__JOB_SETTINGS_URL_VALUE_REQUIRED = 0x40;

const C__JOB_LOCATION_REQUIRES_LOWERCASE = 0x100;
const C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES = 0x200;
const C__JOB_KEYWORD_PARAMETER_SPACES_RAW_ENCODE = 0x400;
const C__JOB_KEYWORD_SUPPORTS_QUOTED_KEYWORDS = 0x800;

const C__JOB_PAGECOUNT_NOTAPPLICABLE__= 0x1000;
const C__JOB_DAYS_VALUE_NOTAPPLICABLE__ = 0x2000;
const C__JOB_ITEMCOUNT_NOTAPPLICABLE__ = 0x4000;
const C__JOB_ITEMCOUNT_STARTSATZERO__ = 0x8000;

//************************************************************************
//
//
//
//  Other Plugin Definition Constants
//
//
//
//************************************************************************

const C__TOTAL_ITEMS_UNKNOWN__ = 1111;
const C_JOB_MAX_RESULTS_PER_SEARCH = C__TOTAL_ITEMS_UNKNOWN__;

define('TITLE_NEG_KWD_MATCH', 'No (Title Excluded Via Negative Keyword)');
define('NO_TITLE_MATCHES', 'No (Title Did Not Match Search Keywords))');

define('JOBS_SCOOPER_MAX_FILE_SIZE', 1024000);

define('REXPR_PARTIAL_MATCH_URL_DOMAIN', '^https*.{3}[^\/]*');
define('REXPR_MATCH_URL_DOMAIN', '/^https*.{3}[^\/]*/');


//***********************************************************************
//
//
//
//  Plugin Instance Setup
//
//
//
//************************************************************************

//
// Load all plugins files found in /plugins/
//
$files = glob(__ROOT__.'/plugins/' . '*.php');
foreach ($files as $file) {
    require_once($file);
}

//
// Load all ATS system plugins files found in /plugins/ats_platforms/
//
$files = glob(__ROOT__.'/plugins/ats_platforms/' . '*.php');
foreach ($files as $file) {
    require_once($file);
}


function setupPlugins()
{
    $jsPlugins = new \Jobscooper\Manager\JsonSitePluginManager();
    $jsPlugins->init();

    $arrAddedPlugins = null;
    $classList = get_declared_classes();
    print('Getting job site plugin list...'. PHP_EOL);
    $matches = array();

    foreach($classList as $class)
    {
        if(preg_match('/^Plugin(\w+)/', $class, $matches) > 0)
        {
            $namekey = strtolower($matches[1]);
            findOrCreateJobSitePlugin($namekey);
//
//            $GLOBALS['JOBSITE_PLUGINS'][$namekey] = array('name'=> $namekey, 'class_name' => $class, 'jobsite_db_object' => null, 'include_in_run' => false, 'other_settings' => [] );
//
//            $GLOBALS['JOBSITE_PLUGINS'][$namekey]['jobsite_db_object'] = \JobScooper\DataAccess\JobSitePluginQuery::create()
//                ->filterByPrimaryKey($namekey)
//                ->findOneOrCreate();
//
//            $GLOBALS['JOBSITE_PLUGINS'][$namekey]['jobsite_db_object']->setKey($namekey);
//            $GLOBALS['JOBSITE_PLUGINS'][$namekey]['jobsite_db_object']->save();

//            if (array_key_exists("JOBSITE_PLUGINS", $GLOBALS) && (array_key_exists(strtolower($this->siteName), $GLOBALS['JOBSITE_PLUGINS']))) {
//                $plugin = $GLOBALS['JOBSITE_PLUGINS'][strtolower($this->siteName)];
//                if (array_key_exists("other_settings", $plugin) && is_array($plugin['other_settings'])) {
//                    $keys = array_keys($plugin['other_settings']);
//                    foreach ($keys as $attrib_name) {
//                        $this->$attrib_name = $plugin['other_settings'][$attrib_name];
//                    }
//                }
//            }
//            $GLOBALS['JOBSITE_PLUGINS'][$name] = array('name'=> $name, 'class_name' => $class, 'include_in_run' => false, 'other_settings' => [] );
//            $classinst=null;
        }
    }
    $strLog = "Added " . count($GLOBALS['JOBSITE_PLUGINS']) ." plugins: " . getArrayValuesAsString(array_column($GLOBALS['JOBSITE_PLUGINS'], "name"), ", ", null, false). ".";
    if(isset($GLOBALS['logger']))
        $GLOBALS['logger']->logLine($strLog , \C__DISPLAY_ITEM_DETAIL__);
    else
        print($strLog . PHP_EOL);

}
