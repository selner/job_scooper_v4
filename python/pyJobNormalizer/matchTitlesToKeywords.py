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
from docopt import docopt
from lib.tasks.match_titles import TaskMatchJobsToKeywords


cli_usage = """
Usage:
  matchTitlesToKeywords.py -i <file> -o <file>
  matchTitlesToKeywords.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -o <file>, --output <file> output file with job match results 
  -i <file>, --input <file> input JSON data file with jobs and keywords
"""

if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')

    matcher = TaskMatchJobsToKeywords(inputfile=arguments["--input"].replace("'", ""), outputfile=arguments["--output"].replace("'", ""))
    matcher.export_results()
