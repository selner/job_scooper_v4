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




function reloadLocationCache()
{
    LogLine("....reloading Location table cache....", \C__DISPLAY_ITEM_DETAIL__);
    $allLocs = \JobScooper\DataAccess\LocationQuery::create()
        ->find();

    if (!array_key_exists('CACHE', $GLOBALS))
        $GLOBALS['CACHE'] = array();

    $GLOBALS['CACHE']['LocationsById'] = array();
    $GLOBALS['CACHE']['LocationsByOsmId'] = array();
    foreach ($allLocs as $loc) {
        $GLOBALS['CACHE']['LocationsById'][$loc->getLocationId()] = $loc;
        $GLOBALS['CACHE']['LocationsByOsmId'][$loc->getOpenStreetMapId()] = $loc;
    }
}


function getLocationById($locationId)
{
    if(!(array_key_exists('CACHE', $GLOBALS) && is_array($GLOBALS['CACHE']['LocationsById']) && array_key_exists('LocationsById', $GLOBALS['CACHE'])))
        reloadLocationCache();

    if(!array_key_exists($locationId, $GLOBALS['CACHE']['LocationsById']))
        return \JobScooper\DataAccess\LocationQuery::create()
            ->filterByLocationId($locationId)
            ->findOne();

    return $GLOBALS['CACHE']['LocationsById'][$locationId];
}

function getLocationByOsmId($osmId)
{
    if(!array_key_exists('CACHE', $GLOBALS) || !array_key_exists('LocationsByOsmId', $GLOBALS['CACHE']))
        reloadLocationCache();

    if(!array_key_exists($osmId, $GLOBALS['CACHE']['LocationsByOsmId']))
    {
        return \JobScooper\DataAccess\LocationQuery::create()
            ->filterByOpenStreetMapId($osmId)
            ->findOne();
    }

    return $GLOBALS['CACHE']['LocationsByOsmId'][$osmId];
}


function reloadLocationNamesCache()
{
    LogLine("....reloading Location Names Lookup table cache....", \C__DISPLAY_ITEM_DETAIL__);
    $allLocs = \JobScooper\DataAccess\LocationNamesQuery::create()
        ->find();

    if (!array_key_exists('CACHE', $GLOBALS))
        $GLOBALS['CACHE'] = array();

    $GLOBALS['CACHE']['LocationAlternateNames'] = array();

    foreach ($allLocs as $loc) {
        $GLOBALS['CACHE']['LocationAlternateNames'][$loc->getSlug()] = $loc;
    }
}
