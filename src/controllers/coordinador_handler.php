<?php

/**
 * Coordinador Handler
 * 
 * This file handles all AJAX requests related to coordinator management.
 */

// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Coordinador.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!AuthHelper::isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Sesión expirada. Por favor, inicie sesión nuevamente.',
            'redirect' => 'login.php'
        ]);
        exit;
    }
    
    // Check if user has admin role
    if (!AuthHelper::hasRole('ADMIN')) {
        echo json_encode([
            'success' => false,
            'message' => 'No tiene permisos para realizar esta acción.'
        ]);
        exit;
    }
    
    // Check session timeout
    if (AuthHelper::isSessionExpired()) {
        AuthHelper::logout();
        echo json_encode([
            'success' => false,
            'message' => 'Sesión expirada. Por favor, inicie sesión nuevamente.',
            'redirect' => 'login.php'
        ]);
        exit;
    }
    
    // Update last activity
    AuthHelper::updateLastActivity();
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Error de conexión a la base de datos.'
        ]);
        exit;
    }
    
    // Initialize controller
    $coordinadorController = new CoordinadorController($db);
    
    // Handle the request
    $response = $coordinadorController->handleRequest();
    
    // Return JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error en coordinador_handler.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Por favor, intente nuevamente.'
    ]);
}
?>
