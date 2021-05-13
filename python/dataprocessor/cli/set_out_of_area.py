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
from dataprocessor.tasks.mark_outofarea_matches import TaskMarkOutOfAreaMatches
from dataprocessor.utils.doctoptext import docopt_ext, COMMON_OPTIONS
from dataprocessor.utils.log  import logmsg

cli_usage = """
Usage:
  {} (-c <dbstring> | --dsn <dbstring> | --host <hostname> --port <portid> --database <dbstring> --user <userstring> --password <userpass>) --jobuserid jobuser
  {} --version
  
Options:
  --jobuserid <userid>    slug key for user to update matches on
""" + COMMON_OPTIONS

if __name__ == '__main__':
    args = docopt_ext(cli_usage, version='0.1.1rc', filename=__file__)

    try:
        if not ("jobuserid" in args and args["jobuserid"]):
            raise Exception("Missing user parameter.")

        matcher = TaskMarkOutOfAreaMatches(**args)
        matcher.mark_out_area()
    except Exception as ex:
        logmsg(f'Unable to deduplicate job postings: {ex}')
        raise ex
