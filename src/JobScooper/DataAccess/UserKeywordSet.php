<?php

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\UserKeywordSet as BaseUserKeywordSet;
use JobScooper\DataAccess\Map\UserKeywordSetTableMap;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Skeleton subclass for representing a row from the 'user_keyword_set' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class UserKeywordSet extends BaseUserKeywordSet
{

	public function preSave(ConnectionInterface $con = null)
	{

		if ($this->isColumnModified(UserKeywordSetTableMap::COL_KEYWORDS) &&
			!empty($this->getKeywords()))
		{
			$token_keywords = tokenizeKeywords($this->getKeywords());
			$this->setKeywordTokens($token_keywords);
		}

		return parent::preSave($con);
	}


}