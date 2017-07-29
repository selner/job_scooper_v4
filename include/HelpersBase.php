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



function object_to_array($obj)
{
    $arr = is_object($obj) ? get_object_vars($obj) : $obj;
    foreach ($arr as $key => $val) {
        $val = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
        $arr[$key] = $val;
    }
    unset($key);
    unset($val);

    return $arr;
}

function handleException($ex, $fmtLogMsg = null, $raise = true)
{
    if (is_null($ex))
        $ex = new Exception($fmtLogMsg);

    if (!array_key_exists('ERROR_REPORT_FILES', $GLOBALS['USERDATA']))
        $GLOBALS['USERDATA']['ERROR_REPORT_FILES'] = array();


    $toThrow = $ex;
    if (!is_null($fmtLogMsg)) {
        $msg = sprintf($fmtLogMsg, $ex->getMessage());
        $toThrow = new Exception($msg, $ex->getCode(), $previous=$ex);
    }

//    $msg .= PHP_EOL . "PHP memory usage: " . getPhpMemoryUsage() . PHP_EOL;

    $excKey = md5($msg);

    //
    // Error key = <md5 msg hash><line#>
    //
    if (array_key_exists($excKey, $GLOBALS['USERDATA']['ERROR_REPORT_FILES']) === true) {
        // we already stored this error so need to re-store it.  Just throw it if needed.
        if ($raise === true)
            throw $toThrow;
    }


    if (isset($GLOBALS['logger'])) {
        $GLOBALS['logger']->logLine(PHP_EOL . PHP_EOL . PHP_EOL);
        $GLOBALS['logger']->logLine($msg, \Scooper\C__DISPLAY_ERROR__);
        $GLOBALS['logger']->logLine(PHP_EOL . PHP_EOL . PHP_EOL);
    }

    $now = new DateTime('NOW');

    $debugData = array(
        "error_time" => $now->format('Y-m-d\TH:i:s'),
        "exception_code" => $ex->getCode(),
        "exception_message" => $msg,
        "exception_file" => $ex->getFile(),
        "exception_line" => $ex->getLine(),
        "exception" => \Scooper\object_to_array($ex)
//        "object_properties" => null,
////        "debug_backtrace" => var_export(debug_backtrace(), true),
//        "exception_stack_trace" => $ex->getTraceAsString()
    );
    $filenm = exportToDebugJSON($debugData, "exception" . $excKey);

    $GLOBALS['USERDATA']['ERROR_REPORT_FILES'][$excKey] = \Scooper\getFilePathDetailsFromString($filenm);


    if ($raise == true) {
        throw $toThrow;
    }
}

