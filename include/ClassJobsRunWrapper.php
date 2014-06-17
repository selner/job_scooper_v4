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
require_once(__ROOT__.'/include/SitePlugins.php');
require_once(__ROOT__.'/include/ClassMultiSiteSearch.php');

class ClassJobsRunWrapper extends ClassJobsSitePlugin
{
    protected $configSettings = null;
    protected $arrUserInputJobs_Active = null;
    protected $arrUserInputJobs_Inactive = null;
    protected $arrJobCSVUserInputFiles = null;
    protected $nNumDaysToSearch = -1;
    protected $detailsOutputSubfolder = null;
    protected $detailsIniFile = null;
    protected $detailsOutputFile = null;
    protected $arrEmailAddresses = null;
    protected $arrUserInputFiles = null;


    function ClassJobsRunWrapper()
    {

        $this->siteName = "JobsRunner";
        $this->arrLatestJobs_FilteredByUserInput = null;
        $this->arrLatestJobs_UnfilteredByUserInput = null;
        __initializeArgs__();

        $this->__setupRunFromArgs__();
    }

    private function __setupRunFromArgs__()
    {
        # After you've configured Pharse, run it like so:
        $GLOBALS['OPTS'] = Pharse::options($GLOBALS['OPTS_SETTINGS']);

        $GLOBALS['DATA']['titles_to_filter'] = null;
        $GLOBALS['DATA']['titles_regex_to_filter'] = null;
        $GLOBALS['DATA']['companies_regex_to_filter'] = null;

        // These will be used at the beginning and end of
        // job processing to filter out jobs we'd previous seen
        // and to make sure our notes get updated on active jobs
        // that we'd seen previously
        //
//    $GLOBALS['DATA']['active_jobs_from_input_source_files'] = null;
//    $GLOBALS['DATA']['inactive_jobs_from_input_source_files'] = null;

        // Now go see what we got back for each of the sites
        //
        foreach($GLOBALS['DATA']['site_plugins']  as $site)
        {
            $fIsIncludedInRun = is_IncludeSite($site['name']);
            $GLOBALS['DATA']['site_plugins'][$site['name']]['include_in_run'] = $fIsIncludedInRun;
        }

        $GLOBALS['OPTS']['DEBUG'] = get_PharseOptionValue('use_debug');
        $GLOBALS['OPTS']['VERBOSE'] = $GLOBALS['OPTS']['DEBUG'];

        if($GLOBALS['OPTS']['use_config_ini_given'])
        {
//            throw new ErrorException("Config ini files not yet supported!");
            $this->detailsIniFile = set_FileDetails_fromPharseSetting("use_config_ini", 'config_file_details', true);

            __debug__printLine("Parsing ini file ".$this->detailsIniFile['full_file_path']."...", C__DISPLAY_ITEM_START__);

            $iniParser = new IniParser($this->detailsIniFile['full_file_path']);
            $confTemp = $iniParser->parse();
            $this->_setupRunFromConfig_($confTemp);
        }

        $nDays = get_PharseOptionValue('number_days');
        if($nDays == false) { $GLOBALS['OPTS']['number_days'] = 1; }


        // Override any INI file setting with the command line output file path & name
        // the user specificed (if they did)
        $userOutfileDetails = get_FileDetails_fromPharseOption("output_file", true);
        if($userOutfileDetails['full_file_path'] != '')
        {
            $this->detailsOutputFile = $userOutfileDetails;
        }


    }


    function __destruct()
    {
        __debug__printLine("Closing ".$this->siteName." instance of class " . get_class($this), C__DISPLAY_ITEM_START__);
    }

    function getMyOutputFileFullPath($strFilePrefix = "")
    {
        return parent::getOutputFileFullPath($this->siteName . "_" . $strFilePrefix, "jobs", "csv");
    }

