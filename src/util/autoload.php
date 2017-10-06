<?php
/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 10/5/17
 * Time: 12:07 AM
 */


$files = glob(realpath(dirname(__FILE__))  . '/*.php');
foreach ($files as $file) {
    print "Autoloading {$file}...";
    require_once($file);
}

