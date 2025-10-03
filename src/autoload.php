<?php

/**
 * Simple autoloader for the Chronos application
 */

spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $class = str_replace('App\\', '', $class);
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Also load non-namespaced classes
spl_autoload_register(function ($class) {
    // Try to load from helpers directory
    $file = __DIR__ . '/helpers/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
    
    // Try to load from models directory
    $file = __DIR__ . '/models/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
    
    // Try to load from app/Models directory
    $file = __DIR__ . '/app/Models/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
