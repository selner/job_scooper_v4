<?php

namespace Jobscooper\BasePlugin;

use const Jobscooper\C__JOB_PAGECOUNT_NOTAPPLICABLE;
use const Jobscooper\C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__;

abstract class BaseJobsAPIPlugin extends AbstractBaseJobsPlugin
{
    function __construct($strBaseDir = null)
    {
        $this->additionalFlags[] = C__JOB_PAGECOUNT_NOTAPPLICABLE;
        $this->pluginResultsType = C__JOB_SEARCH_RESULTS_TYPE_JOBSAPI__;

        parent::__construct($strBaseDir);

    }
}