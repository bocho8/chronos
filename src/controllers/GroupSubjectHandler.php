<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Group Subject Handler
 * Manejador para operaciones AJAX de asignación de materias a grupos
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Grupo.php';

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
    $grupoModel = new Grupo($database->getConnection());
    
    switch ($action) {
        case 'assign_subjects':
            handleAssignSubjects($grupoModel);
            break;
        case 'remove_subject':
            handleRemoveSubject($grupoModel);
            break;
        case 'get_group_subjects':
            handleGetGroupSubjects($grupoModel);
            break;
        case 'get_all_assignments':
            handleGetAllAssignments($grupoModel);
            break;
        default:
            ResponseHelper::error('Acción no válida', 400);
    }
} catch (Exception $e) {
    error_log("Error in group_subject_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor', 500);
}

/**
 * Handle assign subjects to group
 */
function handleAssignSubjects($grupoModel) {
    $id_grupo = $_POST['id_grupo'] ?? '';
    $id_materias = $_POST['id_materias'] ?? [];
    
    if (empty($id_grupo) || !is_numeric($id_grupo)) {
        ResponseHelper::error('ID de grupo inválido');
        return;
    }
    
    if (empty($id_materias) || !is_array($id_materias)) {
        ResponseHelper::error('Debe seleccionar al menos una materia');
        return;
    }
    
    $successCount = 0;
    $errors = [];
    
    foreach ($id_materias as $id_materia) {
        if (!is_numeric($id_materia)) {
            $errors[] = "ID de materia inválido: $id_materia";
            continue;
        }
        
        // Get default hours from the subject
        $horas_semanales = 1; // Default value
        
        $result = $grupoModel->assignSubjectToGroup($id_grupo, $id_materia, $horas_semanales);
        if ($result) {
            $successCount++;
        } else {
            $errors[] = "Error asignando materia $id_materia";
        }
    }
    
    if ($successCount > 0) {
        $message = "Se asignaron $successCount materias exitosamente";
        if (!empty($errors)) {
            $message .= ". Errores: " . implode(', ', $errors);
        }
        ResponseHelper::success($message);
    } else {
        ResponseHelper::error('No se pudo asignar ninguna materia. Errores: ' . implode(', ', $errors));
    }
}

/**
 * Handle remove subject from group
 */
function handleRemoveSubject($grupoModel) {
    $id_grupo = $_POST['id_grupo'] ?? '';
    $id_materia = $_POST['id_materia'] ?? '';
    
    if (empty($id_grupo) || !is_numeric($id_grupo)) {
        ResponseHelper::error('ID de grupo inválido');
        return;
    }
    
    if (empty($id_materia) || !is_numeric($id_materia)) {
        ResponseHelper::error('ID de materia inválido');
        return;
    }
    
    $result = $grupoModel->removeSubjectFromGroup($id_grupo, $id_materia);
    
    if ($result) {
        ResponseHelper::success('Materia removida exitosamente');
    } else {
        ResponseHelper::error('Error removiendo materia');
    }
}

/**
 * Handle get group subjects
 */
function handleGetGroupSubjects($grupoModel) {
    $id_grupo = $_GET['id_grupo'] ?? $_POST['id_grupo'] ?? '';
    
    if (empty($id_grupo) || !is_numeric($id_grupo)) {
        ResponseHelper::error('ID de grupo inválido');
        return;
    }
    
    $materias = $grupoModel->getSubjectsForGroup($id_grupo);
    
    if ($materias !== false) {
        ResponseHelper::success('Materias obtenidas exitosamente', $materias);
    } else {
        ResponseHelper::error('Error obteniendo materias del grupo');
    }
}

/**
 * Handle get all assignments
 */
function handleGetAllAssignments($grupoModel) {
    $grupos = $grupoModel->getAllGroupsWithSubjects();
    
    if ($grupos !== false) {
        ResponseHelper::success('Asignaciones obtenidas exitosamente', $grupos);
    } else {
        ResponseHelper::error('Error obteniendo asignaciones');
    }
}
?>
