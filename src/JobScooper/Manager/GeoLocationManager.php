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

    function addGeolocationToCache(GeoLocation $geoloc)
    {
        $loc = $geoloc->toArray();
        $names = $loc['AlternateNames'];
        if(!empty($loc['DisplayName']))
            $names[] = $loc['DisplayName'];
        $names = array_unique($names);
        foreach ($names as $name) {
            $GLOBALS['CACHES']['LocationIdsByName'][cleanupSlugPart($name)] = $loc['GeoLocationId'];;
        }

    }

    function reloadCache()
    {
        LogLine("....reloading GeoLocation table cache....", \C__DISPLAY_ITEM_DETAIL__);
        $allLocs = \JobScooper\DataAccess\GeoLocationQuery::create()
            ->find();

        if (!array_key_exists('CACHES', $GLOBALS))
            $GLOBALS['CACHES'] = array();
        if (!array_key_exists('numFailedGoogleQueries', $GLOBALS['CACHES']))
            $GLOBALS['CACHES']['numFailedGoogleQueries'] = 0;

        if (!array_key_exists('allowGoogleQueries', $GLOBALS['CACHES']))
            $GLOBALS['CACHES']['allowGoogleQueries'] = true;

        $GLOBALS['CACHES']['LocationIdsByName'] = array();
        foreach ($allLocs as $loc) {
            $this->addGeolocationToCache($loc);
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
        $locId = $this->lookupGeoLocationIdByName($strlocname);
        if (!is_null($locId)) {
            $loc = $this->getLocationById($locId);
        } else {
            $loc = \JobScooper\DataAccess\GeoLocationQuery::create()
                ->filterByAlternateNames(array($strlocname), Criteria::CONTAINS_ALL)
                ->findOneOrCreate();
            if (is_null($loc))
                return null;

            try {

                if ($GLOBALS['CACHES']['allowGoogleQueries'] === true) {
                    if ($GLOBALS['CACHES']['numFailedGoogleQueries'] > 5) {
                        LogError("Exceeded max error threshold for Google queries.  Marking Google as unusable.");
                        $GLOBALS['CACHES']['allowGoogleQueries'] = false;
                    }
                    else {

                        $geocode = $this->geocoder->getPlaceForLocationString($strlocname);
                        $locId = $this->lookupGeoLocationIdByName($geocode['primary_name']);
                        if (!is_null($locId)) {
                            $loc = $this->getLocationById($locId);
                            $loc->addAlternateName($strlocname);
                        }
                        else
                        {
                            $loc->fromGeocode($geocode);
                            $loc->addAlternateName($strlocname);
                        }
                    }
                } else {
                    LogLine("Google geocode querying has been disabled. Not adding location for " . $strlocname, C__DISPLAY_WARNING__);
                    return null;
                }
            } catch (\Exception $ex) {
                $GLOBALS['CACHES']['numFailedGoogleQueries'] = $GLOBALS['CACHES']['numFailedGoogleQueries'] + 1;
                handleException($ex);
                return null;
            }

        }

        if($loc->isNew())
        {
            $loc->save();
            $this->addGeolocationToCache($loc);
        }
        elseif($loc->isModified())
        {
            $loc->save();
        }
        return $loc;
    }

}

