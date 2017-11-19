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
use ErrorException;
use Exception;
use GuzzleHttp\Client;



class SeleniumManager extends \PropertyObject
{
    private $remoteWebDriver = null;
    private $additionalLoadDelaySeconds = null;
    private $lastCookies = array();
    private $_seleniumIsRunning = false;

    function __construct($additionalLoadDelaySeconds = 0)
    {
        $this->additionalLoadDelaySeconds = $additionalLoadDelaySeconds;

        if ($GLOBALS['USERDATA']['configuration_settings']['selenium']['autostart'] == True) {
            SeleniumManager::startSeleniumServer();
        }
    }

    function __destruct()
    {
        $this->doneWithRemoteWebDriver();

        if ($GLOBALS['USERDATA']['configuration_settings']['selenium']['autostart'] == True) {
            SeleniumManager::shutdownSelenium();
        }
        $this->_seleniumIsRunning = false;
    }

    function getPageHTML($url, $recursed = false)
    {
        try {
            $driver = $this->get_driver();

            foreach ($this->lastCookies as $cookie) {
                $driver->manage()->addCookie(array(
                    'name' => $cookie['name'],
                    'value' => $cookie['value'],
                ));
            }
            $this->loadPage($url);

            $this->lastCookies = $driver->manage()->getCookies();

            $src = $driver->getPageSource();


            // BUGBUG:  Firefox has started to return "This tab has crashed" responses often as of late February 2017.
            //          Adding check for that case and a session kill/reload when it happens
            if (stristr($src, "tab has crashed") != false) {
                LogLine("Error in Firefox WebDriver:  tab has crashed retrieving page at " . $url . ".  Killing WebDriver and trying one more time...", \C__DISPLAY_WARNING__);
                // We found "tab has crashed" in the response, so we can't use it.
                if ($recursed != true) {
                    $this->killAllAndRestartSelenium();
                    return $this->getPageHTML($url, $recursed = true);
                } else {
                    handleException(new Exception("Error in Firefox WebDriver:  tab has crashed getting " . $url . " a second time.  Cannot load correct results so aborting..."), "%s", $raise = true);
                }
            }

            return $src;
        } catch (\WebDriverCurlException $ex) {
            handleException($ex, null, true);
        } catch (\WebDriverException $ex) {
            handleException($ex, null, true);
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        }
    }

    function loadPage($url)
    {
        try {
            $driver = $this->get_driver();
            if (strncmp($driver->getCurrentURL(), $url, strlen($url)) != 0) {
                $driver->get($url);
                sleep(2 + $this->additionalLoadDelaySeconds);
            }
        } catch (\WebDriverCurlException $ex) {
            handleException($ex, "Error retrieving Selenium page at {$url}", false);
        } catch (\WebDriverException $ex) {
            handleException($ex, "Error retrieving Selenium page at {$url} ", false);
        } catch (Exception $ex) {
            handleException($ex, "Error retrieving Selenium page at {$url}", false);
        }
    }

    function done()
    {
        $this->doneWithRemoteWebDriver();
        $this->shutdownSelenium();
    }

    function killAllAndRestartSelenium()
    {
        try {
            $this->doneWithRemoteWebDriver();
        } catch (Exception $ex) {
            LogLine("Error stopping active Selenium sessions: " . $ex, \C__DISPLAY_ERROR__);
        }

        $webdriver = $this->getWebDriverKind();
        $pscmd = doExec("pkill -i " . $webdriver);

        SeleniumManager::shutdownSelenium();

        SeleniumManager::startSeleniumServer();

    }

