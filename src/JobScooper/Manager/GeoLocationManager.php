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

use JobScooper\DataAccess\GeoLocation;
use Propel\Runtime\ActiveQuery\Criteria;

const GEOLOCATION_DOES_NOT_EXIST = -1;

class GeoLocationManager
{

    protected $geocoder = null;

    function __construct()
    {
        // make sure any cache is loaded.  this get will reload
        // all the caches if one is not yet loaded
        $this->_getCache("LocationIdsByName");

        $this->geocoder = new GeocodeManager();

    }

    function addGeolocationToCache(GeoLocation $geoloc, $origLocValue=null)
    {
        $loc = $geoloc->toArray();
        $names = $loc['AlternateNames'];
        if(!is_null($loc['DisplayName']) && strlen($loc['DisplayName']) > 0)
            $names[] = $loc['DisplayName'];
        if(!is_null($origLocValue) && strlen($origLocValue) > 0)
            $names[] = $origLocValue;

        $names = array_unique($names);
        foreach ($names as $name) {
            $GLOBALS['CACHES']['LocationIdsByName'][cleanupSlugPart($name)] = $loc['GeoLocationId'];;
        }

    }

    function reloadCache()
    {
        LogLine("....reloading GeoLocation table cache....", \C__DISPLAY_ITEM_DETAIL__);
        $allGeoLocs = \JobScooper\DataAccess\GeoLocationQuery::create()
            ->find();

        $allLocsWithoutGeoLocs = \JobScooper\DataAccess\JobPostingQuery::create()
//            ->select(array("jobposting_id", "location_display_value"))
            ->select(array("location_display_value"))
            ->filterByGeoLocationId(null,Criteria::ISNULL)
            ->filterByLocationDisplayValue(null,Criteria::ISNOTNULL)
            ->find();

        if (!array_key_exists('CACHES', $GLOBALS))
            $GLOBALS['CACHES'] = array();
        if (!array_key_exists('numFailedGoogleQueries', $GLOBALS['CACHES']))
            $GLOBALS['CACHES']['numFailedGoogleQueries'] = 0;

        if (!array_key_exists('allowGoogleQueries', $GLOBALS['CACHES']))
            $GLOBALS['CACHES']['allowGoogleQueries'] = true;

        $GLOBALS['CACHES']['LocationIdsByName'] = array();
        foreach ($allGeoLocs as $loc) {
            $this->addGeolocationToCache($loc);
        }

        foreach ($allLocsWithoutGeoLocs as $name) {
            $GLOBALS['CACHES']['LocationIdsByName'][cleanupSlugPart($name)] = GEOLOCATION_DOES_NOT_EXIST;
        }


    }

    private function _getCache($cacheKey)
    {
        if(!array_key_exists('CACHES', $GLOBALS) || !array_key_exists($cacheKey, $GLOBALS['CACHES']) || is_null($GLOBALS['CACHES'][$cacheKey]))
            $this->reloadCache();

        return $GLOBALS['CACHES'][$cacheKey];

    }

    function getLocationById($locationId)
    {
        return \JobScooper\DataAccess\GeoLocationQuery::create()
            ->filterByGeoLocationId($locationId)
            ->findOne();
    }

    function lookupGeoLocationIdByName($strlocname)
    {
        $cache = $this->_getCache('LocationIdsByName');
        $slug = cleanupSlugPart($strlocname);
        if(array_key_exists($slug, $cache))
            return $cache[$slug];

        return null;
    }

    public function findOrCreateGeoLocationByName($strlocname)
    {
        $loc = null;
        $replaceNoLocInCache = false;

        $locId = $this->lookupGeoLocationIdByName($strlocname);
        if (!is_null($locId) || $locId == GEOLOCATION_DOES_NOT_EXIST) {
            $loc = $this->getLocationById($locId);
        } else {
            $loc = \JobScooper\DataAccess\GeoLocationQuery::create()
                ->filterByAlternateNames(array($strlocname), Criteria::CONTAINS_ALL)
                ->findOneOrCreate();
            if (is_null($loc))
                return null;

            try {

                if($GLOBALS['CACHES']['allowGoogleQueries'] === false) {
                    LogLine("Google geocode querying has been disabled. Not adding location for " . $strlocname, C__DISPLAY_WARNING__);
                    return null;
                }
                elseif($GLOBALS['CACHES']['numFailedGoogleQueries'] > 5)
                {
                    LogError("Exceeded max error threshold for Google queries.  Marking Google as unusable.");
                    $GLOBALS['CACHES']['allowGoogleQueries'] = false;
                    $loc->delete();
                    $loc = null;
                    return null;
                }

                $geocode = $this->geocoder->getPlaceForLocationString($strlocname);
                if(is_null($geocode)) {
                    $loc->delete();
                    $loc = null;
                }
                else
                {
                    $locId = $this->lookupGeoLocationIdByName($geocode['primary_name']);
                    if (!is_null($locId) && $locId != GEOLOCATION_DOES_NOT_EXIST) {
                        $loc = $this->getLocationById($locId);
                        $loc->addAlternateName($strlocname);
                    } else {
                        if ($locId == GEOLOCATION_DOES_NOT_EXIST)
                            $replaceNoLocInCache = true;
                        $loc->fromGeocode($geocode);
                        $loc->addAlternateName($strlocname);
                    }
                }
            } catch (\Exception $ex) {
                $GLOBALS['CACHES']['numFailedGoogleQueries'] = $GLOBALS['CACHES']['numFailedGoogleQueries'] + 1;
                handleException($ex);
                return null;
            }

        }

        if(!is_null($loc)) {
            if ($loc->isNew()) {
                $loc->save();
                if ($replaceNoLocInCache === true)

                    $this->addGeolocationToCache($loc, $origLocValue = $strlocname);
            } elseif ($loc->isModified()) {
                $loc->save();
                $GLOBALS['CACHES']['LocationIdsByName'][cleanupSlugPart($strlocname)] = $loc->getGeoLocationId();
            }
        }
        return $loc;
    }

}

