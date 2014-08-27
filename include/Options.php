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
//
// If installed as part of the package, uses Klogger v0.1 version (http://codefury.net/projects/klogger/)
//
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
        'output_file' => array(
            'description'   => 'Full file path and name for the final results CSV file.',
            'default'       => null,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'o',
        ),
        'use_debug' => array(
            'description'   => 'Output debug files and logging',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'debug',
        ),
        'skip_notifications' => array(
            'description'   => 'Send email notifications of the completed run.',
            'default'       => 1,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'nonotify',
        ),
        'output_interim_files' => array(
            'description'   => 'In addition to the main results, output CSV files for the interim processing of job sites',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'debug_files',
        ),
    );


    addUserOptionForSitePlugins();

//    # You may specify a program banner thusly:
//    $banner = "Find and export basic website, Moz.com, Crunchbase and Quantcast data for any company name or URL.";
//    Pharse::setBanner($banner);
    if($GLOBALS['OPTS']['VERBOSE'] == true && isset($GLOBALS['logger'])) { $GLOBALS['logger']->logLine('Options set: '.var_export($GLOBALS['OPTS'], true), \Scooper\C__DISPLAY_NORMAL__); }

}

function addUserOptionForSitePlugins()
{
    foreach($GLOBALS['DATA']['site_plugins'] as $site)
    {
        $strIncludeKey = 'include_'.strtolower($site['name']);

        $GLOBALS['OPTS_SETTINGS'][$strIncludeKey ] = array(
            'description'   => 'Include ' .strtolower($site['name']) . ' in the results list.' ,
            'default'       => -1,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => strtolower($site['name'])
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
        elseif($GLOBALS['OPTS']['include_all_given'] == true)
        {
            $GLOBALS['OPTS'][$strGivenKey] = true;
            $GLOBALS['OPTS'][$strIncludeSiteKey] = true;
            $ret = $GLOBALS['OPTS'][$strIncludeSiteKey];
        }



    return $ret;
}
