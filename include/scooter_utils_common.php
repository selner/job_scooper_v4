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
require_once dirname(__FILE__) . '/ClassJobsSiteBase.php';
require_once dirname(__FILE__) . '/../../scooper/src/lib/pharse.php';

date_default_timezone_set("America/Los_Angeles");

const C_NORMAL = 0;
const C_EXCLUDE_BRIEF = 1;
const C_EXCLUDE_GETTING_ACTUAL_URL = 3;

const C_STR_DATAFOLDER = '/Users/bryan/Code/data/jobs/';
const C_STR_FOLDER_JOBSEARCH= '/Users/bryan/Dropbox/Job Search 2013/';


function __get_ScooperUtil_args__()
{

    # specify some options
    $options = array(
        'include_all' => array(
            'description'   => 'Include all job sites.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'all',
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
        'number_days' => array(
            'description'   => 'Number of days ago to pull job listings for..',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'days',
        ),
        'filter_notinterested' => array(
            'description'   => 'Exclude listings that are marked as "not interested".',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'fni',
        ),
        'output_folder' => array(
            'description'   => 'Output file path to use.',
            'default'       => null,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'o',
        ),

    );

//    # You may specify a program banner thusly:
//    $banner = "Find and export basic website, Moz.com, Crunchbase and Quantcast data for any company name or URL.";
//    Pharse::setBanner($banner);

    # After you've configured Pharse, run it like so:
    $GLOBALS['OPTS'] = Pharse::options($options);

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