<?php

# Evitar cargar la librería más de una vez
if (defined('REDSYS_LIB_LOADED')) {
    return;
}

define('REDSYS_LIB_LOADED', true);

if (!defined('REDSYS_LIB_PATH')) {
    define('REDSYS_LIB_PATH', __DIR__);
}

spl_autoload_register(function ($class) {

    $prefix = 'Redsys\\';
    $baseDir = REDSYS_LIB_PATH . '/src/';

    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);

    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

# Exponer versión global de la librería
if (!defined('REDSYS_LIB_VERSION')) {
    define('REDSYS_LIB_VERSION', \Redsys\Version::VERSION);
}
