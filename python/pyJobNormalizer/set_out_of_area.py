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
from lib.tasks.mark_outofarea_matches import TaskMarkOutOfAreaMatches
from docopt import docopt

cli_usage = """
Usage:
  set_out_of_area.py -c <dbstring> -u user
  set_out_of_area.py --version
  
Options:
  -h --help     show this help message and exit
  --version     show version and exit
  -v --verbose      print status messages
  -c <dbstring>, --connecturi <dbstring>    connection string uri or dsn for a database to use    
  -u <userkey>, --user <userkey>    slug key for user to update matches on
"""

if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')
    args = {k.replace("--", ""): arguments[k] for k in arguments.keys()}

    if "connecturi" in args and args["connecturi"] and "user" in args and args["user"]:
        matcher = TaskMarkOutOfAreaMatches(**args)
        matcher.mark_out_area()
    else:
        print(u"Unable to mark job matches as out of area.  Missing script arguments.")
