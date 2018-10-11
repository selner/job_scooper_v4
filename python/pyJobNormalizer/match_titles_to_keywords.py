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
from helpers import docopt_ext
from task_match_titles import TaskMatchJobsToKeywords


cli_usage = """
Usage:
  match_titles_to_keywords.py -i <file> -o <file>
  match_titles_to_keywords.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -o <file>, --output <file> output file with job match results 
  -i <file>, --input <file> input JSON data file with jobs and keywords
"""

if __name__ == '__main__':
    arguments = docopt_ext(cli_usage, version='0.1.1rc')



if __name__ == '__main__':
    arguments = docopt_ext(cli_usage, version='0.1.1rc')

    # if not arguments["--input"] or not arguments["--output"]:
    #     print("Unable to deduplicate job postings.  Missing script arguments.")
    # else:
    #     matcher = TaskDedupeJobPostings(arguments["--input"].replace("'", ""), arguments["--output"].replace("'", ""))

    if "input" in arguments and arguments["input"] and "output" in arguments and arguments["output"]:
        matcher = TaskMatchJobsToKeywords(inputfile=arguments["input"].replace("'", ""),
                                          outputfile=arguments["output"].replace("'", ""))
        matcher.export_results()

    elif "--connecturi" in arguments and arguments["--connecturi"]:
        matcher = TaskMatchJobsToKeywords(connecturi=arguments['--connecturi'])
        matcher.export_results()
    else:
        print(u"Unable to deduplicate job postings.  Missing script arguments.")
