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

namespace JobScooper\DataAccess;


use JBZoo\Utils\Exception;
use JobScooper\DataAccess\Base\UserSearchRun as BaseUserSearchRun;

class UserSearchRun extends BaseUserSearchRun
{
    function failRunWithErrorMessage($err)
    {
        $arrV = "";
        if(is_a($err, "\Exception") || is_subclass_of($err, "\Exception"))
        {
            $arrV = array(strval($err));
        }
        elseif(is_object($err))
        {
            $arrV = get_object_vars($err);
            $arrV["toString"] = strval($err);
        }
        elseif(is_string($err))
            $arrV = array($err);

        $this->setRunResultCode("failed");
        parent::setRunErrorDetails($arrV);
    }

    function setRunSucceeded()
    {
        return $this->setRunResultCode('successful');
    }

    function setRunResultCode($val)
    {
        switch ($val) {
            case "failed":
                break;

            case 'successful':
                $this->setRunErrorDetails(array());
                break;

            case "skipped":
                break;

            case "not-run":
            case "excluded":
            default:
                break;
        }

        $ret = parent::setRunResultCode($val);

        parent::setEndedAt(time());

        return $ret;

    }

}
