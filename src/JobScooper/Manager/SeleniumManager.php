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
use Exception;
use Facebook\WebDriver\Exception\WebDriverCurlException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use JobScooper\Utils\PropertyObject;

use Facebook\WebDriver\Remote\DesiredCapabilities;

/**
 * Class SeleniumManager
 * @package JobScooper\Manager
 */
class SeleniumManager extends PropertyObject
{
	/**
	 * @var RemoteWebDriver|null
	 */
	private $remoteWebDriver = null;
    private $additionalLoadDelaySeconds = null;
    private $_settings = null;

	/**
	 * SeleniumManager constructor.
	 *
	 * @param int $additionalLoadDelaySeconds
	 *
	 * @throws \Exception
	 */
	function __construct($additionalLoadDelaySeconds = 0)
    {
        $this->additionalLoadDelaySeconds = $additionalLoadDelaySeconds;
        $this->_settings = getConfigurationSetting('selenium');
		if(empty($this->_settings))
			$this->_settings = array();
    }

	/**
	 *
	 * @throws \Exception
	 */
	function __destruct()
    {
        $this->doneWithRemoteWebDriver();

    }

	/**
	 * @param      $url
	 * @param bool $recursed
	 *
	 * @return string
	 * @throws \Exception
	 */
	function getPageHTML($url, $recursed = false)
    {
        try {
            $driver = $this->get_driver();

            $this->loadPage($url);

            $src = $driver->getPageSource();

            return $src;
        } catch (WebDriverCurlException $ex) {
            handleException($ex, null, true);
        } catch (WebDriverException $ex) {
            handleException($ex, null, true);
        } catch (\Exception $ex) {
            handleException($ex, null, true);
        }
    }

	/**
	 * @param $url
	 *
	 * @throws \Exception
	 */
	function loadPage($url)
    {
        try {
            $driver = $this->get_driver();
            if (strncmp($driver->getCurrentURL(), $url, strlen($url)) != 0) {
                $driver->get($url);
                sleep(2 + $this->additionalLoadDelaySeconds);
            }
        } catch (WebDriverCurlException $ex) {
            handleException($ex, "Error retrieving Selenium page at {$url}", false);
        } catch (WebDriverException $ex) {
            handleException($ex, "Error retrieving Selenium page at {$url} ", false);
        } catch (Exception $ex) {
            handleException($ex, "Error retrieving Selenium page at {$url}", false);
        }
    }

	/**
	 *
	 * @throws \Exception
	 */
	function done()
    {
        $this->doneWithRemoteWebDriver();
    }

	/**
	 * @throws \Exception
	 */
	function waitForAjax($framework='jquery')
	{
		// javascript framework
		switch($framework){
			case 'jquery':
				$code = "return jQuery.active;"; break;
			case 'prototype':
				$code = "return Ajax.activeRequestCount;"; break;
			case 'dojo':
				$code = "return dojo.io.XMLHTTPTransport.inFlight.length;"; break;
			default:
				throw new Exception('Not supported framework');
		}

		// wait for at most 30s, retry every 2000ms (2s)
		$driver = $this->get_driver();
		$driver->wait(30, 2000)->until(
			function ($driver, $code) {
				return !($driver->executeScript($code));
			}
		);
	}




