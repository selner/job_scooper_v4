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
import urllib.parse
import pymysql

from collections import OrderedDict
from pymysql.cursors import DictCursorMixin, Cursor

ARRAY_JOIN_TOKEN = u"_||_"

class OrderedDictCursor(DictCursorMixin, Cursor):
    dict_type = OrderedDict


class DatabaseMixin:
    _debug = False
    dbparams = {}
    _connection = None

    def init_connection(self, **kwargs):

        """
        Args:
            **kwargs:
        """

        if 'connecturi' in kwargs:
            parsed = urllib.parse.urlparse(kwargs['connecturi'])

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

        if 'debug' in self.dbparams:
            self._debug = self.dbparams['debug']

    @property
    def connect_params(self):
        return {k: self.dbparams[k] for k in self.dbparams if
                  k in ["host", "password", "user", "port", "cursorclass", "use_unicode", "database"]}

    @property
    def connection(self):
        if not self._connection:
            if not self.dbparams:
                raise Exception(u"Connection must be initialized before it can be accessed.")

            if 'charset' not in self.dbparams:
                self.dbparams['charset'] = 'utf8'
            if 'use_unicode' not in self.dbparams:
                self.dbparams['use_unicode'] = True

            self._connection = pymysql.connect(**self.connect_params)

        return self._connection

    def new_cursor(self, curtype=OrderedDictCursor):
        cursor = self.connection.cursor(curtype)

        # Enforce UTF-8 for the connection.
        cursor.execute('SET NAMES utf8mb4')
        cursor.execute("SET CHARACTER SET utf8mb4")
        cursor.execute("SET character_set_connection=utf8mb4")

        return cursor


    def close_connection(self):
        if self._connection and self._connection.open:
            self._connection.close()
        self._connection = None

    def fetch_all_from_query(self, querysql):
        """
        Args:
            querysql:
        """
        result = {}

        try:
            print(u"Querying database: {}".format(querysql))

            with self.new_cursor() as cursor:
                cursor.execute(querysql)
                result = cursor.fetchall()
                descr = cursor.description
                fields = [col[0] for col in descr]
            return result
        except Exception as ex:
            print(ex)
            raise(ex)

        finally:
            self.connection.commit()
            self.close_connection()

    def fetch_many_with_callback(self, querysql, callback, batch_size=1000, return_results=False):

        if not callable(callback):
            raise Exception("Specified callback {} is not callable.".format(str(callback)))

        results = []

        try:
            print(u"Querying database: {}".format(querysql))

            with self.new_cursor() as cursor:
                total_rows = cursor.execute(querysql)

                total_num_batches, remainder = divmod(total_rows, batch_size)
                if remainder > 0:
                    total_num_batches += 1
                batch_counter = 0

                print(u"... matched {} DB records".format(total_rows))

                while batch_counter < total_num_batches:
                    curidx = batch_counter * batch_size
                    print(u"... processing records {} - {} through callback {}".format(curidx, curidx+batch_size, str(callback)))

                    rows = cursor.fetchmany(batch_size)
                    if not rows or len(rows) == 0:
                        break
                    if return_results == True:
                        results.extend(callback(rows))
                    else:
                        callback(rows)
                    batch_counter += 1

            self.connection.commit()

            if return_results:
                return results
            else:
                return total_rows

        except Exception, ex:
            print ex
            raise ex

        finally:
            if self.connection.is_connected():
                self.connection.close()

    def fetch_one_from_query(self, querysql):
        """
        Args:
            querysql:
        """
        result = self.fetch_all_from_query(querysql)
        if len(result) > 0 and isinstance(result, list):
            return result[0]

        return result

    def update_many(self, querysql, records):

        try:
            print(u"...updating {} database rows".format(len(records)))

            with self.new_cursor() as cursor:
                return cursor.executemany(querysql, records)

        except Exception, ex:
            print ex
            raise ex

        finally:
            self.connection.commit()
            self.close_connection()

    def run_command(self, querysql, values=None, close_connection=True):
        """
        Args:
            querysql:
            values:
            close_connection:
        """
        try:
            # print("Running command: {}".format(querysql))

            with self.connection.cursor() as cursor:
                return cursor.execute(querysql, values)

        except Exception as ex:
            print(ex)
            raise(ex)

        finally:
            self.connection.commit()

            if close_connection:
                self.close_connection()

    def get_table_columns(self, tablename):
        # print("Running command: {}".format(querysql))
        """
        Args:
            tablename:
        """
        column_data = self.fetch_all_from_query(u"SHOW columns from %s" % tablename)

        return set(col['Field'] for col in column_data)

    def add_row(self, tablename, primary_key_column, rowdict, tablecolumns=None):
        # XXX tablename not sanitized
        # XXX test for allowed keys is case-sensitive

        """
        Args:
            tablename:
            primary_key_column:
            rowdict:
            tablecolumns:
        """
        try:
            if not tablecolumns:
                allowed_keys = self.get_table_columns(tablename)
            else:
                allowed_keys = tablecolumns

            matched_keys = allowed_keys.intersection(rowdict)

            if len(rowdict) > len(matched_keys):
                unknown_keys = set(rowdict) - allowed_keys
                # print >> sys.stderr, "skipping keys:", ", ".join(unknown_keys)

            columns = ", ".join(matched_keys)
            values_template = ", ".join(["%s"] * len(matched_keys))

            sql = u"insert into %s (%s) values (%s)" % (
                tablename, columns, values_template)
            values = tuple(self.connection.escape_string(rowdict[key]) for key in matched_keys)
            with self.new_cursor() as cursor:
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

        except Exception as ex:
            print(ex)
            raise(ex)

        finally:
            self.connection.commit()
            self.close_connection()

    @staticmethod
    def convert_array_to_column_value(arr):
        if arr and len(arr) > 0:
            return ARRAY_JOIN_TOKEN.join(arr)

    @staticmethod
    def convert_column_value_to_array(str):
        if str and len(str) > 0:
            return unicode(str).split(ARRAY_JOIN_TOKEN)

