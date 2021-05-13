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
from logging import Handler, getLogger as getBaseLogger
import sys
from loguru import logger as logurulogger


logurulogger.add(sys.stderr, format="{time} {level} {message}", level="DEBUG")


class PropagateHandler(Handler):
    def emit(self, record):
        getBaseLogger(record.name).handle(record)

logurulogger.add(PropagateHandler(), format="{message}")


def getLogger():
    return logurulogger

def logmsg(msg):
    logurulogger.log("INFO", msg)

