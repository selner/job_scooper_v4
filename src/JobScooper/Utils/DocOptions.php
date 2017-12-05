<?php
/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 12/2/17
 * Time: 2:58 PM
 */

namespace JobScooper\Utils;

class DocOptions extends PropertyObject
{
    public $arguments = array();

private $doc = <<<DOC
{APP_RUN_COMMAND}

Usage:
  {APP_RUN_COMMAND} <configfile> [--jobsite=<jobsitekey>]... [--stages=<stage_numbers>] [--debug] [--disable-notifications]
  {APP_RUN_COMMAND} <configfile> [--debug] [--disable-notifications]
  {APP_RUN_COMMAND} (-h | --help)
  {APP_RUN_COMMAND} --version

Options:
  -h --help                 Show this screen.
  --version                 Show version.
  --stages=<stage_numbers>  Comma-separated list of stage numbers to run. [default: 1,2,3,4]
  --jobsite=<jobsitekey>    Comma-separated list of jobsites to run by JobSiteKey. [default: all]
  --debug                   Show debug output. [default: false]
  --disable-notifications   Do not send email alerts for new jobs. [default: false]

DOC;

    public function __construct($commandFile)
    {
        $file = new \SplFileInfo($commandFile);
        $opts = str_ireplace("{APP_RUN_COMMAND}", $file->getFilename(), $this->doc);

        $args = \Docopt::handle($opts, array('version'=>__APP_VERSION__));
        foreach($args->args as $k => $v) {
            $argkey = $k;
            $argkey = cleanupTextValue($argkey, "<", ">");
            $argkey = cleanupTextValue($argkey, "--", "");

            $argval = $v;
            if (is_string($argval)) {
                $argval = cleanupTextValue($argval, "\"", "\"");
                $arrVals = preg_split("/\s*,\s*/", $argval);
                if (count($arrVals) > 1)
                    $argval = $arrVals;

                if(in_array($argkey, array("jobsite", "stages")) && !is_array($argval) && !empty($argval))
                	$argval = array($argval);
            }

            $this->arguments[$argkey] = $argval;
        }
    }

    public function getAll()
    {
        return $this->arguments;
    }

    static function get($argKey)
    {
        $arguments = getConfigurationSetting('command_line_args');
        if(!empty($arguments) && array_key_exists($argKey, $arguments))
            return $arguments[$argKey];

        return null;
    }

    static function equalsTrue($argKey)
    {
        $cmdline = DocOptions::get($argKey);
        if(is_null($cmdline))
            return false;

        return filter_var($cmdline, FILTER_VALIDATE_BOOLEAN);
    }

}

