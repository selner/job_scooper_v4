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
from lib.tasks.find_and_match_places import FindPlacesFromDBLocationsTask
from docopt import docopt

cli_usage = """
Usage:
  set_geolocations.py -c <dbstring> -s <server>
  set_geolocations.py --version
  
Options:
  -h --help     show this help message and exit
  --version     show version and exit
  -v --verbose      print status messages
  -c <dbstring>, --connecturi <dbstring>    connection string uri or dsn for a database to use    
  -s <server>, --server <server>    hostname for geocode api server [default: http://0.0.0.0:5000]
"""


if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')
    args = { k.replace("--", ""):arguments[k] for k in arguments.keys()}

    if "connecturi" in args and args["connecturi"] and "server" in args and args["server"]:
        matcher = FindPlacesFromDBLocationsTask()
        matcher.update_all_locations(**args)
    else:
        print("Unable to update locations for job postings.  Missing script arguments.")
