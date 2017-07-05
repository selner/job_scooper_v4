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

require "PropertyObject.php";

class SeleniumSession extends PropertyObject
{
    private $remoteWebDriver = null;
    private $additionalLoadDelaySeconds = null;
    private $lastCookies = array();

    function __construct($additionalLoadDelaySeconds = 0)
    {
        $this->additionalLoadDelaySeconds = $additionalLoadDelaySeconds;
        $this->create_remote_webdriver();
    }

    function __destruct()
    {
        $this->doneWithRemoteWebDriver();
    }

    function getPageHTML($url, $recursed=false)
    {
        foreach ($this->lastCookies as $cookie) {
            $this->driver->manage()->addCookie(array(
                'name' => $cookie['name'],
                'value' => $cookie['value'],
            ));
        }
        $this->loadPage($url);

        $this->lastCookies = $this->driver->manage()->getCookies();

        $src = $this->driver->getPageSource();

        // BUGBUG:  Firefox has started to return "This tab has crashed" responses often as of late February 2017.
        //          Adding check for that case and a session kill/reload when it happens
        if (stristr($src, "tab has crashed") != false)
        {
            $GLOBALS['logger']->logLine("Error in Firefox WebDriver:  tab has crashed retrieving page at " . $url .".  Killing WebDriver and trying one more time...", \Scooper\C__DISPLAY_WARNING__);
            // We found "tab has crashed" in the response, so we can't use it.
            if ($recursed != true) {
                $this->killAllAndRestartSelenium();
                return $this->getPageHTML($url, $recursed = true);
            }
            else
            {
                handleException(new Exception("Error in Firefox WebDriver:  tab has crashed getting " . $url ." a second time.  Cannot load correct results so aborting..."), "%s", $raise=true);
            }
        }

        return $src;
    }

    function terminate()
    {
        $this->doneWithRemoteWebDriver();
        $this->shutdownSelenium();
    }

    function loadPage($url, $waitTime=null)
    {
        try
        {

            if (!is_null($waitTime))
            {
                $timeoutval = $waitTime + 10;
                $GLOBALS['logger']->logLine("Setting Selenium javascript and page timeouts to seconds=" . $timeoutval, \Scooper\C__DISPLAY_ITEM_DETAIL__);
                $timeouts = $this->driver->manage()->timeouts();
                
                $timeouts->setScriptTimeout($timeoutval);
                $timeouts->pageLoadTimeout($timeoutval);
            }

            $this->driver->get($url);

            sleep(1+$waitTime);

        } catch (Exception $ex) {
            $strMsg = "Error retrieving Selenium page at " . $url . ":  ". $ex;

            $GLOBALS['logger']->logLine($strMsg, \Scooper\C__DISPLAY_ERROR__);
            throw new ErrorException($strMsg);
        }
    }

    function unset_driver()
    {
        $this->doneWithRemoteWebDriver();
    }

    function killAllAndRestartSelenium()
    {
        try {
            $this->doneWithRemoteWebDriver();
        }
        catch (Exception $ex)
        {
            $GLOBALS['logger']->logLine("Error stopping active Selenium sessions: " . $ex, \Scooper\C__DISPLAY_ERROR__);
        }

        $webdriver = $this->getWebDriverKind();
        $pscmd = doExec("pkill -i " . $webdriver);

        SeleniumSession::shutdownSelenium();

        SeleniumSession::startSeleniumServer();

    }

    static function shutdownSelenium()
    {
        if(array_key_exists('selenium_started', $GLOBALS) && $GLOBALS['selenium_started'] === true) {
            
            if(array_key_exists('stop_command', $GLOBALS['USERDATA']['selenium']) && !is_null($GLOBALS['USERDATA']['selenium']['stop_command']))
            {
                $GLOBALS['logger']->logLine("Attempting to stop Selenium server with command \"" . $GLOBALS['USERDATA']['selenium']['stop_command'] . "\"", \Scooper\C__DISPLAY_NORMAL__);
                $res = doExec($GLOBALS['USERDATA']['selenium']['stop_command']);
                $GLOBALS['logger']->logLine("Stop Selenium server result: "  . $res, \Scooper\C__DISPLAY_NORMAL__);

                $GLOBALS['selenium_started'] = false;

            }
            else {

                try {
                    // The only way to shutdown standalone server in 3.0 is by killing the local process.
                    // Details: https://github.com/SeleniumHQ/selenium/issues/2852
                    //
                    $cmd = 'pid=`ps -eo pid,args | grep selenium-server | grep -v grep | cut -c1-6`; if [ "$pid" ]; then kill -9 $pid; echo "Killed Selenium process #"$pid; else echo "Selenium server is not running."; fi';
                    if (isset($GLOBALS['logger'])) {
                        $GLOBALS['logger']->logLine("Killing Selenium server process with command \"" . $cmd . "\"", \Scooper\C__DISPLAY_NORMAL__);
                    }
                    $res = doExec($cmd);
                    $GLOBALS['logger']->logLine("Killing Selenium server result: "  . $res, \Scooper\C__DISPLAY_NORMAL__);
                    $GLOBALS['selenium_started'] = false;
                } catch (Exception $ex) {
                    $pscmd = doExec("pkill -i selenium");
                    if (isset($GLOBALS['logger'])) {
                        $GLOBALS['logger']->logLine("Failed to send shutdown to Selenium server.  Attempted to kill process, however you may need to manually shut it down.", \Scooper\C__DISPLAY_ERROR__);
                    }
                } finally {
                    $GLOBALS['selenium_started'] = false;
                }
            }
        }
        else
        {
            if (isset($GLOBALS['logger'])) {
                $GLOBALS['logger']->logLine("Skipping Selenium server shutdown since we did not start it.", \Scooper\C__DISPLAY_WARNING__);
            }
        }

    }

