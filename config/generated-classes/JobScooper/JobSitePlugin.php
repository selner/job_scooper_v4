<?php

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

    public function preInsert(\Propel\Runtime\Connection\ConnectionInterface $con = null)
    {
        $this->updateNextRunDate();
        if (is_callable('parent::preInsert')) {
            return parent::preInsert($con);
        }
        return true;
    }

    public function preUpdate(\Propel\Runtime\Connection\ConnectionInterface $con = null)
    {
        $this->updateNextRunDate();

        if (is_callable('parent::preUpdate')) {
            return parent::preUpdate($con);
        }
        return true;
    }

    function setSuccess($boolVal)
    {
        if($boolVal == false) {
            $this->setLastFailedAt(time());
            $this->setLastRunWasSuccessful(false);
        }
        else
        {
            $this->setLastFailedAt(null);
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
