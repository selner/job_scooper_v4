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
from processfile import writedicttocsv

cli_usage = """
Usage:
  jobsjson_to_csv.py -i <file> -o <file>

Options:
  -h --help  show this help message and exit
  -i <file>, --input <file> input jobs data JSON filepath
  -o <file>, --output <file> output filepath
"""

if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')
    print(sys.argv)
    print(arguments)
    infile = None
    outfile = None

    if arguments['--input']:
        infile = arguments['--input'].replace("'", "")
    else:
        raise Exception("No input file specified.")

    if arguments['--output']:
        outfile = arguments['--output'].replace("'", "")
    else:
        raise Exception("No output file specified.")

    print(u"Reading jobs data file from to %s" % infile)
    # dfResults = pd.read_json(infile, orient="columns", encoding="utf-8")
    # dictJobs = dfResults.to_dict()['jobslist']

    import json, codecs

    f = codecs.open(infile, encoding='utf-8', mode='rb')
    dataJobs = json.load(fp=f, encoding="utf-8")
    dictJobs = dataJobs['jobslist']

    writedicttocsv(outfile, dictJobs,
                   keys=["company", "job_title", "job_post_url", "location", "employment_type", "job_site_category",
                         "job_site_date", "interested", "site_name", "job_id"])

    print(u"CSV results written to %s" % outfile)
