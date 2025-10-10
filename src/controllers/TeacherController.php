<?php

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../models/Docente.php';

class DocenteController {
    private $docenteModel;
    private $translation;
    
    public function __construct($database) {
        $this->docenteModel = new Docente($database);
        $this->translation = \Translation::getInstance();
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? 'list';
        
        try {
            match ($action) {
                'create' => $this->createDocente(),
                'update' => $this->updateDocente(),
                'delete' => $this->deleteDocente(),
                'get' => $this->getDocente(),
                'search' => $this->searchDocentes(),
                default => $this->listDocentes()
            };
        } catch (Exception $e) {
            error_log("Error in DocenteController: " . $e->getMessage());
            ResponseHelper::error('Error interno del servidor', null, 500);
        }
    }
    
    private function listDocentes() {
        $docentes = $this->docenteModel->getAllDocentes();
        if ($docentes === false) {
            ResponseHelper::error('Error obteniendo lista de docentes');
        }
        ResponseHelper::success('Docentes obtenidos exitosamente', $docentes);
    }
    
    private function getDocente() {
        $id_docente = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id_docente) {
            ResponseHelper::error('ID de docente requerido');
        }
        
        $docente = $this->docenteModel->getDocenteById($id_docente);
        if (!$docente) {
            ResponseHelper::notFound('Docente');
        }
        
        ResponseHelper::success('Docente obtenido exitosamente', $docente);
    }
    
    private function createDocente() {
        $errors = $this->validateDocenteData($_POST);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }
        
        $docenteData = [
            'cedula' => trim($_POST['cedula']),
            'nombre' => trim($_POST['nombre']),
            'apellido' => trim($_POST['apellido']),
            'email' => trim($_POST['email'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'contrasena' => $_POST['contrasena'],
            'fecha_envio_disponibilidad' => $_POST['fecha_envio_disponibilidad'] ?? null,
            'horas_asignadas' => (int)($_POST['horas_asignadas'] ?? 0),
            'porcentaje_margen' => (float)($_POST['porcentaje_margen'] ?? 0.00)
        ];
        
        $docenteId = $this->docenteModel->createDocente($docenteData);
        if ($docenteId === false) {
            ResponseHelper::error('Error creando docente. Verifique que la cédula no esté en uso.');
        }
        
        ResponseHelper::success('Docente creado exitosamente', ['id_docente' => $docenteId]);
    }
    
    private function updateDocente() {
        $id_docente = $_POST['id_docente'] ?? null;
        if (!$id_docente) {
            ResponseHelper::error('ID de docente requerido');
        }
        
        $errors = $this->validateDocenteData($_POST, false);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }
        
        $docenteData = [
            'nombre' => trim($_POST['nombre']),
            'apellido' => trim($_POST['apellido']),
            'email' => trim($_POST['email'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'fecha_envio_disponibilidad' => $_POST['fecha_envio_disponibilidad'] ?? null,
            'horas_asignadas' => (int)($_POST['horas_asignadas'] ?? 0),
            'porcentaje_margen' => (float)($_POST['porcentaje_margen'] ?? 0.00)
        ];
        
        if (!empty($_POST['contrasena'])) {
            $docenteData['contrasena'] = $_POST['contrasena'];
        }
        
        $result = $this->docenteModel->updateDocente($id_docente, $docenteData);
        if ($result === false) {
            ResponseHelper::error('Error actualizando docente');
        }
        
        ResponseHelper::success('Docente actualizado exitosamente');
    }
    
    private function deleteDocente() {
        $id_docente = $_POST['id_docente'] ?? $_GET['id'] ?? null;
        if (!$id_docente) {
            ResponseHelper::error('ID de docente requerido');
        }
        
        $result = $this->docenteModel->deleteDocente($id_docente);
        if ($result === false) {
            ResponseHelper::error('Error eliminando docente');
        }
        
        ResponseHelper::success('Docente eliminado exitosamente');
    }
    
    private function searchDocentes() {
        $searchTerm = $_GET['q'] ?? $_POST['q'] ?? '';
        if (empty($searchTerm)) {
            return $this->listDocentes();
        }
        
        $docentes = $this->docenteModel->searchDocentes($searchTerm);
        if ($docentes === false) {
            ResponseHelper::error('Error buscando docentes');
        }
        
        ResponseHelper::success('Búsqueda completada', $docentes);
    }
    
    private function validateDocenteData($data, $requirePassword = true) {
        $errors = [];
        
        $errors['cedula'] = \ValidationHelper::validateCedula($data['cedula'] ?? '');
        $errors['nombre'] = \ValidationHelper::validateName($data['nombre'] ?? '', 'nombre');
        $errors['apellido'] = \ValidationHelper::validateName($data['apellido'] ?? '', 'apellido');
        $errors['email'] = \ValidationHelper::validateEmail($data['email'] ?? '', false);
        $errors['telefono'] = \ValidationHelper::validatePhone($data['telefono'] ?? '');
        
        if ($requirePassword) {
            $errors['contrasena'] = \ValidationHelper::validatePassword($data['contrasena'] ?? '', true);
        } elseif (!empty($data['contrasena'])) {
            $errors['contrasena'] = \ValidationHelper::validatePassword($data['contrasena'], false);
        }
        
        if (isset($data['horas_asignadas'])) {
            $errors['horas_asignadas'] = \ValidationHelper::validateNumericRange($data['horas_asignadas'], 'horas_asignadas', 0);
        }
        
        if (isset($data['porcentaje_margen'])) {
            $errors['porcentaje_margen'] = \ValidationHelper::validateNumericRange($data['porcentaje_margen'], 'porcentaje_margen', 0, 100);
        }
        
        return array_filter($errors);
    }
    
}
