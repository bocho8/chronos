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

AuthHelper::requireRole('DOCENTE');

if (!AuthHelper::checkSessionTimeout()) {
    ResponseHelper::error('Sesión expirada', null, 401);
}

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    
    if (!$database->testConnection()) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    $currentUser = AuthHelper::getCurrentUser();
    $userId = $currentUser['id_usuario'] ?? null;
    
    if (!$userId) {
        ResponseHelper::error('Usuario no encontrado', null, 400);
    }
    
    // Obtener ID del docente
    $docenteQuery = "SELECT id_docente FROM docente WHERE id_usuario = :id_usuario";
    $stmt = $database->getConnection()->prepare($docenteQuery);
    $stmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $docenteId = $stmt->fetchColumn();
    
    if (!$docenteId) {
        ResponseHelper::error('Docente no encontrado', null, 400);
    }
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if ($action === 'create_observacion') {
        $idObservacionPredefinida = !empty($_POST['id_observacion_predefinida']) ? (int)$_POST['id_observacion_predefinida'] : null;
        $tipo = $_POST['tipo'] ?? 'General';
        $descripcion = trim($_POST['descripcion'] ?? '');
        $motivoTexto = trim($_POST['motivo_texto'] ?? '');
        
        // Check if "Otro" is selected
        $isOtroSelected = false;
        if ($idObservacionPredefinida) {
            $checkOtroQuery = "SELECT LOWER(TRIM(texto)) as texto FROM observacion_predefinida WHERE id_observacion_predefinida = :id";
            $checkOtroStmt = $database->getConnection()->prepare($checkOtroQuery);
            $checkOtroStmt->bindParam(':id', $idObservacionPredefinida, PDO::PARAM_INT);
            $checkOtroStmt->execute();
            $predefResult = $checkOtroStmt->fetch(PDO::FETCH_ASSOC);
            $isOtroSelected = $predefResult && $predefResult['texto'] === 'otro';
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
        
        // Validate that at least one field has content
        $hasPredefinida = !empty($idObservacionPredefinida);
        $hasTipo = !empty($tipo) && $tipo !== 'General';
        $hasDescripcion = !empty($descripcion) && strlen($descripcion) > 0 && !ctype_space($descripcion);
        $hasMotivo = !empty($motivoTexto) && strlen($motivoTexto) > 0 && !ctype_space($motivoTexto);
        
        if (!$hasPredefinida && !$hasTipo && !$hasDescripcion && !$hasMotivo) {
            ResponseHelper::error("Debe completar al menos uno de los campos: Observación Predefinida, Tipo, Descripción o Motivo", null, 400);
        }
        
        if (empty($tipo)) {
            $tipo = 'General';
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
            $stmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
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
        
        if (!$idObservacion) {
            ResponseHelper::error("ID de observación es requerido", null, 400);
        }
        
        // Verify that the observation belongs to this teacher
        $verifyQuery = "SELECT id_docente FROM observacion WHERE id_observacion = :id_observacion";
        $verifyStmt = $database->getConnection()->prepare($verifyQuery);
        $verifyStmt->bindParam(':id_observacion', $idObservacion, PDO::PARAM_INT);
        $verifyStmt->execute();
        $observation = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$observation) {
            ResponseHelper::error("Observación no encontrada", null, 404);
        }
        
        if ($observation['id_docente'] != $docenteId) {
            ResponseHelper::error("No tiene permisos para modificar esta observación", null, 403);
        }
        
        $idObservacionPredefinida = !empty($_POST['id_observacion_predefinida']) ? (int)$_POST['id_observacion_predefinida'] : null;
        $tipo = $_POST['tipo'] ?? 'General';
        $descripcion = trim($_POST['descripcion'] ?? '');
        $motivoTexto = trim($_POST['motivo_texto'] ?? '');
        
        // Check if "Otro" is selected
        $isOtroSelected = false;
        if ($idObservacionPredefinida) {
            $checkOtroQuery = "SELECT LOWER(TRIM(texto)) as texto FROM observacion_predefinida WHERE id_observacion_predefinida = :id";
            $checkOtroStmt = $database->getConnection()->prepare($checkOtroQuery);
            $checkOtroStmt->bindParam(':id', $idObservacionPredefinida, PDO::PARAM_INT);
            $checkOtroStmt->execute();
            $predefResult = $checkOtroStmt->fetch(PDO::FETCH_ASSOC);
            $isOtroSelected = $predefResult && $predefResult['texto'] === 'otro';
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
        
        // Validate that at least one field has content
        $hasPredefinida = !empty($idObservacionPredefinida);
        $hasTipo = !empty($tipo) && $tipo !== 'General';
        $hasDescripcion = !empty($descripcion) && strlen($descripcion) > 0 && !ctype_space($descripcion);
        $hasMotivo = !empty($motivoTexto) && strlen($motivoTexto) > 0 && !ctype_space($motivoTexto);
        
        if (!$hasPredefinida && !$hasTipo && !$hasDescripcion && !$hasMotivo) {
            ResponseHelper::error("Debe completar al menos uno de los campos: Observación Predefinida, Tipo, Descripción o Motivo", null, 400);
        }
        
        if (empty($tipo)) {
            $tipo = 'General';
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
                          WHERE id_observacion = :id_observacion AND id_docente = :id_docente";
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(':id_observacion', $idObservacion, PDO::PARAM_INT);
            $stmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
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
        
        // Verify that the observation belongs to this teacher
        $verifyQuery = "SELECT id_docente FROM observacion WHERE id_observacion = :id_observacion";
        $verifyStmt = $database->getConnection()->prepare($verifyQuery);
        $verifyStmt->bindParam(':id_observacion', $idObservacion, PDO::PARAM_INT);
        $verifyStmt->execute();
        $observation = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$observation) {
            ResponseHelper::error("Observación no encontrada", null, 404);
        }
        
        if ($observation['id_docente'] != $docenteId) {
            ResponseHelper::error("No tiene permisos para eliminar esta observación", null, 403);
        }
        
        try {
            $db = $database->getConnection();
            $db->beginTransaction();
            
            $deleteQuery = "DELETE FROM observacion WHERE id_observacion = :id_observacion AND id_docente = :id_docente";
            $stmt = $db->prepare($deleteQuery);
            $stmt->bindParam(':id_observacion', $idObservacion, PDO::PARAM_INT);
            $stmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al eliminar la observación");
            }
            
            $db->commit();
            ResponseHelper::success('Observación eliminada exitosamente');
            
        } catch (Exception $e) {
            $database->getConnection()->rollback();
            throw $e;
        }
    } elseif ($action === 'get_observaciones') {
        try {
            $db = $database->getConnection();
            
            $query = "SELECT o.id_observacion, o.id_observacion_predefinida, o.tipo, o.descripcion, o.motivo_texto, 
                     op.texto as observacion_predefinida_texto
                     FROM observacion o
                     LEFT JOIN observacion_predefinida op ON o.id_observacion_predefinida = op.id_observacion_predefinida
                     WHERE o.id_docente = :id_docente
                     ORDER BY o.id_observacion DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
            $stmt->execute();
            
            $observaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ResponseHelper::success('Observaciones obtenidas exitosamente', ['observaciones' => $observaciones]);
            
        } catch (Exception $e) {
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
                     WHERE id_observacion = :id_observacion AND id_docente = :id_docente";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_observacion', $idObservacion, PDO::PARAM_INT);
            $stmt->bindParam(':id_docente', $docenteId, PDO::PARAM_INT);
            $stmt->execute();
            
            $observacion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$observacion) {
                ResponseHelper::error("Observación no encontrada", null, 404);
            }
            
            ResponseHelper::jsonSuccess(['observacion' => $observacion], 'Observación obtenida exitosamente');
            
        } catch (Exception $e) {
            throw $e;
        }
    } else {
        ResponseHelper::error("Acción no válida", null, 400);
    }
    
} catch (Exception $e) {
    error_log("Error en docente_observacion_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor: ' . $e->getMessage(), null, 500);
}

