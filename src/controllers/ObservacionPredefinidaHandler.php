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
    
    $db = $database->getConnection();
    
    // Ensure "Otro" predefined observation exists (RF034)
    $checkOtroQuery = "SELECT id_observacion_predefinida FROM observacion_predefinida WHERE LOWER(TRIM(texto)) = 'otro'";
    $checkOtroStmt = $db->prepare($checkOtroQuery);
    $checkOtroStmt->execute();
    $otroExists = $checkOtroStmt->fetch();
    
    if (!$otroExists) {
        try {
            $db->beginTransaction();
            $insertOtroQuery = "INSERT INTO observacion_predefinida (texto, es_sistema, activa) VALUES ('Otro', TRUE, TRUE)";
            $insertOtroStmt = $db->prepare($insertOtroQuery);
            $insertOtroStmt->execute();
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            // Log but don't fail - "Otro" might already exist from concurrent request
            error_log("Note: Could not create 'Otro' observation (might already exist): " . $e->getMessage());
        }
    }
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if ($action === 'create') {
        $texto = trim($_POST['texto'] ?? '');
        $activa = isset($_POST['activa']) && $_POST['activa'] === '1' ? true : false;
        
        // Validation - check for empty string or whitespace-only
        if (empty($texto) || strlen($texto) === 0 || ctype_space($texto)) {
            ResponseHelper::error("El texto es requerido y no puede estar vacío", null, 400);
        }
        
        if (strlen($texto) > 500) {
            ResponseHelper::error("El texto no puede exceder 500 caracteres", null, 400);
        }
        
        // Check if texto already exists
        try {
            $checkQuery = "SELECT id_observacion_predefinida FROM observacion_predefinida WHERE LOWER(texto) = LOWER(:texto)";
            $checkStmt = $db->prepare($checkQuery);
            if (!$checkStmt) {
                throw new Exception("Error preparando consulta de verificación");
            }
            $checkStmt->bindParam(':texto', $texto, PDO::PARAM_STR);
            
            if (!$checkStmt->execute()) {
                throw new Exception("Error ejecutando consulta de verificación");
            }
            
            if ($checkStmt->fetch()) {
                ResponseHelper::error("Ya existe una observación predefinida con ese texto", null, 400);
            }
        } catch (PDOException $e) {
            error_log("Error en consulta de verificación (create): " . $e->getMessage());
            ResponseHelper::error("Error verificando duplicados", null, 500);
        } catch (Exception $e) {
            error_log("Error en verificación (create): " . $e->getMessage());
            ResponseHelper::error($e->getMessage(), null, 500);
        }
        
        try {
            $db->beginTransaction();
            
            $insertQuery = "INSERT INTO observacion_predefinida (texto, es_sistema, activa) 
                          VALUES (:texto, FALSE, :activa)";
            $stmt = $db->prepare($insertQuery);
            if (!$stmt) {
                throw new Exception("Error preparando consulta de inserción");
            }
            $stmt->bindParam(':texto', $texto, PDO::PARAM_STR);
            $stmt->bindParam(':activa', $activa, PDO::PARAM_BOOL);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Error al crear la observación predefinida: " . ($errorInfo[2] ?? 'Error desconocido'));
            }
            
            $db->commit();
            ResponseHelper::success('Observación predefinida creada exitosamente');
            
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log("Error PDO en create observacion_predefinida: " . $e->getMessage());
            ResponseHelper::error("Error de base de datos al crear la observación predefinida", null, 500);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            throw $e;
        }
        
    } elseif ($action === 'update') {
        $idObservacionPredefinida = $_POST['id_observacion_predefinida'] ?? null;
        $texto = trim($_POST['texto'] ?? '');
        $activa = isset($_POST['activa']) && $_POST['activa'] === '1' ? true : false;
        
        if (!$idObservacionPredefinida) {
            ResponseHelper::error("ID de observación predefinida es requerido", null, 400);
        }
        
        // Validation - check for empty string or whitespace-only
        if (empty($texto) || strlen($texto) === 0 || ctype_space($texto)) {
            ResponseHelper::error("El texto es requerido y no puede estar vacío", null, 400);
        }
        
        if (strlen($texto) > 500) {
            ResponseHelper::error("El texto no puede exceder 500 caracteres", null, 400);
        }
        
        // Check if it's a system observation - protect "Otro" and "Otro liceo"
        $existing = null;
        try {
            $checkQuery = "SELECT texto, es_sistema FROM observacion_predefinida WHERE id_observacion_predefinida = :id";
            $checkStmt = $db->prepare($checkQuery);
            if (!$checkStmt) {
                throw new Exception("Error preparando consulta de verificación");
            }
            $checkStmt->bindParam(':id', $idObservacionPredefinida, PDO::PARAM_INT);
            
            if (!$checkStmt->execute()) {
                throw new Exception("Error ejecutando consulta de verificación");
            }
            
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing) {
                ResponseHelper::error("Observación predefinida no encontrada", null, 404);
            }
        } catch (PDOException $e) {
            error_log("Error en consulta de verificación (update): " . $e->getMessage());
            ResponseHelper::error("Error verificando observación predefinida", null, 500);
        } catch (Exception $e) {
            error_log("Error en verificación (update): " . $e->getMessage());
            ResponseHelper::error($e->getMessage(), null, 500);
        }
        
        // Protect "Otro" and "Otro liceo" - prevent changing texto or disabling for these protected observations
        $existingTextLower = strtolower(trim($existing['texto']));
        $isProtected = ($existingTextLower === 'otro' || $existingTextLower === 'otro liceo');
        
        if ($isProtected) {
            $textoLower = strtolower(trim($texto));
            // Don't allow changing the texto of protected observations
            if ($textoLower !== $existingTextLower) {
                ResponseHelper::error("No se puede modificar el texto de observaciones protegidas ('Otro' y 'Otro liceo')", null, 400);
            }
            // Don't allow disabling protected observations
            if (!$activa) {
                ResponseHelper::error("No se puede desactivar observaciones protegidas ('Otro' y 'Otro liceo')", null, 400);
            }
        }
        
        // Check if texto already exists (except for current one)
        try {
            $checkDuplicateQuery = "SELECT id_observacion_predefinida FROM observacion_predefinida 
                                   WHERE LOWER(texto) = LOWER(:texto) AND id_observacion_predefinida != :id";
            $checkDuplicateStmt = $db->prepare($checkDuplicateQuery);
            if (!$checkDuplicateStmt) {
                throw new Exception("Error preparando consulta de duplicados");
            }
            $checkDuplicateStmt->bindParam(':texto', $texto, PDO::PARAM_STR);
            $checkDuplicateStmt->bindParam(':id', $idObservacionPredefinida, PDO::PARAM_INT);
            
            if (!$checkDuplicateStmt->execute()) {
                throw new Exception("Error ejecutando consulta de duplicados");
            }
            
            if ($checkDuplicateStmt->fetch()) {
                ResponseHelper::error("Ya existe una observación predefinida con ese texto", null, 400);
            }
        } catch (PDOException $e) {
            error_log("Error en consulta de duplicados (update): " . $e->getMessage());
            ResponseHelper::error("Error verificando duplicados", null, 500);
        } catch (Exception $e) {
            error_log("Error en verificación de duplicados (update): " . $e->getMessage());
            ResponseHelper::error($e->getMessage(), null, 500);
        }
        
        try {
            $db->beginTransaction();
            
            $updateQuery = "UPDATE observacion_predefinida 
                          SET texto = :texto, activa = :activa
                          WHERE id_observacion_predefinida = :id_observacion_predefinida";
            $stmt = $db->prepare($updateQuery);
            if (!$stmt) {
                throw new Exception("Error preparando consulta de actualización");
            }
            $stmt->bindParam(':texto', $texto, PDO::PARAM_STR);
            $stmt->bindParam(':activa', $activa, PDO::PARAM_BOOL);
            $stmt->bindParam(':id_observacion_predefinida', $idObservacionPredefinida, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Error al actualizar la observación predefinida: " . ($errorInfo[2] ?? 'Error desconocido'));
            }
            
            $db->commit();
            ResponseHelper::success('Observación predefinida actualizada exitosamente');
            
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log("Error PDO en update observacion_predefinida: " . $e->getMessage());
            ResponseHelper::error("Error de base de datos al actualizar la observación predefinida", null, 500);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            throw $e;
        }
        
    } elseif ($action === 'delete') {
        $idObservacionPredefinida = $_POST['id_observacion_predefinida'] ?? null;
        
        if (!$idObservacionPredefinida) {
            ResponseHelper::error("ID de observación predefinida es requerido", null, 400);
        }
        
        // Check if it's a protected system observation
        try {
            $checkQuery = "SELECT texto, es_sistema FROM observacion_predefinida WHERE id_observacion_predefinida = :id";
            $checkStmt = $db->prepare($checkQuery);
            if (!$checkStmt) {
                throw new Exception("Error preparando consulta de verificación");
            }
            $checkStmt->bindParam(':id', $idObservacionPredefinida, PDO::PARAM_INT);
            
            if (!$checkStmt->execute()) {
                throw new Exception("Error ejecutando consulta de verificación");
            }
            
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing) {
                ResponseHelper::error("Observación predefinida no encontrada", null, 404);
            }
        } catch (PDOException $e) {
            error_log("Error en consulta de verificación (delete): " . $e->getMessage());
            ResponseHelper::error("Error verificando observación predefinida", null, 500);
        } catch (Exception $e) {
            error_log("Error en verificación (delete): " . $e->getMessage());
            ResponseHelper::error($e->getMessage(), null, 500);
        }
        
        // Protect "Otro" and "Otro liceo" from deletion (per RF039)
        if (!$existing) {
            ResponseHelper::error("Observación predefinida no encontrada", null, 404);
        }
        
        $existingTextLower = strtolower(trim($existing['texto']));
        // Protect these texts regardless of es_sistema value
        if ($existingTextLower === 'otro' || $existingTextLower === 'otro liceo') {
            ResponseHelper::error("No se pueden eliminar las observaciones protegidas ('Otro' y 'Otro liceo')", null, 400);
        }
        
        try {
            $db->beginTransaction();
            
            $deleteQuery = "DELETE FROM observacion_predefinida WHERE id_observacion_predefinida = :id_observacion_predefinida";
            $stmt = $db->prepare($deleteQuery);
            if (!$stmt) {
                throw new Exception("Error preparando consulta de eliminación");
            }
            $stmt->bindParam(':id_observacion_predefinida', $idObservacionPredefinida, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Error al eliminar la observación predefinida: " . ($errorInfo[2] ?? 'Error desconocido'));
            }
            
            $db->commit();
            ResponseHelper::success('Observación predefinida eliminada exitosamente');
            
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log("Error PDO en delete observacion_predefinida: " . $e->getMessage());
            
            // Check for foreign key violations
            if (strpos($e->getMessage(), 'foreign key') !== false || strpos($e->getCode(), '23503') !== false) {
                ResponseHelper::error("No se puede eliminar la observación porque está siendo utilizada en otras partes del sistema", null, 400);
            } else {
                ResponseHelper::error("Error de base de datos al eliminar la observación predefinida", null, 500);
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            throw $e;
        }
        
    } elseif ($action === 'get') {
        $idObservacionPredefinida = $_GET['id_observacion_predefinida'] ?? null;
        
        if (!$idObservacionPredefinida) {
            ResponseHelper::error("ID de observación predefinida es requerido", null, 400);
        }
        
        if (!is_numeric($idObservacionPredefinida)) {
            ResponseHelper::error("ID de observación predefinida inválido", null, 400);
        }
        
        try {
            $query = "SELECT id_observacion_predefinida, texto, es_sistema, activa 
                     FROM observacion_predefinida 
                     WHERE id_observacion_predefinida = :id_observacion_predefinida";
            $stmt = $db->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta");
            }
            $stmt->bindParam(':id_observacion_predefinida', $idObservacionPredefinida, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                throw new Exception("Error ejecutando consulta");
            }
            
            $observacion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$observacion) {
                ResponseHelper::error("Observación predefinida no encontrada", null, 404);
            }
            
            ResponseHelper::jsonSuccess(['observacion' => $observacion], 'Observación predefinida obtenida exitosamente');
        } catch (PDOException $e) {
            error_log("Error PDO en get observacion_predefinida: " . $e->getMessage());
            ResponseHelper::error("Error de base de datos al obtener la observación predefinida", null, 500);
        } catch (Exception $e) {
            error_log("Error en get observacion_predefinida: " . $e->getMessage());
            ResponseHelper::error($e->getMessage(), null, 500);
        }
        
    } elseif ($action === 'get_all') {
        try {
            $query = "SELECT id_observacion_predefinida, texto, es_sistema, activa 
                     FROM observacion_predefinida 
                     ORDER BY es_sistema DESC, texto ASC";
            $stmt = $db->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta");
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error ejecutando consulta");
            }
            
            $observaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ResponseHelper::success('Observaciones predefinidas obtenidas exitosamente', ['observaciones' => $observaciones]);
        } catch (PDOException $e) {
            error_log("Error PDO en get_all observacion_predefinida: " . $e->getMessage());
            ResponseHelper::error("Error de base de datos al obtener las observaciones predefinidas", null, 500);
        } catch (Exception $e) {
            error_log("Error en get_all observacion_predefinida: " . $e->getMessage());
            ResponseHelper::error($e->getMessage(), null, 500);
        }
        
    } else {
        ResponseHelper::error("Acción no válida", null, 400);
    }
    
} catch (Exception $e) {
    error_log("Error en observacion_predefinida_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor: ' . $e->getMessage(), null, 500);
}

