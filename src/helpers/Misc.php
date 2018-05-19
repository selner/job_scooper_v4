<?php
/**
 * Copyright 2014-18 Bryan Selner
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


/**
 * @param Object $obj
 *
 * @return array
 */
function object_to_array($obj)
{
    $arr = is_object($obj) ? get_object_vars($obj) : $obj;
    foreach ($arr as $key => $val) {
        $val = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
        $arr[$key] = $val;
    }

    return $arr;
}


/**
 * @param null $flagSettings
 * @param null $flagToCheck
 *
 * @return bool
 */
function isBitFlagSet($flagSettings = null, $flagToCheck= null)
{
	if(empty($flagToCheck) || empty($flagSettings)) {
		return false;
	}

    return ($flagSettings & $flagToCheck);
}

/**
 * @return string
 */
function getPhpMemoryUsage()
{
    $size = memory_get_usage(true);

    $unit = array(' bytes', 'KB', 'MB', 'GB', 'TB', 'PN');

    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}

/**
 * @param Object $obj
 * @param string $strBaseFileName
 *
 * @return string
 */
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

/**
 * @param string $var
 * @param string $matchString
 *
 * @return int|null
 * @throws \Exception
 */
function noJobStringMatch($var, $matchString)
{
    if(is_null($matchString) || strlen($matchString) == 0)
        throw new Exception("Invalid match string passed to helper noJobStringMatch.");

    if(stristr(strtoupper($var), strtoupper($matchString)) !== false)
        return 0;

    return null;
}


/**
 * @param int|null $configNumDays
 *
 * @return string
 */
function getRunDateRange($configNumDays=null)
{
	if(empty($configNumDays))
	    $configNumDays = getConfigurationSetting('number_days');
	
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

/**
 * @param        $node
 * @param bool   $fRecursed
 * @param string $delim
 * @param array  $arrChildStrings
 *
 * @return null|string
 */
function combineTextAllChildren($node, $fRecursed = false, $delim=" ", $arrChildStrings=[])
{

	if (empty($node))
		return null;

	if (is_array($node) && count($node) > 1) {
		$strError = sprintf("Warning:  " . count($node) . " DOM nodes were sent to combineTextAllChildren instead of a single starting node.  Using first node only.");
		LogWarning($strError);
	}

	if (is_array($node) && count($node) >= 1)
		$node = $node[0];

	$nodeKey = $node->getNode()->getNodePath();

	if ($fRecursed === true)
		LogDebug("Combining text for all child from node {$nodeKey}");
	else
		LogDebug("... processing text from {$nodeKey} and related elements...");

	//
	// By luck, the DiDomElement returns the textContent from all child elements
	// underneath it already so we can shortcut this to just return that.
	//
	try
	{
		return $node->text;
	}
	catch (Exception $ex)
	{
		// do nothing

	}
	return '';
}


/**
 * @param        $nodes
 * @param string $delim
 *
 * @return string
 */
function combineTextAllNodes($nodes, $delim=" ")
{
    $retStr = "";
    $arrNodeStrings = array();

	if(!empty($nodes))
	{
        foreach ($nodes as $node) {
	        $nodeKey = $node->getNode()->getNodePath();
	        if($node->hasChildren()) {
		        $arrNodeStrings[$nodeKey] = combineTextAllChildren($node, true, $delim, $arrNodeStrings);
	        }
	        else {
		        $arrNodeStrings[$nodeKey] = $node->text();
	        }
        }

        $retStr = join($delim, $arrNodeStrings);
    }
    return $retStr;

}


/**
 * @param $cmd
 *
 * @throws \Exception
 * @return null|string
 */
function doExec($cmd)
{
	if (stristr($cmd, "2>&1") !== true)
		$cmd = "{$cmd} 2>&1";

	$cmdArrOutput = array();
	$cmdRet = null;
	$lastResultLine = null;

	$lastOutput = exec($cmd, $cmdArrOutput, $cmdRet);
	$cmdStrOutput = join(PHP_EOL, $cmdArrOutput);
	if($cmdRet !== 0)
		throw new Exception("Command '{$cmd}' returned non-zero result code.  Output: {$cmdStrOutput}");

	foreach ($cmdArrOutput as $resultLine) {
		LogMessage($resultLine);
		$lastResultLine = $resultLine;
	}

	if (empty($lastOutput))
		$lastOutput = $lastResultLine;

	LogMessage("Command '{$cmd}' returned code={$cmdRet}; last_line='{$lastOutput}'.");


    return $lastOutput;
}



if ( ! function_exists('glob_recursive'))
{
	// Does not support flag GLOB_BRACE

	/**
	 * @param     $pattern
	 * @param int $flags
	 *
	 * @return array
	 */
	function glob_recursive($pattern, $flags = 0)
	{
		$files = glob($pattern, $flags);

		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
		{
			$files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
		}

		return $files;
	}
}
