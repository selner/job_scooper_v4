#!/bin/python
#  -*- coding: utf-8 -*-
#
###########################################################################
#
#  Copyright 2014-21 Bryan Selner
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
#
###########################################################################
from api.utils.tokenize import Tokenizer
from api.utils.dbmixin import DatabaseMixin


class TaskAddTitleTokens(DatabaseMixin):
    _tokenizer = None
    _config = {}

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)

        self._tokenizer = Tokenizer()
        self._config = dict(kwargs)

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
        return self.fetch_many_with_callback(query, self.tokenize_job_rows)
        # self.log(f'Completed update of title tokens for {total_rows} jobposting rows.')


    def tokenize_job_rows(self, dbrows):
        jobs_to_process = {}

        upd_query: str = '''
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

        if jobs_to_process:
            updated_jobs = self._tokenizer.batch_tokenize_strings(jobs_to_process, u'title', u'title_tokens', u'dict', maxlength=200)
            db_updates = []
            nfailed = 0

            for key in updated_jobs.keys():
                if not ('title_tokens' in updated_jobs[key] and
                        updated_jobs[key]['title_tokens'] and
                        len(updated_jobs[key]['title_tokens']) > 0):
                    self._logger.warning(f'job {key} did not successfully generate the needed title tokens for title {updated_jobs[key]["title"]}')
                    nfailed += 1
                else:
                    row_refkey_titleval = "_".join(updated_jobs[key]['title_tokens'])

                    val_comptitle = "UNKNOWN_COMPANY"
                    if ('company' in updated_jobs[key] and
                            updated_jobs[key]['company'] and
                            len(updated_jobs[key]['company']) > 0):
                        company = updated_jobs[key]['company']
                        company = company.lower().replace(" ", "")

                        val_comptitle = f'{company}{"".join(updated_jobs[key]["title_tokens"])}'

                    db_updates.append([
                        self.convert_array_to_column_value(updated_jobs[key]['title_tokens']),
                        row_refkey_titleval,
                        val_comptitle,
                        key
                    ])

            ret = self.update_many(upd_query, db_updates)

            print(ret)
            return ret
            return {'rows_updated': {'updated': ret, 'failed': nfailed}}