    protected function doneWithRemoteWebDriver()
    {
        if(!is_null($this->remoteWebDriver))
        {
            try {
                $this->remoteWebDriver->close();
            }
            catch (Exception $ex) {  };

            try {
                $this->remoteWebDriver->quit();
            }
            catch (Exception $ex) {  };

        }

        $this->remoteWebDriver = null;
    }

    static function startSeleniumServer()
    {

        $seleniumStarted = SeleniumSession::isServerUp();
        if($seleniumStarted == true) {
            $GLOBALS['logger']->logLine("Selenium is already running on port " . $GLOBALS['USERDATA']['selenium']['port'] . ".  Skipping startup of server.", \Scooper\C__DISPLAY_WARNING__);

            if($GLOBALS['USERDATA']['selenium']['autostart'] == 1)
            {
//                    $cmd = 'ps -eo pid,args | grep selenium-server | grep -v grep | echo `sed \'s/.*port \([0-9]*\).*/\1/\'`';
                // $cmd = 'ps -eo pid,args | grep selenium-server | grep -v grep | ps -p `awk \'NR!=1 {print $2}\'` -o command=';
//                    $cmd = 'lsof -i tcp:' . $GLOBALS['USERDATA']['selenium']['port'] . '| ps -o command= -p `awk \'NR != 1 {print $2}\'` | sed -n 2p';
                $cmd = 'lsof -i tcp:' . $GLOBALS['USERDATA']['selenium']['port'];

                $seleniumStarted = false;
                $pscmd = doExec($cmd);
                if (!is_null($pscmd) && (is_array($pscmd) && count($pscmd) > 1))
                {
                    $pidLine = preg_split('/\s+/', $pscmd[1]);
                    if(count($pidLine) >1)
                    {
                        $pid = $pidLine[1];
                        $cmd = 'ps -o command= -p ' . $pid;
                        $pscmd = doExec($cmd);

                        if(preg_match('/selenium/', $pscmd) !== false)
                        {
                            $seleniumStarted = true;
                            $GLOBALS['logger']->logLine("Selenium is already running on port " . $GLOBALS['USERDATA']['selenium']['port'] . ".  Skipping startup of server.", \Scooper\C__DISPLAY_WARNING__);
                        }
                        else
                        {
                            $msg = "Error: port " . $GLOBALS['USERDATA']['selenium']['port'] . " is being used by process other than Selenium (" . var_export($pscmd, true) . ").  Aborting.";
                            $GLOBALS['logger']->logLine($msg, \Scooper\C__DISPLAY_ERROR__);
                            throw new Exception($msg);

                        }
                    }
                }
            }
        }
        else
        {
        #        if($seleniumStarted === false)
            if($GLOBALS['USERDATA']['selenium']['autostart'] == 1)
            {

                if(array_key_exists('start_command', $GLOBALS['USERDATA']['selenium']) && !is_null($GLOBALS['USERDATA']['selenium']['start_command']))
                {
                    $GLOBALS['logger']->logLine("Attempting to start Selenium server with command \"" . $GLOBALS['USERDATA']['selenium']['start_command'] . "\"", \Scooper\C__DISPLAY_NORMAL__);
                    $res = doExec($GLOBALS['USERDATA']['selenium']['start_command']);
                    $GLOBALS['logger']->logLine("Starting Selenium server result: "  . $res, \Scooper\C__DISPLAY_NORMAL__);

                    sleep(10);
                    $GLOBALS['selenium_started'] = true;
                }
                else if(stripos($GLOBALS['USERDATA']['selenium']['host_location'], "localhost") != false || (stripos($GLOBALS['USERDATA']['selenium']['host_location'], "127.0.0.1") != false)) {

                    $strCmdToRun = "java ";
                    if (array_key_exists('prefix_switches', $GLOBALS['USERDATA']['selenium']))
                        $strCmdToRun .= $GLOBALS['USERDATA']['selenium']['prefix_switches'];

                    $strCmdToRun .= " -jar \"" . $GLOBALS['USERDATA']['selenium']['jar'] . "\" -port " . $GLOBALS['USERDATA']['selenium']['port'] . " ";
                    if (array_key_exists('prefix_switches', $GLOBALS['USERDATA']['selenium']))
                        $strCmdToRun .= $GLOBALS['USERDATA']['selenium']['postfix_switches'];

                    $strCmdToRun .= " >/dev/null &";

                    $GLOBALS['logger']->logLine("Starting Selenium with command: '" . $strCmdToRun . "'", \Scooper\C__DISPLAY_ITEM_RESULT__);
                    $res = doExec($strCmdToRun);
                    sleep(10);
                    $GLOBALS['logger']->logLine("Starting Selenium server result: "  . $res, \Scooper\C__DISPLAY_NORMAL__);
                    $GLOBALS['selenium_started'] = true;
                }
            }
            else {
                throw new Exception("Selenium is not running and was not set to autostart. Cannot continue without an instance of Selenium running.");
            }
        }
    }

