<?php

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\User as BaseUser;
use Propel\Runtime\Map\TableMap;

/**
 * Skeleton subclass for representing a row from the 'user' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class User extends BaseUser
{

	/**
	 * @return \JobScooper\DataAccess\User
	 */
	static function getCurrentUser()
    {
        return getConfigurationSetting('current_user');
    }

    static function setCurrentUser(User $user)
    {
        setConfigurationSetting('current_user', $user);
    }

    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
	    if (array_key_exists("email", $arr)) {
		    $this->setEmailAddress($arr["email"]);
		    unset($arr['email']);
	    }

	    if (array_key_exists("display_name", $arr)) {
		    $this->setName($arr["display_name"]);
		    unset($arr['display_name']);
	    }

	    foreach ($arr as $k => $v)
	    {
	    	unset($arr[$k]);
	    	$arr[ucwords($k)] = $v;
	    }

	    parent::fromArray($arr, $keyType);
    }

}
