<?php
/**
 * Copyright 2014-16 Bryan Selner
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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__ . '/include/ClassJobsSiteCommon.php');




class PluginMediaBistro extends ClassBaseMicroDataPlugin
{
    protected $siteName = 'MediaBistro';
    protected $childSiteURLBase = 'https://www.mediabistro.com';
    protected $childSiteListingPage = 'https://www.mediabistro.com/jobs/openings/';

}


//class PluginPacMed extends ClassBaseMicroDataPlugin
//{
//    protected $siteName = 'PacMed';
//    protected $childSiteURLBase = 'http://pacmed.jobs/jobs/';
//    protected $childSiteListingPage = 'http://pacmed.jobs/jobs';
//}
//
//
//class PluginJobsArkansas extends ClassBaseMicroDataPlugin
//{
//    protected $siteName = 'jobsarkansas';
//    protected $childSiteURLBase = 'http://www.jobsarkansas.com';
//    protected $childSiteListingPage = 'http://www.jobsarkansas.com/Jobs?source=3&countryId=3';
//}



?>

