<?php

/**
 * Copyright 2014-16 Bryan Selner
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

    function __construct($additionalLoadDelaySeconds = 0)
    {
        $this->additionalLoadDelaySeconds = $additionalLoadDelaySeconds;
        $this->create_remote_webdriver();
    }

    function __destruct()
    {
        $this->doneWithRemoteWebDriver();
    }

    function getPageHTML($url)
    {
        $this->loadPage($url);
        return $this->driver->getPageSource();
    }

    function loadPage($url)
    {
        try
        {
            $this->driver->get($url);

            sleep(2+$this->additionalLoadDelaySeconds);

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

    protected function doneWithRemoteWebDriver()
    {
        if(!is_null($this->remoteWebDriver))
        {
            $this->remoteWebDriver->quit();
            $this->remoteWebDriver= null;
        }
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
        private function create_remote_webdriver()
        {

        $webdriver = (array_key_exists('webdriver', $GLOBALS['USERDATA']['selenium'])) ? $GLOBALS['USERDATA']['selenium']['webdriver'] : null;
        if(is_null($webdriver)) {
            $webdriver = "phantomjs";
            if (PHP_OS == "Darwin")
                $webdriver = "safari";
        }

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


        $this->remoteWebDriver = RemoteWebDriver::create($host, $desired_capabilities = $capabilities, 5000);

    }


}