<?php
function loadClass($className) {
    $fileName = '';
    $namespace = 'JobScooper';

    // Sets the include path as the "src" directory
    $includePath = __ROOT__.DIRECTORY_SEPARATOR.'src';

    if (false !== ($lastNsPos = strripos($className, '\\'))) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    $fullFileName = $includePath . DIRECTORY_SEPARATOR . $fileName;

    if (file_exists($fullFileName)) {
        require $fullFileName;
    } else {
        if(isDebug())
            LogLine('Autoloader:  class "'.$className.'" was not found.', C__DISPLAY_WARNING__);
    }
}
spl_autoload_register('loadClass'); // Registers the autoloader
