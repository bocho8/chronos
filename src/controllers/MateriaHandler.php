<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

// Start output buffering to prevent any accidental output before headers are set
ob_start();

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

initSecureSession();

// Check authentication for AJAX requests
if (!AuthHelper::isLoggedIn()) {
    ob_end_clean();
    ResponseHelper::error('No autenticado. Por favor, inicie sesión primero.', null, 401);
    exit;
}

if (!AuthHelper::hasRole('ADMIN')) {
    ob_end_clean();
    ResponseHelper::error('Acceso denegado. Se requiere rol de administrador.', null, 403);
    exit;
}

if (!AuthHelper::checkSessionTimeout()) {
    ob_end_clean();
    ResponseHelper::error('Sesión expirada. Por favor, inicie sesión nuevamente.', null, 401);
    exit;
}

try {
    // Initialize Translation only after authentication passes
    $translation = Translation::getInstance();
    
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    
    if (!$database->testConnection()) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    require_once __DIR__ . '/SubjectController.php';
    $controller = new MateriaController($database->getConnection());
    $controller->handleRequest();
    
} catch (Exception $e) {
    ob_end_clean();
    error_log("Error en materia_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor: ' . $e->getMessage(), null, 500);
    exit;
}
