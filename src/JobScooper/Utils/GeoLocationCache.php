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

namespace JobScooper\Utils;

use JobScooper\DataAccess\GeoLocation;
use const JobScooper\DataAccess\GEOLOCATION_GEOCODE_FAILED;

use JobScooper\DataAccess\GeoLocationQuery;
use Monolog\Logger;
use Psr\Log\LogLevel;

use phpFastCache\CacheManager;


/**
 * Class GeoLocationCache
 * @package JobScooper\Manager
 */
class GeoLocationCache
{
	/**
	 * @var Logger
	 */
	protected $_logger;

	/**
	 * @var null|\phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface
	 */
	private $_cache = null;

	/**
	 * @return null|\phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface
	 * @throws \Exception
	 */
	static function getCacheInstance()
	{
		$name = "geolocation_id_lookup";
		$geoLocCache = getCacheData($name);
		if (empty($geoLocCache)) {
			$geoLocCache = new GeoLocationCache();
			setAsCacheData($name, $geoLocCache);
		}

		return $geoLocCache;
	}

	/**
	 * @param $name
	 * @param $args
	 *
	 * @return mixed
	 * @throws \BadMethodCallException
	 */
	public function __call($name, $args)
	{
		$cache = $this->_cache;
		if (!empty($cache) && method_exists($cache, $name)) {
			return call_user_func_array([$cache, $name], $args);
		} else {
			throw new \BadMethodCallException(sprintf('Method %s does not exists', $name));
		}
	}

	/**
	 * phpFastCache constructor.
	 *
	 * @param string $driver
	 * @param array  $config
	 *
	 * @throws \Exception
	 */
	public function __construct($cacheName = "geolocation_id_lookup")
	{
		$cacheDir = join(DIRECTORY_SEPARATOR, array(getOutputDirectory("caches"), $cacheName));
		$this->log(LogLevel::INFO, "Creating {$cacheName} cache in directory {$cacheDir}...");

		$config = array(
			"path" => $cacheDir
		);
		$this->_cache = CacheManager::getInstance('files', $config);
		$this->_logger = getChannelLogger("caches");

	}

	/**
	 * Logs with an arbitrary level if the logger exists.
	 *
	 * @param mixed  $level
	 * @param string $message
	 * @param array  $context
	 */
	protected function log($level, $message, array $context = [])
	{
		$context['channel'] = 'caches';

		if ($this->_logger !== null) {
			$this->_logger->log($level, $message, $context);
		}
	}

	/**
	 * @param $strAddress
	 *
	 * @return string
	 */
	function getCacheKey($strAddress)
	{
		return cleanupSlugPart($strAddress, $replacement = "_");
	}

