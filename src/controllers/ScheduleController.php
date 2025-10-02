<?php

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../models/Horario.php';

class HorarioController {
    private $db;
    private $horarioModel;
    
    public function __construct($database) {
        $this->db = $database;
        $this->horarioModel = new Horario($database);
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            match ($action) {
                'get' => $this->getHorario(),
                'list' => $this->listHorarios(),
                'list_by_grupo' => $this->listHorariosByGrupo(),
                'list_by_docente' => $this->listHorariosByDocente(),
                'create' => $this->createHorario(),
                'update' => $this->updateHorario(),
                'delete' => $this->deleteHorario(),
                'get_disponibilidad' => $this->getDocenteDisponibilidad(),
                'update_disponibilidad' => $this->updateDocenteDisponibilidad(),
                'get_bloques' => $this->getBloques(),
                'get_grupos' => $this->getGrupos(),
                'get_materias' => $this->getMaterias(),
                'get_docentes' => $this->getDocentes(),
                default => throw new Exception("Acción no válida: $action")
            };
        } catch (Exception $e) {
            error_log("Error in HorarioController: " . $e->getMessage());
            ResponseHelper::error($e->getMessage());
        }
    }
    
    private function getHorario() {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            ResponseHelper::error("ID de horario requerido");
        }
        
        $horario = $this->horarioModel->getHorarioById($id);
        if (!$horario) {
            ResponseHelper::notFound("Horario");
        }
        
        ResponseHelper::success("Horario obtenido exitosamente", $horario);
    }
    
    private function listHorarios() {
        $horarios = $this->horarioModel->getAllHorarios();
        if ($horarios === false) {
            ResponseHelper::error("Error al obtener los horarios");
        }
        
        ResponseHelper::success("Horarios obtenidos exitosamente", $horarios);
    }
    
    private function listHorariosByGrupo() {
        $idGrupo = $_POST['id_grupo'] ?? $_GET['id_grupo'] ?? null;
        if (!$idGrupo) {
            ResponseHelper::error("ID de grupo requerido");
        }
        
        $horarios = $this->horarioModel->getHorariosByGrupo($idGrupo);
        if ($horarios === false) {
            ResponseHelper::error("Error al obtener los horarios del grupo");
        }
        
        ResponseHelper::success("Horarios del grupo obtenidos exitosamente", $horarios);
    }
    
    private function listHorariosByDocente() {
        $idDocente = $_POST['id_docente'] ?? $_GET['id_docente'] ?? null;
        if (!$idDocente) {
            ResponseHelper::error("ID de docente requerido");
        }
        
        $horarios = $this->horarioModel->getHorariosByDocente($idDocente);
        if ($horarios === false) {
            ResponseHelper::error("Error al obtener los horarios del docente");
        }
        
        ResponseHelper::success("Horarios del docente obtenidos exitosamente", $horarios);
    }
    
    private function createHorario() {
        $data = $this->validateHorarioData($_POST);
        
        try {
            $this->db->beginTransaction();
            
            $id = $this->horarioModel->createHorario($data);
            if (!$id) {
                throw new Exception("Error al crear el horario");
            }
            
            $this->logActivity("Creó asignación de horario ID: $id");
            $this->db->commit();
            
            ResponseHelper::success('Horario creado exitosamente', ['id' => $id]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function updateHorario() {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            ResponseHelper::error("ID de horario requerido");
        }
        
        $data = $this->validateHorarioData($_POST, false);
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->horarioModel->updateHorario($id, $data);
            if (!$success) {
                throw new Exception("Error al actualizar el horario");
            }
            
            $this->logActivity("Actualizó asignación de horario ID: $id");
            $this->db->commit();
            
            ResponseHelper::success('Horario actualizado exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function deleteHorario() {
        $id = $_POST['id'] ?? $_POST['id_horario'] ?? null;
        if (!$id) {
            ResponseHelper::error("ID de horario requerido");
        }
        
        try {
            $this->db->beginTransaction();
            
            $horario = $this->horarioModel->getHorarioById($id);
            if (!$horario) {
                throw new Exception("Horario no encontrado");
            }
            
            $success = $this->horarioModel->deleteHorario($id);
            if (!$success) {
                throw new Exception("Error al eliminar el horario");
            }
            
            $this->logActivity("Eliminó asignación de horario: " . $horario['grupo_nombre'] . " - " . $horario['materia_nombre']);
            $this->db->commit();
            
            ResponseHelper::success('Horario eliminado exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function getDocenteDisponibilidad() {
        $idDocente = $_POST['id_docente'] ?? $_GET['id_docente'] ?? null;
        if (!$idDocente) {
            ResponseHelper::error("ID de docente requerido");
        }
        
        $disponibilidad = $this->horarioModel->getDocenteDisponibilidad($idDocente);
        ResponseHelper::success("Disponibilidad obtenida exitosamente", $disponibilidad);
    }
    
    private function updateDocenteDisponibilidad() {
        $idDocente = $_POST['id_docente'] ?? null;
        $idBloque = $_POST['id_bloque'] ?? null;
        $dia = $_POST['dia'] ?? null;
        $disponible = isset($_POST['disponible']) ? filter_var($_POST['disponible'], FILTER_VALIDATE_BOOLEAN) : null;
        
        if (!$idDocente || !$idBloque || !$dia || $disponible === null) {
            ResponseHelper::error("Todos los parámetros son requeridos");
        }
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->horarioModel->updateDocenteDisponibilidad($idDocente, $idBloque, $dia, $disponible);
            if (!$success) {
                throw new Exception("Error al actualizar la disponibilidad");
            }
            
            $disponibilidadText = $disponible ? 'disponible' : 'no disponible';
            $this->logActivity("Marcó docente ID $idDocente como $disponibilidadText para $dia bloque $idBloque");
            $this->db->commit();
            
            ResponseHelper::success('Disponibilidad actualizada exitosamente');
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function getBloques() {
        $bloques = $this->horarioModel->getAllBloques();
        ResponseHelper::success("Bloques obtenidos exitosamente", $bloques);
    }
    
    private function getGrupos() {
        $grupos = $this->horarioModel->getAllGrupos();
        ResponseHelper::success("Grupos obtenidos exitosamente", $grupos);
    }
    
    private function getMaterias() {
        $materias = $this->horarioModel->getAllMaterias();
        ResponseHelper::success("Materias obtenidas exitosamente", $materias);
    }
    
    private function getDocentes() {
        $docentes = $this->horarioModel->getAllDocentes();
        ResponseHelper::success("Docentes obtenidos exitosamente", $docentes);
    }
    
    private function validateHorarioData($data, $required = true) {
        $errors = [];
        $dias_validos = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];
        
        if ($required && empty($data['id_grupo'])) {
            $errors['id_grupo'] = "El grupo es requerido";
        }
        
        if ($required && empty($data['id_docente'])) {
            $errors['id_docente'] = "El docente es requerido";
        }
        
        if ($required && empty($data['id_materia'])) {
            $errors['id_materia'] = "La materia es requerida";
        }
        
        if ($required && empty($data['id_bloque'])) {
            $errors['id_bloque'] = "El bloque horario es requerido";
        }
        
        if ($required && empty($data['dia'])) {
            $errors['dia'] = "El día es requerido";
        } elseif (!empty($data['dia'])) {
            $dia = strtoupper($data['dia']);
            if (!in_array($dia, $dias_validos)) {
                $errors['dia'] = "Día no válido";
            }
        }
        
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }
        
        $validated = [];
        
        if (!empty($data['id_grupo'])) {
            $validated['id_grupo'] = intval($data['id_grupo']);
        }
        
        if (!empty($data['id_docente'])) {
            $validated['id_docente'] = intval($data['id_docente']);
        }
        
        if (!empty($data['id_materia'])) {
            $validated['id_materia'] = intval($data['id_materia']);
        }
        
        if (!empty($data['id_bloque'])) {
            $validated['id_bloque'] = intval($data['id_bloque']);
        }
        
        if (!empty($data['dia'])) {
            $validated['dia'] = strtoupper($data['dia']);
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
