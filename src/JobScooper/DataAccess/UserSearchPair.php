<?php

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\UserSearchPair as BaseUserSearchPair;

/**
 * Skeleton subclass for representing a row from the 'user_search_pair' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class UserSearchPair extends BaseUserSearchPair
{

    /**
     * @return null|string
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getCountryCode() {
        $geoloc = $this->getGeoLocationFromUS();
        if(!is_empty_value($geoloc)) {
            return $geoloc->getCountryCode();
        }

        return null;
    }
}
