<?php
/**
 * Copyright 2014-16 Bryan Selner
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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }

require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/include/ClassJobsSiteCommon.php');

const C__STR_TAG_AUTOMARKEDJOB__ = "[auto-marked]";
const C__STR_TAG_DUPLICATE_POST__ = "No (Duplicate Job Post?)";
const C__STR_TAG_BAD_TITLE_POST__ = "No (Bad Title & Role)";
const C__STR_TAG_NOT_A_KEYWORD_TITLE_MATCH__ = "No (Not a Keyword Title Match)";
const C__STR_TAG_NOT_EXACT_TITLE_MATCH__ = "No (Not an Exact Title Match)";

function clean_utf8($string, $control = true)
{
    $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);

    if ($control === true)
    {
        return preg_replace('~\p{C}+~u', '', $string);
    }

    return preg_replace(array('~\r\n?~', '~[^\P{C}\t\n]+~u'), array("\n", ''), $string);
}

function getDateForDaysAgo($strDaysAgo)
{
    $retDate = null;

    if(!isset($strDaysAgo) || strlen($strDaysAgo) <= 0) return $retDate;

    if(is_numeric($strDaysAgo) )
    {
        $daysToSubtract = $strDaysAgo;
    }
    else
    {
        $strDaysAgo = \Scooper\strScrub($strDaysAgo, SIMPLE_TEXT_CLEANUP | LOWERCASE);
        if(strcasecmp($strDaysAgo, "yesterday") == 0)
        {
            $daysToSubtract = 1;
        }
        elseif(strcasecmp($strDaysAgo, "today") == 0)
        {
            $daysToSubtract = 0;
        }
        else
        {
            $daysToSubtract = null;
        }

    }

    if(isset($daysToSubtract))
    {
        $date = new DateTime();
        $date->modify("-".$daysToSubtract." days");
        $retDate = $date->format('Y-m-d');
    }

    return $retDate;
}

function combineTextAllChildren($node, $fRecursed = false)
{

    $retStr = "";
    if($node->hasChildNodes())
    {
        $retStr = \Scooper\strScrub($node->plaintext . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE  );
        foreach($node->childNodes() as $child)
        {
            $retStr = $retStr . " " . combineTextAllChildren($child, true);
        }
    }
    elseif(isset($node->plaintext) && $fRecursed == false)
    {
        $retStr = \Scooper\strScrub($node->plaintext . " " . $retStr, HTML_DECODE | REMOVE_EXTRA_WHITESPACE  );
    }

    return $retStr;

}

function sortByCountDesc($a, $b)
{
    $al = $a["total_listings"];
    $bl = $b["total_listings"];
    if ($al == $bl) {
        return 0;
    }
    return ($al < $bl) ? +1 : -1;
}

/**
 * TODO:  DOC
 *
 *
 * @param  string TODO DOC
 * @param  string TODO DOC
 * @return string TODO DOC
 */

function getArrayKeyValueForJob($job)
{

    return $job['key_jobsite_siteid'];

/*
    $strKey = \Scooper\strScrub($job['job_site'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS);

    // For craigslist, they change IDs on every post, so deduping that way doesn't help
    // much.  Instead, let's dedupe for Craigslist by using the role title and the jobsite
    // (Company doesn't usually get filled out either with them.)
    if(strcasecmp($strKey, "craigslist") == 0)
    {
        $strKey = $strKey . "-" . \Scooper\strScrub($job['job_title'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS );
    }
    if($job['job_id'] != null && $job['job_id'] != "")
    {
        $strKey = $strKey . "-" . \Scooper\strScrub($job['job_id'], REPLACE_SPACES_WITH_HYPHENS | REMOVE_PUNCT | HTML_DECODE | LOWERCASE);
    }
    else
    {
        $strKey = $strKey . "-" . \Scooper\strScrub($job['company'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS );
        $strKey = $strKey . "-" . \Scooper\strScrub($job['job_title'], DEFAULT_SCRUB | REMOVE_PUNCT | REPLACE_SPACES_WITH_HYPHENS );
    }
    return $strKey;
*/


}

