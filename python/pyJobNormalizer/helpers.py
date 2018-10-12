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
import unicodecsv


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


def load_json(filepath):
    """
    Args:
        filepath:
    """
    f = codecs.open(filepath, 'rb', encoding='utf-8')
    result = json.load(f)
    f.close()
    return result


def write_json(filepath, data):
    """
    Args:
        filepath:
        data:
    """
    outf = codecs.open(filepath, 'w', encoding='utf-8')

    json.dump(data, outf, indent=4, encoding='utf-8', cls=SetEncoder)
    outf.close()


def load_ucsv(filepath, fieldnames=None, delimiter=",", quotechar="\"", keyfield=None):
    """
    Args:
        filepath:
        fieldnames:
        delimiter:
        quotechar:
        keyfield:
    """
    ret = {}

    fp = codecs.open(filepath, mode='r')

    dialect = unicodecsv.Sniffer().sniff(fp.read(1024))
    fp.seek(0)

    has_header = unicodecsv.Sniffer().has_header(fp.read(1024))
    fp.seek(0)

    if has_header is True and fieldnames is None:
        header_line = fp.readline()
        fp.seek(0)
        fieldnames = header_line.split(dialect.delimiter)

    csv_reader = unicodecsv.DictReader(fp, dialect=dialect, delimiter=delimiter, quotechar=quotechar,
                                       fieldnames=fieldnames)

    if fieldnames is None:
        fieldnames = []
        ncount = 0
        for n in csv_reader.unicode_fieldnames:
            fieldnames.append("Field_{}".format(ncount))
            ncount = ncount + 1

        csv_reader.unicode_fieldnames = fieldnames

    # skip the header row
    if has_header is True:
        next(fp)

    nrow = 0
    for row in csv_reader:
        if keyfield:
            ret[row[keyfield]] = row
        else:
            ret[nrow] = row
        nrow = nrow + 1

    return ret


def loadcsv(csvfilename, rowkeyname=None):
    """
    Args:
        csvfilename:
        rowkeyname:
    """

    print(u"Loading {}...".format(csvfilename))
    from io import open
    csv_fp = open(csvfilename, 'rb')
    dict_records = {}
    fields = {}

    csv_reader = None
    try:
        with csv_fp:
            csv_reader = unicodecsv.DictReader(csv_fp, delimiter=",", quoting=unicodecsv.QUOTE_ALL, errors='strict')
            fields = csv_reader.fieldnames
            for row in csv_reader:
                if rowkeyname is None:
                    rowkeyname = fields[0]

                dict_records[row[rowkeyname]] = row
    except Exception as err:
        print(err)
        pass

    print(u"Loaded {} rows from {}.".format(len(dict_records), csvfilename))

    return {'fieldnames': fields, 'dict': dict_records}


def writedicttocsv(csvfilename, data, keys=None):
    """
    Args:
        csvfilename:
        data:
        keys:
    """
    print(u"Writing {} rows to file {}...".format(len(data), csvfilename))

    if keys is None:
        item = data.items()[0]
        keys = item.keys()

    csvfile = open(csvfilename, "wb")
    csv_writer = unicodecsv.DictWriter(csvfile, fieldnames=keys, dialect=unicodecsv.excel)
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

