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
                raise Exception("Connection must be initialized before it can be accessed.")

            self._connection = pymysql.connect(**self.dbparams)

        return self._connection

    def close_connection(self):
        if self._connection and self._connection.open:
            self._connection.close()
        self._connection = None

    def fetch_all_from_query(self, querysql):
        result = {}

        try:
            print("Querying database: {}".format(querysql))

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

    def run_command(self, querysql, close_connection=True):
        try:
            # print("Running command: {}".format(querysql))

            with self.connection.cursor() as cursor:
                cursor.execute(querysql)

        except Exception, ex:
            print ex
            raise ex

        finally:
            self.connection.commit()
            if close_connection:
                self.close_connection()

