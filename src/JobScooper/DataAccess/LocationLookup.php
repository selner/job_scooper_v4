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
use JobScooper\Logging\CSVLogHandler;
use JobScooper\Traits\Singleton;
use JobScooper\Utils\CurlWrapper;
use JobScooper\Utils\Settings;
use Monolog\Logger;

class LocationLookup {
	use Singleton;
	private $_geoapi_srvr = null;
	private $_loggerName = 'geocode_api_calls';
	private $_nFailures = 0;
	/* @var \Monolog\Logger */
	private $logger = null;
	
    /**
     * @throws \Exception
     */
	protected function init(){

		$this->_initializeLogger();

	    $this->_geoapi_srvr = Settings::getValue('geocodeapi_server');

	}

    /**
     * @throws \Exception
     */
    private function _initializeLogger()
    {
        $logger = new Logger('geocode_api_cache');
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
		$result = null;
		$context = [];

		try {
			if($this->_nFailures >= 5) {
				LogWarning('Location lookup halted due to too many errors.');
				return null;
			}
	
			$scrubbed = GeoLocation::scrubLocationValue($str);
			if(is_empty_value($scrubbed)) {
				return null;
			}


			if(!\is_array($args)) {
				$args = [];
			}
			$args['query'] = $scrubbed;

            return  $this->_callApi($args);
		}
		catch (\Exception $ex) {
			$this->_nFailures += 1;
			handleException($ex);
		}
		finally {
            $msg = "Unknown geoloc state for {$scrubbed}";
            if($result !== false && !is_empty_value($result))
            {
                $context['geolocationid'] = $result->getGeoLocationId();
                $context['geolocationkey'] = $result->getGeoLocationKey();
                $this->logger->info($msg, $context);
            }
            else {
                $msg = "Failed to find geolocation for {$scrubbed}";
                $this->logger->error($msg, $context);
            }
		}
	}
	
	/**
      * @return array
	*/
	private function getLogContextKeys() {
				return array(
					'query',
					'scrubbed',
					'cachekey',
					'hittype',
					'geolocationid',
					'geolocationkey'
				);
	}
	
	/**
	  * @param $args
	  *
	  * @return \JobScooper\DataAccess\GeoLocation|null
     * @throws \HttpRequestException
     * @throws \Exception
	*/
	private function _callApi($args) {
	    $url = 'UNKNOWN';
		$response = [ 'API' => $url, 'http_code' => 'UNKNOWN'];
		
		try {
            $searchBias = Settings::getValue('active_search_location_bias');
            if(is_empty_value($searchBias) || !is_array($searchBias)) {
	            $searchloc = Settings::getValue('active_search_location');
	            if(!is_empty_value($searchloc) && is_array($searchloc)) {
			        $searchBias = [
		                'loc_lat' => $searchloc['Latitude'],
		                'loc_long' => $searchloc['Longitude'],
			            'loc_radius' => 10000
			        ];
			        Settings::setValue('active_search_location_bias', $searchBias);
	
	            }
            }
            if (!is_empty_value($searchBias)) {
	            $args = array_merge($args, $searchBias);
            }

            $querystring = http_build_query($args);
			$url = \JBZoo\Utils\Url::buildAll($this->_geoapi_srvr, array('path' => '/places/lookup', 'query' => $querystring));
            $curl = new CurlWrapper();
            $response = $curl->cURL($url);
            if(array_key_exists('body', $response) && $response['http_code'] < 300) {
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
	            } else {
					throw new \HttpRequestException("GeocodeAPI returned an empty body in the response: [HTTP code {$response['http_code']}}");
	            }

            }
            else
            {
				throw new \HttpRequestException("GeocodeAPI failed to return a valid response: [HTTP code {$response['http_code']} / {$response['body']}");
            }
		}
		catch (\Exception $ex) {
			$this->logger->error("GeocodeAPI returned an error: {$ex->getMessage()}.");
			handleException($ex);
		}
		finally {
			LogDebug('Geocode API called',  $extras= [ 'API' => $url, 'http_code' => $response['http_code']]);
		}
	}
}
