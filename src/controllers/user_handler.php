<?php
/**
 * User Handler
 * Endpoint para manejar peticiones del CRUD de usuarios
 */

// Include required files
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

// Initialize secure session first
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit();
}

try {
    // Log the request for debugging
    error_log("User handler called with action: " . ($_POST['action'] ?? $_GET['action'] ?? 'none'));
    error_log("POST data: " . json_encode($_POST));
    error_log("GET data: " . json_encode($_GET));
    
    // Load database configuration
    $dbConfig = require __DIR__ . '/../config/database.php';
    error_log("Database config loaded: " . json_encode($dbConfig));
    
    $database = new Database($dbConfig);
    
    // Test database connection
    if (!$database->testConnection()) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    error_log("Database connection successful");
    
    // Initialize controller
    require_once __DIR__ . '/UserController.php';
    $controller = new UserController($database->getConnection());
    error_log("Controller initialized successfully");
    
    // Handle request
    $controller->handleRequest();
    
} catch (Exception $e) {
    error_log("Error en user_handler: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>
