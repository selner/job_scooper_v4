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
require_once(__ROOT__.'/lib/simple_html_dom.php');
require_once(__ROOT__.'/scooper_common/common.php');

define('BASE_DIR', dirname(__DIR__));
if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
    require_once(__ROOT__. '/vendor/autoload.php');
} else {
    trigger_error("Composer required to run this app.");
}

if ( file_exists ( __ROOT__.'/lib/KLogger.php' ) )
{
    define(C_USE_KLOGGER, 1);
    require_once(__ROOT__.'/lib/KLogger.php');

}
else
{
    print "Could not find KLogger file: ". __ROOT__.'/lib/KLogger.php' .PHP_EOL;
    define(C_USE_KLOGGER, 0);
}

date_default_timezone_set("America/Los_Angeles");

const C_NORMAL = 0;
const C_EXCLUDE_BRIEF = 1;
const C_EXCLUDE_GETTING_ACTUAL_URL = 3;

const C__STR_TAG_AUTOMARKEDJOB__ = "[auto-marked]";
const C__STR_TAG_DULICATE_POST__ = "No (Duplicate Job Post?)";
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
        'excluded_titles_file' => array(
            'description'   => 'CSV file of titles to flag automatically as "not interested"',
            'default'       => null,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 't',
        ),
        'filepath_excluded_titles_regexes' => array(
            'description'   => 'CSV file of titles to flag automatically as "not interested"',
            'default'       => null,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'tr',
        ),
        'filepath_excluded_companies_regexes' => array(
            'description'   => 'CSV file of company names to flag automatically as "not interested"',
            'default'       => null,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'cr',
        ),
        'include_amazon' => array(
            'description'   => 'Include Amazon.',
            'default'       => -1,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'amazon',
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

function get_PharseOptionValue($strOptName)
{
    $retvalue = null;
    $strOptGiven = $strOptName."_given";
    if($GLOBALS['OPTS'][$strOptGiven] == true)
    {
        __debug__printLine("'".$strOptName ."'"."=[".$GLOBALS['OPTS'][$strOptName] ."]", C__DISPLAY_ITEM_DETAIL__);
        $retvalue = $GLOBALS['OPTS'][$strOptName];
    }
    else
    {
        $retvalue = null;
    }

    return $retvalue;
}

function setGlobalFileDetails($key, $fRequireFile = false, $fullpath = null)
{
    $ret = null;
    if($fileDetails != null)
    {
        $ret= $fileDetails;
    }
    else
    {
        $ret = parseFilePath($fullpath, $fRequireFile);
    }

    __debug__printLine("". $key ." set to [" . var_export($ret, true) . "]", C__DISPLAY_ITEM_DETAIL__);

    $GLOBALS['OPTS'][$key] = $ret;

    return $ret;
}

function set_FileDetails_fromPharseSetting($optUserKeyName, $optDetailsKeyName, $fFileRequired)
{
    $valOpt = get_PharseOptionValue($optUserKeyName);
    return setGlobalFileDetails($optDetailsKeyName, $fFileRequired, $valOpt);
}


function get_FileDetails_fromPharseOption($optUserKeyName, $fFileRequired)
{
    $ret = null;
    $valOpt = get_PharseOptionValue($optUserKeyName);
    if($valOpt) $ret = parseFilePath($valOpt, $fFileRequired);

    return $ret;

}