    private function __setupOutputFolders__()
    {
        if($this->detailsOutputFile['directory'] == null || $this->detailsOutputFile['directory']== "")
        {
            throw new ErrorException("Required value for the output folder was not specified. Exiting.");
        }

        if($this->detailsOutputFile['file_name'] == null || $this->detailsOutputFile['full_file_path'] == "")
        {
            $fileName = getDefaultJobsOutputFileName("", "jobs", "csv");
            $this->detailsOutputFile = parseFilePath($this->detailsOutputFile['directory'] . $fileName);
        }
        $this->detailsOutputSubfolder = $this->createOutputSubFolder($this->detailsOutputFile);
    }

    private function _setupRunFromConfig_($config)
    {
        __debug__printLine("Reading configuration options from ".$GLOBALS['OPTS']['config_file_details']['file_full_path']."...", C__DISPLAY_ITEM_START__);
        if($config->output)
        {
             if($config->output->folder)
            {
                $this->detailsOutputFile = parseFilePath($config->output->folder);
                $this->__setupOutputFolders__();
            }

        }

        if($config->emails )
        {
            foreach($config->emails as $emailItem)
            {
                $tempEmail = $this->__getEmptyEmailRecord__();
                $tempEmail['name'] = $emailItem['name'];
                $tempEmail['address'] = $emailItem['address'];
                $tempEmail['type'] = $emailItem['type'];
                $this->arrEmailAddresses[] = $tempEmail;
            }
        }

        $pathInput = "";
        if($config->input && $config->input->folder)
        {
            $pathInput = parseFilePath($config->input->folder);
        }

        if($config->inputfiles)
        {
            foreach($config->inputfiles as $iniInputFile)
            {
                $tempFileDetails = parseFilePath($pathInput['directory'].$iniInputFile['name'], true);

                __debug__printLine("Processing input file '" . $pathInput['directory'].$iniInputFile['name'] . "' with type of '". $iniInputFile['type'] . "'...", C__DISPLAY_NORMAL__);
                $this->__addInputFile__($tempFileDetails, $iniInputFile['type'], $iniInputFile['sheet']);

                switch($iniInputFile['type'])
                {
                    case "jobs":
                        $this->arrJobCSVUserInputFiles[] = $pathInput['directory'] . $iniInputFile['name'];
                        break;

                    case "titles_filter":
                        setGlobalFileDetails('titles_file_details', true, $pathInput['directory']. $iniInputFile['name']);
                        break;

                    case "regex_filter_titles":
                        setGlobalFileDetails('titles_regex_file_details', true, $pathInput['directory']. $iniInputFile['name']);
                        break;

                    case "regex_filter_companies":
                        setGlobalFileDetails('companies_regex_file_details', true, $pathInput['directory']. $iniInputFile['name']);
                        break;


                }

            }
        }


        $this->__getSearchesFromConfig__($config);

        __debug__printLine("Completed loading configuration from INI file:  ".var_export($GLOBALS['OPTS'], true), C__DISPLAY_SUMMARY__);

    }

    private function __getSearchesFromConfig__($config)
    {
        __debug__printLine("Loading searches from config file.", C__DISPLAY_ITEM_START__);
        if(!$config) throw new ErrorException("Invalid configuration.  Cannot load user's searches.");

        if($config->search)
        {
            foreach($config->search as $iniSearch)
            {
                $tempSearch =  array('site_name' => null, 'search_name' => null, 'base_url_format' => null);
                $tempSearch['search_key'] = $iniSearch['key'];
                $tempSearch['site_name'] = $iniSearch['jobsite'];
                $tempSearch['search_name'] = $iniSearch['name'];
                $tempSearch['base_url_format'] = $iniSearch['url_format'];

                if($tempSearch['search_key'] == "")
                {
                    $tempSearch['search_key'] = strScrub($tempSearch['site_name'], FOR_LOOKUP_VALUE_MATCHING) . "-" . strScrub($tempSearch['search_name'], FOR_LOOKUP_VALUE_MATCHING);
                }
                __debug__printLine("Search added [search_name=" . $tempSearch['search_name'] . "; site_name=" . $tempSearch['site_name'] . "; search_key=" . $tempSearch['search_key'] . "]; base_url_format='" . $tempSearch['base_url_format'] . "']", C__DISPLAY_ITEM_DETAIL__);
                $this->arrSearchesToReturn[] = $tempSearch;

            }
        }

        __debug__printLine("Loaded " . count($this->arrSearchesToReturn) . " searches. ", C__DISPLAY_ITEM_RESULT__);

    }
    private function createOutputSubFolder($fileDetails)
    {


        // Append the file name base to the directory as a new subdirectory for output
        $fullNewDirectory = $fileDetails['directory'] . $fileDetails['file_name_base'];
        __debug__printLine("Attempting to create output directory: " . $fullNewDirectory , C__DISPLAY_ITEM_START__);
        if(is_dir($fullNewDirectory))
        {

        }
        else
        {
            if (!mkdir($fullNewDirectory, 0777, true))
            {
                throw new ErrorException('Failed to create the output folder: '.$fullNewDirectory);
            }
         }
        $fullNewFilePath = $fullNewDirectory . '/' . getFileNameFromFileDetails($fileDetails);
        __debug__printLine("Create folder for results output: " . $fullNewFilePath , C__DISPLAY_SUMMARY__);

        // return the new file & path details
        return parseFilePath($fullNewFilePath, false);
    }



