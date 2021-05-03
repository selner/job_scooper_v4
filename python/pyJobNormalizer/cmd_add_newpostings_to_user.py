#!/bin/python
#  -*- coding: utf-8 -*-
#
###########################################################################
#
#  Copyright 2014-18 Bryan Selner
#
#  Licensed under the Apache License, Version 2.0 (the "License"); you may
#  not use this file except in compliance with the License. You may obtain
#  a copy of the License at
#
#      http://www.apache.org/licenses/LICENSE-2.0
#
#  Unless required by applicable law or agreed to in writing, software
#  distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
#  WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
#  License for the specific language governing permissions and limitations
#  under the License.
###########################################################################
from task_add_newposts_to_user import TaskAddNewMatchesToUser
from helpers import docopt_ext, COMMON_OPTIONS, COMMON_OPTIONS
from util_log import logmsg

cli_usage = """
Usage:
  cmd_add_newpostings_to_user.py (-c <dbstring> | --dsn <dbstring>) -u <userid> -j <jobsite>
  cmd_add_newpostings_to_user.py --version
  
Options:
  -u <userid> --userid <userid>     user_id for user to add new matches
  -j <jobsite> --jobsite <jobsite>   jobsitekey for site to add listings from
""" + COMMON_OPTIONS

if __name__ == '__main__':
    args = docopt_ext(cli_usage, version='0.1.1rc')

    try:
        matcher = TaskAddNewMatchesToUser(**args)
    except Exception as ex:
        logmsg(f'Unable to add job matches to user: {ex}')
        raise ex
