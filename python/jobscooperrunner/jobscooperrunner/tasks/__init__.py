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
# __name__ = "tasks"
# __all__ = ['dedupejobs', 'findnearbylocations', 'findplaces', 'markoutofarea', 'matchtitles', 'tokenize']
# # Don't modify the line above, or this line!
# import automodinit
# automodinit.automodinit(__name__, __file__, globals())
# del automodinit
# Anything else you want can go after here, it won't get modified.
import os
mods = []
for filename in os.listdir(os.path.dirname(__file__)):
    if filename.endswith('.py') and \
            filename.startswith('cmd_'):
        mods.append(filename[4:-3])
mods.sort()
for m in mods:
    modname = m
    try:
        modname = m
        mod = __import__(m,
                     None, None, ['cli'])
    except ImportError, imperr:
        print("Failed to import module {}:  {}".format(modname, unicode(imperr)))
