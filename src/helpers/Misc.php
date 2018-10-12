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
 * @param $v
 *
 * @return bool
 */
function is_empty_value($v)
{
    if (!isset($v)) {
        return true;
    }

    $type = gettype($v);

    switch ($type) {

        case 'integer':
        case 'double':
        case 'float':
            return $v === null;

        case 'string':
        case 'unicode':
            $v = trim($v);
            return ($v === null || '' === $v);

        case 'array':
            return $v === null || (is_array($v) && (count($v) === 0 || \count(array_keys($v)) === 0));

        case 'boolean':
            return $v !== null && $v !== false;

        case 'object':
        default:
            return empty($v);

    }
}

function parse_query_string($str)
{
	$args = [];
	if(!is_empty_value($str) && is_string($str))
	{
		$items  = explode('&', $str);
		if(!is_empty_value($items) && is_array($items)) {
			foreach($items as $i) {
				$idecoded = urldecode($i);
				$parts = explode('=', $idecoded);
				if(!is_empty_value($parts) && \count($parts) > 1) {
					$args[$parts[0]] = $parts[1];
				}
			}
		}
	}
	
	return $args;
}
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
    if (empty($flagToCheck) || empty($flagSettings)) {
        return false;
    }

    return ($flagSettings & $flagToCheck);
}

/**
 * @return string
 */
function getPhpMemoryUsage()
{
    $size = memory_get_usage(false);

    $unit = array(' bytes', 'KB', 'MB', 'GB', 'TB', 'PN');

    return @round($size / (1024 ** ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
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
    $debugJSONFile = generateOutputFilePath('debug', $basefile, $ext='json');
    file_put_contents($debugJSONFile, $jsonSelf);

    return $debugJSONFile;
}

/**
 * @param int|null $configNumDays
 *
 * @return string
 */
function getRunDateRange($configNumDays=null)
{
    if (is_empty_value($configNumDays)) {
        $configNumDays = \JobScooper\Utils\Settings::getValue('number_days');
    }
    
    $num_days = filter_var($configNumDays, FILTER_VALIDATE_INT);
    if ($num_days === false) {
        $num_days = 1;
    }

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
function combineTextAllChildren($node)
{
	if(is_empty_value($node)) {
		return null;
	}
	
    if (is_array($node) && \count($node) > 1) {
        $strError = sprintf("Warning:  " . \count($node) . " DOM nodes were sent to combineTextAllChildren instead of a single starting node.  Using first node only.");
        LogWarning($strError);
    }

    if (is_array($node) && \count($node) >= 1) {
        $node = $node[0];
    }
    
    if (null === $node) {
        return null;
    }

    //
    // By luck, the DiDomElement returns the textContent from all child elements
    // underneath it already so we can shortcut this to just return that.
    //
    try {
        return $node->text;
    } catch (Exception $ex) {
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

    if (!empty($nodes)) {
        foreach ($nodes as $node) {
            $nodeKey = $node->getNode()->getNodePath();
            if ($node->hasChildren()) {
                $arrNodeStrings[$nodeKey] = combineTextAllChildren($node);
            } else {
                $arrNodeStrings[$nodeKey] = $node->text();
            }
        }

        $retStr = implode($delim, $arrNodeStrings);
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
    if (stristr($cmd, "2>&1") !== true) {
        $cmd = "{$cmd} 2>&1";
    }

    $cmdArrOutput = array();
    $cmdRet = null;
    $lastResultLine = null;
    $lastOutput = exec($cmd, $cmdArrOutput, $cmdRet);
    $cmdStrOutput = implode(PHP_EOL, $cmdArrOutput);

    foreach ($cmdArrOutput as $resultLine) {
        LogMessage($resultLine);
        $lastResultLine = $resultLine;
    }

    if (empty($lastOutput)) {
        $lastOutput = $lastResultLine;
    }

    LogMessage("Command '{$cmd}' returned code={$cmdRet}");
 
    if ($cmdRet !== 0) {
        throw new Exception("Command '{$cmd}' returned non-zero result code.  Output: " . cleanupTextValue($cmdStrOutput));
    }

    return $cmdRet;
}



if (! function_exists('glob_recursive')) {
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

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }
}



function swapDoubleSingleQuotes($var, $changeDoubles=true)
{
	if($changeDoubles) {
		return preg_replace('/"/',"'", $var);
	}
	else
	{
		return preg_replace("/'/",'"', $var);
	}
}