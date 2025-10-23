<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Horario.php';

initSecureSession();

// Set JSON response headers
header('Content-Type: application/json');

// Check if user is authenticated
if (!AuthHelper::isLoggedIn()) {
    ResponseHelper::jsonError('No autorizado', 401);
    exit();
}

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    ResponseHelper::jsonError('Sesión expirada', 401);
    exit();
}

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $database = new Database($dbConfig);
    $horarioModel = new Horario($database->getConnection());
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'POST':
            handlePostRequest($action, $horarioModel);
            break;
        case 'GET':
            handleGetRequest($action, $horarioModel);
            break;
        default:
            ResponseHelper::jsonError('Método no permitido', 405);
    }
    
} catch (Exception $e) {
    error_log("PublishRequestHandler error: " . $e->getMessage());
    ResponseHelper::jsonError('Error interno del servidor', 500);
}

function handlePostRequest($action, $horarioModel) {
    switch ($action) {
        case 'create':
            createPublishRequest($horarioModel);
            break;
        case 'approve':
            approvePublishRequest($horarioModel);
            break;
        case 'reject':
            rejectPublishRequest($horarioModel);
            break;
        case 'delete':
            deletePublishedSchedule($horarioModel);
            break;
        default:
            ResponseHelper::jsonError('Acción no válida', 400);
    }
}

function handleGetRequest($action, $horarioModel) {
    switch ($action) {
        case 'status':
            getPublishRequestStatus($horarioModel);
            break;
        default:
            ResponseHelper::jsonError('Acción no válida', 400);
    }
}

function createPublishRequest($horarioModel) {
    // Check if user has permission (Coordinator or Admin)
    if (!AuthHelper::hasRole('COORDINADOR') && !AuthHelper::hasRole('ADMIN')) {
        ResponseHelper::jsonError('No tiene permisos para solicitar publicación', 403);
        return;
    }
    
    $userId = $_SESSION['user']['id_usuario'];
    
    try {
        $requestId = $horarioModel->createPublishRequest($userId);
        
        if ($requestId) {
            ResponseHelper::jsonSuccess([
                'message' => 'Solicitud de publicación creada exitosamente',
                'request_id' => $requestId
            ]);
        } else {
            ResponseHelper::jsonError('Error al crear la solicitud de publicación');
        }
    } catch (Exception $e) {
        ResponseHelper::jsonError($e->getMessage());
    }
}

function getPublishRequestStatus($horarioModel) {
    $status = $horarioModel->getPublishRequestStatus();
    
    ResponseHelper::jsonSuccess([
        'status' => $status,
        'can_request' => AuthHelper::hasRole('COORDINADOR') || AuthHelper::hasRole('ADMIN'),
        'can_approve' => AuthHelper::hasRole('DIRECTOR') || AuthHelper::hasRole('ADMIN')
    ]);
}

function approvePublishRequest($horarioModel) {
    // Check if user has permission (Director or Admin)
    if (!AuthHelper::hasRole('DIRECTOR') && !AuthHelper::hasRole('ADMIN')) {
        ResponseHelper::jsonError('No tiene permisos para aprobar solicitudes', 403);
        return;
    }
    
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $requestId = $input['request_id'] ?? null;
    $userId = $_SESSION['user']['id_usuario'];
    
    if (!$requestId) {
        ResponseHelper::jsonError('ID de solicitud requerido');
        return;
    }
    
    try {
        $result = $horarioModel->approvePublishRequest($requestId, $userId);
        
        if ($result) {
            ResponseHelper::jsonSuccess([
                'message' => 'Solicitud aprobada y horarios publicados exitosamente'
            ]);
        } else {
            ResponseHelper::jsonError('Error al aprobar la solicitud');
        }
    } catch (Exception $e) {
        ResponseHelper::jsonError($e->getMessage());
    }
}

function rejectPublishRequest($horarioModel) {
    // Check if user has permission (Director or Admin)
    if (!AuthHelper::hasRole('DIRECTOR') && !AuthHelper::hasRole('ADMIN')) {
        ResponseHelper::jsonError('No tiene permisos para rechazar solicitudes', 403);
        return;
    }
    
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $requestId = $input['request_id'] ?? null;
    $reason = $input['reason'] ?? '';
    $userId = $_SESSION['user']['id_usuario'];
    
    if (!$requestId) {
        ResponseHelper::jsonError('ID de solicitud requerido');
        return;
    }
    
    try {
        $result = $horarioModel->rejectPublishRequest($requestId, $userId, $reason);
        
        if ($result) {
            ResponseHelper::jsonSuccess([
                'message' => 'Solicitud rechazada exitosamente'
            ]);
        } else {
            ResponseHelper::jsonError('Error al rechazar la solicitud');
        }
    } catch (Exception $e) {
        ResponseHelper::jsonError($e->getMessage());
    }
}

function deletePublishedSchedule($horarioModel) {
    // Check if user has permission (Director or Admin)
    if (!AuthHelper::hasRole('DIRECTOR') && !AuthHelper::hasRole('ADMIN')) {
        ResponseHelper::jsonError('No tiene permisos para eliminar horarios publicados', 403);
        return;
    }
    
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $publicationId = $input['publication_id'] ?? null;
    
    if (!$publicationId) {
        ResponseHelper::jsonError('ID de publicación requerido');
        return;
    }
    
    $userId = $_SESSION['user']['id_usuario'];
    
    try {
        $result = $horarioModel->deletePublishedSchedule($publicationId, $userId);
        
        if ($result) {
            ResponseHelper::jsonSuccess([
                'message' => 'Horarios publicados eliminados exitosamente'
            ]);
        } else {
            ResponseHelper::jsonError('Error al eliminar los horarios publicados');
        }
    } catch (Exception $e) {
        ResponseHelper::jsonError($e->getMessage());
    }
}
