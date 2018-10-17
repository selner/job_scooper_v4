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
from util_tokenize import Tokenizer
from mixin_database import DatabaseMixin

class TaskAddTitleTokens(DatabaseMixin):
    _tokenizer = None
    _config = {}

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """
        self._tokenizer = Tokenizer()

        self._config = dict(kwargs)
        self.init_connection(**kwargs)

    def update_jobs_without_tokens(self):

        query = '''
            SELECT 
                jobposting_id,
                title,
                title_tokens,
                company,
                job_reference_key,
                key_company_and_title
            FROM 
                jobposting jp
            WHERE
                title_tokens IS NULL OR 
                title_tokens = ''
            ORDER BY 
               company,
                title
        '''
        total_rows = self.fetch_many_with_callback(query, self.tokenize_job_rows)
        print("Completed update of title tokens for {} jobposting rows.".format(total_rows))

    def tokenize_job_rows(self, dbrows):
        jobs_to_process = {}

        upd_query = '''
            UPDATE jobposting 
            SET 
                title_tokens = %s,
                job_reference_key = %s,
                key_company_and_title = %s 
            WHERE
                jobposting_id = %s'''

        for r in dbrows:
            if 'jobposting_id' in r:
                key = r['jobposting_id']
                jobs_to_process[key] = r

        db_updates = []

        if len(jobs_to_process) > 0:
            updated_jobs = self._tokenizer.batch_tokenize_strings(jobs_to_process, u'title', u'title_tokens', u'dict')
            for key in updated_jobs.keys():
                if not ('title_tokens' in updated_jobs[key] and
                        updated_jobs[key]['title_tokens'] and
                        len(updated_jobs[key]['title_tokens']) > 0):
                    print("Warning:  job {} did not successfully generate the needed title tokens for title '{}'".format(key, updated_jobs[key]['title']))
                else:
                    row_refkey_titleval = u"_".join(updated_jobs[key]['title_tokens'])

                    val_comptitle = "UNKNOWN_COMPANY"
                    if ('company' in updated_jobs[key] and
                            updated_jobs[key]['company'] and
                            len(updated_jobs[key]['company']) > 0):
                        company = updated_jobs[key]['company']
                        company = company.lower().replace(" ", "")

                        val_comptitle = u"{}{}".format(company, u"".join(updated_jobs[key]['title_tokens']))

                    db_updates.append([
                        self.convert_array_to_column_value(updated_jobs[key]['title_tokens']),
                        row_refkey_titleval,
                        val_comptitle,
                        key
                    ])

            rowcount = self.update_many(upd_query, db_updates)
            # print(u"Updated title tokens for {} jobposting records".format(rowcount))

    #
    # def tokenize_job_rows(self, dbrows):
    #     jobs_to_process = {}
    #
    #     for r in dbrows:
    #         if 'jobposting_id' in r:
    #             key = r['jobposting_id']
    #             jobs_to_process[key] = r
    #
    #     if len(jobs_to_process) > 0:
    #         updated_jobs = self._tokenizer.batch_tokenize_strings(jobs_to_process, u'title', u'title_tokens', u'dict')
    #         for key in updated_jobs.keys():
    #             row_refkey_titleval = u"_".join(updated_jobs[key]['title_tokens'])
    #             company = updated_jobs[key]['company']
    #             if len(company) > 0:
    #                 company = company.lower().replace(" ", "")
    #             rowvals = {
    #                 'jobposting_id' : key,
    #                 'titletokens_val' : self.convert_array_to_column_value(updated_jobs[key]['title_tokens']),
    #                 'row_refkey_titleval' : row_refkey_titleval,
    #                 'row_comptitle_keyval' : u"{}{}".format(company, u"".join(updated_jobs[key]['title_tokens']))
    #             }
    #
    #             upd_query = '''
    #                 UPDATE jobposting
    #                 SET
    #                     title_tokens = '%(titletokens_val)s',
    #                     job_reference_key = '%(row_refkey_titleval)s',
    #                     key_company_and_title = '%(row_comptitle_keyval)s'
    #                 WHERE
    #                     jobposting_id = %(jobposting_id)s''' % rowvals
    #
    #             self.run_command(upd_query)


from docopt import docopt

cli_usage = """
Usage:
  task_tokenize_jobtitles.py -c <dbstring> -u user
  task_tokenize_jobtitles.py --version

Options:
  -h --help     show this help message and exit
  --version     show version and exit
  -v --verbose      print status messages
  -c <dbstring>, --connecturi <dbstring>    connection string uri or dsn for a database to use    
"""

if __name__ == '__main__':
    arguments = docopt(cli_usage, version='0.1.1rc')
    args = {k.replace("--", ""): arguments[k] for k in arguments.keys()}

    if "connecturi" in args and args["connecturi"]:
        jobtok = TaskAddTitleTokens(**args)
        jobtok.update_jobs_without_tokens()

    else:
        print(u"Unable to set tokens for updated job titles.  Missing script arguments.")

