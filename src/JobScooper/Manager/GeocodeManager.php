<?php
/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 11/20/17
 * Time: 5:59 PM
 */

namespace JobScooper\Manager;

use Geocoder\Formatter\FormatterInterface;
use Geocoder\HttpAdapter\HttpAdapterInterface;
use Geocoder\Provider\GoogleMapsProvider;
use Geocoder\HttpAdapter\CurlHttpAdapter;
use \Exception;
use Geocoder\Result\ResultInterface;
use JBZoo\Utils\Http;
use JobScooper\Logging\CSVLogHandler;
use JobScooper\Utils\GoogleGeocoderHttpAdapter;

use Monolog\Logger;
use Psr\Log\LogLevel as LogLevel;

class StringFormatter
{

    /**
     * {@inheritdoc}
     */
    public function format(ResultInterface $result, $format)
    {
        return strtr($format, array(
            FormatterInterface::STREET_NUMBER   => $result->getStreetNumber(),
            FormatterInterface::STREET_NAME     => $result->getStreetName(),
            FormatterInterface::CITY            => $result->getCity(),
            FormatterInterface::ZIPCODE         => $result->getZipcode(),
            FormatterInterface::CITY_DISTRICT   => $result->getCityDistrict(),
            FormatterInterface::COUNTY          => $result->getCounty(),
            FormatterInterface::COUNTY_CODE     => $result->getCountyCode(),
            FormatterInterface::REGION          => $result->getRegion(),
            FormatterInterface::REGION_CODE     => $result->getRegionCode(),
            FormatterInterface::COUNTRY         => $result->getCountry(),
            FormatterInterface::COUNTRY_CODE    => $result->getCountryCode(),
            FormatterInterface::TIMEZONE        => $result->getTimezone(),
        ));
    }
}

class GoogleMapsLoggedProvider extends GoogleMapsProvider
{
    protected $callCounter = 0;

    public function __construct(HttpAdapterInterface $adapter, $locale = null, $region = null, $useSsl = false, $apiKey = null, $logger=null)
    {
        $this->setLogger($logger);
        parent::__construct($adapter, $locale, $region, $useSsl, $apiKey);
    }
    protected $logger = null;

    function setLogger($logger)
    {
        $this->logger = $logger;
    }
    protected function executeQuery($query)
    {
        $this->callCounter += 1;
        $context = array("msg"=> "geocoder called (" . $this->callCounter ." call on this run.", "query" => $query, "call_count_for_run" => $this->callCounter);
        if(!empty($this->logger))
        {
            $this->logger->addInfo("Google Geocoder Called (" . $this->callCounter . " times)", getDebugContext($context));
        }
        return parent::executeQuery($query);
    }
}

class GeocodeManager
{
    protected $geocoder = null;
    protected $logger = null;
    protected $loggerName = null;



    function __construct()
    {
        $googleApiKey = getConfigurationSettings('google_maps_api_key');
        if (is_null($googleApiKey) || !is_string($googleApiKey)) {
            throw new Exception("No Google Geocode API key found in configuration.  Instructions for getting an API key are at https://developers.google.com/maps/documentation/geocoding/get-api-key.");
        }

        $regionBias = null;
        $country_codes = getConfigurationSettings('country_codes');
        if (!is_null($country_codes) && is_array($country_codes)) {
            $regionBias = $country_codes[0];
        }

        $this->loggerName = "geocode_calls";
        $this->logger = new \Monolog\Logger($this->loggerName);
        $now = getNowAsString("-");
        $csvlog = getOutputDirectory('logs'). DIRECTORY_SEPARATOR . "{$this->loggerName}-{$now}-geocode_api_calls.csv";
        $fpcsv = fopen($csvlog, "w");
        $handler = new CSVLogHandler($fpcsv, Logger::INFO);
        $this->logger->pushHandler($handler);

        $curl = new GoogleGeocoderHttpAdapter();
        $this->geocoder = new \Geocoder\Geocoder();
        $this->geocoder->registerProviders(array(
            new GoogleMapsLoggedProvider(
                $adapter=$curl,
                $locale="en",
                $region=$regionBias,
                $useSsl = true,
                $apiKey=$googleApiKey,
                $logger=$this->logger
            )
        ));


        LogLine("Geocode API logging started to CSV file at {$csvlog}", C__DISPLAY_ITEM_DETAIL__);

    }

