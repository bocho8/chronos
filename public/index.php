<?php

/**
 * Chronos - School Management System
 * Main entry point with modern routing system
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('America/Montevideo');

// Start output buffering
ob_start();

try {
    // Load autoloader (if using Composer)
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        // Load simple autoloader
        require_once __DIR__ . '/src/autoload.php';
    }
    
    // Load environment configuration
    if (file_exists(__DIR__ . '/../config/environment/ngrok.env')) {
        $env = parse_ini_file(__DIR__ . '/../config/environment/ngrok.env');
        foreach ($env as $key => $value) {
            putenv("$key=$value");
        }
    }
    
    // Load routes
    require_once __DIR__ . '/src/routes/web.php';
    
    // Include toast system
    echo '<script src="/js/toast.js"></script>';
    
} catch (Exception $e) {
    // Log error
    error_log("Fatal error in index.php: " . $e->getMessage());
    
    // Show user-friendly error
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
    
    // In development, show detailed error
    if (getenv('APP_ENV') === 'development') {
        echo "\n\nDebug information:\n";
        echo $e->getMessage() . "\n";
        echo $e->getTraceAsString();
    }
}

// Clean output buffer
ob_end_flush();