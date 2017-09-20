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


require_once(__ROOT__ . "/bootstrap.php");

function LogLine($msg, $scooper_level=\C__DISPLAY_NORMAL__)
{
    if(is_null($GLOBALS['logger']) || !isset($GLOBALS['logger']))
    {
        print($msg);
    }
    else
    {
        $GLOBALS['logger']->logLine($msg, $scooper_level);
    }
}

function LogWarning($msg)
{
    LogLine($msg, \C__DISPLAY_WARNING__);
}


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


function isBitFlagSet($flagSettings, $flagToCheck)
{
    $ret = ($flagSettings & $flagToCheck);
    if($ret == $flagToCheck) { return true; }
    return false;
}

/*

    File Path Helpers


*/

function getFullPathFromFileDetails($arrFileDetails, $strPrependToFileBase = "", $strAppendToFileBase = "")
{
    return $arrFileDetails['directory'] . getFileNameFromFileDetails($arrFileDetails, $strPrependToFileBase, $strAppendToFileBase);

}

function getFileNameFromFileDetails($arrFileDetails, $strPrependToFileBase = "", $strAppendToFileBase = "")
{
    return $strPrependToFileBase . $arrFileDetails['file_name_base'] . $strAppendToFileBase . "." . $arrFileDetails['file_extension'];
}

CONST C__FILEPATH_NO_FLAGS = 0x0;
CONST C__FILEPATH_FILE_MUST_EXIST = 0x1;
CONST C__FILEPATH_DIRECTORY_MUST_EXIST = 0x2;
CONST C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED= 0x4;

function parseFilePath($strFilePath, $fFileMustExist = false)
{
    return getFilePathDetailsFromString($strFilePath, ($fFileMustExist ? C__FILEPATH_FILE_MUST_EXIST : C__FILEPATH_NO_FLAGS));
}

