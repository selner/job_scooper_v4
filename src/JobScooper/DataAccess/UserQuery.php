<?php

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\UserQuery as BaseUserQuery;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Skeleton subclass for performing query and update operations on the 'user' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class UserQuery extends BaseUserQuery
{

	/**
	 *
	 * @throws \Exception
	 * @throws \Propel\Runtime\Exception\PropelException
	 */
	static function findOrCreateUserByConfigPath($config_file, $arrUserFactsToSet=array())
	{
		if(empty($config_file))
			throw new \Exception("Unable to search for user by config file.  Missing required config file path parameter.");

		$user = UserQuery::create()
			->filterByConfigFilePath($config_file)
			->findOneOrCreate();

		$user->setConfigFilePath($config_file);

		if(!empty($arrUserFactsToSet) && is_array($arrUserFactsToSet))
			$user->fromArray($arrUserFactsToSet);

		$user->save();

		return $user;
	}

	static function findUserByEmailAddress($email_address, $arrUserFactsToSet=array())
	{
		$retUser = null;
		try
		{
			// Search for this email address in the database
			// sort by the last created record first so that
			// we return the most recent match if multiple exist
			$data = UserQuery::create()
				->filterByEmailAddress($email_address)
				->orderByUserId(Criteria::DESC)
				->find()
				->getData();

			if(empty($data))
				return null;

			if(countAssociativeArrayValues($data) > 1)
			{
				$retUser = $data[count($data) - 1];
			}
			else
			{
				$retUser = $data[0];
			}

			if(!empty($arrUserFactsToSet) && is_array($arrUserFactsToSet))
				$retUser->fromArray($arrUserFactsToSet);

			$retUser->save();

			return $retUser;

		}
		catch (\Exception $ex)
		{
			// No user found
			return null;
		}
	}
}
