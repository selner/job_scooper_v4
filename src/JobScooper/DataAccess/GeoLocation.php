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

use JobScooper\DataAccess\Base\GeoLocation as BaseGeoLocation;
use JobScooper\DataAccess\Map\GeoLocationTableMap;
use JobScooper\Utils\GeoLocationFormatter;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;

function getStateCode($code)
{
    $STATE_CODES=array(
        'ALABAMA'                       =>'AL',
        'ALASKA'                        =>'AK',
        'AMERICAN-SAMOA'                =>'AS',
        'ARIZONA'                       =>'AZ',
        'ARKANSAS'                      =>'AR',
        'CALIFORNIA'                    =>'CA',
        'COLORADO'                      =>'CO',
        'CONNECTICUT'                   =>'CT',
        'DELAWARE'                      =>'DE',
        'DISTRICT-OF-COLUMBIA'          =>'DC',
        'FEDERATED-STATES-OF-MICRONESIA'=>'FM',
        'FLORIDA'                       =>'FL',
        'GEORGIA'                       =>'GA',
        'GUAM'                          =>'GU',
        'HAWAII'                        =>'HI',
        'IDAHO'                         =>'ID',
        'ILLINOIS'                      =>'IL',
        'INDIANA'                       =>'IN',
        'IOWA'                          =>'IA',
        'KANSAS'                        =>'KS',
        'KENTUCKY'                      =>'KY',
        'LOUISIANA'                     =>'LA',
        'MAINE'                         =>'ME',
        'MARSHALL-ISLANDS'              =>'MH',
        'MARYLAND'                      =>'MD',
        'MASSACHUSETTS'                 =>'MA',
        'MICHIGAN'                      =>'MI',
        'MINNESOTA'                     =>'MN',
        'MISSISSIPPI'                   =>'MS',
        'MISSOURI'                      =>'MO',
        'MONTANA'                       =>'MT',
        'NEBRASKA'                      =>'NE',
        'NEVADA'                        =>'NV',
        'NEW-HAMPSHIRE'                 =>'NH',
        'NEW-JERSEY'                    =>'NJ',
        'NEW-MEXICO'                    =>'NM',
        'NEW-YORK'                      =>'NY',
        'NORTH-CAROLINA'                =>'NC',
        'NORTH-DAKOTA'                  =>'ND',
        'NORTHERN-MARIANA-ISLANDS'      =>'MP',
        'OHIO'                          =>'OH',
        'OKLAHOMA'                      =>'OK',
        'OREGON'                        =>'OR',
        'PALAU'                         =>'PW',
        'PENNSYLVANIA'                  =>'PA',
        'PUERTO-RICO'                   =>'PR',
        'RHODE-ISLAND'                  =>'RI',
        'SOUTH-CAROLINA'                =>'SC',
        'SOUTH-DAKOTA'                  =>'SD',
        'TENNESSEE'                     =>'TN',
        'TEXAS'                         =>'TX',
        'UTAH'                          =>'UT',
        'VERMONT'                       =>'VT',
        'VIRGIN-ISLANDS'                =>'VI',
        'VIRGINIA'                      =>'VA',
        'WASHINGTON'                    =>'WA',
        'WEST-VIRGINIA'                 =>'WV',
        'WISCONSIN'                     =>'WI',
        'WYOMING'                       =>'WY'
    );

    if (is_empty_value($code) || !array_key_exists($code, $STATE_CODES)) {
        return null;
    }

    return $STATE_CODES[$code];
}

