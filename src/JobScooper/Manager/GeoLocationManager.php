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
use PHPMailer\PHPMailer\Exception;
use Propel\Runtime\ActiveQuery\Criteria;

class GeoLocationManager
{
    function __construct()
    {
        // make sure any cache is loaded.  this get will reload
        // all the caches if one is not yet loaded
        $this->_getCache("LocationIdsByName");


    }

    function reloadCache()
    {
        LogLine("....reloading GeoLocation table cache....", \C__DISPLAY_ITEM_DETAIL__);
        $allLocs = \JobScooper\DataAccess\GeoLocationQuery::create()
            ->select(array("GeoLocationId", "OpenStreetMapId", "DisplayName", "GeoLocationKey", "AlternateNames"))
            ->find();

        if (!array_key_exists('CACHES', $GLOBALS))
            $GLOBALS['CACHES'] = array();
        if (!array_key_exists('numFailedOSMQueries', $GLOBALS['CACHES']))
            $GLOBALS['CACHES']['numFailedOSMQueries'] = 0;

        if (!array_key_exists('allowOSMQueries', $GLOBALS['CACHES']))
            $GLOBALS['CACHES']['allowOSMQueries'] = true;

        $GLOBALS['CACHES']['LocationIdsByName'] = array();
        $GLOBALS['CACHES']['LocationIdByOsmId'] = array();
        foreach ($allLocs as $loc) {

            $GLOBALS['CACHES']['LocationIdByOsmId'][$loc['OpenStreetMapId']] = $loc['GeoLocationId'];

            $names = preg_split("/\s*\|\s*/", $loc['AlternateNames'], $limit=-1, PREG_SPLIT_NO_EMPTY);
            if(!empty($loc['DisplayName']))
                $names[] = $loc['DisplayName'];
            $names = array_unique($names);
            foreach ($names as $name) {
                $GLOBALS['CACHES']['LocationIdsByName'][cleanupSlugPart($name)] = $loc['GeoLocationId'];;
            }
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

    function lookupGeoLocationIdByOsmId($osmId)
    {
        $cache = $this->_getCache('LocationIdByOsmId');
        if(array_key_exists($osmId, $cache))
            return $cache[$osmId];
        else
        {
            return \JobScooper\DataAccess\GeoLocationQuery::create()
                ->select("GeoLocationId")
                ->filterByOpenStreetMapId($osmId)
                ->findOne();
        }
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
        $locId = $this->lookupGeoLocationIdByName($strlocname);
        if (!is_null($locId)) {
            return $this->getLocationById($locId);
        }

        try {

            if ($GLOBALS['CACHES']['allowOSMQueries'] === true)
            {
                if($GLOBALS['CACHES']['numFailedOSMQueries'] > 5)
                {
                    LogError("Exceeded max error threshold for Open Street Map queries.  Marking OSM as unusable.");
                    $GLOBALS['CACHES']['allowOSMQueries'] = false;
                    return null;
                }

                $osmPlace = getPlaceFromOpenStreetMap($strlocname);
                if (!is_null($osmPlace) && is_array($osmPlace)) {
                    if (array_key_exists(0, $osmPlace))
                        $osmPlace = $osmPlace[0];

                    $locId = $this->lookupGeoLocationIdByOsmId($osmPlace['osm_id']);
                    if (!is_null($locId)) {
                        return $this->getLocationById($locId);
                    }
                }
            }
            else
            {
                LogLine("Open Street Map querying has been disabled. Not adding location for " . $strlocname, C__DISPLAY_WARNING__);
                return null;
            }
        }
        catch (\Exception $ex)
        {
            $GLOBALS['CACHES']['numFailedOSMQueries'] = $GLOBALS['CACHES']['numFailedOSMQueries'] + 1;
            handleException($ex);
        }

        $loc = \JobScooper\DataAccess\GeoLocationQuery::create()
            ->filterByAlternateNames(array($strlocname), Criteria::CONTAINS_ALL)
            ->findOneOrCreate();
        if (is_null($loc))
            return null;

        $loc->fromDisplayString($strlocname);
        $loc->save();
        return $loc;
    }

}