    static function isServerUp()
    {
        $hostHubPageURL = $GLOBALS['USERDATA']['selenium']['host_location'] . '/wd/hub';
        $msg = "Checking Selenium server up.... ";

        $ret = false;

        try{

            $objSimplHtml = \SimpleHtmlDom\file_get_html($hostHubPageURL);
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

        }
        catch (Exception $ex)
        {
            $msg = $msg . " Error retrieving hub page: " . $ex->getMessage();
        }
        finally
        {
            $GLOBALS['logger']->logLine($msg, \Scooper\C__DISPLAY_NORMAL__);
        }

        return $ret;
    }

    function get_driver()
    {
//                use \Facebook\WebDriver\Remote\WebDriverCapabilityType;
//                use \Facebook\WebDriver\Remote\RemoteWebDriver;
//                use \Facebook\WebDriver\WebDriverDimension;
//
//                $host = '127.0.0.1:8910';
//                $capabilities = array(
//                    WebDriverCapabilityType::BROWSER_NAME => 'phantomjs',
//                    'phantomjs.page.settings.userAgent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:25.0) Gecko/20100101 Firefox/25.0',
//                );
//                $driver = RemoteWebDriver::create($host, $capabilities, 5000);
//
//                $window = new WebDriverDimension(1024, 768);
//                $driver->manage()->window()->setSize($window);
//
//                $driver->get('https://www.google.ru/');
//
//                $driver->takeScreenshot('/tmp/screen.png');
//                $driver->quit();

        if (is_null($this->remoteWebDriver))
            $this->create_remote_webdriver();

        return $this->remoteWebDriver;
    }

    function getWebDriverKind()
    {
        $webdriver = (array_key_exists('webdriver', $GLOBALS['USERDATA']['selenium'])) ? $GLOBALS['USERDATA']['selenium']['webdriver'] : null;
        if(is_null($webdriver)) {
            $webdriver = "phantomjs";
            if (PHP_OS == "Darwin")
                $webdriver = "safari";
        }

        return $webdriver;
    }

    private function create_remote_webdriver()
    {
        $webdriver = $this->getWebDriverKind();
        //
        // First we need to make sure we don't have a conflicting session already hanging out
        // and possibly dead.  If we don't clear it, nothing will work.
        //
        $host = $GLOBALS['USERDATA']['selenium']['host_location'] . '/wd/hub';
        $currentSessions = RemoteWebDriver::getAllSessions($host);
        if($currentSessions != null && is_array($currentSessions))
        {
            foreach($currentSessions as $session)
            {
                if(strcasecmp($webdriver, $session['capabilities']['browserName']) == 0)
                {
                    if($webdriver == "safari")
                    {
                        $oldSessionDriver = RemoteWebDriver::createBySessionID($session['id'], $host);
                        if(!is_null($oldSessionDriver)) {
                            $oldSessionDriver->quit();
                        }
                    }
                }
            }
        }


        $driver = null;

        $capabilities = DesiredCapabilities::$webdriver();

        $capabilities->setCapability("nativeEvents", true);
        $capabilities->setCapability("setThrowExceptionOnScriptError", false);


        $this->remoteWebDriver = RemoteWebDriver::create(
            $host,
            $desired_capabilities = $capabilities,
            $connection_timeout_in_ms = 60000,
            $request_timeout_in_ms = 60000
        );

    }


}