    function geocode($strLocation)
    {
        return $this->geocoder->geocode($strLocation);

    }
    /**
     * @param $strLocation
     */
    function getPlaceForLocationString($strLocation)
    {
        $addr = array();
        try {
            $geocodeResult = $this->geocoder->geocode($strLocation);
        } catch (\Geocoder\Exception\NoResultException $ex) {
            LogDebug("No geocode result was found for " . $strLocation .".  Details: " .$ex->getMessage());
            return null;
        } catch (Exception $ex) {
            handleException($ex);
        }
        finally
        {
            $GLOBALS['CACHES']['totalGoogleQueriesThisRun'] = $GLOBALS['CACHES']['totalGoogleQueriesThisRun'] + 1;
        }

        if (count($addr) > 0) {
            $arrGeoResult = $geocodeResult->toArray();

            if (array_key_exists('city', $arrGeoResult))
                $arrGeoResult['place'] = $arrGeoResult['city'];
            else
                $arrGeoResult['place'] = null;

//            if (array_key_exists('adminLevels', $arrGeoResult) && is_array($arrGeoResult) && count($arrGeoResult) > 0) {
//                if (count($arrGeoResult['adminLevels']) >= 1) {
//                    $arrGeoResult['region'] = $arrGeoResult['adminLevels'][1]['name'];
//                    $arrGeoResult['regioncode'] = $arrGeoResult['adminLevels'][1]['code'];
//                }
//                if (count($arrGeoResult['adminLevels']) >= 2) {
//                    $arrGeoResult['county'] = $arrGeoResult['adminLevels'][2]['name'];
//                }
//            }
//

            $arrGeoResult['key'] = cleanupSlugPart($this->formatAddress($addr, "%C-%R-%L"));

            $fmt = array();
            if(!is_null($arrGeoResult['place']))
                $fmt[] = "%L";

            if(strcasecmp($arrGeoResult['countrycode'], 'US') == 0)
            {
                if(!is_null($arrGeoResult['regioncode']))
                    $fmt[] = "%r %c";
                else
                    $fmt[] = "%c";
            }
            else
            {
                if(!is_null($arrGeoResult['region']))
                    $fmt[] = "%R %c";
                else
                    $fmt[] = "%c";
            }

            $arrGeoResult['primary_name'] = $this->formatAddress($addr, join(", ", $fmt));

            return $arrGeoResult;
        }

        return null;
    }


    /**
     * Street Number: %n
     * Street Name: %S
     * place: %L
     * place District: %D
     * Zipcode: %z
     * Country: %C
     * Country Code: %c
     * Timezone: %T
     * Region: %R
     * Region Code: %r
     *
     * @param $objAddress
     * @param $fmt String containing the format to use (e.g. '%S %n, %z %L')
     */
    function formatAddress($objAddress, $fmt)
    {
        // $address is an instance of Address
        $formatter = new StringFormatter();

        // 'Badenerstrasse 120, 8001 Zuerich'
//        $formatter->format($objAddress, '%S %n, %z %L');
        $ret = $formatter->format($objAddress, $fmt);

        return $ret;
    }

    function getAlternateNames($objAddress)
    {
        $altFormats = array(
            'location-place' => '%L',
            'location-place-state' => '%L %R',
            'location-place-state-country' => '%L %R %C',
            'location-place-state-countrycode' => '%L %R %c',
            'location-place-statecode' => '%L %r',
            'location-place-statecode-country' => '%L %r %C',
            'location-place-statecode-countrycode' => '%L %r %c',
            'location-place-country' => '%L %C',
            'location-place-countrycode' => '%L %c',
        );

        $retNames = array();
        // $address is an instance of Address
        $formatter = new StringFormatter();

        // 'Badenerstrasse 120, 8001 Zuerich'
//        $formatter->format($objAddress, '%S %n, %z %L');

        foreach(array_keys($altFormats) as $k)
        {
            $retNames[$k] = $formatter->format($objAddress, $altFormats[$k]);

//            $retNames[$k] = $this->formatAddress($objAddress, $altFormats[$k]);
        }

        if(array_key_exists('colloquial_area', $objAddress))
        {
            $retNames['colloquial_area'] = $objAddress['colloquial_area'];
        }

        return $retNames;
    }

}
