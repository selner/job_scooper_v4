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
namespace JobScooper\StageProcessor;

use Exception;
use JobScooper\Utils\PythonRunner;
use JobScooper\Utils\Settings;

/**
 * Class JobsAutoMarker
 * @package JobScooper\StageProcessor
 */
class DataNormalizer
{

    /**
     * DataNormalizer constructor.
     *
     * @param array $userFacts
     * @throws \Exception
     */
    public function __construct()
    {

    }

    /**
     *
     * @throws \Exception
     */
    public function normalizeJobs()
    {
        $startMem = getPhpMemoryUsage();
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Filter the full jobs list looking for duplicates, etc.
        //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        try {
            $this->_addMissingTitleTokens();


            $this->_findLocationsViaPython();

            // Must first match locations to jobs before we can dedupe.  Dedupe uses location
            // as part of the determination of whether we have that same listing already.
            //
            $this->_markDuplicatesViaPython();
        } catch (Exception $ex) {
            LogError($ex->getMessage(), null, $ex);
        } finally {
            $endMem = getPhpMemoryUsage();
	        LogMessage("Memory in use on entry:  {$startMem} vs. exit {$endMem}.");

        }

    }

    /**
     * @throws \Exception
     */
    private function _addMissingTitleTokens()
    {
        try {
            startLogSection('Calling python to set the title tokens for new job postings...');
            $runFile = 'pyJobNormalizer/cmd_set_title_tokens.py';

            $resultcode = PythonRunner::execScript($runFile, null, true);
            LogMessage("Python command call '$runFile' finished with result: '$resultcode'");

        } catch (\Exception $ex) {
            handleException($ex, null, false);
        } finally {
            EndLogSection('Completed setting title tokens dedupe.');
        }
    }
    /**
     * @throws \Exception
     */
    private function _markDuplicatesViaPython()
    {
        try {
            startLogSection('Calling python to dedupe new job postings...');
            $runFile = 'pyJobNormalizer/cmd_mark_duplicates.py';

            $resultcode = PythonRunner::execScript($runFile, null, true);
            LogMessage("Python command call '$runFile' finished with result: '$resultcode'");

        } catch (\Exception $ex) {
            handleException($ex, null, false);
        } finally {
            EndLogSection('Completed new job posting dedupe.');
        }
    }




    /**
     * @throws \Exception
     */
    private function _findLocationsViaPython()
    {
        try {
            startLogSection('Calling python to find & map missing locations...');
            $runFile = 'pyJobNormalizer/cmd_set_geolocations.py';
            $params = [
                '--server' => Settings::getValue('geocodeapi_server'),
            ];

            $resultcode = PythonRunner::execScript($runFile, $params, true);
            LogMessage("Python command call '$runFile' finished with result: '$resultcode'");

        } catch (\Exception $ex) {
            handleException($ex, null, false);
        } finally {
            EndLogSection('Completed finding and matching missing locations.');
        }
    }

}

