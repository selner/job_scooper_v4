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
    _outputfile = None

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)
        if 'output' in kwargs:
            self._outputfile = kwargs['output']

        self.gather_report_data()

    def gather_report_data(self):

        querysql = """
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

            successquerysql = """
                        SELECT 
                            js.jobsite_key,
                            max(date_ended) as `most_recent_completed_date`
                        FROM
                            job_site js
                            LEFT JOIN user_search_site_run ussr on ussr.jobsite_key = js.jobsite_key
                        WHERE
                            ussr.date_started > CURDATE() - {}
                        AND ussr.run_result_code = 4
                        GROUP BY 
                            js.jobsite_key
                        ORDER BY 
                            jobsite_key, 
                            `most_recent_completed_date` DESC                    """

            lastsuccesful_jobsites = self.fetch_all_from_query(successquerysql.format(NUMBER_DAYS_BACK))
            broken_sites_by_key = {}
            sites_to_include_by_key = {}

            for site in broken_jobsites:
                successful = { row['jobsite_key']: row['most_recent_completed_date'] for row in lastsuccesful_jobsites }
                if site['jobsite_key'] not in successful.keys() or successful[site['jobsite_key']] < site['most_recent_completed_date']:
                    sites_to_include_by_key[site['jobsite_key']] = site

            for site_error in sites_to_include_by_key.values():
                key = site_error['jobsite_key']
                pluralize = ""
                if site_error['count_search_pairs'] > 1:
                    pluralize = "es"
                broken_sites_by_key[key] = {
                    'jobsite_key': key,
                    'error_details': site_error['run_error_details'],
                    'search_count': f'{site_error["count_search_pairs"]} search{pluralize}',
                    'last_search': f'last run attempt: {site_error["most_recent_start_date"]}'
                }

            self.log(f'Found error reports for {len(sites_to_include_by_key)} broken job sites.  Skipped {len(broken_jobsites)-len(sites_to_include_by_key)} job sites who have since ran successfully.')

            import os
            templpath = os.path.join(os.path.dirname(__file__), "templates", "layouts", "email_broken_plugins_section.html")
            html = self.render_template(templpath, broken_sites_by_key)
            self.export_html_to_file(self._outputfile, html)


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
        self.log(f'Exporting rendered HTML to {filepath}')

        with open(filepath, 'w') as html_fp:
            html_fp.writelines(html)


