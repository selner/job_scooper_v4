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
require_once __ROOT__ . "/bootstrap.php";


function get_PharseOptionValue($strOptName)
{
    $retvalue = null;
    $strOptGiven = $strOptName."_given";
    if(isset($GLOBALS['OPTS']) && isset($GLOBALS['OPTS'][$strOptGiven]) && $GLOBALS['OPTS'][$strOptGiven] == true)
    {
        if(isset($GLOBALS['logger']) && isset($GLOBALS['VERBOSE'])) $GLOBALS['logger']->logLine("'".$strOptName ."'"."=[".$GLOBALS['OPTS'][$strOptName] ."]", C__DISPLAY_ITEM_DETAIL__);
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
    $ret = parseFilePath($fullpath, $fRequireFile);

    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("". $key ." set to [" . var_export($ret, true) . "]", C__DISPLAY_ITEM_DETAIL__);

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

//    $fMatched = preg_match("/^['|\"]([^'\"]{1,})['|\"]$/", $valOpt, $arrMatches);
//    if($fMatched) $valOpt = $arrMatches[1];

    if($valOpt) $ret = parseFilePath($valOpt, $fFileRequired);

    return $ret;

}