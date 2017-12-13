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

const C__SECTION_BEGIN__ = 1;
const C__SECTION_END__ = 2;
const C__DISPLAY_NORMAL__ = 100;
const C__DISPLAY_ITEM_START__ = 200;
const C__DISPLAY_ITEM_DETAIL__ = 300;
const C__DISPLAY_ITEM_RESULT__ = 350;

const C__DISPLAY_WARNING__ = 405;
const C__DISPLAY_ERROR__ = 500;
const C__DISPLAY_RESULT__ = 600;
const C__DISPLAY_SUMMARY__ = 750;


/**
 * @param array $context
 *
 * @return array
 */
function getDebugContext($context=array(), \Exception $thrownExc = null)
{
	$baseContext = [
		'class_call' => "",
		'exception_message' => "",
		'exception_file' => "",
		'exception_line' => "",
//		'exception_trace' => "",
		'channel' => "",
		'jobsite' => ""
	];
	$context = array_merge($baseContext, $context);

	//Debug backtrace called. Find next occurence of class after Logger, or return calling script:
	$dbg = debug_backtrace();
	$i = 0;
	$jobsiteKey = null;
	$usersearch = null;
	$loggedBacktrace = array();

	$class = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
	while ($i < count($dbg) - 1 ) {
		if (!empty($dbg[$i]['class']) && stripos($dbg[$i]['class'], 'LoggingManager') === false &&
			(empty($dbg[$i]['function']) || !in_array($dbg[$i]['function'], array("getDebugContent", "handleException"))))
		{
			$loggedBacktrace = $dbg[$i];

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
							if(is_object($dbg[$i]['args'][0]) && method_exists(get_class($dbg[$i]['args'][0]), "getUserSearchSiteRunKey"))
								$usersearch = $dbg[$i]['args'][0]->getUserSearchSiteRunKey();
							else
								$usersearch = "";
					} catch (Exception $ex) { $usersearch = ""; }
				}
				break;
			}
		}
		$i++;
	}


	$context['class_call'] = $class;
	$context['channel'] = is_null($jobsiteKey) ? "default" : "plugins";
	$context['jobsite'] = $jobsiteKey;
//	$context['user_search_run_key'] = $usersearch,
//	$context['memory_usage'] = memory_get_usage() / 1024 / 1024;


	if(!empty($thrownExc))
	{
		$context['exception_message'] = $thrownExc->getMessage();
		$context['exception_file'] = $thrownExc->getFile();
		$context['exception_line'] = $thrownExc->getLine();
//		$context['exception_trace'] = join("|", preg_split("/$/", encodeJSON($thrownExc->getTrace())));
	}

	return $context;
}


/**
 * @param       $msg
 * @param int   $scooper_level
 * @param array $context
 */
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

/**
 * @param $headerText
 * @param $nSectionLevel
 * @param $nType
 */
function startLogSection($headerText)
{
	if(is_null($GLOBALS['logger']) || !isset($GLOBALS['logger']))
	{
		print($headerText. "\r\n");
	}
	else
	{
		$GLOBALS['logger']->startLogSection($headerText);
	}
}

/**
 * @param $headerText
 * @param $nSectionLevel
 * @param $nType
 */
function endLogSection($headerText)
{
	if(is_null($GLOBALS['logger']) || !isset($GLOBALS['logger']))
	{
		print($headerText. "\r\n");
	}
	else
	{
		$GLOBALS['logger']->endLogSection($headerText);
	}
}



/**
 * @param $msg
 */
function LogError($msg, $context=array())
{
	$ctxt = getDebugContext($context);

	if(is_null($GLOBALS['logger']) || !isset($GLOBALS['logger']))
	{
		print($msg . "\r\n");
	}
	else
	{
		$GLOBALS['logger']->log(\Monolog\Logger::ERROR, $msg, $context);
	}
}

/**
 * @param     $msg
 * @param int $scooper_level
 */
function LogDebug($msg, $scooper_level=C__DISPLAY_NORMAL__)
{
	if(isDebug())
	{
		$context = getDebugContext();
		if(is_null($GLOBALS['logger']) || !isset($GLOBALS['logger']))
		{
			print($msg. "\r\n");
		}
		else
		{
			$GLOBALS['logger']->debug($msg, $context);
		}
	}
}

/**
 * @param       $msg
 * @param array $context
 */
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
/**
 * @param      $ex
 * @param null $fmtLogMsg
 * @param bool $raise
 *
 * @throws \Exception
 */

function handleException(Exception $ex, $fmtLogMsg= null, $raise=true)
{
	$context = getDebugContext(array(), $ex);
	$toThrow = $ex;
	if (empty($toThrow))
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

	LogError($msg, $context);

	if ($raise == true) {
		throw $toThrow;
	}
}
