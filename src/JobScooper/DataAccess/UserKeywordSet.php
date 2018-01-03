<?php

namespace JobScooper\DataAccess;

use \Exception;
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
	public function setKeywords($v)
	{
		if(is_string($v))
		{
			$v = cleanupTextValue($v);
		}
		else if(is_array($v))
		{
			foreach($v as $k => $item)
			{
				$newItem = cleanupTextValue($item);
				if(empty($newItem))
					unset($v[$k]);
				else
					$v[$k] = $newItem;
			}
		}

		$ret = parent::setKeywords($v);
		if(!empty($this->getKeywords()))
		{
			$token_keywords = $this->_tokenizeKeywords($this->getKeywords());
			$this->setKeywordTokens($token_keywords);
		}

		return $ret;
	}

	private function _tokenizeKeywords($arrKeywords)
	{
		if (!is_array($arrKeywords)) {
			throw new Exception("Invalid keywords object type.");
		}

		if(empty($arrKeywords))
			return null;

		$arrKeywordTokens = tokenizeSingleDimensionArray($arrKeywords, "srchkwd", "keywords", "keywords");
		$arrReturnKeywordTokens = array_fill_keys(array_keys($arrKeywordTokens), null);
		foreach (array_keys($arrReturnKeywordTokens) as $key) {
			$arrReturnKeywordTokens[$key] = str_replace("|", " ", $arrKeywordTokens[$key]['keywordstokenized']);
		}
		unset($key);

		return $arrReturnKeywordTokens;
	}
}
