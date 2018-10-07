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
from tasks.find_nearby_locations import TaskFindNearbyGeolocationsFromDb

if __name__ == '__main__':
    findnearby = TaskFindNearbyGeolocationsFromDb(
        connecturi='mysql:host=mysql.jobscooper.local;dbname=js4-dev3;port=3307;charset=utf8mb4;user=jobscooper;password=orange11;')
    geoloc = findnearby.get_geolocation(377)
    nearby = findnearby.find_nearby_locations(geoloc, 20)
    print nearby
