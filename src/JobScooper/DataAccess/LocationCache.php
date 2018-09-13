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

use JobScooper\Logging\CSVLogFormatter;
use JobScooper\Logging\CSVLogHandler;use JobScooper\Traits\Singleton;
use JobScooper\Utils\CurlWrapper;
use JobScooper\Utils\Settings;
use Monolog\Logger;

class LocationCache {
	use Singleton;
	private $_cache = null;

	/**
	 * @return null
	 */
	public  function getCache(){
	    return $this->_cache;
	}
	/**
	 * @param null $cache
	 */
	public  function setCache($cache):void {
	    $this->_cache = $cache;
	}

	private $_geoapi_srvr = null;
	private $_loggerName = 'geocode_api_calls';
	
	/* @var \Monolog\Logger */
	private $logger = null;
	
    /**
     * @throws \Exception
     */
	protected function init(){

		$this->_initializeLogger();
		// create Flysystem object
        $cachedir = getOutputDirectory('reusable_caches') . DIRECTORY_SEPARATOR . 'geocode_api_calls';

		$adapter = new \League\Flysystem\Adapter\Local($cachedir, LOCK_EX);
		$filesystem = new \League\Flysystem\Filesystem($adapter);
		// create Scrapbook KeyValueStore object
		$cache = new \MatthiasMullie\Scrapbook\Adapters\Flysystem($filesystem);

	    $this->_geoapi_srvr = Settings::getValue('geocodeapi_server');
		
		$buffcache = new \MatthiasMullie\Scrapbook\Buffered\BufferedStore($cache);
		$this->_cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache($buffcache);


	}

    /**
     * @throws \Exception
     */
    private function _initializeLogger()
    {
        $this->loggerName = 'geocode_calls';
        $logger = new Logger($this->_loggerName);
        $now = getNowAsString('-');
        $csvlog = getOutputDirectory('logs') . DIRECTORY_SEPARATOR . "{$this->_loggerName}-{$now}.csv";
        $fpcsv = fopen($csvlog, 'w');
        $csvHandler = new CSVLogHandler($fpcsv, LOG_INFO, $bubble = false, $this->getLogContextKeys());
        $fmtr = new CSVLogFormatter();
        $csvHandler->setFormatter($fmtr);
        $logger->pushHandler($csvHandler);

        $logger->addNotice("Initialized log", array_fill_keys($this->getLogContextKeys(), ""));
		
        LogMessage("Geocode API logging started to CSV file at {$csvlog}");

        $this->logger = $logger;
        
    }


	/**
	* @param $str
	* @param array $args
    *
    * @return \JobScooper\DataAccess\GeoLocation|null
	* @throws \Exception
	*/
	public function lookup($str, $args=[])
	{
		$scrubbed = null;
		$cacheSlug = null;
		$cachehit = null;
		$apiresult = null;
		
		try {
		
			$scrubbed = GeoLocation::scrubLocationValue($str);
			$cacheSlug = cleanupSlugPart($scrubbed);
			$cachehit = $this->_cache->get($cacheSlug); // returns 'value'
			if($cachehit !== false && !is_empty_value($cachehit)) {
				return $cachehit;
			}
			
			if(!\is_array($args)) {
				$args = [];
			}
			$args['query'] = $scrubbed;
			
			$apiresult = $this->_callApi($args);
			if(!is_empty_value($apiresult)) {
				$this->_cache->set($cacheSlug, $apiresult); // returns 'value'
			}
			
			return $apiresult;
		}
		catch (\Exception $ex) {
			handleException($ex);
		}
		finally {
				$msg = "Unknown geoloc state for {$scrubbed}";
				$geolocid = null;
				$geolockey = null;
				if($cachehit !== false && !is_empty_value($cachehit)) {
					$msg = "Geolocation cache hit found for {$cacheSlug}";
					$geolocid = $cachehit->getGeoLocationId();
					$geolockey = $cachehit->getGeoLocationKey();
					$hitresult = "CACHE_HIT";
				}
				elseif (!is_empty_value($apiresult)) {
					$msg = "Geolocation api result found for {$scrubbed}";
					$hitresult = "API_HIT";
					$geolocid = $apiresult->getGeoLocationId();
					$geolockey = $apiresult->getGeoLocationKey();
				}
				else {
					$msg = "Failed to find geolocation for {$scrubbed}";
					$hitresult = "FAILED_LOOKUP";
				}

				$context = array(
					'query' => str_replace(',', '\,', $str),
					'scrubbed' => $scrubbed,
					'cachekey' => $cacheSlug,
					'hitresult' => $hitresult,
					'geolocationid' => $geolocid,
					'geolocationkey' => $geolockey
				);
				$this->logger->info($msg, $context);
		}
	}
	
	private function getLogContextKeys() {
				return array(
					'query',
					'scrubbed',
					'cachekey',
					'hitresult',
					'geolocationid',
					'geolocationkey'
				);
	}
	
	private function _callApi($args) {
	        
            $searchBias = Settings::getValue("active_location_search_bias");
            if(!is_empty_value($searchBias) && is_array($searchBias)) {
	            $searchloc = Settings::getValue("active_location_search");
	            if(!is_empty_value($searchloc) && is_array($searchloc)) {
			        $searchBias = [
		                'loc_lat' => $searchloc['latitude'],
		                'loc_long' => $searchloc['longitude'],
			            'loc_radius' => 10000
			        ];
			        Settings::setValue('active_location_search_bias', $searchBias);
	
	            }
            }
            if (!is_empty_value($searchBias)) {
	            $args = array_merge($args, $searchBias);
            }
			
            $querystring = http_build_query($args);
			$url = \JBZoo\Utils\Url::buildAll($this->_geoapi_srvr, array('path' => '/places/lookup', 'query' => $querystring));
            $curl = new CurlWrapper();
			LogDebug("Calling Geocode API: { $url }");
            $response = $curl->cURL($url);
            if($response['http_code'] < 300 && array_key_exists('body', $response)) {
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
