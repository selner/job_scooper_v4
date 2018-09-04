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
import pymysql

import pandas
from datetime import *
# Print all queries to stderr.
import logging

logger = logging.getLogger('peewee')
logger.addHandler(logging.StreamHandler())
logger.setLevel(logging.DEBUG)

cli_usage = """
Usage:
  mark_duplicates.py (-i <file> -o <file>) | -c <dbstring>
  mark_duplicates.py --version
  
Options:
  -h --help     show this help message and exit
  --version     show version and exit
  -v --verbose      print status messages
  -o <file>, --output <file>    output JSON file with ID pairs of duplicate listings 
  -i <file>, --input <file>     input JSON data file with job postings
  -c <dbstring>, --connecturi <dbstring>    connection string uri or dsn for a database to use    
"""
DATA_KEY_JOBPOSTINGS = u'job_postings'
DATA_KEY_JOBPOSTINGS_KEYFIELD = u'JobPostingId'
DATA_KEY_USER = u'user'
DATA_KEY_OUTPUT_DUPLICATE_IDS = u'user'
JSON_DEDUPE_FIELDS = ["JobPostingId", "Title", "Company", "JobSite", "GeoLocationId", "KeyCompanyAndTitle",
                      "FirstSeenAt", "DuplicatesJobPostingId"]

from docopt import docopt
import json
from processfile import tokenizeStrings
import helpers


def dict_from_class(cls, excluded_keys=[]):
    return dict(
        (key, value)
        for (key, value) in cls.__dict__.items() if key not in excluded_keys)


class TaskDedupeJobPostings:
    connecturi = None

    keywords = {}
    negative_keywords = {}
    jobs = {}
    user_id = None
    df_job_tokens = None
    output_data = None

    @property
    def outputfile(self):
        return self.outputfile

    @outputfile.setter
    def outputfile(self, filepath):
        self.outputfile = filepath

    def __init__(self, outputfile=None):
        self.outputfile = outputfile

        # self.load
        # # if self.connecturi:
        # self.database = MySQLDatabase('js4-docker2', **{'host': '192.168.24.209', 'password': 'orange11', 'user': 'jobscooper', 'use_unicode': True, 'charset': 'utf8', 'port': 3307})
        # self.load_from_database()
        #
        # if self.jobs:
        #     print("Deduping job postings...")
        #     self.dedupe_jobs()
        #
        #     self.update_database()
        #
        #     self.export_results()
        #     print("Matching completed.")
        #

    def dedupe_jobs(self):
        dfJobs = pandas.DataFrame.from_records(self.jobs.values(), index="JobPostingId")
        dfJobs["JobPostingId"] = dfJobs.index
        dfJobs.sort_values('JobPostingId', ascending=True)

        print("Marking jobs as duplicate...")
        dfJobs["is_duplicate"] = dfJobs.duplicated({"Company", "TitleTokensString", "GeoLocationId"}, keep="first")
        dfJobs["is_duplicate_stringver"] = dfJobs.duplicated("CompanyTitleGeoLocation", keep="first")
        dictOrigPosts = dfJobs[(dfJobs["is_duplicate"] == False)].to_dict(orient="index")
        dictDupePosts = dfJobs[(dfJobs["is_duplicate"] == True)].to_dict(orient="index")
        dictOrigByCompTitle = {v["CompanyTitleGeoLocation"]: v["JobPostingId"] for (n, v) in dictOrigPosts.items() if
                               ("CompanyTitleGeoLocation") in v.keys()}

        print("Preparing duplicate job post results for export...")
        retDupesByJobId = {}
        for jobid in dictDupePosts:
            item = dictDupePosts[jobid]
            strCompTitle = item["CompanyTitleGeoLocation"]
            retDupesByJobId[jobid] = {
                "JobPostingId": jobid,
                "CompanyTitleGeoLocation": strCompTitle,
                "isDuplicateOf": dictOrigByCompTitle[strCompTitle]
                # ,
                # "DuplicateJob" : item,
                # "SourceJob" : dictOrigPosts[dictOrigByCompTitle[strCompTitle]]
            }

        print("{} / {} job postings have been marked as duplicate".format(len(retDupesByJobId), len(self.jobs)))
        self.output_data = {"duplicate_job_postings": retDupesByJobId}

    def export_results(self):
        print("Exporting final match results to {}".format(self.outputfile))
        helpers.write_json(self.outputfile, self.output_data)
        print("Job post duplicates exported to {}".format(self.outputfile))

        return self.outputfile

    def prepare_data(self, jobsdata):
        print("Tokenizing {} job titles....".format(len(jobsdata)))
        try:
            jobsdata = tokenizeStrings(jobsdata, u'Title', u'TitleTokens', u'set')
        except Exception, ex:
            print("Error tokenizing strings:  {}".format(ex))
            raise ex

        print("Reorganizing source data for duplicate matching...")
        for rowkey in jobsdata.keys():
            item = jobsdata[rowkey]
            subitem = {}
            if item:
                for k, v in item.items():
                    if k in JSON_DEDUPE_FIELDS:
                        subitem[k] = v
                subitem["TitleTokensString"] = "~".join(item["TitleTokens"])
                if "GeoLocationId" in subitem and subitem["GeoLocationId"]:
                    loc = subitem["GeoLocationId"]
                if "LocationDisplayValue" in subitem and subitem["LocationDisplayValue"]:
                    loc = subitem["LocationDisplayValue"]
                else:
                    loc = "NoLocation"
                subitem["CompanyTitleGeoLocation"] = u"{}_{}_{}".format(subitem["Company"],
                                                                        subitem["TitleTokensString"], loc)
                self.jobs[rowkey] = subitem
        jobsdata = None

        print("{} job postings loaded.".format(len(self.jobs)))


