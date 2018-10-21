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
namespace JobScooper\Manager;

use Bramus\Monolog\Formatter\ColorSchemes\DefaultScheme;
use JobScooper\Logging\CSVLogHandler;
use JobScooper\Logging\ErrorEmailLogHandler;
use JobScooper\Utils\JobsExceptionHandler;
use JobScooper\Utils\Settings;
use Monolog\Formatter\LineFormatter;

use Monolog\Handler\BufferHandler;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RavenHandler;
use Propel\Runtime\Propel;
use Psr\Log\LogLevel as LogLevel;
use \Monolog\Handler\StreamHandler;
use Monolog\Logger;
use DateTime;
use Exception;
use \Bramus\Monolog\Formatter\ColoredLineFormatter;

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Class:  Information and Error Logging                                               ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

/**
 * Class LoggingManager
 * @package JobScooper\Manager
 */
class LoggingManager extends \Monolog\Logger
{
    protected $arrCumulativeErrors = array();

    private $_handlersByType = array();
    private $_loggerName = 'default';
    private $_loggers = array();
    private $_sentryClient = null;

    private $_shouldLogContext = false;
    private $_defaultLogLevel = self::ERROR;

    /**
     * LoggingManager constructor.
     *
     * @param       $name
     * @param array $handlers
     * @param array $processors
     *
     * @throws \Exception
     */
    public function __construct($name=C__APPNAME__, array $handlers = array(), array $processors = array())
    {
        $GLOBALS['logger'] = $this;

        $logger = new Logger($name);

        $handler = new JobsExceptionHandler($logger);
        $handler->registerErrorHandler([], false);
        $handler->registerExceptionHandler();
        $handler->registerFatalHandler();

        $this->_handlersByType = array(
//            'stderr' => new StreamHandler('php://stderr', isDebug() ? Logger::DEBUG : Logger::INFO)
        );

        parent::__construct($name, $handlers = $this->_handlersByType);

        $logOptions = Settings::getValue('logging', array());
        $this->_shouldLogContext = filter_var($logOptions['always_log_context'], FILTER_VALIDATE_BOOLEAN);
        if (array_key_exists('log_level', $logOptions) and !empty($logOptions['log_level'])) {
            if (strtoupper($logOptions['log_level']) === 'DEBUG') {
                Settings::setValue('debug', true);
            }
            $this->_defaultLogLevel = self::toMonologLevel($logOptions['log_level']);
        } else {
            $this->_defaultLogLevel = self::ERROR;
        }


        $now = new DateTime('NOW');

        $stderrHandler = new StreamHandler('php://stderr', Logger::DEBUG);
        $fmter = new ColoredLineFormatter(new DefaultScheme());
        $fmter->allowInlineLineBreaks(true);
        $fmter->includeStacktraces(true);
        $fmter->ignoreEmptyContextAndExtra(true);
        $stderrHandler->setFormatter($fmter);
        $this->addHandler('stderr', $stderrHandler);


        $this->_addSentryHandler();


        $this->_loggers[$this->_loggerName] = $this;
        $this->_loggers['plugins'] = $this->withName('plugins');
        $this->_loggers['database'] = $this->withName('database');
        $this->_loggers['caches'] = $this->withName('caches');

        $this->LogRecord(LogLevel::INFO, 'Logging started from STDIN');

//        $serviceContainer->setLogger('defaultLogger', $defaultLogger);
        $propelContainer = Propel::getServiceContainer();

        $this->logRecord(LogLevel::INFO, 'Logging started for ' . __APP_VERSION__ .' at ' . $now->format('Y-m-d\TH:i:s'));
    }

    /**
     * @return mixed|null
     */
    public function getMainLogFilePath()
    {
        $handler = $this->_handlersByType['logfile'];
        $stream = $handler->getStream();
        $meta = null;
        if (!empty($stream)) {
            $meta = stream_get_meta_data($stream);
        }

        if (is_array($meta) && array_key_exists('uri', $meta)) {
            return $meta['uri'];
        }

        return null;
    }

