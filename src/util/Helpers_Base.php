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

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Logging                                                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

const C__NAPPTOPLEVEL__ = 0;
const C__NAPPFIRSTLEVEL__ = 1;
const C__NAPPSECONDLEVEL__ = 2;
const C__SECTION_BEGIN__ = 1;
const C__SECTION_END__ = 2;
const C__DISPLAY_NORMAL__ = 100;
const C__DISPLAY_SECTION_START__ = 250;
const C__DISPLAY_SECTION_END__ = 275;
const C__DISPLAY_ITEM_START__ = 200;
const C__DISPLAY_ITEM_DETAIL__ = 300;
const C__DISPLAY_ITEM_RESULT__ = 350;

const C__DISPLAY_MOMENTARY_INTERUPPT__ = 400;
const C__DISPLAY_WARNING__ = 405;
const C__DISPLAY_ERROR__ = 500;
const C__DISPLAY_RESULT__ = 600;
const C__DISPLAY_FUNCTION__= 700;
const C__DISPLAY_SUMMARY__ = 750;




function LogLine($msg, $scooper_level=\C__DISPLAY_NORMAL__, $context=array())
{
    if(is_null($GLOBALS['logger']) || !isset($GLOBALS['logger']))
    {
        print($msg . "\r\n");
    }
    else
    {
        //Debug backtrace called. Find next occurence of class after Logger, or return calling script:
        $dbg = debug_backtrace();
        $i = 0;
        $jobsite = null;
        $usersearch = null;

        $class = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
        while ($i < count($dbg) - 1 ) {
            if (!empty($dbg[$i]['class']) && $dbg[$i]['class'] != 'Logger' ) {
                $class = $dbg[$i]['class'] . "->" . $dbg[$i]['function'] ."()";
                if(!empty($dbg[$i]['object']))
                {
                    $objclass = get_class($dbg[$i]['object']);
                    if(strcasecmp($objclass, $dbg[$i]['class']) != 0)
                    {
                        $class = "{$objclass} -> {$class}";
                        try{  $jobsite = $dbg[$i]['object']->getName(); } catch (Exception $ex) { $jobsite = ""; }
                        try{  $usersearch = count($dbg[$i]['args']) > 0 ? $dbg[$i]['args'][0]->getUserSearchRunKey() : ""; } catch (Exception $ex) { $usersearch = ""; }
                    }
                }
                break;
            }
            $i++;
        }

        $context = array();
        $context['channel'] = is_null($jobsite) ? "default" : "plugins";
        $context['class_call'] = $class;
        $context['plugin_jobsite'] = $jobsite;
        $context['user_search_run_key'] = $usersearch;

        $GLOBALS['logger']->logLine($msg, $scooper_level, $context);
    }
}

function LogPlainText($msg, $context = array())
{
    $textParts = preg_split("/[\\r\\n|" . PHP_EOL . "]/", $msg);
    if(($textParts === false) || is_null($textParts))
        logLine($msg);
    else {
        foreach ($textParts as $part) {
            LogLine($part);
        }
    }
}


function object_to_array($obj)
{
    $arr = is_object($obj) ? get_object_vars($obj) : $obj;
    foreach ($arr as $key => $val) {
        $val = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
        $arr[$key] = $val;
    }

    return $arr;
}


function isBitFlagSet($flagSettings, $flagToCheck)
{
    $ret = ($flagSettings & $flagToCheck);
    if($ret == $flagToCheck) { return true; }
    return false;
}

/*

    File Path util


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
    $basefile = getDefaultJobsOutputFileName($strFilePrefix = "_debug_" . $strBaseFileName, $strExt = "", $delim = "-");
    $debugJSONFile = generateOutputFileName($basefile, $ext="json");
    file_put_contents($debugJSONFile, $jsonSelf);

    return $debugJSONFile;

}

function handleException($ex, $fmtLogMsg = null, $raise = true)
{
    $toThrow = $ex;
    if (is_null($toThrow))
        $toThrow = new Exception($fmtLogMsg);
    
    $msg = $fmtLogMsg;
    if (!is_null($toThrow) && !is_null($fmtLogMsg) && !is_null($ex) && strlen($fmtLogMsg) > 0)
    {
        if(stristr($fmtLogMsg, "%s") !== false)
        {
            $msg = sprintf($fmtLogMsg, $toThrow->getMessage());
            $toThrow = new Exception($msg, null, $ex);
        }
        else
        {
            $msg = $fmtLogMsg . PHP_EOL . " ~ " . $toThrow->getMessage();
        }
    }
    elseif(!is_null($ex))
    {
        $msg = $toThrow->getMessage();
    }

    LogLine(PHP_EOL . PHP_EOL . PHP_EOL);
    LogLine($msg, \C__DISPLAY_ERROR__);
    LogLine(PHP_EOL . PHP_EOL . PHP_EOL);

    if ($raise == true) {
        throw $toThrow;
    }
}


function exceptionHandler($exception) {

    // these are our templates
    $traceline = "#%s %s(%s): %s(%s)";
    $msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

    // alter your trace as you please, here
    $trace = $exception->getTrace();
    $key = "unknown";
    foreach ($trace as $key => $stackPoint) {
        // I'm converting arguments to their type
        // (prevents passwords from ever getting logged as anything other than 'string')
        $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
    }

    // build your tracelines
    $result = array();
    foreach ($trace as $key => $stackPoint) {
        $result[] = sprintf(
            $traceline,
            $key,
            $stackPoint['file'],
            $stackPoint['line'],
            $stackPoint['function'],
            implode(', ', $stackPoint['args'])
        );
    }
    // trace always ends with {main}
    $result[] = '#' . ++$key . ' {main}';

    // write tracelines into main template
    $msg = sprintf(
        $msg,
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        implode("\n", $result),
        $exception->getFile(),
        $exception->getLine()
    );

    // log or echo as you please
    error_log($msg);
}
set_exception_handler('exceptionHandler');


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


function array_subset(array $haystack, array $needle)
{
    return array_intersect_key($haystack, array_flip($needle));
}

function array_from_orm_object_list_by_array_keys(array $list, array $keysToReturn)
{
    return array_map(function ($v) use ($keysToReturn) {return array_subset($v->toArray(), $keysToReturn);} , $list);
}

