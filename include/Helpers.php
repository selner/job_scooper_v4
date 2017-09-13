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
require_once dirname(dirname(__FILE__))."/bootstrap.php";




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
        $retStr = \Scooper\strScrub($node->plaintext . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE);
        foreach ($node->childNodes() as $child) {
            $retStr = $retStr . " " . combineTextAllChildren($child, true);
        }
        unset($child);

    } elseif (isset($node->plaintext) && $fRecursed == false) {
        $retStr = \Scooper\strScrub($node->plaintext . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE);
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

            $retStr = $retStr . \Scooper\strScrub($node->plaintext . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE);
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
        if (!is_null($GLOBALS['logger'])) $GLOBALS['logger']->logLine($resultLine, \Scooper\C__DISPLAY_ITEM_DETAIL__);
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
        } elseif (is_array($val) && !(\Scooper\is_array_multidimensional($val))) {
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
    $retDetails = \Scooper\array_copy($source);

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
    LogLine("Tokenizing title exclusion matches from " . $inputfile . ".", \Scooper\C__DISPLAY_ITEM_DETAIL__);
    if (!$outputFile)
        $outputFile = $GLOBALS['USERDATA']['directories']['debug'] . "/tempCallTokenizer.csv";
    $PYTHONPATH = realpath(__DIR__ . "/../python/pyJobNormalizer/");
    $cmd = "python " . $PYTHONPATH . "/normalizeStrings.py -i " . $inputfile . " -o " . $outputFile . " -k " . $keyname;
    if ($indexKeyName != null)
        $cmd .= " --index " . $indexKeyName;
    LogLine("Running command: " . $cmd, \Scooper\C__DISPLAY_ITEM_DETAIL__);

    doExec($cmd);

    LogLine("Loading tokens for " . $inputfile . ".", \Scooper\C__DISPLAY_ITEM_DETAIL__);
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
    $valInterimFiles = \Scooper\get_PharseOptionValue('output_interim_files');

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
        $count = \Scooper\substr_count_array($subject, $pattern);
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


function isValueURLEncoded($str)
{
    if (strlen($str) <= 0) return 0;
    return (\Scooper\substr_count_array($str, array("%22", "&", "=", "+", "-", "%7C", "%3C")) > 0);
}


function readJobsListDataFromLocalJsonFile($fileKey, $returnFailedSearches = true, $dirKey = null)
{

    assert(!is_null($dirKey));

    $fileKey = str_replace(" ", "", $fileKey);
    $resultsFile = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories'][$dirKey], strtolower($fileKey)));
    if (stripos($fileKey, ".json") === false)
        $resultsFile = $resultsFile . "-" . strtolower(getTodayAsString("")) . ".json";

    return readJobsListDataFromLocalFile($resultsFile, $returnFailedSearches);
}

function readJobsListDataFromLocalFile($filepath, $returnFailedSearches = true)
{

    if (stripos($filepath, ".json") === false)
        $filepath = $filepath . "-" . strtolower(getTodayAsString("")) . ".json";

        if (is_file($filepath)) {
            LogLine("Reading json data from file " . $filepath, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            $data = loadJSON($filepath);
//            $jsonText = file_get_contents($filepath, FILE_TEXT);
//
//            $data = json_decode($jsonText, $assoc = true, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);

            if ($returnFailedSearches === false) {
                if ($data['search']['   search_run_result']['success'] !== true) {
                    LogLine("Ignoring incomplete search results found in file with key " . $filepath);
                    $data = null;
                }
        }


        return $data;
    }

    return array();
}

function getPhpMemoryUsage()
{
    $size = memory_get_usage(true);

    $unit = array(' bytes', 'KB', 'MB', 'GB', 'TB', 'PN');

    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}

function getFailedSearchesByPlugin()
{
    if (!array_key_exists('search_results', $GLOBALS['USERDATA']) || is_null($GLOBALS['USERDATA']['search_results']))
        return null;

    $arrFailedSearches = array_filter($GLOBALS['USERDATA']['search_results'], function ($k) {
        return ($k->getSearchRunResult()['success'] !== true);
    });

    if (is_null($arrFailedSearches) || !is_array($arrFailedSearches))
        return null;

    $arrFailedPluginsReport = array();
    foreach ($arrFailedSearches as $search) {
        if (!is_null($search->getSearchRunResult()['success'])) {
            if (!array_key_exists($search->getJobSite(), $arrFailedPluginsReport))
                $arrFailedPluginsReport[$search->getJobSite()]= array();

            $arrFailedPluginsReport[$search->getJobSite()][$search->getKey()] = cloneArray($search, array(
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
    unset($search);

    return $arrFailedPluginsReport;
}
function getFailedSearches()
{
    if (!array_key_exists('search_results', $GLOBALS['USERDATA']) || is_null($GLOBALS['USERDATA']['search_results']))
        return null;

    $arrFailedSearches = array_filter($GLOBALS['USERDATA']['search_results'], function ($k) {
        return ($k['search_run_result']['success'] !== true);
    });

    if (is_null($arrFailedSearches) || !is_array($arrFailedSearches))
        return null;

    $arrFailedPluginsReport = array();
    foreach ($arrFailedSearches as $search) {
        if (!is_null($search['search_run_result']['success'])) {

            $arrFailedPluginsReport[$search['key']] = $search;
        }
    }

    return $arrFailedPluginsReport;
}

function noJobStringMatch($var, $matchString)
{
    if(is_null($matchString) || strlen($matchString) == 0)
        throw new Exception("Invalid match string passed to helper noJobStringMatch.");
        
    if(stristr(strtoupper($var), strtoupper($matchString)) !== false)
        return 0;

    return null;
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


