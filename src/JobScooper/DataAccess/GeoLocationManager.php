<?php
/**
 * Copyright 2014-18 Bryan Selner
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

use Geocoder\Geocoder;
use \Exception;
use JobScooper\Logging\CSVLogHandler;
use Monolog\Logger;
use JobScooper\Traits\Singleton;

/**
 * Class GeoLocationManager
 * @package JobScooper\DataAccess
 */
class GeoLocationManager
{
	use Singleton;
	private $_inst = null;

    /**
     * @var Geocoder
    */
    private $_geocache = null;

    /**
     * @throws \Exception
     */
    public function init() {
    	
        if(is_empty_value($this->_geocache)) {
	    	$this->_geocache = LocationCache::getInstance();
	    }
	    
    }
 
    /**
     * @throws \Exception
     * @return GeoLocation|null
     */
    public function lookupAddress($strAddress): ?GeoLocation
    {
        try {
	        $geoloc = $this->_geocache->lookup($strAddress);
	        if(null === $geoloc){
	            throw new \InvalidArgumentException("Error:  Geocoder returned a null location for {$strAddress}.");
	        }
            LogMessage("... Geocoder returned Geolocation {$geoloc->getGeoLocationKey()} for {$strAddress}.");
        } catch (Exception $e) {
            throw($e);
        }

        return $geoloc;
    }

}
