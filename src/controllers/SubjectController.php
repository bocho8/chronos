<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../models/Materia.php';

initSecureSession();

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
                'create_pauta' => $this->createPautaAnep(),
                'update_pauta' => $this->updatePautaAnep(),
                'delete_pauta' => $this->deletePautaAnep(),
                default => throw new Exception("Acción no válida: $action")
            };
        } catch (Exception $e) {
            error_log("Error in MateriaController: " . $e->getMessage());
            \ResponseHelper::error($e->getMessage());
        }
    }
    
    private function getMateria() {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            \ResponseHelper::error("ID de materia requerido");
        }
        
        $materia = $this->materiaModel->getMateriaById($id);
        if (!$materia) {
            \ResponseHelper::notFound("Materia");
        }
        
        \ResponseHelper::success("Materia obtenida exitosamente", $materia);
    }
    
    private function listMaterias() {
        $materias = $this->materiaModel->getAllMaterias();
        if ($materias === false) {
            \ResponseHelper::error("Error al obtener las materias");
        }
        
        \ResponseHelper::success("Materias obtenidas exitosamente", $materias);
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
            
            \ResponseHelper::success('Materia creada exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function updateMateria() {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            \ResponseHelper::error("ID de materia requerido");
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
            
            \ResponseHelper::success('Materia actualizada exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function deleteMateria() {
        $id = $_POST['id'] ?? $_POST['id_materia'] ?? null;
        error_log("Delete materia request - ID: " . var_export($id, true));
        error_log("POST data: " . var_export($_POST, true));
        
        if (!$id) {
            error_log("No ID provided for delete materia");
            \ResponseHelper::error("ID de materia requerido");
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            $materia = $this->materiaModel->getMateriaById($id);
            if (!$materia) {
                error_log("Materia not found with ID: " . $id);
                throw new Exception("Materia no encontrada");
            }
            
            error_log("Found materia: " . var_export($materia, true));
            
            $success = $this->materiaModel->deleteMateria($id);
            if (!$success) {
                error_log("Failed to delete materia with ID: " . $id);
                // Check if it's because the materia is in use
                if ($this->materiaModel->materiaInUse($id)) {
                    throw new Exception("No se puede eliminar la materia '{$materia['nombre']}' porque está siendo utilizada en horarios. Primero debe eliminar los horarios asociados.");
                } else {
                    throw new Exception("Error al eliminar la materia");
                }
            }
            
            error_log("Successfully deleted materia with ID: " . $id);
            
            $this->logActivity("Eliminó la materia: " . $materia['nombre']);
            $this->db->commit();
            
            ResponseHelper::success('Materia eliminada exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error deleting materia: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            \ResponseHelper::error($e->getMessage());
            return;
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
    
    private function createPautaAnep() {
        $data = $this->validatePautaAnepData($_POST);
        error_log("Create pauta ANEP request - Data: " . var_export($data, true));
        
        try {
            $this->db->beginTransaction();
            
            $id = $this->materiaModel->createPautaAnep($data);
            if (!$id) {
                error_log("Failed to create pauta ANEP");
                throw new Exception("Error al crear la pauta ANEP");
            }
            
            error_log("Successfully created pauta ANEP with ID: " . $id);
            
            $this->logActivity("Creó la pauta ANEP: " . $data['nombre']);
            $this->db->commit();
            
            \ResponseHelper::success('Pauta ANEP creada exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error creating pauta ANEP: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            \ResponseHelper::error($e->getMessage());
        }
    }
    
    private function updatePautaAnep() {
        $id = $_POST['id'] ?? null;
        error_log("Update pauta ANEP request - ID: " . var_export($id, true));
        
        if (!$id) {
            error_log("No ID provided for update pauta ANEP");
            \ResponseHelper::error("ID de pauta ANEP requerido");
        }
        
        $data = $this->validatePautaAnepData($_POST);
        error_log("Update pauta ANEP data: " . var_export($data, true));
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->materiaModel->updatePautaAnep($id, $data);
            if (!$success) {
                error_log("Failed to update pauta ANEP with ID: " . $id);
                throw new Exception("Error al actualizar la pauta ANEP");
            }
            
            error_log("Successfully updated pauta ANEP with ID: " . $id);
            
            $this->logActivity("Actualizó la pauta ANEP ID: $id");
            $this->db->commit();
            
            \ResponseHelper::success('Pauta ANEP actualizada exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error updating pauta ANEP: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            \ResponseHelper::error($e->getMessage());
        }
    }
    
    private function deletePautaAnep() {
        $id = $_POST['id'] ?? null;
        error_log("Delete pauta ANEP request - ID: " . var_export($id, true));
        error_log("POST data: " . var_export($_POST, true));
        
        if (!$id) {
            error_log("No ID provided for delete pauta ANEP");
            \ResponseHelper::error("ID de pauta ANEP requerido");
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->materiaModel->deletePautaAnep($id);
            if (!$success) {
                error_log("Failed to delete pauta ANEP with ID: " . $id);
                throw new Exception("Error al eliminar la pauta ANEP");
            }
            
            error_log("Successfully deleted pauta ANEP with ID: " . $id);
            
            $this->logActivity("Eliminó la pauta ANEP ID: $id");
            $this->db->commit();
            
            ResponseHelper::success('Pauta ANEP eliminada exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error deleting pauta ANEP: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            \ResponseHelper::error($e->getMessage());
            return;
        }
    }
    
    private function validatePautaAnepData($data) {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors['nombre'] = "El nombre de la pauta es requerido";
        } elseif (strlen(trim($data['nombre'])) > 200) {
            $errors['nombre'] = "El nombre de la pauta no puede exceder 200 caracteres";
        }
        
        if (isset($data['dias_minimos'])) {
            $error = \ValidationHelper::validateNumericRange($data['dias_minimos'], 'dias_minimos', 1, 7);
            if ($error) {
                $errors['dias_minimos'] = $error;
            }
        }
        
        if (isset($data['dias_maximos'])) {
            $error = \ValidationHelper::validateNumericRange($data['dias_maximos'], 'dias_maximos', 1, 7);
            if ($error) {
                $errors['dias_maximos'] = $error;
            }
        }
        
        if (isset($data['dias_minimos']) && isset($data['dias_maximos'])) {
            if ($data['dias_maximos'] < $data['dias_minimos']) {
                $errors['dias_maximos'] = "Los días máximos no pueden ser menores que los días mínimos";
            }
        }
        
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }
        
        $validated = [
            'nombre' => trim($data['nombre']),
            'dias_minimos' => isset($data['dias_minimos']) ? intval($data['dias_minimos']) : 1,
            'dias_maximos' => isset($data['dias_maximos']) ? intval($data['dias_maximos']) : 5,
            'condiciones_especiales' => !empty($data['condiciones_especiales']) ? trim($data['condiciones_especiales']) : null
        ];
        
        return $validated;
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
            $error = \ValidationHelper::validateNumericRange($data['horas_semanales'], 'horas_semanales', 1, 40);
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
            $user = \AuthHelper::getCurrentUser();
            
            if ($user && isset($user['id_usuario'])) {
                // Check if log table exists before trying to insert
                $stmt = $this->db->prepare("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'log')");
                $stmt->execute();
                $tableExists = $stmt->fetchColumn();
                
                if ($tableExists) {
                    $stmt = $this->db->prepare("INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())");
                    $stmt->execute([$user['id_usuario'], $accion]);
                }
            }
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
            // Don't throw the exception, just log it
        }
    }
}
