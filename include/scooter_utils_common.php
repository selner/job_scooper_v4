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
require_once dirname(__FILE__) . '/../../scooper/src/include/plugin-base.php';
require_once dirname(__FILE__) . '/../../scooper/src/include/common.php';
require_once dirname(__FILE__) . '/../lib/simple_html_dom.php';
require_once dirname(__FILE__) . '/ClassJobsSite.php';
require_once dirname(__FILE__) . '/../../scooper/src/lib/pharse.php';

date_default_timezone_set("America/Los_Angeles");

const C_NORMAL = 0;
const C_EXCLUDE_BRIEF = 1;
const C_EXCLUDE_GETTING_ACTUAL_URL = 3;

const C_STR_DATAFOLDER = '/Users/bryan/Code/data/jobs/';
const C_STR_FOLDER_JOBSEARCH= '/Users/bryan/Dropbox/Job Search 2013/';


function __initializeArgs__()
{
    $GLOBALS['SITES_SUPPORTED'] = array();

    # specify some options
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
        'output_folder' => array(
            'description'   => 'Output file path to use.',
            'default'       => null,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'o',
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
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'amazon',
        ),
        'include_craigslist' => array(
            'description'   => 'Include Craigslist.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'craigslist',
        ),
        'include_simplyhired' => array(
            'description'   => 'Include SimplyHired.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'simplyhired',
        ),
        'include_indeed' => array(
            'description'   => 'Include Indeed.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'indeed',
        ),
    );

//    # You may specify a program banner thusly:
//    $banner = "Find and export basic website, Moz.com, Crunchbase and Quantcast data for any company name or URL.";
//    Pharse::setBanner($banner);

    $GLOBALS['OPTS_SETTINGS'] = $options;
}

function __getPassedArgs__()
{
    # After you've configured Pharse, run it like so:
   $GLOBALS['OPTS'] = Pharse::options($GLOBALS['OPTS_SETTINGS']);

    foreach($GLOBALS['SITES_SUPPORTED']  as $site)
    {
        $GLOBALS['SITES_SUPPORTED'] [$site['site_name']]['include_in_run'] = is_IncludeSite($site['site_name']);
    }

    $nDays = get_PharseOptionValue('number_days');
    if($nDays == false) { $GLOBALS['OPTS']['number_days'] = 1; }


    if($GLOBALS['OPTS']['filter_notinterested_given'])
    {
        $GLOBALS['OPTS']['filter_notinterested'] = false;
    }
    else
    {
        $GLOBALS['OPTS']['filter_notinterested'] = true;
    }

    $strOutputDir =  get_PharseOptionValue("output_folder");
    if($strOutputDir == false)  { $strOutputDir  = null; }


    return $GLOBALS['OPTS'];
}

function is_IncludeSite($strName)
{
    if($GLOBALS['OPTS']['include_all_given']) return true;

    $strFullOptName = "include_" . strtolower($strName);

    if($GLOBALS['OPTS'][$strFullOptName . "_given"] == true) return true;

    return false;
}

function get_PharseOptionValue($strOptName)
{

    if($GLOBALS['OPTS'][$strOptName. "_given"] == true)
    {
        return $GLOBALS['OPTS'][$strOptName];
    }

    return false;
}


function intceil($number)
{
    if(is_string($number)) $number = floatval($number);

    $ret = ( is_numeric($number) ) ? ceil($number) : false;
    if ($ret != false) $ret = intval($ret);

    return $ret;
}
