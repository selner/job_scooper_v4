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
            $this->_markDuplicatesViaPython();
            $this->_findLocationsViaPython();
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
            $params = [
                '-c' => Settings::get_db_dsn()
            ];

            $resultcode = PythonRunner::execScript($runFile, $params);
            LogMessage('Python command call finished.');

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
            $runFile = 'pyJobNormalizer/mark_duplicates.py';
            $params = [
                '-c' => Settings::get_db_dsn()
            ];

            $resultcode = PythonRunner::execScript($runFile, $params);
            LogMessage('Python command call finished.');

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
            $runFile = 'pyJobNormalizer/set_geolocations.py';
            $params = [
                '-c' => Settings::get_db_dsn(),
                '--server' => Settings::getValue('geocodeapi_server'),
            ];

            $resultcode = PythonRunner::execScript($runFile, $params);
            LogMessage('Python command call finished.');

        } catch (\Exception $ex) {
            handleException($ex, null, false);
        } finally {
            EndLogSection('Completed finding and matching missing locations.');
        }
    }

}

