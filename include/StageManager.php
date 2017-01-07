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
require_once(__ROOT__.'/include/SitePlugins.php');
require_once(__ROOT__.'/include/ClassMultiSiteSearch.php');
require_once(__ROOT__.'/include/S3Manager.php');
require_once(__ROOT__.'/include/ClassJobsNotifier.php');
require_once(__ROOT__.'/include/JobsAutoMarker.php');

const JOBLIST_TYPE_UNFILTERED = "unfiltered";
const JOBLIST_TYPE_MARKED = "marked";
const JSON_FILENAME = "-alljobsites.json";


class S3JobListManager extends ClassJobsSiteCommon
{
    protected $arrLatestJobs_UnfilteredByUserInput = array();
    protected $arrMarkedJobs = array();
    protected $s3Manager = null;
    protected $logger = null;

    function __construct($logger)
    {
        parent::__construct(null);

        if ($logger)
            $this->logger = $logger;
        elseif($GLOBALS['logger'])
            $this->logger = $GLOBALS['logger'];
        else
            $this->logger = new \Scooper\ScooperLogger($GLOBALS['USERDATA']['directories']['stage1'] );

        $this->s3Manager = new S3Manager($GLOBALS['USERDATA']['AWS']['S3']['bucket'], $GLOBALS['USERDATA']['AWS']['S3']['region']);
    }

    public function publishS3JobsList($stageNumber, $listType)
    {

        try {
            $data = $this->_getJobsListDataForS3_($stageNumber, $listType);
            if (is_null($data)) return null;
            $keyparts = explode("/", $data['key']);

            $path = implode("/", array_slice($keyparts,2));
            writeJobsListDataToLocalJSONFile($path, $data['jobslist'], $listType, $stageNumber, $searchDetails=null);
            if($this->s3Manager->isConnected() === true)
            {

                $this->s3Manager->uploadObject($data['key'], $data['jobslist']);
            }

            return $data['key'];

        } catch (Exception $e) {
            $msg = "Failed to load " . $listType . " joblist to S3:  " . $e->getMessage();
            $this->logger->logLine($msg);
            throw new Exception($msg);
        }
    }

    public function getS3JobsList($stageNumber, $listType)
    {

        try {
            $data = $this->_getJobsListDataForS3_($stageNumber, $listType);
            if (is_null($data)) return null;
            return $this->s3Manager->getObject($data['key']);


        } catch (Exception $e) {
            $msg = "Warning:  could not find object for " . $listType . " joblist in S3:  " . $e->getMessage();
            $this->logger->logLine($msg, \Scooper\C__DISPLAY_WARNING__);
            return array();
        }
    }

    public function migrateAndLoadS3JobListsIntoStage($thisStageNumber)
    {
        $this->migrateAndLoadS3JobListForStage($thisStageNumber, JOBLIST_TYPE_UNFILTERED);
        $this->migrateAndLoadS3JobListForStage($thisStageNumber, JOBLIST_TYPE_MARKED);
    }

