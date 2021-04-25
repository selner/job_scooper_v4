<?php

namespace Jobscooper\BasePlugin;

use const Jobscooper\C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__;

abstract class BaseServerHTMLJobSitePlugin extends AbstractBaseJobsPlugin
{
    function __construct($strBaseDir = null)
    {
        $this->pluginResultsType = C__JOB_SEARCH_RESULTS_TYPE_SERVERSIDE_WEBPAGE__;

        parent::__construct($strBaseDir);

    }

}