    static function shutdownSelenium()
    {
        $canStop = false;
        $settings = getConfigurationSettings('selenium');
        if(array_key_exists('autostart', $settings))
            $canStop = ($settings['autostart'] === True);

        if($canStop)
        {
            if(array_key_exists('stop_command', $settings) && !is_null($settings['stop_command']) && !empty($settings['stop_command']))
            {
                LogLine("Attempting to stop Selenium server with command \"" . $settings['stop_command'] . "\"", \C__DISPLAY_ITEM_DETAIL__);
                $res = doExec($settings['stop_command']);
                LogLine("Stopping Selenium server result: "  . $res, \C__DISPLAY_ITEM_RESULT__);
            }
            else {

                try {
                    // The only way to shutdown standalone server in 3.0 is by killing the local process.
                    // Details: https://github.com/SeleniumHQ/selenium/issues/2852
                    //
                    $cmd = 'pid=`ps -eo pid,args | grep selenium-server | grep -v grep | cut -c1-6`; if [ "$pid" ]; then kill -9 $pid; echo "Killed Selenium process #"$pid; else echo "Selenium server is not running."; fi';
                    if (isset($GLOBALS['logger'])) {
                        LogLine("Killing Selenium server process with command \"" . $cmd . "\"", \C__DISPLAY_ITEM_DETAIL__);
                    }
                    $res = doExec($cmd);
                    LogLine("Killing Selenium server result: " . $res, \C__DISPLAY_ITEM_RESULT__);
                } catch (Exception $ex) {
                    $pscmd = doExec("pkill -i selenium");
                    LogLine("Killing Selenium server result: " . $res, \C__DISPLAY_ITEM_RESULT__);
                    LogLine("Failed to send shutdown to Selenium server.  Attempted to kill process, however you may need to manually shut it down.", \C__DISPLAY_ERROR__);
                }
            }
        }
        else
        {
            LogLine("Did not attempt Selenium server shutdown since we didn't and can't autostart it.", \C__DISPLAY_ITEM_DETAIL__);
        }

    }

     protected function doneWithRemoteWebDriver()
    {
        try {

            if(!is_null($this->remoteWebDriver))
            {
                $this->remoteWebDriver->quit();
            }
        } catch (\WebDriverCurlException $ex) {
            handleException($ex, "Failed to quit Webdriver: ", false);
        } catch (\WebDriverException $ex) {
            handleException($ex, "Failed to quit Webdriver: ", false);
        } catch (Exception $ex) {
            handleException($ex, "Failed to quit Webdriver: ", false);
        }
        finally
        {
            $driver = null;
            $this->remoteWebDriver = null;
        }
    }

    function startSelenium()
    {
        $this->_seleniumIsRunning = SeleniumManager::startSeleniumServer();


    }
    static function startSeleniumServer()
    {
        $settings = getConfigurationSettings('selenium');

        $seleniumStarted = SeleniumManager::isServerUp();
        if($seleniumStarted == false)
        {
            $canStop = false;
            if(array_key_exists('autostart', $settings))
                $canStop = ($settings['autostart'] === True);

            if($canStop === True && array_key_exists('start_command', $settings) && !is_null($settings['start_command']))
            {
                LogLine("Attempting to start Selenium server with command \"" . $settings['start_command'] . "\"", \C__DISPLAY_NORMAL__);
                $res = doExec($settings['start_command']);
                LogLine("Starting Selenium server result: "  . $res, \C__DISPLAY_NORMAL__);

                sleep(10);
                $seleniumStarted = true;
            }
            else if($canStop && stripos($settings['host_location'], "localhost") != false || (stripos($settings['host_location'], "127.0.0.1") != false)) {

                $strCmdToRun = "java ";
                if (array_key_exists('prefix_switches', $settings))
                    $strCmdToRun .= $settings['prefix_switches'];

                $strCmdToRun .= " -jar \"" . $settings['jar'] . "\" -port " . $settings['port'] . " ";
                if (array_key_exists('prefix_switches', $settings))
                    $strCmdToRun .= $settings['postfix_switches'];

                $strCmdToRun .= " >/dev/null &";

                LogLine("Starting Selenium with command: '" . $strCmdToRun . "'", \C__DISPLAY_ITEM_RESULT__);
                $res = doExec($strCmdToRun);
                sleep(10);
                LogLine("Starting Selenium server result: "  . $res, \C__DISPLAY_NORMAL__);
                $seleniumStarted = true;
            }
            else {
                $seleniumStarted = false;
                throw new Exception("Selenium is not running and was not set to autostart. Cannot continue without an instance of Selenium running.");
            }
        }
        else {
            $seleniumStarted = true;
            LogLine("Selenium is already running on port " . $settings['port'] . ".  Skipping startup of server.", \C__DISPLAY_WARNING__);
        }
        return $seleniumStarted;
    }

