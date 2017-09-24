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

function writeJSON($data, $filepath)
{
    $jsonData = json_encode($data, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
    if ($jsonData === false) {
        $err = json_last_error_msg();
        $errMsg = "Error:  Unable to convert jobs list data to json due to error   " . $err;
        LogLine($errMsg, \C__DISPLAY_ERROR__);
        throw new Exception($errMsg);

    }

    LogLine("Writing final job data pull results to json file " . $filepath);
    if (file_put_contents($filepath, $jsonData, FILE_TEXT) === false) {
        $err = error_get_last();
        $errMsg = "Error:  Unable to save JSON results to file " . $filepath . " due to error   " . $err;
        LogLine($errMsg, \C__DISPLAY_ERROR__);
        throw new Exception($errMsg);

    }

    return $filepath;
}

function loadJSON($file)
{
    if(is_file($file)) {
#        LogLine("Reading json data from file " . $file, \C__DISPLAY_ITEM_DETAIL__);
        LogLine("Reading json data from file " . $file);
        $jsonText = file_get_contents($file, FILE_TEXT);

        $data = json_decode($jsonText, $assoc = true, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
        return $data;
    }
    else
    {
        LogLine("Unable to load json data from file " . $file, \C__DISPLAY_ERROR__);
        return null;
    }

}


use LightnCandy\LightnCandy;

function loadTemplate($path)
{
    $template  = file_get_contents($path);


    $partialDir = dirname($path) . "/partials";

    $phpStr = LightnCandy::compile($template, Array(
        'flags' => LightnCandy::FLAG_RENDER_DEBUG | LightnCandy::FLAG_ERROR_LOG| LightnCandy::FLAG_ERROR_EXCEPTION| LightnCandy::FLAG_HANDLEBARSJS_FULL,
        'partialresolver' => function ($cx, $name) use($partialDir) {
            $partialpath = "$partialDir/$name.tmpl";
            if (file_exists($partialpath)) {
                return file_get_contents($partialpath);
            }
            return "[partial (file:$partialpath) not found]";
        }

    ));  // set compiled PHP code into $phpStr

    // Save the compiled PHP code into a php file
    $renderFile = $GLOBALS['USERDATA']['directories']['debug'] . "/" .basename($path) .'-render.php';

    file_put_contents($renderFile, '<?php ' . $phpStr . '?>');

    // Get the render function from the php file
//    $renderer = include($renderFile);
// Get the render function
    $renderer = LightnCandy::prepare($phpStr);
    if($renderer == false)
    {
        throw new Exception("Error: unable to compile template '$path'");
    }


    return $renderer;
}

