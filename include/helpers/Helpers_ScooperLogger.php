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

use Psr\Log\LogLevel as LogLevel;
use \Katzgrau\KLogger\Logger;

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Logging                                                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

const C__NAPPTOPLEVEL__ = 0;
const C__NAPPFIRSTLEVEL__ = 1;
const C__NAPPSECONDLEVEL__ = 2;
const C__SECTION_BEGIN__ = 1;
const C__SECTION_END__ = 2;
const C__DISPLAY_NORMAL__ = 100;
const C__DISPLAY_SECTION_START__ = 250;
const C__DISPLAY_ITEM_START__ = 200;
const C__DISPLAY_ITEM_DETAIL__ = 300;
const C__DISPLAY_ITEM_RESULT__ = 350;

const C__DISPLAY_MOMENTARY_INTERUPPT__ = 400;
const C__DISPLAY_WARNING__ = 405;
const C__DISPLAY_ERROR__ = 500;
const C__DISPLAY_RESULT__ = 600;
const C__DISPLAY_FUNCTION__= 700;
const C__DISPLAY_SUMMARY__ = 750;




/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Class:  Information and Error Logging                                               ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

Class ScooperLogger extends \Katzgrau\KLogger\Logger
{
    protected $arrCumulativeErrors = array();


    function addToErrs(&$strErr, $strNew)
    {
        $strErr = (strlen($strErr) > 0 ? "; " : ""). $strNew;
        $this->arrCumulativeErrors[] = $strNew;
    }


    function __construct($strOutputDirPath = null )
    {
        $GLOBALS['logger'] = null;

        if(!isset($strOutputDirPath)) { $strOutputDirPath = sys_get_temp_dir(); }
        parent::__construct($strOutputDirPath, LogLevel::DEBUG);

        $GLOBALS['logger'] = $this;
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


            $this->log($strLineBeginning . $strToPrint . $strLineEnd, $logLevel);

        }
    }

    public function log($message, $level, array $context = array())
    {
        if($level == LOG_ERR) {  $this->arrCumulativeErrors[] = $message; }

        print($message ."\r\n");
        parent::log($level, $message, $context);


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
            $strSectionType = "  END:    ".$headerText;}
        //
        // Output the section header
        //
        if($nType == C__SECTION_BEGIN__ || $nSectionLevel == C__NAPPTOPLEVEL__ )
        {
            echo $strPaddingBefore;
            echo $strSectionIntroSeparatorLine;
            echo ' '.$strSectionType.' '."\r\n";
            echo $strSectionIntroSeparatorLine;
            if($nSectionLevel == C__NAPPTOPLEVEL__ )
            {
                echo $strPaddingAfter;
            }

        }
        else // C__SECTION_END__ $strSectionType = "      Done.  ";}
        {
            echo "\r\n" . ' '.$strSectionType.' ' ."\r\n". $strSectionIntroSeparatorLine . "\r\n";
        }
    }

}
