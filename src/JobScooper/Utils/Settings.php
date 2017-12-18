<?php
/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 5/16/18
 * Time: 11:05 PM
 */

namespace JobScooper\Utils;


use M1\Vars\Vars;
use Noodlehaus\Config;

class Settings extends \Adbar\Dot
{
	static function loadConfig($file)
	{
		$config = self::loadFile($file);
		return $config->all();
	}

	static function loadFile($file)
	{
		$config = new Config($file);
		$imports = $config->get("imports", array());
		while(!empty($imports))
		{
			$import = array_pop($imports);
			$subConfig = Settings::loadFile($import);
			$config->merge($subConfig);
		}
		unset($config["imports"]);

		return $config;

	}

	const JSCOOP_GLOBALS = 'JSCOOP';

	static function getValue($key, $default=null)
	{
		$set = new Settings();
		$val = $set->get($key);
		if(empty($val))
		{
			return $default;
		}
		return $val;
	}

	static function setValue($key, $value)
	{
		$set = new Settings();
		$set->set($key, $value);
	}

	static function getAllValues()
	{
		$set = new Settings();
		return $set->all();
	}

	public function __construct($items = [])
	{
		if(empty($items) && array_key_exists(self::JSCOOP_GLOBALS, $GLOBALS)) {
			$items = $GLOBALS[self::JSCOOP_GLOBALS];
		}

		parent::__construct($items);

		$GLOBALS[self::JSCOOP_GLOBALS] = $this;
	}

	static function moveValue($oldKey, $newKey)
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
