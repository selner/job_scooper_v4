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

use Geocoder\Exception\QuotaExceededException;
use Geocoder\Result\ResultInterface;

use Cache\Adapter\PHPArray\ArrayCachePool;
use \InvalidArgumentException;
use \Exception;
use JobScooper\DataAccess\GeoLocation;
use Propel\Runtime\ActiveQuery\Criteria;


class LocationManager
{

    static function getLocationManager()
    {
        return $GLOBALS['CACHES']['LOCATION_MANAGER'];
    }

    static function create()
    {
        $GLOBALS['CACHES']['LOCATION_MANAGER'] = new LocationManager();
    }





    function __construct()
    {
        $this->_pool = new ArrayCachePool();
        $this->_geocoder = new GeocodeManager();

        $this->__initialize__();
    }

    private function __initialize__()
    {
        LogLine("Loading Geolocation cache ...", \C__DISPLAY_ITEM_DETAIL__);

        $allLocsWithoutGeoLocs = \JobScooper\DataAccess\JobPostingQuery::create()
            ->select(array("location_display_value"))
            ->filterByGeoLocationId($geoLocationId = null, Criteria::ISNULL)
            ->filterByLocationDisplayValue(null, Criteria::ISNOTNULL)
            ->find()
            ->getData();

        foreach ($allLocsWithoutGeoLocs as $name) {
            $key = $this->getCacheKeyForAddress($name);
            $this->setCacheItem($key, LocationManager::UNABLE_TO_GEOCODE_ADDRESS);
        }

        $allGeoLocs = \JobScooper\DataAccess\GeoLocationQuery::create()
            ->find();

        foreach ($allGeoLocs as $loc) {
            $this->setCachedGeoLocation($loc);
        }


        LogLine("... adding missing locations from JobPosting table ...", \C__DISPLAY_ITEM_DETAIL__);

    }

    function getId($strId)
    {
        return \JobScooper\DataAccess\GeoLocationQuery::create()
            ->filterByGeoLocationId($strId)
            ->findOne();
    }

    function getAddress($strAddress)
    {
        //
        // Generate the cache key and do a lookup for that item
        //
        $itemKey = $this->getCacheKeyForAddress($strAddress);

        //
        // return the item if we found it
        //
        if ($this->_pool->has($itemKey)) {
            $itemVal = $this->_pool->get($itemKey);
            LogDebug("... Geolocation cache hit for " . $strAddress);
            if (!empty($itemVal)) {
                return $this->returnGeoLocationFromCacheItem($itemKey, $itemVal);
            }
        }

        // Cache miss
        //
        LogLine("... Geolocation cache miss for " . $strAddress . ".  Calling Geocoder...");

        if ($GLOBALS['CACHES']['GEOCODER_ENABLED'] !== true) {
            LogLine("Geocoder current disabled as a result of too many error results.");
            return null;
        }
        else
        {
            return $this->geocode($strAddress);
        }

    }


    const UNABLE_TO_GEOCODE_ADDRESS = -1;
    private $isGeocodingDisabled = false;
    private $countGeocodeErrors = 0;
    private $countGeocodeCalls = 0;
    private $_pool = null;
    private $_geocoder = null;

    private function getCacheKeyForAddress($strAddress)
    {
        return cleanupSlugPart($strAddress, $replacement = "_");
    }

    private function returnGeoLocationFromCacheItem($itemKey, $itemVal)
    {
        // make sure we've just got an ID like we thought
        if (is_numeric($itemVal) && $itemVal != LocationManager::UNABLE_TO_GEOCODE_ADDRESS) {
            return \JobScooper\DataAccess\GeoLocationQuery::create()
                ->filterByGeoLocationId($itemVal)
                ->findOne();
        } elseif ($itemVal == LocationManager::UNABLE_TO_GEOCODE_ADDRESS) {
            // returning an address that Google previously failed to geocode
            return null;
        }

        throw new InvalidArgumentException("Cache key " . $itemKey . " returned unexpected data from the cache.");
    }



    private function setCacheItem($key, $value)
    {
        if (!$this->_pool->set($key, $value, $ttl = 60 * 60 * 24 * 7)) {
            LogLine("Failed to save Geolocation to the cache under key " . $key);
        }
    }

