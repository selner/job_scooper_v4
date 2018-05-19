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

use Geocoder\Exception\NoResultException;
use Geocoder\Geocoder;

use \Exception;
use JobScooper\DataAccess\GeoLocation;
use JobScooper\DataAccess\GeoLocationQuery;
use JobScooper\Utils\GeocodeApiHttpAdapter;
use JobScooper\Utils\GeocodeApiLoggedProvider;
use \JobScooper\Utils\GeoLocationCache;
use JobScooper\Logging\CSVLogHandler;
use JobScooper\Utils\GeoLocationResultsFactory;
use JobScooper\Utils\GoogleGeocoderHttpAdapter;
use JobScooper\Utils\GoogleMapsLoggedProvider;

use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;


/**
 * Class LocationManager
 * @package JobScooper\Manager
 */
class LocationManager
{
	/**
	 * @var \JobScooper\Utils\GeoLocationCache|null
	 */
	private $_geoLocCache = null;

	/**
	 * @return LocationManager
	 * @throws \Exception
	 */
	static function getLocationManager()
	{
		$loc = getCacheData('LocationManager');
		if(empty($loc)) {
			$loc = LocationManager::create();
		}
		return $loc;
	}

	/**
	 * @return LocationManager
	 * @throws \Exception
	 */
	static function create()
	{
		$loc = new LocationManager();
		setAsCacheData('LocationManager', $loc);
		return $loc;
	}

	/**
	 * @param $strAddress
	 *
	 * @return mixed|null|string|string[]
	 */
	static function scrubLocationValue($strAddress)
	{

		$lookupAddress = $strAddress;

		// Strip out any zip code (aka a 5 digit set) from the string
		//
		// Most of the time when we see a zip, it is placed in one of these three cases:
		//      Case 1  =  "Seattle WA 98102 (Cap Hill Area)"
		//      Case 2  =  "Seattle WA 98102"
		//      Case 3  =  "98102"
		//
		$zipSplits = preg_split("/\b\d{5}\b/", $lookupAddress);
		if (count($zipSplits) === 1 && !empty(trim($zipSplits[0])))
			$lookupAddress = $zipSplits[0];  // "Dallas Tx 55555" => "Dallas Tx"
		elseif (count($zipSplits) === 2) {
			$lookupAddress = str_ireplace(" area", "", $zipSplits[1]) . " " . $zipSplits[0];
		} elseif (count($zipSplits) > 2)
			$lookupAddress = join(" ", $zipSplits);

		$lookupAddress = strip_punctuation($lookupAddress);

		//
		// if name is something like "Greater London" or
		// "Greater Seattle Area", strip it down to just the
		// place name
		//
		$lookupAddress = preg_replace("/greater\s(\w+\s)area/i", "\\1", $lookupAddress, -1);
		$lookupAddress = preg_replace("/greater\s(\w+\s)/i", "\\1", $lookupAddress);
		$lookupAddress = cleanupTextValue($lookupAddress);

		$lookupAddress = preg_replace("/\s{2,}/", " ", $lookupAddress);
		return $lookupAddress;
	}

	/**
	 * @throws \Exception
	 * @return GeoLocation|null
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	function lookupAddress($strAddress)
	{
		$lookupAddress = self::scrubLocationValue($strAddress);

		//
		// Generate the cache key and do a lookup for that item
		//
		$geolocation = $this->_geoLocCache->get($lookupAddress);
		if (!empty($geolocation) && $geolocation != GEOLOCATION_GEOCODE_FAILED) {
			return $geolocation;
		}

		if (!empty($geolocation) && $geolocation == GEOLOCATION_GEOCODE_FAILED) {
			return null;
		}

		//
		// Cache miss
		//
		LogMessage("... Geolocation cache miss for " . $lookupAddress . ".  Checking database for match...");

		$geolocation = $this->_queryDBForLocation($lookupAddress);
		if(null !== $geolocation)
		{
			$this->_geoLocCache->cacheGeoLocation($geolocation, $lookupAddress);
			return $geolocation;
		}

		//
		// Cache miss
		//
		LogMessage("... Geolocation database miss for " . $lookupAddress . ".  Calling Geocoder...");

		if ($GLOBALS['CACHES']['GEOCODER_ENABLED'] !== true) {
			LogMessage("Geocoder current disabled as a result of too many error results.");

			return null;
		}

		try {
			$geoloc = $this->geocode($lookupAddress);
		} catch (Exception $e) {
			throw($e);
		} catch (\Psr\Cache\InvalidArgumentException $e) {
			throw($e);
		}

		return $geoloc;
	}

	/**
	 * @param $lookupAddress
	 *
	 * @return \JobScooper\DataAccess\GeoLocation|null
	 */
	private function _queryDBForLocation($lookupAddress)
	{
		$geoloc = GeoLocationQuery::create()
			->findOneByAlternateNames(array($lookupAddress));
		if(null !== $geoloc)
			return $geoloc;

		return null;
	}