    private function loadUserInputJobsFromCSV()
    {
        $arrAllJobsLoadedFromSrc = null;

        $arrFiles = $this->__getInputFilesByType__("jobs");
//        $arrAllJobsLoadedFromSrc = $this->loadJobsListFromCSVs($this->arrJobCSVUserInputFiles);
        $arrAllJobsLoadedFromSrc = $this->loadJobsListFromCSVs($arrFiles);
        if($arrAllJobsLoadedFromSrc )
        {
            $this->normalizeJobList($arrAllJobsLoadedFromSrc);
        }

        if($GLOBALS['OPTS']['DEBUG'] == true)
        {
            $strCSVInputJobsPath = getFullPathFromFileDetails($this->detailsOutputSubfolder, "", "_Jobs_From_UserInput");
            $this->writeJobsListToFile($strCSVInputJobsPath, $arrAllJobsLoadedFromSrc, true, false, "ClassJobRunner-LoadCSVs");
        }

        // These will be used at the beginning and end of
        // job processing to filter out jobs we'd previous seen
        // and to make sure our notes get updated on active jobs
        // that we'd seen previously
        //
        //
        // Set a global var with an array of all input cSV jobs marked new or not marked as excluded (aka "Yes" or "Maybe")
        //
        $this->arrUserInputJobs_Active = array_filter($arrAllJobsLoadedFromSrc, "isMarked_InterestedOrBlank");
        __debug__printLine(count($this->arrUserInputJobs_Active). " active job listings loaded from user input CSVs.", C__DISPLAY_SUMMARY__);

        //
        // Set a global var with an array of all input CSV jobs that are not in the first set (aka marked Not Interested & Not Blank)
        //
        $this->arrUserInputJobs_Inactive = array_filter($arrAllJobsLoadedFromSrc, "isMarked_NotInterestedAndNotBlank");
        __debug__printLine(count($this->arrUserInputJobs_Inactive). " inactive job listings loaded from user input CSVs.", C__DISPLAY_SUMMARY__);

    }

    private function writeRunsJobsToFile($strFileOut, $arrJobsToOutput, $strLogDescriptor, $strExt = "CSV", $keysToOutput = null)
    {
        $this->writeJobsListToFile($strFileOut, $arrJobsToOutput, true, false, "ClassJobRunner-".$strLogDescriptor, $strExt, $keysToOutput);

    }

    private function __getAlternateOutputFileDetails__($ext, $strNamePrepend = "", $strNameAppend = "")
    {
        $detailsRet = $this->detailsOutputSubfolder;
        $detailsRet['file_extension'] = $ext;
        $strTempPath = getFullPathFromFileDetails($detailsRet, $strNamePrepend , $strNameAppend);
        $detailsRet= parseFilePath($strTempPath, false);
        return $detailsRet;
    }

