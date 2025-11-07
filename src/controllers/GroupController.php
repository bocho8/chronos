<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Grupo Controller
 * Controlador para manejar operaciones de grupos
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Grupo.php';

initSecureSession();

AuthHelper::requireRole(['ADMIN', 'DIRECTOR', 'COORDINADOR']);

if (!AuthHelper::checkSessionTimeout()) {
    ResponseHelper::sendError('Sesión expirada', 401);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    $grupoModel = new Grupo($database->getConnection());
    
    switch ($method) {
        case 'GET':
            handleGetRequest($grupoModel, $action);
            break;
        case 'POST':
            handlePostRequest($grupoModel, $action);
            break;
        case 'PUT':
            handlePutRequest($grupoModel, $action);
            break;
        case 'DELETE':
            handleDeleteRequest($grupoModel, $action);
            break;
        default:
            ResponseHelper::sendError('Método no permitido', 405);
    }
} catch (Exception $e) {
    error_log("Error in GrupoController: " . $e->getMessage());
    ResponseHelper::sendError('Error interno del servidor', 500);
}

/**
 * Handle GET requests
 */
function handleGetRequest($grupoModel, $action) {
    switch ($action) {
        case 'get_all':
            $grupos = $grupoModel->getAllGrupos();
            if ($grupos !== false) {
                ResponseHelper::sendSuccess($grupos);
            } else {
                ResponseHelper::sendError('Error al obtener grupos');
            }
            break;
            
        case 'get_by_id':
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
            break;
            
        case 'get_by_nivel':
            $nivel = $_GET['nivel'] ?? '';
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
            break;
            
        case 'search':
            $searchTerm = $_GET['q'] ?? '';
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
            break;
            
        case 'stats':
            $stats = $grupoModel->getGruposStats();
            if ($stats !== false) {
                ResponseHelper::sendSuccess($stats);
            } else {
                ResponseHelper::sendError('Error al obtener estadísticas');
            }
            break;
            
        default:
            ResponseHelper::sendError('Acción no válida', 400);
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($grupoModel, $action) {
    switch ($action) {
        case 'create':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                ResponseHelper::sendError('Datos inválidos');
                return;
            }
            
            $nombre = trim($data['nombre'] ?? '');
            $nivel = trim($data['nivel'] ?? '');
            
            if (empty($nombre)) {
                ResponseHelper::sendError('El nombre del grupo es requerido');
                return;
            }
            
            if (empty($nivel)) {
                ResponseHelper::sendError('El nivel es requerido');
                return;
            }
            
            if (strlen($nombre) > 100) {
                ResponseHelper::sendError('El nombre del grupo no puede exceder 100 caracteres');
                return;
            }
            
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
            break;
            
        default:
            ResponseHelper::sendError('Acción no válida', 400);
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($grupoModel, $action) {
    switch ($action) {
        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                ResponseHelper::sendError('Datos inválidos');
                return;
            }
            
            $id = $data['id'] ?? '';
            $nombre = trim($data['nombre'] ?? '');
            $nivel = trim($data['nivel'] ?? '');
            
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
            
            if (strlen($nombre) > 100) {
                ResponseHelper::sendError('El nombre del grupo no puede exceder 100 caracteres');
                return;
            }
            
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
            break;
            
        default:
            ResponseHelper::sendError('Acción no válida', 400);
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($grupoModel, $action) {
    switch ($action) {
        case 'delete':
            $id = $_GET['id'] ?? '';
            
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
            break;
            
        default:
            ResponseHelper::sendError('Acción no válida', 400);
    }
}
