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

AuthHelper::requireRole(['ADMIN', 'DIRECTOR', 'COORDINADOR']);

if (!AuthHelper::checkSessionTimeout()) {
    ResponseHelper::error('Sesión expirada', null, 401);
}

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    
    if (!$database->testConnection()) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
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
    } elseif ($action === 'create_observacion') {
        $idDocente = $_POST['id_docente'] ?? null;
        $idObservacionPredefinida = !empty($_POST['id_observacion_predefinida']) ? $_POST['id_observacion_predefinida'] : null;
        $tipo = trim($_POST['tipo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $motivoTexto = trim($_POST['motivo_texto'] ?? '');
        
        if (!$idDocente) {
            ResponseHelper::error("ID de docente es requerido", null, 400);
        }
        
        // Check if "Otro" is selected (RF034)
        $isOtroSelected = false;
        if ($idObservacionPredefinida) {
            try {
                $otroQuery = "SELECT texto FROM observacion_predefinida WHERE id_observacion_predefinida = :id AND LOWER(TRIM(texto)) = 'otro'";
                $otroStmt = $database->getConnection()->prepare($otroQuery);
                $otroStmt->bindParam(':id', $idObservacionPredefinida, PDO::PARAM_INT);
                $otroStmt->execute();
                $isOtroSelected = $otroStmt->fetch() !== false;
            } catch (Exception $e) {
                error_log("Error checking 'Otro' observation: " . $e->getMessage());
            }
        }
        
        // If "Otro" is selected, motivo_texto is required
        if ($isOtroSelected) {
            if (empty($motivoTexto) || strlen($motivoTexto) === 0 || ctype_space($motivoTexto)) {
                ResponseHelper::error("Al seleccionar 'Otro', debe completar el campo 'Motivo / Texto libre' con la descripción de la observación", null, 400);
            }
        }
        
        // Trim and normalize empty strings to null
        if (empty($descripcion)) {
            $descripcion = null;
        }
        if (empty($motivoTexto)) {
            $motivoTexto = null;
        }
        
        // Validate that at least one field has content (predefinida, tipo, descripcion, or motivo)
        $hasPredefinida = !empty($idObservacionPredefinida);
        $hasTipo = !empty($tipo) && $tipo !== 'General';
        $hasDescripcion = !empty($descripcion) && strlen($descripcion) > 0 && !ctype_space($descripcion);
        $hasMotivo = !empty($motivoTexto) && strlen($motivoTexto) > 0 && !ctype_space($motivoTexto);
        
        if (!$hasPredefinida && !$hasTipo && !$hasDescripcion && !$hasMotivo) {
            ResponseHelper::error("Debe completar al menos uno de los campos: Observación Predefinida, Tipo, Descripción o Motivo", null, 400);
        }
        
        if (empty($tipo)) {
            $tipo = 'General'; // Default tipo since it's required in schema
        }
        
        if ($motivoTexto && strlen($motivoTexto) > 500) {
            ResponseHelper::error("El motivo no puede exceder 500 caracteres", null, 400);
        }
        
        try {
            $db = $database->getConnection();
            $db->beginTransaction();
            
            $insertQuery = "INSERT INTO observacion (id_docente, id_observacion_predefinida, tipo, descripcion, motivo_texto) 
                          VALUES (:id_docente, :id_observacion_predefinida, :tipo, :descripcion, :motivo_texto)";
            $stmt = $db->prepare($insertQuery);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->bindParam(':id_observacion_predefinida', $idObservacionPredefinida, PDO::PARAM_INT);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':motivo_texto', $motivoTexto, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al crear la observación");
            }
            
            $db->commit();
            ResponseHelper::success('Observación creada exitosamente');
            
        } catch (Exception $e) {
            $database->getConnection()->rollback();
            throw $e;
        }
    } elseif ($action === 'update_observacion') {
        $idObservacion = $_POST['id_observacion'] ?? null;
        $idObservacionPredefinida = !empty($_POST['id_observacion_predefinida']) ? $_POST['id_observacion_predefinida'] : null;
        $tipo = trim($_POST['tipo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $motivoTexto = trim($_POST['motivo_texto'] ?? '');
        
        if (!$idObservacion) {
            ResponseHelper::error("ID de observación es requerido", null, 400);
        }
        
        // Check if "Otro" is selected (RF034)
        $isOtroSelected = false;
        if ($idObservacionPredefinida) {
            try {
                $otroQuery = "SELECT texto FROM observacion_predefinida WHERE id_observacion_predefinida = :id AND LOWER(TRIM(texto)) = 'otro'";
                $otroStmt = $database->getConnection()->prepare($otroQuery);
                $otroStmt->bindParam(':id', $idObservacionPredefinida, PDO::PARAM_INT);
                $otroStmt->execute();
                $isOtroSelected = $otroStmt->fetch() !== false;
            } catch (Exception $e) {
                error_log("Error checking 'Otro' observation: " . $e->getMessage());
            }
        }
        
        // If "Otro" is selected, motivo_texto is required
        if ($isOtroSelected) {
            if (empty($motivoTexto) || strlen($motivoTexto) === 0 || ctype_space($motivoTexto)) {
                ResponseHelper::error("Al seleccionar 'Otro', debe completar el campo 'Motivo / Texto libre' con la descripción de la observación", null, 400);
            }
        }
        
        // Trim and normalize empty strings to null
        if (empty($descripcion)) {
            $descripcion = null;
        }
        if (empty($motivoTexto)) {
            $motivoTexto = null;
        }
        
        // Validate that at least one field has content (predefinida, tipo, descripcion, or motivo)
        $hasPredefinida = !empty($idObservacionPredefinida);
        $hasTipo = !empty($tipo) && $tipo !== 'General';
        $hasDescripcion = !empty($descripcion) && strlen($descripcion) > 0 && !ctype_space($descripcion);
        $hasMotivo = !empty($motivoTexto) && strlen($motivoTexto) > 0 && !ctype_space($motivoTexto);
        
        if (!$hasPredefinida && !$hasTipo && !$hasDescripcion && !$hasMotivo) {
            ResponseHelper::error("Debe completar al menos uno de los campos: Observación Predefinida, Tipo, Descripción o Motivo", null, 400);
        }
        
        if (empty($tipo)) {
            $tipo = 'General'; // Default tipo since it's required in schema
        }
        
        if ($motivoTexto && strlen($motivoTexto) > 500) {
            ResponseHelper::error("El motivo no puede exceder 500 caracteres", null, 400);
        }
        
        try {
            $db = $database->getConnection();
            $db->beginTransaction();
            
            $updateQuery = "UPDATE observacion 
                          SET id_observacion_predefinida = :id_observacion_predefinida, 
                              tipo = :tipo, 
                              descripcion = :descripcion, 
                              motivo_texto = :motivo_texto
                          WHERE id_observacion = :id_observacion";
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(':id_observacion', $idObservacion, PDO::PARAM_INT);
            $stmt->bindParam(':id_observacion_predefinida', $idObservacionPredefinida, PDO::PARAM_INT);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':motivo_texto', $motivoTexto, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar la observación");
            }
            
            $db->commit();
            ResponseHelper::success('Observación actualizada exitosamente');
            
        } catch (Exception $e) {
            $database->getConnection()->rollback();
            throw $e;
        }
    } elseif ($action === 'delete_observacion') {
        $idObservacion = $_POST['id_observacion'] ?? null;
        
        if (!$idObservacion) {
            ResponseHelper::error("ID de observación es requerido", null, 400);
        }
        
        try {
            $db = $database->getConnection();
            $db->beginTransaction();
            
            $deleteQuery = "DELETE FROM observacion WHERE id_observacion = :id_observacion";
            $stmt = $db->prepare($deleteQuery);
            $stmt->bindParam(':id_observacion', $idObservacion, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al eliminar la observación");
            }
            
            $db->commit();
            ResponseHelper::success('Observación eliminada exitosamente');
            
        } catch (Exception $e) {
            $database->getConnection()->rollback();
            throw $e;
        }
    } elseif ($action === 'get_observacion') {
        $idObservacion = $_GET['id_observacion'] ?? null;
        
        if (!$idObservacion) {
            ResponseHelper::error("ID de observación es requerido", null, 400);
        }
        
        try {
            $db = $database->getConnection();
            
            $query = "SELECT id_observacion, id_docente, id_observacion_predefinida, tipo, descripcion, motivo_texto 
                     FROM observacion 
                     WHERE id_observacion = :id_observacion";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_observacion', $idObservacion, PDO::PARAM_INT);
            $stmt->execute();
            
            $observacion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$observacion) {
                ResponseHelper::error("Observación no encontrada", null, 404);
            }
            
            ResponseHelper::jsonSuccess(['observacion' => $observacion], 'Observación obtenida exitosamente');
            
        } catch (Exception $e) {
            throw $e;
        }
    } elseif ($action === 'get_observaciones') {
        $idDocente = $_GET['id_docente'] ?? null;
        
        if (!$idDocente) {
            ResponseHelper::error("ID de docente es requerido", null, 400);
        }
        
        try {
            $db = $database->getConnection();
            
            $query = "SELECT o.id_observacion, o.id_observacion_predefinida, o.tipo, o.descripcion, o.motivo_texto, 
                     op.texto as observacion_predefinida_texto
                     FROM observacion o
                     LEFT JOIN observacion_predefinida op ON o.id_observacion_predefinida = op.id_observacion_predefinida
                     WHERE o.id_docente = :id_docente
                     ORDER BY o.id_observacion DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->execute();
            
            $observaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ResponseHelper::success('Observaciones obtenidas exitosamente', ['observaciones' => $observaciones]);
            
        } catch (Exception $e) {
            throw $e;
        }
    } else {
        ResponseHelper::error("Acción no válida", null, 400);
    }
    
} catch (Exception $e) {
    error_log("Error en admin_disponibilidad_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor: ' . $e->getMessage(), null, 500);
}