    private function outputFilteredJobsListToFile($strFilterToApply, $strFileNameAppend, $strExt = "CSV", $strFilterDescription = null, $keysToOutput = null)
    {
        if(count($this->arrLatestJobs) == 0) return null;

        $arrToOutput = null;

        if($strFilterToApply == null || $strFilterToApply == "")
        {
            $arrToOutput = $this->arrLatestJobs;
        }
        else
        {
            $arrToOutput = array_filter($this->arrLatestJobs, $strFilterToApply);
        }

        if($strFileNameAppend == null || $strFileNameAppend == "")
        {
            throw new ErrorException("Required array filter callback was not specified.  Cannot output " . $strFilterToApply . " filtered jobs list.");
        }

        $details = $this->__getAlternateOutputFileDetails__($strExt, "", $strFileNameAppend);
        $strFilteredCSVOutputPath = $details['full_file_path'];
        $this->writeRunsJobsToFile($strFilteredCSVOutputPath, $arrToOutput, $strFilterToApply, $strExt,$keysToOutput );
        __debug__printLine(($strFilterDescription != null ? $strFilterDescription : $strFileNameAppend) . " " . count($arrToOutput). " job listings output to  " . $strFilteredCSVOutputPath, C__DISPLAY_SUMMARY__);

        return $arrToOutput;
    }

    //
    // Note:  This function does not take the user's input job listings into account at all.  It
    //        returns the pure new job listings from all the specified searches
    //
    protected function getLatestRawJobsFromAllSearches()
    {
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Download all the job listings for all the users searches
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        __debug__printLine(PHP_EOL."**************  Starting Run of " . count($this->arrSearchesToReturn) . " Searches  **************  ".PHP_EOL, C__DISPLAY_NORMAL__);


        //
        // the Multisite class handles the heavy lifting for us by executing all
        // the searches in the list and returning us the combined set of new jobs
        // (with the exception of Amazon for historical reasons)
        //
        $classMulti = new ClassMultiSiteSearch($this->detailsOutputSubfolder['full_file_path']);
        $classMulti->addSearches($this->arrSearchesToReturn);
        $classMulti->downloadAllUpdatedJobs( $this->nNumDaysToSearch);
        $this->_addJobsToMyJobsList_($classMulti->getMyJobsList());

        //
        // Let's go get Amazon too if the user asked for Amazon's jobs
        //
/*        if($GLOBALS['DATA']['site_plugins']['Amazon']['include_in_run'] == true)
        {
            __debug__printLine("Adding Amazon jobs....", C__DISPLAY_ITEM_START__);
            $class = new PluginAmazon($this->detailsOutputSubfolder['full_file_path']);
            $class->downloadAllUpdatedJobs( $this->nNumDaysToSearch);
            $this->_addJobsToMyJobsList_($class->getMyJobsList());
        }
*/
        //
        // Let's save off the unfiltered jobs list in case we need it later.  The $this->arrLatestJobs
        // will shortly have the user's input jobs applied to it
        //
        addJobsToJobsList($this->arrLatestJobs_UnfilteredByUserInput, $this->arrLatestJobs);

        $strRawJobsListOutput = getFullPathFromFileDetails($this->detailsOutputSubfolder, "", "_rawjobslist_preuser_filtering");
//        $this->writeJobsListToFile($strRawJobsListOutput, $this->arrLatestJobs_UnfilteredByUserInput , true, false, "ClassJobsRunWrapper-_rawjobslist_preuser_filtering");
        $this->writeRunsJobsToFile($strRawJobsListOutput, $this->arrLatestJobs_UnfilteredByUserInput, "RawJobsList_PreUserDataFiltering");

        $detailsBodyContentFile = null;

       __debug__printLine(count($this->arrLatestJobs_UnfilteredByUserInput). " raw, latest job listings from " . count($this->arrSearchesToReturn) . " searches downloaded to " . $strRawJobsListOutput, C__DISPLAY_SUMMARY__);


    }

