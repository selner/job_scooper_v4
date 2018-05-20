<?php
/**
 * Copyright 2014-18 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the 'License'); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace JobScooper\Utils;

use JobScooper\DataAccess\GeoLocation;
use JobScooper\DataAccess\GeoLocationQuery;
use JobScooper\Manager\LocationManager;
use Monolog\Logger;
use \Psr\Cache\InvalidArgumentException;
use Propel\Runtime\ActiveQuery\Criteria;
use Psr\Log\LogLevel;

use phpFastCache\CacheManager;

const C_CACHE_ITEM_EXPIRATION_SECS = 604800; # 7 * 24 * 60 * 60

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
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public static function getCacheInstance()
    {
        $name = 'geolocation_id_lookup';
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
            throw new \BadMethodCallException(sprintf("Method {$name} does not exists"));
        }
    }

    /**
     * phpFastCache constructor.
     *
     * @param string $cacheName
     *
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function __construct($cacheName = 'geolocation_id_lookup')
    {
        $cacheDir = join(DIRECTORY_SEPARATOR, array(getOutputDirectory('caches'), $cacheName));
        $this->log(LogLevel::INFO, "Creating {$cacheName} cache in directory {$cacheDir}...");

        $config = array(
            'path' => $cacheDir
        );
        $this->_cache = CacheManager::getInstance('files', $config);
        $this->_logger = getChannelLogger('caches');

        $this->_warmUpCache();
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
    public function getCacheKey($strAddress)
    {
        return strScrub($strAddress, FOR_LOOKUP_VALUE_MATCHING);
    }

    /**
     * @param $strLocationName
     *
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function cacheUnknownLocation($strLocationName)
    {
        $k = $this->getCacheKey($strLocationName);
        LogDebug("... adding unknown location {$k} = '{$strLocationName}' to cache ");
        $this->cacheGeoLocationItem($k, GEOLOCATION_GEOCODE_FAILED);
    }

    /**
	 * @param $key
	 * @param $value
	 * @param array $tags
	 *
	 * @return \phpFastCache\Core\Item\ExtendedCacheItemInterface
	 * @throws \Exception
	*/
    private function cacheGeoLocationItem($key, $value, $tags = array())
    {
    	try {
	        $cacheItem = $this->_cache->getItem($key);
            $cacheItem->addTags($tags);
            $cacheItem->set($value);
            $cacheItem->expiresAfter(C_CACHE_ITEM_EXPIRATION_SECS);
            $this->_cache->setItem($cacheItem);

            return $this->_cache->getItem($key);
        }
        catch(\phpFastCache\Exceptions\phpFastCacheInvalidArgumentException $ex) {
			throw new \Exception("Failed to set cache item with key={$key}, value={$value}, tags=". getArrayDebugOutput($tags), $ex->getCode(), $ex);;
        }
    }

    /**
     * @param \JobScooper\DataAccess\GeoLocation $geolocation
     * @param string $newLookupString
     *
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function cacheGeoLocation(GeoLocation $geolocation, $newLookupString = null)
    {
        $lookups = array($newLookupString);
        $tags = [$geolocation->getGeoLocationKey()];

        $prevCacheItems = $this->_cache->getItemsByTag($geolocation->getGeoLocationKey());
        if (!empty($prevCacheItems)) {
            LogDebug("{$geolocation->getGeoLocationKey()} already cached; adding additional lookup string '{$newLookupString}'...");
        } else {
            if (isDebug()) {
                $this->log(Logger::DEBUG, "... adding new Geolocation {$geolocation->getGeoLocationKey()} / {$geolocation->getGeoLocationId()} to cache ...");
            }

            $lookups = array_merge($lookups, array($geolocation->getDisplayName(), $geolocation->getGeoLocationKey()));

            $altVars = $geolocation->getVariants();
            if (!empty($altVars) && is_array($altVars)) {
                $lookups = array_merge($lookups, $altVars);
            }

            $altNames = $geolocation->getAlternateNames();
            if (!empty($altNames) && is_array($altNames)) {
                $lookups = array_merge($lookups, $altNames);
            }
        }

        foreach ($lookups as $k => $l) {
            $lookups[$k] = LocationManager::scrubLocationValue($l);
        }
        $lookups = array_iunique(array_values($lookups));

        $keys = array();
        foreach ($lookups as $look) {
            $keys[] = $this->getCacheKey($look);
        }
        $keys = array_iunique($keys);

        $strKeys = getArrayDebugOutput($keys);
        $cntKeys = count($keys);
        $strTags = getArrayDebugOutput($tags);

        if (isDebug()) {
            $this->log(Logger::DEBUG, "... adding {$cntKeys} lookups for {$geolocation->getGeoLocationKey()} to the cache:  {$strKeys}.");
        }

        $newCacheItems = array();
        foreach ($keys as $k) {
        	$cacheItem = $this->cacheGeoLocationItem($k, $geolocation->getGeoLocationId(), $tags);
            $newCacheItems[] = $cacheItem;
        }
        $this->_cache->saveMultiple($newCacheItems);

        $strLookups = getArrayDebugOutput($lookups);
        $this->log(Logger::DEBUG, "Added {$cntKeys} lookups ({$strLookups}) to cache for data '{$geolocation->getDisplayName()}/{$geolocation->getGeoLocationKey()}' with tags {$strTags}.");
    }

    /**
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidArgumentException
     * @throws InvalidArgumentException
     */
    private function _warmUpCache()
    {
        LogDebug('... adding missing locations from JobPosting table ...');
        $allLocsWithoutGeoLocs = \JobScooper\DataAccess\JobPostingQuery::create()
            ->filterByGeoLocationId($geoLocationId = null, Criteria::ISNULL)
            ->filterByLocation(null, Criteria::ISNOTNULL)
            ->addGroupByColumn('Location')
            ->select(array('jobposting.location'))
            ->find()
            ->getData();

        LogDebug('... ' . count($allLocsWithoutGeoLocs) . ' missing locations found and being added to cache...');

        foreach ($allLocsWithoutGeoLocs as $name) {
            if (!empty($name)) {
                $this->cacheUnknownLocation($name);
            }
        }

        //		LogDebug("... adding Geolocations to cache ...');
//		$allGeoLocs = GeoLocationQuery::create()
//			->find();
//
//		foreach ($allGeoLocs as $loc) {
//			try {
//				$this->cacheGeoLocation($loc);
//			} catch (phpFastCacheInvalidArgumentException $e) {
//				handleException($e);
//			} catch (InvalidArgumentException $e) {
//				handleException($e);
//			}
//		}
    }


    /**
     * @param $lookupAddress
     *
     * @return \JobScooper\DataAccess\GeoLocation|int|false
     * @throws \Exception
     */
    public function get($lookupAddress)
    {
        $geolocation = null;

        //
        // Generate the cache key and do a lookup for that item
        //
        $itemKey = $this->getCacheKey($lookupAddress);
        try {
            $itemExists = $this->_cache->hasItem($itemKey);
            $geoLocId = null;
            if ($itemExists === true) {
                $cacheItem = $this->_cache->getItem($itemKey);
                if ($cacheItem->isHit() === true) {
                    $geoLocId = $cacheItem->get();
                }
                if (!is_empty_value($geoLocId) && $geoLocId !== false && $geoLocId != GEOLOCATION_GEOCODE_FAILED) {
                    $geolocation = GeoLocationQuery::create()
                        ->findOneByGeoLocationId($geoLocId);

                    return $geolocation;
                } elseif ($geoLocId == GEOLOCATION_GEOCODE_FAILED) {
                    return GEOLOCATION_GEOCODE_FAILED;
                }
            }
            $this->_cache->deleteItem($itemKey);
            $this->_cache->commit();
            return false;
        } catch (InvalidArgumentException $e) {
            handleException($e, null, false);
        }
        return false;
    }
}
