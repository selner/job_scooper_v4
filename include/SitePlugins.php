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

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/include/Options.php');
require_once(__ROOT__.'/lib/Linkify.php');
require_once(__ROOT__.'/include/ClassJobsSitePlugin.php');
require_once(__ROOT__.'/include/ClassJobsSitePluginCommon.php');
require_once(__ROOT__.'/include/ClassJobsRunWrapper.php');


require_once (__ROOT__.'/include/ClassMultiSiteSearch.php');
require_once (__ROOT__.'/plugins/PluginAmazon.php');
require_once (__ROOT__.'/plugins/PluginCraigslist.php');
require_once (__ROOT__.'/plugins/PluginIndeed.php');
require_once (__ROOT__.'/plugins/PluginSimplyHired.php');
require_once (__ROOT__.'/plugins/PluginGlassdoor.php');
require_once (__ROOT__.'/plugins/PluginPorch.php');
require_once (__ROOT__.'/plugins/PluginExpedia.php');
require_once (__ROOT__.'/plugins/PluginLinkUp.php');
require_once (__ROOT__.'/plugins/PluginEmploymentGuide.php');
require_once (__ROOT__.'/plugins/PluginMonster.php');
require_once (__ROOT__.'/plugins/PluginCareerBuilder.php');
require_once (__ROOT__.'/plugins/PluginMashable.php');
require_once (__ROOT__.'/plugins/PluginDisney.php');
require_once (__ROOT__.'/plugins/PluginOuterwall.php');
require_once (__ROOT__.'/plugins/PluginTableau.php');
require_once (__ROOT__.'/plugins/PluginGoogle.php');
require_once (__ROOT__.'/plugins/PluginFacebook.php');
require_once (__ROOT__.'/plugins/PluginEbay.php');
require_once (__ROOT__.'/plugins/PluginGroupon.php');
require_once (__ROOT__.'/plugins/PluginGeekwire.php');
require_once (__ROOT__.'/plugins/PluginDotJobs.php');
require_once (__ROOT__.'/plugins/PluginZipRecruiter.php');
require_once (__ROOT__.'/plugins/PluginStartupHire.php');
require_once (__ROOT__.'/plugins/PluginAdicioCareerCast.php');

define( "C__SEARCH_RESULTS_TYPE_NONE__", 0x0 );
define( "C__SEARCH_RESULTS_TYPE_WEBPAGE__", 0x1 );
define( "C__SEARCH_RESULTS_TYPE_XML__", 0x2 );
define( "C__SEARCH_RESULTS_TYPE_HTML_FILE__", 0x4 );
//And so on, 0x8, 0x10, 0x20, 0x40, 0x80, 0x100, 0x200, 0x400, 0x800 etc..

const C__JOB_NONE = 0x100;
const C__JOB_PAGECOUNT_NOTAPPLICABLE__= 0x200;
const C__JOB_DAYS_VALUE_NOTAPPLICABLE__ = 0x400;
const C__JOB_ITEMCOUNT_NOTAPPLICABLE__ = 0x800;

const C__TOTAL_ITEMS_UNKNOWN__ = 11111;


define('JOBSITE_BASE_WEBPAGE_FLAGS', C__SEARCH_RESULTS_TYPE_WEBPAGE__);
define('JOBSITE_BASE_HTML_DOWNLOAD_FLAGS', C__SEARCH_RESULTS_TYPE_HTML_FILE__ | C__JOB_PAGECOUNT_NOTAPPLICABLE__ | C__JOB_ITEMCOUNT_NOTAPPLICABLE__ | C__JOB_DAYS_VALUE_NOTAPPLICABLE__);
define('JOBSITE_BASE_XMLRSS_FLAGS', C__SEARCH_RESULTS_TYPE_XML__| C__JOB_PAGECOUNT_NOTAPPLICABLE__ | C__JOB_ITEMCOUNT_NOTAPPLICABLE__);


setupPlugins();

function setupPlugins()
{

    $classList = get_declared_classes();
    print('Getting job site plugin list...'. PHP_EOL);
    foreach($classList as $class)
    {
        if(preg_match('/^Plugin/', $class) > 0)
        {

            $classinst = new $class(null, null);
            $GLOBALS['DATA']['site_plugins'][strtolower($classinst->getName())] = array('name'=>strtolower($classinst->getName()), 'class_name' => $class, 'include_in_run' => false );
            print('Added job site plugin for '. $classinst->getName() . '.' . PHP_EOL);
            $classinst=null;
        }
    }
}

?>