    function RunAll()
    {

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Process the input CSVs of job listings that the user specified.
        // The inactives get added to the full jobs list as the starting jobs
        // The actives will get added at the end so they overwrite any jobs that
        // were found again
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if($this->arrJobCSVUserInputFiles != null)
        {
            __debug__printLine(PHP_EOL."**************  Loading user-specified jobs list information from ". count($this->arrJobCSVUserInputFiles) ." CSV files **************  ".PHP_EOL, C__DISPLAY_NORMAL__);
            $this->loadUserInputJobsFromCSV();
        }


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // At this point, we have a full list of all the new jobs that have been posted within the user's search parameters
        // completely unfiltered.  Let's save that off now before we update it with the values that user passed in via CSVs.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if($this->arrSearchesToReturn != null)
        {
            __debug__printLine(PHP_EOL."************** Get the latest jobs for all searches ****************".PHP_EOL, C__DISPLAY_NORMAL__);
            $this->getLatestRawJobsFromAllSearches();
        }
        else
        {
            throw new ErrorException("No searches have been set to be run.");

        }




        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Now we can update the full jobs list with the active jobs we loaded from the CSV at the start
        // $this->arrLatestJobs_UnfilteredByUserInput is the unfiltered list;
        // $this->arrLatestJobs is the processed & filtered jobs list
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        if($this->arrUserInputJobs_Inactive != null)
        {
            $this->arrLatestJobs = null;
            addJobsToJobsList($this->arrLatestJobs, $this->arrUserInputJobs_Inactive);
            addJobsToJobsList($this->arrLatestJobs, $this->arrLatestJobs_UnfilteredByUserInput);
        }

        if($this->arrUserInputJobs_Active  != null)
        {
            addJobsToJobsList($this->arrLatestJobs, $this->arrUserInputJobs_Active);
        }


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        __debug__printLine(PHP_EOL."**************  Updating jobs list for known filters ***************".PHP_EOL, C__DISPLAY_NORMAL__);
        $this->markMyJobsList_withAutoItems();


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Output the full jobs list into a file and into files for different cuts at the jobs list data
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        __debug__printLine(PHP_EOL."**************  Writing final list of " . count($this->arrLatestJobs) . " jobs to output files.  ***************  ".PHP_EOL, C__DISPLAY_NORMAL__);
        $class = null;

        // Write to the main output file name that the user passed in
        $this->writeRunsJobsToFile($this->detailsOutputFile['full_file_path'], $this->arrLatestJobs, "ClassJobsRunWrapper-UserOutputFile");
        $detailsCSVFile = $this->detailsOutputFile;

        //
        // Output all job records and their values
        //
        $arrJobs_Active = $this->outputFilteredJobsListToFile(null, "_AllJobs", "CSV");
//        $strOutDetailsAllResultsName = getFullPathFromFileDetails($this->detailsOutputFile, "", "_AllJobs");
//        $this->writeJobsListToFile($strOutDetailsAllResultsName, $this->arrLatestJobs, "ClassJobsRunWrapper-AllJobs");

        //
        // Now, output the various subsets of the total jobs list
        //



        // Output only records that are new or not marked as excluded (aka "Yes" or "Maybe")
        $arrJobs_Active = $this->outputFilteredJobsListToFile("isMarked_InterestedOrBlank", "_ActiveJobs", "CSV");
        $arrJobs_Active = $this->outputFilteredJobsListToFile("isMarked_InterestedOrBlank", "_ActiveJobs", "HTML", null, $this->getKeysForHTMLOutput());
        $detailsHTMLFile = $this->__getAlternateOutputFileDetails__("HTML", "", "_ActiveJobs");

        $arrJobs_Updated = $this->outputFilteredJobsListToFile("isJobUpdatedToday", "_UpdatedJobs");


        // Output only new records that haven't been looked at yet
        $arrJobs_NewOnly = $this->outputFilteredJobsListToFile("isNewJobToday_Interested_IsBlank", "_NewJobs_ForReview", "CSV");
        $arrJobs_NewOnly = $this->outputFilteredJobsListToFile("isNewJobToday_Interested_IsBlank", "_NewJobs_ForReview", "HTML", null, $this->getKeysForHTMLOutput());

        // Output all records that were automatically excluded
        $arrJobs_AutoExcluded = $this->outputFilteredJobsListToFile("isMarked_NotInterested", "_ExcludedJobs");

        // Output only records that were auto-marked as duplicates
        // $arrJobs_AutoDupe = $this->outputFilteredJobsListToFile("isInterested_MarkedDuplicateAutomatically", "_AutoDuped");
        // $arrJobs_NewButFiltered = $this->outputFilteredJobsListToFile("isNewJobToday_Interested_IsNo", "_NewJobs_AutoExcluded");
        // Output all records that were previously marked excluded manually by the user
        // $arrJobs_ManualExcl = $this->outputFilteredJobsListToFile("isMarked_ManuallyNotInterested", "_ManuallyExcludedJobs");

        $strOutputResult = "Result:  ". PHP_EOL. "All:  ". count($arrJobs_Active) . " Active, " .count($arrJobs_AutoExcluded). " Auto-Filtered, ". count($arrJobs_AutoDupe). " Dupes, " . count($this->arrLatestJobs).  " Jobs Total." .PHP_EOL. "New:  ". count($arrJobs_NewOnly) . " jobs for review. " .count($arrJobs_NewButFiltered). " jobs were auto-filtered, ". count($arrJobs_Updated) . " updated; " . count($this->arrLatestJobs_UnfilteredByUserInput) . " Jobs Downloaded." .PHP_EOL;
        $arrUnfilteredCounts = $this->getListingCountsByPlugin();
        $strOutputResult = $arrUnfilteredCounts . $arrUnfilteredCounts['text'];
        __debug__printLine($strOutputResult, C__DISPLAY_SUMMARY__);

        //
        // Send the email notification out for the completed job
        //
        $this->__sendJobCompletedEmail__($strOutputResult, $detailsCSVFile, $detailsHTMLFile);

        __debug__printLine(PHP_EOL."**************  DONE.  Cleaning up.  **************  ".PHP_EOL, C__DISPLAY_NORMAL__);
    }


