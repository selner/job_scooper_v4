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

function getArrayDebugOutput($arr)
{
	try
	{
		$dbg = encodeJSON($arr);
	}
	catch( \Exception $ex)
	{
		$dbg = var_dump($arr, true);
	}
	return $dbg;
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



/**
 * routine to return -1 if there is no match for strpos
 *
 *
 * @author William Jaspers, IV <wjaspers4@gmail.com>
 * @created 2009-02-27 17:00:00 +6:00:00 GMT
 * @access public
 * @ref http://www.php.net/manual/en/function.preg-match.php#89252
 *
 * @param $haystack
 * @param $needle
 * @return bool|int
 */
function inStr($haystack, $needle)
{
    $pos=strpos($haystack, $needle);
    if ($pos !== false)
    {
        return $pos;
    }
    else
    {
        return -1;
    }
}
/**
 *
 * in_string_array that takes an array of values to match against a string.
 * note the stupid argument order (to match strpos).  Returns
 * true if all needles are found in haystack or false if not.
 *
 * @param $haystack
 * @param $needle
 * @return bool|int
 */
function in_string_array($haystack, $needle)
{
    if(!is_array($needle))
    {
        if(!is_string($needle))
            $needle = strval($needle);

        $needle = array($needle);
    }

    foreach($needle as $what) {
        if(($pos = strpos($haystack, $what))===false) return false;
    }
    return true;
}


function flattenWithKeys(array $array, $childPrefix = '.', $root = '', $result = array()) {
    //if(!is_array($array)) return $result;

    ### print_r(array(__LINE__, 'arr' => $array, 'prefix' => $childPrefix, 'root' => $root, 'result' => $result));

    foreach($array as $k => $v) {
        if(is_array($v) || is_object($v)) $result = flattenWithKeys( (array) $v, $childPrefix, $root . $k . $childPrefix, $result);
        else $result[ $root . $k ] = $v;
    }
    return $result;
}

/**
 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
 * keys to arrays rather than overwriting the value in the first array with the duplicate
 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
 * this happens (documented behavior):
 *
 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('org value', 'new value'));
 *
 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
 * Matching keys' values in the second array overwrite those in the first array, as is the
 * case with array_merge, i.e.:
 *
 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('new value'));
 *
 * Parameters are passed by reference, though only for performance reasons. They're not
 * altered by this function.
 *
 * @param array $array1
 * @param array $array2
 * @return array
 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
 */
function array_merge_recursive_distinct ( array &$array1, array &$array2 )
{
    $merged = $array1;

    foreach ( $array2 as $key => &$value )
    {
        if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
        {
            $merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value );
        }
        else
        {
            $merged [$key] = $value;
        }
    }

    return $merged;
}

function updateColumnsForCSVFlatArray(&$arr, \Propel\Runtime\Map\TableMap $tablemap)
{
    foreach(array_keys($arr) as $key) {
	    if ($tablemap->hasColumnByPhpName($key)) {
		    $col = $tablemap->getColumnByPhpName($key);
		    if ($col->getType() == "ARRAY" && !empty($arr[$key])) {
			    $arr[$key] = join("|", flattenWithKeys(array($key => $arr[$key])));
		    } elseif ($col->getType() == "TIMESTAMP" && !empty($arr[$key])) {
			    $date = DateTime::createFromFormat("Y-m-d\TH:i:sT", $arr[$key]);
			    if (!empty($date)) {
				    $arr[$key] = $date->format("Y-m-d");
			    }
		    }
	    }
    }
}

function array_subset(array $haystack, array $needle)
{
    return array_intersect_key($haystack, array_flip($needle));
}

function array_from_orm_object_list_by_array_keys(array $list, array $keysToReturn)
{
    return array_map(function ($v) use ($keysToReturn) {return array_subset($v->toArray(), $keysToReturn);} , $list);
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

            if ($boolMustMatchAllKeywords == true)
                return (sizeof($findings) === sizeof($patterns));

        } else {
            if (PREG_NO_ERROR !== ($code = preg_last_error() )) {
                $errors[$name] = $code;
            }
            else
            {
                // No match was found, so don't return it in the findings
             $findings[$name] = array();
            }
        }
    }
    return !(0 === sizeof($findings));
}

function getArrayItem($key, $arr)
{
    $ret = null;
    if(array_key_exists($key, $arr)) {
        if (is_numeric($arr[$key]))
            $ret = $arr[$key];
        else if (!empty($arr[$key]))
            $ret = $arr[$key];
    }
    return $ret;
}

function setArrayItem(&$destArray, $destKey, $sourceArray, $sourceKey)
{
    $ret = null;
    $val = getArrayItem($sourceKey, $sourceArray);
    if(is_numeric($val) || !empty($val))
    {
        $destArray[$destKey] = $val;
    }
}




