<?php
/**
 * Copyright 2014-18 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace JobScooper\Traits;

/**
 * Define a reusable singleton trait.
 */
trait Singleton
{
	protected static $_instance;
	
    /**
    * @return \JobScooper\Traits\Singleton
	*/
    final public static function getInstance()
    {
        return static::$_instance !== null ? static::$_instance: static::$_instance= new static;
    }
	
    /**
    * @return \JobScooper\Traits\Singleton
	*/
	final public static function clean()
	{
		return static::$_instance = new static;
	}
	
    private function __construct()
	{
		static::init();
    }
	
    protected static function init() {}
	
    final public function __wakeup() {}
	
    private function __clone() {}
}
