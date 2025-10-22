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
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_disponibilidad') {
        $idDocente = $_POST['id_docente'] ?? null;
        $idBloque = $_POST['id_bloque'] ?? null;
        $dia = $_POST['dia'] ?? null;
        $disponible = isset($_POST['disponible']) ? filter_var($_POST['disponible'], FILTER_VALIDATE_BOOLEAN) : null;
        
        if (!$idDocente || !$idBloque || !$dia || $disponible === null) {
            ResponseHelper::error("Todos los parámetros son requeridos", null, 400);
        }
        
        try {
            $database->getConnection()->beginTransaction();
            
            // Verificar si ya existe un registro
            $checkQuery = "SELECT id_disponibilidad FROM disponibilidad WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            $checkStmt = $database->getConnection()->prepare($checkQuery);
            $checkStmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $checkStmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
            $checkStmt->bindParam(':dia', $dia, PDO::PARAM_STR);
            $checkStmt->execute();
            $existingRecord = $checkStmt->fetch();
            
            if ($existingRecord) {
                // Actualizar registro existente
                $updateQuery = "UPDATE disponibilidad SET disponible = :disponible WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
                $updateStmt = $database->getConnection()->prepare($updateQuery);
                $updateStmt->bindParam(':disponible', $disponible, PDO::PARAM_BOOL);
                $updateStmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
                $updateStmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
                $updateStmt->bindParam(':dia', $dia, PDO::PARAM_STR);
                $success = $updateStmt->execute();
            } else {
                // Insertar nuevo registro
                $insertQuery = "INSERT INTO disponibilidad (id_docente, id_bloque, dia, disponible) VALUES (:id_docente, :id_bloque, :dia, :disponible)";
                $insertStmt = $database->getConnection()->prepare($insertQuery);
                $insertStmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
                $insertStmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
                $insertStmt->bindParam(':dia', $dia, PDO::PARAM_STR);
                $insertStmt->bindParam(':disponible', $disponible, PDO::PARAM_BOOL);
                $success = $insertStmt->execute();
            }
            
            if (!$success) {
                throw new Exception("Error al actualizar la disponibilidad");
            }
            
            // Actualizar fecha de envío de disponibilidad
            $updateDocente = "UPDATE docente SET fecha_envio_disponibilidad = CURRENT_DATE WHERE id_docente = :id_docente";
            $updateDocenteStmt = $database->getConnection()->prepare($updateDocente);
            $updateDocenteStmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $updateDocenteStmt->execute();
            
            $database->getConnection()->commit();
            
            ResponseHelper::success('Disponibilidad actualizada exitosamente');
            
        } catch (Exception $e) {
            $database->getConnection()->rollback();
            throw $e;
        }
    } else {
        ResponseHelper::error("Acción no válida", null, 400);
    }
    
} catch (Exception $e) {
    error_log("Error en admin_disponibilidad_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor: ' . $e->getMessage(), null, 500);
}
