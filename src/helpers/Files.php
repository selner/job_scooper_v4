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


const C__FILEPATH_NO_FLAGS = 0x0;
const C__FILEPATH_FILE_MUST_EXIST = 0x1;
const C__FILEPATH_DIRECTORY_MUST_EXIST = 0x2;
const C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED = 0x4;

function get_dir_realpath($target, $parent) {
    $finalpath = realpath($target);
    if($finalpath == false) {
        $parts = explode("/", $target);
        if ($parts[0] = ".") {
            array_shift($parts);
            $relpath = implode("/", $parts);
            $finalpath = "$parent/$relpath";
        }
    }

    return $finalpath;
}


/**
 * @param     $strFilePath
 * @param int $flags
 *
 * @return SplFileInfo
 * @throws \ErrorException
 */
function parsePathDetailsFromString($strFilePath, $flags = C__FILEPATH_NO_FLAGS)
{
    //************************************************************************
    //
    // Now let's figure out what each part really maps to and setup the array with names for returning
    // to the caller.
    //
    // If AllParts only has one item, then there were no "/" characters in the path string.
    // So assume the path was either a filename only OR a relative directory path with no trailing '/'
    //

    if (!is_empty_value($strFilePath)) {
        $f = new SplFileInfo($strFilePath);

        //
        // At this point, we've set the values for the return array completely
        //


        if (isBitFlagSet($flags, C__FILEPATH_DIRECTORY_MUST_EXIST) && !$f->isDir()) {
            throw new \ErrorException('Directory \'' . $f->getPathname() . '\' does not exist.');
        }

        if (isBitFlagSet($flags, C__FILEPATH_FILE_MUST_EXIST) && !$f->isFile()) {
            throw new \ErrorException('File \'' . $f->getPathname() . '\' does not exist.');
        }

        if (isBitFlagSet($flags, C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED) && !$f->isDir()) {
            if (!mkdir($concurrentDirectory = $f->getPathname(), 0777, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf("Directory {$concurrentDirectory} was not created."));
            }
        }


        return $f;
    }

    return null;
}


/**
 * @param $key
 * @return mixed|null|string
 */
function getOutputDirectory($key)
{
    $ret = \JobScooper\Utils\Settings::getValue("output_directories.{$key}");
    if (empty($ret)) {
        $ret = sys_get_temp_dir();
    }

    return $ret;
}

/**
 * @param string $dirKey
 * @param string $baseFileName
 * @param string $ext
 * @param null $userId
 * @return string
 */
function generateOutputFilePath($dirKey = 'debug', $baseFileName = 'UNKNOWN', $ext = 'UNKNOWN', $userId = null)
{
    $outDir = getOutputDirectory($dirKey);
    $now = '_' . getNowAsString('');
    $user = '';

    if (null !== $userId) {
        $userFacts = \JobScooper\DataAccess\User::getUserFactsById($userId);
        if (!is_empty_value($userFacts)) {
            $user = '_' . $userFacts['UserSlug'];
        }
    }

    return "{$outDir}/{$baseFileName}{$user}{$now}.{$ext}";
}


/**
 * @param string $baseFileName
 * @param string $ext
 * @param bool $isUserSpecific
 * @param string $dirKey
 * @return string
 */
function generateOutputFileName($baseFileName = 'NONAME', $ext = 'UNK', $isUserSpecific = true, $dirKey = 'debug')
{
    $userId = null;

    if ($isUserSpecific === true) {
        $userFacts = \JobScooper\DataAccess\User::getCurrentUserFacts();
        if (!is_empty_value($userFacts)) {
            $userId = $userFacts['UserId'];
        }
    }

    return generateOutputFilePath($dirKey, $baseFileName, $ext, $userId);
}


/**
 * @param $filename
 * @param null $indexKeyName
 * @return array
 * @throws \Exception
 */
function loadCSV($filename, $indexKeyName = null)
{
    if (!is_file($filename)) {
        throw new Exception("Specified input file '{$filename}' was not found.  Aborting.");
    }

    $file = fopen($filename, 'r');
    if (is_bool($file)) {
        throw new Exception("Specified input file '{$filename}' could not be opened.  Aborting.");
    }

    $headers = fgetcsv($file);
    $arrLoadedList = array();
    while (!feof($file)) {
        $rowData = fgetcsv($file);
        if ($rowData === false) {
            break;
        } else {
            $arrRec = array_combine($headers, $rowData);
            if ($indexKeyName != null) {
                $arrLoadedList[$arrRec[$indexKeyName]] = $arrRec;
            } else {
                $arrLoadedList[] = $arrRec;
            }
        }
    }

    fclose($file);

    return $arrLoadedList;
}


/**
 * @param string $strFilePrefix
 * @param string $strBase
 * @param string $strExt
 * @param string $delim
 * @param null $directoryKey
 * @return string
 */
