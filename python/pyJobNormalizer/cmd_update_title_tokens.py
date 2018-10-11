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
from task_tokenize_jobtitles import TaskAddTitleTokens
from helpers import docopt_ext


cli_usage = """
Usage:
  cmd_update_title_tokens.py -c <dbstring>
  cmd_update_title_tokens.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -c <dbstring>, --connecturi <dbstring>    connection string uri or dsn for a database to use    
"""

if __name__ == '__main__':
    arguments = docopt_ext(cli_usage, version='0.1.1rc')

    toks = TaskAddTitleTokens(**arguments)
    toks.update_jobs_without_tokens()

