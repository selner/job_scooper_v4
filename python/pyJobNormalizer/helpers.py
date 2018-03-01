#!/bin/python
# -*- coding: utf-8 -*-
import codecs
import re
import unicodecsv


xstr = lambda s: str(s) or ""



def load_ucsv(filePath, fieldnames=None, delimiter=",", quotechar="\"", keyfield=None):
    ret = {}

    fp = codecs.open(filePath, mode='r')

    dialect = unicodecsv.Sniffer().sniff(fp.read(1024))
    fp.seek(0)

    has_header = unicodecsv.Sniffer().has_header(fp.read(1024))
    fp.seek(0)

    if has_header is True and fieldnames is None:
        header_line = fp.readline()
        fp.seek(0)
        fieldnames = header_line.split(dialect.delimiter)

    csv_reader = unicodecsv.DictReader(fp, dialect=dialect, delimiter=delimiter, quotechar=quotechar, fieldnames=fieldnames)

    if fieldnames is None:
        fieldnames = []
        nCount = 0
        for n in csv_reader.unicode_fieldnames:
            fieldnames.append("Field_{}".format(nCount))
            nCount = nCount + 1

        csv_reader.unicode_fieldnames = fieldnames

    # skip the header row
    if has_header is True:
        next(fp)

    nRow = 0
    for row in csv_reader:
        if keyfield:
            ret[row[keyfield]] = row
        else:
            ret[nRow] = row
        nRow = nRow + 1

    return ret