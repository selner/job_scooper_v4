<?php

/**
 * Copyright 2014-17 Bryan Selner
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


abstract class AbstractResumator extends \JobScooper\Plugins\lib\ServerHtmlSimplePlugin
{
    protected $arrListingTagSetup = array(
        'JobPostItem' => array(array('tag' => 'div', 'attribute' => 'id', 'attribute_value' =>'resumator-content-left-wrapper'), array('tag' => 'div', 'attribute' => 'class', 'attribute_value' => 'resumator-job')),
        'Title' => array('tag' => 'a', 'attribute' => 'class', 'attribute_value' =>'resumator-job-title-link'),
        'Url' => array('tag' => 'a', 'attribute' => 'class', 'attribute_value' =>'resumator-job-title-link'),
        'Department' => array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'resumator-job-info'),
        'Location' => null,
        'regex_link_job_id' => '/.com\/apply\/(\S*)\//i',
    );
}



class PluginAtlanticMedia extends AbstractResumator
{
    protected $siteName = 'AtlanticMedia';
    protected $childSiteURLBase = 'http://atlanticmedia.theresumator.com/';
    protected $childSiteListingPage = 'http://atlanticmedia.theresumator.com/';
}


class PluginMashableCorporate extends AbstractResumator
{
    protected $siteName = 'MashableCorporate';
    protected $childSiteURLBase = 'http://mashable.theresumator.com/';
    protected $childSiteListingPage = 'http://mashable.theresumator.com/';

    protected $arrListingTagSetup = array(
        'JobPostItem' => array(array('tag' => 'ul', 'attribute' => 'class', 'attribute_value' =>'list-group'), array('tag' => 'li', 'attribute' => 'class', 'attribute_value' => 'list-group-item')),
        'Title' => array(array('tag' => 'h4', 'attribute' => 'class', 'attribute_value' =>'list-group-item-heading'), array('tag' => 'a')),
        'Url' => array(array('tag' => 'h4', 'attribute' => 'class', 'attribute_value' =>'list-group-item-heading'), array('tag' => 'a')),
        'Department' => array(array('tag' => 'ul' ), array('tag' => 'li', 'index' => 0)),
        'Location' => array(array('tag' => 'ul' ), array('tag' => 'li', 'index' => 1)),
        'regex_link_job_id' => '/.com\/apply\/(\S*)\//i',
    );
}







