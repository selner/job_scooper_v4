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
from jobscooperrunner.tasks.dedupejobs import DedupeJobPostingFromDB
import click

@click.command('markdupes', short_help='Deduplicates recently updated jobpostings in the database.')
@click.option('-c', '--connecturi', default=None, required=False, help='connection string uri or dsn for database access')
def markdupes_cli(connecturi=None):
    click.echo('Launching mark_duplicates with connecturi: "%s"' % connecturi)
    matcher = DedupeJobPostingFromDB()
    matcher.load_data(connecturi=connecturi)
    matcher.dedupe_jobs()
    matcher.update_database()



# from complex.cli import pass_context
#

# @click.argument('path', required=False, type=click.Path(resolve_path=True))
# @pass_context
# def cli(ctx, path):
#     """Initializes a repository."""
#     if path is None:
#         path = ctx.home
#     ctx.log('Initialized the repository in %s',
#             click.format_filename(path))
# @click.command('mark_duplicates', short_help='Deduplicates recently updated jobpostings in the database.')
# @click.option('-i', '--input', required=True, type=File('rb'), help='input JSON data file of job postings')
# @click.option('-o', '--output', required=True, type=File('wb'), help='output JSON data file path for results')
# @click.option('-c', '--connecturi', default=None, required=False, help='connection string uri or dsn for database access')
# def mark_duplicates_cli(input=None, output=None, connecturi=None):
#     click.echo('Launching mark_duplicates input: %s ouput: %s, connecturi: %s' % input, output, connecturi)
#     click.echo('TODO TODO TODO')

