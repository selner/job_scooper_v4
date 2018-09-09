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

namespace JobScooper\DataAccess;

use JobScooper\Traits\Singleton;
use JobScooper\Utils\CurlWrapper;
use JobScooper\Utils\Settings;

class LocationCache {
	use Singleton;
	private $_cache = null;
	private $_geoapi_srvr = null;
	
	protected function init(){

		// create Flysystem object
        $cachedir = getOutputDirectory('cache') . DIRECTORY_SEPARATOR . self::class;

		$adapter = new \League\Flysystem\Adapter\Local($cachedir, LOCK_EX);
		$filesystem = new \League\Flysystem\Filesystem($adapter);
		// create Scrapbook KeyValueStore object
		$cache = new \MatthiasMullie\Scrapbook\Adapters\Flysystem($filesystem);

	    $this->_geoapi_srvr = Settings::getValue('geocodeapi_server');
		
		$buffcache = new \MatthiasMullie\Scrapbook\Buffered\BufferedStore($cache);
		$this->_cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache($buffcache);


	}


	public function lookup($str, $args=[]) {
		
		$scrubbed = GeoLocation::scrubLocationValue($str);
		$cachehit = $this->_cache->get($scrubbed); // returns 'value'
		if($cachehit !== false && !is_empty_value($cachehit)) {
			return $cachehit;
		}
		
		if(!\is_array($args)) {
			$args = [];
		}
		$args['query'] = $scrubbed;
		
		$apiresult = $this->_callApi($args);
		if(!is_empty_value($apiresult)) {
			$this->_cache->set($scrubbed, $apiresult); // returns 'value'

		}
		
		return $apiresult;
	}
	
	private function _callApi($args) {
	        
            $locbias = Settings::getValue("active_location_search_bias");
            if(!is_empty_value($locbias) && is_array($locbias)) {
            	$args = array_merge($args, $locbias);
            }
			
            $querystring = http_build_query($args);
			$url = \JBZoo\Utils\Url::buildAll($this->_geoapi_srvr, array('path' => '/places/lookup', 'query' => $querystring));
            $curl = new CurlWrapper();
			LogDebug("Calling Geocode API: { $url }");
            $response = $curl->cURL($url);
            if($response['http_code'] < 300 && array_key_exists('body',$response)) {
	            if(!is_empty_value($response['body'])) {
	                $resdata = decodeJson($response['body']);
	                $geoloc = new GeoLocation();
	                $geoloc->fromGeocode($resdata);
	                unset($resdata);
	                $key = $geoloc->getGeoLocationKey();
	                
		            $locexists = GeoLocationQuery::create()
		                ->findOneByGeoLocationKey($key);
		            if(!is_empty_value($locexists)) {
		            	unset($geoloc);
		            	unset($curl);
		                return $locexists;
		            }
		            else {
		                $geoloc->save();
		            	unset($curl);
		                return $geoloc;
		            }
	            }
	            
            }
	
            return null;
	}
}
