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
require_once dirname(dirname(__FILE__))."/bootstrap.php";

class PluginSmashingMagazine extends ClassClientHTMLJobSitePlugin
{
    protected $siteName = 'SmashingMagazine';
    protected $childSiteURLBase = 'http://jobs.smashingmagazine.com';
    protected $childSiteListingPage = 'http://jobs.smashingmagazine.com';
    protected $paginationType = C__PAGINATION_NONE;

    protected $arrListingTagSetup = array(
        'tag_listings_section' => array(array('tag' => 'ul', 'attribute' => 'class', 'attribute_value' =>'entry-list compact'), array('tag' => 'li')),
        'tag_title' => array('tag' => 'h2'),
        'tag_link' =>  array(array('tag' => 'a'), 'return_attribute' => 'href'),
        'tag_job_id' =>  array(array('tag' => 'article'), 'return_attribute' => 'id'),
        'tag_company' => array('tag' => 'span', 'attribute' => 'class', 'attribute_value' =>'entry-company'),
        );

}
