<?php
/**
 * Copyright 2014-18 Bryan Selner
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
namespace JobScooper\Utils;

use JobScooper\DataAccess\JobPostingQuery;
use JobScooper\DataAccess\Map\JobPostingTableMap;
use JobScooper\Manager\LoggingManager;
use Monolog\ErrorHandler;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
/**
 * Class JobsExceptionHandler
 * @package JobScooper\Utils
 */
class JobsExceptionHandler extends ErrorHandler
{
    /**
     * @param $e
     *
     * @throws \Exception
     */
    public function handleThrowable($e)
    {
        if (is_a($e, \Error::class)) {
            $e = new \ErrorException($e->getMessage(), $e->getCode(), $severity = 1, $e->getFile(), $e->getLine(), $e->getPrevious());
        }

        if (empty($GLOBALS['logger'])) {
            $GLOBALS['logger'] = LoggingManager::getInstance();
        }

        LogError(sprintf('Uncaught Exception: %s', $e->getMessage()), null, $e);
        
        if(is_a($e, PropelException::class)) {
        	$con = Propel::getWriteConnection(JobPostingTableMap::DATABASE_NAME);
			JobPostingQuery::create()->filterByFirstSeenAt(null)->find($con);
			$msg = " [{$con->getQueryCount()} open queries on Propel connection]";

        	$lastq = $con->getLastExecutedQuery();
        	if(!is_empty_value($lastq)) {
				$msg .= " [Last Executed Query: {$lastq}.";
        	}

	        handleThrowable($e, 'Uncaught Propel Exception: %s ' . $msg, false);

			throw $e;
        }
        
        handleThrowable($e, 'Uncaught Exception: %s', false);
    }
}
