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

use Monolog\ErrorHandler;
use Psr\Log\LogLevel as LogLevel;
use \Monolog\Handler\StreamHandler;
use Monolog\Logger;
use DateTime;


/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Class:  Information and Error Logging                                               ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

Class LoggingManager extends \Monolog\Logger
{
    protected $arrCumulativeErrors = array();

    private $_handlersByType = array();
    private $_loggerName = "default";


    function addToErrs(&$strErr, $strNew)
    {
        $strErr = (strlen($strErr) > 0 ? "; " : ""). $strNew;
        $this->arrCumulativeErrors[] = $strNew;
    }

    public function __construct($name, array $handlers = array(), array $processors = array())
    {
        $GLOBALS['logger'] = null;

        $GLOBALS['logger'] = $this;

        $name = C__APPNAME__;
        $this->_handlersByType = array(
            'stderr' => new StreamHandler('php://stderr', isDebug() ? Logger::DEBUG : Logger::INFO),
            'bufferedmail' => new \Monolog\Handler\BufferHandler(new ErrorEmailHandler(Logger::ERROR, true), $bufferLimit = 100, $level = Logger::ERROR, $bubble = false, $flushOnOverflow = true)
        );
        parent::__construct($name, $handlers = $this->_handlersByType);

        ErrorHandler::register($this);

        $now = new DateTime('NOW');
        $this->logLine("Logging started for " . __APP_VERSION__ ." at " . $now->format('Y-m-d\TH:i:s'), C__DISPLAY_NORMAL__);
    }

    public function addFileHandler($logPath)
    {
        $today = getTodayAsString("-");
        $this->_handlersByType['logfile'] = new StreamHandler($logPath. DIRECTORY_SEPARATOR . "{$this->_loggerName}-{$today}.log", isDebug() ? Logger::DEBUG : Logger::INFO);
        $this->pushHandler($this->_handlersByType['logfile']);
        $this->logLine("Logging started to logfile at {$logPath}", C__DISPLAY_NORMAL__);
    }

    function __destruct()
    {
        $this->flushErrorNotifications();
        $now = new DateTime('NOW');
        $this->logLine("Logging ended for " . __APP_VERSION__ ." at " . $now->format('Y-m-d\TH:i:s'),C__DISPLAY_NORMAL__);
    }

    function flushErrorNotifications()
    {
        $this->_handlersByType['bufferedmail']->flush();

    }

    function logLine($strToPrint, $varDisplayStyle = C__DISPLAY_NORMAL__, $fDebuggingOnly = false)
    {
        if($fDebuggingOnly != true)
        {
            $strLineEnd = '';
            $logLevel = null;
            switch ($varDisplayStyle)
            {
                case  C__DISPLAY_FUNCTION__:
                    $strLineBeginning = '<<<<<<<< function "';
                    $strLineEnd = '" called >>>>>>> ';
                    $logLevel = "debug";
                    break;

                case C__DISPLAY_WARNING__:
                    $strLineBeginning = "\r\n"."\r\n".'^^^^^^^^^^ "';
                    $strLineEnd = '" ^^^^^^^^^^ '."\r\n";
                    $logLevel = "warning";
                    break;

                case C__DISPLAY_SUMMARY__:

                    $strLineBeginning = "\r\n"."************************************************************************************"."\r\n". "\r\n";
                    $strLineEnd = "\r\n"."\r\n"."************************************************************************************"."\r\n";

                    $logLevel = "info";
                    break;

                    
                case C__DISPLAY_SECTION_START__:
                    $strLineBeginning = "\r\n"."####################################################################################"."\r\n". "\r\n";
                    $strLineEnd = "\r\n"."\r\n"."####################################################################################"."\r\n";

                    $logLevel = "info";
                    break;

                case C__DISPLAY_SECTION_END__:
                    $strLineBeginning = "\r\n"."~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"."\r\n". "\r\n";
                    $strLineEnd = "\r\n"."\r\n"."~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"."\r\n";

                    $logLevel = "info";
                    break;


                case C__DISPLAY_RESULT__:
                    $strLineBeginning = '==> ';

                    $logLevel = "info";
                    break;

                case C__DISPLAY_ERROR__:
                    $strLineBeginning = '!!!!! ';
                    $logLevel = "error";
                    break;

                case C__DISPLAY_ITEM_START__:
                    $strLineBeginning = '---> ';

                    $logLevel = "info";
                    break;

                case C__DISPLAY_ITEM_DETAIL__:
                    $strLineBeginning = '     ';

                    $logLevel = "info";
                    break;

                case C__DISPLAY_ITEM_RESULT__:
                    $strLineBeginning = '======> ';

                    $logLevel = "info";
                    break;

                case C__DISPLAY_MOMENTARY_INTERUPPT__:
                    $strLineBeginning = '......';
                    $logLevel = "warning";
                    break;

                case C__DISPLAY_NORMAL__:
                    $strLineBeginning = '';

                    $logLevel = "info";
                    break;

                default:
                    throw new ErrorException('Invalid type value passed to __debug__printLine.  Value = '.$varDisplayStyle. ".");
                    break;
            }


            $this->log($logLevel, $strLineBeginning . $strToPrint . $strLineEnd);

        }
    }

    public function log($level, $message, array $context = array())
    {
        if($level == LOG_ERR) {  $this->arrCumulativeErrors[] = $message; }

        if(parent::log($level, $message, $context) === false)
            print($message ."\r\n");


    }

    function logSectionHeader($headerText, $nSectionLevel, $nType)
    {

        $strPaddingBefore = "";
        $strPaddingAfter = "";

        //
        // Set the section header box style and intro/outro padding based on it's level
        // and whether its a section beginning header or an section ending.
        //
        switch ($nSectionLevel)
        {

            case(C__NAPPTOPLEVEL__):
                if($nType == C__SECTION_BEGIN__) { $strPaddingBefore = "\r\n"."\r\n"; }
                $strSeparatorChars = "#";
                if($nType == C__SECTION_END__) { $strPaddingAfter = "\r\n"."\r\n"; }
                break;

            case(C__NAPPFIRSTLEVEL__):
                if($nType == C__SECTION_BEGIN__) { $strPaddingBefore = ''; }
                $strSeparatorChars = "=";
                if($nType == C__SECTION_END__) { $strPaddingAfter = ''; }
                break;

            case(C__NAPPSECONDLEVEL__):
                if($nType == C__SECTION_BEGIN__)  { $strPaddingBefore = ''; }
                $strSeparatorChars = "-";
                if($nType == C__SECTION_END__) { $strPaddingAfter = ''; }
                break;

            default:
                $strSeparatorChars = ".";
                break;
        }

        //
        // Compute how wide the header box needs to be and then create a string of that length
        // filled in with just the separator characters.
        //
        $nHeaderWidth = 80;
        $fmtSeparatorString = "%'".$strSeparatorChars.($nHeaderWidth+3)."s\n";
        $strSectionIntroSeparatorLine = sprintf($fmtSeparatorString, $strSeparatorChars);



        if($nType == C__SECTION_BEGIN__)
        {
            $strSectionType = "  BEGIN:  ".$headerText;
        } else
        {
            $strSectionType = "  END:    ".$headerText;
        }


        //
        // Output the section header
        //
        if($nType == C__SECTION_BEGIN__ || $nSectionLevel == C__NAPPTOPLEVEL__ )
        {
            logLine("{$strPaddingBefore}{$strSectionIntroSeparatorLine}{$strSectionType}");
            $strLinesToPrint = $strSectionIntroSeparatorLine;
            if($nSectionLevel == C__NAPPTOPLEVEL__ )
            {
                $strLinesToPrint .= $strPaddingAfter;
            }
            logLine($strLinesToPrint);
        }
        else // C__SECTION_END__ $strSectionType = "      Done.  ";}
        {
            logLine("");
            logLine($strSectionType);
            logLine($strSectionIntroSeparatorLine);
        }
    }

}
