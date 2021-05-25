###########################################################################
#
#  Copyright 2014-21 Bryan Selner
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
###########################################################################
from loguru import logger as logurulogger

def getLogger():
    return logurulogger

def logmsg(msg):
    logurulogger.log("INFO", msg)

def logdebug(msg):
    logurulogger.debug(msg)

from functools import wraps
from flask import request

