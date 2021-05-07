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
from task_find_nearby_locations import TaskFindNearbyGeolocationsFromDb


class TaskMarkOutOfAreaMatches(DatabaseMixin):
    _geolocation_id = None
    jobuserid = None
    _findtask = None
    _config = None

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)

        if 'geolocation_id' in kwargs:
            self._geolocation_id = kwargs['geolocation_id']
        if 'jobuserid' in kwargs:
            self._userid = kwargs['jobuserid']

        self._config = kwargs

    def get_user_search_geolocation_ids(self, userkey):
        """
        Args:
            userkey:
        """
        result = self.fetch_all_from_query("""
        SELECT 
            geolocation_id
        FROM 
            user_search_pair 
        WHERE 
            user_id = '%s'""" % self._userid)

        ret = []
        for rec in result:
            if 'geolocation_id' in rec:
                ret.append(rec['geolocation_id'])
        return set(ret)


    def mark_out_area(self):
        radius = 20
        if not self._userid:
            raise Exception("Missing required user_id value.")

        geolocids = self.get_user_search_geolocation_ids(self._userid)

        where_clause = ""

        findtask = TaskFindNearbyGeolocationsFromDb(**self._config)

        if len(geolocids) == 1:
            locid = geolocids.pop()
            geoloc = findtask.get_geolocation(locid)
            where_clause = findtask.get_nearby_locations_query(geoloc, radius, columns=["geolocation_id"])
        else:
            for locid in geolocids:
                geoloc = findtask.get_geolocation(locid)
                subquery = findtask.get_nearby_locations_query(geoloc, radius, columns=["geolocation_id"])

                if len(where_clause) > 0:
                    where_clause += " UNION "
                where_clause = "({})".format(subquery)

        base_query_update_in_area = """
            UPDATE user_job_match 
            SET 
                out_of_user_area = {}
            WHERE
                user_job_match_id > 0
                    AND user_job_match.jobposting_id IN 
                    (SELECT 
                        jobposting_id
                    FROM
                        jobposting
                    WHERE
                        geolocation_id {}
                    ({}))"""

        #
        #  mark job matches as "in" nearby locations for user's search areas
        #
        query_update_in_area = base_query_update_in_area.format(0, 'IN', where_clause)
        count_in_area = self.run_command(query_update_in_area)

        #
        #  mark job matches as "out of area" aka not in nearby locations to user's search areas
        #
        query_update_out_of_area = base_query_update_in_area.format(1, 'NOT IN', where_clause)
        count_out_area = self.run_command(query_update_out_of_area)

        #
        #  print out a last summary line with the results for logging by the original calling process
        #
        self.log(f'{count_in_area} user job matches marked in {self._userid}\'s search areas; {count_out_area} matches marked out of area.')

