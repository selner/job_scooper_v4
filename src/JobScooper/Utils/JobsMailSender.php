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
namespace JobScooper\Utils;

//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use InvalidArgumentException;

class JobsMailSender
{
    private $alwaysNotify = false;

    function __construct($alwaysNotify = false)
    {
        $this->alwaysNotify = $alwaysNotify;
    }

    function __destruct()
    {

    }


    private function _getEmailAddressesByEmailType_($emailKind=null)
    {

        $settings = $GLOBALS['USERDATA']['configuration_settings']['email'];

        if(is_null($emailKind) || empty($emailKind))
            $emailKind = 'results';

        $retEmails = array (
            'to' => array(),
            'from' => array(),
            'bcc' => array()
        );

        if(!is_null($settings['email_addresses']) && is_array($settings['email_addresses']))
        {
            foreach($settings['email_addresses'] as $emailaddy)
            {
                if((!array_key_exists('emailkind', $emailaddy) && $emailKind == "results") || strcasecmp($emailaddy['emailkind'], $emailKind) == 0)
                {
                    if(!array_key_exists('name', $emailaddy))
                        $emailaddy['name'] = $emailaddy['address'];

                    $retEmails[$emailaddy['type']][] = $emailaddy;
                }
            }
            $retEmails['from'] = $retEmails['from'][0];
        }
        else
        {
            $adminFallback = array(
                "type" => "to",
                "address" => "dev@bryanselner.com",
                "name" => "dev@bryanselner.com",
                "emailkind" => "error");

            $retEmails["to"] = array($adminFallback);
            $retEmails["from"] = $adminFallback;
        }

        if(!isset($retEmails["to"]) || count($retEmails["to"]) < 1 || strlen(current($retEmails["to"])['address']) <= 0)
        {
            $msg = "Could not find 'to:' email address in configuration file. Notification will not be sent.";
            LogLine($msg, \C__DISPLAY_ERROR__);
            throw new InvalidArgumentException($msg);
        }

        if(is_array_multidimensional($retEmails['from']))
        {
            LogLine("Multiple 'from:' email addresses found. Notification will be from first one only (" . $retEmails['from'][0]['address'] . ").", \C__DISPLAY_WARNING__);
        }
        elseif(!is_array($retEmails['from']))
        {
            $msg = "Could not find 'from:' email address in configuration file. Notification will not be sent.";
            LogLine($msg, \C__DISPLAY_ERROR__);
            throw new InvalidArgumentException($msg);
        }

        return $retEmails;

    }


