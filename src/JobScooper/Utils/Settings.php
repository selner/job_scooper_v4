<?php
/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 5/16/18
 * Time: 11:05 PM
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
}
