<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Grupo Handler
 * Manejador para operaciones AJAX de grupos
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Grupo.php';

initSecureSession();

AuthHelper::requireRole('ADMIN');

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
        case 'create':
            handleCreateGrupo($grupoModel);
            break;
        case 'update':
            handleUpdateGrupo($grupoModel);
            break;
        case 'delete':
            handleDeleteGrupo($grupoModel);
            break;
        case 'get':
            handleGetGrupo($grupoModel);
            break;
        case 'get_all':
            handleGetAllGrupos($grupoModel);
            break;
        case 'search':
            handleSearchGrupos($grupoModel);
            break;
        case 'get_by_nivel':
            handleGetGruposByNivel($grupoModel);
            break;
        case 'stats':
            handleGetStats($grupoModel);
            break;
        default:
            ResponseHelper::error('Acción no válida', 400);
    }
} catch (Exception $e) {
    error_log("Error in grupo_handler: " . $e->getMessage());
    ResponseHelper::error('Error interno del servidor', 500);
}

/**
 * Handle create grupo
 */
function handleCreateGrupo($grupoModel) {
    $nombre = trim($_POST['nombre'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    
    if (empty($nombre)) {
        ResponseHelper::error('El nombre del grupo es requerido');
        return;
    }
    
    if (empty($nivel)) {
        ResponseHelper::error('El nivel es requerido');
        return;
    }
    
    if (strlen($nombre) > 100) {
        ResponseHelper::error('El nombre del grupo no puede exceder 100 caracteres');
        return;
    }
    
    if (strlen($nivel) > 50) {
        ResponseHelper::error('El nivel no puede exceder 50 caracteres');
        return;
    }
    
    $result = $grupoModel->createGrupo($nombre, $nivel);
    
    if ($result['success']) {
        // Get the created group data
        $grupo = $grupoModel->getGrupoById($result['id']);
        ResponseHelper::success('Grupo creado exitosamente', $grupo);
    } else {
        ResponseHelper::error($result['message'], 400);
    }
}

/**
 * Handle update grupo
 */
function handleUpdateGrupo($grupoModel) {
    $id = $_POST['id'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    
    if (empty($id) || !is_numeric($id)) {
        ResponseHelper::error('ID de grupo inválido');
        return;
    }
    
    if (empty($nombre)) {
        ResponseHelper::error('El nombre del grupo es requerido');
        return;
    }
    
    if (empty($nivel)) {
        ResponseHelper::error('El nivel es requerido');
        return;
    }
    
    if (strlen($nombre) > 100) {
        ResponseHelper::error('El nombre del grupo no puede exceder 100 caracteres');
        return;
    }
    
    if (strlen($nivel) > 50) {
        ResponseHelper::error('El nivel no puede exceder 50 caracteres');
        return;
    }
    
    $result = $grupoModel->updateGrupo($id, $nombre, $nivel);
    
    if ($result['success']) {
        // Get the updated group data
        $grupo = $grupoModel->getGrupoById($id);
        ResponseHelper::success('Grupo actualizado exitosamente', $grupo);
    } else {
        ResponseHelper::error($result['message'], 400);
    }
}

/**
 * Handle delete grupo
 */
function handleDeleteGrupo($grupoModel) {
    $id = $_POST['id'] ?? $_GET['id'] ?? '';

    error_log("Delete grupo request - ID: " . $id . ", Method: " . $_SERVER['REQUEST_METHOD']);
    
    if (empty($id) || !is_numeric($id)) {
        error_log("Invalid grupo ID provided: " . $id);
        ResponseHelper::error('ID de grupo inválido');
        return;
    }
    
    try {
        $result = $grupoModel->deleteGrupo($id);
        
        if ($result['success']) {
            error_log("Grupo deletion successful: " . $id);
            ResponseHelper::success($result);
        } else {
            error_log("Grupo deletion failed: " . $result['message'] . " (ID: " . $id . ")");
            ResponseHelper::error($result['message'], 400);
        }
    } catch (Exception $e) {
        error_log("Exception in handleDeleteGrupo: " . $e->getMessage());
        ResponseHelper::error('Error interno del servidor', 500);
    }
}

/**
 * Handle get grupo by ID
 */
function handleGetGrupo($grupoModel) {
    $id = $_GET['id'] ?? '';
    
    if (empty($id) || !is_numeric($id)) {
        ResponseHelper::error('ID de grupo inválido');
        return;
    }
    
    $grupo = $grupoModel->getGrupoById($id);
    
    if ($grupo !== false) {
        if ($grupo) {
            // Return data in the correct format
            ResponseHelper::success('Grupo obtenido exitosamente', $grupo);
        } else {
            ResponseHelper::error('Grupo no encontrado', 404);
        }
    } else {
        ResponseHelper::error('Error al obtener grupo');
    }
}

/**
 * Handle get all grupos
 */
function handleGetAllGrupos($grupoModel) {
    $grupos = $grupoModel->getAllGrupos();
    
    if ($grupos !== false) {
        ResponseHelper::success($grupos);
    } else {
        ResponseHelper::error('Error al obtener grupos');
    }
}

/**
 * Handle search grupos
 */
function handleSearchGrupos($grupoModel) {
    $searchTerm = $_GET['q'] ?? $_POST['q'] ?? '';
    
    if (empty($searchTerm)) {
        ResponseHelper::error('Término de búsqueda requerido');
        return;
    }
    
    $grupos = $grupoModel->searchGrupos($searchTerm);
    
    if ($grupos !== false) {
        ResponseHelper::success($grupos);
    } else {
        ResponseHelper::error('Error al buscar grupos');
    }
}

/**
 * Handle get grupos by nivel
 */
function handleGetGruposByNivel($grupoModel) {
    $nivel = $_GET['nivel'] ?? $_POST['nivel'] ?? '';
    
    if (empty($nivel)) {
        ResponseHelper::error('Nivel requerido');
        return;
    }
    
    $grupos = $grupoModel->getGruposByNivel($nivel);
    
    if ($grupos !== false) {
        ResponseHelper::success($grupos);
    } else {
        ResponseHelper::error('Error al obtener grupos por nivel');
    }
}

/**
 * Handle get stats
 */
function handleGetStats($grupoModel) {
    $stats = $grupoModel->getGruposStats();
    
    if ($stats !== false) {
        ResponseHelper::success($stats);
    } else {
        ResponseHelper::error('Error al obtener estadísticas');
    }
}
