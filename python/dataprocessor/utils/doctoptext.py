###########################################################################
#
#  Copyright 2014-2021 Bryan Selner
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
#
############################################################################

import docopt
import os

docopt_func = getattr(docopt, 'docopt')

COMMON_OPTIONS = """
  --dsn <dbstring>                          DSN connection string for database     
  -c <dbstring>, --connecturi <dbstring>    connection string uri or dsn for a database to use    
  --log <logdir>                            output directory for logging
  -u <userstring> --user <userstring>                       DB user for connection
  -P <userpass> --password <userpass>                         DB user password for connection
  -h <hostname> --host <hostname>                         DB server host for connection
  -p <portid> --port <portid>                           DB server port for connection
  --database <dbstring>                     DB server database for connection      
  -h --help                                 show this help message and exit
  --version                                 show version and exit
  -v --verbose                              print status messages
"""

def docopt_ext(doc, argv=None, help=True, version=None, options_first=False, filename=None):
    from dataprocessor.utils.log import logurulogger

    if filename:
        logfile = f'dataprocessor-{os.path.basename(filename)[:-3]}'
        logurulogger.add(f'/tmp/{logfile}.log', format="{time} {level} {message}", level="INFO")

    vals = docopt_func(doc, argv, help, version, options_first)
    if vals and len(vals) > 0:
        retvals = {}
        for k in vals.keys():
            key = k
            if k.startswith("--"):
                key = k[2:]

            v = vals[k]

            if v and isinstance(v, str) and v.startswith("'") and v.endswith("'"):
                v = v[1:-1]

            retvals[key] = v

        return retvals

    return vals

