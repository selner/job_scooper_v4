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


class TaskFindNearbyGeolocationsFromDb(DatabaseMixin):

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)

    def get_geolocation(self, geolocation_id):
        """
        Args:
            geolocation_id:
        """
        return self.fetch_one_from_query(f'SELECT * FROM geolocation WHERE geolocation_id = {geolocation_id}')

    def find_nearby_locations(self, geolocation, radius):

        """
        Args:
            geolocation:
            radius:
        """
        query = self.get_nearby_locations_query(geolocation, radius)
        result = self.fetch_all_from_query(query)

        print(f'Found {len(result)} geolocations within {radius} miles of the source geolocation.')

        return result


    def get_nearby_locations_query(self, geolocation, radius, columns=None):
        """
        Args:
            geolocation:
            radius:
            columns:
        """
        if not columns:
            columns = ["*"]

        if "latitude" not in geolocation or "longitude" not in geolocation:
            raise Exception("Geolocation missing latitude/longitude values required to compute nearby geolocations.")

        values = {
            'orig_latitude' : geolocation['latitude'],
            'orig_longitude' : geolocation['longitude'],
            'radius': radius,
            'column_fields': ", ".join(columns)
        }

        # SQL query adapted from original example
        # @author Seth McLean
        # @date January 28, 2015
        # @link https://clickherelabs.com/development-2/distance-searching-and-mysql/

        querysql = """
            SELECT %(column_fields)s
            FROM (
              SELECT 
                *,
                3956 * ACOS(COS(RADIANS(%(orig_latitude)s)) * COS(RADIANS(`latitude`)) * COS(RADIANS(%(orig_longitude)s) - RADIANS(`longitude`)) + SIN(RADIANS(%(orig_latitude)s)) * SIN(RADIANS(`latitude`))) AS `distance`
              FROM `geolocation`
              WHERE
                `latitude` 
                  BETWEEN %(orig_latitude)s - (%(radius)s / 69) 
                  AND %(orig_latitude)s + (%(radius)s / 69)
                AND `longitude` 
                  BETWEEN %(orig_longitude)s - (%(radius)s / (69 * COS(RADIANS(%(orig_latitude)s)))) 
                  AND %(orig_longitude)s + (%(radius)s / (69* COS(RADIANS(%(orig_latitude)s))))
            ) r
            WHERE `distance` <= %(radius)s
            -- ORDER BY `distance` ASC
        """ % values

        return querysql
