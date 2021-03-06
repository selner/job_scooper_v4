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
import codecs
import json
import csv

import docopt
from util_log import logmsg, logdebug

docopt_func = getattr(docopt, 'docopt')

COMMON_OPTIONS = """
  --dsn <dbstring>                          DSN connection string for database     
  -c <dbstring>, --connecturi <dbstring>    connection string uri or dsn for a database to use    
  --log <logdir>                            output directory for logging
  -u <userstring> --user <userstring>                       DB user for connection
  -P <userpass> --password <userpass>                         DB user password for connection
  -h <hostname> --host <hostname>                         DB server host for connection
  -p <portid> --port <portid>                           DB server port for connection
  --database <dbstring>                     DB server database for connection      
  -h --help                                 show this help message and exit
  --version                                 show version and exit
  -v --verbose                              print status messages
"""

def docopt_ext(doc, argv=None, help=True, version=None, options_first=False, filename=None):

    if filename:
        from util_log import logurulogger
        import os
        logfile = f'pyJobNormalizer-{os.path.basename(filename)[:-3]}'
        logurulogger.add(f'/tmp/{logfile}.log', format="{time} {level} {message}", level="INFO")

    vals = docopt_func(doc, argv, help, version, options_first)
    if vals and len(vals) > 0:
        retvals = {}
        for k in vals.keys():
            key = k
            if k.startswith("--"):
                key = k[2:]

            v = vals[k]

            if v and isinstance(v, str) and v.startswith("'") and v.endswith("'"):
                v = v[1:-1]

            retvals[key] = v

        return retvals

    return vals


class SetEncoder(json.JSONEncoder):
    def default(self, obj):
        """
        Args:
            obj:
        """
        if isinstance(obj, set):
            return list(obj)
        return json.JSONEncoder.default(self, obj)


xstr = lambda s: str(s) or ""

def strip_control_chars(val):
    stripped = lambda s: "".join(i for i in val if 31 < ord(i) < 127)
    return stripped


def clean_text_for_matching(val):

    from string import punctuation
    # using exclist from above
    ret = ''.join(x for x in val if x not in punctuation)

    ret = ret.replace("  ", " ")
    ret = ret.strip()

    return ret

def simpleuni(val):
    """
        unidecode(u'ko\u017eu\u0161\u010dek')
        'kozuscek'

        unidecode(u'30 \U0001d5c4\U0001d5c6/\U0001d5c1')
        '30 km/h'

        unidecode("\u5317\u4EB0")
        'Bei Jing '
    """
    from unidecode import unidecode
    return unidecode(val)

def load_json(filepath):
    """
    Args:
        filepath:
    """
    f = codecs.open(filepath, 'rb')
    result = json.load(f)
    f.close()
    return result


def write_json(filepath, data):
    """
    Args:
        filepath:
        data:
    """
    outf = codecs.open(filepath, 'w')
    logdebug(f'Writing to json file {filepath}')
    json.dump(data, outf, indent=4, cls=SetEncoder)
    outf.close()

def dump_var_to_json(data):
    return json.dumps(data, indent=4, cls=SetEncoder)



def loadcsv(csvfilename, rowkeyname=None):
    """
    Args:
        csvfilename:
        rowkeyname:
    """
    import os


    logdebug(f'Loading {csvfilename}...')
    from io import open
    csv_fp = open(csvfilename, 'r')
    dict_records = {}
    fields = {}

    csv_reader = None
    try:
        with csv_fp:
            csv_reader = csv.DictReader(csv_fp, delimiter=",", quoting=csv.QUOTE_ALL)
            fields = csv_reader.fieldnames
            for row in csv_reader:
                if rowkeyname is None:
                    rowkeyname = fields[0]

                dict_records[row[rowkeyname]] = row
    except Exception as err:
        print(err)
        pass

    logdebug(f'Loaded {len(dict_records)} rows from {csvfilename}')

    return {'fieldnames': fields, 'dict': dict_records}

def load_csv_data(csvfilename, fields=None):
    """
    Args:
        csvfilename:
        rowkeyname:
    """
    import os


    logmsg(f'Loading {csvfilename}...')
    from io import open
    csv_fp = open(csvfilename, 'r')
    data = []

    csv_reader = None
    try:
        with csv_fp:
            csv_reader = csv.DictReader(csv_fp, fieldnames=fields, delimiter=",", quoting=csv.QUOTE_ALL)
            fields = csv_reader.fieldnames
            for row in csv_reader:
                if list(row.values()) == fields:
                    continue
                if len(fields) == 1:
                    data.append(row[fields[0]])
                else:
                    data.append(row)

        logdebug(f'Loaded {len(data)} rows from {csvfilename}')
        return data

    except Exception as err:
        print(err)
        pass


def writedicttocsv(csvfilename, data, keys=None):
    """
    Args:
        csvfilename:
        data:
        keys:
    """
    logdebug(f'Writing {len(data)} rows to file {csvfilename}...')

    if keys is None:
        itemkey = list(data)[0]
        item = data[itemkey]
        keys = list(item)

    csvfile = open(csvfilename, "w")
    csv_writer = csv.DictWriter(csvfile, fieldnames=keys, dialect=csv.excel)
    csv_writer.writeheader()
    for row in data:
        for k in data[row].keys():
            if k not in keys:
                del data[row][k]
        try:
            csv_writer.writerow(data[row])
        except Exception as ex:
            pass

    csvfile.close()
    return csvfilename


def combine_dicts(a, b):
    """
    Args:
        a:
        b:
    """
    z = a.copy()
    for k in a.keys():
        for kb in b[k]:
            z[k][kb] = b[k][kb]
    return z

def dump_var_to_json(data):
    return json.dumps(data, indent=4, cls=SetEncoder)

