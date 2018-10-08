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

import os
import sys
import click

CONTEXT_SETTINGS = dict(auto_envvar_prefix='JOBSCOOPER')


class Context(object):

    def __init__(self):
        self.verbose = False
        self.home = os.getcwd()

    def log(self, msg, *args):
        """Logs a message to stderr."""
        if args:
            msg %= args
        click.echo(msg, file=sys.stderr)

    def vlog(self, msg, *args):
        """Logs a message to stderr only if verbose is enabled."""
        if self.verbose:
            self.log(msg, *args)


pass_context = click.make_pass_decorator(Context, ensure=True)
cmd_folder = os.path.abspath(os.path.join(os.path.dirname(__file__),
                                          'commands'))


class JobNormalizerMulticommand(click.CommandCollection):
    commands = []

    def list_commands(self, ctx):
        rv = []
        for filename in os.listdir(cmd_folder):
            if filename.endswith('.py') and \
               filename.startswith('cmd_'):
                rv.append(filename[4:-3])
        rv.sort()
        return rv

    def get_command(self, ctx, name):
        modname = 'commands.cmd_' + name
        try:
            if sys.version_info[0] == 2:
                name = name.encode('ascii', 'replace')
            modname = 'commands.cmd_' + name
            mod = __import__(modname,
                             None, None, ['cli'])
        except ImportError, imperr:
            print("Failed to import module {}:  {}".format(modname, unicode(imperr)))
            return

        modcommand = "{}_cli".format(name)
        return getattr(mod, modcommand)


@click.command(
    cls=JobNormalizerMulticommand,
    epilog='See "COMMAND -h" to read about a specific subcommand',
    short_help='%(prog)s [-h] COMMAND [args]',
    context_settings=CONTEXT_SETTINGS)
@click.option('-v', '--verbose', is_flag=True,
              help='Enables verbose mode.')
@pass_context
def cli(ctx, verbose=None):
    """Jobscooper task runner."""
    ctx.verbose = verbose

def main():
    argv = sys.argv[1:]
    debug = cli(argv, auto_envvar_prefix="JOBSCOOPER")

if __name__ == '__main__':
    main()
