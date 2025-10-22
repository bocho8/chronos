<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

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
    
    require_once __DIR__ . '/ScheduleManagementController.php';
    $controller = new ScheduleManagementController($database->getConnection());
    $controller->handleRequest();
    
} catch (Exception $e) {
    error_log("Error en gestion_horarios_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor: ' . $e->getMessage(), null, 500);
}
