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

from api.utils.files import load_csv_data, clean_text_for_matching
from api.utils.tokenize import Tokenizer
from api.utils.dbmixin import DatabaseMixin
from collections import OrderedDict

JSON_KEY_JOBMATCHES = 'job_matches'
JSON_KEY_POS_KEYWORDS = 'SearchKeywords'
JSON_KEY_NEG_KEYWORDS = 'negative_title_keywords'
JSON_KEY_NEG_REGEX = 'negative_title_regex'
FILEKEY_NEG_TITLE_REGEX = 'negative_title_regex'
FILEKEY_NEG_TITLE_REGEX_FIELD = 'matches_regex'
FILEKEY_NEG_TITLE_KEYWORD = 'negative_title_keywords'
FILEKEY_NEG_TITLE_KEYWORD_FIELD = 'match_keywords'
JSON_KEY_USER = u'user'


class TaskMatchJobsToKeywords(DatabaseMixin):
    _tokenizer = None
    _input_data = None
    _re_negregex = None
    negative_regex = OrderedDict()
    _user = None
    _user_keywords = None

    @property
    def user(self):
        if self._user:
            return self._user

        return None

    @property
    def user_id(self):
        if self._user_id:
            return self._user_id

        return None

    @user_id.setter
    def user_id(self, val):
        if val:
            self._user_id = val
            querysql = """
                SELECT 
                    *
                FROM
                    user
                WHERE
                    user.user_id = {}
            """.format(self._user_id)

            self._user = self.fetch_one_from_query(querysql)
            if 'input_files_json' in self._user and self._user['input_files_json']:
                import json
                self._user['inputfiles'] = json.loads(self._user['input_files_json'])

    @property
    def user_keywords(self):
        if not self._user_keywords:
            if not self.user:
                raise Exception("No user information loaded")

            usertokens = self.tokenizer.get_tokens_from_string(self.user['search_keywords'])
            self._user_keywords = set([t for t in usertokens if len(t) > 3])
        return self._user_keywords

    @property
    def userfiles(self):
        if self.user and 'inputfiles' in self.user:
            return self.user['inputfiles']

        return None

    @property
    def tokenizer(self):
        if not self._tokenizer:
            self._tokenizer = Tokenizer()
        return self._tokenizer

    @property
    def re_negativeregex(self):

        if not self._re_negregex:
            matchstring = None
            import re
            matchstring = r"|".join([rf'({match})' for match in list(self.negative_regex.values())])
            self._re_negregex = re.compile(matchstring, re.IGNORECASE)

        return self._re_negregex

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)

        if 'user_id' in kwargs and kwargs['user_id']:
            self.user_id = kwargs['user_id']
            self.load_user_neg_keywords()
            self.load_user_neg_regex()
        else:
            raise Exception("user_id is required.")

    def load_user_neg_regex(self):
        totalneg = 0
        if self.userfiles and FILEKEY_NEG_TITLE_REGEX in self.userfiles:
            for userfile in self.userfiles[FILEKEY_NEG_TITLE_REGEX].values():
                negwords = load_csv_data(userfile, [FILEKEY_NEG_TITLE_REGEX_FIELD])
                sort = sorted(negwords)
                self.negative_regex.update(OrderedDict(zip(sort, sort)))
                totalneg += len(negwords)

        # Sort the neg regex for ease in debugging / lookup
        lowered = {str(k).lower(): k for k in self.negative_regex}
        sorted(lowered)
        self.negative_regex = OrderedDict({k: lowered[k] for k in lowered})

        if totalneg:
            self.log(f'Loaded {totalneg} negative title regex values for user {self.user["user_id"]}.')
        else:
            self.log(f'No negative title regex values were found for user {self.user["user_id"]}.')

    def load_user_neg_keywords(self):
        ntotalkwds = 0
        if self.userfiles and JSON_KEY_NEG_KEYWORDS in self.userfiles:
            for userfile in self.userfiles[FILEKEY_NEG_TITLE_KEYWORD].values():
                negwords = load_csv_data(userfile, [FILEKEY_NEG_TITLE_KEYWORD_FIELD])
                if negwords:
                    distwords = sorted(set(negwords))

                    for val in distwords:
                        valtokens = self.tokenizer.tokenize_string(val)
                        if isinstance(valtokens, str):
                            valtokens = [valtokens]
                        if len(valtokens) >= 1:
                            import re
                            reval = ""
                            for tok in valtokens:
                                if reval:
                                    reval += rf'\s+'
                                reval += rf'{tok}\w*\b'
                            self.negative_regex[val] = reval
                            ntotalkwds += 1
        self.log(f'Loaded {ntotalkwds} negative title keyword values for user.')

    def process_user_token_matches(self):

        self.log(f'Processing job title token matches against user {self.user["user_slug"]}\'s search terms & negative keywords...')
        gooduserkeywords = self.user_keywords

        querysql = """
            SELECT DISTINCT
                # user_job_match.user_job_match_id,
                # user_job_match.jobposting_id,
                # user_job_match.is_job_match,
                # user_job_match.is_excluded,
                # user_job_match.out_of_user_area,
                # user_job_match.matched_user_keywords,
                # user_job_match.matched_negative_title_keywords,
                # user_job_match.matched_negative_company_keywords,
                # user_job_match.user_notification_state,
                # user_job_match.first_matched_at,
                # jobposting.jobposting_id,
                jobposting.title,
                jobposting.title_tokens
            FROM user_job_match
            INNER JOIN jobposting ON (user_job_match.jobposting_id=jobposting.jobposting_id) """

        querysql += self._get_titlematch_where_clause(self.user['user_id'], gooduserkeywords)

        try:
            matched_jobs = self.fetch_all_from_query(querysql)

            # arrtitles = {k: dict(title=matched_jobs[k]['title'], title_tokens=matched_jobs[k]['title_tokens']) for k in
            #              matched_jobs}
            #
            for jobmatch in matched_jobs:
                dbupdates = {}
                jobtokens = self.convert_column_value_to_array(jobmatch['title_tokens'])
                good_matches = set(jobtokens).intersection(gooduserkeywords)
                if good_matches:
                    dbupdates['good_job_title_keyword_matches'] = good_matches

                    if 'title' in jobmatch and jobmatch['title']:
                        titleval = clean_text_for_matching(jobmatch['title']).lower()

                        negmatches = self.re_negativeregex.findall(titleval)
                        if negmatches and len(negmatches) > 0:
                            badmatches = {negmatches[0].index(grp): grp for grp in negmatches[0] if grp}
                            matchsrckeys = [list(self.negative_regex.keys())[k] for k in badmatches.keys()]
                            if badmatches:
                                dbupdates['bad_job_title_keyword_matches'] = badmatches.values()

                    if len(dbupdates.keys()) > 0:
                        self._update_userjob_kwdmatch_column(self.user['user_id'], jobtokens, dbupdates)

        except Exception as e:
            self.handle_error(e)

    def _get_titlematch_where_clause(self, user_id, tokens):
        retokens = ""
        if isinstance(tokens, dict):
            tokens = list(tokens.values())
        for kywd in tokens:
            kwdval = kywd.strip().lower()
            if not retokens:
                retokens += f'{kwdval}'
            else:
                retokens += f'|{kwdval}'

        wheresql = """
            WHERE user_job_match.user_notification_state in (0, 1)
                AND user_job_match.first_matched_at >= CURDATE() - 7 AND
                user_id = {} AND 
                user_job_match.jobposting_id IN (SELECT 
                            jobposting.jobposting_id
                        FROM
                            jobposting
                        WHERE
                            jobposting.duplicates_posting_id is NULL AND
                            jobposting.title_tokens REGEXP '{}');
                """.format(user_id, retokens)

        return wheresql

    def _update_userjob_kwdmatch_column(self, user_id, title_tokens, match_updates):
        """
        Args:
        """

        colupdates = []
        for colname in match_updates:
            dbval_matched = self.get_col_val_from_array(match_updates[colname], 'user_job_match', colname)
            colupdates.append("{} = '{}'".format(colname, dbval_matched))
        try:
            statement = """
                UPDATE user_job_match 
                SET 
                """ + ",\n".join(colupdates)

            statement += self._get_titlematch_where_clause(user_id, title_tokens)

            rows_updated = self.run_command(statement, close_connection=False)
            self.log(f'Updated {rows_updated} user_job_matches that had jobs with title tokes "{title_tokens}".')
            return {'rows_updated': rows_updated}

        except Exception as e:
            self.handle_error(e)


if __name__ == "__main__":
    kwargs = {
        'user': 'jobscooper',
        'database': 'jobscooper',
        'password': 'orange11',
        'port': '5556',
        'host': '192.168.50.15'
    }
    kwargs['jsuserid'] = 2
    matcher = TaskMatchJobsToKeywords(**kwargs)
    matcher.process_user_token_matches()