    private function setCachedGeoLocation(GeoLocation $geolocation)
    {
        LogDebug("... adding new Geolocation " . $geolocation->getGeoLocationKey() . " / " . $geolocation->getGeoLocationId() . " to cache ...");
        $geoLocId = $geolocation->getGeoLocationId();
        $key = $this->getCacheKeyForAddress($geolocation->getDisplayName());
        $this->setCacheItem($key, $geoLocId);

        $altVars = $geolocation->getVariants();
        $altNames = $geolocation->getAlternateNames();

        $otherNamesToKey = array_unique(array_merge($altNames, $altVars));

        foreach ($otherNamesToKey as $strVar) {
            $key = $this->getCacheKeyForAddress($strVar);
            $this->setCacheItem($key, $geoLocId);
        }
    }

    private function geocode($strAddress)
    {
        if ($this->countGeocodeErrors >= 5) {
            LogLine("Google Geocoding is disabled because of too many error results during this run.");
            $GLOBALS['CACHES']['GEOCODER_ENABLED'] = false;
            return null;
        }

        $geocodeResult = null;
        try {
            $this->countGeocodeCalls += 1;
            $geocodeResult = $this->_geocoder->geocode($strAddress);
        } catch (\Geocoder\Exception\NoResultException $ex) {
            LogDebug("No geocode result was found for " . $strAddress . ".  Details: " . $ex->getMessage());
            $geocodeResult = null;
        } catch (Exception $ex) {
            $this->countGeocodeErrors += 1;
            LogError("Failed to geocode '" . $strAddress . "''.  Details: " . $ex->getMessage());
            throw $ex;
        }

        if (!empty($geocodeResult)) {
            $arrGeocode = $geocodeResult->toArray();

            $geolocation = \JobScooper\DataAccess\GeoLocationQuery::create()
                ->filterByDisplayName($arrGeocode['primary_name'])
                ->findOneOrCreate();

            if (empty($geolocation))
                throw new \Exception("Could not find or create a new geolocation in the database.");

            LogDebug("... " . $geolocation->isNew() ? "saving new " : "updating found Geolocation for " . $strAddress . " to database...");

            //
            // Update the geolocation with the facts from the
            // geocode result
            //
            $geolocation->fromArray($arrGeocode);
            $isNewRecord = $geolocation->isNew();
            $geolocation->save();

            if ($isNewRecord) {
                $geolocation->addAlternateName($strAddress);
                $this->setCachedGeoLocation($geolocation);
            }
            return $geolocation;
        } else {
            $key = $this->getCacheKeyForAddress($strAddress);
            $this->setCacheItem($key, LocationManager::UNABLE_TO_GEOCODE_ADDRESS);
            return null;
        }
    }


    /**
     * @param $strLocation
     */
    private function extendGeocodeDataResult(ResultInterface $geocode)
    {
        if (!empty($geocode)) {
            $arrGeoResult = $geocode->toArray();

            $arrGeoResult['display_value'] = $arrGeoResult['formatted_address'];

            if (array_key_exists('city', $arrGeoResult))
                $arrGeoResult['place'] = $arrGeoResult['city'];
            else
                $arrGeoResult['place'] = null;

//            if (array_key_exists('adminLevels', $arrGeoResult) && is_array($arrGeoResult) && count($arrGeoResult) > 0) {
//                if (count($arrGeoResult['adminLevels']) >= 1) {
//                    $arrGeoResult['region'] = $arrGeoResult['adminLevels'][1]['name'];
//                    $arrGeoResult['regioncode'] = $arrGeoResult['adminLevels'][1]['code'];
//                }
//                if (count($arrGeoResult['adminLevels']) >= 2) {
//                    $arrGeoResult['county'] = $arrGeoResult['adminLevels'][2]['name'];
//                }
//            }
//

//            $arrGeoResult['key'] = cleanupSlugPart($this->formatAddress($geocode, "%C-%R-%L"));
//            $arrGeoResult['key'] = cleanupSlugPart($this->formatAddress($geocode, "%C-%R-%L"));

            $fmt = array();
            if (!is_null($arrGeoResult['place']))
                $fmt[] = "%L";

            if (strcasecmp($arrGeoResult['countrycode'], 'US') == 0) {
                if (!is_null($arrGeoResult['regioncode']))
                    $fmt[] = "%r %c";
                else
                    $fmt[] = "%c";
            } else {
                if (!is_null($arrGeoResult['region']))
                    $fmt[] = "%R %c";
                else
                    $fmt[] = "%c";
            }

//            $arrGeoResult['primary_name'] = $this->formatAddress($geocode, join(", ", $fmt));
//
            return $geocode->fromArray($arrGeoResult);
        }
    }
}
