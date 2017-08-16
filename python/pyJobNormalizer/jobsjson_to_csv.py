#!/bin/python
# -*- coding: utf-8 -*-
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
    dataJobs = json.load(fp=f, encoding="utf-8")
    dictJobs = dataJobs['jobslist']

    writedicttocsv(outfile, dictJobs, keys=["JobPostingId", "JobSite", "JobSitePostID", "Title", "TitleTokens", "Url", "Company", "Location", "EmploymentType", "Department", "Category", "UpdatedAt", "PostedAt", "FirstSeenAt", "RemovedAt", "KeySiteAndPostID", "KeyCompanyAndTitle"])

    print (u"CSV results written to %s" % outfile)

