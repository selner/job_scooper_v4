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
use FemtoPixel\Monolog\Handler\CsvHandler;
use JobScooper\Logging\ErrorEmailLogHandler;
use JobScooper\Utils\JobsExceptionHandler;
use JobScooper\Utils\Settings;
use Monolog\Formatter\LineFormatter;

use Monolog\Handler\BufferHandler;
use Monolog\Handler\HandlerInterface;
use Propel\Runtime\Propel;
use Psr\Log\LogLevel as LogLevel;
use \Monolog\Handler\StreamHandler;
use Monolog\Logger;
use DateTime;
use Exception;
use \Bramus\Monolog\Formatter\ColoredLineFormatter;
use JobScooper\Traits\Singleton;

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Class:  Information and Error Logging                                               ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

/**
 * Class LoggingManager
 * @package JobScooper\Manager
 */
class LoggingManager extends \Monolog\Logger
{
    use Singleton;

    protected $arrCumulativeErrors = array();

    private $_handlersByType = array();
    private $_loggerName = 'default';
    private $_loggers = array();

    private $_shouldLogContext = false;
    private $_monologLevelForRun = self::ERROR;
    private $_mysqlConn = null;


    /**
     * LoggingManager constructor.
     *
     * @param       $name
     * @param array $handlers
     * @param array $processors
     *
     * @throws \Exception
     */
    public function __construct($name = C__APPNAME__, array $handlers = array(), array $processors = array())
    {
        $GLOBALS['logger'] = $this;

        $logger = new Logger($name);

        $handler = new JobsExceptionHandler($logger);
//        $handler->registerErrorHandler([], false);
        $handler->registerExceptionHandler();
        $handler->registerFatalHandler();

        $this->_handlersByType = array(
//            'stderr' => new StreamHandler('php://stderr', isDebug() ? Logger::DEBUG : Logger::INFO)
        );

        parent::__construct($name, $this->_handlersByType);

        $logOptions = Settings::getValue('logging', array());
        if (isDebug()) {
            $this->_monologLevelForRun = self::DEBUG;
        } elseif (array_key_exists('log_level', $logOptions) &&
            !empty($logOptions['log_level'])) {
            if (strtoupper($logOptions['log_level']) === 'DEBUG') {
                Settings::setValue('debug', true);
            }
            $this->_monologLevelForRun = self::toMonologLevel($logOptions['log_level']);
        } else {
            $this->_monologLevelForRun = self::INFO;
        }


        $now = new DateTime('NOW');

        $stderrHandler = new StreamHandler('php://stderr', $this->_monologLevelForRun);
        $fmter = new ColoredLineFormatter(new DefaultScheme());
        $fmter->allowInlineLineBreaks(true);
        $fmter->includeStacktraces(true);
        $fmter->ignoreEmptyContextAndExtra(true);
        $stderrHandler->setFormatter($fmter);
        $this->addHandler('stderr', $stderrHandler);


        $this->_loggers[$this->_loggerName] = $this;

        $this->logRecord(LogLevel::INFO, 'Logging started from STDIN');

//        $serviceContainer->setLogger('defaultLogger', $defaultLogger);
        $propelContainer = Propel::getServiceContainer();

        $this->logRecord(LogLevel::INFO, 'Logging started for ' . __APP_VERSION__ . ' at ' . $now->format('Y-m-d\TH:i:s'));
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
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function updatePropelLogging(): void
    {
        Propel::getServiceContainer()->setLogger($this->getName(), $this);
        if (isDebug()) {
            $con = Propel::getWriteConnection(\JobScooper\DataAccess\Map\JobPostingTableMap::DATABASE_NAME);
            $con->useDebug(true);
            LogMessage('Enabled debug logging for Propel.');
        }
    }

    /**
     * Pushes a handler on to the stack.
     *
     * @param  string $logType
     * @param  HandlerInterface $handler
     * @return $this
     */
    private function addHandler(string $logType, HandlerInterface $handler): self
    {
        $this->_handlersByType[$logType] = $handler;
        return $this->pushHandler($handler);
    }

    /**
     * /**
     * @param $logPath
     *
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
     */
    public function addFileHandlers($logPath)
    {

        //
        // Define the various output log filenames
        //
        $now = getNowAsString('-');
        $today = getTodayAsString('-');
        $pathLogBase = $logPath . DIRECTORY_SEPARATOR . "{$this->_loggerName}-{$today}";
        $mainLog = "{$pathLogBase}.log";
        $csvlog = "{$pathLogBase}_run_errors_{$now}.csv";

        //
        // Configure and add the main output log handler
        //
        $mainHandler = new StreamHandler($mainLog, $this->_monologLevelForRun, $bubble = true);
        $fmter = $mainHandler->getFormatter();
        $fmter->allowInlineLineBreaks(true);
        $fmter->includeStacktraces(true);
        $fmter->ignoreEmptyContextAndExtra(true);
        $mainHandler->setFormatter($fmter);
        $this->addHandler('logfile', $mainHandler);
        $this->logRecord(LogLevel::INFO, "Logging started to logfile at {$mainLog}");

        //
        // Configure the CSV-format error log
        //
        $csvErrHandler = new CsvHandler($csvlog, self::WARNING, $bubble = true);
        $this->addHandler('csverrors', $csvErrHandler);
        $this->logRecord(LogLevel::INFO, "Error logging to CSV file: {$csvlog}");

        //
        // Configure the errors email log handler
        //
        $emailHandler = new BufferHandler(
            new ErrorEmailLogHandler(self::ERROR, true),
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
        $this->_mysqlConn = null;
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
     * @param null $ex
     */
    public function logRecord($level, $message, $extras = array(), $ex = null, $log_topic = null)
    {
        if (empty($level)) {
            $level = $this->_monologLevelForRun;
        }
        $context = null;

        if(isDebug()) {
            $context = $this->getDebugContext($extras, $ex, $log_topic);
        }

        $monologLevel = Logger::toMonologLevel($level);
        if($context == null) {
            $context = array();
        }
        if ($this->log($monologLevel, $message, $context) === false) {
            print($message . PHP_EOL . PHP_EOL);
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
        $this->_logSectionHeader($headerText, LoggingManager::C__LOG_SECTION_BEGIN);
    }

    /**
     * @param $headerText
     */
    public function endLogSection($headerText)
    {
        $this->_logSectionHeader($headerText, LoggingManager::C__LOG_SECTION_END);
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

    /**
     * @param array $context
     *
     * @return array
     */
    public function getDebugContext($context = array(), \Exception $thrownExc = null, $log_topic=null)
    {

        $blankContext = $this->_getEmptyLogContext();
        if(is_null($context)) {
            $context = $blankContext;
        }
        else
        {
            $context = array_merge($blankContext, $context);
        }

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

        if(!is_empty_value($log_topic)) {
            $baseContext['log_topic'] = $log_topic;
        }

        if (is_array($context)) {
            $context = array_merge($baseContext, $context);
        } else {
            $context = $baseContext;
        }

        if (null !== $thrownExc) {
            $errContext = $this->_getEmptyLogContext();

            $context = array_merge($errContext, $context);


            $jobsiteKey = null;
            if (array_key_exists('user', $context)) {
                $context['search_user'] = $context['user'];
                unset($context['user']);
            }

            if (!is_empty_value($context['jobsitekey'])) {
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
                    $class = $dbg[$i]['class'] . '->' . $dbg[$i]['function'] . '()';
                    if (!empty($dbg[$i]['object'])) {
                        $objclass = get_class($dbg[$i]['object']);
                        if (strcasecmp($objclass, $dbg[$i]['class']) != 0) {
                            $class = "{$objclass} -> {$class}";
                            if (is_empty_value($jobsiteKey)) {
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
            if(!is_empty_value($jobsiteKey)) {
                $context['log_topic'] = 'plugins';
            }
            $context['jobsitekey'] = $jobsiteKey;

            $context['exception_message'] = $thrownExc->getMessage();
            $context['exception_file'] = $thrownExc->getFile();
            $context['exception_line'] = $thrownExc->getLine();
            //		$context['exception_trace'] = join('|', preg_split('/$/', encodeJson($thrownExc->getTrace())));
        }


        return $context;
    }


    private function _getEmptyLogContext(){
        return [
            'class_call' => '',
            'memory_usage' => '',
            'exception_message' => '',
            'exception_file' => '',
            'exception_line' => '',
    		'exception_trace' => '',
            'hostname' => gethostname(),
            'jobsitekey' => '',
            'runtime' => '',
            'log_topic' => 'default',
            'search_user' => ''];

    }
}
