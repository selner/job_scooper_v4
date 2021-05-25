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
use \GuzzleHttp\Client;


use Requests_Exception;

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

    static function get_python_path($scriptfile)
    {
        $root = Settings::getValue("source_root_directory");
        return "$root/python";
//        return dirname(dirname(realpath(__FILE__))) . "/python";

    }
    static function set_python_path_cmd($scripfile) {
        $pythondir = PythonRunner::get_python_path($scripfile);
        $pythonpath = getenv('PYTHONPATH');
        $scriptdir = dirname($scripfile);
        if(false == realpath($scriptdir)) {
            $scriptdir = "$pythondir/$scriptdir";
        }
        if ($pythonpath != false) {
            $pythonpath = "$pythonpath;$pythondir;$scriptdir";
        }
        else {
            $pythonpath = "$pythondir;$scriptdir";
        }
        $pythonpath = "$pythondir";

        return "PYTHONPATH=$pythonpath ";

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
#            $scriptPath = __ROOT__ . "/python/$scriptFile";
            $scriptPath = "./$scriptFile";
            $cmdLine = "";

            if($script_params == null) {
                $script_params = array();
            }

            if ($includeDBParams == true) {
                $dbParams = Settings::getDatabaseParams();
                $script_params = array_merge($script_params, $dbParams);
            }

            foreach($script_params as $key => $value) {
                if($key[0] != "-") {
                    $key = "--$key";
                }
                $cmdLine .= " $key " . escapeshellarg($value);
            }

            $scriptdir = PythonRunner::get_python_path($scriptFile);


            $pythonpath = PythonRunner::set_python_path_cmd($scriptFile);
//            $pythonScriptPath = realpath($scriptPath);
//            if(is_empty_value($pythonScriptPath) || $pythonScriptPath === false) {
//                throw new \Exception("Python script file '$scriptPath' could not be found.");
//            }
            $cmd = "{$pythonpath} cd \"$scriptdir\" && $exec $scriptFile $cmdLine";
            LogMessage(PHP_EOL . "    ~~~~~~ Running command: $cmd  ~~~~~~~" . PHP_EOL);

            $resultcode  = doExec($cmd);
            LogMessage(PHP_EOL . "    >>>>> command completed with result {$resultcode} >>>>>>" . PHP_EOL);
            endLogSection(" Finished Python Script call:  $scriptFile");
            return $resultcode;

        } catch (\Exception $ex) {
            handleThrowable($ex);
        }

        return null;

    }

    /** The enumerated values for the user_notification_state field */
    const API_SET_TITLE_TOKENS      = '/api/set_title_tokens';
    const API_ADD_JOBS_TO_USERS     = '/api/add_jobs_to_users';
    const API_UPDATE_USER_MATCHES   = '/api/update_user_job_matches';
    const API_DUPES_MATCH           = '/api/process_duplicates';
    const API_SET_OUT_OF_AREA       = '/api/set_out_of_area/<int:user_id>';
    const API_UPDATE_GEOCODES       = '/api/update_geocodes';
    const API_MATCH_JOB_TOKENS      = '/api/match_user_keywords';

    const ARG_DB_USER = 'user';
    const ARG_DB_PASSWORD = 'password';
    const ARG_DB_HOST = 'host';
    const ARG_DB_DATABASE = 'database';
    const ARG_DB_PORT = 'port';
    const ARG_DB_GEOCODE_SERVER = 'geocode_server';

    static function getParams($inclGeocode=false) {
        $dbparams = Settings::getDatabaseParams();

        $options = array_copy($dbparams);
        if($inclGeocode == true) {
            $options[self::ARG_DB_GEOCODE_SERVER] == Settings::getValue('geocodeapi.server');
        }

        return $options;
    }

    static function getUrl($url, $data) {
        $query = "";

        $apiserver = Settings::getValue('jobnormalizer.server');


        $params = array();
        if ($data != null) {

            foreach (array_keys($data) as $qparam) {
                $params[] = "$qparam=" . urlencode($data[$qparam]);
            }

            $sep = "?";
            if (str_contains("?", $url) == true) {
                $sep = "&";
            }
            $query = $sep . implode("&", $params);
        }

        return "$apiserver$url$query";
    }


    static function callJobNormalizerAPI($api, $extraParams=null, $fileUpload=null)
    {
        $apiurl = self::getUrl($api, null);

        $data = self::getParams();
        switch($api) {

            case self::API_UPDATE_GEOCODES:
                $data = self::getParams(inclGeocode: true);
                $rtype = 'GET';
                break;

            case self::API_MATCH_JOB_TOKENS:
                $data = self::getParams();
                $api = "$api/" . $extraParams['user_id'];
                unset($extraParams['user_id']);
                $apiurl = self::getUrl($api, null);
                break;

            default:
                $rtype = 'GET';
                break;
        }

        if ($fileUpload != null) {
            $rtype = 'PUT';
        }

        if($extraParams != null && count($extraParams) > 0) {
            $data = array_merge($data, $extraParams);
        }

        $options = array();
        $headers = array(
            "Connection: keep-alive",
            "Keep-Alive: timeout=5, max=100",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        );


        $session = new \Requests_Session($api, $headers, $data, $options);


        try {

            switch($rtype) {
                    // Provide an fopen resource.

//                    $body = \GuzzleHttp\Psr7\Utils::tryFopen($fileUpload, 'r');
//                    $data['body'] = $body;
//                    $r = $client->request('POST', 'http://127.0.0.1:5000/testjson', $data);
//                    $r->getStatusCode();
////                    $request = $session->put("http://127.0.0.1:5000/testjson", $headers, data:$data, options: array('filename'=>$fileUpload));
//                    break;

                case "PUT":
                case "POST":
                    $client = new Client();
                    // Provide an fopen resource.
                    $body = \GuzzleHttp\Psr7\Utils::tryFopen($fileUpload, 'r');
                    $data['json'] = $body;
                    $r = $client->request('POST', $apiurl, ['body' => $body]);
                    $r->getStatusCode();
//                    $request = $session->post("http://127.0.0.1:5000/testjson", $headers, data:$data);
//                    $request = $session->post($apiurl, $headers, json_encode($jsonData));
                    break;

                case "GET":
                default:
                    $request = $session->get($apiurl, $headers);
                    break;

            }
            $request->throw_for_status(true);

        } catch (Requests_Exception $ex) {
            LogError("API call '$apiurl' failed", ex:$ex);
        }

        assert($request->status_code == 200);

        return json_decode($request->body );

    }

}
