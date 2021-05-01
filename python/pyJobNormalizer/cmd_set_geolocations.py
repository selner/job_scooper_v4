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
from task_find_and_match_places import FindPlacesFromDBLocationsTask
from helpers import docopt_ext

cli_usage = """
Usage:
  cmd_set_geolocations.py  (-c <dbstring> | --dsn <dbstring>)  -s <server>
  cmd_set_geolocations.py --version
  
Options:
  -h --help     show this help message and exit
  --version     show version and exit
  -v --verbose      print status messages
  -c <dbstring>, --connecturi <dbstring>    connection string uri or dsn for a database to use    
  --dsn <dbstring>                          DSN connection string for database     
-s <server>, --server <server>    hostname for geocode api server [default: http://0.0.0.0:5000]
"""

if __name__ == '__main__':
    args = docopt_ext(cli_usage, version='0.1.1rc')

    try:
        matcher = FindPlacesFromDBLocationsTask(**args)
        matcher.update_all_locations(**args)
    except Exception as ex:
        print(f'Unable to set geolocations: {ex}')
        raise ex
