<?php

/**
 * CoordinadorController
 * 
 * This controller handles the business logic for coordinator management.
 */

require_once __DIR__ . '/../models/Coordinador.php';
require_once __DIR__ . '/../helpers/Translation.php';

class CoordinadorController {
    private $coordinadorModel;
    private $translation;
    
    public function __construct($database) {
        $this->coordinadorModel = new Coordinador($database);
        $this->translation = new Translation();
    }
    
    /**
     * Obtener todos los coordinadores
     */
    public function getAllCoordinadores() {
        try {
            $coordinadores = $this->coordinadorModel->getAllCoordinadores();
            
            if ($coordinadores === false) {
                return [
                    'success' => false,
                    'message' => $this->translation->get('error_loading_coordinators')
                ];
            }
            
            return [
                'success' => true,
                'data' => $coordinadores
            ];
        } catch (Exception $e) {
            error_log("Error en CoordinadorController::getAllCoordinadores: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $this->translation->get('error_loading_coordinators')
            ];
        }
    }
    
    /**
     * Obtener coordinador por ID
     */
    public function getCoordinador($id) {
        try {
            $coordinador = $this->coordinadorModel->getCoordinadorById($id);
            
            if (!$coordinador) {
                return [
                    'success' => false,
                    'message' => $this->translation->get('coordinator_not_found')
                ];
            }
            
            // Convertir roles string a array
            $coordinador['roles'] = !empty($coordinador['roles']) ? explode(', ', $coordinador['roles']) : [];
            $coordinador['role_names'] = !empty($coordinador['role_names']) ? explode(', ', $coordinador['role_names']) : [];
            
            return [
                'success' => true,
                'data' => $coordinador
            ];
        } catch (Exception $e) {
            error_log("Error en CoordinadorController::getCoordinador: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $this->translation->get('error_loading_coordinator')
            ];
        }
    }
    
    /**
     * Crear nuevo coordinador
     */
    public function createCoordinador($data) {
        try {
            // Validar datos requeridos
            $requiredFields = ['cedula', 'nombre', 'apellido', 'email', 'contrasena'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => $this->translation->get('all_fields_required')
                    ];
                }
            }
            
            // Validar formato de email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => $this->translation->get('invalid_email')
                ];
            }
            
            // Validar longitud de contraseña
            if (strlen($data['contrasena']) < 6) {
                return [
                    'success' => false,
                    'message' => $this->translation->get('password_too_short')
                ];
            }
            
            $result = $this->coordinadorModel->createCoordinador($data);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => $this->translation->get('coordinator_created_successfully')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $this->translation->get('error_creating_coordinator')
                ];
            }
        } catch (Exception $e) {
            error_log("Error en CoordinadorController::createCoordinador: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualizar coordinador
     */
    public function updateCoordinador($id, $data) {
        try {
            // Validar datos requeridos
            $requiredFields = ['cedula', 'nombre', 'apellido', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => $this->translation->get('all_fields_required')
                    ];
                }
            }
            
            // Validar formato de email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => $this->translation->get('invalid_email')
                ];
            }
            
            // Validar longitud de contraseña si se proporciona
            if (!empty($data['contrasena']) && strlen($data['contrasena']) < 6) {
                return [
                    'success' => false,
                    'message' => $this->translation->get('password_too_short')
                ];
            }
            
            $result = $this->coordinadorModel->updateCoordinador($id, $data);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => $this->translation->get('coordinator_updated_successfully')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $this->translation->get('error_updating_coordinator')
                ];
            }
        } catch (Exception $e) {
            error_log("Error en CoordinadorController::updateCoordinador: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar coordinador
     */
    public function deleteCoordinador($id) {
        try {
            $result = $this->coordinadorModel->deleteCoordinador($id);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => $this->translation->get('coordinator_deleted_successfully')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $this->translation->get('error_deleting_coordinator')
                ];
            }
        } catch (Exception $e) {
            error_log("Error en CoordinadorController::deleteCoordinador: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Buscar coordinadores
     */
    public function searchCoordinadores($searchTerm) {
        try {
            $coordinadores = $this->coordinadorModel->searchCoordinadores($searchTerm);
            
            if ($coordinadores === false) {
                return [
                    'success' => false,
                    'message' => $this->translation->get('error_searching_coordinators')
                ];
            }
            
            return [
                'success' => true,
                'data' => $coordinadores
            ];
        } catch (Exception $e) {
            error_log("Error en CoordinadorController::searchCoordinadores: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $this->translation->get('error_searching_coordinators')
            ];
        }
    }
    
    /**
     * Manejar solicitudes AJAX
     */
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($action) {
            case 'create':
                return $this->createCoordinador($_POST);
                
            case 'get':
                $id = $_POST['id'] ?? $_GET['id'] ?? 0;
                return $this->getCoordinador($id);
                
            case 'update':
                $id = $_POST['id'] ?? 0;
                return $this->updateCoordinador($id, $_POST);
                
            case 'delete':
                $id = $_POST['id'] ?? $_GET['id'] ?? 0;
                return $this->deleteCoordinador($id);
                
            case 'search':
                $searchTerm = $_POST['search'] ?? $_GET['search'] ?? '';
                return $this->searchCoordinadores($searchTerm);
                
            default:
                return [
                    'success' => false,
                    'message' => $this->translation->get('invalid_action')
                ];
        }
    }
}
?>
