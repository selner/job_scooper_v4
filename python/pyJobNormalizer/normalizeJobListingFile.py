#!/bin/python
# -*- coding: utf-8 -*-
import sys
import uuid
reload(sys)
sys.setdefaultencoding('utf-8')

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

from docopt import docopt

if __name__ == '__main__':
    print " ".join(sys.argv)
    arguments = docopt(cli_usage, version='0.1.1rc')
    print arguments
    import processfile

    dataKey = arguments['--column']
    if dataKey is None:
        dataKey = "Title"

    indexKey = arguments['--index']
    if indexKey is None:
        indexKey = "KeyCompanyAndTitle"

    if arguments['--infile']:
        infile = arguments['--infile'].replace("'", "")

    if arguments['--outfile']:
        outfile = arguments['--outfile'].replace("'", "")

    tokfile = processfile.tokenizeJSONFile(infile, outfile, dataKey=dataKey, indexKey=indexKey)

    print (u"Tokenized results completed.")