	/**
	 * @param $strLocationName
	 *
	 * @throws \phpFastCache\Exceptions\phpFastCacheInvalidArgumentException
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	function cacheUnknownLocation($strLocationName)
	{
		if (isDebug())
			$this->log(Logger::DEBUG, "... adding unknown location '{$strLocationName}' to cache...");
		$k = $this->getCacheKey($strLocationName);
		$cacheItem = $this->_cache->getItem($k);
		$cacheItem->set(GEOLOCATION_GEOCODE_FAILED);
		$this->_cache->setItem($cacheItem);
	}

	/**
	 * @param \JobScooper\DataAccess\GeoLocation $geolocation
	 * @param array                              $arrAddlLookups
	 *
	 * @throws \phpFastCache\Exceptions\phpFastCacheInvalidArgumentException
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	function cacheGeoLocation(GeoLocation $geolocation, $newLookupString = null)
	{
		$lookups = array($newLookupString);
		$geoLocId = $geolocation->getGeoLocationId();
		$tags = ["GeoLocationId{$geolocation->getGeoLocationId()}", $geolocation->getGeoLocationKey()];

		$prevCacheItems = $this->_cache->getItemsByTag($geolocation->getGeoLocationKey());
		if(!empty($prevCacheItems))
		{
			LogDebug("{$geolocation->getGeoLocationKey()} already cached; adding additional lookup string '{$newLookupString}...");
		}
		else {
			if (isDebug())
				$this->log(Logger::DEBUG, "... adding new Geolocation {$geolocation->getGeoLocationKey()} / {$geolocation->getGeoLocationId()} to cache ...");

			$lookups = array_merge($lookups, array($geolocation->getDisplayName(), $geolocation->getGeoLocationKey()));

			$altVars = $geolocation->getVariants();
			if (!empty($altVars) && is_array($altVars))
				$lookups = array_merge($lookups, $altVars);

			$altNames = $geolocation->getAlternateNames();
			if (!empty($altNames) && is_array($altNames))
				$lookups = array_merge($lookups, $altNames);

		}

		$keys = array();
		foreach ($lookups as $look)
			$keys[] = $this->getCacheKey($look);
		$keys = array_unique($keys);

		$strKeys = getArrayDebugOutput($keys);
		$cntKeys = count($keys);
		$strTags = getArrayDebugOutput($tags);

		if (isDebug())
			$this->log(Logger::DEBUG, "... adding {$cntKeys} lookups for {$geolocation->getGeoLocationKey()} to the cache:  {$strKeys}.");

		$newCacheItems = array();
		foreach ($keys as $k) {
			$cacheItem = $this->_cache->getItem($k);
			$cacheItem->addTags($tags);
			$cacheItem->set($geoLocId);
			$this->_cache->setItem($cacheItem);
			$newCacheItems[] = $cacheItem;
		}
		$this->_cache->saveMultiple($newCacheItems);

		$this->log(Logger::DEBUG, "Cached '{$geolocation->getDisplayName()}/{$geolocation->getGeoLocationKey()} under {$cntKeys} lookups with cached tags {$strTags}.");
	}

	/**
	 * @throws \Exception
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	/*	private function _warmUpCache()
		{
			LogDebug("... adding missing locations from JobPosting table ...");
			$allLocsWithoutGeoLocs = \JobScooper\DataAccess\JobPostingQuery::create()
				->filterByGeoLocationId($geoLocationId = null, Criteria::ISNULL)
				->filterByLocation(null, Criteria::ISNOTNULL)
				->addGroupByColumn("Location")
				->select(array("jobposting.location"))
				->find()
				->getData();

			LogDebug("... " . count($allLocsWithoutGeoLocs) . " missing locations found and being added to cache...");

			foreach ($allLocsWithoutGeoLocs as $name) {
				if(!empty($name)) {
					$key = $this->getCacheKey($name);
					$item = $this->getItem($key);
					$item->set(LocationManager::UNABLE_TO_GEOCODE_ADDRESS);
					$this->save($item);

					$this->setCacheItem($key, LocationManager::UNABLE_TO_GEOCODE_ADDRESS);
				}
			}

			LogDebug("... adding Geolocations to cache ...");
			$allGeoLocs = \JobScooper\DataAccess\GeoLocationQuery::create()
				->find();

			foreach ($allGeoLocs as $loc) {
				try {
					$this->cacheGeoLocation($loc);
				} catch (phpFastCacheInvalidArgumentException $e) {
					handleException($e);
				} catch (\Psr\Cache\InvalidArgumentException $e) {
					handleException($e);
				}
			}
		}
	*/



	/**
	 * @param $lookupAddress
	 *
	 * @return \JobScooper\DataAccess\GeoLocation|null
	 * @throws \Exception
	 */
	function get($lookupAddress)
	{
		//
		// Generate the cache key and do a lookup for that item
		//
		$itemKey = $this->getCacheKey($lookupAddress);

		try {
			$itemExists = $this->_cache->hasItem($itemKey);
			$geoLocId = null;
			if($itemExists === true)
			{
				$cacheItem = $this->_cache->getItem($itemKey);
				if($cacheItem->isHit()  === true)
					$geoLocId = $cacheItem->get();
				if(!is_null($geoLocId) && $geoLocId !== false && $geoLocId != GEOLOCATION_GEOCODE_FAILED)
				{
					$geolocation = GeoLocationQuery::create()
						->findOneByGeoLocationId($geoLocId);
					return $geolocation;
				}
				elseif($geoLocId == GEOLOCATION_GEOCODE_FAILED)
				{
					return null;
				}
			}
		} catch (\Psr\Cache\InvalidArgumentException $e) {
			handleException($e, null, false);
		}


	}
}
