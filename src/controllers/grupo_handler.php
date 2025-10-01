<?php
/**
 * Grupo Handler
 * Manejador para operaciones AJAX de grupos
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Grupo.php';

// Initialize secure session
initSecureSession();

// Require authentication and admin role
AuthHelper::requireRole('ADMIN');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    ResponseHelper::sendError('Sesión expirada', 401);
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    // Load database configuration
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
            ResponseHelper::sendError('Acción no válida', 400);
    }
} catch (Exception $e) {
    error_log("Error in grupo_handler: " . $e->getMessage());
    ResponseHelper::sendError('Error interno del servidor', 500);
}

/**
 * Handle create grupo
 */
function handleCreateGrupo($grupoModel) {
    $nombre = trim($_POST['nombre'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    
    // Validate required fields
    if (empty($nombre)) {
        ResponseHelper::sendError('El nombre del grupo es requerido');
        return;
    }
    
    if (empty($nivel)) {
        ResponseHelper::sendError('El nivel es requerido');
        return;
    }
    
    // Validate nombre length
    if (strlen($nombre) > 100) {
        ResponseHelper::sendError('El nombre del grupo no puede exceder 100 caracteres');
        return;
    }
    
    // Validate nivel length
    if (strlen($nivel) > 50) {
        ResponseHelper::sendError('El nivel no puede exceder 50 caracteres');
        return;
    }
    
    $result = $grupoModel->createGrupo($nombre, $nivel);
    
    if ($result['success']) {
        ResponseHelper::sendSuccess($result, 201);
    } else {
        ResponseHelper::sendError($result['message'], 400);
    }
}

/**
 * Handle update grupo
 */
function handleUpdateGrupo($grupoModel) {
    $id = $_POST['id'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    
    // Validate required fields
    if (empty($id) || !is_numeric($id)) {
        ResponseHelper::sendError('ID de grupo inválido');
        return;
    }
    
    if (empty($nombre)) {
        ResponseHelper::sendError('El nombre del grupo es requerido');
        return;
    }
    
    if (empty($nivel)) {
        ResponseHelper::sendError('El nivel es requerido');
        return;
    }
    
    // Validate nombre length
    if (strlen($nombre) > 100) {
        ResponseHelper::sendError('El nombre del grupo no puede exceder 100 caracteres');
        return;
    }
    
    // Validate nivel length
    if (strlen($nivel) > 50) {
        ResponseHelper::sendError('El nivel no puede exceder 50 caracteres');
        return;
    }
    
    $result = $grupoModel->updateGrupo($id, $nombre, $nivel);
    
    if ($result['success']) {
        ResponseHelper::sendSuccess($result);
    } else {
        ResponseHelper::sendError($result['message'], 400);
    }
}

/**
 * Handle delete grupo
 */
function handleDeleteGrupo($grupoModel) {
    $id = $_POST['id'] ?? $_GET['id'] ?? '';
    
    if (empty($id) || !is_numeric($id)) {
        ResponseHelper::sendError('ID de grupo inválido');
        return;
    }
    
    $result = $grupoModel->deleteGrupo($id);
    
    if ($result['success']) {
        ResponseHelper::sendSuccess($result);
    } else {
        ResponseHelper::sendError($result['message'], 400);
    }
}

/**
 * Handle get grupo by ID
 */
function handleGetGrupo($grupoModel) {
    $id = $_GET['id'] ?? '';
    
    if (empty($id) || !is_numeric($id)) {
        ResponseHelper::sendError('ID de grupo inválido');
        return;
    }
    
    $grupo = $grupoModel->getGrupoById($id);
    
    if ($grupo !== false) {
        if ($grupo) {
            ResponseHelper::sendSuccess($grupo);
        } else {
            ResponseHelper::sendError('Grupo no encontrado', 404);
        }
    } else {
        ResponseHelper::sendError('Error al obtener grupo');
    }
}

/**
 * Handle get all grupos
 */
function handleGetAllGrupos($grupoModel) {
    $grupos = $grupoModel->getAllGrupos();
    
    if ($grupos !== false) {
        ResponseHelper::sendSuccess($grupos);
    } else {
        ResponseHelper::sendError('Error al obtener grupos');
    }
}

/**
 * Handle search grupos
 */
function handleSearchGrupos($grupoModel) {
    $searchTerm = $_GET['q'] ?? $_POST['q'] ?? '';
    
    if (empty($searchTerm)) {
        ResponseHelper::sendError('Término de búsqueda requerido');
        return;
    }
    
    $grupos = $grupoModel->searchGrupos($searchTerm);
    
    if ($grupos !== false) {
        ResponseHelper::sendSuccess($grupos);
    } else {
        ResponseHelper::sendError('Error al buscar grupos');
    }
}

/**
 * Handle get grupos by nivel
 */
function handleGetGruposByNivel($grupoModel) {
    $nivel = $_GET['nivel'] ?? $_POST['nivel'] ?? '';
    
    if (empty($nivel)) {
        ResponseHelper::sendError('Nivel requerido');
        return;
    }
    
    $grupos = $grupoModel->getGruposByNivel($nivel);
    
    if ($grupos !== false) {
        ResponseHelper::sendSuccess($grupos);
    } else {
        ResponseHelper::sendError('Error al obtener grupos por nivel');
    }
}

/**
 * Handle get stats
 */
function handleGetStats($grupoModel) {
    $stats = $grupoModel->getGruposStats();
    
    if ($stats !== false) {
        ResponseHelper::sendSuccess($stats);
    } else {
        ResponseHelper::sendError('Error al obtener estadísticas');
    }
}