function getStateByCode($code)
{
    $STATES_BY_CODE = array(
    'AL' => 'ALABAMA',
    'AK' => 'ALASKA',
    'AS' => 'AMERICAN-SAMOA',
    'AZ' => 'ARIZONA',
    'AR' => 'ARKANSAS',
    'CA' => 'CALIFORNIA',
    'CO' => 'COLORADO',
    'CT' => 'CONNECTICUT',
    'DE' => 'DELAWARE',
    'DC' => 'DISTRICT-OF-COLUMBIA',
    'FM' => 'FEDERATED-STATES-OF-MICRONESIA',
    'FL' => 'FLORIDA',
    'GA' => 'GEORGIA',
    'GU' => 'GUAM',
    'HI' => 'HAWAII',
    'ID' => 'IDAHO',
    'IL' => 'ILLINOIS',
    'IN' => 'INDIANA',
    'IA' => 'IOWA',
    'KS' => 'KANSAS',
    'KY' => 'KENTUCKY',
    'LA' => 'LOUISIANA',
    'ME' => 'MAINE',
    'MH' => 'MARSHALL-ISLANDS',
    'MD' => 'MARYLAND',
    'MA' => 'MASSACHUSETTS',
    'MI' => 'MICHIGAN',
    'MN' => 'MINNESOTA',
    'MS' => 'MISSISSIPPI',
    'MO' => 'MISSOURI',
    'MT' => 'MONTANA',
    'NE' => 'NEBRASKA',
    'NV' => 'NEVADA',
    'NH' => 'NEW-HAMPSHIRE',
    'NJ' => 'NEW-JERSEY',
    'NM' => 'NEW-MEXICO',
    'NY' => 'NEW-YORK',
    'NC' => 'NORTH-CAROLINA',
    'ND' => 'NORTH-DAKOTA',
    'MP' => 'NORTHERN-MARIANA-ISLANDS',
    'OH' => 'OHIO',
    'OK' => 'OKLAHOMA',
    'OR' => 'OREGON',
    'PW' => 'PALAU',
    'PA' => 'PENNSYLVANIA',
    'PR' => 'PUERTO-RICO',
    'RI' => 'RHODE-ISLAND',
    'SC' => 'SOUTH-CAROLINA',
    'SD' => 'SOUTH-DAKOTA',
    'TN' => 'TENNESSEE',
    'TX' => 'TEXAS',
    'UT' => 'UTAH',
    'VT' => 'VERMONT',
    'VI' => 'VIRGIN-ISLANDS',
    'VA' => 'VIRGINIA',
    'WA' => 'WASHINGTON',
    'WV' => 'WEST-VIRGINIA',
    'WI' => 'WISCONSIN',
    'WY' => 'WYOMING'
);

    if (is_empty_value($code) || !array_key_exists($code, $STATES_BY_CODE)) {
        return null;
    }

    return $STATES_BY_CODE[$code];
}

/**
* @param $code
* @param bool $reverseMap
 *
 * @return mixed|null
*/function getCountryCodeRemapping($code, $reverseMap=false)
{
	if (is_empty_value($code)) {
		return null;
	}

    $COUNTRY_CODE_REMAPPINGS = array(
        'GB' => 'UK'
    );

	$remapList = $COUNTRY_CODE_REMAPPINGS;
    if($reverseMap === true) {
    	$remapList = array_flip($COUNTRY_CODE_REMAPPINGS);
    }

    if (!array_key_exists($code, $remapList)) {
        return null;
    }

    return $remapList[$code];
}

class GeoLocation extends BaseGeoLocation
{
    public function getCountryCode()
    {
        $ret = parent::getCountryCode();
        $remap = getCountryCodeRemapping($ret, false);
        if (!is_empty_value($remap)) {
            return $remap;
        }
        return $ret;
    }

    public function setCountryCode($value)
    {
        if (!is_empty_value($value)) {
            $remap=getCountryCodeRemapping(strtoupper($value), true);
            if (!is_empty_value($remap)) {
                parent::setCountryCode($remap);
            }
        }
        parent::setCountryCode($value);
    }
    
    public function setAutoPopulatedFields()
    {
        $dispVal = '';
        $keyVal = '';

        if (!empty($this->getPlace())) {
            $dispVal = '%L';
            $keyVal = '%L';
        }
        elseif (!empty($this->getCounty())) {
            $dispVal = '%A2';
            $keyVal = '%A2';
        }

        if ($this->getCountryCode() === 'US') {
            if (!empty($this->getRegionCode())) {
                $dispVal .= ' %r %c';
	            $keyVal = '%c_%r_' . $keyVal;
            } else {
                $dispVal .= ' %c';
	            $keyVal = '%c_%R_' . $keyVal;
            }
        } else {
            if (!empty($this->getRegion())) {
                $dispVal .= ' %R %c';
	            $keyVal = '%c_%R_' . $keyVal;
            } else {
                $dispVal .= ' %c';
	            $keyVal = '%c_' . $keyVal;
            }
        }

        $this->setDisplayName(trim($this->format($dispVal)));

        $this->setGeoLocationKey(strtolower($this->format($keyVal)));
    }

