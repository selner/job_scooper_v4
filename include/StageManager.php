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
        if ($logger)
            $this->logger = $logger;
        elseif($GLOBALS['logger'])
            $this->logger = $GLOBALS['logger'];
        else
            $this->logger = new \Scooper\ScooperLogger($GLOBALS['USERDATA']['directories']['debug'] );

        $this->s3Manager = new S3Manager($GLOBALS['USERDATA']['AWS']['S3']['bucket'], $GLOBALS['USERDATA']['AWS']['S3']['region']);
    }

    public function publishS3JobsList($stageNumber, $listType)
    {

        try {
            $data = $this->_getJobsListDataForS3_($stageNumber, $listType);
            $this->s3Manager->uploadObject($data['key'], $data['joblistings']);
            $keyparts = explode("/", $data['key']);
//            if($GLOBALS['OPTS']['DEBUG'] == true)
//            {
                writeJobsListDataToLocalJSONFile($keyparts[count($keyparts)-1], $data['joblistings'], $listType, $stageNumber);
//            }

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
        $arrJobsCurrStage = $result['BodyDecoded'];
        if(countJobRecords($arrJobsCurrStage) > 0)
        {
            addJobsToJobsList($arrRetJobs, $arrJobsCurrStage);
        }
        if($prevStageNumber > 0)
        {
            $result = $this->getS3JobsList($prevStageNumber, $listType);
            $arrJobsPrevStage = $result['BodyDecoded'];
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

        writeJobsListDataToLocalJSONFile($fileKey="migratedFromPrevStageTo". $thisStageNumber, $dataJobs=$arrRetJobs, $listType, $stageNumber=3);
        $this->publishS3JobsList($thisStageNumber, $listType);
        $this->deleteS3JobsList($prevStageNumber, $listType);
    }

    public function deleteS3JobsList($stageNumber, $listType)
    {
        $key = "unknown";
        try {
            $data = $this->_getJobsListDataForS3_($stageNumber, $listType);
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
            'joblistings' =>  $jobList
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
        $this->logger->logLine("Closing ".$this->siteName." instance of class " . get_class($this), \Scooper\C__DISPLAY_ITEM_START__); 

    }


    public function runAll()
    {
        $arrRunStages = explode(",", \Scooper\get_PharseOptionValue("stages"));
        if(is_array($arrRunStages) && count($arrRunStages) >= 1)
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
            $this->doStage5();
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
//                for($z = 0; $z < count($arrSearchesToRun) ; $z++)
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

                if($this->is_OutputInterimFiles() == true) {

                    //
                    // Let's save off the unfiltered jobs list in case we need it later.  The $this->arrLatestJobs
                    // will shortly have the user's input jobs applied to it
                    //
                    $strRawJobsListOutput = \Scooper\getFullPathFromFileDetails($this->classConfig->getFileDetails('output_subfolder'), "", "_rawjobslist_preuser_filtering");
                    $this->writeRunsJobsToFile($strRawJobsListOutput, $this->arrLatestJobs_UnfilteredByUserInput, "RawJobsList_PreUserDataFiltering");

                    $this->logger->logLine(count($this->arrLatestJobs_UnfilteredByUserInput). " raw, latest job listings from " . count($arrSearchesToRun) . " search(es) downloaded to " . $strRawJobsListOutput, \Scooper\C__DISPLAY_SUMMARY__);
                }
            } else {
                throw new ErrorException("No searches have been set to be run.");
            }

            // TODO:  Remove this local copy when we refactor the output section later
            $GLOBALS['USERDATA']['searches_for_run'] = \Scooper\array_copy($arrSearchesToRun);
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


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Tokenize the job listings found in the stage 2 prefix on S3
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $stage1Key = $this->getS3KeyForStage(1, JOBLIST_TYPE_UNFILTERED);
        $stage2Key = $this->getS3KeyForStage(2, JOBLIST_TYPE_UNFILTERED);

        $PYTHONPATH = realpath(__DIR__ ."/../python/pyJobNormalizer/normalizeS3JobListings.py");
        $cmd = "python " . $PYTHONPATH . " -b " . $GLOBALS['USERDATA']['AWS']['S3']['bucket'] . " --inkey " . escapeshellarg($stage1Key) . " --outkey " . escapeshellarg($stage2Key) . " --column job_title --index key_jobsite_siteid";
        $this->logger->logLine("Running command: " . $cmd   , \Scooper\C__DISPLAY_ITEM_DETAIL__);

        $cmdOutput = array();
        $cmdRet = "";
        exec($cmd, $cmdOutput, $cmdRet);
        foreach($cmdOutput as $resultLine)
            $this->logger->logLine($resultLine, \Scooper\C__DISPLAY_ITEM_DETAIL__);
    }
//
//    public function doStage3()
//    {
//        if(isset($GLOBALS['logger'])) $this->logger->logLine("Stage 3:  Tokenizing Jobs ", \Scooper\C__DISPLAY_SECTION_START__);
//        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//        //
//        // Load the jobs list we need to process in this stage
//        //
//        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//        $this->migrateAndLoadS3JobListForStage(3, JOBLIST_TYPE_UNFILTERED);
//
//        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//        //
//        // Download the stage 2 job listings from S3 to a local files, aggregate them and re-load them to S3
//        //
//        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//        $this->arrLatestJobs_UnfilteredByUserInput = null;
//
//        $details = \Scooper\getFilePathDetailsFromString($GLOBALS['USERDATA']['directories']['stage2'], \Scooper\C__FILEPATH_CREATE_DIRECTORY_PATH_IF_NEEDED);
//        $prefix = getStageKeyPrefix(2, STAGE_FLAG_INCLUDEDATE);
//        $this->s3Manager->downloadObjectsToFile($prefix, $details['directory']);
//
//        $filesToLoad = array_filter(scandir($details['directory']), function($file) { return (strcasecmp(substr($file, strlen($file)-5, 5), ".json") == 0); });
//        foreach($filesToLoad as $file)
//        {
//            $fileFullPath = $details['directory'] . DIRECTORY_SEPARATOR . $file;
//            $jsonText = file_get_contents($fileFullPath, FILE_TEXT);
//            $arrJobs = json_decode($jsonText, true, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
//            addJobsToJobsList($this->arrLatestJobs_UnfilteredByUserInput, $arrJobs);
//        }
//        $this->publishS3JobsList(2, JOBLIST_TYPE_UNFILTERED);
//
//        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//        //
//        // Publish the aggregated job list file back to S3 so its there for the next stage.
//        // Remove the previous stage's processed file.
//        //
//        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    }

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
            $this->logger->logLine("No jobs were loaded to auto-mark." . PHP_EOL, \Scooper\C__DISPLAY_WARNING__);
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
        $notifier = new ClassJobsNotifier($this->arrLatestJobs_UnfilteredByUserInput, $this->arrMarkedJobs);
        $notifier->processNotifications();
    }

} 