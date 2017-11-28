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


function getDebugContext($context=array())
{
    //Debug backtrace called. Find next occurence of class after Logger, or return calling script:
    $dbg = debug_backtrace();
    $i = 0;
    $jobsiteKey = null;
    $usersearch = null;

    $class = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
    while ($i < count($dbg) - 1 ) {
        if (!empty($dbg[$i]['class']) && stripos($dbg[$i]['class'], 'LoggingManager') === false &&
            (empty($dbg[$i]['function']) || !in_array($dbg[$i]['function'], array("getDebugContent", "handleException"))))
            {
            $class = $dbg[$i]['class'] . "->" . $dbg[$i]['function'] ."()";
            if(!empty($dbg[$i]['object']))
            {
                $objclass = get_class($dbg[$i]['object']);
                if(strcasecmp($objclass, $dbg[$i]['class']) != 0)
                {
                    $class = "{$objclass} -> {$class}";
                    try{
                        if( is_object($dbg[$i]['object']) && method_exists($dbg[$i]['object'], "getName"))
                            $jobsiteKey = $dbg[$i]['object']->getName();
                        } catch (Exception $ex) {
                            $jobsiteKey = "";
                        }
                    try{
                        if(array_key_exists('args', $dbg[$i]) & is_array($dbg[$i]['args']))
                            if(is_object($dbg[$i]['args'][0]) && method_exists(get_class($dbg[$i]['args'][0]), "getUserSearchRunKey"))
                                $usersearch = $dbg[$i]['args'][0]->getUserSearchRunKey();
                            else
                                $usersearch = "";
                    } catch (Exception $ex) { $usersearch = ""; }
                }
                break;
            }
        }
        $i++;
    }

    $context['channel'] = is_null($jobsiteKey) ? "default" : "plugins";
    $context['class_call'] = $class;
    $context['plugin_jobsite'] = $jobsiteKey;
    $context['user_search_run_key'] = $usersearch;
    $context['memory_usage'] = memory_get_usage() / 1024 / 1024;


    return $context;
}

function LogLine($msg, $scooper_level=\C__DISPLAY_NORMAL__, $context=array())
{
    if(is_null($GLOBALS['logger']) || !isset($GLOBALS['logger']))
    {
        print($msg . "\r\n");
    }
    else
    {
        $GLOBALS['logger']->logLine($msg, $scooper_level, null, $context);
    }
}

function LogError($msg, $scooper_level=\C__DISPLAY_ERROR__)
{
    if(is_null($GLOBALS['logger']) || !isset($GLOBALS['logger']))
    {
        print($msg . "\r\n");
    }
    else
    {
        $context = getDebugContext();

        $GLOBALS['logger']->logLine($msg, $scooper_level, \Psr\Log\LogLevel::ERROR, $context);
    }
}

