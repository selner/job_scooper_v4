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
from task_match_titles import TaskMatchJobsToKeywords
from util_log import logmsg


cli_usage = """
Usage:
  cmd_match_titles_to_keywords.py -i <file> -o <file> (-c <dbstring> | --dsn <dbstring> | --host <hostname> --port <portid> --database <dbstring> --user <userstring> --password <userpass>)
  cmd_match_titles_to_keywords.py --version

Options:
  -o <file>, --output <file> output file with job match results 
  -i <file>, --input <file> input JSON data file with jobs and keywords
""" + COMMON_OPTIONS

if __name__ == '__main__':
    args = docopt_ext(cli_usage, version='0.1.1rc', filename=__file__)

    try:
        matcher = TaskMatchJobsToKeywords(**args)
        matcher.export_results()
    except Exception as ex:
        logmsg(f'Unable to match title keywords: {ex}')
        raise ex
