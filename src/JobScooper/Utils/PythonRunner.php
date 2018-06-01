<?php
/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 5/31/18
 * Time: 4:41 PM
 */

namespace JobScooper\Utils;


class PythonRunner {

	
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
        	return $results;
        }
        
        
	}
}