class DedupeJobPostingFromDB(TaskDedupeJobPostings):
    dbparams = {}

    def load_data(self, **kwargs):

        from urlparse import urlparse

        if 'connecturi' in kwargs:
            parsed = urlparse(kwargs['connecturi'])

            if parsed.hostname:
                self.dbparams['host'] = parsed.hostname
                self.dbparams['user'] = parsed.username
                self.dbparams['password'] = parsed.password
                self.dbparams['port'] = parsed.port
                self.dbparams['database'] = parsed.path.replace("/", "")
            else:
                params = kwargs['connecturi'].split(":")

                args = {x.split('=')[0]: x.split('=')[1] for x in params[1].split(';') if len(x) > 1}
                for a in args:
                    self.dbparams[a] = args[a]

        self.dbparams['cursorclass'] = pymysql.cursors.DictCursor
        if 'use_unicode' not in self.dbparams:
            self.dbparams['use_unicode'] = True
        self.dbparams['charset'] = "utf8mb4"
        if 'dbname' in self.dbparams:
            self.dbparams['database'] = self.dbparams.pop('dbname')
        if 'port' in self.dbparams:
            self.dbparams['port'] = int(self.dbparams.pop('port'))

        print("Processing job postings for duplicates from database {}".format(self.dbparams))

        print("Loading job list to match...")
        self.load_from_database()

    def load_from_database(self):
        backwardsdate = datetime.now() - timedelta(days=14)

        print("Connecting to database...")

        connection = pymysql.connect(**self.dbparams)

        try:
            print("Getting recent job postings for deduplication...")

            with connection.cursor() as cursor:
                querysql = " \
                    SELECT jobposting_id as `JobPostingId`, key_company_and_title as `KeyCompanyTitle`, title as `Title`, company as `Company`, geolocation_id as `GeoLocationId`, duplicates_posting_id as `isDuplicateOf` \
                    FROM jobposting \
                    WHERE job_posted_date >= '{}' ".format(backwardsdate.strftime("%Y-%m-%d"))
                # AND key_company_and_title in ( \
                #         SELECT jp2.key_company_and_title \
                #     FROM jobposting jp2 \
                #     GROUP BY jp2.key_company_and_title \
                #     HAVING Count(key_company_and_title) > 1) \
                # ORDER BY key_company_and_title"
                cursor.execute(querysql)
                result = cursor.fetchall()

                jobsdata = {val['JobPostingId']: val for val in result}

                self.prepare_data(jobsdata)

        except Exception, ex:
            print ex
            raise ex

        finally:
            connection.commit()
            connection.close()
        # query = (Jobposting
        #          .select(Jobposting.jobposting_id, Jobposting.key_company_and_title, Jobposting.title, Jobposting.company, Jobposting.duplicates_posting_id, Jobposting.job_posted_date)
        #          .where(Jobposting.job_posted_date >= backwardsdate)
        #          .group_by(Jobposting.key_company_and_title).having(fn.Count(Jobposting.key_company_and_title) >= 2)
        #          .order_by(Jobposting.key_company_and_title, Jobposting.job_posted_date))
        #
        # for row in query:
        #     print row

    from peewee import SelectQuery

    def update_database(self):
        connection = None
        print("Updating {} duplicate job posts in the database...".format(
            len(self.output_data["duplicate_job_postings"])))

        try:
            connection = pymysql.connect(**self.dbparams)
            # Cursor object creation

            for rec in self.output_data['duplicate_job_postings'].values():
                cursorObject = connection.cursor()

                updateStatement = """
                UPDATE jobposting 
                SET duplicates_posting_id={} 
                WHERE jobposting_id={}""".format(rec['isDuplicateOf'], rec['JobPostingId'])

                # Execute the SQL UPDATE statement

                cursorObject.execute(updateStatement)

        except Exception as e:
            print("Exeception occured:{}".format(e))


        finally:
            connection.close()