function getFilePathDetailsFromString($strFilePath, $flags = C__FILEPATH_NO_FLAGS)
{
    $fileDetailsReturn = array ('directory' => '', 'has_directory' => false, 'file_name' => '', 'has_file' => false, 'file_name_base' => '', 'file_extension' => '', 'full_file_path' => '' );


    if($strFilePath == null || strlen($strFilePath) <= 0)
    {
        return $fileDetailsReturn;
    }

    // if the path doesn't start with a '/', it's a relative path
    //
    $fPathIsRelative = !(substr($strFilePath, 0, 1) == '/');

    //************************************************************************
    //
    // First, pull the path string apart into it's component directories and possible filename
    // by separating the path elements by '/'
    $arrInputPathAllParts = explode("/", $strFilePath);

    // Setup a string value for the last element (usually a filename, but could be directory)
    //
    $finalPathPart_String = $arrInputPathAllParts[count($arrInputPathAllParts)-1];

    // Setup array value for the last element separated by '.'.  We'll assume that if there
    // was a '.' then the last element was a filename, not a directory (and vice versa.)
    //
    $finalPathPart_DotArray = $arrLastTermParts = explode(".", $finalPathPart_String);

    // Lastly, set an array value for all the directory parts minus the last one
    //
    $arrPathParts_AllButFinal = $arrInputPathAllParts;  // copy the full list of parts and then...
    unset($arrPathParts_AllButFinal[count($arrPathParts_AllButFinal)-1]); // ... remove the last part


    //************************************************************************
    //
    // Now let's figure out what each part really maps to and setup the array with names for returning
    // to the caller.
    //
    // If AllParts only has one item, then there were no "/" characters in the path string.
    // So assume the path was either a filename only OR a relative directory path with no trailing '/'
    //
    if(substr($strFilePath, (strlen($strFilePath) - 1), 1) == '/' || // if the path ended with a / or...
        count($finalPathPart_DotArray) == 1) // ... only the last part had no '.' so isn't a filename
    {
        //
        // There was no filename on the input path
        //
        $fileDetailsReturn['has_file'] = false;

        // add any beginning path parts to the directory path...
        if(count($arrPathParts_AllButFinal) > 0)
        {
            $strDirectory = join("/", $arrPathParts_AllButFinal);
            // and add the final part to the end
            $strDirectory .= "/" . $finalPathPart_String;
        }
        else // otherwise, the directory is just the final part
        {
            $strDirectory = $finalPathPart_String;
        }
        $fileDetailsReturn['has_directory'] = true;
        $fileDetailsReturn['directory'] = $strDirectory;
    }
    else // we have a filename at least
    {
        assert(count($finalPathPart_DotArray) > 1);

        // we did have a '.' so let's assume this term is a filename
        $fileDetailsReturn['file_name'] = $finalPathPart_String;

        // the last portion of the split filename is the extension
        $fileDetailsReturn['file_extension'] = $finalPathPart_DotArray[count($finalPathPart_DotArray)-1];

        // everything else is the base name for the file
        $fileDetailsReturn['file_name_base'] = join(".", array_splice($finalPathPart_DotArray,0,count($finalPathPart_DotArray)-1));
        $fileDetailsReturn['has_file'] = true;


        // Set the directory part to everything before the last part
        if(count($arrPathParts_AllButFinal) > 0)
        {
            // if the first part is "" then the path part
            // was actually "/<something>" so put the / back
            if(count($arrPathParts_AllButFinal) == 1 && strlen($arrPathParts_AllButFinal[0]) == 0)
            {
                $fileDetailsReturn['directory'] = "/";
            }
            $fileDetailsReturn['directory'] .= join("/", $arrPathParts_AllButFinal);
            $fileDetailsReturn['has_directory'] = true;
        }

        // if there were no other parts, so set the directory to be relative to the file
        if($fileDetailsReturn['has_directory'] == false)
        {
            $fileDetailsReturn['directory'] = "./";
            $fileDetailsReturn['has_directory'] = true;
        }
    }

    assert($fileDetailsReturn['has_directory'] == true);

    // Make sure the directory value always ends with a slash
    // (makes it easier for callers to depend on it)
    //
    if((strlen($fileDetailsReturn['directory']) >= 1) &&
        $fileDetailsReturn['directory'][strlen($fileDetailsReturn['directory'])-1] != "/")
    {
        $fileDetailsReturn['directory'] = $fileDetailsReturn['directory'] . "/";
    }

    if($fileDetailsReturn['has_file'])
    {
        $fileDetailsReturn['full_file_path'] = $fileDetailsReturn['directory'] . $fileDetailsReturn['file_name'];


        assert($fileDetailsReturn['file_name'] == $fileDetailsReturn['file_name_base'] . "." . $fileDetailsReturn['file_extension']);
        assert($fileDetailsReturn['full_file_path'] == $fileDetailsReturn['directory'] . $fileDetailsReturn['file_name_base'] . "." . $fileDetailsReturn['file_extension']);

    }
    else
    {
        $fileDetailsReturn['full_file_path'] = '';
    }


    //
    // At this point, we've set the values for the return array completely
    //


    if(isBitFlagSet($flags, C__FILEPATH_DIRECTORY_MUST_EXIST) && !is_dir($fileDetailsReturn['directory']))
    {
        throw new \ErrorException("Directory '" . $fileDetailsReturn['directory'] . "' does not exist.");
    }

    if(isBitFlagSet($flags, C__FILEPATH_FILE_MUST_EXIST) && !is_file($fileDetailsReturn['full_file_path']))
    {
        throw new \ErrorException("File '" . $fileDetailsReturn['full_file_path'] . "' does not exist.");
    }

    if(isBitFlagSet($flags, C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED) && !is_dir($fileDetailsReturn['directory']))
    {
        mkdir($fileDetailsReturn['directory'], 0777, true);
    }




    return $fileDetailsReturn;

}


