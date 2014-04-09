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
require_once dirname(__FILE__) . '/../lib/pharse.php';

if ( file_exists ( dirname(__FILE__) . '/../lib/KLogger.php') )
{
    define(C_USE_KLOGGER, 1);
    require_once dirname(__FILE__) . '/../lib/KLogger.php';

}
else
{
    print "Could not find KLogger file: ". dirname(__FILE__) . '/../lib/KLogger.php'.PHP_EOL;
    define(C_USE_KLOGGER, 0);
}
require_once dirname(__FILE__) . '/../../scooper/src/include/plugin-base.php';
require_once dirname(__FILE__) . '/../../scooper/src/include/common.php';
require_once dirname(__FILE__) . '/../lib/simple_html_dom.php';
require_once dirname(__FILE__) . '/ClassJobsSite.php';

date_default_timezone_set("America/Los_Angeles");

const C_NORMAL = 0;
const C_EXCLUDE_BRIEF = 1;
const C_EXCLUDE_GETTING_ACTUAL_URL = 3;

const C_STR_DATAFOLDER = '/Users/bryan/Code/data/jobs/';
const C_STR_FOLDER_JOBSEARCH= '/Users/bryan/Dropbox/Job Search 2013/';


function __initializeArgs__()
{


    $GLOBALS['DEBUG'] = false;
    if($GLOBALS['DEBUG'] == true ) { $GLOBALS['VERBOSE'] = true; }

    $GLOBALS['sites_supported'] = array();

    $options = array(
        'include_all' => array(
            'description'   => 'Include all job sites.',
            'default'       => 1,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'all',
        ),
        'number_days' => array(
            'description'   => 'Number of days ago to pull job listings for..',
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
        'filter_notinterested' => array(
            'description'   => 'Exclude listings that are marked as "not interested".',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'fni',
        ),
        'include_amazon' => array(
            'description'   => 'Include Amazon.',
            'default'       => -1,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'amazon',
        ),
    );




//    # You may specify a program banner thusly:
//    $banner = "Find and export basic website, Moz.com, Crunchbase and Quantcast data for any company name or URL.";
//    Pharse::setBanner($banner);
    if($GLOBALS['VERBOSE'] == true) { __log__ ('Options set: '.var_export($GLOBALS['OPTS'], true), C__LOGLEVEL_INFO__); }


    $GLOBALS['sites_supported']['Amazon'] =  array('site_name' => 'Amazon', 'include_in_run' => -1);

    $GLOBALS['OPTS_SETTINGS'] = $options;


}

function __getPassedArgs__()
{
    // Add each of the sites to the supported list so that they show up as keys in the potential
    // option choices
    //
    foreach($GLOBALS['sites_supported']  as $site)
    {
        $GLOBALS['sites_supported'][$site['site_name']]['include_in_run'] = is_IncludeSite($site['site_name']);
    }

    __log__ ('Possible options: '.var_export($GLOBALS['OPTS_SETTINGS'], true), C__LOGLEVEL_INFO__);

    # After you've configured Pharse, run it like so:
    $GLOBALS['OPTS'] = Pharse::options($GLOBALS['OPTS_SETTINGS']);

    // Now go see what we got back for each of the sites
    //
    foreach($GLOBALS['sites_supported']  as $site)
    {
        $GLOBALS['sites_supported'][$site['site_name']]['include_in_run'] = is_IncludeSite($site['site_name']);
    }
    $nDays = get_PharseOptionValue('number_days');
    if($nDays == false) { $GLOBALS['OPTS']['number_days'] = 1; }


    $GLOBALS['OPTS']['filter_notinterested'] = get_PharseOptionValue('filter_notinterested');

    $GLOBALS['excluded_titles_file_details'] = get_PharseOption_FileDetails("excluded_titles_file", true);

    $GLOBALS['output_file_details'] = get_PharseOption_FileDetails("output_file", false);

    $GLOBALS['titles_to_filter'] = null;
    $GLOBALS['company_role_pairs'] = null;

    if($GLOBALS['VERBOSE'] == true) { __log__ ('Options set: '.var_export($GLOBALS['OPTS'], true), C__LOGLEVEL_INFO__); }

    return $GLOBALS['OPTS'];
}
function strTrimAndLower($str)
{
    if($str != null && is_string($str)) { return strtolower(trim($str)); }

    return $str;
}

function strScrub($str)
{
    $ret = strTrimAndLower($str);
    if($ret != null)
    {
        $ret  = str_replace(array(".", ",", "–", "/", "-", ":", ";"), " ", $ret);
        $ret  = str_replace("  ", " ", $ret);
        $ret  = str_replace("  ", " ", $ret); // do it twice to catch the multiples
    }
    return $ret;
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
               $GLOBALS['OPTS'][$strGivenKey] = 0;
                break;

           case -1:
           case 1:
           default:
           $GLOBALS['OPTS'][$strGivenKey] = 1;
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

    return $retvalue;
}

function get_PharseOption_FileDetails($strOptName, $fFileRequired)  // todo: add "is file requried?" functionality
{
    $retFileDetails = null;
    $valOpt = get_PharseOptionValue($strOptName);
    if($valOpt = false)
    {
        $GLOBALS['OPTS'][$strOptName] = null;
    }
    else
    {
        $retFileDetails = parseFilePath($GLOBALS['OPTS'][$strOptName], $fFileRequired);
        __debug__printLine("". $strOptName ."details= [" . var_export($retFileDetails , true) . "]", C__DISPLAY_ITEM_DETAIL__);
    }

    return $retFileDetails;
}


function intceil($number)
{
    if(is_string($number)) $number = floatval($number);

    $ret = ( is_numeric($number) ) ? ceil($number) : false;
    if ($ret != false) $ret = intval($ret);

    return $ret;
}
