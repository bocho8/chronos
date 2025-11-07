<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/BloqueController.php';

// Initialize session
initSecureSession();

// Check authentication
AuthHelper::requireRole(['ADMIN', 'DIRECTOR', 'COORDINADOR']);

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    ResponseHelper::error('Sesión expirada', null, 401);
    exit;
}

// Set content type
header('Content-Type: application/json');

try {
    // Get database connection
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    
    // Initialize controller
    $controller = new BloqueController($database->getConnection());
    
    // Parse JSON input for POST requests
    $data = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        if ($rawInput) {
            $jsonData = json_decode($rawInput, true);
            if ($jsonData) {
                $data = $jsonData;
            }
        }
        
        // Merge with $_POST data
        $data = array_merge($data, $_POST);
    } else {
        $data = $_GET;
    }
    
    // Get action from parsed data
    $action = $data['action'] ?? '';
    
    error_log("BloqueHandler - Action: " . $action);
    
    // Handle the request
    $response = $controller->handleRequest($action, $data);
    
    // Output response
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error in BloqueHandler: " . $e->getMessage());
    $response = ResponseHelper::error('Error interno del servidor');
    echo json_encode($response);
}
