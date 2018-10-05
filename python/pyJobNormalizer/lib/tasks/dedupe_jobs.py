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
from datetime import *
from ..helpers import write_json, load_ucsv, load_json
from tokenize import Tokenizer
from ..database import DatabaseMixin
from collections import OrderedDict
from cleanco import cleanco

DATA_KEY_JOBPOSTINGS = u'job_postings'
DATA_KEY_JOBPOSTINGS_KEYFIELD = u'JobPostingId'
DATA_KEY_USER = u'user'
DATA_KEY_OUTPUT_DUPLICATE_IDS = u'user'
JSON_DEDUPE_FIELDS = ["JobPostingId", "Title", "Company", "JobSite", "GeoLocationId", "KeyCompanyAndTitle",
                      "FirstSeenAt", "DuplicatesJobPostingId", "WasDuplicate"]


class BaseTaskDedupeJobPostings:

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
                               "CompanyTitleGeoLocation" in v.keys()}

        print("Preparing duplicate job post results for export...")
        retDupesByJobId = {}
        for jobid in dictDupePosts:
            item = dictDupePosts[jobid]
            strCompTitle = item["CompanyTitleGeoLocation"]
            retDupesByJobId[jobid] = {
                "JobPostingId": jobid,
                "CompanyTitleGeoLocation": strCompTitle,
                "isDuplicateOf": dictOrigByCompTitle[strCompTitle],
                "WasDuplicate": item["WasDuplicate"]
            }

        print("{} / {} job postings have been marked as duplicate".format(len(retDupesByJobId), len(self.jobs)))
        self.output_data = {"duplicate_job_postings": retDupesByJobId}

    def export_results(self):
        print("Exporting final match results to {}".format(self.outputfile))
        write_json(self.outputfile, self.output_data)
        print("Job post duplicates exported to {}".format(self.outputfile))

        return self.outputfile

    def prepare_data(self, jobsdata):
        print("Tokenizing {} job titles....".format(len(jobsdata)))
        try:
            tokenizer = Tokenizer()
            jobsdata = tokenizer.tokenizeStrings(jobsdata, u'Title', u'TitleTokens', u'set')
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
                subitem["TitleTokensString"] = "~".join(sorted(item["TitleTokens"]))

                #
                # Clean up things like LTD from the company name
                # so we can match on cleaner company names
                #
                subitem["CompanyCleaned"] = "NO COMPANY LISTED"
                if item["Company"]:
                    ccleaner = cleanco(item["Company"])
                    subitem["CompanyCleaned"] = ccleaner.clean_name()

                subitem["WasDuplicate"] = (item['DuplicatesJobPostingId'] is not None)

                if "GeoLocationId" in subitem and subitem["GeoLocationId"]:
                    loc = subitem["GeoLocationId"]
                if "LocationDisplayValue" in subitem and subitem["LocationDisplayValue"]:
                    loc = subitem["LocationDisplayValue"]
                else:
                    loc = "NOLOCATION"
                subitem["CompanyTitleGeoLocation"] = u"{}_{}_{}".format(subitem["TitleTokensString"], subitem["CompanyCleaned"], loc)
                self.jobs[rowkey] = subitem
        jobsdata = None

        print("{} job postings loaded.".format(len(self.jobs)))


class DedupeJobPostingFromDB(BaseTaskDedupeJobPostings, DatabaseMixin):
    dbparams = {}

    def load_data(self, **kwargs):

        self.init_connection(**kwargs)

        print(u"Processing job postings for duplicates from database {}".format(self.dbparams))

        print(u"Loading job list to match...")
        self.load_from_database()

    def load_from_database(self):
        backwardsdate = datetime.now() - timedelta(days=14)

        print(u"Connecting to database...")

        querysql = u"""
            SELECT 
                jobposting_id as `JobPostingId`, 
                key_company_and_title as `KeyCompanyTitle`, 
                title as `Title`, 
                company as `Company`, 
                geolocation_id as `GeoLocationId`, 
                duplicates_posting_id as `DuplicatesJobPostingId`
            FROM jobposting
            WHERE job_posted_date >= '{}'
            ORDER BY title, company 
            """.format(backwardsdate.strftime("%Y-%m-%d"))

        result = self.fetch_all_from_query(querysql)
        jobsdata = OrderedDict()

        for val in result:
            jobsdata[val['JobPostingId']] = val

        self.prepare_data(jobsdata)

    def update_database(self):
        print(u"Updating {} duplicate job posts in the database...".format(
            len(self.output_data["duplicate_job_postings"])))
        nskipped = 0
        nupdated = 0

        try:

            for rec in self.output_data['duplicate_job_postings'].values():
                #
                # don't waste time updating records that were already
                # marked as duplicates
                #
                if not rec['WasDuplicate']:
                    updateStatement = u"""
                        UPDATE jobposting 
                        SET duplicates_posting_id={} 
                        WHERE jobposting_id={}""".format(rec['isDuplicateOf'], rec['JobPostingId'])

                    self.run_command(updateStatement, close_connection=False)
                    nupdated += 1
                else:
                    nskipped += 1

        except Exception as e:
            print(u"Exception occurred:{}".format(e))
            raise e

        finally:
            self.close_connection()
            print(u"Updated {} new duplicates in the database.  Skipped {} previously marked as duplicate.".format(nupdated, nskipped))


class DedupeJobPostingFile(BaseTaskDedupeJobPostings):
    inputfile = None

    def load_data(self, **kwargs):
        if 'inputfile' in kwargs:
            self.inputfile = kwargs['inputfile']
        else:
            raise Exception(u"No input file specified for processing.")

        print(u"Processing job postings for duplicates from input file {}".format(self.inputfile))

        print(u"Loading job list to match...")
        self.load_jobpostings()

    def load_jobpostings(self):

        jobsdata = {}
        if str(self.inputfile).endswith(".csv"):
            print(u"Loading jobs from CSV file {}".format(self.inputfile))
            jobsdata = load_ucsv(self.inputfile, fieldnames=None, delimiter=",", quotechar="\"",
                                                             keyfield=DATA_KEY_JOBPOSTINGS_KEYFIELD)
        elif str(self.inputfile).endswith(".json"):
            print(u"Loading jobs from JSON file {}".format(self.inputfile))
            inputdata = load_json(self.inputfile)
            if inputdata:
                if isinstance(inputdata, dict):
                    if (DATA_KEY_JOBPOSTINGS in inputdata and isinstance(inputdata[DATA_KEY_JOBPOSTINGS], dict) and len(
                            inputdata[DATA_KEY_JOBPOSTINGS]) > 0):
                        jobsdata = inputdata[DATA_KEY_JOBPOSTINGS]
        else:
            raise Exception(u"Unknown input data file format: {}".format(self.inputfile))

        inputdata = None
        print(u"Loaded {} total jobs to deduplicate.".format(len(jobsdata)))

        self.prepare_data(jobsdata)
        jobsdata = None

