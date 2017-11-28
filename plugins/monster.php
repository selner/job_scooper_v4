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


class AbstractMonster extends \JobScooper\Plugins\Classes\ServerHtmlSimplePlugin
{
    function getDaysURLValue($days = null) {
        $ret = "1";

        if($days != null)
        {
            switch($days)
            {

                case ($days>=31):
                    $ret = "";
                    break;

                case ($days>=15 && $days<31):
                    $ret = "30";
                    break;

                case ($days>=7 && $days<15):
                    $ret = "14";
                    break;

                case ($days>=3 && $days<7):
                    $ret = "7";
                    break;

                case ($days>=1 && $days<3):
                    $ret = "3";
                    break;


                case $days<=1:
                default:
                    $ret = 1;
                    break;

            }
        }

        return $ret;

    }
}
