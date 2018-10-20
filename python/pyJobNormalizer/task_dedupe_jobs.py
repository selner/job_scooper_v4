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
from helpers import write_json, load_ucsv, load_json
from util_tokenize import Tokenizer
from mixin_database import DatabaseMixin
from collections import OrderedDict
from cleanco import cleanco

DATA_KEY_JOBPOSTINGS = u'job_postings'
DATA_KEY_JOBPOSTINGS_KEYFIELD = u'JobPostingId'
DATA_KEY_USER = u'user'
DATA_KEY_OUTPUT_DUPLICATE_IDS = u'user'
JSON_DEDUPE_FIELDS = ["JobPostingId", "Title", "Company", "JobSite", "LocationDisplayValue", "KeyCompanyAndTitle",
                      "FirstSeenAt", "DuplicatesJobPostingId", "WasDuplicate"]

from task_tokenize_jobtitles import TaskAddTitleTokens


class BaseTaskDedupeJobPostings:
    connecturi = None
    keywords = {}
    negative_keywords = {}
    jobs = {}
    user_id = None
    df_job_tokens = None
    output_data = None
    _outputfile = None

    @property
    def outputfile(self):
        return self._outputfile

    def __init__(self, outputfile=None):
        """
        Args:
            outputfile:
        """
        self._outputfile = outputfile

    def dedupe_jobs(self):


        ##
        #  TODO:  Consider doing this via a DB query instead.   Read more about query structure
        # at https://stackoverflow.com/questions/688549/finding-duplicate-values-in-mysql?rq=1
        ##

        ##
        #  TODO:  Use lat/long box or approximation to group posts in similar area but not same
        # specific location.   E.g.  "Picadilly" vs. "Greater London" (same job really since
        # Picadilly is within Greater London.
        #
        ##

        dfjobs = pandas.DataFrame.from_dict(self.jobs, orient="index")
        dfjobs["JobPostingId"] = dfjobs.index
        dfjobs.sort_values('JobPostingId', ascending=True)

        print("Marking jobs as duplicate...")
        dfjobs["is_duplicate"] = dfjobs.duplicated({"CompanyCleaned", "TitleTokensString", "LocationDisplayValue"}, keep="first")
        dfjobs["is_duplicate_stringver"] = dfjobs.duplicated("CompanyTitleLocation", keep="first")
        dict_orig_posts = dfjobs[(dfjobs["is_duplicate"] == False)].to_dict(orient="index")
        dict_dupe_posts = dfjobs[(dfjobs["is_duplicate"] == True)].to_dict(orient="index")
        dict_orig_by_comptitle = {v["CompanyTitleLocation"]: v["JobPostingId"] for (n, v) in dict_orig_posts.items() if
                               "CompanyTitleLocation" in v.keys()}


        # self.df_to_csv("/tmp/dfjobs_export.csv", dfjobs)

        print("Preparing duplicate job post results for export...")
        ret_dupes_by_jobid = {}
        for jobid in dict_dupe_posts:
            item = dict_dupe_posts[jobid]
            str_comptitle = item["CompanyTitleLocation"]
            ret_dupes_by_jobid[jobid] = {
                "JobPostingId": jobid,
                "CompanyTitleLocation": str_comptitle,
                "isDuplicateOf": dict_orig_by_comptitle[str_comptitle],
                "WasDuplicate": item["WasDuplicate"]
            }

        print("{} / {} job postings have been marked as duplicate".format(len(ret_dupes_by_jobid), len(self.jobs)))
        self.output_data = {"duplicate_job_postings": ret_dupes_by_jobid}

    # def df_to_csv(self, csvpath, dfdata):
    #     dict_jobs = dfdata.to_dict(orient="index")
    #     loutput = list()
    #
    #     import os, sys
    #     loutput.append(",".join(dfdata.columns) + os.linesep)
    #     for row in dict_jobs.values():
    #         rowfacts = [str(k).replace(os.linesep, "") for k in row.values()]
    #         loutput.append("\"" + "\",\"".join(rowfacts) + "\"" + os.linesep)
    #
    #     with open(csvpath, "w") as fp:
    #         fp.writelines(loutput)
    #     print("Data written to {}".format(csvpath))

    def export_results(self):
        print("Exporting final match results to {}".format(self.outputfile))
        write_json(self.outputfile, self.output_data)
        print("Job post duplicates exported to {}".format(self.outputfile))

        return self.outputfile

    def prepare_data(self, jobsdata):
        """
        Args:
            jobsdata:
        """
        print("Tokenizing {} job titles....".format(len(jobsdata)))
        try:
            tokenizer = Tokenizer()
            jobsdata = tokenizer.batch_tokenize_strings(jobsdata, u'Title', u'TitleTokens', u'set')
        except Exception as ex:
            print("Error tokenizing strings:  {}".format(ex))
            raise(ex)

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
                subitem["CompanyCleaned"] = "UNKNOWN"
                if 'JobSite' in item and item['JobSite']:
                    subitem["CompanyCleaned"] = "UNKNOWN_" + item['JobSite']

                if "Company" in item and item["Company"]:
                    ccleaner = cleanco(item["Company"])
                    subitem["CompanyCleaned"] = ccleaner.clean_name()

                subitem["WasDuplicate"] = (item['DuplicatesJobPostingId'] is not None)

                if "LocationDisplayValue" in subitem and subitem["LocationDisplayValue"]:
                    loc = subitem["LocationDisplayValue"]
                else:
                    loc = "NOLOCATION"

                subitem["CompanyTitleLocation"] = "_".join([
                    subitem["TitleTokensString"],
                    subitem["CompanyCleaned"],
                    str(loc)
                ])

                self.jobs[rowkey] = subitem
        jobsdata = None

        print("{} job postings loaded.".format(len(self.jobs)))


