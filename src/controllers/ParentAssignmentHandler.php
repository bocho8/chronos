<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Parent Assignment Handler
 * Manejador para operaciones AJAX de asignación de padres a grupos
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Padre.php';

initSecureSession();

AuthHelper::requireRole(['ADMIN', 'DIRECTOR', 'COORDINADOR']);

if (!AuthHelper::checkSessionTimeout()) {
    ResponseHelper::error('Sesión expirada', 401);
    exit();
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    $padreModel = new Padre($database->getConnection());
    
    switch ($action) {
        case 'assign_groups':
            handleAssignGroups($padreModel);
            break;
        case 'remove_group':
            handleRemoveGroup($padreModel);
            break;
        case 'get_parent_groups':
            handleGetParentGroups($padreModel);
            break;
        case 'get_all_assignments':
            handleGetAllAssignments($padreModel);
            break;
        default:
            ResponseHelper::error('Acción no válida', 400);
    }
} catch (Exception $e) {
    error_log("Error in parent_assignment_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor', 500);
}

/**
 * Handle assign groups to parent
 */
function handleAssignGroups($padreModel) {
    $id_padre = $_POST['id_padre'] ?? '';
    $id_grupos = $_POST['id_grupos'] ?? [];
    $replace = $_POST['replace'] ?? false;
    
    if (empty($id_padre) || !is_numeric($id_padre)) {
        ResponseHelper::error('ID de padre inválido');
        return;
    }
    
    if (empty($id_grupos) || !is_array($id_grupos)) {
        ResponseHelper::error('Debe seleccionar al menos un grupo');
        return;
    }
    
    // If replace mode, first remove all existing assignments
    if ($replace) {
        $currentGroups = $padreModel->getGroupsForParent($id_padre);
        if ($currentGroups !== false) {
            foreach ($currentGroups as $group) {
                $padreModel->removeGroupFromParent($id_padre, $group['id_grupo']);
            }
        }
    }
    
    $successCount = 0;
    $errors = [];
    
    foreach ($id_grupos as $id_grupo) {
        if (!is_numeric($id_grupo)) {
            $errors[] = "ID de grupo inválido: $id_grupo";
            continue;
        }
        
        // Skip if already assigned (unless in replace mode, which was already cleared)
        if (!$replace && $padreModel->hasAccessToGroup($id_padre, $id_grupo)) {
            $successCount++;
            continue;
        }
        
        $result = $padreModel->assignGroupToParent($id_padre, $id_grupo);
        if ($result) {
            $successCount++;
        } else {
            $errors[] = "Error asignando grupo $id_grupo";
        }
    }
    
    if ($successCount > 0) {
        $message = "Se asignaron $successCount grupos exitosamente";
        if (!empty($errors)) {
            $message .= ". Errores: " . implode(', ', $errors);
        }
        ResponseHelper::success($message);
    } else {
        ResponseHelper::error('No se pudo asignar ningún grupo. Errores: ' . implode(', ', $errors));
    }
}

/**
 * Handle remove group from parent
 */
function handleRemoveGroup($padreModel) {
    $id_padre = $_POST['id_padre'] ?? '';
    $id_grupo = $_POST['id_grupo'] ?? '';
    
    if (empty($id_padre) || !is_numeric($id_padre)) {
        ResponseHelper::error('ID de padre inválido');
        return;
    }
    
    if (empty($id_grupo) || !is_numeric($id_grupo)) {
        ResponseHelper::error('ID de grupo inválido');
        return;
    }
    
    $result = $padreModel->removeGroupFromParent($id_padre, $id_grupo);
    
    if ($result) {
        ResponseHelper::success('Grupo removido exitosamente');
    } else {
        ResponseHelper::error('Error removiendo grupo');
    }
}

/**
 * Handle get parent groups
 */
function handleGetParentGroups($padreModel) {
    $id_padre = $_GET['id_padre'] ?? $_POST['id_padre'] ?? '';
    
    if (empty($id_padre) || !is_numeric($id_padre)) {
        ResponseHelper::error('ID de padre inválido');
        return;
    }
    
    $grupos = $padreModel->getGroupsForParent($id_padre);
    
    if ($grupos !== false) {
        ResponseHelper::success('Grupos obtenidos exitosamente', $grupos);
    } else {
        ResponseHelper::error('Error obteniendo grupos del padre');
    }
}

/**
 * Handle get all assignments
 */
function handleGetAllAssignments($padreModel) {
    $padres = $padreModel->getAllParentsWithGroups();
    
    if ($padres !== false) {
        ResponseHelper::success('Asignaciones obtenidas exitosamente', $padres);
    } else {
        ResponseHelper::error('Error obteniendo asignaciones');
    }
}
?>
