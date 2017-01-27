<?php

/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 1/6/17
 * Time: 7:36 PM
 */

abstract class PropertyObject
{
    public function __get($name)
    {
        if (method_exists($this, ($method = 'get_'.$name)))
        {
            return $this->$method();
        }
        else return false;
    }

    public function __isset($name)
    {
        if (method_exists($this, ($method = 'isset_'.$name)))
        {
            return $this->$method();
        }
        else return false;
    }

    public function __set($name, $value)
    {
        if (method_exists($this, ($method = 'set_'.$name)))
        {
            $this->$method($value);
        }
    }

    public function __unset($name)
    {
        if (method_exists($this, ($method = 'unset_'.$name)))
        {
            $this->$method();
        }
    }
}