    static function isServerUp()
    {
        $hostHubPageURL = $GLOBALS['USERDATA']['configuration_settings']['selenium']['host_location'] . '/wd/hub';
        $msg = "Checking Selenium server up.... ";

        $ret = false;

        try{

            $client = new \GuzzleHttp\Client();

            $res = $client->request('GET', $hostHubPageURL);
            $rescode = $res->getStatusCode();
            if($rescode > 200)
            {
                return false;
            }
            $strHtml = $res->getBody();

            $objSimplHtml = \SimpleHTMLHelper::str_get_html($strHtml);
            if ($objSimplHtml === false)
            {
                $ret = false;
                return $ret;
            }

            $tag = $objSimplHtml->find("title");
            if (is_null($tag) != true && count($tag) >= 1)
            {
                $title = $tag[0]->plaintext;
                $msg = $msg . " Found hub server page '" . $title . "' as expected.  Selenium server is up.'";
                $ret = true;
            }

        } catch (\WebDriverCurlException $ex) {
            $msg = $msg . " Selenium not yet running (failed to access the hub page.)";
        } catch (\WebDriverException $ex) {
            $msg = $msg . " Selenium not yet running (failed to access the hub page.)";
        } catch (Exception $ex) {
            $msg = $msg . " Selenium not yet running (failed to access the hub page.)";
        }
        finally
        {
            LogLine($msg, \C__DISPLAY_NORMAL__);
        }

        return $ret;
    }

    function get_driver()
    {
        try {

                try {
                    if (is_null($this->remoteWebDriver))
                        $this->create_remote_webdriver();
                    return $this->remoteWebDriver;

                } catch (\WebDriverCurlException $ex) {
                    $this->killAllAndRestartSelenium();
                    $this->create_remote_webdriver();
                    return $this->remoteWebDriver;

                } catch (\WebDriverException $ex) {
                    $this->killAllAndRestartSelenium();
                    $this->create_remote_webdriver();
                    return $this->remoteWebDriver;

                } catch (Exception $ex) {
                    $this->killAllAndRestartSelenium();
                    $this->create_remote_webdriver();
                    return $this->remoteWebDriver;

                }
        } catch (\WebDriverCurlException $ex) {
            handleException($ex, "Failed to get Selenium remote webdriver: ", true);
        }

        return null;
    }

    function getWebDriverKind()
    {
        $webdriver = (array_key_exists('webdriver', $GLOBALS['USERDATA']['configuration_settings']['selenium'])) ? $GLOBALS['USERDATA']['configuration_settings']['selenium']['webdriver'] : null;
        if(is_null($webdriver)) {
            $webdriver = "phantomjs";
            if (PHP_OS == "Darwin")
                $webdriver = "safari";
        }

        return $webdriver;
    }

    private function create_remote_webdriver()
    {
        $hubUrl = $GLOBALS['USERDATA']['configuration_settings']['selenium']['host_location'] . '/wd/hub';
        logLine("Creating Selenium remote web driver to host {$hubUrl}...");

        try {

            $webdriver = $this->getWebDriverKind();
            $hubUrl = $GLOBALS['USERDATA']['configuration_settings']['selenium']['host_location'] . '/wd/hub';
            $driver = null;

            $capabilities = \DesiredCapabilities::$webdriver();

            $capabilities->setCapability("setThrowExceptionOnScriptError", false);
            $capabilities->setCapability("unexpectedAlertBehaviour", "dismiss");
            $capabilities->setCapability(\WebDriverCapabilityType::ACCEPT_SSL_CERTS, true);
            $capabilities->setCapability(\WebDriverCapabilityType::APPLICATION_CACHE_ENABLED, true);
            $capabilities->setCapability(\WebDriverCapabilityType::CSS_SELECTORS_ENABLED, true);
            $capabilities->setCapability(\WebDriverCapabilityType::WEB_STORAGE_ENABLED, true);
            $capabilities->setCapability(\WebDriverCapabilityType::NATIVE_EVENTS, true);
            $capabilities->setCapability(\WebDriverCapabilityType::HANDLES_ALERTS, true);
            $capabilities->setCapability(\WebDriverCapabilityType::DATABASE_ENABLED, true);
            $capabilities->setCapability(\WebDriverCapabilityType::LOCATION_CONTEXT_ENABLED, true);

            $this->remoteWebDriver = \RemoteWebDriver::create(
                $hubUrl,
                $desired_capabilities = $capabilities,
                $connection_timeout_in_ms = 60000,
                $request_timeout_in_ms = 60000
            );

            LogLine("Remote web driver instantiated.");

            return $this->remoteWebDriver;
        } catch (\WebDriverCurlException $ex) {
            handleException($ex, "Failed to get webdriver from {$hubUrl}: ", true);
        } catch (\WebDriverException $ex) {
            handleException($ex, "Failed to get webdriver from {$hubUrl}: ", true);
        } catch (Exception $ex) {
            handleException($ex, "Failed to get webdriver from {$hubUrl}: ", true);
        }
        return null;
    }


}
