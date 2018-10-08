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
from jobscooperrunner.tasks.findplaces import FindPlacesFromDBLocationsTask
import click


@click.command('updatelocations', short_help='Find and set geolocations on job postings.')
@click.option('-c', '--connecturi',
              default=None,
              type=click.STRING,
              required=True,
              help='connection string uri or dsn for database access')
@click.option('-s', '--server',
              default="http://0.0.0.0:5000",
              type=click.STRING,
              required=True,
              help='scheme, domain and port for geocode api server [default: http://0.0.0.0:5000]')
def updatelocations_cli(connecturi, server):
    click.echo('Launching FindPlacesFromDBLocation with connecturi: "%s" and server: "%s"' % (connecturi, server))
    matcher = FindPlacesFromDBLocationsTask()
    matcher.update_all_locations(connecturi=connecturi, server=server)
