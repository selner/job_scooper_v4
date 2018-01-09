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

import sys
reload(sys)
sys.setdefaultencoding('utf-8')

cli_usage = """
Usage:
  normalizeStrings.py -i <file> -o <file> -c <string> [--index <string>]
  normalizeStrings.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -o <file>, --output <file> output file [default: ./tokenized.csv]
  -i <file>, --input <file> input text file of strings
  -c <string>, --columnkey=<string> csv key name for column to tokenize
  --index <string> csv key name for index column in input csv
"""

from docopt import docopt

if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')
    print sys.argv
    print arguments
    import processfile
    processfile.tokenizeFile(arguments["--input"], arguments["--output"], dataKey=arguments['--columnkey'], indexKey=arguments['--index'])

    print (u"Tokenized results to %s" % arguments["--output"])