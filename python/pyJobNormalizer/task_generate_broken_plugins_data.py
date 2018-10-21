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
from mixin_database import DatabaseMixin

import json
from datetime import date
import uuid
from io import open

class JSONEncoderExt(json.JSONEncoder):
    """Extends the default encoder by also supporting
    ``datetime`` objects, ``UUID`` and lists).
    """

    def default(self, o):
        """Implement this method in a subclass such that it returns a
        serializable object for ``o``, or calls the base implementation (to
        raise a :exc:`TypeError`).

        For example, to support arbitrary iterators, you could implement
        default like this::

            def default(self, o):
                try:
                    iterable = iter(o)
                except TypeError:
                    pass
                else:
                    return list(iterable)
                return JSONEncoder.default(self, o)
        """
        if isinstance(o, date):
            return o.isoformat()
        elif isinstance(o, uuid.UUID):
            return str(o)
        else:
            try:
                iterable = iter(o)
            except TypeError:
                pass
            else:
                return list(iterable)

        return json.JSONEncoder.default(self, o)

NUMBER_DAYS_BACK = 3

class TaskGenerateBrokenPluginReportData(DatabaseMixin):

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)

        self.gather_report_data()

    def gather_report_data(self):

        querysql = u"""
            SELECT 
                js.jobsite_key,
--                 run_result_code,
                max(date_started) as `most_recent_start_date`,
                max(date_ended) as `most_recent_completed_date`,
                COUNT(user_search_pair_id) as `count_search_pairs`,
                run_error_details
            FROM
                job_site js
                LEFT JOIN user_search_site_run ussr on ussr.jobsite_key = js.jobsite_key
            WHERE
                ussr.date_started > CURDATE() - {}
            AND ussr.run_result_code = 1
            GROUP BY 
    --          run_result_code , 
                js.jobsite_key,
                run_error_details
            ORDER BY 
                jobsite_key, 
                `most_recent_completed_date` DESC, 
                `most_recent_start_date` DESC, 
                `run_result_code` DESC
        """

        try:
            broken_jobsites = self.fetch_all_from_query(querysql.format(NUMBER_DAYS_BACK))
            broken_sites_by_key = {}


            for site_error in broken_jobsites:
                key = site_error['jobsite_key']
                pluralize = ""
                if site_error['count_search_pairs'] > NUMBER_DAYS_BACK:
                    pluralize = "es"
                broken_sites_by_key[key] = {
                    'jobsite_key': key,
                    'error_details': site_error['run_error_details'],
                    'search_count': "{} search{}".format(site_error['count_search_pairs'], pluralize),
                    'last_search': "last run attempt: {}".format(site_error['most_recent_start_date'])
                }

            print("Found error reports for {} broken job sites.".format(len(broken_jobsites)))

            import os
            templpath = os.path.join(os.path.dirname(__file__), "templates", "layouts", "email_broken_plugins_section.html")
            html = self.render_template(templpath, broken_sites_by_key)
            self.export_html_to_file(self._dbparams['output'], html)


        except Exception as e:
            self.handle_error(e)

    @staticmethod
    def render_template(template_filepath, data):
        from jinja2 import Template
        fp_template = open(template_filepath, 'r')
        template_content = fp_template.readlines()
        template = Template("\n".join(template_content))

        return template.render(data=data)

    def export_html_to_file(self, filepath, html):
        print("Exporting rendered HTML to {}".format(filepath))

        with open(filepath, 'w') as html_fp:
            html_fp.writelines(html)


