<?php

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\UserKeywordSet as BaseUserKeywordSet;

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

		return parent::setKeywords($v);
	}
}
