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
namespace JobScooper\Manager;

use JobScooper\Logging\CSVLogHandler;
use JobScooper\Logging\ErrorEmailLogHandler;
use Monolog\ErrorHandler;
use Monolog\Handler\DeduplicationHandler;
use Propel\Runtime\Propel;
use Psr\Log\LogLevel as LogLevel;
use \Monolog\Handler\StreamHandler;
use Monolog\Logger;
use DateTime;


/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Class:  Information and Error Logging                                               ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

function getChannelLogger($channel)
{
    if(!is_null($GLOBALS['logger']))
        return $GLOBALS['logger']->getChannelLogger($channel);
}

Class JobsErrorHandler extends ErrorHandler
{
    public function handleException($e)
    {
	    if(empty($GLOBALS['logger']))
		    $GLOBALS['logger'] = getChannelLogger("default");
	    LogError(sprintf("Uncaught Exception: %s", $e->getMessage()));
	    handleException($e, "Uncaught Exception: %s");
//        exit(255);
    }

}

Class LoggingManager extends \Monolog\Logger
{
    protected $arrCumulativeErrors = array();

    private $_handlersByType = array();
    private $_loggerName = "default";
    private $_loggers = array();
    private $_csvHandle = null;
    private $_dedupeHandle = null;
    private $_doLogContext = false;

    public function __construct($name, array $handlers = array(), array $processors = array())
    {
        $GLOBALS['logger'] = null;
        $GLOBALS['logger'] = $this;
        
        $name = C__APPNAME__;
        JobsErrorHandler::register($this, array(), LogLevel::ERROR);

        $this->_handlersByType = array(
//            'stderr' => new StreamHandler('php://stderr', isDebug() ? Logger::DEBUG : Logger::INFO)
        );

        parent::__construct($name, $handlers = $this->_handlersByType);

        $this->_loggers[$this->_loggerName] = $this;
        $this->_loggers['plugins'] = $this->withName('plugins');
        $this->_loggers['database'] = $this->withName('database');

        $logOptions = getConfigurationSetting('logging');
        $this->_doLogContext = filter_var($logOptions['always_log_context'], FILTER_VALIDATE_BOOLEAN);


        $now = new DateTime('NOW');

        $this->_handlersByType['stderr'] = new StreamHandler("php://stderr", Logger::DEBUG );
        $this->pushHandler($this->_handlersByType['stderr']);
        $this->logLine("Logging started from STDIN", C__DISPLAY_ITEM_DETAIL__);

//        $serviceContainer->setLogger('defaultLogger', $defaultLogger);
        $propelContainer = Propel::getServiceContainer();

        $this->logLine("Logging started for " . __APP_VERSION__ ." at " . $now->format('Y-m-d\TH:i:s'), C__DISPLAY_NORMAL__);
    }

    public function getChannelLogger($channel)
    {
        if( is_null($channel) || !in_array($channel, array_keys($this->_loggers)))
            $channel = 'default';

        return $this->_loggers[$channel];
    }

    /**
     * @private
     */
    public function handleException($e)
    {
        handleException($e);
        exit(255);
    }

    public function updatePropelLogging()
    {
        Propel::getServiceContainer()->setLogger('defaultLogger', $this);
        if(isDebug()) {
            $con = Propel::getWriteConnection(\JobScooper\DataAccess\Map\JobPostingTableMap::DATABASE_NAME);
            $con->useDebug(true);
            LogLine("Enabled debug logging for Propel.", C__DISPLAY_ITEM_DETAIL__);
        }
    }

    public function addFileHandlers($logPath)
    {
        $logLevel = (isDebug() ? Logger::DEBUG : Logger::INFO);

        $today = getTodayAsString("-");
        $mainLog = $logPath. DIRECTORY_SEPARATOR . "{$this->_loggerName}-{$today}.log";
        $this->_handlersByType['logfile'] = new StreamHandler($mainLog, $logLevel, $bubble = true);
        $this->pushHandler($this->_handlersByType['logfile']);
        $this->logLine("Logging started to logfile at {$mainLog}", C__DISPLAY_ITEM_DETAIL__);

        $now = getNowAsString("-");
        $csvlog = $logPath. DIRECTORY_SEPARATOR . "{$this->_loggerName}-{$now}-run_errors.csv";
        $fpcsv = fopen($csvlog, "w");
        $this->_handlersByType['csverrors'] = new CSVLogHandler($fpcsv, Logger::WARNING, $bubble = true);
        $this->pushHandler($this->_handlersByType['csverrors'] );
        $this->logLine("Logging started to CSV file at {$csvlog}", C__DISPLAY_ITEM_DETAIL__);

        $now = getNowAsString("-");
        $dedupeLog = $logPath. DIRECTORY_SEPARATOR . "{$this->_loggerName}-{$now}-dedupe_log_errors.csv";
        $this->_dedupeHandle = fopen($dedupeLog, "w");
        $this->_handlersByType['dedupe_email'] = new DeduplicationHandler(new ErrorEmailLogHandler(Logger::ERROR, true),  $deduplicationStore = $dedupeLog, $deduplicationLevel = Logger::ERROR, $time = 60, $bubble = true);
        $this->pushHandler($this->_handlersByType['dedupe_email']);
        $this->logLine("Logging started for deduped email log file at {$dedupeLog}", C__DISPLAY_ITEM_DETAIL__);

        $this->updatePropelLogging();

    }

    function __destruct()
    {
        $this->flushErrorNotifications();

        if(!is_null($this->_csvHandle))
        {
            fclose($this->_csvHandle);
        }
    }

    function flushErrorNotifications()
    {
//        $this->_handlersByType['bufferedmail']->flush();

    }

    function logLine($strToPrint, $varDisplayStyle = C__DISPLAY_NORMAL__, $origLogLevel = LogLevel::INFO, $context = array())
    {
        if($this->_doLogContext && count($context) == 0)
            $context = getDebugContext();
        elseif(is_array($context))
            $context = array_merge_recursive_distinct($context, getDebugContext());

        $strLineBeginning = '';
        $strLineEnd = '';
        $logLevel = null;
        switch ($varDisplayStyle)
        {
            case C__DISPLAY_WARNING__:
                $logLevel = "warning";
                break;

	        case C__DISPLAY_NORMAL__:
            case C__DISPLAY_SUMMARY__:
                $logLevel = "info";
                break;

            case C__DISPLAY_RESULT__:
                $strLineBeginning = '==> ';
                $logLevel = "info";
                break;

            case C__DISPLAY_ERROR__:
                $logLevel = "error";
                break;

            case C__DISPLAY_ITEM_START__:
            case C__DISPLAY_ITEM_DETAIL__:
            case C__DISPLAY_ITEM_RESULT__:
                $logLevel = "info";
                break;

            default:
                throw new \ErrorException('Invalid type value passed to __debug__printLine.  Value = '.$varDisplayStyle. ".");
                break;
        }

        if(count($context) > 0)
        {
            $channel = array_shift($context);
            $logger = $this->getChannelLogger($channel);
        }
        else
        {
            $logger = $this->getChannelLogger('default');
        }

        if(!is_null($origLogLevel))
            $logLevel = $origLogLevel;

        if($logLevel >= LogLevel::WARNING and empty($context))
            $context = getDebugContext();

        $logger->log($logLevel, $strLineBeginning . $strToPrint . $strLineEnd, $context);

    }

    public function log($level, $message, array $context = array())
    {

        if(parent::log($level, $message, $context) === false)
            print($message .PHP_EOL . PHP_EOL );
    }

    private $_openSections = 0;

	function startLogSection($headerText)
	{
		return $this->_logSectionHeader($headerText, C__SECTION_BEGIN__);
	}

	function endLogSection($headerText)
	{
		return $this->_logSectionHeader($headerText, C__SECTION_END__);
	}


	private function _logSectionHeader($headerText, $nType)
	{

		if ($nType == C__SECTION_BEGIN__)
		{
			$indentCount = $this->_openSections * 2;
			$lineChar = strval($this->_openSections + 1);
			$intro = "BEGIN: ";
			$this->_openSections += 1;
		}
		else {
			$this->_openSections -= 1;
			$lineChar = strval($this->_openSections + 1);
			$indentCount = $this->_openSections * 2;
			$intro = "END: ";
		}

        $indent = sprintf("%-{$indentCount}s", "");
		$numCharsSecLines = max((strlen($headerText) + 15), 80);
		$sepLineFmt = "[%'{$lineChar}{$numCharsSecLines}s]";

		$sepLine = sprintf($sepLineFmt, "") . PHP_EOL;

		$fmt = PHP_EOL . PHP_EOL .
			"{$indent}{$sepLine}" . PHP_EOL .
			"{$indent}%-5s%s%s " . PHP_EOL . PHP_EOL .
			"{$indent}{$sepLine}" .
			PHP_EOL;

		$lineContent = sprintf($fmt, "", $intro, $headerText );

		$this->log(LogLevel::INFO, $lineContent);

    }

}
