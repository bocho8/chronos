<?php
/**
 * Database Response Time Endpoint for Zabbix Monitoring
 * Returns numeric response time in seconds
 */

header('Content-Type: text/plain');

$start_time = microtime(true);

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
        // Calculate response time
        $response_time = microtime(true) - $start_time;
        echo number_format($response_time, 3);
    } else {
        // Database query failed - return high value
        echo "999.999";
    }
    
} catch (PDOException $e) {
    // Database connection failed - return high value
    echo "999.999";
} catch (Exception $e) {
    // General error - return high value
    echo "999.999";
}
?>
