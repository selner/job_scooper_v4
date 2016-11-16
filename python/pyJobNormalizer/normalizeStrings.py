#!/bin/python
# -*- coding: utf-8 -*-
import sys
reload(sys)
sys.setdefaultencoding('utf-8')

cli_usage = """
Usage:
  normalizeStrings.py -i <file> -o <file> -k <string> [--index <string>]
  normalizeStrings.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -o <file>, --output <file> output file [default: ./tokenized.csv]
  -i <file>, --input <file> input text file of strings
  -k <string>, --columnkey=<string> csv key name for column to tokenize
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