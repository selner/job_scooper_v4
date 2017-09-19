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

namespace JobScooper;

use JobScooper\Base\JobSitePlugin as BaseJobSitePlugin;

/**
 * Skeleton subclass for representing a row from the 'jobsite_plugin' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class JobSitePlugin extends BaseJobSitePlugin
{
    protected function updateNextRunDate()
    {
        if(!is_null($this->getLastRunAt()))
        {
            if ($this->getLastRunWasSuccessful() == true || is_null($this->getLastRunWasSuccessful())) {
                $nextDate = $this->getLastRunAt();
                if (is_null($nextDate))
                    $nextDate = new \DateTime();
                date_add($nextDate, date_interval_create_from_date_string('18 hours'));

                $this->setStartNextRunAfter($nextDate);
            }
        }
    }

    function setSuccess($boolVal)
    {
        if($boolVal !== true) {
            $this->setLastFailedAt(time());
            $this->setLastRunWasSuccessful(false);
            $this->setStartNextRunAfter("");
        }
        else
        {
            $this->updateNextRunDate();
            $this->setLastFailedAt("");
            $this->setLastRunWasSuccessful(true);
        }

    }

    public function shouldRunNow()
    {
        $nextTime = $this->getStartNextRunAfter();
        if(!is_null($nextTime))
            return (time() > $nextTime->getTimestamp());

        return true;
    }

}