function exportToDebugJSON($obj, $strBaseFileName)
{
    $saveArr = array();
    $arrObj = object_to_array($obj);
    foreach (array_keys($arrObj) as $key) {
        $saveArr[$key] = json_encode($arrObj[$key], JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
    }
    unset($key);

    $jsonSelf = json_encode($saveArr, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
    $debugJSONFile = $GLOBALS['USERDATA']['directories']['debug'] . "/" . getDefaultJobsOutputFileName($strFilePrefix = "_debug_" . $strBaseFileName, $strExt = "", $delim = "-") . ".json";
    file_put_contents($debugJSONFile, $jsonSelf);

    return $debugJSONFile;

}

function handleException($ex, $fmtLogMsg = null, $raise = true)
{
    $toThrow = $ex;
    if (is_null($toThrow))
        $toThrow = new Exception($fmtLogMsg);

    if (!array_key_exists('ERROR_REPORT_FILES', $GLOBALS['USERDATA']))
        $GLOBALS['USERDATA']['ERROR_REPORT_FILES'] = array();

    $msg = $fmtLogMsg;
    if (!is_null($toThrow) && !is_null($fmtLogMsg) && !is_null($ex) && strlen($fmtLogMsg) > 0 &&
        stristr($fmtLogMsg, "%s") !== false)
    {
        $msg = sprintf($fmtLogMsg, $toThrow->getMessage());
        $toThrow = new Exception($msg, $toThrow->getCode(), $previous=$ex);
    }
    elseif(!is_null($ex))
    {
        $msg = $toThrow->getMessage();
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

    LogLine(PHP_EOL . PHP_EOL . PHP_EOL);
    LogLine($msg, \C__DISPLAY_ERROR__);
    LogLine(PHP_EOL . PHP_EOL . PHP_EOL);

    $now = new DateTime('NOW');

    $debugData = array(
        "error_time" => $now->format('Y-m-d\TH:i:s'),
        "exception_code" => $toThrow->getCode(),
        "exception_message" => $msg,
        "exception_file" => $toThrow->getFile(),
        "exception_line" => $toThrow->getLine(),
        "exception" => object_to_array($toThrow)
//        "object_properties" => null,
////        "debug_backtrace" => var_export(debug_backtrace(), true),
//        "exception_stack_trace" => $ex->getTraceAsString()
    );
    $filenm = exportToDebugJSON($debugData, "exception" . $excKey);

    $GLOBALS['USERDATA']['ERROR_REPORT_FILES'][$excKey] = getFilePathDetailsFromString($filenm);


    if ($raise == true) {
        throw $toThrow;
    }
}

/**
 * Strip punctuation from text.
 * http://nadeausoftware.com/articles/2007/9/php_tip_how_strip_punctuation_characters_web_page
 * @param $text
 * @return mixed
 */
function strip_punctuation( $text )
{
    $urlbrackets    = '\[\]\(\)';
    $urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
    $urlspaceafter  = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
    $urlall         = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;

    $specialquotes  = '\'"\*<>';

    $fullstop       = '\x{002E}\x{FE52}\x{FF0E}';
    $comma          = '\x{002C}\x{FE50}\x{FF0C}';
    $arabsep        = '\x{066B}\x{066C}';
    $numseparators  = $fullstop . $comma . $arabsep;

    $numbersign     = '\x{0023}\x{FE5F}\x{FF03}';
    $percent        = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
    $prime          = '\x{2032}\x{2033}\x{2034}\x{2057}';
    $nummodifiers   = $numbersign . $percent . $prime;

    return preg_replace(
        array(
            // Remove separator, control, formatting, surrogate,
            // open/close quotes.
            '/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
            // Remove other punctuation except special cases
            '/\p{Po}(?<![' . $specialquotes .
            $numseparators . $urlall . $nummodifiers . '])/u',
            // Remove non-URL open/close brackets, except URL brackets.
            '/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
            // Remove special quotes, dashes, connectors, number
            // separators, and URL characters followed by a space
            '/[' . $specialquotes . $numseparators . $urlspaceafter .
            '\p{Pd}\p{Pc}]+((?= )|$)/u',
            // Remove special quotes, connectors, and URL characters
            // preceded by a space
            '/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
            // Remove dashes preceded by a space, but not followed by a number
            '/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
            // Remove consecutive spaces
            '/ +/',
        ),
        ' ',
        $text );
}
