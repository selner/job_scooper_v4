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

use Geocoder\Result\ResultFactoryInterface;
use JobScooper\DataAccess\GeoLocation;
use JobScooper\DataAccess\GeoLocationQuery;

class GeoLocationResultsFactory implements ResultFactoryInterface
{
    /**
     * {@inheritDoc}
     * @returns GeoLocation
     */
    final public function createFromArray(array $data)
    {
        $geolocation = $this->newInstance();
        $geolocation->fromGeocode(isset($data[0]) ? $data[0] : $data);


        $locKey = $geolocation->getGeoLocationKey();
        $existingGeo = GeoLocationQuery::create()
            ->findOneByGeoLocationKey($locKey);
        if (null !== $existingGeo) {
            $geolocation->save();

            return $geolocation;
        }

        unset($geolocation);
        return $existingGeo;
    }

    /**
     * {@inheritDoc}
     * @returns GeoLocation
     */
    public function newInstance()
    {
        return new GeoLocation();
    }
}