class DedupeJobPostingFile(TaskDedupeJobPostings):
    inputfile = None

    def load_data(self, **kwargs):
        if ('inputfile' in kwargs):
            self.inputfile = kwargs['inputfile']
        else:
            raise Exception("No input file specified for processing.")

        print("Processing job postings for duplicates from input file {}".format(self.inputfile))

        print("Loading job list to match...")
        self.load_jobpostings()

    def load_jobpostings(self):

        jobsdata = {}
        inputdata = {}
        if str(self.inputfile).endswith(".csv"):
            print("Loading jobs from CSV file {}".format(self.inputfile))
            jobsdata = helpers.load_ucsv(self.inputfile, fieldnames=None, delimiter=",", quotechar="\"",
                                         keyfield=DATA_KEY_JOBPOSTINGS_KEYFIELD)
        elif str(self.inputfile).endswith(".json"):
            print("Loading jobs from JSON file {}".format(self.inputfile))
            inputdata = helpers.load_json(self.inputfile)
            if inputdata:
                if isinstance(inputdata, dict):
                    if (DATA_KEY_JOBPOSTINGS in inputdata and isinstance(inputdata[DATA_KEY_JOBPOSTINGS], dict) and len(
                            inputdata[DATA_KEY_JOBPOSTINGS]) > 0):
                        jobsdata = inputdata[DATA_KEY_JOBPOSTINGS]
        else:
            raise Exception("Unknown input data file format: {}".format(self.inputfile))

        inputdata = None
        print("Loaded {} total jobs to deduplicate.".format(len(jobsdata)))

        self.prepare_data(jobsdata)
        jobsdata = None


if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')

    # if not arguments["--input"] or not arguments["--output"]:
    #     print("Unable to deduplicate job postings.  Missing script arguments.")
    # else:
    #     matcher = TaskDedupeJobPostings(arguments["--input"].replace("'", ""), arguments["--output"].replace("'", ""))

    if "--input" in arguments and arguments["--input"] and "--output" in arguments and arguments["--output"]:
        matcher = DedupeJobPostingFile()
        matcher.outputfile = arguments["--output"].replace("'", "")
        matcher.load_data(inputfile=arguments["--input"].replace("'", ""))
        matcher.dedupe_jobs()
        matcher.export_results()

    elif "--connecturi" in arguments and arguments["--connecturi"]:
        matcher = DedupeJobPostingFromDB()
        matcher.load_data(connecturi=arguments['--connecturi'])
        matcher.dedupe_jobs()
        matcher.update_database()
    else:
        print("Unable to deduplicate job postings.  Missing script arguments.")