    function sendEmail($strBodyText = null, $strBodyHTML = null, $arrDetailsAttachFiles = array(), $subject="No subject", $emailKind='results')
    {
        if($this->alwaysNotify === false) {
            if (!isset($GLOBALS['USERDATA']['OPTS']['send_notifications']) || $GLOBALS['USERDATA']['OPTS']['send_notifications'] != 1) {
                LogLine(PHP_EOL . "User set -send_notifications = false so skipping email notification.)" . PHP_EOL, \C__DISPLAY_NORMAL__);
                LogLine("Mail contents would have been:" . PHP_EOL . $strBodyText, \C__DISPLAY_NORMAL__);
                return null;
            }
        }

        $settings = getConfigurationSettings('emails_to_send');

        $mail = new PHPMailer();

        $smtpSettings = $settings['PHPMailer_SMTPSetup'];

        if($smtpSettings != null && is_array($smtpSettings))
        {
            $mail->isSMTP();
            $properties = array_keys($smtpSettings);
            foreach($properties as $property)
            {
                $mail->$property = $smtpSettings[$property];
            }

        }
        else
        {
            $mail->isSendmail();
        }


        //
        // Add initial email address header values
        //
        $emailAddrs = getConfigurationSettings('emails_to_send');
        $strToAddys = "";
        $strBCCAddys = "";
        foreach($emailAddrs["to"] as $to)
        {
            if($to['emailkind'] == $emailKind)
            {
                $mail->addAddress($to['EmailAddress'], is_null($to['Name']) ? $to['EmailAddress'] : $to['Name']);
                $strToAddys .= (strlen($strToAddys) <= 0 ? "" : ", ") . $to['EmailAddress'];
            }
        }
        foreach($emailAddrs['bcc'] as $bcc)
        {
            if($to['emailkind'] == $emailKind) {
                $mail->addBCC($bcc['EmailAddress'], $bcc['Name']);
                $strBCCAddys .= ", " . $bcc['EmailAddress'];
            }
        }
        foreach($emailAddrs['from'] as $from)
        {
            if($from['emailkind'] == $emailKind) {
                $mail->setFrom($from['EmailAddress'], $from['Name']);
                break;
            }
        }

        $mail->addReplyTo("dev@bryanselner.com", "dev@bryanselner.com" );
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        LogLine("Email to:\t" . $strToAddys , \C__DISPLAY_NORMAL__);
        LogLine("Email from:\t" . $emailAddrs['from']['EmailAddress'], \C__DISPLAY_NORMAL__);
        LogLine("Email bcc:\t" . $strBCCAddys, \C__DISPLAY_NORMAL__);


        $mail->WordWrap = 120;                                          // Set word wrap to 120 characters
        if(!is_null($arrDetailsAttachFiles) && is_array($arrDetailsAttachFiles))
        {
            foreach($arrDetailsAttachFiles as $detailsAttach)
            {
                if(isset($detailsAttach) && isset($detailsAttach['full_file_path']))
                {
                    $mail->addAttachment($detailsAttach['full_file_path']);
                }
            }
        }        // Add attachments

        $mail->isHTML(true);                                            // Set email format to HTML

        if(strlen($strBodyText) == 0 || strlen($strBodyHTML) > 0)
            $strBodyText = strip_tags($strBodyHTML);

        $mail->Body    = $strBodyHTML;
        $mail->AltBody = $strBodyText;
        $mail->Subject = $subject;

        $ret = $mail->send();
        if($ret !== true)
        {
            //
            // If sending the email fails, try again but this time with SMTP debug
            // enabled so we have any idea what the issue might be in the log.
            // If we don't do this, we just get "failed" without any useful details.
            //
            $msg = "Failed to send notification email with error = ".$mail->ErrorInfo . PHP_EOL . "Retrying email send with debug enabled to log error details...";
            LogLine($msg, \C__DISPLAY_ERROR__);
            $mail->SMTPDebug = 1;
            $ret = $mail->send();
            if($ret === true) return $ret;

            $msg = "Failed second attempt to send notification email.  Debug error details should be logged above.  Error: " . PHP_EOL .$mail->ErrorInfo;
            LogLine($msg, \C__DISPLAY_ERROR__);
            throw new Exception($msg);

        }
        else
        {
            LogLine("Email notification sent to '" . $strToAddys . "' from '" . $emailAddrs['from']['EmailAddress'] . "' with BCCs to '" . $strBCCAddys ."'", \C__DISPLAY_ITEM_RESULT__);
        }
        return $ret;

    }

    public function addMailCssToHTMLFile($strFilePath)
    {

        $strHTMLContent = file_get_contents($strFilePath);
        $retWrapped = $this->addMailCssToHTML($strHTMLContent);

        file_put_contents($strFilePath, $retWrapped);

    }

    public function addMailCssToHTML($strHTML)
    {
        $cssData = array(
            'color-primary' => "#49332D",
            'color-secondary' => "#e7dcd7",
            'color-level-1' => '#6d4d41',
            'color-level-2' => '#61443a',
            'color-level-3' => '#553c33',
            'color-level-4' => '#49332c',
            'color-level-5' => '#3d2b24'
        );


        $renderer = loadTemplate(__ROOT__."/assets/templates/html_notification_email_css.tmpl");

        $css = renderTemplate($renderer, $cssData);

        $cssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
        $cssToInlineStyles->setHTML($strHTML);
        $cssToInlineStyles->setCSS($css);
        return $cssToInlineStyles->convert();
    }


}