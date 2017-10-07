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




function reloadJobLocationCache()
{
    LogLine("....reloading Job Location table cache....", \C__DISPLAY_ITEM_DETAIL__);
    $allLocs = \JobScooper\DataAccess\JobLocationQuery::create()
        ->find();

    if (!array_key_exists('CACHE', $GLOBALS))
        $GLOBALS['CACHE'] = array();
    $GLOBALS['CACHE']['JobLocationsById'] = array();
    $GLOBALS['CACHE']['JobLocationsByOsm'] = array();
    foreach ($allLocs as $loc) {
        $GLOBALS['CACHE']['JobLocationsById'][$loc->getLocationId()] = $loc;
        $GLOBALS['CACHE']['JobLocationsByOsmId'][$loc->getOpenStreetMapId()] = $loc;
    }
}


function getJobLocationById($locationId)
{
    if(!(array_key_exists('CACHE', $GLOBALS) && is_array($GLOBALS['CACHE']['JobLocationsById']) && array_key_exists('JobLocationsById', $GLOBALS['CACHE'])))
        reloadJobLocationCache();

    if(!array_key_exists($locationId, $GLOBALS['CACHE']['JobLocationsById']))
        return \JobScooper\DataAccess\JobLocationQuery::create()
            ->filterByLocationId($locationId)
            ->findOne();

    return $GLOBALS['CACHE']['JobLocationsById'][$locationId];
}

function getJobLocationByOsmId($osmId)
{
    if(!array_key_exists('CACHE', $GLOBALS) || !array_key_exists('JobLocationsByOsmId', $GLOBALS['CACHE']))
        reloadJobLocationCache();

    if(!array_key_exists($osmId, $GLOBALS['CACHE']['JobLocationsByOsmId']))
    {
        return \JobScooper\DataAccess\JobLocationQuery::create()
            ->filterByOpenStreetMapId($osmId)
            ->findOne();
    }

    return $GLOBALS['CACHE']['JobLocationsByOsmId'][$osmId];
}


function reloadLocationNamesCache()
{
    LogLine("....reloading Job Location Names Lookup table cache....", \C__DISPLAY_ITEM_DETAIL__);
    $allLocs = \JobScooper\DataAccess\JobPlaceLookupQuery::create()
        ->find();

    if (!array_key_exists('CACHE', $GLOBALS))
        $GLOBALS['CACHE'] = array();

    $GLOBALS['CACHE']['JobPlaceLookup'] = array();

    foreach ($allLocs as $loc) {
        $GLOBALS['CACHE']['JobPlaceLookup'][$loc->getSlug()] = $loc;
    }
}


function getJobPlaceLookup($slug)
{
    if(!array_key_exists('CACHE', $GLOBALS) || !array_key_exists('JobPlaceLookup', $GLOBALS['CACHE']))
        reloadLocationNamesCache();

    if(!array_key_exists($slug, $GLOBALS['CACHE']['JobPlaceLookup']))
    {
        LogLine("Cache missed; looking up location in database by name string '" . $slug ."'", \C__DISPLAY_NORMAL__);
        return \JobScooper\DataAccess\JobPlaceLookupQuery::create()
            ->filterBySlug($slug)
            ->findOneOrCreate();

    }

    return $GLOBALS['CACHE']['JobPlaceLookup'][$slug];

}

