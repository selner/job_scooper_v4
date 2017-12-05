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


$GLOBALS['OPTS']['VERBOSE'] = false;
$GLOBALS['OPTS']['VERBOSE_API_CALLS'] = false;




function setGlobalFileDetails($key, $fRequireFile = false, $fullpath = null)
{
    $ret = null;
    $ret = parseFilePath($fullpath, $fRequireFile);

    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("". $key ." set to [" . var_export($ret, true) . "]", C__DISPLAY_ITEM_DETAIL__);

    $GLOBALS['OPTS'][$key] = $ret;

    return $ret;
}

function isVerbose() {
    if(isset($GLOBALS['OPTS']) && isset($GLOBALS['OPTS']['VERBOSE']))
        return filter_var($GLOBALS['OPTS']['VERBOSE'], FILTER_VALIDATE_BOOLEAN);

    return filter_var(getConfigurationSettings('verbose'), FILTER_VALIDATE_BOOLEAN);
}

function getGlobalConfigOptionBoolean($key)
{
    return filter_var(getConfigurationSettings($key), FILTER_VALIDATE_BOOLEAN);
}

function isDebug() {
    $cmdDebugEnabled = \JobScooper\Utils\DocOptions::equalsTrue('debug');
    $dbgSettings = getGlobalConfigOptionBoolean('debug');
    if(empty($dbgSettings)) $dbgSettings = false;
    return $dbgSettings || $cmdDebugEnabled;
}

function isTestRun() {
    return getGlobalConfigOptionBoolean('test_run');
}

function getOutputDirectory($key)
{
    $ret =  sys_get_temp_dir();
    if(array_key_exists('directories', $GLOBALS) && !is_null($GLOBALS['directories']) && is_array($GLOBALS['directories'])) {
        if (array_key_exists($key, $GLOBALS['directories']))
            $ret = $GLOBALS['directories'][$key];
    }

    return $ret;
}

function generateOutputFileName($baseFileName="NONAME", $ext="UNK", $isUserSpecific=true, $dirKey="debug")
{
    $outDir = getOutputDirectory($dirKey);
    $today = "_" . getNowAsString("");
    $user = "";
    if($isUserSpecific === true) {
        $objUser = \JobScooper\DataAccess\User::getCurrentUser();
        if(!is_null($objUser))
        {
            $user = "_" . $objUser->getUserSlug();
        }
    }

    $ret = "{$outDir}/{$baseFileName}{$user}{$today}.{$ext}";
    return $ret;
}

function is_OptionIncludedSite($JobSiteKey)
{
    $sites = \JobScooper\Utils\DocOptions::get("jobsites");
    return in_array($JobSiteKey, $sites);
}
