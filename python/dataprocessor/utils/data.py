###########################################################################
#
#  Copyright 2014-2021 Bryan Selner
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
#
############################################################################
import codecs
import json
import csv
from dataprocessor.utils.log import logmsg

class SetEncoder(json.JSONEncoder):
    def default(self, obj):
        """
        Args:
            obj:
        """
        if isinstance(obj, set):
            return list(obj)
        return json.JSONEncoder.default(self, obj)


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


    logmsg(f'Loading {csvfilename}...')
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

    logmsg(f'Loaded {len(dict_records)} rows from {csvfilename}')

    return {'fieldnames': fields, 'dict': dict_records}