    public function preSave(ConnectionInterface $con = null)
    {
        return parent::preSave($con);
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return int
     * @throws \Exception
     */
    public function save(ConnectionInterface $con = null)
    {
        try {
            return parent::save($con);
        } catch (PropelException $ex) {
            handleException($ex, 'Failed to save GeoLocation ' . $this->getDisplayName() . '.  Error: %s', true);
        }

        return false;
    }

    public function postSave(ConnectionInterface $con = null)
    {
        parent::postSave($con);
    }

    public function setGeoLocationKey($v)
    {
        $v = cleanupSlugPart($v);
        return parent::setGeoLocationKey($v);
    }

    public function setRegion($v)
    {
        parent::setRegion($v);

        $newCode = $this->getRegionCodeFromRegion($v);

        if (!empty($this->getRegionCode())) {
            parent::setRegionCode($newCode);
        }
    }

    public function setRegionCode($v)
    {
        if (null !== $v && strlen($v) > 0) {
            $v = strtoupper($v);
        }

        parent::setRegionCode($v);

        $newState = $this->getRegionFromRegionCode($v);

        if (null === $this->getRegion()) {
            $this->setRegion($newState);
        }
    }


    public function fromGeocode($geocode)
    {
        $arrVals = array();
        foreach (array_keys($geocode) as $field) {
            switch (strtolower($field)) {
                case 'latitude':
                case 'longitude':
                case 'place':
                case 'country':
                case 'countrycode':
                case 'region':
                case 'regioncode':
                case 'county':
                    $arrVals[strtolower($field)] = $geocode[$field];
                    break;

                case 'primary_name':
                    $arrVals['display_name'] = $geocode['primary_name'];
                    break;

                case 'city':
                    $arrVals['place'] = $geocode['city'];
                    break;

                default:
            }
        }

        if (strcasecmp($arrVals['country'], $arrVals['countrycode']) == 0) {
            $arrVals['countrycode'] = null;
        }

        if (strcasecmp($arrVals['region'], $arrVals['regioncode']) == 0) {
            $arrVals['regioncode'] = null;
        }


        $this->fromArray($arrVals, GeoLocationTableMap::TYPE_FIELDNAME);
        $this->setAutoPopulatedFields();
    }

    public function toFlatArrayForCSV()
    {
        $arrItem = $this->toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false);
        updateColumnsForCSVFlatArray($arrItem, new GeoLocationTableMap());

        return $arrItem;
    }

    public function getRegionCodeFromRegion($code)
    {
        $slug= strtoupper(cleanupSlugPart($code));
        $remap = getStateCode($slug);
        if (!is_empty_value($remap)) {
            return $remap;
        }
        return null;
    }

    public function getRegionFromRegionCode($state)
    {
        $slug= strtoupper(cleanupSlugPart($state));
        $remap = getStateByCode($slug);
        if (!is_empty_value($remap)) {
            return $remap;
        }
        return null;
    }

    public function format($fmt)
    {
        $formatter = new GeoLocationFormatter();
        return $formatter->format($this, $fmt);
    }



    public function getVariants()
    {
        $retNames = array();
	
    	if(!is_empty_value($this->getPlace())) {
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
	
	        foreach (array_keys($altFormats) as $k) {
	            $retNames[$k] = $this->format($altFormats[$k]);
	        }
    	}
        return $retNames;
    }


    public function formatLocationByLocationType($locFormatNeeded)
    {
        switch ($locFormatNeeded) {

            case 'location-city':
                $locFormatString = '%L';
                break;

            case 'location-city-comma-statecode':
                $locFormatString = '%L, %r';
                break;

            case 'location-city-space-statecode':
                $locFormatString = '%L %r';
                break;

            case 'location-city-dash-statecode':
                $locFormatString = '%L-%r';
                break;

            case 'location-city-comma-nospace-statecode':
                $locFormatString = '%L,%r';
                break;

            case 'location-city-comma-statecode-underscores-and-dashes':
                $locFormatString = '%L__2c-%r';
                break;

            case 'location-city-comma-state':
                $locFormatString = '%L, %R';
                break;

            case 'location-city-comma-state-country':
                $locFormatString = '%L, %R, %C';
                break;

            case 'location-city-comma-state-country-no-commas':
                $locFormatString = '%L %R %C';
                break;

            case 'location-city-comma-state-comma-country':
                $locFormatString = '%L, %R, %C';
                break;

            case 'location-city-comma-statecode-comma-country':
                $locFormatString = '%L, %r, %C';
                break;

            case 'location-city-comma-state-comma-countrycode':
                $locFormatString = '%L, %R, %c';
                break;

            case 'location-city-comma-country':
                $locFormatString = '%L, %C';
                break;

            case 'location-city-comma-countrycode':
                $locFormatString = '%L, %c';
                break;

            case 'location-city-comma-statecode-comma-countrycode':
                $locFormatString = '%L, %r, %c';
                break;

            case 'location-countrycode':
                $locFormatString = '%c';
                break;

            case 'location-city-country-no-commas':
                $locFormatString = '%L %C';
                break;

            case 'location-state':
                $locFormatString = '%R';
                break;

            case 'location-statecode':
                $locFormatString = '%r';
                break;

            default:
                return $this->getDisplayName();
                break;
        }

        return trim($this->format($locFormatString));
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

}
