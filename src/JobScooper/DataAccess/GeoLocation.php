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

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\GeoLocation as BaseGeoLocation;
use JobScooper\Manager\GeoLocationManager;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

class GeoLocation extends BaseGeoLocation
{
    public function save(ConnectionInterface $con = null, $skipReload = false)
    {
        try {
            return parent::save($con, $skipReload);
        }
        catch (PropelException $ex)
        {
            handleException($ex, "Failed to save GeoLocation: %s", true);
        }
    }

    public function preSave(\Propel\Runtime\Connection\ConnectionInterface $con = null)
    {
        $this->_normalizeGeoLocationRecord();

        if (is_callable('parent::preSave')) {
            return parent::preSave($con);
        }
        return true;
    }

    private function _normalizeGeoLocationRecord()
    {
        if(is_null($this->getOpenStreetMapId()))
        {
            $facts = getOpenStreetMapFacts($this->getDisplayName());
            $this->fromOSMData($facts);
        }

    }


    public function postSave(ConnectionInterface $con = null)
    {
        parent::postSave($con);

        $caches = new GeoLocationManager();
        $caches->reloadCache();
    }

    public function setState($v)
    {
        parent::setState($v);

        $newCode = getStateCodeFromState($v);

        if (is_null($this->getStateCode()))
            parent::setStateCode($newCode);
    }

    public function setStateCode($v)
    {
        if (!is_null($v) && strlen($v) > 0)
            $v = strtoupper($v);

        parent::setStateCode($v);

        $newState = getStateFromStateCode($v);

        if (is_null($this->getState()))
            $this->setState($newState);

    }


    public function setCountryCode($v)
    {
        if (!is_null($v) && strlen($v) > 0)
            $v = strtoupper($v);

        parent::setCountryCode($v);

    }

    public function setFullOsmDataFromArray($v)
    {
        $osmJson = encodeJSON($v);
        parent::setFullOsmData($osmJson);
    }

    public function getFullOsmDataAsArray()
    {
        $osmJsonText = parent::getFullOsmData();
        if (!is_null($osmJsonText) && strlen($osmJsonText) > 0)
            return decodeJSON($osmJsonText);
        return null;
    }

    private function _setFactFromOSM($key, $osmData, $osmKey, $overwrite)
    {
        $getfunc = "get" . $key;
        $setfunc = "set" . $key;
        $curval = $this->$getfunc();
        if(is_null($curval) || $overwrite)
        {
            $this->$setfunc($osmData[$osmKey]);
        }
    }

    public function fromDisplayString($strloc)
    {
        $this->addAlternateName($strloc);
        if(is_null($this->getDisplayName()))
            $this->setDisplayName($strloc);
        $this->_normalizeGeoLocationRecord();
        $this->save();
    }

    public function fromOSMData($osmPlace, $overwriteValues = false)
    {
        if(!is_null($osmPlace) && is_array($osmPlace) && count($osmPlace) > 0)
        {
            if(array_key_exists(0, $osmPlace))
                $osmPlace = $osmPlace[0];


            $locMgr = new GeoLocationManager();
            $otherLocationId = $locMgr->getGeoLocationIdByOsmId($osmPlace['osm_id']);
            if(!is_null($otherLocationId) && $otherLocationId !== false) {
                $otherLocation = $locMgr->getLocationById($otherLocationId);
                $otherLocation->copyInto($this, false, false);
            }
            else {
                $this->_setFactFromOSM('OpenStreetMapId', $osmPlace, 'osm_id', $overwriteValues);
                $this->_setFactFromOSM('Latitude', $osmPlace, 'lat', $overwriteValues);
                $this->_setFactFromOSM('Longitude', $osmPlace, 'long', $overwriteValues);
                $this->_setFactFromOSM('Place', $osmPlace, 'place_name', $overwriteValues);
                $this->setFullOsmDataFromArray($osmPlace);
                if (array_key_exists('address', $osmPlace)) {
                    $this->_setFactFromOSM('Place', $osmPlace['address'], 'city', $overwriteValues);
                    $this->_setFactFromOSM('State', $osmPlace['address'], 'state', $overwriteValues);
                    $this->_setFactFromOSM('County', $osmPlace['address'], 'county', $overwriteValues);
                    $this->_setFactFromOSM('Country', $osmPlace['address'], 'country', $overwriteValues);
                    $this->_setFactFromOSM('CountryCode', $osmPlace['address'], 'country_code', $overwriteValues);
                    $this->_setFactFromOSM('DisplayName', $osmPlace, 'display_name', true);
                }

                if (array_key_exists('namedetails', $osmPlace))
                    $this->addAlternateNames($osmPlace['namedetails']);
            }

            if(!is_null($this->getPlace())) {
                $dispName = $this->getPlace();
                $dispName .= !is_null($this->getStateCode()) ? ", " . $this->getStateCode() : "";
                $this->setDisplayName($dispName);
            }
        }
    }

