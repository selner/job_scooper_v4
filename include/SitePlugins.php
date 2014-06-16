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
require_once(__ROOT__ . '/lib/scooper_common/src/scooper/scooper_common.php');
require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/lib/Linkify.php');
require_once(__ROOT__.'/include/ClassJobsSitePlugin.php');
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');
require_once(__ROOT__.'/include/ClassJobsRunWrapper.php');


require_once (__ROOT__.'/include/ClassMultiSiteSearch.php');
require_once (__ROOT__.'/plugins/PluginAmazon.php');
require_once (__ROOT__.'/plugins/PluginCraigslist.php');
require_once (__ROOT__.'/plugins/PluginIndeed.php');
require_once (__ROOT__.'/plugins/PluginSimplyHired.php');
require_once (__ROOT__.'/plugins/PluginGlassdoor.php');
require_once (__ROOT__.'/plugins/PluginPorch.php');
require_once (__ROOT__.'/plugins/PluginExpedia.php');
require_once (__ROOT__.'/plugins/PluginLinkUp.php');
require_once (__ROOT__.'/plugins/PluginEmploymentGuide.php');
require_once (__ROOT__.'/plugins/PluginMonster.php');
require_once (__ROOT__.'/plugins/PluginCareerBuilder.php');
require_once (__ROOT__.'/plugins/PluginMashable.php');
require_once (__ROOT__.'/plugins/PluginDisney.php');
require_once (__ROOT__.'/plugins/PluginOuterwall.php');
require_once (__ROOT__.'/plugins/PluginTableau.php');
require_once (__ROOT__.'/plugins/PluginGoogle.php');
require_once (__ROOT__.'/plugins/PluginFacebook.php');
require_once (__ROOT__.'/plugins/PluginEbay.php');
require_once (__ROOT__.'/plugins/PluginGroupon.php');
require_once (__ROOT__.'/plugins/PluginGeekwire.php');
require_once (__ROOT__.'/plugins/PluginDotJobs.php');

// const C__SEARCH_RESULTS_TYPE_NONE__ = 0;
// const C__SEARCH_RESULTS_TYPE_WEBPAGE__ = 1;
// const C__SEARCH_RESULTS_TYPE_XML__ = 2;
// const C__SEARCH_RESULTS_TYPE_HTML_FILE__ = 4;
define( "C__SEARCH_RESULTS_TYPE_NONE__", 0x0 );
define( "C__SEARCH_RESULTS_TYPE_WEBPAGE__", 0x1 );
define( "C__SEARCH_RESULTS_TYPE_XML__", 0x2 );
define( "C__SEARCH_RESULTS_TYPE_HTML_FILE__", 0x4 );
//And so on, 0x8, 0x10, 0x20, 0x40, 0x80, 0x100, 0x200, 0x400, 0x800 etc..

const C__JOB_NONE = 0x100;
const C__JOB_PAGECOUNT_NOTAPPLICABLE__= 0x200;
const C__JOB_DAYS_VALUE_NOTAPPLICABLE__ = 0x400;
const C__JOB_ITEMCOUNT_NOTAPPLICABLE__ = 0x800;

const C__TOTAL_ITEMS_UNKNOWN__ = 11111;