    public function migrateAndLoadS3JobListForStage($thisStageNumber, $listType)
    {
        $prevStageNumber = \Scooper\intceil($thisStageNumber)-1;
        $arrRetJobs = array();
        $result = $this->getS3JobsList($thisStageNumber, $listType);

        if (!is_null($result) && is_array($result) && array_key_exists('BodyDecoded', $result))
        {
            $arrJobsCurrStage = $result['BodyDecoded'];
        }
        else
        {
            $data = $this->_getJobsListDataForS3_($thisStageNumber, $listType);
            $keyparts = explode("/", $data['key']);
            $path = implode("/", array_slice($keyparts,2));
            $arrJobsCurrStage = readJobsListFromLocalJsonFile($path, $thisStageNumber);
        }
        if(countJobRecords($arrJobsCurrStage) > 0)
        {
            addJobsToJobsList($arrRetJobs, $arrJobsCurrStage);
        }

        if($prevStageNumber > 0)
        {
            $result = $this->getS3JobsList($prevStageNumber, $listType);
            if (!is_null($result) && is_array($result) && array_key_exists('BodyDecoded', $result))
            {
                $arrJobsPrevStage = $result['BodyDecoded'];
            }
            else
            {
                $data = $this->_getJobsListDataForS3_($prevStageNumber, $listType);
                $keyparts = explode("/", $data['key']);
                $path = implode("/", array_slice($keyparts,2));
                $arrJobsPrevStage = readJobsListFromLocalJsonFile($path, $prevStageNumber);
            }
            if(countJobRecords($arrJobsPrevStage) > 0)
            {
                addJobsToJobsList($arrRetJobs, $arrJobsPrevStage);
            }
        }

        switch($listType)
        {
            case JOBLIST_TYPE_UNFILTERED:
                $this->arrLatestJobs_UnfilteredByUserInput = $arrRetJobs;
                break;
            case JOBLIST_TYPE_MARKED:
                $this->arrMarkedJobs = $arrRetJobs;
                break;

            default:
                throw new Exception("Invalid job list type specified: " . $listType);
        }

        writeJobsListDataToLocalJSONFile(("migrated-to-stage".$thisStageNumber), $dataJobs=$arrRetJobs, $listType, $stageNumber=$thisStageNumber, $searchDetails=null);
        if($this->s3Manager->isConnected() === true)
        {
            $this->publishS3JobsList($thisStageNumber, $listType);
            if($prevStageNumber > 0)
                $this->deleteS3JobsList($prevStageNumber, $listType);
        }
    }

    public function deleteS3JobsList($stageNumber, $listType)
    {
        $key = "unknown";
        try {
            $data = $this->_getJobsListDataForS3_($stageNumber, $listType);
            if (is_null($data)) return null;

            $key = $data['key'];
            $this->s3Manager->deleteObjects($key);

        } catch (Exception $e) {
            $msg = "Warning:  failed to delete object " . $key . " for ". $listType . " joblist in S3:  " . $e->getMessage();
            $this->logger->logLine($msg, \Scooper\C__DISPLAY_WARNING__);
        }

    }


    public function getS3KeyForStage($stageNumber, $listType)
    {
        $result = $this->_getJobsListDataForS3_($stageNumber, $listType);
        if (array_key_exists('key', $result) == true)
            return $result['key'];
        return null;
    }

    private function _getJobsListDataForS3_($stageNumber, $listType)
    {
        $jobList = null;

        switch($listType)
        {
            case JOBLIST_TYPE_UNFILTERED:
                $jobList = $this->arrLatestJobs_UnfilteredByUserInput;
                $keyEnding = JOBLIST_TYPE_UNFILTERED . JSON_FILENAME;
                break;
            case JOBLIST_TYPE_MARKED:
                $jobList = $this->arrMarkedJobs;
                $keyEnding = JOBLIST_TYPE_MARKED . JSON_FILENAME;
                break;

            default:
                throw new Exception("Invalid job list type specified: " . $listType);
        }

        $key = getStageKeyPrefix($stageNumber, STAGE_FLAG_INCLUDEDATE , "") . "-" . $keyEnding;
        $data = array(
            'key' => $key,
            'type' => $listType,
            'jobslist' =>  $jobList
        );

        return $data;
    }


}

class StageManager extends S3JobListManager
{
    protected $siteName = "StageManager";
    protected $classConfig = null;

    function __construct()
    {
        $this->classConfig = new ClassConfig();
        $this->classConfig->initialize();
        $logger = $this->classConfig->getLogger();

        parent::__construct($logger);
    }