    public function setDisplayName($v)
    {
        parent::setDisplayName($v);
        $this->addAlternateName($v);
    }

    public function addAlternateNames($value)
    {
        if(!is_null($value) && is_array($value))
            $names = $value;
        else {
            $names = preg_split("/\s*\|\s*/", $value, $limit = -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach($names as $name)
        {
            $this->addAlternateName($name);
        }
    }

    public function setAlternateNames($value)
    {
        if(!is_null($value) && is_array($value))
            $value = array_unique($value);
        parent::setAlternateNames($value);
    }

    public function formatLocation($formatString)
    {
        return replaceTokensInString($formatString, $this->toArray());
    }

    public function formatLocationByLocationType($locFormatNeeded)
    {
        switch ($locFormatNeeded)
        {

            case 'location-city':
                $locFormatString = "{PLACE}";
                break;

            case 'location-city-comma-statecode':
                $locFormatString = "{PLACE}, {STATECODE}";
                break;

            case 'location-city-space-statecode':
                $locFormatString = "{PLACE} {STATECODE}";
                break;

            case 'location-city-dash-statecode':
                $locFormatString = "{PLACE}-{STATECODE}";
                break;

            case 'location-city-comma-nospace-statecode':
                $locFormatString = "{PLACE},{STATECODE}";
                break;

            case 'location-city-comma-statecode-underscores-and-dashes':
                $locFormatString = "{PLACE}__2c-{STATECODE}";
                break;

            case 'location-city-comma-state':
                $locFormatString = "{PLACE}, {STATE}";
                break;

            case 'location-city-comma-state-country':
                $locFormatString = "{PLACE}, {STATE}, {COUNTRY}";
                break;

            case 'location-city-comma-state-country-no-commas':
                $locFormatString = "{PLACE} {STATE} {COUNTRY}";
                break;

            case 'location-city-comma-state-comma-country':
                $locFormatString = "{PLACE}, {STATE}, {COUNTRY}";
                break;

            case 'location-city-comma-statecode-comma-country':
                $locFormatString = "{PLACE}, {STATECODE}, {COUNTRY}";
                break;

            case 'location-city-comma-state-comma-countrycode':
                $locFormatString = "{PLACE}, {STATE}, {COUNTRYCODE}";
                break;

            case 'location-city-comma-country':
                $locFormatString = "{PLACE}, {COUNTRY}";
                break;

            case 'location-city--comma-countrycode':
                $locFormatString = "{PLACE}, {COUNTRYCODE}";
                break;

            case 'location-city-comma-statecode-comma-countrycode':
                $locFormatString = "{PLACE}, {STATECODE}, {COUNTRYCODE}";
                break;

            case 'location-countrycode':
                $locFormatString = "{COUNTRYCODE}";
                break;

            case 'location-city-country-no-commas':
                $locFormatString = "{PLACE} {COUNTRY}";
                break;

            case 'location-state':
                $locFormatString = "{STATE}";
                break;

            case 'location-statecode':
                $locFormatString = "{STATECODE}";
                break;

            default:
                $locFormatString = "{DISPLAYNAME}";
                break;
        }

        return $this->formatLocation($locFormatString);
    }

}
