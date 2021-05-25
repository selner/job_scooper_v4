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
from api.utils.dbmixin import DatabaseMixin

class TaskMarkNonMatchesAsSkipSend(DatabaseMixin):

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)

    def update_job_matches(self):
        nonmatched = self.update_nonmatched_jobs()
        matched = self.update_matched_jobs()
        return { 'rows_updated': {
                 'nonmatched': nonmatched,
                'matched': matched
            }
        }

    def update_nonmatched_jobs(self):

        self.log("Marking user_job_matches that did not match job titles to skip-send...")

        querysql = """
            UPDATE
                user_job_match
            SET 
                user_notification_state = 3  -- 3 is "marked-skip-send"
            WHERE 
                user_notification_state = 1 AND -- 1 is "marked"
                is_job_match = 0
            AND user_job_match_id > 1;
            """

        try:
            rows_updated = self.run_command(querysql)
            self.log(f'Marked {rows_updated} user job matches as skip-send because they failed to match a user\'s job title.')
            return rows_updated

        except Exception as e:
            self.handle_error(e)

    def update_matched_jobs(self):

        self.log("Marking user_job_matches that match job titles to ready-to-send...")

        querysql = """
            UPDATE
                user_job_match
            SET 
                user_notification_state = 2  -- 2 is "ready-to-send"
            WHERE 
                user_notification_state = 1 AND -- 1 is "marked"
                is_job_match = 1
            AND user_job_match_id > 1;
            """

        try:
            rows_updated = self.run_command(querysql)
            self.log(f'Marked {rows_updated} user job matches as ready-to-send because they matched a user\'s job title.')
            return rows_updated

        except Exception as e:
            self.handle_error(e)

