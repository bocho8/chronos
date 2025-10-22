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
    error_log("HorarioHandler - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("HorarioHandler - POST data: " . json_encode($_POST));
    error_log("HorarioHandler - GET data: " . json_encode($_GET));
    
    $rawInput = file_get_contents('php://input');
    error_log("HorarioHandler - Raw input: " . $rawInput);
    
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    
    if (!$database->testConnection()) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    require_once __DIR__ . '/ScheduleController.php';
    $controller = new HorarioController($database->getConnection());
    
    // Parse JSON input if it exists
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if ($jsonData) {
            // Merge JSON data into POST array
            $_POST = array_merge($_POST, $jsonData);
        }
    }
    
    $controller->handleRequest();
    
} catch (Exception $e) {
    error_log("Error en horario_handler: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ResponseHelper::error('Error interno del servidor: ' . $e->getMessage(), null, 500);
}
