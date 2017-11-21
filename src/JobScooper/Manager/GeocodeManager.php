<?php
/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 11/20/17
 * Time: 5:59 PM
 */

namespace JobScooper\Manager;

use Geocoder\Formatter\FormatterInterface;
use Geocoder\Provider\GoogleMapsProvider;
use Geocoder\HttpAdapter\CurlHttpAdapter;
use \Exception;
use Geocoder\Result\ResultInterface;


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


class GeocodeManager
{
    protected $geocoder = null;


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

        $curl = new CurlHttpAdapter();
        $this->geocoder = new \Geocoder\Geocoder();
        $this->geocoder->registerProviders(array(
            new \Geocoder\Provider\GoogleMapsProvider(
                $curl,
                $region=$regionBias,
                $apiKey=$googleApiKey
            )
        ));
    }


    /**
     * @param $strLocation
     */
    function getPlaceForLocationString($strLocation)
    {
        try {
            $addr = $this->geocoder->geocode($strLocation);
        } catch (\Geocoder\Exception\NoResultException $ex) {
            LogDebug("No geocode result was found for " . $strLocation .".  Details: " .$ex->getMessage());
            return null;
        } catch (Exception $ex) {
            handleException($ex);
        }

        if (count($addr) > 0) {
            $arrAddr = $addr->toArray();

            if (array_key_exists('city', $arrAddr))
                $arrAddr['place'] = $arrAddr['city'];
            else
                $arrAddr['place'] = null;

            if (array_key_exists('countryCode', $arrAddr))
                $arrAddr['countrycode'] = $arrAddr['countryCode'];

            if (array_key_exists('regionCode', $arrAddr))
                $arrAddr['regioncode'] = $arrAddr['regionCode'];

//            if (array_key_exists('adminLevels', $arrAddr) && is_array($arrAddr) && count($arrAddr) > 0) {
//                if (count($arrAddr['adminLevels']) >= 1) {
//                    $arrAddr['region'] = $arrAddr['adminLevels'][1]['name'];
//                    $arrAddr['regioncode'] = $arrAddr['adminLevels'][1]['code'];
//                }
//                if (count($arrAddr['adminLevels']) >= 2) {
//                    $arrAddr['county'] = $arrAddr['adminLevels'][2]['name'];
//                }
//            }
//
            $arrAddr['alternate_names'] = $this->getAlternateNames($addr);

            $arrAddr['key'] = cleanupSlugPart($this->formatAddress($addr, "%C-%R-%L"));
            $arrAddr['primary_name'] = $this->formatAddress($addr, "%L, %R, %C");

            return $arrAddr;
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
            'location-place--countrycode' => '%L %c',
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
