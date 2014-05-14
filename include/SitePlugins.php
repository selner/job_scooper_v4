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


require_once dirname(__FILE__) . '/Options.php';
require_once dirname(__FILE__) . '/RunHelpers.php';
require_once dirname(__FILE__) . '/ClassMultiSiteSearch.php';
require_once dirname(__FILE__) . '/../plugins/PluginAmazon.php';
require_once dirname(__FILE__) . '/../plugins/PluginCraigslist.php';
require_once dirname(__FILE__) . '/../plugins/PluginIndeed.php';
require_once dirname(__FILE__) . '/../plugins/PluginSimplyHired.php';
require_once dirname(__FILE__) . '/../plugins/PluginGlassdoor.php';
require_once dirname(__FILE__) . '/../plugins/PluginPorch.php';
require_once dirname(__FILE__) . '/../plugins/PluginExpedia.php';
require_once dirname(__FILE__) . '/../plugins/PluginLinkUp.php';
require_once dirname(__FILE__) . '/../plugins/PluginEmploymentGuide.php';
require_once dirname(__FILE__) . '/../plugins/PluginMonster.php';
require_once dirname(__FILE__) . '/../plugins/PluginCareerBuilder.php';
require_once dirname(__FILE__) . '/../plugins/PluginMashable.php';
require_once dirname(__FILE__) . '/../plugins/PluginDisney.php';
require_once dirname(__FILE__) . '/../plugins/PluginOuterwall.php';
require_once dirname(__FILE__) . '/../plugins/PluginTableau.php';
require_once dirname(__FILE__) . '/../plugins/PluginGoogle.php';
require_once dirname(__FILE__) . '/../plugins/PluginFacebook.php';


$GLOBALS['site_plugins'] = array(
    'amazon' => array('name' => 'amazon', 'class_name' => 'PluginAmazon', 'include_in_run' => false),
    'craigslist' => array('name' => 'craigslist', 'class_name' => 'PluginCraigslist', 'include_in_run' => false),
    'porch' => array('name' => 'porch', 'class_name' => 'PluginPorch', 'include_in_run' => false),
    'expedia' => array('name' => 'expedia', 'class_name' => 'PluginExpedia', 'include_in_run' => false),
    'indeed' => array('name' => 'indeed', 'class_name' => 'PluginIndeed', 'include_in_run' => false),
    'glassdoor' => array('name' => 'glassdoor', 'class_name' => 'PluginGlassdoor', 'include_in_run' => false),
    'simplyhired' => array('name' => 'simplyhired', 'class_name' => 'PluginSimplyHired', 'include_in_run' => false),
    'linkup' => array('name' => 'linkup', 'class_name' => 'PluginLinkUp', 'include_in_run' => false),
    'employmentguide' => array('name' => 'employmentguide', 'class_name' => 'PluginEmploymentGuide', 'include_in_run' => false),
    'monster' => array('name' => 'monster', 'class_name' => 'PluginMonster', 'include_in_run' => false),
    'careerbuilder' => array('name' => 'careerbuilder', 'class_name' => 'PluginCareerBuilder', 'include_in_run' => false),
    'mashable' => array('name' => 'mashable', 'class_name' => 'PluginMashable', 'include_in_run' => false),
    'disney' => array('name' => 'disney', 'class_name' => 'PluginDisney', 'include_in_run' => false),
    'outerwall' => array('name' => 'outerwall', 'class_name' => 'PluginOuterwall', 'include_in_run' => false),
    'tableau' => array('name' => 'tableau', 'class_name' => 'PluginTableau', 'include_in_run' => false),
    'google' => array('name' => 'google', 'class_name' => 'PluginGoogle', 'include_in_run' => false),
    'facebook' => array('name' => 'facebook', 'class_name' => 'PluginFacebook', 'include_in_run' => false),
);

?>