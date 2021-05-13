from logging import Handler, getLogger as getBaseLogger
import sys
from loguru import logger as logurulogger

#
# logurulogger.add(sys.stderr, format="{time} {level} {message}", level="DEBUG")
#
#
# class PropagateHandler(Handler):
#     def emit(self, record):
#         getBaseLogger(record.name).handle(record)

# logurulogger.add(PropagateHandler(), format="{message}")


def getLogger():
    return logurulogger

def logmsg(msg):
    logurulogger.log("INFO", msg)

def logdebug(msg):
    logurulogger.debug("INFO", msg)

