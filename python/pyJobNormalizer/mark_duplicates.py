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

import pandas
import sys

reload(sys)
sys.setdefaultencoding('utf-8')

cli_usage = """
Usage:
  mark_duplicates.py -i <file> -o <file>
  mark_duplicates.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -o <file>, --output <file> output JSON file with ID pairs of duplicate listings [DupeID], [OriginalId] 
  -i <file>, --input <file> input JSON data file with job postings 
"""
DATA_KEY_JOBPOSTINGS = u'job_postings'
DATA_KEY_JOBPOSTINGS_KEYFIELD = u'JobPostingId'
DATA_KEY_USER = u'user'
DATA_KEY_OUTPUT_DUPLICATE_IDS = u'user'
JSON_DEDUPE_FIELDS = ["JobPostingId", "Title", "Company", "JobSite", "KeyCompanyAndTitle", "FirstSeenAt", "DuplicatesJobPostingId"]

from docopt import docopt
import json
from processfile import tokenizeStrings
import helpers

import itertools
from operator import itemgetter

class SetEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, set):
            return list(obj)
        return json.JSONEncoder.default(self, obj)


class TaskDedupeJobPostings:
    inputfile = None
    keywords = {}
    negative_keywords = {}
    jobs = {}
    user_id = None
    df_job_tokens = None
    output_data = None

    def __init__(self, inputfile, outputfile):

        self.inputFile = inputfile
        self.outputfile = outputfile

        print "Processing job postings for duplicates from input file {}".format(self.inputFile)

        print "Loading job list to match..."
        self.load_jobpostings()

        print "Deduping job postings..."
        self.dedupe_jobs()

        self.export_results()
        print "Matching completed."

    def dedupe_jobs(self):
        dfJobs = pandas.DataFrame.from_records(self.jobs.values(), index="JobPostingId")
        dfJobs["JobPostingId"] = dfJobs.index
        dfJobs.sort_values('JobPostingId', ascending=True)

        print "Marking jobs as duplicate..."
        dfJobs["is_duplicate"] = dfJobs.duplicated(set(["Company", "TitleTokensString"]), keep="first")
        dfJobs["is_duplicate_stringver"] = dfJobs.duplicated("CompanyTitleTokensString", keep="first")
        dictOrigPosts = dfJobs[(dfJobs["is_duplicate"] == False)].to_dict(orient="index")
        dictDupePosts = dfJobs[(dfJobs["is_duplicate"] == True)].to_dict(orient="index")
        dictOrigByCompTitle = { v["CompanyTitleTokensString"]:v["JobPostingId"] for (n,v) in dictOrigPosts.items() if ("CompanyTitleTokensString") in v.keys()}

        print "Preparing duplicate job post results for export..."
        retDupesByJobId = {}
        for jobid in dictDupePosts:
            item = dictDupePosts[jobid]
            strCompTitle = item["CompanyTitleTokensString"]
            retDupesByJobId[jobid] = {
                "JobPostingId" : jobid,
                "CompanyTitleTokensString" : strCompTitle,
                "isDuplicateOf" : dictOrigByCompTitle[strCompTitle]
                # ,
                # "DuplicateJob" : item,
                # "SourceJob" : dictOrigPosts[dictOrigByCompTitle[strCompTitle]]
            }

        print "{} / {} job postings have been marked as duplicate".format(len(retDupesByJobId), len(self.jobs))
        self.output_data = { "duplicate_job_postings" : retDupesByJobId }

    def export_results(self):
        print "Exporting final match results to {}".format(self.outputfile)

        outf = open(self.outputfile, "w")
        json.dump(self.output_data, outf, indent=4, encoding='utf-8', cls=SetEncoder)
        outf.close()

        print "Job post duplicates exported to {}".format(self.outputfile)

        return self.outputfile

    def load_jobpostings(self):
        inf = open(self.inputFile, "rU")

        jobsdata = {}
        input_data = {}
        if str(self.inputFile).endswith(".csv"):
            print "Loading jobs from CSV file {}".format(self.inputFile)

            jobsdata = helpers.load_ucsv(self.inputFile, fieldnames=None, delimiter=",", quotechar="\"", keyfield=DATA_KEY_JOBPOSTINGS_KEYFIELD)
        elif str(self.inputFile).endswith(".json"):
            print "Loading jobs from JSON file {}".format(self.inputFile)
            inputData = json.load(inf)
            if inputData:
                if isinstance(inputData, dict):
                    if (DATA_KEY_JOBPOSTINGS in inputData and isinstance(inputData[DATA_KEY_JOBPOSTINGS], dict) and len(
                            inputData[DATA_KEY_JOBPOSTINGS]) > 0):
                        jobsdata = inputData[DATA_KEY_JOBPOSTINGS]
        else:
            raise Exception("Unknown input data file format: {}".format(self.inputFile))

        print "Loaded {} total jobs to deduplicate.".format(len(jobsdata))

        print "Tokenizing job titles for duplicate matching"
        jobsdata = tokenizeStrings(jobsdata, u'Title', u'TitleTokens', u'set')

        print "Reorganizing source data for duplicate matching..."
        for rowkey in jobsdata.keys():
            item = jobsdata[rowkey]
            subitem = {}
            if item:
                for k, v in item.items():
                    if k in JSON_DEDUPE_FIELDS:
                        subitem[k] = v
                subitem["TitleTokensString"] = "~".join(item["TitleTokens"])
                subitem["CompanyTitleTokensString"] = "{}__{}".format(subitem["Company"], subitem["TitleTokensString"])
                self.jobs[rowkey] = subitem
        jobsdata = None

        print "{} job postings loaded and ready for deduplication.".format(len(self.jobs))


if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')

    matcher = TaskDedupeJobPostings(arguments["--input"].replace("'", ""), arguments["--output"].replace("'", ""))