function LogDebug($msg, $scooper_level=C__DISPLAY_NORMAL__)
{
    if(is_null($GLOBALS['logger']) || !isset($GLOBALS['logger']))
    {
        print($msg . "\r\n");
    }
    else
    {
        $context = getDebugContext();

        $GLOBALS['logger']->logLine($msg, $scooper_level, \Psr\Log\LogLevel::DEBUG, $context);
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

    File Path Utils


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

function isValueURLEncoded($str)
{
    if (strlen($str) <= 0) return 0;
    return (substr_count_array($str, array("%22", "&", "=", "+", "-", "%7C", "%3C")) > 0);
}


function getPhpMemoryUsage()
{
    $size = memory_get_usage(true);

    $unit = array(' bytes', 'KB', 'MB', 'GB', 'TB', 'PN');

    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}
function getTodayAsString($delim = "-")
{
    $fmt = "Y" . $delim . "m" . $delim . "d";
    return date($fmt);
}

function getNowAsString($delim = "-")
{
    $fmt = join($delim, array("%Y", "%m", "%d", "%H", "%M", "%S"));
    return strftime($fmt, time());
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
    $context = getDebugContext();
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
    LogLine($msg, \C__DISPLAY_ERROR__, $context);
    LogLine(PHP_EOL . PHP_EOL . PHP_EOL);

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

function getFailedSearchesByPlugin()
{
    return getSearchesByRunResult("failed");
}

function getSearchesByRunResult($resultCode)
{
    $arrSearchReportByPlugin = array();
    if(is_null($GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN']))
        return array();

    foreach ($GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'] as $jobsiteKey) {
        foreach($jobsiteKey as $search)
        {
            if($search->getRunResultCode() == "failed") {
                if (!array_key_exists($search->getJobSiteKey(), $arrSearchReportByPlugin))
                    $arrSearchReportByPlugin[$search->getJobSiteKey()] = array();

                $arrSearchReportByPlugin[$search->getJobSiteKey()][$search->getUserSearchRunKey()] = cloneArray($search->toArray(), array(
                    'keywords_string_for_url',
                    'base_url_format',
                    'keywords_array_tokenized',
                    'search_start_url',
                    'location_user_specified_override',
                    'location_search_value',
                    'keyword_search_override',
                    'keywords_array'));
            }
        }
    }

    return $arrSearchReportByPlugin;
}

function setSiteAsExcluded($excludedSite)
{
    $excludedSite = cleanupSlugPart($excludedSite);
    if(!array_key_exists('JOBSITES_AND_SEARCHES_TO_RUN', $GLOBALS))
        $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'] = array();

    $GLOBALS['USERDATA']['configuration_settings']['excluded_sites'][$excludedSite] = $excludedSite;
    if(array_key_exists($excludedSite, $GLOBALS['USERDATA']['configuration_settings']['included_sites']))
    {
        unset($GLOBALS['USERDATA']['configuration_settings']['included_sites'][$excludedSite]);
    }

    if(isset($GLOBALS['logger']) && isDebug()) $GLOBALS['logger']->logLine("Setting " . $excludedSite . " as excluded for this run.", \C__DISPLAY_ITEM_DETAIL__);

    if(array_key_exists($excludedSite, $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'])) {
        foreach($GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'][$excludedSite] as $search)
        {
            $search->setRunResultCode("excluded");
            $search->save();
        }
    }
}

function noJobStringMatch($var, $matchString)
{
    if(is_null($matchString) || strlen($matchString) == 0)
        throw new Exception("Invalid match string passed to helper noJobStringMatch.");

    if(stristr(strtoupper($var), strtoupper($matchString)) !== false)
        return 0;

    return null;
}


function getRunDateRange()
{
    $configNumDays = getConfigurationSettings('number_days');
    $num_days = filter_var($configNumDays, FILTER_VALIDATE_INT);
    if($num_days === false)
        $num_days = 1;

    $strDateRange = null;
    $startDate = new DateTime();
    $strMod = "-" . $num_days . " days";
    $startDate = $startDate->modify($strMod);
    $today = new DateTime();
    if ($startDate->format('Y-m-d') != $today->format('Y-m-d')) {
        $strDateRange = $startDate->format('D, M d') . " - " . $today->format('D, M d');
    } else {
        $strDateRange = $today->format('D, M d');
    }
    return $strDateRange;
}


define('REMOVE_PUNCT', 0x001);
define('LOWERCASE', 0x002);
define('HTML_DECODE', 0x004);
define('URL_ENCODE', 0x008);
define('REPLACE_SPACES_WITH_HYPHENS', 0x010);
define('REMOVE_EXTRA_WHITESPACE', 0x020);
define('REMOVE_ALL_SPACES', 0x040);
define('SIMPLE_TEXT_CLEANUP', HTML_DECODE | REMOVE_EXTRA_WHITESPACE );
define('ADVANCED_TEXT_CLEANUP', HTML_DECODE | REMOVE_EXTRA_WHITESPACE | REMOVE_PUNCT );
define('FOR_LOOKUP_VALUE_MATCHING', REMOVE_PUNCT | LOWERCASE | HTML_DECODE | REMOVE_EXTRA_WHITESPACE | REMOVE_ALL_SPACES );
define('DEFAULT_SCRUB', REMOVE_PUNCT | HTML_DECODE | LOWERCASE | REMOVE_EXTRA_WHITESPACE );

//And so on, 0x8, 0x10, 0x20, 0x40, 0x80, 0x100, 0x200, 0x400, 0x800 etc..


function strScrub($str, $flags = null)
{
    if($flags == null)  $flags = REMOVE_EXTRA_WHITESPACE;

    if(strlen($str) == 0) return $str;

    // If this isn't a valid string we can process,
    // log a warning and return the value back to the caller untouched.
    //
    if($str == null || !isset($str) || !is_string($str))
    {
        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("strScrub was called with an invalid value to scrub (not a string, null, or similar.  Cannot scrub the passed value: " . var_export($str, true), C__DISPLAY_WARNING__);
        return $str;
    }

    $ret = $str;


    if ($flags & HTML_DECODE)
    {
        $ret = html_entity_decode($ret);
    }

    if ($flags & REMOVE_PUNCT)  // has to come after HTML_DECODE
    {
        $ret = strip_punctuation($ret);
    }

    if ($flags & REMOVE_ALL_SPACES)
    {
        $ret = trim($ret);
        if($ret != null)
        {
            $ret  = str_replace(" ", "", $ret);
        }
    }

    if ($flags & REMOVE_EXTRA_WHITESPACE)
    {
        $ret = trim($ret);
        if($ret != null)
        {
            $ret  = str_replace(array("   ", "  ", "    "), " ", $ret);
            $ret  = str_replace(array("   ", "  ", "    "), " ", $ret);
        }
        $ret = trim($ret);
    }


    if ($flags & REPLACE_SPACES_WITH_HYPHENS) // has to come after REMOVE_EXTRA_WHITESPACE
    {
        $ret  = str_replace(" ", "-", $ret); // do it twice to catch the multiples
    }


    if ($flags & LOWERCASE)
    {
        $ret = strtolower($ret);
    }

    if ($flags & URL_ENCODE)
    {
        $ret  = urlencode($ret);
    }

    return $ret;
}

function intceil($number)
{
    if(is_string($number)) $number = floatval($number);

    $ret = ( is_numeric($number) ) ? ceil($number) : false;
    if ($ret != false) $ret = intval($ret);

    return $ret;
}


function clean_utf8($string, $control = true)
{
    $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);

    if ($control === true) {
        return preg_replace('~\p{C}+~u', '', $string);
    }

    return preg_replace(array('~\r\n?~', '~[^\P{C}\t\n]+~u'), array("\n", ''), $string);
}


function replaceTokensInString($formatString, $arrVariables)
{
//    $variables = array("first_name"=>"John","last_name"=>"Smith","status"=>"won");
//    $string = 'Dear {FIRST_NAME} {LAST_NAME}, we wanted to tell you that you {STATUS} the competition.';
    $ret = $formatString;
    foreach($arrVariables as $key => $value){
        $ret = str_replace('{'.strtoupper($key).'}', $value, $ret);
//        $ret = str_replace('***'.strtoupper($key).'***', $value, $ret);
    }

    return $ret;
}

function combineTextAllChildren($node, $fRecursed = false)
{

    $retStr = "";
    if (is_array($node) && count($node) > 1) {
        $strError = sprintf("Warning:  " . count($node) . " DOM nodes were sent to combineTextAllChildren instead of a single starting node.  Using first node only.");
        LogLine($strError, \C__DISPLAY_WARNING__);
    }

    if(is_array($node) && count($node) >= 1)
        $node = $node[0];

    if ($node->hasChildren()) {
        foreach ($node->children() as $child) {
            $retStr = $retStr . " " . combineTextAllChildren($child, true);
        }
        unset($child);
    }

    if($fRecursed == false)
    {
        $retStr = strScrub($node->text() . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE) . $retStr;
    }

    return $retStr;

}

function combineTextAllNodes($nodes)
{
    $retStr = "";
    if ($nodes) {
        foreach ($nodes as $node) {
            if($retStr != "")
                $retStr = $retStr . ", ";

            $retStr = $retStr . strScrub($node->text() . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE);
            if(!is_null($node->children())) {
                foreach ($node->children() as $child) {
                    $retStr = $retStr . " " . combineTextAllChildren($child, true);
                }
            }
        }
    }
    return $retStr;

}


function doExec($cmd)
{
    $cmdOutput = array();
    $cmdRet = "";

    exec($cmd, $cmdOutput, $cmdRet);
    foreach ($cmdOutput as $resultLine)
        if (!is_null($GLOBALS['logger'])) $GLOBALS['logger']->logLine($resultLine, \C__DISPLAY_ITEM_DETAIL__);
    unset($resultLine);

    if (is_array($cmdOutput))
    {
        if (count($cmdOutput) >= 1)
            return $cmdOutput[0];
        else
            return "";
    }
    return $cmdOutput;
}

function glue_url($parsed) {
    if (!is_array($parsed)) {
        return false;
    }

    $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';
    $uri .= isset($parsed['user']) ? $parsed['user'].(isset($parsed['pass']) ? ':'.$parsed['pass'] : '').'@' : '';
    $uri .= isset($parsed['host']) ? $parsed['host'] : '';
    $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';

    if (isset($parsed['path'])) {
        $uri .= (substr($parsed['path'], 0, 1) == '/') ?
            $parsed['path'] : ((!empty($uri) ? '/' : '' ) . $parsed['path']);
    }

    $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
    $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

    return $uri;
}
