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

use Adbar\Dot;
use JBZoo\Utils\Str;
use Noodlehaus\Config;
use Noodlehaus\Exception\EmptyDirectoryException;

class Settings extends \Adbar\Dot
{
    const SETTINGS_GEOCODEAPI = "geocodeapi";
    const SETTINGS_JOBNORMALIZER = "jobnormalizer";
    const SETTINGS_SELENIUM = "selenium";

    /**
     * @param $file
     * @return Dot|array|null
     * @throws EmptyDirectoryException
     */
    public static function loadConfig($file)
    {
        $config = self::loadFile($file);
        return new Dot($config->all());
    }

    /**
     * @param $file
     * @return Config
     */
    public static function loadFile($file, $parentdir=null)
    {
        if($parentdir == null) {
            $parentdir = dirname(realpath($file));
        }

        $config = new Config($file);
        $imports = $config->get("imports", array());
        while (!empty($imports)) {
            $import = array_pop($imports);
            $pathimp = get_dir_realpath($import, $parentdir);

            LogMessage("Loading config settings from file $pathimp");
            $subConfig = Settings::loadFile($pathimp, $parentdir);
            $config->merge($subConfig);
        }
        unset($config["imports"]);

        return $config;
    }

    /**
     *
     */
    const JSCOOP_GLOBALS = 'JSCOOP';

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public static function getValue($key, $default=null)
    {
        $set = new Settings();
        $val = $set->get($key);
        if (empty($val)) {
            return $default;
        }
        return $val;
    }

    /**
     * @param $key
     * @param $value
     */
    public static function setValue($key, $value)
    {
        $set = new Settings();
        $set->set($key, $value);
    }

    /**
     * @return array
     */
    public static function getAllValues()
    {
        $set = new Settings();
        return $set->all();
    }

    /**
     * @return string
     */
    public static function exportAll()
    {
        $set = new Settings();
        $all = $set->all();
        return var_export($all, true);
    }

    /**
     * Settings constructor.
     * @param array $items
     */
    public function __construct($items = [])
    {
        if (empty($items) && array_key_exists(self::JSCOOP_GLOBALS, $GLOBALS)) {
            $items = $GLOBALS[self::JSCOOP_GLOBALS];
        }

        parent::__construct($items);

        $GLOBALS[self::JSCOOP_GLOBALS] = $this;
    }



    /**
     * @param $oldKey
     * @param $newKey
     */
    public static function copyValue($oldKey, $newKey)
    {
        $settings = new Settings();

        $settings->set($newKey, self::getValue($oldKey));
    }

    /**
     * @param $oldKey
     * @param $newKey
     */
    public static function moveValue($oldKey, $newKey)
    {
        $settings = new Settings();

        $settings->copyValue($newKey, self::getValue($oldKey));
        $settings->clear($oldKey);
    }
    /**
     * @param array|int|string $keys
     * @param null $value
     */
    public function set($keys, $value = null)
    {
        parent::set($keys, $value);
        $GLOBALS[self::JSCOOP_GLOBALS] = $this;
    }


    /**
     * @param null $keys
     */
    public function clear($keys = null)
    {
        parent::clear($keys);
        $GLOBALS[self::JSCOOP_GLOBALS] = $this;
    }

    /**
     * @param $key
     * @param $checkVal
     * @return bool
     */
    public static function is_in_setting_value($key, $checkVal) {

        $valuesSet = self::getValue($key);
        if(is_empty_value($valuesSet)) {
            return is_empty_value($checkVal);
        }

        if(is_array($valuesSet)) {
            return in_array($checkVal, $valuesSet);
        }

        return $valuesSet == $checkVal;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function get_db_dsn() {
        $cfg = self::getValue('db_config');
        if(is_empty_value($cfg)) {
            throw new \Exception('Could not find database configuration to use.');
        }
        $dsn = "";
        foreach($cfg as $k=>$v) {
            if(!is_array($v)) {
                if(in_array($k, ["classname"])) {
                    continue;
                }
                elseif ($k === "dsn") {
                    $dsn .= "{$v};";
                }
                else
                {
                    $dsn .= "{$k}={$v};";
                }

            }
        }

        return $dsn;

    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function getDatabaseParams() {

        $cfg = self::getValue('db_config');
        if(is_empty_value($cfg)) {
            throw new \Exception('Could not find database configuration to use.');
        }
        $cliparams = array_subset_keys($cfg, ['user', 'host', 'password', 'port', 'dbname']);
        if(array_key_exists('dbname', $cliparams) && $cliparams['dbname'] != null) {
            $cliparams['database'] = $cliparams['dbname'];
            unset($cliparams['dbname']);
        }
        return $cliparams ;
    }
}
