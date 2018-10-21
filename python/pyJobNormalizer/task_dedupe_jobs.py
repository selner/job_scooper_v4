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


class TaskDedupeJobPosting(DatabaseMixin):
    connecturi = None
    keywords = {}
    negative_keywords = {}
    jobs = {}
    user_id = None
    df_job_tokens = None
    output_data = None
    _dupe_job_groups = {}

    def __init__(self, **kwargs):
        self.init_connection(**kwargs)

    def dedupe_jobs(self):

        print(u"Processing job postings for duplicates from database {}".format(self.dbparams))

        self.get_recent_duplicate_posts()

        self.update_database()

        self.update_jobmatch_exclusions()


    def get_recent_duplicate_posts(self):
        print(u"Getting groups of duplicate job postings...")

        querysql = u"""
                SELECT 
                MIN(jobposting_id) AS `first_posting_id`,
                --    COUNT(jobposting_id) AS `count_jobpostings`,
                    GROUP_CONCAT(jobposting_id) as `dupe_jobposting_ids`,
                    title_tokens,
                    REGEXP_REPLACE(IF(company IS NULL OR company = '',
                        jobsite_key,
                        company), '[^a-zA-Z0-9]{1,}', '') AS `company_or_jobsite_value`,
                    LOWER(REGEXP_REPLACE(location_display_value, '[^a-zA-Z0-9]{1,}', '')) as `location_value`
                FROM
                    jobposting
                WHERE
                    job_posted_date >= CURDATE() - 14
                GROUP BY title_tokens , `company_or_jobsite_value` , `location_value`
                        HAVING COUNT(jobposting_id) > 1
                ORDER BY title_tokens , `company_or_jobsite_value` ,  `location_value`, jobposting_id;
            """

        self._dupe_job_groups = self.fetch_all_from_query(querysql)

    def update_database(self):
        print(u"Updating {} duplicate job post groupings in the database...".format(
            len(self._dupe_job_groups)))
        nupdated = 0
        total_dupe_posts = 0

        try:

            for rec in self._dupe_job_groups:

                dupe_jobposting_ids = rec['dupe_jobposting_ids']
                if dupe_jobposting_ids and len(dupe_jobposting_ids) > 0:
                    ids = dupe_jobposting_ids.split(",")
                    dupe_ids = [id for id in ids if id != str(rec['first_posting_id'])]
                    total_dupe_posts += len(dupe_ids)

                    statement = u"""
                        UPDATE jobposting 
                        SET duplicates_posting_id={} 
                        WHERE jobposting_id IN ({})""".format(rec['first_posting_id'], ",".join(dupe_ids))

                    nupdated += self.run_command(statement, close_connection=False)

            print(u"Processed {} duplicate job postings over the past 14 days; marked {} newly as duplicate.".format(total_dupe_posts, nupdated))

        except Exception as e:
            print(u"Exception occurred:{}".format(e))
            raise e

        finally:
            self.close_connection()

    def update_jobmatch_exclusions(self):
        """
        Args:
        """
        try:
            print(u"Updating duplicate-related user_job_matches...")

            statement = u"""
                UPDATE user_job_match 
                SET 
                    is_excluded = 1
                WHERE
                    user_job_match_id > 0 AND 
                    user_notification_state NOT IN (4, 5) AND 
                    user_job_match.jobposting_id IN (SELECT 
                            jobposting.jobposting_id
                        FROM
                            jobposting
                        WHERE
                            duplicates_posting_id IS NOT NULL);
                """

            rows_updated = self.run_command(statement, close_connection=False)
            print(u"Updated {} user_job_matches marked excluded because they map to duplicate job postings.'".format(rows_updated))
            return rows_updated

        except Exception as e:
            print(u"Exception occurred:{}".format(e))
            raise e

        finally:
            self.close_connection()


