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
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/lib/pharse.php');
require_once(__ROOT__.'/lib/Linkify.php');

define('BASE_DIR', dirname(__DIR__));
if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
    require_once(__ROOT__. '/vendor/autoload.php');
} else {
    trigger_error("Composer required to run this app.");
}

$GLOBALS['OPTS']['VERBOSE'] = false;

date_default_timezone_set("America/Los_Angeles");


const C__STR_TAG_AUTOMARKEDJOB__ = "[auto-marked]";
const C__STR_TAG_DUPLICATE_POST__ = "No (Duplicate Job Post?)";
const C__STR_TAG_BAD_TITLE_POST__ = "No (Bad Title & Role)";

function __initializeArgs__()
{
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
    );


    addUserOptionForSitePlugins();

//    # You may specify a program banner thusly:
//    $banner = "Find and export basic website, Moz.com, Crunchbase and Quantcast data for any company name or URL.";
//    Pharse::setBanner($banner);
    if($GLOBALS['OPTS']['VERBOSE'] == true) { __log__ ('Options set: '.var_export($GLOBALS['OPTS'], true), C__LOGLEVEL_INFO__); }

}

function __dumpGlobalArray__($strKey)
{
    __debug__printLine('-------- $GLOBALS['.$strKey.']: '.var_export($GLOBALS[$strKey], false) . "--------", C__DISPLAY_NORMAL__);

    var_dump('$GLOBALS['.$strKey.']', $GLOBALS[$strKey]);
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


    if($GLOBALS['OPTS'][$strGivenKey] == true)
    {
       switch($GLOBALS['OPTS'][$strIncludeSiteKey])
       {
           case 0:
               $GLOBALS['OPTS'][$strIncludeSiteKey] = 0;
                break;

           case -1:
           case 1:
           default:
           $GLOBALS['OPTS'][$strIncludeSiteKey] = 1;
           break;

       }
    }
    else if($GLOBALS['OPTS']['include_all_given'] == true)
    {
        $GLOBALS['OPTS'][$strGivenKey] = true;
        $GLOBALS['OPTS'][$strIncludeSiteKey] = true;
    }



    return $GLOBALS['OPTS'][$strIncludeSiteKey];
}
