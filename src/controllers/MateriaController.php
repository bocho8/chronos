<?php

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../models/Materia.php';

class MateriaController {
    private $db;
    private $materiaModel;
    
    public function __construct($database) {
        $this->db = $database;
        $this->materiaModel = new Materia($database);
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            match ($action) {
                'get' => $this->getMateria(),
                'list' => $this->listMaterias(),
                'create' => $this->createMateria(),
                'update' => $this->updateMateria(),
                'delete' => $this->deleteMateria(),
                'get_pautas' => $this->getPautasAnep(),
                'get_grupos' => $this->getGrupos(),
                default => throw new Exception("Acción no válida: $action")
            };
        } catch (Exception $e) {
            error_log("Error in MateriaController: " . $e->getMessage());
            ResponseHelper::error($e->getMessage());
        }
    }
    
    private function getMateria() {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            ResponseHelper::error("ID de materia requerido");
        }
        
        $materia = $this->materiaModel->getMateriaById($id);
        if (!$materia) {
            ResponseHelper::notFound("Materia");
        }
        
        ResponseHelper::success("Materia obtenida exitosamente", $materia);
    }
    
    private function listMaterias() {
        $materias = $this->materiaModel->getAllMaterias();
        if ($materias === false) {
            ResponseHelper::error("Error al obtener las materias");
        }
        
        ResponseHelper::success("Materias obtenidas exitosamente", $materias);
    }
    
    private function createMateria() {
        $data = $this->validateMateriaData($_POST);
        
        try {
            $this->db->beginTransaction();
            
            $id = $this->materiaModel->createMateria($data);
            if (!$id) {
                throw new Exception("Error al crear la materia");
            }
            
            $this->logActivity("Creó la materia: " . $data['nombre']);
            $this->db->commit();
            
            ResponseHelper::success('Materia creada exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function updateMateria() {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            ResponseHelper::error("ID de materia requerido");
        }
        
        $data = $this->validateMateriaData($_POST, false);
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->materiaModel->updateMateria($id, $data);
            if (!$success) {
                throw new Exception("Error al actualizar la materia");
            }
            
            $this->logActivity("Actualizó la materia ID: $id");
            $this->db->commit();
            
            ResponseHelper::success('Materia actualizada exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function deleteMateria() {
        $id = $_POST['id'] ?? $_POST['id_materia'] ?? null;
        if (!$id) {
            ResponseHelper::error("ID de materia requerido");
        }
        
        try {
            $this->db->beginTransaction();
            
            $materia = $this->materiaModel->getMateriaById($id);
            if (!$materia) {
                throw new Exception("Materia no encontrada");
            }
            
            $success = $this->materiaModel->deleteMateria($id);
            if (!$success) {
                throw new Exception("Error al eliminar la materia");
            }
            
            $this->logActivity("Eliminó la materia: " . $materia['nombre']);
            $this->db->commit();
            
            ResponseHelper::success('Materia eliminada exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function getPautasAnep() {
        $pautas = $this->materiaModel->getAllPautasAnep();
        ResponseHelper::success("Pautas ANEP obtenidas exitosamente", $pautas);
    }
    
    private function getGrupos() {
        $grupos = $this->materiaModel->getAllGrupos();
        ResponseHelper::success("Grupos obtenidos exitosamente", $grupos);
    }
    
    private function validateMateriaData($data, $required = true) {
        $errors = [];
        
        if ($required && empty($data['nombre'])) {
            $errors['nombre'] = "El nombre de la materia es requerido";
        } elseif (!empty($data['nombre'])) {
            $nombre = trim($data['nombre']);
            if (strlen($nombre) > 200) {
                $errors['nombre'] = "El nombre de la materia no puede exceder 200 caracteres";
            }
        }
        
        if (isset($data['horas_semanales'])) {
            $error = ValidationHelper::validateNumericRange($data['horas_semanales'], 'horas_semanales', 1, 40);
            if ($error) {
                $errors['horas_semanales'] = $error;
            }
        }
        
        $validated = [];
        
        if (!empty($data['nombre'])) {
            $validated['nombre'] = trim($data['nombre']);
        }
        
        if (isset($data['horas_semanales'])) {
            $validated['horas_semanales'] = intval($data['horas_semanales']);
        }
        
        if (isset($data['id_pauta_anep'])) {
            $validated['id_pauta_anep'] = intval($data['id_pauta_anep']);
        }
        
        if (isset($data['en_conjunto'])) {
            $validated['en_conjunto'] = filter_var($data['en_conjunto'], FILTER_VALIDATE_BOOLEAN);
        }
        
        if (isset($data['id_grupo_compartido']) && !empty($data['id_grupo_compartido'])) {
            $validated['id_grupo_compartido'] = intval($data['id_grupo_compartido']);
        }
        
        if (isset($data['es_programa_italiano'])) {
            $validated['es_programa_italiano'] = filter_var($data['es_programa_italiano'], FILTER_VALIDATE_BOOLEAN);
        }
        
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }
        
        return $validated;
    }
    
    private function logActivity($accion) {
        try {
            require_once __DIR__ . '/../helpers/AuthHelper.php';
            $user = AuthHelper::getCurrentUser();
            
            if ($user && isset($user['id_usuario'])) {
                $stmt = $this->db->prepare("INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())");
                $stmt->execute([$user['id_usuario'], $accion]);
            }
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
