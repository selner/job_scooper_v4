# #!/bin/python
# # -*- coding: utf-8 -*-
# import os
# import sys
# reload(sys)
# sys.setdefaultencoding('utf-8')
#
# cli_usage = """
# Usage:
#   matchTokens.py -i <file> -o <file> -kmatch1 <string> -kmatch2 <string>
#   normalizeStrings.py --version
#
# Options:
#   -h --help  show this help message and exit
#   --version  show version and exit
#   -v --verbose  print status messages
#   -o <file>, --output <file> output file [default: ./tokenized.csv]
#   -i1 <file>, --input1 <file> input text file of list of keywords to match
#   -i2 <file>, --input2 <file> input text to match strings in file against
#   -kmatch1 <string>, --columnkey1=<string> csv key name for the column to match from input1
#   -kmatch2 <string>, --columnkey2=<string> csv key name for the column to match from input2
# """
#
# from docopt import docopt
#
# if __name__ == '__main__':
#     arguments = docopt(cli_usage, version='0.1.1rc')
#     print sys.argv
#     print arguments
#     import processfile
#     processfile.tokenizeFile(arguments["--input1"], arguments["--input2"], arguments["--output"], kmatch1=arguments['--columnkey1'],kmatch2=arguments['--columnkey2'])
#
#     print (u"Tokenizing and matching results:\n\t input file:{}\n\t output file:{}\n\t column 1 key: " % {arguments["--input"], arguments["--output"], arguments["--columnkey1"], arguments["--columnkey2"]})