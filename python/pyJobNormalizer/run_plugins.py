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
from docopt import docopt
import os
import sys
import subprocess
import datetime
import codecs

cli_usage = """
Usage:
  run_plugins.py --configdir <string> [--outdir <string> --user <string> --stages <string>]
  run_plugins.py --help

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  --user=<string> a specific user filename to run
  --stages=<string> the stage list to run (separated by commas e.g. "1,2,3") [default: "1"]
  --configdir=<string> directory location for user configuration files
  --outdir=<string> directory to use for output
"""


def get_plugin_files():
    """

    Returns:

    """
    plugins = {}
    plugindir = os.path.realpath("../../plugins")
    for root, dirs, files in os.walk(plugindir):
        for d in dirs:
            dirpath = os.path.join(root, d)
            for fitem in files:
                itemname = os.path.basename(fitem)
                basef = itemname.split(".")[0]

                filepath = os.path.join(root, d, fitem)
                plugins[basef] = filepath
    return plugins


def run_plugin_for_user(plug, configini, outpath, run_stages):
    """

    Args:
        plug:
        configini:
        outpath:
        run_stages:
    """
    run_args = ["php", "/opt/jobs_scooper/runJobs.php", "-days 3", "--use_config_ini " + configini, "-o " + outpath,
               "-" + plugin]
    if run_stages:
        run_args.append("--stages {}".format(run_stages))

    # RUNCMD = " ".join(run_args)

    # cmd = RUNCMD.format(plug, configini, outpath)
    print(u"\trunning {} plugin".format(plugin))
    print (u"\tcalling: php {}".format(" ".join(run_args)))

    # file = "{}_runlog_{}.log".format(plug, datetime.datetime.now().strftime("%m-%d-%Y") + "_")
    # outfile = os.path.join(outpath, f)
    try:
        # f = codecs.open(outfile, encoding='utf-8', mode='w')
        p = subprocess.Popen(args=run_args, stdout=subprocess.PIPE,
                             stderr=subprocess.STDOUT,
                             stdin=subprocess.PIPE)

        resp = p.communicate()[0]
        # dresp = resp.split("\n")
        # print ("Last logged lines: " + "\n".join(dresp[0:5]))
        save_run_log(outpath, plugin, resp)
        # f.close()
    except:
        pass

    # print('Response: ', pp.pprint(dresp))
    # print('Return code:', p.returncode)


def save_run_log(outpath=None, name=None, textdata=None, encoding='utf-8'):
    """
        Writes a file to disk with the text passed.  If filepath is not specified, the filename will
        be <testname>_results.txt.
    :return: the path of the file
    """

    logfile = u"{}_run.log".format(name)
    outfile = os.path.join(outpath, logfile)
    try:
        fout = codecs.open(outfile, encoding=encoding, mode='w+')
        fout.write(textdata)
        fout.close()
    except:
        pass

    return outfile


if __name__ == '__main__':
    print " ".join(sys.argv)
    arguments = docopt(cli_usage, version='0.1.1rc')
    print (u"Run Plugins called with arguments: " + str(arguments))

    userKey = arguments['--user']

    stages = arguments['--stages']
    if not stages:
        stages = "1"

    inidir = None
    outdir = None
    if arguments['--configdir']:
        inidir = arguments['--configdir'].replace("'", "")

    if arguments['--outdir']:
        outdir = arguments['--outdir'].replace("'", "")
    if not outdir:
        outdir = os.environ['JOBSCOOPER_OUTPUT']

    outdir = os.path.join(outdir, "plugin_run_logs", datetime.datetime.now().strftime("%m-%d-%Y"))
    if not os.path.isdir(outdir):
        os.makedirs(outdir)

    plugs = get_plugin_files()
    print (u"Found {} plugins to run.".format(len(plugs)))
    print (u"Parameters:  inidir={}; outdir={}; userKey={}, stages={}".format(inidir, outdir, userKey, stages))
    for fname in os.listdir(inidir):
        f = os.path.join(inidir, fname)
        if os.path.isfile(f) and f.endswith(".ini") and (userKey is None or userKey in f):
            nextcfg = f
            print(u"Running plugins for config file {}".format(nextcfg))
            for plugin in plugs:
                run_plugin_for_user(plugin, nextcfg, outdir, stages)
