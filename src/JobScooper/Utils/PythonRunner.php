<?php
/**
 * Copyright 2014-18 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the 'License'); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */


namespace JobScooper\Utils;


/**
 * Class PythonRunner
 * @package JobScooper\Utils
 */
class PythonRunner {

    /**
     * @param string $command
     * @param array $command_params
     * @return null|string
     * @throws \Exception
     */
    static function execCommand($command, $command_params=array()) {
        $results = null;

        try {
            $PYTHONPATH = realpath(__ROOT__ . "/python/jobscooperrunner/jobscooperrunner/cli.py");
            $cmdLine = " {$command} ";
            foreach($command_params as $key => $value) {
                $cmdLine .= " {$key} " . escapeshellarg($value);
            }

            $pythonCmd = $PYTHONPATH . $cmdLine;
            $pythonExec = 'python ';
            $workingDir = dirname($PYTHONPATH);
            $pythonExec = "cd '{$workingDir}'; python ";


            $venvDir = __ROOT__ . '/python/.venv/bin';
            if(is_dir($venvDir)) {
                $pythonExec = "source {$venvDir}/activate; {$pythonExec} ";
            }

            LogMessage(PHP_EOL . "    ~~~~~~ Running command: {$pythonExec} {$pythonCmd}  ~~~~~~~" . PHP_EOL);
            $results  = doExec("{$pythonExec} {$pythonCmd}");
        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            LogMessage($results);
        }

        return $results;

    }


	/**
	 * @param $python_file
	 * @param array $script_params
	 * @return null|string
	 * @throws \Exception
	*/
	static function execScript($python_file, $script_params=array()) {
		$results = null;
		
        try {
            $PYTHONPATH = realpath(__ROOT__ . "/python/{$python_file}");
            $cmdLine = "";
            foreach($script_params as $key => $value) {
            	$cmdLine .= " {$key} " . escapeshellarg($value);
            }
            
            $pythonCmd = $PYTHONPATH . $cmdLine;
            $pythonExec = 'python ';
            $venvDir = __ROOT__ . '/python/.venv/bin';
            if(is_dir($venvDir)) {
                $pythonExec = preg_replace("/python /", "source {$venvDir}/activate; python ", $pythonExec);
            }

            LogMessage(PHP_EOL . "    ~~~~~~ Running command: {$pythonExec} {$pythonCmd}  ~~~~~~~" . PHP_EOL);
            $results  = doExec("{$pythonExec} {$pythonCmd}");
        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            LogMessage($results);
        }

        return $results;
        
	}
}
