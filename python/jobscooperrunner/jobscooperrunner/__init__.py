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

__name__ = "jobscooperrunner"
__package__ = "jobscooperrunner"
__all__ = ['cli', 'commands', 'tasks', 'utils']

import os
import pkgutil
import sys

def load_all_modules_from_dir(dirname):
    for importer, package_name, _ in pkgutil.iter_modules([dirname]):
        full_package_name = '%s.%s' % (dirname, package_name)

        print("Loading module {}".format(full_package_name))
        if full_package_name not in sys.modules:
            module = importer.find_module(package_name
                        ).load_module(package_name)


for mod in __all__:
    if os.path.isdir(mod):
        load_all_modules_from_dir(mod)

print("\n")
print("\n")