class TaskDedupeJobPostingFromDB(BaseTaskDedupeJobPostings, DatabaseMixin):
    dbparams = {}

    def load_data(self, **kwargs):

        """
        Args:
            **kwargs:
        """
        self.init_connection(**kwargs)

        print(u"Verifying title tokens are set for all jobpostings in database {}".format(self.dbparams))
        # first we need to make sure all the jobpostings have title tokens
        # and reference keys set already
        #
        toks = TaskAddTitleTokens(**kwargs)
        toks.update_jobs_without_tokens()

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
                jobsite_key as `JobSite`, 
                company as `Company`, 
                location_display_value as `LocationDisplayValue`,
                duplicates_posting_id as `DuplicatesJobPostingId`
            FROM jobposting
            WHERE job_posted_date >= '{}'
            ORDER BY title, company, location_display_value, jobsite_key
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
                    statement = u"""
                        UPDATE jobposting 
                        SET duplicates_posting_id={} 
                        WHERE jobposting_id={}""".format(rec['isDuplicateOf'], rec['JobPostingId'])

                    self.run_command(statement, close_connection=False)
                    nupdated += 1
                else:
                    nskipped += 1

            print(u"Updated {} new duplicates in the database.  Skipped {} previously marked as duplicate.".format(
                nupdated, nskipped))
            self.update_jobmatch_exclusions()

        except Exception as e:
            print(u"Exception occurred:{}".format(e))
            raise e

        finally:
            self.close_connection()

    def update_jobmatch_exclusions(self):
        """
        Args:
        """
        print(u"Updating duplicate-related user_job_matches...")

        statement = u"""
            UPDATE user_job_match 
            SET 
                is_excluded = 1
            WHERE
                user_job_match_id > 0 AND 
                user_job_match.jobposting_id IN (SELECT 
                        jobposting.jobposting_id
                    FROM
                        jobposting
                    WHERE
                        duplicates_posting_id IS NOT NULL);
            """

        rows_updated = self.run_command(statement, close_connection=True)
        print(u"Updated {} user_job_matches marked excluded because they map to duplicate job postings.'".format(rows_updated))
        return rows_updated


class TaskDedupeJobPostingFile(BaseTaskDedupeJobPostings):
    inputfile = None

    def load_data(self, **kwargs):
        """
        Args:
            **kwargs:
        """
        if 'inputfile' in kwargs:
            self.inputfile = kwargs['inputfile']
        else:
            raise Exception(u"No input file specified for processing.")

        if 'outputfile' in kwargs:
            self._outputfile = kwargs['outputfile']
        else:
            raise Exception(u"No output file specified for processing.")

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
