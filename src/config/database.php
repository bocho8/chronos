<?php

/**
 * Database Configuration
 * 
 * This file contains the database connection settings for the application.
 * Modify these values according to your PostgreSQL setup.
 */

return [
    'host' => 'localhost',
    'port' => '5432',
    'dbname' => 'chronos',
    'username' => 'postgres',
    'password' => 'your_password_here',
    
    // Connection options
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true
    ]
];
