<?php
/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 3/5/18
 * Time: 2:19 AM
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
		if(empty($existingGeo)) {
			$geolocation->save();

			return $geolocation;

		}

		unset($geolocation);
		return $existingGeo;

	}

	/**
	 * {@inheritDoc}
	 */
	public function newInstance()
	{
		return new GeoLocation();
	}
}