	/**
	 * @throws \Exception
	 */
	protected function doneWithRemoteWebDriver()
    {
//	    $logs_browse = $this->remoteWebDriver->manage()->getLog("browser");
//	    $logs_client = $this->remoteWebDriver->manage()->getLog("client");
//
//	    LogMessage("Selenium browser log:  " . getArrayDebugOutput($logs_browse));
//	    LogMessage("Selenium client log:  " . getArrayDebugOutput($logs_client));

	    try {

            if(!is_null($this->remoteWebDriver))
            {
                $this->remoteWebDriver->quit();
            }
        } catch (WebDriverCurlException $ex) {
            handleException($ex, "Failed to quit Webdriver: ", false);
        } catch (WebDriverException $ex) {
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

    /**
     * Get the current webdriver or create a new one if needed.
     *
     * @throws \Exception
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver|null
     *
     */
    function get_driver()
    {
        try {
            if (is_null($this->remoteWebDriver))
                $this->create_remote_webdriver();
            return $this->remoteWebDriver;

        } catch (WebDriverCurlException $ex) {
            $this->create_remote_webdriver();
            return $this->remoteWebDriver;

        } catch (WebDriverException $ex) {
            $this->create_remote_webdriver();
            return $this->remoteWebDriver;

        } catch (Exception $ex) {
            $this->create_remote_webdriver();
            return $this->remoteWebDriver;
        }
    }

	/**
	 * @return mixed|null|string
	 */
	function getWebDriverKind()
    {
        $webdriver = (array_key_exists('webdriver', $this->_settings)) ? $this->_settings['webdriver'] : null;
        if(is_null($webdriver)) {
            $webdriver = "phantomjs";
            if (PHP_OS == "Darwin")
                $webdriver = "safari";
        }

        return $webdriver;
    }

	/**
	 * @return null|\Facebook\WebDriver\Remote\RemoteWebDriver
	 * @throws \Exception
	 */
	private function create_remote_webdriver()
    {
        $hubUrl = $this->_settings['host_location'] . '/wd/hub';
        LogMessage("Creating Selenium remote web driver to host {$hubUrl}...");

        try {

            $webdriver = $this->getWebDriverKind();
            $hubUrl = $this->_settings['host_location'] . '/wd/hub';
            $driver = null;

	        /** @var DesiredCapabilities $capabilities */
	        $capabilities = call_user_func(array("Facebook\WebDriver\Remote\DesiredCapabilities", $webdriver));

//
//	        $capabilities->setCapability(
//		        'moz:firefoxOptions',
//		        ['args' => ['-headless']]
//	        );
//	        $capabilities->setCapability(
//		        'moz:webdriverClick',
//		        false
//	        );

//	        $prof = $capabilities->getCapability(FirefoxDriver::PROFILE);
//
//	        $fflog = getOutputDirectory('debug') . "/firefox_webdriver_log.csv";
//	        $prof->setPreference("setEnableNativeEvents", true);
//	        $prof->setPreference("webdriver.log.file", $fflog);
//	        $prof->setPreference("webdriver.log.driver", "INFO");
//	        $capabilities->setCapability(FirefoxDriver::PROFILE, $prof);


	        $capabilities->setCapability('acceptInsecureCerts', true);
	        $capabilities->setCapability("setThrowExceptionOnScriptError", false);
            $capabilities->setCapability("unexpectedAlertBehaviour", "dismiss");
            $capabilities->setCapability(WebDriverCapabilityType::ACCEPT_SSL_CERTS, true);
            $capabilities->setCapability(WebDriverCapabilityType::APPLICATION_CACHE_ENABLED, true);
            $capabilities->setCapability(WebDriverCapabilityType::CSS_SELECTORS_ENABLED, true);
            $capabilities->setCapability(WebDriverCapabilityType::WEB_STORAGE_ENABLED, true);
            $capabilities->setCapability(WebDriverCapabilityType::NATIVE_EVENTS, true);
            $capabilities->setCapability(WebDriverCapabilityType::HANDLES_ALERTS, true);
            $capabilities->setCapability(WebDriverCapabilityType::DATABASE_ENABLED, true);
            $capabilities->setCapability(WebDriverCapabilityType::LOCATION_CONTEXT_ENABLED, true);

            $this->remoteWebDriver = RemoteWebDriver::create(
                $hubUrl,
                $desired_capabilities = $capabilities,
                $connection_timeout_in_ms = 60000,
                $request_timeout_in_ms = 60000
            );

//
//	        $window = new WebDriverDimension(1024, 768);
//	        $this->remoteWebDriver->manage()->window()->setSize($window);

	        LogMessage("Remote web driver instantiated.");

            return $this->remoteWebDriver;
        } catch (WebDriverCurlException $ex) {
            handleException($ex, "Failed to get webdriver from {$hubUrl}: ", true);
        } catch (WebDriverException $ex) {
            handleException($ex, "Failed to get webdriver from {$hubUrl}: ", true);
        } catch (Exception $ex) {
            handleException($ex, "Failed to get webdriver from {$hubUrl}: ", true);
        }
        return null;
    }


}
