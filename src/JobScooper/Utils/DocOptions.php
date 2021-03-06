<?php /** @noinspection SpellCheckingInspection */
/** @noinspection SpellCheckingInspection */

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

class DocOptions extends PropertyObject
{
    public $arguments = array();

    private $doc = '{APP_RUN_COMMAND}

Usage:
  {APP_RUN_COMMAND} [--config=<config_file_path>] [--user=<config_user_key>] [--jobsite=<jobsitekey>]... [--stages=<stage_numbers>] [--debug] [--ignore_recent] 
  {APP_RUN_COMMAND} [--config=<config_file_path>] [--debug]
  {APP_RUN_COMMAND} (-h | --help)
  {APP_RUN_COMMAND} --version

Options:
  -h --help                 Show this screen.
  --version                 Show version.
  --config=<config_file_path>  Full path to the configuration setting file to load.  
  --user=<config_user_key>  Which set of user configuration settings should we run.  
  --stages=<stage_numbers>  Comma-separated list of stage numbers to run from 1 - 4. [default: 1,2,3,4]
  --jobsite=<jobsitekey>    Comma-separated list of jobsites to run by JobSiteKey.
  --debug                   Show debug output [default: 0]
  --ignore_recent           Run a search regardless of whether it was run recently.
';

    public function __construct($commandfile, $input = array())
    {
        PropertyObject::__construct($input);

        $file = new \SplFileInfo($commandfile);
        $opts = str_ireplace('{APP_RUN_COMMAND}', $file->getFilename(), $this->doc);

        $args = \Docopt::handle($opts, array('version'=>__APP_VERSION__));
        foreach ($args->args as $k => $v) {
            $argkey = $k;
            $argkey = cleanupTextValue($argkey, '<', '>');
            $argkey = cleanupTextValue($argkey, '--', '');

            $argval = $v;
            
            // check for a matching environment variable and use the value from it if it exists, overriding
            // the command line. All environment setting values start with JOBSCOOPER_
            // and then the all caps name of the command line switch.
            // e.g.  JOBSCOOPER_CONFIG or JOBSCOOPER_USER
            $envkey = strtoupper('JOBSCOOPER_' . strtoupper($argkey));
            $envval = getenv($envkey);
            if ($envval !== false) {
                $argval=$envval;
                Settings::setValue("environment.{$argkey}", $argval);
            }

            if (is_array($argval) && in_array($argkey, array('jobsite', 'stages'))) {
                $argval = strtolower(implode(',', $argval));
            }
            if (is_string($argval)) {
                $argval = cleanupTextValue($argval, '\"', '\"');
                if (in_array($argkey, array('jobsite', 'stages')) && is_string($argval)) {
                    $argval = strtolower($argval);
                }
                $arrVals = preg_split('/\s*,\s*/', $argval);
                if (count($arrVals) > 1) {
                    $argval = $arrVals;
                }

                if (in_array($argkey, array('jobsite', 'stages')) && !is_array($argval) && !empty($argval)) {
                    $argval = array($argval);
                }
            }

            $this->arguments[$argkey] = $argval;
        }

        if (!array_key_exists("jobsite", $this->arguments) || is_empty_value($this->arguments['jobsite'])) {
            $this->arguments['jobsite'] = "all";
        }

    }

    public function getAll()
    {
        return $this->arguments;
    }

    public static function get($argKey)
    {
        $arguments = Settings::getValue('command_line_args');
        if (!empty($arguments) && array_key_exists($argKey, $arguments)) {
            return $arguments[$argKey];
        }

        return null;
    }

    public static function equalsTrue($argKey)
    {
        $cmdline = self::get($argKey);
        if (empty($cmdline)) {
            return false;
        }

        return filter_var($cmdline, FILTER_VALIDATE_BOOLEAN);
    }
}
