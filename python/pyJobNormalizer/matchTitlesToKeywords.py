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


import helpers

cli_usage = """
Usage:
  matchTitlesToKeywords.py -i <file> -o <file>
  matchTitlesToKeywords.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -o <file>, --output <file> output file with job match results 
  -i <file>, --input <file> input JSON data file with jobs and keywords
"""
JSON_KEY_JOBMATCHES = u'job_matches'
JSON_KEY_POS_KEYWORDS = u'SearchKeywords'
JSON_KEY_NEG_KEYWORDS = u'negative_title_keywords'
JSON_KEY_USER = u'user'

from docopt import docopt
from processfile import tokenizeStrings


class MatchJobsToKeywordsTask:
    inputfile = None
    keywords = {}
    negative_keywords = {}
    jobs = {}
    user_id = None
    df_job_tokens = None

    def __init__(self, inputfile, outputfile):

        self.inputFile = inputfile
        self.outputfile = outputfile

        print("Processing job title matching with input file {}".format(self.inputFile))

        print("Loading user keywords for matching...")
        self.load_keywords()

        print("Loading job list to match...")
        self.loadJobs()

        print("Matching job list titles vs. user search keywords ...")
        self.mark_positive_matches()

        print("Matching job list titles vs. user negative keyword matches...")
        self.mark_negative_matches()

        self.exportResultsData()
        print("Matching completed.")

    def get_output_data(self):
        return {
            JSON_KEY_JOBMATCHES: self.jobs,
            JSON_KEY_POS_KEYWORDS: self.keywords,
            JSON_KEY_NEG_KEYWORDS: self.negative_keywords
        }

    def exportResultsData(self):
        print("Exporting final match results to {}".format(self.outputfile))
        helpers.write_json(self.outputfile, self.get_output_data())

        return self.outputfile

    def loadJobs(self):
        inputData = helpers.load_json(self.inputFile)
        if inputData:
            if isinstance(inputData, dict):
                if (JSON_KEY_JOBMATCHES in inputData and isinstance(inputData[JSON_KEY_JOBMATCHES], dict) and len(
                        inputData[JSON_KEY_JOBMATCHES]) > 0):
                    self.jobs = inputData[JSON_KEY_JOBMATCHES]
                    self.jobs = tokenizeStrings(self.jobs, u'Title', u'TitleTokens', u'set')

    def get_unique_keywd_set(self, keywords):

        all_keyword_sets = [x['tokens'].replace("||", "|")[1:-1].split("|") for x in keywords.values()]

        #
        # build a unique set of all single keyword sets items
        #
        all_single_tok = ["".join(s) for s in all_keyword_sets if len(s) == 1]
        uniq_single_toks = list(sorted(set(all_single_tok)))

        #
        # build a dictionary of all multiple keyword sets with a key value
        # like "vice|president" and a value like ["vice", "presiden"]
        #
        all_multi_tok_sets = {"|".join(s): list(set(s)) for s in all_keyword_sets if len(s) > 1}

        return { u'single_tokens' : uniq_single_toks, u'multi_tokens' : all_multi_tok_sets }

    def set_keyword_matches(self, data, keySource, keyResult):
        if not data or not data.values():
            return
        kwds_to_match = self.get_unique_keywd_set(data)

        all_possible_multi_toks = "|".join(kwds_to_match['multi_tokens'].keys()).split("|")
        for jobid in self.jobs:
            matched_toks = []

            # check if we match any of the single term keywords.  We split
            # them by single/multi because we can do the single check in one call
            # but have to iterate for the
            #
            matched_singles = self.jobs[jobid][keySource].intersection(kwds_to_match['single_tokens'])
            if matched_singles:
                matched_toks.extend(matched_singles)
            # first, do a quick shortcut check to see if we matched even one of the tokens
            # in the multi token sets.  if we didn't, we can skip the loop.  Otherwise
            # we then have to find any set where all the tokens were found in the token list
            # we are matching against
            #
            matched_any_multis = self.jobs[jobid][keySource].intersection(all_possible_multi_toks)
            if matched_any_multis:
                for m in kwds_to_match['multi_tokens'].keys():
                    multitokset = kwds_to_match['multi_tokens'][m]
                    if self.jobs[jobid][u'TitleTokens'].issuperset(set(multitokset)):
                        matched_toks.append("|".join(multitokset))

            if matched_toks:
                self.jobs[jobid][keyResult] = matched_toks
            else:
                self.jobs[jobid][keyResult] = None

    def mark_positive_matches(self):
        print( "Marking jobs that match {} positive title keywords...".format(len(self.keywords)))

        self.set_keyword_matches(self.keywords, "TitleTokens", "MatchedUserKeywords")

    def mark_negative_matches(self):
        print( "Marking jobs that match {} negative title keywords...".format(len(self.negative_keywords)))
        self.set_keyword_matches(self.negative_keywords, "TitleTokens", "MatchedNegativeTitleKeywords")

    def load_keywords(self):
        data = helpers.load_json(self.inputFile)
        if JSON_KEY_USER in data and 'UserId' in data[JSON_KEY_USER]:
            self.user_id = data[JSON_KEY_USER]['UserId']

            if JSON_KEY_POS_KEYWORDS in data[JSON_KEY_USER]:
                for keyword in data[JSON_KEY_USER][JSON_KEY_POS_KEYWORDS]:
                    self.keywords[keyword] = {
                        'key': keyword,
                        'user_keyword_set_key': keyword,
                        'keyword': keyword,
                        'tokens': None
                    }
                outData = tokenizeStrings(self.keywords, 'keyword', 'tokens')

                self.keywords = outData

        if JSON_KEY_NEG_KEYWORDS in data:
            for kwdkey in data[JSON_KEY_NEG_KEYWORDS]:
                self.negative_keywords[kwdkey] = {
                    'key': kwdkey,
                    'keyword': kwdkey,
                    'tokens': None
                }
            outData = tokenizeStrings(self.negative_keywords, 'keyword', 'tokens')
            self.negative_keywords = outData

        print("Loaded {} positive keywords and {} negative keywords for matching.".format(len(self.keywords), len(self.negative_keywords)))


if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')

    matcher = MatchJobsToKeywordsTask(arguments["--input"].replace("'", ""), arguments["--output"].replace("'", ""))
