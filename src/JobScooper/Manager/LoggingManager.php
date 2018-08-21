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
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;

use Monolog\Handler\BufferHandler;
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
    private $_csvHandle = null;
    private $_emailHandle = null;
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

        $logOptions = \JobScooper\Utils\Settings::getValue('logging', array());
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

        $this->_handlersByType['stderr'] = new StreamHandler('php://stderr', Logger::DEBUG);
        $fmter = new ColoredLineFormatter(new DefaultScheme());
        //	    $fmter = $this->_handlersByType['stderr']->getFormatter();
        $fmter->allowInlineLineBreaks(true);
        $fmter->includeStacktraces(true);
        $fmter->ignoreEmptyContextAndExtra(true);
        $this->_handlersByType['stderr']->setFormatter($fmter);
        $this->pushHandler($this->_handlersByType['stderr']);
        $this->_addSentryHandler();


        $this->_loggers[$this->_loggerName] = $this;
        $this->_loggers['plugins'] = $this->withName('plugins');
        $this->_loggers['database'] = $this->withName('database');
        $this->_loggers['caches'] = $this->withName('caches');

        $this->LogRecord(\Psr\Log\LogLevel::INFO, 'Logging started from STDIN');

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
     * @throws \Exception
     */
    public function handleException($e, $record)
    {
        handleException($e);
        exit(255);
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function updatePropelLogging()
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
    private function _addSentryHandler()
    {
        $settings = Settings::getValue('config_file_settings.sentry');
        if (!empty($settings)) {
            if (array_key_exists('dsn', $settings)) {
                LogMessage('Found Sentry config properties; setting up Sentry logging...');
                $sentryOptions = array(
                    'server' => gethostname(),
                    'auto_log_stacks' => true
                );
                $this->_sentryClient = new \Raven_Client($settings['dsn'], $sentryOptions);

                $handler = new RavenHandler($this->_sentryClient, Logger::ERROR);
                $handler->setFormatter(new LineFormatter('%message% %context% %extra%\n'));

                $this->_handlersByType['sentry'] = $handler;
                $this->pushHandler($handler);

                $handler = new \Raven_Breadcrumbs_MonologHandler($this->_sentryClient, Logger::ERROR);
                $this->_handlersByType['sentry_breadcrumbs'] = $handler;
                $this->pushHandler($handler);
            }
        }
    }

    /**
     * @param $logPath
     *
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
     */
    public function addFileHandlers($logPath)
    {
        $logLevel = (isDebug() ? Logger::DEBUG : $this->_defaultLogLevel);

        $today = getTodayAsString('-');
        $mainLog = $logPath. DIRECTORY_SEPARATOR . "{$this->_loggerName}-{$today}.log";
        $this->_handlersByType['logfile'] = new StreamHandler($mainLog, $logLevel, $bubble = true);
        $fmter = $this->_handlersByType['logfile']->getFormatter();
        $fmter->allowInlineLineBreaks(true);
        $fmter->includeStacktraces(true);
        $fmter->ignoreEmptyContextAndExtra(true);
        $this->_handlersByType['logfile']->getFormatter($fmter);
        $this->pushHandler($this->_handlersByType['logfile']);
        $this->logRecord(\Psr\Log\LogLevel::INFO, "Logging started to logfile at {$mainLog}");

        $now = getNowAsString('-');
        $csvlog = $logPath. DIRECTORY_SEPARATOR . "{$this->_loggerName}-{$now}-run_errors.csv";
        $fpcsv = fopen($csvlog, 'w');
        $this->_handlersByType['csverrors'] = new CSVLogHandler($fpcsv, $this->_defaultLogLevel, $bubble = true);
        $this->pushHandler($this->_handlersByType['csverrors']);
        $this->LogRecord(\Psr\Log\LogLevel::INFO, "Logging started to CSV file at {$csvlog}");

        $now = getNowAsString('-');
        $emailLog = $logPath. DIRECTORY_SEPARATOR . "{$this->_loggerName}-{$now}-email_error_log_errors.csv";
        $this->_emailHandle = fopen($emailLog, 'w');
        $this->_handlersByType['email_errors'] = new BufferHandler(
        	    new ErrorEmailLogHandler(Logger::ERROR, true),
                1000);
        $this->pushHandler($this->_handlersByType['email_errors']);
        $this->LogRecord(\Psr\Log\LogLevel::INFO, "Logging started for emailed error log file at {$emailLog}");

        $this->updatePropelLogging();
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->flushErrorNotifications();

        if (null !== $this->_csvHandle) {
            fclose($this->_csvHandle);
        }
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
            $indentCount = $this->_openSections * 2;
            $lineChar = (string) $this->_openSections + 1;
            $intro = 'BEGIN: ';
            $this->_openSections += 1;
        } else {
            $this->_openSections -= 1;
            $lineChar = (string) $this->_openSections + 1;
            $indentCount = $this->_openSections * 2;
            $intro = 'END: ';
        }

        $indent = sprintf("%-{$indentCount}s", '');
        $numCharsSecLines = max((strlen($headerText) + 15), 80);
        $sepLineFmt = "[%'{$lineChar}{$numCharsSecLines}s]";

        $sepLine = sprintf($sepLineFmt, '') . PHP_EOL;

        $fmt = PHP_EOL . PHP_EOL .
            "{$indent}{$sepLine}" . PHP_EOL .
            "{$indent}%-5s%s%s " . PHP_EOL . PHP_EOL .
            "{$indent}{$sepLine}" .
            PHP_EOL;

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
            'jobsite' => ''];

            $context = array_merge($errContext, $context);


	        //Debug backtrace called. Find next occurence of class after Logger, or return calling script:
	        $dbg = debug_backtrace();
	        $i = 0;
	        $jobsiteKey = null;
	        $usersearch = null;
	
	        $class = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
	        while ($i < count($dbg) - 1) {
	            if (!empty($dbg[$i]['class']) && stripos($dbg[$i]['class'], 'LoggingManager') === false &&
	                (empty($dbg[$i]['function']) || !in_array($dbg[$i]['function'], array('getDebugContent', 'handleException')))) {
	                $class = $dbg[$i]['class'] . '->' . $dbg[$i]['function'] .'()';
	                if (!empty($dbg[$i]['object'])) {
	                    $objclass = get_class($dbg[$i]['object']);
	                    if (strcasecmp($objclass, $dbg[$i]['class']) != 0) {
	                        $class = "{$objclass} -> {$class}";
	                        try {
	                            if (is_object($dbg[$i]['object']) && method_exists($dbg[$i]['object'], 'getName')) {
	                                $jobsiteKey = $dbg[$i]['object']->getName();
	                            }
	                        } catch (Exception $ex) {
	                            $jobsiteKey = '';
	                        }
	                        try {
	                            if (array_key_exists('args', $dbg[$i]) & is_array($dbg[$i]['args'])) {
	                                if (is_object($dbg[$i]['args'][0]) && method_exists(get_class($dbg[$i]['args'][0]), 'getUserSearchSiteRunKey')) {
	                                    $usersearch = $dbg[$i]['args'][0]->getUserSearchSiteRunKey();
	                                } else {
	                                    $usersearch = '';
	                                }
	                            }
	                        } catch (Exception $ex) {
	                            $usersearch = '';
	                        }
	                    }
	                    break;
	                }
	            }
	            $i++;
	        }
	
	
	        $context['class_call'] = $class;
	        $context['channel'] = null === $jobsiteKey ? 'default' : 'plugins';
	        $context['jobsite'] = $jobsiteKey;

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