    private function __sendJobCompletedEmail__($strBodyText = null, $detailsFileCSV = null, $detailsFileHTML = null)
    {
        //      set strEmailBodyContentFile to first item of argv
        //		set strFileToAttachCSV to second item of argv
        //		set strFileToAttachHTML to third item of argv
        //		set strToAddr to fourthitem of argv
        //		set strToName to fifth item of argv
        //		set strBCCAddr to sixth item of argv

        __debug__printLine("Sending email notifications for completed run. ", C__DISPLAY_ITEM_START__);

        $strHTMLFilePath = "";
        $strBodyFilePath = "";
        $strCSVFilePath = "";

        if($strBodyText != null)
        {
            $detailsFileBody = $this->__getAlternateOutputFileDetails__("TXT", "", "_Results");
            $fp = fopen($detailsFileBody['full_file_path'],'w+');
            if(!$fp)
                throw new ErrorException("Unable to open file '". $detailsFileBody['full_file_path'] . "' with access mode of 'w'.".PHP_EOL .error_get_last()['message']) ;

            if(!fputs($fp, $strBodyText))
            {
                throw new Exception("Unable to write file: " .$detailsFileBody['full_file_path'] );
            }

            $strBodyFilePath = $detailsFileBody['full_file_path'];
        }


        // Get Active Jobs HTML file name again
        if($detailsFileHTML != null)
        {
            $strHTMLFilePath = $detailsFileHTML['full_file_path'];
        }

        if($detailsFileCSV != null)
        {
            $strCSVFilePath = $detailsFileCSV['full_file_path'];

        }

        $toEmail = $this->__getEmptyEmailRecord__();;
        $bccEmail = $this->__getEmptyEmailRecord__();;

        if($this->arrEmailAddresses)
        {
            foreach($this->arrEmailAddresses as $email)
            {
                switch($email['type'])
                {
                    case "to":
                        $toEmail = $email;
                        break;

                    case "bcc":
                        $bccEmail = $email;
                        break;

                    default:
                        break;
                }

            }

            $result = "";

            //
            // Shell out to Applescript to send the email notifications
            //
            $strCmdToRun = 'osascript ' . __ROOT__ . '/main/email_job_run_results.appleScript "' . $strBodyFilePath  . '" "' . $strCSVFilePath  . '" "'.$strHTMLFilePath.'" "' . $toEmail['address'] . '" "' . $toEmail['name'] . '" "' . $bccEmail['address'] . '"';
            $strCmdToRun = escapeshellcmd($strCmdToRun);
            __debug__printLine("Starting email notifications: " . $strCmdToRun, C__DISPLAY_ITEM_DETAIL__);
            $result = my_exec($strCmdToRun);
            if($result['return'] != 0)
            {
                __debug__printLine("Failed to send notification email with error = ".$result['stderr'], C__DISPLAY_ERROR__);
            }
            else
            {
                __debug__printLine($result['stdout'], C__DISPLAY_ITEM_DETAIL__);

            }

            __debug__printLine("Email notification send done.", C__DISPLAY_ITEM_RESULT__);
        }
        else
        {
            __debug__printLine("No email addresses were set; cannot send email notifications.", C__DISPLAY_ERROR__);
        }

    }