    function __destruct()
    {
        if(isset($GLOBALS['logger'])) $this->logger->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__);

    }


    public function runAll()
    {
        $arrRunStages = explode(",", \Scooper\get_PharseOptionValue("stages"));
        if(is_array($arrRunStages) && count($arrRunStages) >= 1 && strlen($arrRunStages[0]) > 0)
        {
            foreach($arrRunStages as $stage)
            {
                $stageFunc = "doStage" . $stage;
                try
                {
                    call_user_func(array($this, $stageFunc));
                }
                catch (Exception $ex)
                {
                    throw new Exception("Error:  failed to call method \$this->".$stageFunc."() for " . $stage . " from option --stages ". join(",", $arrRunStages) .".  Error: " . $ex);
                }
            }
        }
        else
        {
            $this->doStage1();
            $this->doStage2();
            $this->doStage3();
            $this->doStage4();
        }
    }


    public function doStage1()
    {

        if(isset($GLOBALS['logger'])) $this->logger->logLine("Stage 1: Downloading Latest Matching Jobs ", \Scooper\C__DISPLAY_SECTION_START__);


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Load the jobs list we need to process in this stage
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $this->migrateAndLoadS3JobListsIntoStage(1);

        //
        // let's start with the searches specified with the details in the the config.ini
        //
        $arrSearchesToRun = $this->classConfig->getSearchConfiguration('searches');

        if(isset($arrSearchesToRun))
        {
            if(count($arrSearchesToRun) > 0)
            {

                //
                // Remove any sites that were excluded in this run from the searches list
                //
                foreach(array_keys($arrSearchesToRun) as $z)
                {
                    $curSearch = $arrSearchesToRun[$z];

                    $strIncludeKey = 'include_'.$curSearch['site_name'];

                    $valInclude = \Scooper\get_PharseOptionValue($strIncludeKey);

                    if(!isset($valInclude) || $valInclude == 0)
                    {
                        $this->logger->logLine($curSearch['site_name'] . " excluded, so dropping its searches from the run.", \Scooper\C__DISPLAY_ITEM_START__);
                        unset($arrSearchesToRun[$z]);
                    }
                }
            }

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //
            // OK, now we have our list of searches & sites we are going to actually run
            // Let's go get the jobs for those searches
            //
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            if ($arrSearchesToRun != null)
            {
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                //
                // Download all the job listings for all the users searches
                //
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $this->logger->logLine(PHP_EOL."**************  Starting Run of " . count($arrSearchesToRun) . " Searches  **************  ".PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);


                //
                // the Multisite class handles the heavy lifting for us by executing all
                // the searches in the list and returning us the combined set of new jobs
                // (with the exception of Amazon for historical reasons)
                //

                $classMulti = new ClassMultiSiteSearch($this->classConfig->getFileDetails('output_subfolder')['directory']);
                $classMulti->addMultipleSearches($arrSearchesToRun, null);
                $arrUpdatedJobs = $classMulti->updateJobsForAllPlugins();
                $this->arrLatestJobs_UnfilteredByUserInput = \Scooper\array_copy($arrUpdatedJobs);
                $this->publishS3JobsList(1, JOBLIST_TYPE_UNFILTERED);

                if(isDebug() === true) {

                    //
                    // Let's save off the unfiltered jobs list in case we need it later.  The $this->arrLatestJobs
                    // will shortly have the user's input jobs applied to it
                    //
                    $strRawJobsListOutput = \Scooper\getFullPathFromFileDetails($this->classConfig->getFileDetails('output_subfolder'), "", "_rawjobslist_preuser_filtering");
                    $this->writeRunsJobsToFile($strRawJobsListOutput, $this->arrLatestJobs_UnfilteredByUserInput, "RawJobsList_PreUserDataFiltering");

                    $this->logger->logLine(count($this->arrLatestJobs_UnfilteredByUserInput). " raw, latest job listings from " . count($arrSearchesToRun) . " search(es) downloaded to " . $strRawJobsListOutput, \Scooper\C__DISPLAY_SUMMARY__);
                }

                // Process the search results and send an error alert for any that failed unexpectedly

                $notifier = new ClassJobsNotifier($this->arrLatestJobs_UnfilteredByUserInput, $this->arrMarkedJobs);
                $notifier->sendErrorEmail();

            } else {
                throw new ErrorException("No searches have been set to be run.");
            }
        }
    }

    public function doStage2()
    {

        if(isset($GLOBALS['logger'])) $this->logger->logLine("Stage 2:  Tokenizing Jobs ", \Scooper\C__DISPLAY_SECTION_START__);

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Load the jobs list we need to process in this stage
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $this->migrateAndLoadS3JobListsIntoStage(2);

        if(countAssociativeArrayValues($this->arrLatestJobs_UnfilteredByUserInput) == 0)
        {
            if(isset($GLOBALS['logger'])) $this->logger->logLine("No jobs found to process. Skipping Stage 2.", \Scooper\C__DISPLAY_WARNING__);
            return;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Tokenize the job listings found in the stage 2 prefix on S3
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $stage1Key = $this->getS3KeyForStage(1, JOBLIST_TYPE_UNFILTERED);
        $stage2Key = $this->getS3KeyForStage(2, JOBLIST_TYPE_UNFILTERED);

        $PYTHONPATH = realpath(__DIR__ ."/../python/pyJobNormalizer/normalizeS3JobListings.py");
        $sourceparam = "";
        $bucketparam = "";

        if(array_key_exists('AWS', $GLOBALS['USERDATA']) && array_key_exists('S3', $GLOBALS['USERDATA']['AWS']) && array_key_exists('bucket', $GLOBALS['USERDATA']['AWS']['S3']) && strlen($GLOBALS['USERDATA']['AWS']['S3']['bucket'])>0)
        {
            $sourceparam = "--source s3";
            $bucketparam = "-b " . $GLOBALS['USERDATA']['AWS']['S3']['bucket'];
        }
        else
        {
            $sourceparam = "--source " . escapeshellarg($GLOBALS['OPTS']['output']);
        }
        $cmd = "python " . $PYTHONPATH . " ". $bucketparam . " " . $sourceparam . " --inkey " . escapeshellarg($stage1Key) . " --outkey " . escapeshellarg($stage2Key) . " --column job_title --index key_jobsite_siteid";
        $this->logger->logLine("Running command: " . $cmd   , \Scooper\C__DISPLAY_ITEM_DETAIL__);

        doExec($cmd);

//        $this->logger->logLine("Loading results and saving for next stage...", \Scooper\C__DISPLAY_SECTION_START__);
//        $keyparts = explode("/", $stage2Key);
//        $path = implode("/", array_slice($keyparts,2));
//
//        $this->arrLatestJobs_UnfilteredByUserInput = readJobsListFromLocalJsonFile($path, 2);
//        $this->publishS3JobsList(2, JOBLIST_TYPE_UNFILTERED);

    }

    public function doStage3()
    {

        $this->logger->logLine("Stage 3:  Automarking Jobs ", \Scooper\C__DISPLAY_SECTION_START__);


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Load the jobs list we need to process in this stage
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $this->migrateAndLoadS3JobListsIntoStage(3);


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if (countJobRecords($this->arrLatestJobs_UnfilteredByUserInput) == 0)
        {
            $this->logger->logLine("No jobs were loaded to auto-mark.  Skipping Stage 3.", \Scooper\C__DISPLAY_WARNING__);
            return;
        }

        $this->logger->logLine(PHP_EOL . "**************  Updating jobs list for known filters ***************" . PHP_EOL, \Scooper\C__DISPLAY_NORMAL__);
        $marker = new JobsAutoMarker($this->arrLatestJobs_UnfilteredByUserInput);
        $marker->markJobsList();
        $this->arrMarkedJobs = $marker->getMarkedJobs();

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Save the automarked jobs back to stage 4 on S3
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $this->publishS3JobsList(3, JOBLIST_TYPE_MARKED);
    }

    public function doStage4()
    {
        $this->migrateAndLoadS3JobListsIntoStage(4);
        $this->logger->logLine("Stage 4: Notifying User", \Scooper\C__DISPLAY_SECTION_START__);

        if ((countJobRecords($this->arrMarkedJobs) + countJobRecords($this->arrLatestJobs_UnfilteredByUserInput)) == 0)
        {
            $this->logger->logLine("No jobs were loaded for notification. Skipping Stage 4." , \Scooper\C__DISPLAY_WARNING__);
            return;
        }

        $notifier = new ClassJobsNotifier($this->arrLatestJobs_UnfilteredByUserInput, $this->arrMarkedJobs);
        $notifier->processNotifications();
    }




} 