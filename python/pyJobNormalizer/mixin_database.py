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
from util_log import getLogger

ARRAY_JOIN_TOKEN = "_||_"
from logging import INFO, DEBUG, WARNING, ERROR, CRITICAL



class OrderedDictCursor(DictCursorMixin, Cursor):
    dict_type = OrderedDict


class DatabaseMixin:
    _debug = False
    _dbparams = {}
    _connection = None
    _logger = None
    
    def __init__(self, **kwargs):
        self._logger = getLogger()

        self.log("Parsing commmand line parameters...")
        self._parse_arguments(**kwargs)

    def log(self, msg, level=INFO):
        self._logger.log(level, msg)

    def handle_error(self, err, msg=None):
        if msg and len(msg) > 0:
            self._logger.error(msg, exc_info=1)
        else:
            self._logger.error("Exception occurred: {}".format(err), exc_info=1)
        raise(err)

    def _parse_arguments(self, **kwargs):
        """
        Args:
            **kwargs:
        """
        import re
        values = None
        if 'dsn' in kwargs:
            driver, rest = kwargs['dsn'].split(':', 1)
            values = dict(re.findall('([\w\.]+)=([\w\.]+)', rest), driver=driver)
            self._dbparams.update(values)
        elif 'connecturi' in kwargs:
            parsed = urllib.parse.urlparse(kwargs['connecturi'])

            if parsed.hostname:
                self._dbparams['host'] = parsed.hostname
                self._dbparams['user'] = parsed.username
                self._dbparams['password'] = parsed.password
                self._dbparams['port'] = parsed.port
                self._dbparams['database'] = parsed.path.replace("/", "")
            else:
                params = kwargs['connecturi'].split(":")

                args = {x.split('=')[0]: x.split('=')[1] for x in params[1].split(';') if len(x) > 1}
                for a in args:
                    self._dbparams[a] = args[a]

        self._dbparams['cursorclass'] = OrderedDictCursor
        if 'use_unicode' not in self._dbparams:
            self._dbparams['use_unicode'] = True
        self._dbparams['charset'] = "utf8mb4"
        if 'dbname' in self._dbparams:
            self._dbparams['database'] = self._dbparams.pop('dbname')
        if 'port' in self._dbparams:
            self._dbparams['port'] = int(self._dbparams.pop('port'))

        if 'debug' in self._dbparams:
            self._debug = self._dbparams['debug']
            
    @property
    def connect_params(self):
        return {k: self._dbparams[k] for k in self._dbparams if
                  k in ["host", "password", "user", "port", "cursorclass", "use_unicode", "database"]}

    @property
    def connection(self):
        if not self._connection:
            if not self._dbparams:
                raise Exception("Connection must be initialized before it can be accessed.")

            if 'charset' not in self._dbparams:
                self._dbparams['charset'] = 'utf8'
            if 'use_unicode' not in self._dbparams:
                self._dbparams['use_unicode'] = True

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
            self.log("Querying database: {}".format(querysql))

            with self.new_cursor() as cursor:
                cursor.execute(querysql)
                result = cursor.fetchall()
                descr = cursor.description
                fields = [col[0] for col in descr]
            return result
        except Exception as ex:
            self.handle_error(ex)

        finally:
            self.connection.commit()
            self.close_connection()

    def fetch_many_with_callback(self, querysql, callback, batch_size=1000, return_results=False):

        if not callable(callback):
            raise Exception("Specified callback {} is not callable.".format(str(callback)))

        results = []

        try:
            self.log("Querying database: {}".format(querysql))

            with self.new_cursor() as cursor:
                total_rows = cursor.execute(querysql)

                total_num_batches, remainder = divmod(total_rows, batch_size)
                if remainder > 0:
                    total_num_batches += 1
                batch_counter = 0

                self.log("... matched {} DB records".format(total_rows))

                while batch_counter < total_num_batches:
                    curidx = batch_counter * batch_size
                    self.log("... processing records {} - {} through callback {}".format(curidx, curidx+batch_size, str(callback)))

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

        except Exception as ex:
            self.handle_error(ex)

        finally:
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
            self.log("...updating {} database rows".format(len(records)))

            with self.new_cursor() as cursor:
                return cursor.executemany(querysql, records)

        except Exception as ex:
            self.handle_error(ex)


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
            self.log("executing SQL: {}".format(querysql), DEBUG)

            with self.connection.cursor() as cursor:
                return cursor.execute(querysql, values)

        except Exception as ex:
            self.handle_error(ex)

        finally:
            self.connection.commit()

            if close_connection:
                self.close_connection()

    def get_table_columns(self, tablename):
        # self.log("Running command: {}".format(querysql))
        """
        Args:
            tablename:
        """
        column_data = self.fetch_all_from_query("SHOW columns from %s" % tablename)

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

            sql = "insert into %s (%s) values (%s)" % (tablename, columns, values_template)
            values = tuple(self.connection.escape_string(rowdict[key]) for key in matched_keys)
            with self.new_cursor() as cursor:
                cursor.execute(sql, values)
                inserted_id = cursor.lastrowid
                if inserted_id:
                    query = "SELECT * FROM {} WHERE {} ={}".format(tablename, primary_key_column, inserted_id)
                    result = self.fetch_all_from_query(query)
                    if result and len(result) > 0:
                        return result[0]
                    else:
                        return result
                else:
                    return None

        except Exception as ex:
            self.handle_error(ex)

        finally:
            self.connection.commit()
            self.close_connection()

    @staticmethod
    def convert_array_to_column_value(arr):
        if arr and len(arr) > 0:
            return ARRAY_JOIN_TOKEN.join(arr)

    def get_table_column_info(self, tablename):
        # print("Running command: {}".format(querysql))
        """
        Args:
            tablename:
        """

        column_data = self.fetch_all_from_query("SHOW columns from %s" % tablename)

        column_info = {}
        for col in column_data:
            import re
            size = re.sub("[^0-9]", "", str(col['Type']))
            if not size or len(size) == 0:
                size = None
            else:
                size = int(size)

            column_info[col['Field']] = {
                "type" : re.sub("[^a-z]", "", str(col['Type'])),
                "name" : col['Field'],
                "default_value" : col['Default'],
                "size" : size
            }

        return column_info

    @staticmethod
    def convert_column_value_to_array(str):
        if str and len(str) > 0:
            return str.split(ARRAY_JOIN_TOKEN)