    function parseJobsListForPage($objSimpHTML)
    {
        throw new ErrorException("parseJobsListForPage not supported for class" . get_class($this));
    }
    function parseTotalResultsCount($objSimpHTML)
    {
        throw new ErrorException("parseTotalResultsCount not supported for class " . get_class($this));
    }

    private function __getEmptyEmailRecord__()
    {
        return array('type'=> null, 'name'=>null, 'address' => null);
    }
    private function __addInputFile__($fileDetails, $file_use, $excel_sheet_name)
    {
        $this->arrUserInputFiles[] = array('details'=> $fileDetails, 'file_use_type' => $file_use, 'worksheet_name'=>$excel_sheet_name);
    }

    private function __getInputFilesByType__($strType)
    {
        $ret = $this->__getInputFilesByValue__('file_use_type', $strType);

        return $ret;
    }

    private function __getInputFilesByValue__($valKey, $val)
    {
        $ret = null;
        foreach($this->arrUserInputFiles as $fileItem)
        {
            if(strcasecmp($fileItem[$valKey], $val) == 0)
            {
                $ret[] = $fileItem;
            }
        }

        return $ret;
    }

    private function getListingCountsByPlugin()
    {

        $arrCounts = null;

        foreach( $GLOBALS['DATA']['site_plugins'] as $plugin_setup)
        {
            $strName = $plugin_setup['name'];
            $classPlug = new $plugin_setup['class_name'](null, null);
            $arrPluginJobs = array_filter($this->getMyJobsList(), array($classPlug, "isJobListingMine"));
            $arrCounts[$strName]['name'] = $strName;
            $arrCounts[$strName]['total_listings'] = count($arrPluginJobs);
            $arrCounts[$strName]['downloaded_today'] = count(array_filter($arrPluginJobs, "wasJobPulledToday"));
            $arrCounts[$strName]['updated_today'] = count(array_filter($arrPluginJobs, "isJobUpdatedToday"));
            $arrCounts[$strName]['total_not_interested'] = count(array_filter($arrPluginJobs, "isMarked_NotInterested"));
            $arrCounts[$strName]['total_active'] = count(array_filter($arrPluginJobs, "isMarked_InterestedOrBlank"));
        }

        $strOut = "	  Downloaded	Total 	Updated	Not Interested	/	Active" .PHP_EOL;
        foreach($arrCounts as $site)
        {
            $strOut = $strOut . $site['name'].":    ".$site['downloaded_today']."			".$site['total_listings']."			".$site['updated_today']."		".$site['total_not_interested']."					".$site['total_active'].PHP_EOL;
        }

//        __debug__printLine($strOut, C__DISPLAY_ITEM_DETAIL__);

        return array('text' => $strOut, 'data' => $arrCounts);
    }


    private function getKeysForHTMLOutput()
    {
        return array(
            'company',
            'job_title',
            'job_post_url',
            'location',
            'job_site_category',
//            'job_site_date' =>'',
            'interested',
            'notes',
//            'status',
//            'last_status_update',
            'date_pulled',
            'job_site',
            'job_id',
//            'key_jobsite_siteid',
//            'key_company_role',
//            'date_last_updated',
        );
    }

} 