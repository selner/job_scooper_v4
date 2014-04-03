<?php
/**
 * Copyright 2014 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
require_once dirname(__FILE__) . '/../../scooper/src/include/plugin-base.php';
require_once dirname(__FILE__) . '/../../scooper/src/include/common.php';
require_once dirname(__FILE__) . '/../lib/simple_html_dom.php';
require_once dirname(__FILE__) . '/../src/ClassSiteExportBase.php';
require_once dirname(__FILE__) . '/../../scooper/src/lib/pharse.php';

date_default_timezone_set("America/Los_Angeles");

const C_NORMAL = 0;
const C_EXCLUDE_BRIEF = 1;
const C_EXCLUDE_GETTING_ACTUAL_URL = 3;

const C_STR_DATAFOLDER = '/Users/bryan/Code/data/jobs/';
const C_STR_FOLDER_JOBSEARCH= '/Users/bryan/Dropbox/Job Search 2013/';

