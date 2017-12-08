<?php

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\UserSearchSiteRun as BaseUserSearchSiteRun;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Skeleton subclass for representing a row from the 'user_search_site_run' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class UserSearchSiteRun extends BaseUserSearchSiteRun
{
	function failRunWithErrorMessage($err)
	{
		$arrV = "";
		if(is_a($err, "\Exception") || is_subclass_of($err, "\Exception"))
		{
			$arrV = array(strval($err));
		}
		elseif(is_object($err))
		{
			$arrV = get_object_vars($err);
			$arrV["toString"] = strval($err);
		}
		elseif(is_string($err))
			$arrV = array($err);

		$this->setRunResultCode("failed");
		parent::setRunErrorDetails($arrV);
	}

	function setRunSucceeded()
	{
		return $this->setRunResultCode('successful');
	}

	function setRunResultCode($val)
	{
		switch ($val) {
			case "failed":
				break;

			case 'successful':
				$this->setRunErrorDetails(array());
				break;

			case "skipped":
				break;

			case "not-run":
			case "excluded":
			default:
				break;
		}

		$ret = parent::setRunResultCode($val);

		parent::setEndedAt(time());

		return $ret;

	}

	function getUserKeywordSet(ConnectionInterface $con = null)
	{
		$user_search = $this->getUserSearch($con);
		if (!empty($user_search))
			return $user_search->getUserKeywordSetFromUS($con);

	}


	function getUserSearch(ConnectionInterface $con = null)
	{
		return $this->getUserSearchFromUSSR($con);
	}


	/**
	 * Derived method to catches calls to undefined methods.
	 *
	 *
	 * @param string $name
	 * @param mixed  $params
	 *
	 * @return array|string
	 */
	public function __call($method, $params)
	{
		$user_kwd_set = null;
		$user = null;
		$user_search = $this->getUserSearch();
		$user_kwd_set = $this->getUserKeywordSet();

		if(method_exists($this, $method)) {
			return call_user_func(
				array($this, $method),
				$params
			);
		}
		else {
			foreach(array($user_search, $user_kwd_set, $user) as $relObject)
			{
				if(method_exists($relObject, $method)) {
					return call_user_func(
						array($relObject, $method),
						$params
					);
				}
			}
		}

		return false;
	}

}