<?php

/**
 * Simple autoloader for the Chronos application
 */

spl_autoload_register(function ($class) {

    $class = str_replace('App\\', '', $class);
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

spl_autoload_register(function ($class) {

    $file = __DIR__ . '/helpers/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }

    $file = __DIR__ . '/models/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }

    $file = __DIR__ . '/app/Models/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
