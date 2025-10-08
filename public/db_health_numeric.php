<?php
/**
 * Database Health Check Endpoint for Zabbix Monitoring (Numeric Response)
 * Returns numeric values suitable for Zabbix monitoring
 */

header('Content-Type: text/plain');

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
        // Database is healthy - return 0
        echo "0";
    } else {
        // Database query failed - return 1
        echo "1";
    }
    
} catch (PDOException $e) {
    // Database connection failed - return 1
    echo "1";
} catch (Exception $e) {
    // General error - return 1
    echo "1";
}
?>
