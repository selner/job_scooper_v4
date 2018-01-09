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
import unicodecsv

reload(sys)
sys.setdefaultencoding('utf-8')

cli_usage = """
Usage:
  jobsjson_to_csv.py -i <file> -o <file>

Options:
  -h --help  show this help message and exit
  -i <file>, --input <file> input jobs data JSON filepath
  -o <file>, --output <file> output filepath
"""

from docopt import docopt

from processfile import writedicttocsv

if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')
    print sys.argv
    print arguments
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


    print (u"Reading jobs data file from to %s" % infile)
    # dfResults = pd.read_json(infile, orient="columns", encoding="utf-8")
    # dictJobs = dfResults.to_dict()['jobslist']

    import json, codecs

    f = codecs.open(infile, encoding='utf-8', mode='rb')
    txt = "\n".join(f.readlines())
    print ("length of doc is " + str(len(txt)))

    dataJobs = json.loads(txt, encoding="utf-8")
    dictJobs = dataJobs['jobslist']

    writedicttocsv(outfile, dictJobs, keys=["JobPostingId", "JobSite", "JobSitePostId", "Title", "TitleTokens", "Url", "Company", "Location", "EmploymentType", "Department", "Category", "UpdatedAt", "PostedAt", "FirstSeenAt", "RemovedAt", "KeySiteAndPostId", "KeyCompanyAndTitle"])

    print (u"CSV results written to %s" % outfile)

