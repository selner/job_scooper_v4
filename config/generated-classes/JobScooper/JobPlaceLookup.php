<?php

namespace JobScooper;

use JobScooper\Base\JobPlaceLookup as BaseJobPlaceLookup;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Skeleton subclass for representing a row from the 'job_place_lookup' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class JobPlaceLookup extends BaseJobPlaceLookup
{

    public function postSave(ConnectionInterface $con = null)
    {
        $ret = parent::postSave($con);

        reloadLocationNamesCache();
        return $ret;
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
                    $loc = new \JobScooper\JobLocation();
                    $loc->fromOSMData($osm);
                    $loc->save();
                }
            }
            else
            {
                $loc = new \JobScooper\JobLocation();
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