function getDefaultJobsOutputFileName($strFilePrefix = '', $strBase = '', $strExt = '', $delim = '', $directoryKey = null)
{
    $strFilename = '';
    if (strlen($strFilePrefix) > 0) {
        $strFilename .= $strFilePrefix . '-';
    }

    $strFilename .= getNowAsString($delim);

    if (strlen($strBase) > 0) {
        $strFilename .= '-' . $strBase;
    }
    if (strlen($strExt) > 0) {
        $strFilename .= '.' . $strExt;
    }

    $directory = getOutputDirectory($directoryKey);
    if (!empty($directory)) {
        $strFilename = $directory . DIRECTORY_SEPARATOR . $strFilename;
    }
    return $strFilename;
}

/**
 * json encode that can handle invalid UTF-8 chars
 *
 * @link http://php.net/manual/en/function.json-last-error.php#121233
 * @param $value
 * @param int $options
 * @param int $depth
 * @return string
 */
function safe_json_encode($value, $options = 0, $depth = 512)
{
    $encoded = json_encode($value, $options, $depth);
    if ($encoded === false && $value && json_last_error() == JSON_ERROR_UTF8) {
        $encoded = json_encode(utf8ize($value), $options, $depth);
    }
    return $encoded;
}

/**
 * use this code with mb_convert_encoding, you can json_encode some corrupt UTF-8 chars
 *
 * @link http://php.net/manual/en/function.json-last-error.php#121233
 * @param $mixed
 * @return array|mixed|string
 */
function utf8ize($mixed)
{
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
    }
    return $mixed;
}

/**
 * @param $data
 * @return string
 * @throws \Exception
 */
function encodeJson(&$data): string
{
    $jsonData = safe_json_encode($data, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    if ($jsonData === false) {
        $err = json_last_error_msg();
        $errMsg = "Error:  Unable to encode data to JSON.  Error: {$err}";
        LogError($errMsg);
        throw new Exception($errMsg);
    }
    return $jsonData;
}

/**
 * @param $strData
 * @param $filepath
 * @return string
 * @throws \Exception
 */
function file_put_text(&$strData, $filepath): string
{
    LogMessage("Writing data to json '{$filepath}'");
    if (file_put_contents($filepath, $strData, FILE_TEXT) === false) {
        $err = error_get_last();
        $errMsg = "Error:  Unable to save string data to file {$filepath} due to error:  {$err}";
        LogError($errMsg);
        throw new Exception($errMsg);
    }

    return $filepath;
}

/**
 * @param $data
 * @param $filepath
 * @return string
 * @throws \Exception
 */
function writeJson(&$data, $filepath): string
{
    try {
        $jsonData = encodeJson($data);
        return file_put_text($jsonData, $filepath);
    }
    catch(Throwable $ex) {
        LogError("Unable to encode JSON to file '{$filepath}': %s", null, $ex);
    }
}

/**
 * @param $strJsonText
 * @param null $options
 * @param bool $boolEscapeBackSlashes
 * @return mixed
 */
function decodeJson($strJsonText, $options = null, $boolEscapeBackSlashes = false)
{
    if (null === $options) {
        $options = JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK;
    }


    if ($boolEscapeBackSlashes === true) {
        $strJsonText = str_replace('\\', '\\\\', $strJsonText);
    }
    $data = json_decode($strJsonText, $assoc = true, $depth = 512, $options);
    return $data;
}

/**
 * @param $file
 * @param null $options
 * @param bool $boolEscapeBackSlashes
 * @return mixed|null
 */
function loadJson($file, $options = null, $boolEscapeBackSlashes = false)
{


// You can pass args like in `json_decode`
//    (new Comment)->decode($someJsonText, $assoc = true, $depth = 512, $options = JSON_BIGINT_AS_STRING);

    if (is_file($file)) {
        LogDebug("Reading json data from '{$file}'");
        $jsonText = file_get_contents($file, FILE_TEXT);
        // Strip only!
        $jsonText = (new \Ahc\Json\Comment())->strip($jsonText);
        return decodeJson($jsonText, $options, $boolEscapeBackSlashes);
    } else {
        LogError("Unable to load json data from '{$file}'");
        return null;
    }
}


/**
 * @param $prependText
 * @param $filepath
 * @throws \ErrorException
 */
function file_prepend($prependText, $filepath)
{
    $context = stream_context_create();
    $orig_file = fopen($filepath, 'r', 1, $context);
    if ($orig_file === false) {
        throw new ErrorException("Unable to open file stream {$filepath} for reading.");
    }

    $temp_filename = tempnam(sys_get_temp_dir(), 'php_prepend_');
    file_put_contents($temp_filename, $prependText);
    file_put_contents($temp_filename, $orig_file, FILE_APPEND);

    fclose($orig_file);
    unlink($filepath);
    rename($temp_filename, $filepath);
}
