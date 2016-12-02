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


/*
	For explanation and usage, see:

    Based on orignal JG_Cache
    @source https://github.com/diogeneshamilton/JG_Cache
	For explanation and usage of JG_Cache, see:
	http://www.jongales.com/blog/2009/02/18/simple-file-based-php-cache-class/

    @source https://github.com/diogeneshamilton/JG_Cache
    JG_Cache2 added the ability to have a human-readable cache subdirectory for cached files
*/

class JG_Cache2 extends JG_Cache {
    function __construct($dir, $subdir = "")
    {

        $cachedir = join(DIRECTORY_SEPARATOR, array($dir, strtolower(getTodayAsString()), strtolower($subdir)));
        $cachedir = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $cachedir);

//        if (isset($subdir) && count($subdir) > 0)
//            $dir = $dir . strtolower($subdir);
//
//        $dir = $dir . strtolower(getTodayAsString());
//

        if ( !file_exists($cachedir))
        {
            mkdir($cachedir, $mode = 0777, $recursive = true);
        }

        parent::__construct($cachedir);
    }
};



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
    $al = $a["updated_today"];
    $bl = $b["updated_today"];
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

    $ret = getMergedJobRecord($prevJobRecord, $jobRecordChanges);
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
        updateJobColumn($mergedJob, $newerJobRecord, 'status', false);
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
        $outputFile = $GLOBALS['USERDATA']['directories']['staging'] . "tempCallTokenizer.csv";
    $PYTHONPATH = realpath(__DIR__ ."/../python/pyJobNormalizer/");
    $cmd = "python " . $PYTHONPATH . "/normalizeStrings.py -i " . $inputfile . " -o " . $outputFile . " -k " . $keyname;
    if ($indexKeyName != null)
        $cmd .= " --index " . $indexKeyName;
    $GLOBALS['logger']->logLine("Running command: " . $cmd   , \Scooper\C__DISPLAY_ITEM_DETAIL__);

    $cmdOutput = array();
    $cmdRet = "";
    exec($cmd, $cmdOutput, $cmdRet);
    foreach($cmdOutput as $resultLine)
        $GLOBALS['logger']->logLine($resultLine, \Scooper\C__DISPLAY_ITEM_DETAIL__);

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
    $inputFile = $GLOBALS['USERDATA']['directories']['staging'] . "tmp-".$tempFileKey."-token-input.csv";
    $outputFile = $GLOBALS['USERDATA']['directories']['staging']. "tmp-".$tempFileKey."-token-output.csv";

    $headers = array($dataKeyName);
    if(array_key_exists($dataKeyName, $arrData)) $headers = array_keys($arrData);

    if(is_file($inputFile))
    {
        unlink($inputFile);
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

    $ret = callTokenizer($inputFile, $outputFile, $dataKeyName, $indexKeyName);
    $valInterimFiles = \Scooper\get_PharseOptionValue('output_interim_files');

    if(isset($valInterimFiles) && $valInterimFiles != true)
    {
        unlink($inputFile);
        unlink($outputFile);
    }


    return $ret;
}


function tokenizeMultiDimensionArray($arrData, $tempFileKey, $dataKeyName, $indexKeyName = null)
{
    $inputFile = $GLOBALS['USERDATA']['directories']['staging'] . "tmp-".$tempFileKey."-token-input.csv";
    $outputFile = $GLOBALS['USERDATA']['directories']['staging'] . "tmp-".$tempFileKey."-token-output.csv";

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
    $keywordset = array_column($arrKeywordTokens, "tokenized");
    $retKeywords = array();
    foreach (array_keys($keywordset) as $setKey)
    {
        $retKeywords[$setKey] = str_replace("|", " ", $keywordset[$setKey]);
    }
    return $retKeywords;
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


function getPhpMemoryUsage()
{
    $size = memory_get_usage(true);

    $unit=array(' bytes','KB','MB','GB','TB','PN');

    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

?>