function doExec($cmd)
{
    $cmdOutput = array();
    $cmdRet = "";

    exec($cmd, $cmdOutput, $cmdRet);
    foreach($cmdOutput as $resultLine)
        if(!is_null($GLOBALS['logger'])) $GLOBALS['logger']->logLine($resultLine, \Scooper\C__DISPLAY_ITEM_DETAIL__);
    if(is_array($cmdOutput) && count($cmdOutput) == 1)
        return $cmdOutput[0];
    return $cmdOutput;
}

function countAssociativeArrayValues($arrToCount)
{
    if($arrToCount == null || !is_array($arrToCount))
    {
        return 0;
    }

    $count = 0;
    foreach($arrToCount as $item)
    {
        $count = $count + 1;
    }

    $arrValues = array_values($arrToCount);
    $nValues = count($arrValues);
    return max($nValues, $count);
//    $arrKeys = array_keys($arrToCount);
//    $nKeys = count($arrKeys);

//    return max($nKeys, $nValues);
}

function countJobRecords($arrJobs)
{
    return countAssociativeArrayValues($arrJobs);
}


function addJobsToJobsList(&$arrJobsListToUpdate, $arrAddJobs)
{
    if($arrAddJobs == null) return;

    if(!is_array($arrAddJobs) || count($arrAddJobs) == 0)
    {
        // skip. no jobs to add
        return;
    }
    if($arrJobsListToUpdate == null) $arrJobsListToUpdate = array();

    foreach($arrAddJobs as $jobRecord)
    {
        addJobToJobsList($arrJobsListToUpdate, $jobRecord);
    }

}


function addJobToJobsList(&$arrJobsListToUpdate, $job)
{
    if($arrJobsListToUpdate == null) $arrJobsListToUpdate = array();

    $jobToAdd = \Scooper\array_copy($job);



    if(isset($arrJobsListToUpdate[$job['key_jobsite_siteid']]))
    {
        $jobToAdd = getMergedJobRecord($arrJobsListToUpdate[$job['key_jobsite_siteid']], $job);
    }


    $arrJobsListToUpdate[$job['key_jobsite_siteid']] = $jobToAdd;
}



function updateJobColumn(&$job, $newJob, $strColumn, $fAllowEmptyValueOverwrite = false)
{
    $prevJob = \Scooper\array_copy($job);

    if(strlen($job[$strColumn]) == 0)
    {
        $job[$strColumn] = $newJob[$strColumn];
    }
    elseif(strlen($newJob[$strColumn]) == 0)
    {
        if($fAllowEmptyValueOverwrite == true)
        {
            $job[$strColumn] = $newJob[$strColumn];
            $job['match_notes'] .= $strColumn . " value '" . $prevJob[$strColumn]."' removed.'".PHP_EOL;
        }
    }
    else
    {
        if(strcasecmp(\Scooper\strScrub($job[$strColumn]), \Scooper\strScrub($newJob[$strColumn])) != 0)
        {
            $job[$strColumn] = $newJob[$strColumn];
            $job['match_notes'] .= PHP_EOL.$strColumn . ": old[" . $prevJob[$strColumn]."], new[" .$job[$strColumn]."]".PHP_EOL;
        }
    }

}

function appendJobColumnData(&$job, $strColumn, $delim, $newData)
{
    if(is_string($job[$strColumn]) && strlen($job[$strColumn]) > 0)
    {
        $job[$strColumn] .= $delim . " ";
    }
    $job[$strColumn] .= $newData;

}

function getArrayItemDetailsAsString($arrItem, $key, $fIsFirstItem = true, $strDelimiter = "", $strIntro = "", $fIncludeKey = true)
{
    $strReturn = "";

    if(isset($arrItem[$key]))
    {
        $val = $arrItem[$key];
        if(is_string($val) && strlen($val) > 0)
        {
            $strVal = $val;
        }
        elseif(is_array($val) && !(\Scooper\is_array_multidimensional($val)))
        {
            $strVal = join(" | ", $val);
        }
        else
        {
            $strVal = var_export($val, true);
        }

        if($fIsFirstItem == true)
        {
            $strReturn = $strIntro;
        }
        else
        {
            $strReturn .= $strDelimiter;
        }
        if($fIncludeKey == true) {
            $strReturn .= $key . '=['.$strVal.']';
        } else {
            $strReturn .= $strVal;
        }

    }


    return $strReturn;
}