function getEmptyJobListingRecord()
{
    return array(
        'JobSiteKey' => '',
        'JobSitePostId' => '',
        'Company' => '',
        'Title' => '',
        'Url' => '',
        'Location' => '',
        'Category' => '',
        'PostedAt' =>'',
        'EmploymentType' => '',
    );
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
    $arr1Keys = array_keys($arr1);
    $arr2Keys = array_keys($arr2);
    $arrCombinedKeys = array_merge_recursive($arr1Keys, $arr2Keys);

    $arrNewBlankCombinedRecord = array_fill_keys($arrCombinedKeys, 'unknown');

    $arrMerged =  array_replace( $arrNewBlankCombinedRecord, $arr1 );
    $arrMerged =  array_replace( $arrMerged, $arr2 );

    return $arrMerged;
}


/**
 * Returns an array of all the keys of all values at every level
 * of a multi-dimensional array
 *
 * @param array $array
 *
 * @return array the set of all keys used for values at all levels in the array
 */
function array_keys_multi(array $array)
{
    $keys = array();

    foreach ($array as $key => $value) {
        $keys[] = $key;

        if (is_array($value)) {
            $keys = array_merge($keys, array_keys_multi($value));
        }
    }

    return $keys;
}

/**
 * If you need, for some reason, to create variable Multi-Dimensional Arrays, here's a quick
 * function that will allow you to have any number of sub elements without knowing how many
 * elements there will be ahead of time. Note that this will overwrite an existing array
 * value of the same path.
 *
 * @author brian at blueeye dot us
 * @Link http://php.net/manual/en/function.array.php#52138
 *
 * @param $path
 * @param $data
 *
 * @return mixed
 */
function array_set_element(&$path, $data) {
	if(is_string($path))
		$path = preg_split("/\s*[\.:]\s*/", $path);
	return ($key = array_pop($path)) ? array_set_element($path, array($key=>$data)) : $data;
}

function array_add_element(&$arr, $path, $value)
{
	$newArrVal = array_set_element($path, $value);
	$arr = array_merge_recursive_distinct($arr, $newArrVal);
}

function array_get_element(&$path, $arr) {
	if(is_string($path))
		$path = array_reverse(preg_split("/\s*[\.:]\s*/", $path));
	$key = array_pop($path);
	return (!empty($path)) ? array_get_element($path, $arr[$key]) : $arr[$key];
}

/**
 *
 * Some INI files use dot or colon notation to define section and subkeys.  Convert
 * any keys with dot notion to be subarray elements of the overall config array.
 *
 * Example:
 *      database.connector.mysql.host = dbserver01.myserver.net
 *  becomes
 *      config['database']['connector']['mysql']['myhost']
 *
 * @param $config array storing the loaded configuration data
 *
 */
function convertDotNotation(&$config)
{
	$allKeys = array_keys_multi($config);
	$sectionedKeys = array_filter($allKeys, function ($v) {
		$keyLevels = preg_split("/\s*[\.:]\s*/", $v);
		if (count($keyLevels) > 1)
			return true;

		return false;
	});

	foreach ($sectionedKeys as $treeKey) {
		$keyPath = preg_split("/\s*[\.:]\s*/", $treeKey);
		$newValues = array_set_element($keyPath, $config[$treeKey]);
		$config = array_merge_recursive_distinct($config, $newValues);
		unset($config[$treeKey]);
	}
}


function setGlobalSetting($root, $keyPath, $value)
{
	doGlobalSettingExists($root);

	array_add_element($GLOBALS[$root], $keyPath, $value);
	ksort($GLOBALS[$root]);
}

function getGlobalSetting($root, $keyPath=null)
{
	doGlobalSettingExists($root);

	// return the whole array if no keypath was given
	if(empty($keyPath))
		return $GLOBALS[$root];

	return array_get_element($keyPath, $GLOBALS[$root]);
}

function doGlobalSettingExists($root)
{
	if(!array_key_exists($root, $GLOBALS))
		$GLOBALS[$root] = array();
}

const JOBSCOOPER_CONFIGSETTING_ROOT = "JSCOOP";
const JOBSCOOPER_CACHES_ROOT = "JSCOOP_CACHES";

function setConfigurationSetting($keyPath, $value)
{
	setGlobalSetting($root=JOBSCOOPER_CONFIGSETTING_ROOT, $keyPath, $value);
}

function getConfigurationSetting($keyPath)
{
	return getGlobalSetting(JOBSCOOPER_CONFIGSETTING_ROOT, $keyPath);
}

function getAllConfigurationSettings()
{
	doGlobalSettingExists(JOBSCOOPER_CONFIGSETTING_ROOT);

	return getGlobalSetting(JOBSCOOPER_CONFIGSETTING_ROOT);
}

function setCacheItem($cacheName, $keyPath, $value)
{
	setGlobalSetting($root=JOBSCOOPER_CACHES_ROOT, $cacheName.".".$keyPath, $value);
}

function getCacheItem($cacheName, $keyPath)
{
	return getGlobalSetting(JOBSCOOPER_CACHES_ROOT, $cacheName.".".$keyPath);
}

function getCacheAsArray($cacheName)
{
	doGlobalSettingExists(JOBSCOOPER_CACHES_ROOT);

	return getGlobalSetting(JOBSCOOPER_CACHES_ROOT, $cacheName);
}

function setAsCacheData($cacheName, $value)
{
	setGlobalSetting($root=JOBSCOOPER_CACHES_ROOT, $cacheName, $value);
}


