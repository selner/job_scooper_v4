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

require_once dirname(__FILE__) . '/RunHelpers.php';

function __initializeArgs__()
{



    $GLOBALS['OPTS_SETTINGS']  = array(
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
        'excluded_titles_regexes_file' => array(
            'description'   => 'CSV file of titles to flag automatically as "not interested"',
            'default'       => null,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'tr',
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
    if($GLOBALS['VERBOSE'] == true) { __log__ ('Options set: '.var_export($GLOBALS['OPTS'], true), C__LOGLEVEL_INFO__); }



}

function __getPassedArgs__()
{

    __debug__printLine('Possible options: '.var_export($GLOBALS['OPTS_SETTINGS'], true), C__DISPLAY_NORMAL__);

    # After you've configured Pharse, run it like so:
    $GLOBALS['OPTS'] = Pharse::options($GLOBALS['OPTS_SETTINGS']);

    // Now go see what we got back for each of the sites
    //
    foreach($GLOBALS['site_plugins']  as $site)
    {
        $fIsIncludedInRun = is_IncludeSite($site['name']);
        $GLOBALS['site_plugins'][$site['name']]['include_in_run'] = $fIsIncludedInRun;
    }

    var_dump('$fIsIncludedInRun', $fIsIncludedInRun);
    var_dump('plugins', $GLOBALS['site_plugins']);
    var_dump('plugins', $GLOBALS['OPTS']);

    $nDays = get_PharseOptionValue('number_days');
    if($nDays == false) { $GLOBALS['OPTS']['number_days'] = 1; }


    $GLOBALS['OPTS']['filter_notinterested'] = get_PharseOptionValue('filter_notinterested');

    $GLOBALS['titles_file_details'] = get_PharseOption_FileDetails("excluded_titles_file", true);
    $GLOBALS['titles_regex_file_details'] = get_PharseOption_FileDetails("excluded_titles_regexes_file", true);

    $GLOBALS['output_file_details'] = get_PharseOption_FileDetails("output_file", false);

    $GLOBALS['titles_to_filter'] = null;
    $GLOBALS['titles_regex_to_filter'] = null;

    $GLOBALS['company_role_pairs'] = null;


    $GLOBALS['OPTS']['DEBUG'] = false;
    $GLOBALS['OPTS']['DEBUG'] = get_PharseOptionValue('use_debug');
    if($GLOBALS['OPTS']['DEBUG'] == true ) { $GLOBALS['VERBOSE'] = true; }


    if($GLOBALS['VERBOSE'] == true) { __log__ ('Options set: '.var_export($GLOBALS['OPTS'], true), C__LOGLEVEL_INFO__); }

    return $GLOBALS['OPTS'];
}


function addUserOptionForSitePlugins()
{
    foreach($GLOBALS['site_plugins'] as $site)
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
//        var_dump('$strOptName', $strOptName);
        $retFileDetails = parseFilePath($GLOBALS['OPTS'][$strOptName], $fFileRequired);
//        var_dump('$retFileDetails ', $retFileDetails);
        __debug__printLine("". $strOptName ."details= [" . var_export($retFileDetails , true) . "]", C__DISPLAY_ITEM_DETAIL__);
    }

    return $retFileDetails;
}
