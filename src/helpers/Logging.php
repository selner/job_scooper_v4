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

function getLogger($channel=null)
{
    if (array_key_exists('logger', $GLOBALS)) {
        if (!empty($channel)) {
            return getChannelLogger($channel);
        }

        return $GLOBALS['logger'];
    }

    return null;
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
function LogMessage($msg, $logLevel= Logger::INFO, $extras=array(), $ex=null, $channel=null)
{
    $logger = getLogger();
    if (empty($logger)) {
        print($msg . "\r\n");
    } else {
        if (empty($logLevel)) {
            $logLevel = Logger::INFO;
        }
        $logger->logRecord($logLevel, $msg, $extras, $ex, $channel);
    }
}


/**
 * @param $msg
 */
function LogError($msg, $extras=array(), $ex=null, $channel=null)
{
    LogMessage($msg, Logger::ERROR, $extras, $ex, $channel);
}


/**
 * @param $msg
 */
function LogWarning($msg, $extras=array(), $channel=null)
{
    LogMessage($msg, Logger::WARNING, $extras, null, $channel);
}

/**
 * @param     $msg
 * @param int $scooper_level
 */
function LogDebug($msg, $extras=array(), $channel=null)
{
    if (isDebug()) {
        LogMessage($msg, Logger::DEBUG, $extras, null, $channel);
    }
}

/**
 * @param       $msg
 * @param array $context
 */
function LogPlainText($msg, $logLevel=Logger::INFO, $extras = array(), $channel=null)
{
    $textParts = preg_split("/[\\r\\n|" . PHP_EOL . "]/", $msg);
    if (($textParts === false) || null === $textParts) {
        LogMessage($msg);
    } else {
        foreach ($textParts as $part) {
            LogMessage($part, $logLevel, $extras, null, $channel);
        }
    }
}



/**
 * @param $channel
 *
 * @return mixed
 */
function getChannelLogger($channel)
{
    $logger = getLogger();
    if (empty($logger)) {
        return $logger->getChannelLogger($channel);
    }

    return null;
}

/**
 * @param      $ex
 * @param null $fmtLogMsg
 * @param bool $raise
 *
 * @throws \Exception
 */
function handleException(Exception $ex, $fmtLogMsg= null, $raise=true, $extraData=null, $channel=null)
{
    $toThrow = $ex;
    if (null === $toThrow) {
        $toThrow = new Exception($fmtLogMsg);
    }

    if(!is_empty_value($extraData) && array_key_exists('JobSiteKey', $extraData)) {
        $channel = 'plugins';
    }


	if(is_a($ex, PDOException::class))
	{
	    $conmgr = Propel\Runtime\Propel::getConnectionManager(\JobScooper\DataAccess\Map\JobPostingTableMap::DATABASE_NAME);
	    $conmgr->closeConnections();
	    $channel = 'database';
	}

    $msg = $fmtLogMsg;
    if (null !== $toThrow && null !== $fmtLogMsg && null !== $ex && strlen($fmtLogMsg) > 0) {
        if (false !== stripos($fmtLogMsg, '%s')) {
            $msg = sprintf($fmtLogMsg, $toThrow->getMessage());
            $toThrow = new Exception($msg, null, $ex);
        } else {
            $msg = $fmtLogMsg . PHP_EOL . ' ~ ' . $toThrow->getMessage();
        }
    } elseif (null !== $ex) {
        $msg = $toThrow->getMessage();
    }

    LogMessage($msg, Logger::ERROR, $extras=$extraData, $ex=$toThrow, $channel);

    if ($raise == true) {
        throw $toThrow;
    }
}
