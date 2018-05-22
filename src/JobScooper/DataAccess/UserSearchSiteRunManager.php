<?php
/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 5/22/18
 * Time: 2:51 PM
 */

namespace JobScooper\DataAccess;

use JobScooper\Utils\SimpleHTMLHelper;


class UserSearchSiteRunManager {

    /**
	* @param $searchRunFacts
	* @param $err
	* @param \JobScooper\Utils\SimpleHTMLHelper|null $objPageHtml
	*
	* @throws \Propel\Runtime\Exception\PropelException
	*/
    static function failRunWithErrorMessage($searchRunFacts, $err, SimpleHTMLHelper $objPageHtml=null)
    {
        $arrV = '';
        if (is_a($err, \Exception::class) || is_subclass_of($err, \Exception::class)) {
            $arrV = array((string)$err);
        } elseif (is_object($err)) {
            $arrV = get_object_vars($err);
            $arrV['toString'] = (string)$err;
        } elseif (is_string($err)) {
            $arrV = array($err);
        }

        $searchRun = self::getSearchRunObjFromFacts($searchRunFacts);
        $searchRun->setRunResultCode('failed');
        if (null !== $objPageHtml) {
            try {
                $filepath = $objPageHtml->debug_dump_to_file();
                $searchRun->setRunErrorPageHtml($filepath);
            } catch (\Exception $ex) {
                LogWarning('Failed to save HTML for page that generated the error.');
            }
        }
        $searchRun->setRunErrorDetails($arrV);
        $searchRun->save();
        $searchRun = null;
    }


    /**
	* @param $searchRunFacts
	*
	* @return \JobScooper\DataAccess\UserSearchSiteRun
	*/
    static function getSearchRunObjFromFacts($searchRunFacts) {
	    if(is_empty_value($searchRunFacts) || !is_array($searchRunFacts) || !array_key_exists('UserSearchSiteRunId', $searchRunFacts) ||
    	    is_empty_value($searchRunFacts['UserSearchSiteRunId'])) {
        	throw new \InvalidArgumentException('Invalid UserSearchSiteRun details were passed to mark as failed.');
        }

		return self::getSearchRunObjectById($searchRunFacts['UserSearchSiteRunId']);
    }

    /**
	* @param $searchRunId
	*
	* @return \JobScooper\DataAccess\UserSearchSiteRun
	*/
    static function getSearchRunObjectById($searchRunId) {
      $searchRun = UserSearchSiteRunQuery::create()
            ->findOneByUserSearchSiteRunId($searchRunId);

        if(null === $searchRun) {
        	throw new \InvalidArgumentException("Could not find UserSearchSiteRun with UserSearchSiteRunId = {$searchRunId}");
        }
        return $searchRun;
    }

    /**
	* @param $searchRunFacts
	*/
    static function setRunSucceededByFacts($searchRunFacts)
    {
    	self::setRunResultCodeByFacts($searchRunFacts, 'successful');
    }

    /**
	* @param $searchRunFacts
	* @param $code
	*
	* @throws \Propel\Runtime\Exception\PropelException
	*/
    static function setRunResultCodeByFacts($searchRunFacts, $code)
    {
        $searchRun = self::getSearchRunObjFromFacts($searchRunFacts);

        switch ($code) {
            case 'failed':
                break;

            case 'successful':
                $searchRun->removeRunErrorDetail(array());
                break;

            case 'skipped':
                break;

            case 'not-run':
            case 'excluded':
            default:
                break;
        }

        $searchRun->setRunResultCode($code);

        $searchRun->setEndedAt(time());
		$searchRun->save();
		$searchRun = null;
    }

    /**
	* @param $searchFacts
	*
	* @return null
	*/
    static function getUserIdFromSearchFacts($searchFacts)
    {
    	$ret = null;
    	$searchRun = self::getSearchRunObjFromFacts($searchFacts);
    	if(null !== $searchRun) {
	        $searchPair = $searchRun->getUserSearchPairFromUSSR();
	        if(null !== $searchPair) {
	            $ret = $searchPair->getUserId();
	        }
    		$searchPair = null;
    	}
		$searchRun = null;
    	return $ret;
    }
}

