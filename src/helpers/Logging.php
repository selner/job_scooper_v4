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


/****************************************************************************************************************/

use Monolog\Logger;

/****                                                                                                        ****/
/****         Logging                                                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
/**
 * @return Logger
 */

function getLogger()
{
    if (!array_key_exists('logger', $GLOBALS)) {
        $GLOBALS['logger'] = \JobScooper\Manager\LoggingManager::getInstance();
    }

    return $GLOBALS['logger'];
}
/**
 * @param $headerText
 * @param $nSectionLevel
 * @param $nType
 */
function startLogSection($headerText)
{
    $logger = getLogger();
    if (empty($logger)) {
        print($headerText. "\r\n");
    } else {
        $logger->startLogSection($headerText);
    }
}

/**
 * @param $headerText
 * @param $nSectionLevel
 * @param $nType
 */
function endLogSection($headerText)
{
    $logger = getLogger();
    if (empty($logger)) {
        print($headerText. "\r\n");
    } else {
        $logger->endLogSection($headerText);
    }
}


/**
 * @param $msg
 * @param \Psr\Log\LogLevel $level
 * @param array $context
 */
function LogMessage($msg, $logLevel= Logger::INFO, $extras=array(), $ex=null, $log_topic=null)
{
    $logger = getLogger();
    if (empty($logger)) {
        print($msg . "\r\n");
    } else {
        if (empty($logLevel)) {
            $logLevel = Logger::INFO;
        }
        $logger->logRecord($logLevel, $msg, $extras, $ex, $log_topic);
    }
}

/**
 * @param $msg
 */
function LogError($msg, $extras=array(), $ex=null, $log_topic=null)
{
    LogMessage($msg, Logger::ERROR, $extras, $ex, $log_topic);
}


/**
 * @param $msg
 */
function LogWarning($msg, $extras=array(), $log_topic=null)
{
    LogMessage($msg, Logger::WARNING, $extras, null, $log_topic);
}

/**
 * @param     $msg
 * @param int $scooper_level
 */
function LogDebug($msg, $extras=array(), $log_topic=null)
{
    if (isDebug()) {
        LogMessage($msg, Logger::DEBUG, $extras, null, $log_topic);
    }
}

/**
 * @param       $msg
 * @param array $context
 */
function LogPlainText($msg, $logLevel=Logger::INFO, $extras = array(), $log_topic=null)
{
    $textParts = preg_split("/[\\r\\n|" . PHP_EOL . "]/", $msg);
    if (($textParts === false) || null === $textParts) {
        LogMessage($msg);
    } else {
        foreach ($textParts as $part) {
            LogMessage($part, $logLevel, $extras, null, $log_topic);
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
function handleException(Exception $ex, $fmtLogMsg= null, $raise=true, $extraData=null, $log_topic=null, $exceptClass = null)
{
    $logLevel = Logger::ERROR;


    if(!is_empty_value($extraData) && array_key_exists('JobSiteKey', $extraData)) {
        $log_topic = 'plugins';
    }


	if(is_a($ex, PDOException::class))
	{
	    $conmgr = Propel\Runtime\Propel::getConnectionManager(\JobScooper\DataAccess\Map\JobPostingTableMap::DATABASE_NAME);
	    $conmgr->closeConnections();
	    $log_topic = 'database';
	}

    $msg = $fmtLogMsg;
    if (null !== $ex && null !== $fmtLogMsg && null !== $ex && strlen($fmtLogMsg) > 0) {
        if (false !== stripos($fmtLogMsg, '%s')) {
            $msg = sprintf($fmtLogMsg, $ex->getMessage());
        } else {
            $msg = $fmtLogMsg . PHP_EOL . ' ~ ' . $ex->getMessage();
        }
    } elseif (null !== $ex) {
        $msg = $ex->getMessage();
        $logLevel = Logger::DEBUG;
    }

    $toThrow = $ex;
    if (null === $toThrow) {
        if(!is_empty_value($exceptClass)) {
            try {
                $toThrow = new $exceptClass($msg, $previous=$ex);
            }
            catch (Exception $ex) {
                $toThrow = new \Exception($msg, $previous = $ex);
            }
        }
        else {
            $toThrow = new \Exception($msg, $previous = $ex);
        }
    }


    LogMessage($msg, $logLevel, $extras=$extraData, $ex=$toThrow, $log_topic);

    if ($raise == true) {
        throw $toThrow;
    }
}

function throwException($fmtLogMsg= null, Exception $ex=null, $raise=true, $extraData=null, $log_topic=null, $exceptClass = null)
{
    if (is_null($ex)) {
        $ex = new Exception($fmtLogMsg);
    }
    handleException($ex, $fmtLogMsg, $raise, $extraData, $log_topic, $exceptClass);
}
