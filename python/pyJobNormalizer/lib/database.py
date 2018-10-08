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
import pymysql

from collections import OrderedDict
from pymysql.cursors import DictCursorMixin, Cursor
import sys


class OrderedDictCursor(DictCursorMixin, Cursor):
    dict_type = OrderedDict


class DatabaseMixin:
    dbparams = {}
    _connection = None

    def init_connection(self, **kwargs):

        from urlparse import urlparse

        if 'connecturi' in kwargs:
            parsed = urlparse(kwargs['connecturi'])

            if parsed.hostname:
                self.dbparams['host'] = parsed.hostname
                self.dbparams['user'] = parsed.username
                self.dbparams['password'] = parsed.password
                self.dbparams['port'] = parsed.port
                self.dbparams['database'] = parsed.path.replace("/", "")
            else:
                params = kwargs['connecturi'].split(":")

                args = {x.split('=')[0]: x.split('=')[1] for x in params[1].split(';') if len(x) > 1}
                for a in args:
                    self.dbparams[a] = args[a]

        self.dbparams['cursorclass'] = OrderedDictCursor
        if 'use_unicode' not in self.dbparams:
            self.dbparams['use_unicode'] = True
        self.dbparams['charset'] = "utf8mb4"
        if 'dbname' in self.dbparams:
            self.dbparams['database'] = self.dbparams.pop('dbname')
        if 'port' in self.dbparams:
            self.dbparams['port'] = int(self.dbparams.pop('port'))

    @property
    def connection(self):
        if not self._connection:
            if not self.dbparams:
                raise Exception(u"Connection must be initialized before it can be accessed.")

            self._connection = pymysql.connect(**self.dbparams)

        return self._connection

    def close_connection(self):
        if self._connection and self._connection.open:
            self._connection.close()
        self._connection = None

    def fetch_all_from_query(self, querysql):
        result = {}

        try:
            print(u"Querying database: {}".format(querysql))

            with self.connection.cursor() as cursor:
                cursor.execute(querysql)
                result = cursor.fetchall()
            return result
        except Exception, ex:
            print ex
            raise ex

        finally:
            self.connection.commit()
            self.close_connection()

    def fetch_one_from_query(self, querysql):
        result = self.fetch_all_from_query(querysql)
        if len(result) > 0 and isinstance(result, list):
            return result[0]

        return result

    def run_command(self, querysql, values=None, close_connection=True):
        try:
            print("Running command: {}".format(querysql))

            with self.connection.cursor() as cursor:
                return cursor.execute(querysql, values)

        except Exception, ex:
            print ex
            raise ex

        finally:
            self.connection.commit()

            if close_connection:
                self.close_connection()

    def get_table_columns(self, tablename):
        # print("Running command: {}".format(querysql))
        column_data = self.fetch_all_from_query(u"SHOW columns from %s" % tablename)

        return set(col['Field'] for col in column_data)

    def add_row(self, tablename, primary_key_column, rowdict, tablecolumns=None):
        # XXX tablename not sanitized
        # XXX test for allowed keys is case-sensitive

        try:
            if not tablecolumns:
                allowed_keys = self.get_table_columns(tablename)
            else:
                allowed_keys = tablecolumns

            matched_keys = allowed_keys.intersection(rowdict)

            if len(rowdict) > len(matched_keys):
                unknown_keys = set(rowdict) - allowed_keys
                print >> sys.stderr, "skipping keys:", ", ".join(unknown_keys)

            columns = ", ".join(matched_keys)
            values_template = ", ".join(["%s"] * len(matched_keys))

            sql = u"insert into %s (%s) values (%s)" % (
                tablename, columns, values_template)
            values = tuple(self.connection.escape_string(rowdict[key]) for key in matched_keys)
            with self.connection.cursor() as cursor:
                cursor.execute(sql, values)
                inserted_id = cursor.lastrowid
                if inserted_id:
                    result = self.fetch_all_from_query(
                        u"SELECT * FROM {} WHERE {} ={}".format(tablename, primary_key_column, inserted_id))
                    if result and len(result) > 0:
                        return result[0]
                    else:
                        return result
                else:
                    return None

        except Exception, ex:
            print ex
            raise ex

        finally:
            self.connection.commit()
            self.close_connection()
