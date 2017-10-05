<?php
/**
 * Register Autoload
 */
spl_autoload_register(function ($entity) {
    $module = explode('\\', $entity, 2);
    if (!in_array('JobScooper', $module)) {
        /**
         * Not a part of JobScooper file
         * then we return here.
         */
        return;
    }

    $entity = str_replace('\\', DIRECTORY_SEPARATOR, $entity);
    $path = __ROOT__ . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . $entity . '.php';

    if (is_readable($path)) {
        require_once $path;
    } else {
        if(isDebug())
            LogLine("Autoloader:  could not find expected source file '{$path}' for class {$entity}.", C__DISPLAY_WARNING__);
    }

});
