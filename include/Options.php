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
//
// If installed as part of the package, uses Klogger v0.1 version (http://codefury.net/projects/klogger/)
//
define('__APP_VERSION__', "Job Scooper v4");

if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/lib/pharse.php');
require_once(__ROOT__.'/lib/Linkify.php');

define('BASE_DIR', dirname(__DIR__));
if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
    require_once(__ROOT__. '/vendor/autoload.php');
} else {
    trigger_error("Composer required to run this app.");
}

$GLOBALS['OPTS']['VERBOSE'] = false;
$GLOBALS['OPTS']['VERBOSE_API_CALLS'] = false;
const C__STR_USER_AGENT__ = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.152 Safari/537.36";

date_default_timezone_set("America/Los_Angeles");

function isVerbose() {
    if(isset($GLOBALS['OPTS']) && isset($GLOBALS['OPTS']['VERBOSE']) && $GLOBALS['OPTS']['VERBOSE'] == true) return true;
    return false;
}

function isDebug() {
    if(isset($GLOBALS['OPTS']) && isset($GLOBALS['USERDATA']['configuration_settings']['debug']) && ($GLOBALS['USERDATA']['configuration_settings']['debug'] === true || $GLOBALS['USERDATA']['configuration_settings']['debug'] == 1)) return true;
    return false;
}

function isTestRun() {
    return (array_key_exists('test_run', $GLOBALS['USERDATA']['configuration_settings']) && intval(($GLOBALS['USERDATA']['configuration_settings']['test_run']) == 1));
}

function __initializeArgs__()
{
    setupPlugins();

    $GLOBALS['OPTS_SETTINGS']  = array(
        'use_config_ini' => array(
            'description'   => 'Use only the settings from this INI config file ',
            'default'       => 1,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'ini',
        ),
        'include_all' => array(
            'description'   => 'Include all job sites.',
            'default'       => 1,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'all',
        ),
        'number_days' => array(
            'description'   => 'Number of days ago to pull job listings for.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'days',
        ),
        'output' => array(
            'description'   => 'Full file path to use for results output.',
            'default'       => null,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => true,
            'short'      => 'o',
        ),
        'send_notifications' => array(
            'description'   => 'Send email notifications of the completed run.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'notify',
        ),
        's3bucket' => array(
            'description'   => 'Name of the S3 bucket to publish to',
            'default'       => "",
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false
        ),
        's3region' => array(
            'description'   => 'Name of the AWS region to use for the S3 bucket',
            'default'       => "",
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false
        ),
        'stages' => array(
            'description'   => 'Comma-separated list of stage numbers to execute.  All stages are run if not present.',
            'default'       => "1,2,3,4",
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false
        ),
    );


    addUserOptionForSitePlugins();

//    # You may specify a program banner thusly:
//    $banner = "Find and export basic website, Moz.com, Crunchbase and Quantcast data for any company name or URL.";
//    Pharse::setBanner($banner);
    if(isset($GLOBALS['logger']))
    {
        $GLOBALS['logger']->logLine('Options set: '.var_export($GLOBALS['OPTS'], true).PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
    }
    else { print('Options set: '.var_export($GLOBALS['OPTS'], true).PHP_EOL); }

}

function addUserOptionForSitePlugins()
{
    foreach($GLOBALS['JOBSITE_PLUGINS'] as $site)
    {
        $sitename = strtolower($site['name']);
        $strIncludeKey = 'include_'.$sitename;

        $GLOBALS['OPTS_SETTINGS'][$strIncludeKey ] = array(
            'description'   => 'Include ' .$sitename . ' in the results list.' ,
            'default'       => -1,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'         => $sitename
        );
    }
}

function is_IncludeSite($strName)
{
    $strIncludeSiteKey = "include_" . strtolower($strName);
    $strGivenKey = $strIncludeSiteKey."_given";
    $ret = false;

        if (isset($GLOBALS['OPTS'][$strGivenKey]) && $GLOBALS['OPTS'][$strGivenKey] == true)
        {
           switch($GLOBALS['OPTS'][$strIncludeSiteKey])
           {
               case 0:
                   $GLOBALS['OPTS'][$strIncludeSiteKey] = false;
                    break;

               case -1:
               case 1:
               default:
               $GLOBALS['OPTS'][$strIncludeSiteKey] = true;
               break;

           }
           $ret = $GLOBALS['OPTS'][$strIncludeSiteKey];
        }
        elseif(isset($GLOBALS['OPTS']['include_all_given']) && $GLOBALS['OPTS']['include_all_given'] == true)
        {
            $GLOBALS['OPTS'][$strGivenKey] = true;
            $GLOBALS['OPTS'][$strIncludeSiteKey] = true;
            $ret = $GLOBALS['OPTS'][$strIncludeSiteKey];
        }



    return $ret;
}
