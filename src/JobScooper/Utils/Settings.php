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

use Noodlehaus\Config;

class Settings extends \Adbar\Dot
{
    public static function loadConfig($file)
    {
        $config = self::loadFile($file);
        return $config->all();
    }

    public static function loadFile($file)
    {
        $config = new Config($file);
        $imports = $config->get("imports", array());
        while (!empty($imports)) {
            $import = array_pop($imports);
            $subConfig = Settings::loadFile($import);
            $config->merge($subConfig);
        }
        unset($config["imports"]);

        return $config;
    }

    const JSCOOP_GLOBALS = 'JSCOOP';

    public static function getValue($key, $default=null)
    {
        $set = new Settings();
        $val = $set->get($key);
        if (empty($val)) {
            return $default;
        }
        return $val;
    }

    public static function setValue($key, $value)
    {
        $set = new Settings();
        $set->set($key, $value);
    }

    public static function getAllValues()
    {
        $set = new Settings();
        return $set->all();
    }

    public function __construct($items = [])
    {
        if (empty($items) && array_key_exists(self::JSCOOP_GLOBALS, $GLOBALS)) {
            $items = $GLOBALS[self::JSCOOP_GLOBALS];
        }

        parent::__construct($items);

        $GLOBALS[self::JSCOOP_GLOBALS] = $this;
    }

    public static function moveValue($oldKey, $newKey)
    {
        $settings = new Settings();

        $settings->set($newKey, self::getValue($oldKey));
        $settings->clear($oldKey);
    }

    public function set($keys, $value = null)
    {
        parent::set($keys, $value);
        $GLOBALS[self::JSCOOP_GLOBALS] = $this;
    }

    public function clear($keys = null)
    {
        parent::clear($keys);
        $GLOBALS[self::JSCOOP_GLOBALS] = $this;
    }

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
}
