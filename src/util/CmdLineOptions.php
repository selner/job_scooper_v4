<?php
/**
 * Copyright 2014-17 Bryan Selner
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


$GLOBALS['USERDATA']['OPTS']['VERBOSE'] = false;
$GLOBALS['USERDATA']['OPTS']['VERBOSE_API_CALLS'] = false;
const C__STR_USER_AGENT__ = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.152 Safari/537.36";

date_default_timezone_set("America/Los_Angeles");

function __initializeArgs__($rootdir)
{
    $pluginsDir = realpath($rootdir. DIRECTORY_SEPARATOR . "plugins");
    $mgrPlugins = new \JobScooper\Manager\JobSitePluginManager($pluginsDir);


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
            'default'       => 1,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'days',
        ),
        'output' => array(
            'description'   => 'Full file path to use for results output.',
            'default'       => null,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'o',
        ),
        'send_notifications' => array(
            'description'   => 'Send email notifications of the completed run.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'notify',
        ),
        'stages' => array(
            'description'   => 'Comma-separated list of stage numbers to execute.  All StageProcessor are run if not present.',
            'default'       => "1,2,3,4",
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false
        ),
        'debug' => array(
            'description'   => 'Output debug logging',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false
        ),
    );


    addUserOptionForSitePlugins();

//    # You may specify a program banner thusly:
//    $banner = "Find and export basic website, Moz.com, Crunchbase and Quantcast data for any company name or URL.";
//    Pharse::setBanner($banner);
    LogDebug('Options available: '.var_export($GLOBALS['OPTS_SETTINGS'], true).PHP_EOL);
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

function is_OptionIncludedSite($strName)
{
    $strIncludeSiteKey = "include_" . strtolower($strName);
    $strGivenKey = $strIncludeSiteKey."_given";
    $ret = false;

    if (isset($GLOBALS['USERDATA']['OPTS'][$strGivenKey]) && $GLOBALS['USERDATA']['OPTS'][$strGivenKey] == true)
    {
        switch($GLOBALS['USERDATA']['OPTS'][$strIncludeSiteKey])
        {
            case 0:
                $GLOBALS['USERDATA']['OPTS'][$strIncludeSiteKey] = false;
                break;

            case -1:
            case 1:
            default:
                $GLOBALS['USERDATA']['OPTS'][$strIncludeSiteKey] = true;
                break;

        }
        $ret = $GLOBALS['USERDATA']['OPTS'][$strIncludeSiteKey];
    }
    elseif(isset($GLOBALS['USERDATA']['OPTS']['include_all_given']) && $GLOBALS['USERDATA']['OPTS']['include_all_given'] == true)
    {
        $GLOBALS['USERDATA']['OPTS'][$strGivenKey] = true;
        $GLOBALS['USERDATA']['OPTS'][$strIncludeSiteKey] = true;
        $ret = $GLOBALS['USERDATA']['OPTS'][$strIncludeSiteKey];
    }



    return $ret;
}
