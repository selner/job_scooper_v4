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

    static function getPythonExec() {
        $pythonExec = 'python ';
        $venvDir = __ROOT__ . '/python/.venv/bin';
        if(is_dir($venvDir)) {
            $pythonExec = preg_replace("/python /", "source {$venvDir}/activate; python ", $pythonExec);
        }

        return $pythonExec;
    }

	/**
	 * @param string $scriptFile
	 * @param string[] $script_params
	 * @return null|string|integer
     *
	 * @throws \Exception
	*/
	static function execScript($scriptFile, $script_params=array()) {

        try {
            $exec = PythonRunner::getPythonExec();

            $scriptPath = __ROOT__ . "/python/{$scriptFile}";
            $cmdLine = "";
            foreach($script_params as $key => $value) {
            	$cmdLine .= " {$key} " . escapeshellarg($value);
            }

            $pythonScriptPath = realpath($scriptPath);
            if(is_empty_value($pythonScriptPath) || $pythonScriptPath === false) {
                throw new \Exception("Python script file '{$scriptPath} could not be found.");
            }
            LogMessage(PHP_EOL . "    ~~~~~~ Running command: {$exec} {$pythonScriptPath} {$cmdLine}  ~~~~~~~" . PHP_EOL);

            $resultcode  = doExec("{$exec} {$pythonScriptPath} {$cmdLine}");
        } catch (\Exception $ex) {
            handleException($ex);
        }
        return $resultcode;
        
	}
}
