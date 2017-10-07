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

use JobScooper\DataAccess\Base\Location as BaseLocation;
use Propel\Runtime\Connection\ConnectionInterface;

class Location extends BaseLocation
{

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {

        if(is_null($this->getOpenStreetMapId()))
        {
            if(!is_null($this->getPrimaryName()))
            {
                $osm = getOpenStreetMapFacts($this->getPrimaryName());
                $this->fromOSMData($osm);
            }
        }
        return true;
    }

    public function postSave(ConnectionInterface $con = null)
    {
        parent::postSave($con);

        reloadLocationCache();
    }



    public function setState($v)
    {
        parent::setState($v);

        $newCode = getStateCodeFromState($v);

        if(is_null($this->getStateCode()))
            $this->setStateCode($newCode);
    }

    public function setStateCode($v)
    {
        if(!is_null($v) && strlen($v) > 0)
            $v = strtoupper($v);

        parent::setStateCode($v);

        $newState = getStateFromStateCode($v);

        if(is_null($this->getState()))
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
        if(!is_null($osmJsonText) && strlen($osmJsonText) > 0)
            return decodeJSON($osmJsonText);
        return null;
    }

    public function fromOSMData($osmPlace)
    {

        if(!is_null($osmPlace) && is_array($osmPlace) && count($osmPlace) > 0)
        {
            $this->setOpenStreetMapId($osmPlace['osm_id']);
            $this->setFullOsmDataFromArray($osmPlace);
            $this->setLatitude($osmPlace['lat']);
            $this->setLongitude($osmPlace['lon']);
            if(array_key_exists('primary_name', $osmPlace))
                $this->setDisplayName($osmPlace['primary_name']);
            elseif (array_key_exists('display_name', $osmPlace))
                $this->setDisplayName($osmPlace['display_name']);
            if (array_key_exists('place_name', $osmPlace))
                $this->setPlace($osmPlace['place_name']);
            $this->setAlternateNames($osmPlace['namedetails']);
            if (array_key_exists('address', $osmPlace)) {
                if (array_key_exists('city', $osmPlace['address']))
                    $this->setPlace($osmPlace['address']['city']);

                if (array_key_exists('state', $osmPlace['address']))
                    $this->setState($osmPlace['address']['state']);

                if (array_key_exists('county', $osmPlace['address']))
                    $this->setCounty($osmPlace['address']['county']);

                if (array_key_exists('country', $osmPlace['address']))
                    $this->setCountry($osmPlace['address']['country']);
                if (array_key_exists('country_code', $osmPlace['address']))
                    $this->setCountryCode($osmPlace['address']['country_code']);
            }
        }
    }


//$state = $this->getState();
//if(!is_null($state) && strlen($state) > 0)
//$this->setStateCode($STATE_CODES[$state]);
//
}
