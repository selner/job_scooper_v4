<?php

namespace JobScooper;

use JobScooper\Base\JobLocation as BaseJobLocation;
use Propel\Runtime\Connection\ConnectionInterface;


/**
 * Skeleton subclass for representing a row from the 'job_location' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class JobLocation extends BaseJobLocation
{

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {

        if(is_null($this->getOpenStreetMapId()))
        {
            if(!is_null($this->getPrimaryName()))
            {
                $osm = getOpenStreetMapFacts($this->getPrimaryName());
                $this->fromOSMData($osm);
            }
        }
        return true;
    }

    public function postSave(ConnectionInterface $con = null)
    {
        $ret = parent::postSave($con);

        reloadJobLocationCache();
        return $ret;
    }


    public function fromOSMData($osmPlace)
    {
    
        if(!is_null($osmPlace) && is_array($osmPlace) && count($osmPlace) > 0) {
            $this->setOpenStreetMapId($osmPlace['osm_id']);
            $this->setLatitude($osmPlace['lat']);
            $this->setLogitude($osmPlace['lon']);
            $this->setDisplayName($osmPlace['display_name']);
            if (array_key_exists('place_name', $osmPlace))
                $this->setPlace($osmPlace['place_name']);
            $this->setAlternateNames($osmPlace['namedetails']);
            if (array_key_exists('address', $osmPlace)) {
                if (array_key_exists('state', $osmPlace['address']))
                    $this->setState($osmPlace['address']['state']);

                if (array_key_exists('country', $osmPlace['address']))
                    $this->setCountry($osmPlace['address']['country']);
                if (array_key_exists('country_code', $osmPlace['address']))
                    $this->setCountryCode($osmPlace['address']['country_code']);
            }
        }
    }


//$state = $this->getState();
//if(!is_null($state) && strlen($state) > 0)
//$this->setStateCode($STATE_CODES[$state]);
//

}
