<?php
/**
 * Copyright 2014-17 Bryan Selner
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

use JobScooper\DataAccess\Base\LocationNames as BaseLocationNames;
use Propel\Runtime\Connection\ConnectionInterface;

class LocationNames extends BaseLocationNames
{

    public function postSave(ConnectionInterface $con = null)
    {
        parent::postSave($con);

        reloadLocationNamesCache();
    }

//    /**
//     * Code to be run before inserting to database
//     * @param  ConnectionInterface $con
//     * @return boolean
//     */
//    public function preSave(ConnectionInterface $con = null)
//    {
//        $locId = $this->getLocationId();
//        if(is_null($locId)) {
//            $osm = getPlaceFromOpenStreetMap($this->getLocationAlternateName());
//            if(!is_null($osm) && is_array($osm) && array_key_exists('osm_id', $osm) && !is_null($osm['osm_id']))
//            {
//                $loc = getLocationByOsmId($osm['osm_id']);
//                if (!$loc) {
//                    $loc = new \JobScooper\DataAccess\Location();
//                    $loc->fromOSMData($osm);
//                    $loc->save();
//                }
//                $this->setLocation($loc);
//
//                if (is_callable('parent::preSave')) {
//                    return parent::preSave($con);
//                }
//            }
//            else
//            {
//                $this->delete();
//            }
//        }
//
//        return false;
//
//    }
//

}
