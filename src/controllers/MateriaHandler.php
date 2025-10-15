<?php

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

initSecureSession();
$translation = Translation::getInstance();

// Check authentication for AJAX requests
if (!AuthHelper::isLoggedIn()) {
    ResponseHelper::error('No autenticado. Por favor, inicie sesión primero.', null, 401);
}

if (!AuthHelper::hasRole('ADMIN')) {
    ResponseHelper::error('Acceso denegado. Se requiere rol de administrador.', null, 403);
}

if (!AuthHelper::checkSessionTimeout()) {
    ResponseHelper::error('Sesión expirada. Por favor, inicie sesión nuevamente.', null, 401);
}

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    
    if (!$database->testConnection()) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    require_once __DIR__ . '/SubjectController.php';
    $controller = new MateriaController($database->getConnection());
    $controller->handleRequest();
    
} catch (Exception $e) {
    error_log("Error en materia_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor: ' . $e->getMessage(), null, 500);
}
