<?php

/**
 * Chronos - School Management System
 * Main entry point with modern routing system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('America/Montevideo');

ob_start();

try {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        require_once __DIR__ . '/src/autoload.php';
    }
    if (file_exists(__DIR__ . '/../config/environment/ngrok.env')) {
        $env = parse_ini_file(__DIR__ . '/../config/environment/ngrok.env');
        foreach ($env as $key => $value) {
            putenv("$key=$value");
        }
    }
    
    require_once __DIR__ . '/src/routes/web.php';
    
    echo '<script src="/js/toast.js"></script>';
    
} catch (Exception $e) {
    error_log("Fatal error in index.php: " . $e->getMessage());
    
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

ob_end_flush();