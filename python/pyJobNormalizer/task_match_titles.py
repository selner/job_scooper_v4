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

from helpers import load_json, write_json, load_csv_data, clean_text_for_matching

from task_tokenize_jobtitles import Tokenizer

JSON_KEY_JOBMATCHES = 'job_matches'
JSON_KEY_POS_KEYWORDS = 'SearchKeywords'
JSON_KEY_NEG_KEYWORDS = 'negative_title_keywords'
JSON_KEY_NEG_REGEX = 'negative_title_regex'
FILEKEY_NEG_TITLE_REGEX = 'negative_title_keywords'
FILEKEY_NEG_TITLE_REGEX_FIELD = 'matches_regex'
JSON_KEY_USER = u'user'

from mixin_database import DatabaseMixin

class TaskMatchJobsToKeywords(DatabaseMixin):
    _inputfile = None
    _outputfile = None
    keywords = {}
    negative_keywords = {}
    negative_regex = {}

    jobs = {}
    user_id = None
    df_job_tokens = None
    _tokenizer = None
    _input_data = None

    @property
    def outputfile(self):
        return self._outputfile

    @outputfile.setter
    def outputfile(self, filepath):
        """
        Args:
            filepath:
        """
        self._outputfile = filepath

    @property
    def input_data(self):
        if not self._input_data:
            self._input_data = load_json(self._inputfile)

        return self._input_data

    @property
    def user(self):
        if 'user' in self._input_data:
            return self._input_data['user']

        return None

    @property
    def userfiles(self):
        if self.user and 'inputfiles' in self.user:
            return self.user['inputfiles']

        return None

    def load_user_neg_regex(self):
        if self.userfiles and FILEKEY_NEG_TITLE_REGEX in self.userfiles:
            for userfile in self.userfiles[FILEKEY_NEG_TITLE_REGEX].values():
                negwords = load_csv_data(userfile, [FILEKEY_NEG_TITLE_REGEX_FIELD])
                negdict = dict(zip(negwords, negwords))

                self.negative_regex.update(negdict)

            self.log(f'Loaded {len(self.negative_regex.keys())} negative title regex values for user.')

    @property
    def tokenizer(self):
        if not self._tokenizer:
            self._tokenizer = Tokenizer()
        return self._tokenizer

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)
        if 'input' in kwargs:
            self._inputfile = kwargs['input']
        else:
            raise Exception("No input file specified for processing.")

        if 'output' in kwargs:
            self._outputfile = kwargs['output']

        self.log("Loading job list to match...")
        self.load_jobs()

        self.mark_title_matches()

    def mark_title_matches(self):

        self.log("Loading user keywords for matching...")
        self.load_keywords()

        self.log("Matching job list titles vs. user search keywords ...")
        self.mark_positive_matches()

        self.log("Matching job list titles vs. user negative keyword matches...")
        self.mark_negative_matches()

        self.log("Matching completed.")

    def get_output_data(self):
        return {
            JSON_KEY_JOBMATCHES: self.jobs,
            JSON_KEY_POS_KEYWORDS: self.keywords,
            JSON_KEY_NEG_KEYWORDS: self.negative_keywords,
            JSON_KEY_NEG_REGEX: self.negative_regex
        }

    def export_results(self):
        self.log(f'Exporting final match results to {self.outputfile}')
        write_json(self.outputfile, self.get_output_data())

        return self.outputfile

    def load_jobs(self):

        if (
            self.input_data
            and isinstance(self.input_data, dict)
            and (
                JSON_KEY_JOBMATCHES in self.input_data
                and isinstance(self.input_data[JSON_KEY_JOBMATCHES], dict)
                and len(self.input_data[JSON_KEY_JOBMATCHES]) > 0
            )
        ):
            self.jobs = self.input_data[JSON_KEY_JOBMATCHES]
            self.log(f'Loaded {len(self.input_data[JSON_KEY_JOBMATCHES])} jobs for matching.')

            tokenizer = Tokenizer()
            self.jobs = tokenizer.batch_tokenize_strings(self.jobs, u'Title', u'TitleTokens', u'set', maxlength=200)

    @staticmethod
    def get_unique_keywd_set(keywords):

        """
        Args:
            keywords:
        """
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

        return {u'single_tokens': uniq_single_toks, u'multi_tokens': all_multi_tok_sets}

    def set_keyword_matches(self, data, key_source, key_result):
        """
        Args:
            data:
            key_source:
            key_result:
        """
        nmatched = 0
        nnotmatched = 0

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
            matched_singles = self.jobs[jobid][key_source].intersection(kwds_to_match['single_tokens'])
            if matched_singles:
                matched_toks.extend(matched_singles)
            # first, do a quick shortcut check to see if we matched even one of the tokens
            # in the multi token sets.  if we didn't, we can skip the loop.  Otherwise
            # we then have to find any set where all the tokens were found in the token list
            # we are matching against
            #
            matched_any_multis = self.jobs[jobid][key_source].intersection(all_possible_multi_toks)
            if matched_any_multis:
                for m in kwds_to_match['multi_tokens'].keys():
                    multitokset = kwds_to_match['multi_tokens'][m]
                    if self.jobs[jobid][u'TitleTokens'].issuperset(set(multitokset)):
                        matched_toks.append("|".join(multitokset))

            if matched_toks:
                self.jobs[jobid][key_result] = matched_toks
                nmatched += 1
            else:
                if key_result in self.jobs[jobid]:
                    del self.jobs[jobid][key_result]

                nnotmatched += 1
        self.log(f'{key_source} match results:  {nmatched}/{len(self.jobs)} matched, {nnotmatched}/{len(self.jobs)} not matched')

    def mark_positive_matches(self):
        self.log(f'Marking jobs that match {len(self.keywords)} positive title keywords...')

        self.set_keyword_matches(self.keywords, "TitleTokens", "GoodJobTitleKeywordMatches")

    def mark_negative_matches(self):
        self.log(f'Marking jobs that match {len(self.negative_keywords)} negative title keywords...')
        self.set_keyword_matches(self.negative_keywords, "TitleTokens", "BadJobTitleKeywordMatches")
        if self.negative_regex:
            self.log(f'Marking jobs that match {len(self.negative_regex)} negative title regex...')
            matchstring = None
            for match in self.negative_regex:
                if matchstring:
                    matchstring = f'({match})|' + matchstring
                else:
                    matchstring = f'({match})'
            nmatched = 0
            import re
            renegkwd = re.compile(matchstring, re.IGNORECASE)

            for jobid in self.jobs:
                if 'Title' in self.jobs[jobid] and self.jobs[jobid]['Title']:
                    titleval = clean_text_for_matching(self.jobs[jobid]['Title']).lower()

                    negmatches = renegkwd.findall(titleval)
                    if negmatches and len(negmatches) > 0:
                        badmatches = [grp for grp in negmatches[0] if grp]
                        if badmatches:
                            self.jobs[jobid]['BadJobTitleRegexMatches'] = badmatches
                        nmatched += 1

            self.log(f'Found {nmatched} jobs that matched negative title regex strings.')

    def load_keywords(self):
        outdata = None

        self.load_user_neg_regex()

        if JSON_KEY_USER in self.input_data and 'UserId' in self.input_data[JSON_KEY_USER]:
            self.user_id = self.input_data[JSON_KEY_USER]['UserId']

            if JSON_KEY_POS_KEYWORDS in self.input_data[JSON_KEY_USER]:
                for keyword in self.input_data[JSON_KEY_USER][JSON_KEY_POS_KEYWORDS]:
                    self.keywords[keyword] = {
                        'key': keyword,
                        'user_keyword_set_key': keyword,
                        'keyword': keyword,
                        'tokens': None
                    }
                    outdata = self.tokenizer.batch_tokenize_strings(self.keywords, 'keyword', 'tokens')

                self.keywords = outdata

        if JSON_KEY_NEG_KEYWORDS in self.input_data and len(self.input_data[JSON_KEY_NEG_KEYWORDS]) > 0:
            for kwdkey in self.input_data[JSON_KEY_NEG_KEYWORDS]:
                self.negative_keywords[kwdkey] = {
                    'key': kwdkey,
                    'keyword': kwdkey,
                    'tokens': None
                }
            data = self.tokenizer.batch_tokenize_strings(self.negative_keywords, 'keyword', 'tokens')
            self.negative_keywords = data

        self.log(f'Loaded {len(self.keywords)} positive keywords and {len(self.negative_keywords)} negative keywords for matching.')