function cloneArray($source, $arrDontCopyTheseKeys = array())
{
    $retDetails = \Scooper\array_copy($source);

    foreach($arrDontCopyTheseKeys as $key)
    {
        unset($retDetails[$key]);
    }

    return $retDetails;
}


function array_mapk($callback, $array) {
    $newArray = array();
    foreach ($array as $k => $v) {
        $newArray[$k] = call_user_func($callback, $k, $v);
    }
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

    if(isset($arrDetails) && is_array($arrDetails))
    {
        foreach(array_keys($arrDetails) as $key)
        {
            $strReturn .= getArrayItemDetailsAsString($arrDetails, $key, (strlen($strReturn) <= 0), $strDelimiter, $strIntro, $fIncludeKey);
        }
    }

    return $strReturn;
}

function updateJobRecord($prevJobRecord, $jobRecordChanges)
{

    return getMergedJobRecord($prevJobRecord, $jobRecordChanges);
}

function getMergedJobRecord($prevJobRecord, $newerJobRecord)
{
    if($prevJobRecord['key_jobsite_siteid'] == $newerJobRecord['key_jobsite_siteid'])
    {
        return $prevJobRecord; // don't update yourself.
    }

    // Since we already had a job record for this particular listing,
    // we'll merge the new info into the old one.  For most fields,
    // the latter (aka the passed in $job) values will win.  For some
    // fields such as Notes, the values will be combined.
    //
    $mergedJob = \Scooper\array_copy($prevJobRecord);

    updateJobColumn($mergedJob, $newerJobRecord, 'company', false);
    updateJobColumn($mergedJob, $newerJobRecord, 'job_title', false);
//    updateJobColumn($mergedJob, $newerJobRecord, 'location', false);
//    updateJobColumn($mergedJob, $newerJobRecord, 'job_site_category', false);
//    updateJobColumn($mergedJob, $newerJobRecord, 'date_pulled', false);
//    updateJobColumn($mergedJob, $newerJobRecord, 'job_post_url', false);
//    updateJobColumn($mergedJob, $newerJobRecord, 'job_site_date', false);

    if(!isMarked_InterestedOrBlank($prevJobRecord))
    {
        updateJobColumn($mergedJob, $newerJobRecord, 'interested', false);
//        updateJobColumn($mergedJob, $newerJobRecord, 'status', false);
    }


    $mergedJob['match_notes'] = $newerJobRecord['match_notes'] . ' ' . $mergedJob['match_notes'];
    $mergedJob['date_last_updated'] = getTodayAsString();

    return $mergedJob;

}

