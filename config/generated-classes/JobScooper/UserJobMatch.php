<?php

namespace JobScooper;

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
    }

//    private $delim = ' | ';
//
//    function updateMatchExclusionReason($newData)
//    {
//        $current = $this->getUserMatchExcludeReason();
//        if (is_string($current) && strlen($current) > 0) {
//            $this->setUserMatchExcludeReason($current . $this->delim . $newData);
//        }
//        else
//        {
//            $this->setUserMatchExcludeReason($newData);
//        }
//    }

}
