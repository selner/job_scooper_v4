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
//
// If installed as part of the package, uses Klogger v0.1 version (http://codefury.net/projects/klogger/)
//
require_once(dirname(dirname(__FILE__)) . "/bootstrap.php");


class JobPosting extends \JobScooper\Base\JobPosting
{
    protected function updateAutoColumns()
    {
        $this->setKeyCompanyAndTitle($this->getCompany() . $this->getTitle());
        $this->setKeySiteAndPostID($this->getJobSite() . $this->getJobSitePostID());
    }

    public function setAutoColumnRelatedProperty($method, $v)
    {
        if(is_null($v) || strlen($v) <= 0)
            $v = "_VALUENOTSET_";
        $ret = parent::$method($v);
        $this->updateAutoColumns();
        return $ret;
    }

    public function setCompany($v)
    {
        return $this->setAutoColumnRelatedProperty(__METHOD__, $v);
    }

    public function setJobsite($v)
    {
        return $this->setAutoColumnRelatedProperty(__METHOD__, $v);
    }

    public function setJobSitePostID($v)
    {
        return $this->setAutoColumnRelatedProperty(__METHOD__, $v);
    }

    public function setTitle($v)
    {
        return $this->setAutoColumnRelatedProperty(__METHOD__, $v);
    }


    public function preInsert(\Propel\Runtime\Connection\ConnectionInterface $con = null)
    {
        $this->updateAutoColumns();

        if (is_callable('parent::preInsert')) {
            return parent::preInsert($con);
        }
        return true;
    }


}