function loadCSV($filename, $indexKeyName = null)
{
    if(!is_file($filename))
    {
        throw new Exception("Specified input file '" . $filename . "' was not found.  Aborting.");
    }

    $file = fopen($filename,"r");
    if(is_bool($file))
    {
        throw new Exception("Specified input file '" . $filename . "' could not be opened.  Aborting.");
    }

    $headers = fgetcsv($file);
    $arrLoadedList = array();
    while (!feof($file) ) {
        $rowData = fgetcsv($file);
        if($rowData === false)
        {
            break;
        }
        else
        {
            $arrRec = array_combine($headers, $rowData);
            if($indexKeyName != null)
                $arrLoadedList[$arrRec[$indexKeyName]] = $arrRec;
            else
                $arrLoadedList[] = $arrRec;
        }
    }

    fclose($file);

    return $arrLoadedList;

}
function callTokenizer($inputfile, $outputFile, $keyname, $indexKeyName = null)
{
    $GLOBALS['logger']->logLine("Tokenizing title exclusion matches from ".$inputfile."." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
    if(!$outputFile)
        $outputFile = $GLOBALS['USERDATA']['directories']['stage2'] . "/tempCallTokenizer.csv";
    $PYTHONPATH = realpath(__DIR__ ."/../python/pyJobNormalizer/");
    $cmd = "python " . $PYTHONPATH . "/normalizeStrings.py -i " . $inputfile . " -o " . $outputFile . " -k " . $keyname;
    if ($indexKeyName != null)
        $cmd .= " --index " . $indexKeyName;
    $GLOBALS['logger']->logLine("Running command: " . $cmd   , \Scooper\C__DISPLAY_ITEM_DETAIL__);

    doExec($cmd);

    $GLOBALS['logger']->logLine("Loading tokens for ".$inputfile."." , \Scooper\C__DISPLAY_ITEM_DETAIL__);
    $file = fopen($outputFile,"r");
    if(is_bool($file))
    {
        throw new Exception("Specified input file '" . $outputFile . "' could not be opened.  Aborting.");
    }

    $headers = fgetcsv($file);
    $arrTokenizedList = array();
    while (!feof($file) ) {
        $rowData = fgetcsv($file);
        if($rowData === false)
        {
            break;
        }
        else
        {
            $arrRec = array_combine($headers, $rowData);
            if($indexKeyName != null)
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
    $inputFile = $GLOBALS['USERDATA']['directories']['stage2'] . "/tmp-".$tempFileKey."-token-input.csv";
    $outputFile = $GLOBALS['USERDATA']['directories']['stage2']. "/tmp-".$tempFileKey."-token-output.csv";

    $headers = array($dataKeyName);
    if(array_key_exists($dataKeyName, $arrData)) $headers = array_keys($arrData);

    if(is_file($inputFile))
    {
        unlink($inputFile);
    }

    if(is_file($outputFile))
    {
        unlink($outputFile);
    }

    $file = fopen($inputFile,"w");
    fputcsv($file, $headers);

    foreach ($arrData as $line)
    {
        if(is_string($line))
            $line = explode(',', $line);
        fputcsv($file, $line);
    }

    fclose($file);

    $tmpTokenizedWords = callTokenizer($inputFile, $outputFile, $dataKeyName, $indexKeyName);
    $valInterimFiles = \Scooper\get_PharseOptionValue('output_interim_files');

    if(isset($valInterimFiles) && $valInterimFiles != true)
    {
        unlink($inputFile);
        unlink($outputFile);
    }


    return $tmpTokenizedWords;
}


function tokenizeMultiDimensionArray($arrData, $tempFileKey, $dataKeyName, $indexKeyName = null)
{
    $inputFile = $GLOBALS['USERDATA']['directories']['stage2'] . "/tmp-".$tempFileKey."-token-input.csv";
    $outputFile = $GLOBALS['USERDATA']['directories']['stage2'] . "/tmp-".$tempFileKey."-token-output.csv";

    if(is_file($inputFile))
    {
        unlink($inputFile);
    }

    $file = fopen($inputFile,"w");
//    fputs($file, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

    fputcsv($file, array_keys(array_shift(\Scooper\array_copy($arrData))));

    foreach ($arrData as $rec)
    {
        $line = join('`', $rec);

        $outline = preg_replace("/\\x00/", "", utf8_encode($line));
        $outRec = explode('`', $outline);
        fputcsv($file, $outRec, ',', '"');
    }

    fclose($file);

    $ret = callTokenizer($inputFile, $outputFile, $dataKeyName, $indexKeyName);
    if(isset($valInterimFiles) && $valInterimFiles != true)
    {
        unlink($inputFile);
        unlink($outputFile);
    }

    return $ret;
}



function tokenizeKeywords($arrKeywords)
{
    if (!is_array($arrKeywords))
    {
        throw new Exception("Invalid keywords object type.");
    }

    $arrKeywordTokens = tokenizeSingleDimensionArray($arrKeywords, "srchkwd", "keywords", "keywords");
    $arrReturnKeywordTokens = array_fill_keys(array_keys($arrKeywordTokens), null);
    foreach(array_keys($arrReturnKeywordTokens) as $key)
    {
        $arrReturnKeywordTokens[$key] = str_replace("|", " ", $arrKeywordTokens[$key]['tokenized']);
    }
    return $arrReturnKeywordTokens;
//    $keywordset = array_column($arrKeywordTokens, "tokenized");
//    $retKeywords = array();
//    foreach (array_keys($keywordset) as $setKey)
//    {
//        $retKeywords[$setKey] = str_replace("|", " ", $keywordset[$setKey]);
//    }
//    return $retKeywords;
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
function preg_match_multiple(array $patterns = array(), $subject = null, &$findings = array(), $flags = false, &$errors = array())
{
    foreach ($patterns as $name => $pattern) {
        $found = false;
        if (1 <= preg_match_all($pattern, $subject, $found, $flags)) {
            $findings[$name] = $found;
        } else {
            if (PREG_NO_ERROR !== ($code = preg_last_error() )) {
                $errors[$name] = $code;
            }
            else
            {
                // No match was found, so don't return it in the findings
                // $findings[$name] = array();
            }
        }
    }
    return (0 === sizeof($errors));
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

            if($boolMustMatchAllKeywords == true)
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

function getTodayAsString($delim="-")
{
    $fmt = "Y" . $delim ."m" . $delim . "d";
    return date($fmt);
}


function getDefaultJobsOutputFileName($strFilePrefix = '', $strBase = '', $strExt = '', $delim="")
{
    $strFilename = '';
    if(strlen($strFilePrefix) > 0) $strFilename .= $strFilePrefix . "-";
    $date=date_create(null);
    $fmt = "Y" . $delim ."m" . $delim . "d" . "Hi";

    $strFilename .= date_format($date, $fmt);

    if(strlen($strBase) > 0) $strFilename .= "-" . $strBase;
    if(strlen($strExt) > 0) $strFilename .= "." . $strExt;

    return $strFilename;
}

function isValueURLEncoded($str)
{
    if(strlen($str) <= 0) return 0;
    return (\Scooper\substr_count_array($str, array("%22", "&", "=", "+", "-", "%7C", "%3C" )) >0 );
}



const STAGE1_PATHKEY = "stage1-rawlistings/";
const STAGE2_PATHKEY = "stage2-rawlistings/";
const STAGE3_PATHKEY = "stage3-automarkedlistings/";
const STAGE4_PATHKEY = "stage4-notifyuser/";
const STAGE_FLAG_STAGEONLY = 0x0;
const STAGE_FLAG_INCLUDEUSER = 0x1;
const STAGE_FLAG_INCLUDEDATE = 0x2;

function addDelimIfNeeded($currString, $delim, $strToAdd)
{
    $result = $currString;
    if(strlen($currString) > 0)
        $result = $result . $delim;

    $result = $result . $strToAdd;
    return $result;
}
function getStageKeyPrefix($stageNumber, $fileFlags = STAGE_FLAG_STAGEONLY, $delim="")
{
    $prefix =  "";

    if(($fileFlags& STAGE_FLAG_INCLUDEUSER) == true)
        $prefix = addDelimIfNeeded($prefix , $delim, $GLOBALS['USERDATA']['user_unique_key']);

    if(($fileFlags & STAGE_FLAG_INCLUDEDATE) == true)
        $prefix = addDelimIfNeeded($prefix , $delim, \Scooper\getTodayAsString(""));

    $rootPrefix = $GLOBALS['USERDATA']['user_unique_key'] . "/";
    switch($stageNumber)
    {
        case 1:
            $prefix = STAGE1_PATHKEY . $prefix;
            break;
        case 2:
            $prefix = STAGE2_PATHKEY . $prefix;
            break;
        case 3:
            $prefix = STAGE3_PATHKEY . $prefix;
            break;
        case 4:
            $prefix = STAGE4_PATHKEY . $prefix;
            break;
        default:
            throw new IndexOutOfBoundsException("Error: invalid stage number passed '" . $stageNumber. "'" );
            break;
    }

    return ($rootPrefix . $prefix);
}

function writeJobsListDataToLocalJSONFile($fileKey, $dataJobs, $listType, $stageNumber = null, $searchDetails = null)
{
    if(is_null($dataJobs))
        $dataJobs = array();

    if(is_null($stageNumber))
        $stageNumber = 1;

    $stageName = "stage" . $stageNumber;
    $fileKey = str_replace(" ", "", $fileKey);
    $resultsFile = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories'][$stageName], strtolower($fileKey) ));
    if(stripos($fileKey, ".json") === false)
        $resultsFile = $resultsFile . "-" . strtolower(getTodayAsString("")) . ".json";

    $data = array('key' => $fileKey, 'stage' => $stageNumber, 'listtype' => $listType, 'jobs_count' => countJobRecords($dataJobs), 'jobslist' => $dataJobs, 'search' => $searchDetails);

    $jobsJson = json_encode($data, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
    if($jobsJson === false)
    {
        $err = json_last_error_msg();
        $errMsg = "Error:  Unable to convert jobs list data to json due to error   " . $err;
        $GLOBALS['logger']->logLine($errMsg, \Scooper\C__DISPLAY_ERROR__);
        throw new Exception($errMsg);

    }

    $GLOBALS['logger']->logLine("Writing final job data pull results to json file " . $resultsFile);
    if(file_put_contents($resultsFile, $jobsJson, FILE_TEXT) === false)
    {
        $err = error_get_last();
        $errMsg = "Error:  Unable to save JSON results to file " . $resultsFile . " due to error   " . $err;
        $GLOBALS['logger']->logLine($errMsg, \Scooper\C__DISPLAY_ERROR__);
        throw new Exception($errMsg);

    }

    return $resultsFile;
}

function loadJSON($file)
{
    $GLOBALS['logger']->logLine("Reading json data from file " . $file);
    $jsonText = file_get_contents($file, FILE_TEXT);

    $data = json_decode($jsonText, $assoc=true, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);

    return $data;

}

function readJobsListDataFromLocalJsonFile($fileKey, $stageNumber, $returnFailedSearches=true)
{
    if(is_null($stageNumber))
        $stageNumber = 1;


    $stageName = "stage" . $stageNumber;
    $fileKey = str_replace(" ", "", $fileKey);
    $resultsFile = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['directories'][$stageName],  strtolower($fileKey) ));
    if(stripos($fileKey, ".json") === false)
        $resultsFile = $resultsFile . "-" . strtolower(getTodayAsString("")) . ".json";

    if(is_file($resultsFile))
    {
        $GLOBALS['logger']->logLine("Reading json data from file " . $resultsFile);
        $jsonText = file_get_contents($resultsFile, FILE_TEXT);

        $data = json_decode($jsonText, $assoc=true, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);

        if ($returnFailedSearches === false)
        {
           if($data['search']['search_run_result']['success'] !== true ) {
               $GLOBALS['logger']->logLine("Ignoring incomplete search results found in file with key " . $fileKey);
               $data = null;
           }
        }


        return $data;
    }

    return array();
}

function readJobsListFromLocalJsonFile($fileKey, $stageNumber=1, $returnFailedSearches=true)
{
    $retJobs = null;
    $data = readJobsListDataFromLocalJsonFile($fileKey, $stageNumber, $returnFailedSearches);

    if(!is_null($data) && is_array($data))
    {
        if (array_key_exists("jobslist", $data)) {
            $retJobs = array_filter($data['jobslist'], "isIncludedJobSite");
        }

        return $retJobs;
    }

    return null;
}


function getPhpMemoryUsage()
{
    $size = memory_get_usage(true);

    $unit=array(' bytes','KB','MB','GB','TB','PN');

    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

function getFailedSearchesByPlugin()
{
    if(!array_key_exists('search_results', $GLOBALS['USERDATA']) || is_null($GLOBALS['USERDATA']['search_results']))
        return null;

    $arrFailedSearches = array_filter($GLOBALS['USERDATA']['search_results'], function($k) {
        return ($k['search_run_result']['success'] !== true);
    });

    if (is_null($arrFailedSearches) || !is_array($arrFailedSearches))
        return null;

    $arrFailedPluginsReport = array();
    foreach($arrFailedSearches as $search) {
        if (!is_null($search['search_run_result']['success'])) {
            if(!array_key_exists($search['site_name'], $arrFailedPluginsReport))
                $arrFailedPluginsReport[$search['site_name']] = array();

            $arrFailedPluginsReport[$search['site_name']][$search['key']] = cloneArray($search, array(
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
    return $arrFailedPluginsReport;
}


?>