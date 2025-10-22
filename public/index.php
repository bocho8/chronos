<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Chronos - School Management System
 * Main entry point with modern routing system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('America/Montevideo');

ob_start();

try {
    // Try different autoload paths for different environments
    $autoloadPaths = [
        __DIR__ . '/../vendor/autoload.php',  // Composer autoload (if exists)
        __DIR__ . '/../src/autoload.php',     // Local development
        __DIR__ . '/src/autoload.php',        // Docker container
    ];
    
    $autoloadLoaded = false;
    foreach ($autoloadPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $autoloadLoaded = true;
            break;
        }
    }
    
    if (!$autoloadLoaded) {
        throw new Exception('Autoload file not found in any expected location');
    }
    if (file_exists(__DIR__ . '/../config/environment/ngrok.env')) {
        $env = parse_ini_file(__DIR__ . '/../config/environment/ngrok.env');
        foreach ($env as $key => $value) {
            putenv("$key=$value");
        }
    }
    
    // Try different routes paths for different environments
    $routesPaths = [
        __DIR__ . '/../src/routes/web.php',  // Local development
        __DIR__ . '/src/routes/web.php',     // Docker container
    ];
    
    $routesLoaded = false;
    foreach ($routesPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $routesLoaded = true;
            break;
        }
    }
    
    if (!$routesLoaded) {
        throw new Exception('Routes file not found in any expected location');
    }
    
    echo '<script src="/js/toast.js"></script>';
    
} catch (Exception $e) {
    error_log("Fatal error in index.php: " . $e->getMessage());
    
    // Try to show error page, fallback to JSON if error page fails
    try {
        $errorController = new \App\Controllers\ErrorController();
        $errorController->show500($e->getMessage());
    } catch (Exception $errorPageException) {
        // Fallback to JSON response if error page fails
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal server error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ]);
        
        if (getenv('APP_ENV') === 'development') {
            echo "\n\nDebug information:\n";
            echo $e->getMessage() . "\n";
            echo $e->getTraceAsString();
        }
    }
}

ob_end_flush();