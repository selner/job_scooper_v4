<?php

/**
 * Copyright 2014-18 Bryan Selner
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



class PluginPersonForce extends \JobScooper\SitePlugins\AjaxSitePlugin
{
    protected $JobSiteName = 'PersonForce';
    protected $JobPostingBaseUrl = 'http://www.personforce.com';
    protected $SearchUrlFormat = 'https://www.personforce.com/jobs/';
    protected $LocationType = 'location-city-comma-statecode';
    protected $JobListingsPerPage = 6;
    protected $additionalLoadDelaySeconds = 1;
    protected $PaginationType = C__PAGINATION_PAGE_VIA_NEXTBUTTON;
    protected $additionalBitFlags = [C__JOB_KEYWORD_URL_PARAMETER_NOT_SUPPORTED, C__JOB_LOCATION_URL_PARAMETER_NOT_SUPPORTED];


    protected $arrListingTagSetup = array(
    'JobPostItem' => ['selector' => '#listdata .display-box.row'],
    'Title' => ['selector' => 'div.jobTitle h5 a b', 'return_attribute' => 'text'],
    'Url' => ['selector' => 'div.jobTitle h5 a', 'return_attribute' => 'href'],
    'Company' => ['selector' => 'div.jobTitle2 b', 'return_attribute' => 'text'],
    'Category' => ['selector' => 'div.jobTitle4'],
    'DatePosted' => ['selector' => 'div.jobTitle3'],
    'Location' => ['selector' => ' span.address', 'return_attribute' => 'text', 'return_value_regex' => '/\(([^\]+)\)/'],
    'NextButton' => ['selector' => 'ul.pagination li:last-child a', 'index' => 0],
    'JobSitePostId' =>  ['selector' => 'div.jobTitle h5 a', 'return_attribute' => 'href', 'return_value_regex' => '/.*jobs\/(\d+)\/.*?/']
    );

}