	/**
	 * @var int
	 */
	private $countGeocodeErrors = 0;
	/**
	 * @var int
	 */
	private $countGeocodeCalls = 0;

	/**
	 * @var Geocoder
	*/
	private $_geocoder = null;


	/**
	 * @var \Monolog\Logger|null
	 */
	protected $logger = null;
	/**
	 * @var null
	 */
	private $loggerName = null;



	/**
	 * LocationManager constructor.
	 * @throws \Exception
	 */
	function __construct()
	{
		try {
			$this->_geoLocCache = GeoLocationCache::getCacheInstance();
		} catch (Exception $e) {
			handleException($e);
		} catch (InvalidArgumentException $e) {
			handleException($e);
		}

		$this->logger = $this->_initializeLogger();

		$this->_geocoder = $this->_initializeGeocoder();
	}

	/**
	 * @throws \Exception
	 */
	private function _initializeGeocoder()
	{
		LogMessage('Loading Geolocation cache ...');

		$googleApiKey = getConfigurationSetting('google_maps_api_key');
		if (is_empty_value($googleApiKey) || !is_string($googleApiKey)) {
			throw new Exception('No Google Geocode API key found in configuration.  Instructions for getting an API key are at https://developers.google.com/maps/documentation/geocoding/get-api-key.');
		}

		$regionBias = null;
		$country_codes = getConfigurationSetting('country_codes');
		if (null !== $country_codes && is_array($country_codes)) {
			$regionBias = $country_codes[0];
		}

		$geocoder = new Geocoder();

		$geoapi_srvr = getConfigurationSetting('geocodeapi_server');
		if (!empty($geoapi_srvr))
		{
			$curl = new GeocodeApiHttpAdapter();
			$geocoder->registerProviders(array(
				new GeocodeApiLoggedProvider(
					$adapter = $curl,
					$locale = 'en',
					$region = $regionBias,
					$apiKey = $googleApiKey,
					$server = $geoapi_srvr,
					$logger = $this->logger
				)
			));
		}
		else
		{
			$curl = new GoogleGeocoderHttpAdapter();
			$geocoder->registerProviders(array(
				new GoogleMapsLoggedProvider(
					$adapter = $curl,
					$locale = 'en',
					$region = $regionBias,
					$useSsl = true,
					$apiKey = $googleApiKey,
					$logger = $this->logger
				)
			));
		}
		$geocoder->setResultFactory(new GeoLocationResultsFactory());
		return $geocoder;
	}

	/**
	 * @return \Monolog\Logger
	 * @throws \Exception
	 */
	private function _initializeLogger()
	{
		$this->loggerName = 'geocode_calls';
		$logger = new Logger($this->loggerName);
		$now = getNowAsString('-');
		$csvlog = getOutputDirectory('logs') . DIRECTORY_SEPARATOR . "{$this->loggerName}-{$now}-geocode_api_calls.csv";
		$fpcsv = fopen($csvlog, 'w');
		$handler = new CSVLogHandler($fpcsv, Logger::INFO);
		$logger->pushHandler($handler);

		LogMessage("Geocode API logging started to CSV file at {$csvlog}");

		return $logger;

	}

	/**
	 * @param $strAddress
	 *
	 * @return \JobScooper\DataAccess\GeoLocation|null
	 * @throws \Exception
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	private function geocode($strAddress)
	{
		if ($this->countGeocodeErrors >= 5) {
			LogMessage('Google Geocoding is disabled because of too many error results during this run.');
			$GLOBALS['CACHES']['GEOCODER_ENABLED'] = false;

			return null;
		}

		$geocodeResult = null;
		try {
			$this->countGeocodeCalls += 1;
			$geocodeResult = $this->_geocoder->geocode($strAddress);
		} catch (NoResultException $ex) {
			LogDebug("No geocode result was found for {$strAddress}:  " . $ex->getMessage(), null, $this->loggerName);
			$geocodeResult = null;
		} catch (Exception $ex) {
			$this->countGeocodeErrors += 1;
			LogError("Failed to geocode {$strAddress}: " . $ex->getMessage(), null, $ex, $this->loggerName);
			throw $ex;
		}

		if (is_empty_value($geocodeResult) ) {
			$geolocation = $geocodeResult;
			try {
				$lookup = LocationManager::scrubLocationValue($strAddress);
				$this->_geoLocCache->cacheGeoLocation($geolocation, $lookup);
			} catch (\InvalidArgumentException $e) {
				handleException($e);
			} catch (\Psr\Cache\InvalidArgumentException $e) {
				handleException($e);
			}

			return $geolocation;
		} else {
			$lookup = LocationManager::scrubLocationValue($strAddress);
			$this->_geoLocCache->cacheUnknownLocation($lookup);

			return null;
		}
	}
}
