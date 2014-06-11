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
require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/lib/Linkify.php');
require_once(__ROOT__.'/include/ClassJobsSitePlugin.php');
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');
require_once(__ROOT__.'/include/ClassJobsRunWrapper.php');

require_once(__ROOT__.'/include/ClassMultiSiteSearch.php');
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


$GLOBALS['DATA']['site_plugins'] = array(
    'amazon' => array('name' => 'amazon', 'class_name' => 'PluginAmazon',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'craigslist' => array('name' => 'craigslist', 'class_name' => 'PluginCraigslist',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'porch' => array('name' => 'porch', 'class_name' => 'PluginPorch',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'expedia' => array('name' => 'expedia', 'class_name' => 'PluginExpedia',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'indeed' => array('name' => 'indeed', 'class_name' => 'PluginIndeed',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'glassdoor' => array('name' => 'glassdoor', 'class_name' => 'PluginGlassdoor',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'simplyhired' => array('name' => 'simplyhired', 'class_name' => 'PluginSimplyHired',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'linkup' => array('name' => 'linkup', 'class_name' => 'PluginLinkUp',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'employmentguide' => array('name' => 'employmentguide', 'class_name' => 'PluginEmploymentGuide',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'monster' => array('name' => 'monster', 'class_name' => 'PluginMonster',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'careerbuilder' => array('name' => 'careerbuilder', 'class_name' => 'PluginCareerBuilder',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'mashable' => array('name' => 'mashable', 'class_name' => 'PluginMashable',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'disney' => array('name' => 'disney', 'class_name' => 'PluginDisney',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'outerwall' => array('name' => 'outerwall', 'class_name' => 'PluginOuterwall',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'tableau' => array('name' => 'tableau', 'class_name' => 'PluginTableau',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'google' => array('name' => 'google', 'class_name' => 'PluginGoogle',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML_FILE__, 'include_in_run' => false),
    'facebook' => array('name' => 'facebook', 'class_name' => 'PluginFacebook',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false), 
    'ebay' => array('name' => 'ebay', 'class_name' => 'PluginEbay',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false),
    'groupon' => array('name' => 'groupon', 'class_name' => 'PluginGroupon',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML__, 'include_in_run' => false),
    'dotjobs' => array('name' => 'dotjobs', 'class_name' => 'PluginDotJobs',  'results_type' => C__SEARCH_RESULTS_TYPE_XML__, 'include_in_run' => false),
    'geekwire' => array('name' => 'geekwire', 'class_name' => 'PluginGeekwire',  'results_type' => C__SEARCH_RESULTS_TYPE_HTML_FILE__, 'include_in_run' => false),
);

?>