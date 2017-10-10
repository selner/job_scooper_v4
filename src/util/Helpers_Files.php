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




function loadCSV($filename, $indexKeyName = null)
{
    if (!is_file($filename)) {
        throw new Exception("Specified input file '" . $filename . "' was not found.  Aborting.");
    }

    $file = fopen($filename, "r");
    if (is_bool($file)) {
        throw new Exception("Specified input file '" . $filename . "' could not be opened.  Aborting.");
    }

    $headers = fgetcsv($file);
    $arrLoadedList = array();
    while (!feof($file)) {
        $rowData = fgetcsv($file);
        if ($rowData === false) {
            break;
        } else {
            $arrRec = array_combine($headers, $rowData);
            if ($indexKeyName != null)
                $arrLoadedList[$arrRec[$indexKeyName]] = $arrRec;
            else
                $arrLoadedList[] = $arrRec;
        }
    }

    fclose($file);

    return $arrLoadedList;

}


function getDefaultJobsOutputFileName($strFilePrefix = '', $strBase = '', $strExt = '', $delim = "", $directoryKey = null)
{
    $strFilename = '';
    if (strlen($strFilePrefix) > 0) $strFilename .= $strFilePrefix . "-";
    $date = date_create(null);
    $fmt = "Y" . $delim . "m" . $delim . "d" . "Hi";

    $strFilename .= date_format($date, $fmt);

    if (strlen($strBase) > 0) $strFilename .= "-" . $strBase;
    if (strlen($strExt) > 0) $strFilename .= "." . $strExt;

    if(!is_null($directoryKey) && array_key_exists($directoryKey, $GLOBALS['USERDATA']['directories']))
        $strFilename = $GLOBALS['USERDATA']['directories'][$directoryKey] . "/" . $strFilename;
    return $strFilename;
}

function encodeJSON($data)
{
    $jsonData = json_encode($data, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
    if ($jsonData === false) {
        $err = json_last_error_msg();
        $errMsg = "Error:  Unable to data to JSON.  Error: " . $err;
        LogLine($errMsg, \C__DISPLAY_ERROR__);
        throw new Exception($errMsg);
    }
    return $jsonData;
}

function writeJSON($data, $filepath)
{
    $jsonData = encodeJSON($data);

    LogLine("Writing data to json file " . $filepath);
    if (file_put_contents($filepath, $jsonData, FILE_TEXT) === false) {
        $err = error_get_last();
        $errMsg = "Error:  Unable to save JSON results to file " . $filepath . " due to error   " . $err;
        LogLine($errMsg, \C__DISPLAY_ERROR__);
        throw new Exception($errMsg);

    }

    return $filepath;
}

function decodeJSON($strJsonText, $options=null, $boolEscapeBackSlashes=false)
{
    if(is_null($options))
        $options = JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP;


    if($boolEscapeBackSlashes === true)
        $strJsonText = str_replace('\\', '\\\\', $strJsonText);
    $data = json_decode($strJsonText, $assoc = true, $depth=512, $options);
    return $data;

}

function loadJSON($file, $options=null, $boolEscapeBackSlashes=false)
{
    if(is_file($file)) {
#        LogLine("Reading json data from file " . $file, \C__DISPLAY_ITEM_DETAIL__);
        LogLine("Reading json data from file " . $file);
        $jsonText = file_get_contents($file, FILE_TEXT);
        return decodeJSON($jsonText, $options, $boolEscapeBackSlashes);
    }
    else
    {
        LogLine("Unable to load json data from file " . $file, \C__DISPLAY_ERROR__);
        return null;
    }

}