    /**
     * @param $channel
     *
     * @return mixed
     */
    public function getChannelLogger($channel)
    {
        if (empty($channel) || !array_key_exists($channel, $this->_loggers)) {
            $channel = $this->_loggerName;
        }

        return $this->_loggers[$channel];
    }

    /**
	* @param \Exception $e
	* @param array $record
	* @throws \Exception
	*/
    public function handleException(\Exception $e, array $record):void
    {
        handleException($e);
        exit(255);
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function updatePropelLogging():void
    {
        $logger = $this->getChannelLogger('database');

        Propel::getServiceContainer()->setLogger($logger->getName(), $logger);
        if (isDebug()) {
            $con = Propel::getWriteConnection(\JobScooper\DataAccess\Map\JobPostingTableMap::DATABASE_NAME);
            $con->useDebug(true);
            LogMessage('Enabled debug logging for Propel.');
        }
    }

    /**
     *
     */
    private function _addSentryHandler():void
    {
        $settings = Settings::getValue('logging.sentry.options');
        if (!empty($settings)) {
            if (array_key_exists('dsn', $settings)) {
                $this->log(Logger::INFO, 'Found Sentry config properties; setting up Sentry logging...');
                $settings = array_merge(array(
                    'server' => gethostname(),
                    'auto_log_stacks' => true
                ), $settings);

                $this->_sentryClient = new \Raven_Client($settings);

                $handler = new RavenHandler($this->_sentryClient, Logger::ERROR);
                $handler->setFormatter(new LineFormatter('%message% %context% %extra%\n'));

                $this->addHandler('sentry', $handler);

                $handler = new \Raven_Breadcrumbs_MonologHandler($this->_sentryClient, Logger::ERROR);
                $this->addHandler('sentry_breadcrumbs', $handler);
            }
        }
    }

    /**
     * Pushes a handler on to the stack.
     *
     * @param  HandlerInterface $handler
     * @return $this
     */
    private function addHandler(string $logType, HandlerInterface $handler):self
    {
        $this->_handlersByType[$logType] = $handler;
        return $this->pushHandler($handler);
    }

    /**
    /**
     * @param $logPath
     *
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
     */
    public function addFileHandlers($logPath)
    {
        $logLevel = (isDebug() ? Logger::DEBUG : $this->_defaultLogLevel);
        
		//
		// Define the various output log filenames
		//
        $now = getNowAsString('-');
        $today = getTodayAsString('-');
        $pathLogBase = $logPath. DIRECTORY_SEPARATOR . "{$this->_loggerName}-{$today}";
        $mainLog = "{$pathLogBase}.log";
        $errorLog = "{$pathLogBase}_errors_with_context.log";
        $csvlog = "{$pathLogBase}_run_errors_{$now}.csv";

		//
		// Configure and add the main output log handler
		//
        $mainHandler = new StreamHandler($mainLog, $logLevel, $bubble = true);
        $fmter = $mainHandler->getFormatter();
	        $fmter->allowInlineLineBreaks(true);
            $fmter->includeStacktraces(true);
            $fmter->ignoreEmptyContextAndExtra(true);
		$mainHandler->setFormatter($fmter);
        $this->addHandler('logfile', $mainHandler);
        $this->logRecord(LogLevel::INFO, "Logging started to logfile at {$mainLog}");

		//
		// Configure the error log handler.  Error log includes the log context for the last
		// few entries before an error as well as the error record itself.
		//
        $errLogHandler = new StreamHandler($errorLog, LogLevel::DEBUG, $bubble = true);
		$errLogHandler->setFormatter($fmter);
        $fingersHandler = new FingersCrossedHandler(
		    $errLogHandler,
		    new ErrorLevelActivationStrategy(Logger::ERROR),
		    10,
		    true,
		    false,
		    Logger::WARNING
		);
        $this->addHandler('errorlog', $fingersHandler);
        $this->logRecord(LogLevel::INFO, "Logging errors only to file: {$errorLog}");

		//
		// Configure the CSV-format error log
		//
        $fpcsv = fopen($csvlog, 'wb');
        $csvErrHandler = new CSVLogHandler($fpcsv, LogLevel::ERROR, $bubble = true);
        $this->addHandler('csverrors', $csvErrHandler);
        $this->logRecord(LogLevel::INFO, "Error logging to CSV file: {$csvlog}");

		//
		// Configure the errors email log handler
		//
        $emailHandler = new BufferHandler(
        	    new ErrorEmailLogHandler(Logger::ERROR, true),
                100);
        $this->addHandler('email_errors', $emailHandler);

        $this->updatePropelLogging();
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->flushErrorNotifications();
    }

    /**
     *
     */
    public function flushErrorNotifications()
    {
//        $this->_handlersByType['bufferedmail']->flush();
    }

    /**
     * @param       $level
     * @param       $message
     * @param array $extras
     * @param null  $ex
     */
    public function logRecord($level, $message, $extras=array(), $ex=null, $channel=null)
    {
        if (empty($level)) {
            $level = $this->_defaultLogLevel;
        }

        $logger = $this->getChannelLogger($channel);

        $context = array();
        $monologLevel = \Monolog\Logger::toMonologLevel($level);
        if ($this->_shouldLogContext === true || in_array($monologLevel, array(
            \Monolog\Logger::WARNING, \Monolog\Logger::EMERGENCY, \Monolog\Logger::ERROR, \Monolog\Logger::DEBUG, \Monolog\Logger::CRITICAL))) {
            $context = $this->getDebugContext($extras, $ex);
        }

        if ($logger->log($monologLevel, $message, $context) === false) {
            print($message .PHP_EOL . PHP_EOL);
        }
    }

    /**
     * @var int
     */
    private $_openSections = 0;


    const C__LOG_SECTION_BEGIN = 1;
    const C__LOG_SECTION_END = 2;

    /**
     * @param $headerText
     */
    public function startLogSection($headerText)
    {
        return $this->_logSectionHeader($headerText, LoggingManager::C__LOG_SECTION_BEGIN);
    }

    /**
     * @param $headerText
     */
    public function endLogSection($headerText)
    {
        return $this->_logSectionHeader($headerText, LoggingManager::C__LOG_SECTION_END);
    }


    /**
     * @param $headerText
     * @param $nType
     */
    private function _logSectionHeader($headerText, $nType)
    {

        if ($nType == LoggingManager::C__LOG_SECTION_BEGIN) {
            assert($this->_openSections >= 0);
            $this->_openSections += 1;
            $indentCount = ($this->_openSections - 1) * 2;
            $lineChar = strval($this->_openSections);
            $intro = 'BEGIN: ';
        } else {
            assert($this->_openSections > 0);
            $lineChar = strval($this->_openSections);
            $indentCount = ($this->_openSections - 1) * 2;
            $intro = 'END: ';
            $this->_openSections -= 1;
        }

        $indent = sprintf("%-{$indentCount}s", '');
        $numCharsSecLines = max((strlen($headerText) + 15), 80);
        $sepLineFmt = "[%'{$lineChar}{$numCharsSecLines}s]";

        $sepLine = sprintf($sepLineFmt, '') . PHP_EOL;

        $fmt = PHP_EOL . PHP_EOL .
            "{$indent}{$sepLine}" . PHP_EOL .
            "{$indent}%-5s%s%s " . PHP_EOL . PHP_EOL .
            "{$indent}{$sepLine}" .
            PHP_EOL . PHP_EOL . PHP_EOL;

        $lineContent = sprintf($fmt, '', $intro, $headerText);

        $this->log(LogLevel::INFO, $lineContent);
    }

    public function getName()
    {
        return $this->_loggerName;
    }

    /**
     * @param array $context
     *
     * @return array
     */
    public function getDebugContext($context=array(), \Exception $thrownExc = null)
    {
    	$runtime_secs = 0;
        $runStartTime = Settings::getValue('app_run_start_datetime');
        if (!empty($runStartTime)) {
            $runtime_interval = $runStartTime->diff(new \DateTime());
            $runtime_secs = (int)$runtime_interval->format('%s');
        }

        $baseContext = [
            'runtime' => $runtime_secs,
            'memory_usage' => getPhpMemoryUsage()
        ];

        if (is_array($context)) {
            $context = array_merge($baseContext, $context);
        } else {
            $context = $baseContext;
        }

        if(null !== $thrownExc)
        {
            $errContext = [
            'class_call' => '',
            'exception_message' => '',
            'exception_file' => '',
            'exception_line' => '',
//		'exception_trace' => '',
            'hostname' => gethostname(),
            'channel' => '',
            'jobsitekey' => ''];

            $context = array_merge($errContext, $context);

            $jobsiteKey = null;
            if(!is_empty_value($context['jobsitekey'])) {
                $jobsiteKey = $context['jobsitekey'];
            }

	        //Debug backtrace called. Find next occurence of class after Logger, or return calling script:
	        $dbg = debug_backtrace();
	        $i = 0;
	        $usersearch = null;
	
	        $class = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
	        while ($i < \count($dbg) - 1) {
	            if (!empty($dbg[$i]['class']) && stripos($dbg[$i]['class'], 'LoggingManager') === false &&
	                (empty($dbg[$i]['function']) || !in_array($dbg[$i]['function'], array('getDebugContent', 'handleException')))) {
	                $class = $dbg[$i]['class'] . '->' . $dbg[$i]['function'] .'()';
	                if (!empty($dbg[$i]['object'])) {
	                    $objclass = get_class($dbg[$i]['object']);
	                    if (strcasecmp($objclass, $dbg[$i]['class']) != 0) {
	                        $class = "{$objclass} -> {$class}";
	                        if(is_empty_value($jobsiteKey)) {
                                try {
                                    if (is_object($dbg[$i]['object']) && method_exists($dbg[$i]['object'], 'getName')) {
                                        $jobsiteKey = $dbg[$i]['object']->getName();
                                    }
                                } catch (Exception $ex) {

                                }
                            }
//	                        try {
//	                            if (array_key_exists('args', $dbg[$i]) & is_array($dbg[$i]['args']) && array_key_exists(0, $dbg[$i]['args'])) {
//	                                if (is_object($dbg[$i]['args'][0]) && method_exists(get_class($dbg[$i]['args'][0]), 'getUserSearchSiteRunKey')) {
//	                                    $usersearch = $dbg[$i]['args'][0]->getUserSearchSiteRunKey();
//	                                } else {
//	                                    $usersearch = '';
//	                                }
//	                            }
//	                        } catch (Exception $ex) {
//	                            $usersearch = '';
//	                        }
	                    }
	                    break;
	                }
	            }
	            $i++;
	        }
	
	
	        $context['class_call'] = $class;
	        $context['channel'] = null === $jobsiteKey ? 'default' : 'plugins';
	        $context['jobsitekey'] = $jobsiteKey;

            $context['exception_message'] = $thrownExc->getMessage();
            $context['exception_file'] = $thrownExc->getFile();
            $context['exception_line'] = $thrownExc->getLine();
            //		$context['exception_trace'] = join('|', preg_split('/$/', encodeJson($thrownExc->getTrace())));
        }


        // If we are also connected to Sentry, make sure we've set similar context
        // values for those logged entries as well
        //
        if (!empty($this->_sentryClient)) {
            $this->_sentryClient = new \Raven_Client();

            if (!empty($context)) {
                $sentryContext = array_copy($context);
                $sentryContext['exception'] = $thrownExc;
                $this->_sentryClient->context = $sentryContext;
            }
        }

        return $context;
    }
}