$GLOBALS['DATA']['site_plugins'] = array(
    'amazon' => array('name' => 'amazon', 'class_name' => 'PluginAmazon',  'flags' => C__SEARCH_RESULTS_TYPE_HTML_FILE__ | C__JOB_PAGECOUNT_NOTAPPLICABLE__ | C__JOB_ITEMCOUNT_NOTAPPLICABLE__ , 'include_in_run' => false),
    'craigslist' => array('name' => 'craigslist', 'class_name' => 'PluginCraigslist',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__, 'include_in_run' => false),
    'porch' => array('name' => 'porch', 'class_name' => 'PluginPorch',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__ | C__JOB_ITEMCOUNT_NOTAPPLICABLE__ | C__JOB_PAGECOUNT_NOTAPPLICABLE__ | C__JOB_DAYS_VALUE_NOTAPPLICABLE__, 'include_in_run' => false),
    'expedia' => array('name' => 'expedia', 'class_name' => 'PluginExpedia',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__, 'include_in_run' => false),
    'indeed' => array('name' => 'indeed', 'class_name' => 'PluginIndeed',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__, 'include_in_run' => false),
    'glassdoor' => array('name' => 'glassdoor', 'class_name' => 'PluginGlassdoor',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__, 'include_in_run' => false),
    'simplyhired' => array('name' => 'simplyhired', 'class_name' => 'PluginSimplyHired',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__, 'include_in_run' => false),
    'linkup' => array('name' => 'linkup', 'class_name' => 'PluginLinkUp',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__, 'include_in_run' => false),
    'employmentguide' => array('name' => 'employmentguide', 'class_name' => 'PluginEmploymentGuide',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__, 'include_in_run' => false),
    'monster' => array('name' => 'monster', 'class_name' => 'PluginMonster',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__, 'include_in_run' => false),
    'careerbuilder' => array('name' => 'careerbuilder', 'class_name' => 'PluginCareerBuilder',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__, 'include_in_run' => false),
    'mashable' => array('name' => 'mashable', 'class_name' => 'PluginMashable',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__, 'include_in_run' => false),
    'disney' => array('name' => 'disney', 'class_name' => 'PluginDisney',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__ | C__JOB_DAYS_VALUE_NOTAPPLICABLE__, 'include_in_run' => false),
    'outerwall' => array('name' => 'outerwall', 'class_name' => 'PluginOuterwall',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__|C__JOB_ITEMCOUNT_NOTAPPLICABLE__ | C__JOB_ITEMCOUNT_NOTAPPLICABLE__ | C__JOB_PAGECOUNT_NOTAPPLICABLE__ | C__JOB_DAYS_VALUE_NOTAPPLICABLE__, 'include_in_run' => false),
    'tableau' => array('name' => 'tableau', 'class_name' => 'PluginTableau',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__ | C__JOB_PAGECOUNT_NOTAPPLICABLE__ | C__JOB_DAYS_VALUE_NOTAPPLICABLE__, 'include_in_run' => false),
    'google' => array('name' => 'google', 'class_name' => 'PluginGoogle',  'flags' => C__SEARCH_RESULTS_TYPE_HTML_FILE__|C__JOB_ITEMCOUNT_NOTAPPLICABLE__ | C__JOB_PAGECOUNT_NOTAPPLICABLE__ | C__JOB_DAYS_VALUE_NOTAPPLICABLE__, 'include_in_run' => false),
    'facebook' => array('name' => 'facebook', 'class_name' => 'PluginFacebook',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__| C__JOB_ITEMCOUNT_NOTAPPLICABLE__ | C__JOB_PAGECOUNT_NOTAPPLICABLE__|C__JOB_DAYS_VALUE_NOTAPPLICABLE__, 'include_in_run' => false),
    'ebay' => array('name' => 'ebay', 'class_name' => 'PluginEbay',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__ | C__JOB_DAYS_VALUE_NOTAPPLICABLE__, 'include_in_run' => false),
    'groupon' => array('name' => 'groupon', 'class_name' => 'PluginGroupon',  'flags' => C__SEARCH_RESULTS_TYPE_WEBPAGE__|C__JOB_ITEMCOUNT_NOTAPPLICABLE__ | C__JOB_PAGECOUNT_NOTAPPLICABLE__ | C__JOB_DAYS_VALUE_NOTAPPLICABLE__, 'include_in_run' => false),
    'dotjobs' => array('name' => 'dotjobs', 'class_name' => 'PluginDotJobs',  'flags' => C__SEARCH_RESULTS_TYPE_XML__| C__JOB_PAGECOUNT_NOTAPPLICABLE__ | C__JOB_ITEMCOUNT_NOTAPPLICABLE__ , 'include_in_run' => false),
    'geekwire' => array('name' => 'geekwire', 'class_name' => 'PluginGeekwire',  'flags' => C__SEARCH_RESULTS_TYPE_HTML_FILE__ | C__JOB_ITEMCOUNT_NOTAPPLICABLE__ | C__JOB_PAGECOUNT_NOTAPPLICABLE__ | C__JOB_DAYS_VALUE_NOTAPPLICABLE__ , 'include_in_run' => false),
);

?>