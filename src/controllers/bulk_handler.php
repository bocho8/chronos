<?php
/**
 * Bulk Operations Handler
 * Handles bulk operations for multiple selected items
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/Database.php';

initSecureSession();

if (!AuthHelper::isLoggedIn()) {
    ResponseHelper::error('Authentication required');
    exit;
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $action = $input['action'] ?? '';
    $entityType = $input['entity_type'] ?? '';
    $ids = $input['ids'] ?? [];
    
    if (empty($action) || empty($entityType) || empty($ids)) {
        throw new Exception('Missing required parameters');
    }
    
    if (!is_array($ids) || count($ids) === 0) {
        throw new Exception('No items selected');
    }
    
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    $connection = $database->getConnection();
    
    $result = false;
    $message = '';
    
    switch ($action) {
        case 'bulk_delete':
            $result = handleBulkDelete($connection, $entityType, $ids);
            $message = 'Items deleted successfully';
            break;
            
        case 'bulk_update_status':
            $status = $input['status'] ?? '';
            if (empty($status)) {
                throw new Exception('Status parameter required');
            }
            $result = handleBulkUpdateStatus($connection, $entityType, $ids, $status);
            $message = 'Items updated successfully';
            break;
            
        case 'bulk_export':
            $result = handleBulkExport($connection, $entityType, $ids);
            $message = 'Items exported successfully';
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    if ($result) {
        ResponseHelper::success($message, ['count' => count($ids)]);
    } else {
        ResponseHelper::error('Operation failed');
    }
    
} catch (Exception $e) {
    error_log("Bulk operation error: " . $e->getMessage());
    ResponseHelper::error($e->getMessage());
}

/**
 * Handle bulk delete operations
 */
function handleBulkDelete($connection, $entityType, $ids) {
    $tableMap = [
        'usuarios' => 'usuario',
        'materias' => 'materia',
        'grupos' => 'grupo',
        'docentes' => 'docente'
    ];
    
    if (!isset($tableMap[$entityType])) {
        throw new Exception('Invalid entity type');
    }
    
    $table = $tableMap[$entityType];
    $idField = getPrimaryKeyField($entityType);

    $sanitizedIds = array_map('intval', $ids);
    $placeholders = str_repeat('?,', count($sanitizedIds) - 1) . '?';
    
    $sql = "DELETE FROM {$table} WHERE {$idField} IN ({$placeholders})";
    $stmt = $connection->prepare($sql);
    
    try {
        return $stmt->execute($sanitizedIds);
    } catch (PDOException $e) {
        if ($e->getCode() == '23503') {
            $errorMessage = getForeignKeyErrorMessage($e->getMessage(), $entityType);
            throw new Exception($errorMessage);
        }
        throw $e;
    }
}

/**
 * Handle bulk status update operations
 */
function handleBulkUpdateStatus($connection, $entityType, $ids, $status) {
    $tableMap = [
        'usuarios' => 'usuario',
        'materias' => 'materia',
        'grupos' => 'grupo',
        'docentes' => 'docente'
    ];
    
    if (!isset($tableMap[$entityType])) {
        throw new Exception('Invalid entity type');
    }
    
    $table = $tableMap[$entityType];
    $idField = getPrimaryKeyField($entityType);
    $statusField = getStatusField($entityType);
    
    if (!$statusField) {
        throw new Exception('Status field not available for this entity type');
    }

    $sanitizedIds = array_map('intval', $ids);
    $placeholders = str_repeat('?,', count($sanitizedIds) - 1) . '?';
    
    $sql = "UPDATE {$table} SET {$statusField} = ? WHERE {$idField} IN ({$placeholders})";
    $stmt = $connection->prepare($sql);
    
    $params = array_merge([$status], $sanitizedIds);
    return $stmt->execute($params);
}

/**
 * Handle bulk export operations
 */
function handleBulkExport($connection, $entityType, $ids) {
    $tableMap = [
        'usuarios' => 'usuario',
        'materias' => 'materia',
        'grupos' => 'grupo',
        'docentes' => 'docente'
    ];
    
    if (!isset($tableMap[$entityType])) {
        throw new Exception('Invalid entity type');
    }
    
    $table = $tableMap[$entityType];
    $idField = getPrimaryKeyField($entityType);

    $sanitizedIds = array_map('intval', $ids);
    $placeholders = str_repeat('?,', count($sanitizedIds) - 1) . '?';
    
    $sql = "SELECT * FROM {$table} WHERE {$idField} IN ({$placeholders})";
    $stmt = $connection->prepare($sql);
    $stmt->execute($sanitizedIds);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $csv = generateCSV($data, $entityType);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $entityType . '_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    echo $csv;
    exit;
}

/**
 * Get primary key field name for entity type
 */
function getPrimaryKeyField($entityType) {
    $keyMap = [
        'usuarios' => 'id_usuario',
        'materias' => 'id_materia',
        'grupos' => 'id_grupo',
        'docentes' => 'id_docente',
        'coordinadores' => 'id_coordinador'
    ];
    
    return $keyMap[$entityType] ?? 'id';
}

/**
 * Get status field name for entity type
 */
function getStatusField($entityType) {
    $statusMap = [
        'usuarios' => null,
        'materias' => null,
        'grupos' => null,
        'docentes' => null
    ];
    
    return $statusMap[$entityType] ?? null;
}

/**
 * Generate CSV content from data
 */
function generateCSV($data, $entityType) {
    if (empty($data)) {
        return '';
    }
    
    $headers = array_keys($data[0]);
    $csv = '';
    
    $csv .= implode(',', array_map('wrapCSVField', $headers)) . "\n";
    
    foreach ($data as $row) {
        $csv .= implode(',', array_map('wrapCSVField', array_values($row))) . "\n";
    }
    
    return $csv;
}

/**
 * Wrap CSV field in quotes and escape quotes
 */
function wrapCSVField($field) {
    $field = str_replace('"', '""', $field);
    return '"' . $field . '"';
}

/**
 * Get user-friendly error message for foreign key constraint violations
 */
function getForeignKeyErrorMessage($errorMessage, $entityType) {
    require_once __DIR__ . '/../helpers/Translation.php';
    $translation = Translation::getInstance();
    
    if (strpos($errorMessage, 'horario_id_materia_fkey') !== false) {
        return $translation->get('foreign_key_violation_materias');
    }
    
    if (strpos($errorMessage, 'horario_id_grupo_fkey') !== false) {
        return $translation->get('foreign_key_violation_grupos');
    }
    
    if (strpos($errorMessage, 'horario_id_docente_fkey') !== false) {
        return $translation->get('foreign_key_violation_docentes');
    }
    
    if (strpos($errorMessage, 'usuario_rol_id_usuario_fkey') !== false) {
        return $translation->get('foreign_key_violation_usuarios');
    }

    return $translation->get('foreign_key_violation_generic');
}
?>
