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

namespace JobScooper;
require_once dirname(dirname(dirname(dirname(__FILE__))))."/bootstrap.php";

use JobScooper\Base\UserJobMatch as BaseUserJobMatch;

/**
 * Skeleton subclass for representing a row from the 'user_job_match' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class UserJobMatch extends BaseUserJobMatch
{
    public function __construct()
    {
        $this->setAppRunId($GLOBALS['USERDATA']['configuration_settings']['app_run_id']);
        parent::__construct();
    }

    private $delim = ' | ';

    function updateMatchNotes($newData)
    {
        $current = $this->getMatchNotes();
        if (is_string($current) && strlen($current) > 0) {
            $this->setMatchNotes($current . $this->delim . $newData);
        }
        else
        {
            $this->setMatchNotes($newData);
        }
    }

}
