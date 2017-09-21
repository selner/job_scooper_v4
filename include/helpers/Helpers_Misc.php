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




function array_find_closest_key_match($search, $arr) {
    $closest = null;
    $closestScore = null;
    $percent = 0;
    foreach (array_keys($arr) as $item) {
        similar_text($search, $item, $percent);
        if($percent > $closestScore) {
            $closestScore = $percent;
            $closest = $item;
        }
//        if ($closest === null || abs($search - $closest) > abs($item - $search)) {
//            $closest = $item;
//        }
    }
    return $closest;
}

function clean_utf8($string, $control = true)
{
    $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);

    if ($control === true) {
        return preg_replace('~\p{C}+~u', '', $string);
    }

    return preg_replace(array('~\r\n?~', '~[^\P{C}\t\n]+~u'), array("\n", ''), $string);
}


function combineTextAllChildren($node, $fRecursed = false)
{

    $retStr = "";
    if ($node->hasChildNodes()) {
        $retStr = strScrub($node->plaintext . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE);
        foreach ($node->childNodes() as $child) {
            $retStr = $retStr . " " . combineTextAllChildren($child, true);
        }
        unset($child);

    } elseif (isset($node->plaintext) && $fRecursed == false) {
        $retStr = strScrub($node->plaintext . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE);
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

            $retStr = $retStr . strScrub($node->plaintext . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE);
            if(!is_null($node->childNodes())) {
                foreach ($node->childNodes() as $child) {
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

function countAssociativeArrayValues($arrToCount)
{
    if ($arrToCount == null || !is_array($arrToCount)) {
        return 0;
    }

    $count = 0;
    foreach ($arrToCount as $item) {
        $count = $count + 1;
    }
    unset($item);


    $arrValues = array_values($arrToCount);
    $nValues = count($arrValues);
    return max($nValues, $count);
}

function countJobRecords($arrJobs)
{
    return countAssociativeArrayValues($arrJobs);
}

function getArrayItemDetailsAsString($arrItem, $key, $fIsFirstItem = true, $strDelimiter = "", $strIntro = "", $fIncludeKey = true)
{
    $strReturn = "";

    if (isset($arrItem[$key])) {
        $val = $arrItem[$key];
        if (is_string($val) && strlen($val) > 0) {
            $strVal = $val;
        } elseif (is_array($val) && !(is_array_multidimensional($val))) {
            $strVal = join(" | ", $val);
        } else {
            $strVal = print_r($val, true);
        }

        if ($fIsFirstItem == true) {
            $strReturn = $strIntro;
        } else {
            $strReturn .= $strDelimiter;
        }
        if ($fIncludeKey == true) {
            $strReturn .= $key . '=[' . $strVal . ']';
        } else {
            $strReturn .= $strVal;
        }

    }


    return $strReturn;
}

function cloneArray($source, $arrDontCopyTheseKeys = array())
{
    $retDetails = array_copy($source);

    foreach ($arrDontCopyTheseKeys as $key) {
        unset($retDetails[$key]);
    }

    return $retDetails;
}


function array_mapk($callback, $array)
{
    $newArray = array();
    foreach ($array as $k => $v) {
        $newArray[$k] = call_user_func($callback, $k, $v);
    }
    unset($k);

    return $newArray;
}

function array_unique_multidimensional($input)
{
    $serialized = array_map('serialize', $input);
    $unique = array_unique($serialized);
    return array_intersect_key($input, $unique);
}

function getArrayValuesAsString($arrDetails, $strDelimiter = ", ", $strIntro = "", $fIncludeKey = true)
{
    $strReturn = "";

    if (isset($arrDetails) && is_array($arrDetails)) {
        foreach (array_keys($arrDetails) as $key) {
            $strReturn .= getArrayItemDetailsAsString($arrDetails, $key, (strlen($strReturn) <= 0), $strDelimiter, $strIntro, $fIncludeKey);
        }
        unset($key);
    }

    return $strReturn;
}


function callTokenizer($inputfile, $outputFile, $keyname, $indexKeyName = null)
{
    LogLine("Tokenizing title exclusion matches from " . $inputfile . ".", \C__DISPLAY_ITEM_DETAIL__);
    if (!$outputFile)
        $outputFile = $GLOBALS['USERDATA']['directories']['debug'] . "/tempCallTokenizer.csv";
    $PYTHONPATH = realpath(__ROOT__ . "/python/pyJobNormalizer/");
    $cmd = "python " . $PYTHONPATH . "/normalizeStrings.py -i " . $inputfile . " -o " . $outputFile . " -k " . $keyname;
    if ($indexKeyName != null)
        $cmd .= " --index " . $indexKeyName;
    LogLine("Running command: " . $cmd, \C__DISPLAY_ITEM_DETAIL__);

    doExec($cmd);

    LogLine("Loading tokens for " . $inputfile . ".", \C__DISPLAY_ITEM_DETAIL__);
    $file = fopen($outputFile, "r");
    if (is_bool($file)) {
        throw new Exception("Specified input file '" . $outputFile . "' could not be opened.  Aborting.");
    }

    $headers = fgetcsv($file);
    $arrTokenizedList = array();
    while (!feof($file)) {
        $rowData = fgetcsv($file);
        if ($rowData === false) {
            break;
        } else {
            $arrRec = array_combine($headers, $rowData);
            if ($indexKeyName != null)
                $arrTokenizedList[$arrRec[$indexKeyName]] = $arrRec;
            else
                $arrTokenizedList[] = $arrRec;
        }
    }

    fclose($file);

    return $arrTokenizedList;

}

function tokenizeSingleDimensionArray($arrData, $tempFileKey, $dataKeyName = "keywords", $indexKeyName = null)
{
    $inputFile = $GLOBALS['USERDATA']['directories']['debug'] . "/tmp-" . $tempFileKey . "-token-input.csv";
    $outputFile = $GLOBALS['USERDATA']['directories']['debug'] . "/tmp-" . $tempFileKey . "-token-output.csv";

    $headers = array($dataKeyName);
    if (array_key_exists($dataKeyName, $arrData)) $headers = array_keys($arrData);

    if (is_file($inputFile)) {
        unlink($inputFile);
    }

    if (is_file($outputFile)) {
        unlink($outputFile);
    }

    $file = fopen($inputFile, "w");
    fputcsv($file, $headers);

    foreach ($arrData as $line) {
        if (is_string($line))
            $line = explode(',', $line);
        fputcsv($file, $line);
    }
    unset($line);

    fclose($file);

    $tmpTokenizedWords = callTokenizer($inputFile, $outputFile, $dataKeyName, $indexKeyName);
    $valInterimFiles = get_PharseOptionValue('output_interim_files');

    if (isset($valInterimFiles) && $valInterimFiles != true) {
        unlink($inputFile);
        unlink($outputFile);
    }


    return $tmpTokenizedWords;
}




function tokenizeKeywords($arrKeywords)
{
    if (!is_array($arrKeywords)) {
        throw new Exception("Invalid keywords object type.");
    }

    $arrKeywordTokens = tokenizeSingleDimensionArray($arrKeywords, "srchkwd", "keywords", "keywords");
    $arrReturnKeywordTokens = array_fill_keys(array_keys($arrKeywordTokens), null);
    foreach (array_keys($arrReturnKeywordTokens) as $key) {
        $arrReturnKeywordTokens[$key] = str_replace("|", " ", $arrKeywordTokens[$key]['tokenized']);
    }
    unset($key);

    return $arrReturnKeywordTokens;
}

/**
 * Allows multiple expressions to be tested on one string.
 * This will return a boolean, however you may want to alter this.
 *
 * @author William Jaspers, IV <wjaspers4@gmail.com>
 * @created 2009-02-27 17:00:00 +6:00:00 GMT
 * @access public
 * @ref http://www.php.net/manual/en/function.preg-match.php#89252
 *
 * @param array $patterns An array of expressions to be tested.
 * @param String $subject The data to test.
 * @param array $findings Optional argument to store our results.
 * @param mixed $flags Pass-thru argument to allow normal flags to apply to all tested expressions.
 * @param array $errors A storage bin for errors
 *
 * @returns bool True if successful; false if errors occurred.
 */
function substr_count_multi($subject = "", array $patterns = array(), &$findings = array(), $boolMustMatchAllKeywords = false)
{
    foreach ($patterns as $name => $pattern) {
        $found = false;
        $count = substr_count_array($subject, $pattern);
        if (0 < $count) {
            $findings[$name] = $pattern;
        } else {

            if ($boolMustMatchAllKeywords == true)
                return (sizeof($findings) === sizeof($patterns));

//            if (PREG_NO_ERROR !== ($code = preg_last_error() )) {
//                $errors[$name] = $code;
//            }
//            else
//            {
            // No match was found, so don't return it in the findings
            // $findings[$name] = array();
//            }
        }
    }
    return !(0 === sizeof($findings));
}

function getTodayAsString($delim = "-")
{
    $fmt = "Y" . $delim . "m" . $delim . "d";
    return date($fmt);
}

function getArrayItem($key, $arr)
{
    $ret = null;
    if(array_key_exists($key, $arr))
    {
        $ret = $arr[$key];
    }

    if(is_string($ret))
        $ret = stripslashes($ret);

    return $ret;
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


function getFailedSearchesByPlugin()
{
    return getSearchesByRunResult("failed");
}

function getSearchesByRunResult($resultCode)
{
    $arrSearchReportByPlugin = array();
    foreach ($GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'] as $jobsite) {
        foreach($jobsite as $search)
        {
            if($search->getRunResultCode() == "failed") {
                if (!array_key_exists($search->getJobSiteKey(), $arrSearchReportByPlugin))
                    $arrSearchReportByPlugin[$search->getJobSiteKey()] = array();

                $arrSearchReportByPlugin[$search->getJobSiteKey()][$search->getKey()] = cloneArray($search->toArray(), array(
                    'keywords_string_for_url',
                    'base_url_format',
                    'keywords_array_tokenized',
                    'user_setting_flags',
                    'search_start_url',
                    'location_set_key',
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
    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Setting " . $excludedSite . " as excluded for this run.", \C__DISPLAY_ITEM_DETAIL__);

    if(array_key_exists($excludedSite, $GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'])) {
        foreach($GLOBALS['JOBSITES_AND_SEARCHES_TO_RUN'][$excludedSite] as $search)
        {
            $search->setRunResult("excluded");
            $search->save();
        }
    }

    LogLine($excludedSite . " excluded, so skipping its searches.", \C__DISPLAY_ITEM_START__);
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
    $strDateRange = null;
    $startDate = new DateTime();
    $strMod = "-".$GLOBALS['USERDATA']['configuration_settings']['number_days']." days";
    $startDate = $startDate->modify($strMod);
    $today = new DateTime();
    if($startDate->format('Y-m-d') != $today->format('Y-m-d'))
    {
        $strDateRange = $startDate->format('D, M d') . " - " . $today->format('D, M d');
    }
    else
    {
        $strDateRange = $today->format('D, M d');
    }
    return $strDateRange;
}


function getEmptyJobListingRecord()
{
    return array(
        'job_site' => '',
        'job_id' => '',
        'company' => '',
        'job_title' => '',
        'job_post_url' => '',
        'location' => '',
        'job_site_category' => '',
        'job_site_date' =>'',
        'employment_type' => '',
    );
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


function array_copy( array $array ) {
    $result = array();
    foreach( $array as $key => $val ) {
        if( is_array( $val ) ) {
            $result[$key] = array_copy( $val );
        } elseif ( is_object( $val ) ) {
            $result[$key] = clone $val;
        } else {
            $result[$key] = $val;
        }
    }
    return $result;
}

function intceil($number)
{
    if(is_string($number)) $number = floatval($number);

    $ret = ( is_numeric($number) ) ? ceil($number) : false;
    if ($ret != false) $ret = intval($ret);

    return $ret;
}

function substr_count_array( $haystack, $needle ) {
    $count = 0;
    if(!is_array($needle))
    {
        $needle = array($needle);
    }
    foreach ($needle as $substring) {
        $count += substr_count( $haystack, $substring);
    }
    return $count;
}



function is_array_multidimensional($a)
{
    if(!is_array($a)) return false;
    foreach($a as $v) if(is_array($v)) return TRUE;
    return FALSE;
}

function my_merge_add_new_keys( $arr1, $arr2 )
{
    // check if inputs are really arrays
    if (!is_array($arr1) || !is_array($arr2)) {
        throw new \Exception("Argument is not an array (in function my_merge_add_new_keys.)");
    }
    $strFunc = "my_merge_add_new_keys(arr1(size=".count($arr1)."),arr2(size=".count($arr2)."))";
    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine($strFunc, C__DISPLAY_FUNCTION__, true);
    $arr1Keys = array_keys($arr1);
    $arr2Keys = array_keys($arr2);
    $arrCombinedKeys = array_merge_recursive($arr1Keys, $arr2Keys);

    $arrNewBlankCombinedRecord = array_fill_keys($arrCombinedKeys, 'unknown');

    $arrMerged =  array_replace( $arrNewBlankCombinedRecord, $arr1 );
    $arrMerged =  array_replace( $arrMerged, $arr2 );

    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine('returning from ' . $strFunc, C__DISPLAY_FUNCTION__, true);
    return $arrMerged;
}
