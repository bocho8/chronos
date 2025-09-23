<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

initSecureSession();
$translation = Translation::getInstance();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    ResponseHelper::error('Sesión expirada', null, 401);
}

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    
    if (!$database->testConnection()) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    require_once __DIR__ . '/UserController.php';
    $controller = new UserController($database->getConnection());
    $controller->handleRequest();
    
} catch (Exception $e) {
    error_log("Error en user_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor: ' . $e->getMessage(), null, 500);
}
?>
