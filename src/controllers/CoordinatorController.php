<?php

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../models/Coordinador.php';

class CoordinadorController {
    private $coordinadorModel;
    private $translation;
    
    public function __construct($database) {
        $this->coordinadorModel = new Coordinador($database);
        $this->translation = \Translation::getInstance();
    }
    
    public function getAllCoordinadores() {
        $coordinadores = $this->coordinadorModel->getAllCoordinadores();
        if ($coordinadores === false) {
            ResponseHelper::error($this->translation->get('error_loading_coordinators'));
        }
        ResponseHelper::success('Coordinadores obtenidos exitosamente', $coordinadores);
    }
    
    public function getCoordinador($id) {
        $coordinador = $this->coordinadorModel->getCoordinadorById($id);
        if (!$coordinador) {
            ResponseHelper::notFound('Coordinador');
        }
        
        $coordinador['roles'] = !empty($coordinador['roles']) ? explode(', ', $coordinador['roles']) : [];
        $coordinador['role_names'] = !empty($coordinador['role_names']) ? explode(', ', $coordinador['role_names']) : [];
        
        ResponseHelper::success('Coordinador obtenido exitosamente', $coordinador);
    }
    
    public function createCoordinador($data) {
        $errors = [];
        
        $errors['cedula'] = \ValidationHelper::validateCedula($data['cedula'] ?? '');
        $errors['nombre'] = \ValidationHelper::validateName($data['nombre'] ?? '', 'nombre');
        $errors['apellido'] = \ValidationHelper::validateName($data['apellido'] ?? '', 'apellido');
        $errors['email'] = \ValidationHelper::validateEmail($data['email'] ?? '', true);
        $errors['contrasena'] = \ValidationHelper::validatePassword($data['contrasena'] ?? '', true);
        
        $errors = array_filter($errors);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }
        
        $result = $this->coordinadorModel->createCoordinador($data);
        if (!$result) {
            ResponseHelper::error($this->translation->get('error_creating_coordinator'));
        }
        
        ResponseHelper::success($this->translation->get('coordinator_created_successfully'));
    }
    
    public function updateCoordinador($id, $data) {
        $errors = [];
        
        $errors['cedula'] = \ValidationHelper::validateCedula($data['cedula'] ?? '');
        $errors['nombre'] = \ValidationHelper::validateName($data['nombre'] ?? '', 'nombre');
        $errors['apellido'] = \ValidationHelper::validateName($data['apellido'] ?? '', 'apellido');
        $errors['email'] = \ValidationHelper::validateEmail($data['email'] ?? '', true);
        
        if (!empty($data['contrasena'])) {
            $errors['contrasena'] = \ValidationHelper::validatePassword($data['contrasena'], false);
        }
        
        $errors = array_filter($errors);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }
        
        $result = $this->coordinadorModel->updateCoordinador($id, $data);
        if (!$result) {
            ResponseHelper::error($this->translation->get('error_updating_coordinator'));
        }
        
        ResponseHelper::success($this->translation->get('coordinator_updated_successfully'));
    }
    
    public function deleteCoordinador($id) {
        $result = $this->coordinadorModel->deleteCoordinador($id);
        if (!$result) {
            ResponseHelper::error($this->translation->get('error_deleting_coordinator'));
        }
        
        ResponseHelper::success($this->translation->get('coordinator_deleted_successfully'));
    }
    
    public function searchCoordinadores($searchTerm) {
        $coordinadores = $this->coordinadorModel->searchCoordinadores($searchTerm);
        if ($coordinadores === false) {
            ResponseHelper::error($this->translation->get('error_searching_coordinators'));
        }
        
        ResponseHelper::success('Búsqueda completada', $coordinadores);
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            match ($action) {
                'create' => $this->createCoordinador($_POST),
                'get' => $this->getCoordinador($_POST['id'] ?? $_GET['id'] ?? 0),
                'update' => $this->updateCoordinador($_POST['id'] ?? 0, $_POST),
                'delete' => $this->deleteCoordinador($_POST['id'] ?? $_GET['id'] ?? 0),
                'search' => $this->searchCoordinadores($_POST['search'] ?? $_GET['search'] ?? ''),
                default => ResponseHelper::error($this->translation->get('invalid_action'))
            };
        } catch (Exception $e) {
            error_log("Error in CoordinadorController: " . $e->getMessage());
            ResponseHelper::error('Error interno del servidor', null, 500);
        }
    }
}
?>
