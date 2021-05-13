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

    static function getPythonExec($scriptFile) {
        $pythonExec = '/python ';
        $pythondir = __ROOT__ . "/python";

        $venvdirs = ["/venv/bin", "/.venv/bin"];
        $trydirs = [$pythondir];

        $scriptDirs = preg_split("/\//", $scriptFile);
        if($scriptDirs != null && count($scriptDirs) > 0) {
            $lastDir = $pythondir;
            foreach($scriptDirs as $dir) {
                $lastDir = $lastDir . "/" . $dir;
                if(is_dir($lastDir)) {
                    $trydirs[] = $lastDir;
                }
            }
        }
        foreach($trydirs as $subdir) {
            foreach($venvdirs as $venv) {
                $testdir = $subdir . $venv;
                if(is_dir($testdir)) {
                    if(is_link("$testdir/python") || is_file("$testdir/python")) {
                        return "$testdir/python";
                    }
                }
            }
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
	static function execScript($scriptFile, $script_params=array(), $includeDBParams=false) {

        startLogSection("Calling Python Script:  $scriptFile");

        try {
            $exec = PythonRunner::getPythonExec($scriptFile);
            $scriptPath = __ROOT__ . "/python/$scriptFile";
            $cmdLine = "";

            if($script_params == null) {
                $script_params = array();
            }

            if ($includeDBParams == true) {
                $dbParams = Settings::get_db_cli_params();
                $script_params = array_merge($script_params, $dbParams);
            }

            foreach($script_params as $key => $value) {
            	if($key[0] != "-") {
            	    $key = "--{$key}";
                }
                $cmdLine .= " {$key} " . escapeshellarg($value);
            }

            $pythonScriptPath = realpath($scriptPath);
            if(is_empty_value($pythonScriptPath) || $pythonScriptPath === false) {
                throw new \Exception("Python script file '$scriptPath' could not be found.");
            }
            LogMessage(PHP_EOL . "    ~~~~~~ Running command: {$exec} {$pythonScriptPath} {$cmdLine}  ~~~~~~~" . PHP_EOL);

            $resultcode  = doExec("{$exec} {$pythonScriptPath} {$cmdLine}");
            LogMessage(PHP_EOL . "    >>>>> command completed with result {$resultcode} >>>>>>" . PHP_EOL);
            endLogSection(" Finished Python Script call:  $scriptFile");
            return $resultcode;

        } catch (\Exception $ex) {
            handleThrowable($ex);
        }

        return null;

    }
}
