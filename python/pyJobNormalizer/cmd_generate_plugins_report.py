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
from helpers import docopt_ext, COMMON_OPTIONS
from task_generate_broken_plugins_data import TaskGenerateBrokenPluginReportData

from util_log import logmsg

cli_usage = """
Usage:
  {} (-c <dbstring> | --dsn <dbstring>) -o <outputfile>
  {} --version

Options:
  -o <file>, --output <file> output file with job match results 
""" + COMMON_OPTIONS

if __name__ == '__main__':

    args = docopt_ext(cli_usage.format(__file__, __file__), version='0.1.1rc')

    try:
        if "output" in args and args["output"] :
            reporter = TaskGenerateBrokenPluginReportData(**args)
        else:
            raise Exception("Missing output parameter.")
    except Exception as ex:
        logmsg(f'Unable to generate broken plugin report data:  {ex}')
        raise ex
