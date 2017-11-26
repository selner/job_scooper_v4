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

use JobScooper\Utils\Pharse as Pharse;

$GLOBALS['USERDATA']['OPTS']['VERBOSE'] = false;
$GLOBALS['USERDATA']['OPTS']['VERBOSE_API_CALLS'] = false;
const C__STR_USER_AGENT__ = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.152 Safari/537.36";

date_default_timezone_set("America/Los_Angeles");


function get_PharseOptionValue($strOptName)
{
    $retvalue = null;
    $strOptGiven = $strOptName."_given";
    if(isset($GLOBALS['USERDATA']['OPTS']) && isset($GLOBALS['USERDATA']['OPTS'][$strOptGiven]) && $GLOBALS['USERDATA']['OPTS'][$strOptGiven] == true)
    {
        if(isset($GLOBALS['logger']) && isset($GLOBALS['VERBOSE'])) $GLOBALS['logger']->logLine("'".$strOptName ."'"."=[".$GLOBALS['USERDATA']['OPTS'][$strOptName] ."]", C__DISPLAY_ITEM_DETAIL__);
        $retvalue = $GLOBALS['USERDATA']['OPTS'][$strOptName];
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
    $ret = parseFilePath($fullpath, $fRequireFile);

    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("". $key ." set to [" . var_export($ret, true) . "]", C__DISPLAY_ITEM_DETAIL__);

    $GLOBALS['USERDATA']['OPTS'][$key] = $ret;

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

//    $fMatched = preg_match("/^['|\"]([^'\"]{1,})['|\"]$/", $valOpt, $arrMatches);
//    if($fMatched) $valOpt = $arrMatches[1];

    if($valOpt) $ret = parseFilePath($valOpt, $fFileRequired);

    return $ret;

}

function getConfigurationSettings($strSubkey = null)
{
    $ret = null;
    if(array_key_exists('USERDATA', $GLOBALS) && array_key_exists('configuration_settings', $GLOBALS['USERDATA']) && !is_null($GLOBALS['USERDATA']['configuration_settings']) && is_array($GLOBALS['USERDATA']['configuration_settings'])) {
        if (isset($strSubkey) && (isset($GLOBALS['USERDATA']['configuration_settings'][$strSubkey]) || $GLOBALS['USERDATA']['configuration_settings'][$strSubkey] == null))
            $ret = $GLOBALS['USERDATA']['configuration_settings'][$strSubkey];
        else
            $ret = $GLOBALS['USERDATA']['configuration_settings'];
    }

    return $ret;
}


function isVerbose() {
    if(isset($GLOBALS['USERDATA']['OPTS']) && isset($GLOBALS['USERDATA']['OPTS']['VERBOSE']))
        return filter_var($GLOBALS['USERDATA']['OPTS']['VERBOSE'], FILTER_VALIDATE_BOOLEAN);

    return filter_var(getConfigurationSettings('verbose'), FILTER_VALIDATE_BOOLEAN);
}

function getGlobalConfigOptionBoolean($key)
{
    return filter_var(getConfigurationSettings($key), FILTER_VALIDATE_BOOLEAN);
}

function isDebug() {
    $cmdline = get_PharseOptionValue('debug');
    $dbgCmdLine = filter_var($cmdline, FILTER_VALIDATE_BOOLEAN);

    return getGlobalConfigOptionBoolean('debug') || $dbgCmdLine;
}

function isTestRun() {
    return getGlobalConfigOptionBoolean('test_run');
}

function getOutputDirectory($key)
{
    $ret =  sys_get_temp_dir();
    if(array_key_exists('USERDATA', $GLOBALS) && array_key_exists('directories', $GLOBALS['USERDATA']) && !is_null($GLOBALS['USERDATA']['directories']) && is_array($GLOBALS['USERDATA']['directories'])) {
        if (array_key_exists($key, $GLOBALS['USERDATA']['directories']))
            $ret = $GLOBALS['USERDATA']['directories'][$key];
    }

    return $ret;
}

function getCurrentUserDetails()
{
    return getConfigurationSettings('user_details');
}


function generateOutputFileName($baseFileName="NONAME", $ext="UNK", $isUserSpecific=true, $dirKey="debug")
{
    $outDir = getOutputDirectory($dirKey);
    $today = "_" . getNowAsString("");
    $user = "";
    if($isUserSpecific === true) {
        $objUser = getCurrentUserDetails();
        if(!is_null($objUser))
        {
            $user = "_" . $objUser->getUserSlug();
        }
    }

    $ret = "{$outDir}/{$baseFileName}{$user}{$today}.{$ext}";
    return $ret;
}

function __initializeArgs__($rootdir)
{
    $pluginsDir = realpath($rootdir. DIRECTORY_SEPARATOR . "plugins");
    $mgrPlugins = new \JobScooper\Manager\JobSitePluginBuilder($pluginsDir);


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
    // LogDebug('Options available: '.var_export($GLOBALS['OPTS_SETTINGS'], true).PHP_EOL);
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
