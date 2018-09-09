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

namespace JobScooper\Manager;

use Geocoder\Exception\NoResultException;
use Geocoder\Geocoder;

use \Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use JobScooper\Utils\Settings;
use Monolog\Logger;
use JobScooper\Traits\Singleton;
use Nette\Caching\Storages\FileStorage;

/**
 * Class LocationManager
 * @package JobScooper\Manager
 */
class LocationManager
{
	use Singleton;
    /**
     * @var \Monolog\Logger|null
     */
    protected $logger = null;

    /**
     * @var null
     */
    private $loggerName = null;
	private $cachedir = null;
	private $geoApiServer = null;
	private $googleApiKey = null;

    /**
     * @return LocationManager
     * @throws \Exception
     */

    public function _instance() {

   	    $this->cachedir = getOutputDirectory('caches') . DIRECTORY_SEPARATOR . 'location_manager';

		$this->storage = new FileStorage($this->cachedir);

        $googleApiKey = \JobScooper\Utils\Settings::getValue('google_maps_api_key');
        if (is_empty_value($googleApiKey) || !is_string($googleApiKey)) {
            throw new Exception('No Google Geocode API key found in configuration.  Instructions for getting an API key are at https://developers.google.com/maps/documentation/geocoding/get-api-key.');
        }
        $this->geoApiServer = \JobScooper\Utils\Settings::getValue('geocodeapi_server');
        if (!is_empty_value($this->geoApiServer)) {
            throw new Exception('No Geocode API server found in configuration.');
		}

    }

    /**
     * @param $strAddress
     *
     * @return mixed|null|string|string[]
     */
    public static function scrubLocationValue($strAddress)
    {
        $lookupAddress = $strAddress;

        // Strip out any zip code (aka a 5 digit set) from the string
        //
        // Most of the time when we see a zip, it is placed in one of these three cases:
        //      Case 1  =  "Seattle WA 98102 (Cap Hill Area)"
        //      Case 2  =  "Seattle WA 98102"
        //      Case 3  =  "98102"
        //
        $zipSplits = preg_split("/\b\d{5}\b/", $lookupAddress);
        if (count($zipSplits) === 1 && !empty(trim($zipSplits[0]))) {
            $lookupAddress = $zipSplits[0];
        }  // "Dallas Tx 55555" => "Dallas Tx"
        elseif (count($zipSplits) === 2) {
            $lookupAddress = str_ireplace(" area", "", $zipSplits[1]) . " " . $zipSplits[0];
        } elseif (count($zipSplits) > 2) {
            $lookupAddress = implode(" ", $zipSplits);
        }

        $lookupAddress = strip_punctuation($lookupAddress);

        //
        // if name is something like "Greater London" or
        // "Greater Seattle Area", strip it down to just the
        // place name
        //
        $lookupAddress = preg_replace("/greater\s(\w+\s)area/i", "\\1", $lookupAddress, -1);
        $lookupAddress = preg_replace("/greater\s(\w+\s)/i", "\\1", $lookupAddress);
        $lookupAddress = cleanupTextValue($lookupAddress);

        $lookupAddress = preg_replace("/\s{2,}/", " ", $lookupAddress);
        return $lookupAddress;
    }

    /**
     * @throws \Exception
     * @return \JobScooper\DataAccess\GeoLocation|array|null
     */
    public function lookupAddress($strAddress)
    {
        $lookupAddress = self::scrubLocationValue($strAddress);

        // Do HTTP call to geocoder
        
        return array();

    }

    /**
     * @param $strAddress
     *
     * @return \Geocoder\Result\ResultInterface|\Geocoder\Result\ResultInterface|\JobScooper\DataAccess\GeoLocation|null
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function find_location($strAddress)
    {
        if ($this->countLookupErrors >= 5) {
            LogMessage('Google Geocoding is disabled because of too many error results during this run.');
            $GLOBALS['CACHES']['GEOCODER_ENABLED'] = false;

            return null;
        }

        try {
	        $client = new Client([
	            'base_uri' => $this->geoApiServer
			]);
	        $lookup = self::scrubLocationValue($strAddress);
	
	        $lookup_params = [
	        	'query' => $strAddress,
	        	'apikey' => $this->googleApiKey
	        	];
	        
            $locbias = Settings::getValue("active_location_search_bias");
            if(!is_empty_value($locbias) && is_array($locbias)) {
            	$lookup_params = array_merge($lookup_params, $locbias);
            }

	        $response = $client->request("GET", "place/lookup", $lookup_params);
	        
            if($response->getStatusCode() >= 300)
            {
                throw new RequestException("GeocodeAPI request returned exception: " . $response->getReasonPhrase(), null, $response);
            }

			$result = $response->json();

            $geolocation = GeoLocationQuery::create()
                ->findOneByGeoLocationId($geoLocId);
            return $geolocation;


            return $geolocation;

        } catch (NoResultException $ex) {
            LogDebug("No geocode result was found for {$strAddress}:  " . $ex->getMessage(), null, $this->loggerName);
            $geolocation = null;
	        $this->_geoLocCache->cacheUnknownLocation($lookup);
        } catch (Exception $ex) {
            ++$this->countGeocodeErrors;
            LogError("Failed to geocode {$strAddress}: " . $ex->getMessage(), null, $ex, $this->loggerName);
	        $this->_geoLocCache->cacheUnknownLocation($lookup);
        }
        return null;

    }
    
    
    public function getContent($url)  // used in Google Maps Geocoder only
    {
        $curl_output = $this->cURL($url, $json = null, $action = 'GET', $content_type = null, $pagenum = null, $onbehalf = null, $fileUpload = null, $secsTimeout = null, $cookies = null, $referrer = C__SRC_LOCATION);
        if (array_key_exists('body', $curl_output)) {
            $json = json_decode($curl_output['body']);
            if ($json->status != 'OK') {
                $errMsg = $json->status . ' - ' . $json->error_message;
                switch ($json->status) {
                    case 'REQUEST_DENIED':
                        throw new \Geocoder\Exception\InvalidCredentialsException($errMsg);
                        break;

                    case 'OVER_QUERY_LIMIT':
                        throw new \Geocoder\Exception\QuotaExceededException($errMsg);
                        break;

                    case 'ZERO_RESULTS':
                        LogWarning('No results were found for the query ' . $url);
                        return $curl_output['body'];
                        break;

                    default:
                        throw new InvalidServerResponse($errMsg);
                        break;
                }
            }
            return $curl_output['body'];
        }

        return $curl_output['output'];
    }
}
