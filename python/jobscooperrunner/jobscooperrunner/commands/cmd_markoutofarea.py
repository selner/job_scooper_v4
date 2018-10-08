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
from jobscooperrunner.tasks.markoutofarea import TaskMarkOutOfAreaMatches

import click

@click.command('markoutofarea', short_help='Mark user_job_matches as in or out of user\'s search area.')
@click.option('-c', '--connecturi', default=None, type=click.STRING, required=True, help='connection string uri or dsn for database access')
@click.option('-u', '--user', type=click.STRING, required=True, help='slug key for which user to update job matches')
def markoutofarea_cli(connecturi, user):
    click.echo('Launching TaskMarkOutOfAreaMatches with connecturi: "%s" and user: "%s"' % (connecturi, user))
    matcher = TaskMarkOutOfAreaMatches(connecturi=connecturi, user=user)
    matcher.mark_out_area()
