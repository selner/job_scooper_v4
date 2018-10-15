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
from database import DatabaseMixin

class TaskAddNewMatchesToUser(DatabaseMixin):
    dbparams = {}

    def __init__(self, **kwargs):

        """
        Args:
            **kwargs:
        """
        self.init_connection(**kwargs)

        if 'jobsite' in kwargs and 'userid' in kwargs:
            self.add_new_posts_to_user(jobsitekey=kwargs['jobsite'], user_id=kwargs['userid'])


    def add_new_posts_to_user(self, jobsitekey, user_id):

        print(u"Adding all new jobpostings for {} to user_id={}...".format(jobsitekey, user_id))

        querysql = u"""
            INSERT IGNORE INTO user_job_match(
                user_job_match.user_id,
                user_job_match.jobposting_id, 
                user_job_match.first_matched_at,
                user_job_match.last_updated_at
            )
            SELECT 
               {},
               jp.jobposting_id,
               CURDATE(),
               CURDATE()
            FROM 
                jobposting jp
            WHERE 
                first_seen_at >= CURDATE() - 1
            AND 
                jobsite_key = '{}' AND 
                duplicates_posting_id IS NULL AND
                jp.jobposting_id NOT IN (
                    SELECT uj.jobposting_id FROM user_job_match uj WHERE uj.user_id={})
            -- AND 
            --	NOT (title_tokens like '%serv%' OR 
            --    title_tokens like '%barten%' )
        """

        try:
            rows_updated = self.run_command(querysql.format(user_id, jobsitekey, user_id))
            print("Added {} jobpostings to user for jobsite {}".format(rows_updated, jobsitekey))

        except Exception as e:
            print(u"Exception occurred: {}".format(e))
            raise e
