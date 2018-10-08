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
from jobscooperrunner.tasks.matchtitles import TaskMatchJobsToKeywords
import click


@click.command('matchtitles', short_help='Deduplicates recently updated jobpostings in the database.')
@click.option('-i', '--inputfile',
              required=True,
              type=click.File(mode='rb'),
              help='input JSON data file of job postings')
@click.option('-o', '--outputfile',
              required=True,
              type=click.File(mode='wb'),
              help='output JSON data file path for results')
@click.option('-c', '--connecturi',
              default='',
              required=False,
              help='connection string uri or dsn for database access')
def matchtitles_cli(inputfile, outputfile, connecturi=None):
    click.echo('Launching matchtitles...')
    matcher = TaskMatchJobsToKeywords(input=inputfile, output=outputfile)
    matcher.export_results()
