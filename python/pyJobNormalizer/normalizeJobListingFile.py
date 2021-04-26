#!/bin/python
###############################################################################
#   Copyright 2014-21 Bryan Selner
#
#   Licensed under the Apache License, Version 2.0 (the "License"); you may
#   not use this file except in compliance with the License. You may obtain
#   a copy of the License at
#
#       http://www.apache.org/licenses/LICENSE-2.0
#
#   Unless required by applicable law or agreed to in writing, software
#   distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
#   WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
#   License for the specific language governing permissions and limitations
#   under the License.
#
###############################################################################
import sys
from docopt import docopt

cli_usage = """
Usage:
  normalizeJobListingFile.py --infile <string> --outfile <string> [-b <string> --source <string> --column <string> --index <string>]
  normalizeJobListingFile.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -b <string>, --bucket <string> AWS S3 bucket name to use
  -c <string>, --column=<string> csv key name for column to tokenize
  --source=<string> either "s3" or the local directory that contains the files.  [default: s3]
  --infile=<string> file key name for the input file
  --outfile=<string> file key name for the output file
  --index=<string> csv key name for index column in input csv
"""


if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')
    print(sys.argv)
    print(arguments)
    import processfile

    dataKey = arguments['--column']
    if dataKey is None:
        dataKey = "job_title"

    indexKey = arguments['--index']
    if indexKey is None:
        indexKey = "key_jobsite_siteid"

    if arguments['--infile'] and arguments['--outfile']:
        infile = arguments['--infile'].replace("'", "")
        outfile = arguments['--outfile'].replace("'", "")

        tokfile = processfile.tokenizeJSONFile(infile, outfile, dataKey=dataKey, indexKey=indexKey)
    else:
        raise Exception("You must specify both --infile and --outfile parameters.  Aborting.")

    print(u"Tokenized results completed.")
