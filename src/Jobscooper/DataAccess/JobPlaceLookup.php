<?php

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\JobPlaceLookup as BaseJobPlaceLookup;
use Propel\Runtime\Connection\ConnectionInterface;

class JobPlaceLookup extends BaseJobPlaceLookup
{

    public function postSave(ConnectionInterface $con = null)
    {
        parent::postSave($con);

        reloadLocationNamesCache();
    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        $locId = $this->getLocationId();
        if(is_null($locId)) {
            $osm = getOpenStreetMapFacts($this->getPlaceAlternateName());
            if(!is_null($osm) && is_array($osm)) {
                $loc = getJobLocationByOsmId($osm['osm_id']);
                if (!$loc) {
                    $loc = new \JobScooper\DataAccess\JobLocation();
                    $loc->fromOSMData($osm);
                    $loc->save();
                }
            }
            else
            {
                $loc = new \JobScooper\DataAccess\JobLocation();
                $loc->setPrimaryName($this->getPlaceAlternateName());
                $loc->save();
            }
            $this->setJobLocation($loc);
        }

        if (is_callable('parent::preSave')) {
            return parent::preSave($con);
        }

        return true;

    }

}