# class TaskMatchJobTitlesFromDB(TaskMatchJobsToKeywords, DatabaseMixin)
#
#     def __init__(self, **kwargs):
#         self.init_connection(**kwargs)
#         TaskMatchJobsToKeywords.__init__(**kwargs)
#
#         if '_inputfile' in kwargs:
#             self._inputfile = kwargs['inputfile']
#         else:
#             raise Exception("No input file specified for processing.")
#
#         print("Loading job list to match...")
#         self.load_jobs()
#
#         self.mark_title_matches()
#
#     def load_jobs(self):
#         backwardsdate = datetime.now() - timedelta(days=3)
#
#         querysql = """
#                 SELECT
#                     user_job_match.user_job_match_id,
#                     user_job_match.jobposting_id,
#                     user_job_match.is_job_match,
#                     user_job_match.is_excluded,
#                     user_job_match.out_of_user_area,
#                     user_job_match.matched_user_keywords,
#                     user_job_match.matched_negative_title_keywords,
#                     user_job_match.matched_negative_company_keywords,
#                     user_job_match.user_notification_state,
#                     user_job_match.first_matched_at,
#                     jobposting.jobposting_id,
#                     jobposting.title,
#                     jobposting.location,
#                     jobposting.last_updated_at,
#                     jobposting.job_posted_date,
#                     jobposting.first_seen_at,
#                     jobposting.location_display_value,
#                     jobposting.geolocation_id,
#                     jobposting.duplicates_posting_id,
#                 FROM user_job_match
#                 INNER JOIN jobposting ON (user_job_match.jobposting_id=jobposting.jobposting_id)
#                 WHERE user_job_match.user_notification_state={}
#                     AND jobposting.duplicates_posting_id is NULL
#                     AND user_job_match.first_matched_at >= {}
#                 AND user_job_match.user_id={}
#
#                   SELECT
#                      jobposting_id as `JobPostingId`,
#                      key_company_and_title as `KeyCompanyTitle`,
#                      title as `Title`,
#                      company as `Company`,
#                      geolocation_id as `GeoLocationId`,
#                      duplicates_posting_id as `isDuplicateOf`
#                  FROM jobposting
#                  WHERE job_posted_date >= '{}' """.format(backwardsdate.strftime("%Y-%m-%d"))
#
#         result = self.fetch_all_from_query(querysql)
#         jobsdata = {val['JobPostingId']: val for val in result}
#
#         self.prepare_data(jobsdata)
#
#     def update_database(self):
