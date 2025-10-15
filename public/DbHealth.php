<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Database Health Check Endpoint for Zabbix Monitoring
 * This file provides a simple health check for the PostgreSQL database
 */

header('Content-Type: application/json');

try {
    // Database connection parameters
    $host = $_ENV['POSTGRES_HOST'] ?? 'postgres';
    $dbname = $_ENV['POSTGRES_DB'] ?? 'chronos_db';
    $username = $_ENV['POSTGRES_USER'] ?? 'chronos_user';
    $password = $_ENV['POSTGRES_PASSWORD'] ?? 'chronos_pass';
    
    // Create PDO connection
    $dsn = "pgsql:host=$host;dbname=$dbname;port=5432";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    // Test query
    $stmt = $pdo->query("SELECT 1 as health_check");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['health_check'] == 1) {
        // Database is healthy
        http_response_code(200);
        echo json_encode([
            'status' => 'healthy',
            'database' => $dbname,
            'timestamp' => date('Y-m-d H:i:s'),
            'response_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        ]);
    } else {
        // Database query failed
        http_response_code(503);
        echo json_encode([
            'status' => 'unhealthy',
            'error' => 'Database query failed',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (PDOException $e) {
    // Database connection failed
    http_response_code(503);
    echo json_encode([
        'status' => 'unhealthy',
        'error' => 'Database connection failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    // General error
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Health check failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
