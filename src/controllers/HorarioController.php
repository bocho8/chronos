<?php
/**
 * HorarioController
 * Controlador para manejar operaciones CRUD de horarios y disponibilidad
 */

require_once __DIR__ . '/../models/Horario.php';

class HorarioController {
    private $db;
    private $horarioModel;
    
    public function __construct($database) {
        $this->db = $database;
        $this->horarioModel = new Horario($database);
    }
    
    /**
     * Maneja las peticiones HTTP
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        error_log("HorarioController handling request - Method: $method, Action: $action");
        
        try {
            switch ($action) {
                case 'get':
                    $this->getHorario();
                    break;
                case 'list':
                    $this->listHorarios();
                    break;
                case 'list_by_grupo':
                    $this->listHorariosByGrupo();
                    break;
                case 'list_by_docente':
                    $this->listHorariosByDocente();
                    break;
                case 'create':
                    $this->createHorario();
                    break;
                case 'update':
                    $this->updateHorario();
                    break;
                case 'delete':
                    $this->deleteHorario();
                    break;
                case 'get_disponibilidad':
                    $this->getDocenteDisponibilidad();
                    break;
                case 'update_disponibilidad':
                    $this->updateDocenteDisponibilidad();
                    break;
                case 'get_bloques':
                    $this->getBloques();
                    break;
                case 'get_grupos':
                    $this->getGrupos();
                    break;
                case 'get_materias':
                    $this->getMaterias();
                    break;
                case 'get_docentes':
                    $this->getDocentes();
                    break;
                default:
                    throw new Exception("Acción no válida: $action");
            }
        } catch (Exception $e) {
            error_log("Error in HorarioController: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtiene un horario específico
     */
    private function getHorario() {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) {
            throw new Exception("ID de horario requerido");
        }
        
        $horario = $this->horarioModel->getHorarioById($id);
        
        if (!$horario) {
            throw new Exception("Horario no encontrado");
        }
        
        echo json_encode([
            'success' => true,
            'data' => $horario
        ]);
    }
    
    /**
     * Lista todos los horarios
     */
    private function listHorarios() {
        $horarios = $this->horarioModel->getAllHorarios();
        
        if ($horarios === false) {
            throw new Exception("Error al obtener los horarios");
        }
        
        echo json_encode([
            'success' => true,
            'data' => $horarios
        ]);
    }
    
    /**
     * Lista horarios por grupo
     */
    private function listHorariosByGrupo() {
        $idGrupo = $_POST['id_grupo'] ?? $_GET['id_grupo'] ?? null;
        
        if (!$idGrupo) {
            throw new Exception("ID de grupo requerido");
        }
        
        $horarios = $this->horarioModel->getHorariosByGrupo($idGrupo);
        
        if ($horarios === false) {
            throw new Exception("Error al obtener los horarios del grupo");
        }
        
        echo json_encode([
            'success' => true,
            'data' => $horarios
        ]);
    }
    
    /**
     * Lista horarios por docente
     */
    private function listHorariosByDocente() {
        $idDocente = $_POST['id_docente'] ?? $_GET['id_docente'] ?? null;
        
        if (!$idDocente) {
            throw new Exception("ID de docente requerido");
        }
        
        $horarios = $this->horarioModel->getHorariosByDocente($idDocente);
        
        if ($horarios === false) {
            throw new Exception("Error al obtener los horarios del docente");
        }
        
        echo json_encode([
            'success' => true,
            'data' => $horarios
        ]);
    }
    
    /**
     * Crea un nuevo horario
     */
    private function createHorario() {
        $data = $this->validateHorarioData($_POST);
        
        try {
            $this->db->beginTransaction();
            
            $id = $this->horarioModel->createHorario($data);
            
            if (!$id) {
                throw new Exception("Error al crear el horario");
            }
            
            // Registrar en el log
            $this->logActivity("Creó asignación de horario ID: $id");
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Horario creado exitosamente',
                'data' => ['id' => $id]
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Actualiza un horario existente
     */
    private function updateHorario() {
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            throw new Exception("ID de horario requerido");
        }
        
        $data = $this->validateHorarioData($_POST, false);
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->horarioModel->updateHorario($id, $data);
            
            if (!$success) {
                throw new Exception("Error al actualizar el horario");
            }
            
            // Registrar en el log
            $this->logActivity("Actualizó asignación de horario ID: $id");
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Horario actualizado exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Elimina un horario
     */
    private function deleteHorario() {
        $id = $_POST['id'] ?? $_POST['id_horario'] ?? null;
        
        if (!$id) {
            throw new Exception("ID de horario requerido");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Obtener información del horario antes de eliminarlo
            $horario = $this->horarioModel->getHorarioById($id);
            if (!$horario) {
                throw new Exception("Horario no encontrado");
            }
            
            $success = $this->horarioModel->deleteHorario($id);
            
            if (!$success) {
                throw new Exception("Error al eliminar el horario");
            }
            
            // Registrar en el log
            $this->logActivity("Eliminó asignación de horario: " . $horario['grupo_nombre'] . " - " . $horario['materia_nombre']);
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Horario eliminado exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Obtiene la disponibilidad de un docente
     */
    private function getDocenteDisponibilidad() {
        $idDocente = $_POST['id_docente'] ?? $_GET['id_docente'] ?? null;
        
        if (!$idDocente) {
            throw new Exception("ID de docente requerido");
        }
        
        $disponibilidad = $this->horarioModel->getDocenteDisponibilidad($idDocente);
        
        echo json_encode([
            'success' => true,
            'data' => $disponibilidad
        ]);
    }
    
    /**
     * Actualiza la disponibilidad de un docente
     */
    private function updateDocenteDisponibilidad() {
        $idDocente = $_POST['id_docente'] ?? null;
        $idBloque = $_POST['id_bloque'] ?? null;
        $dia = $_POST['dia'] ?? null;
        $disponible = isset($_POST['disponible']) ? filter_var($_POST['disponible'], FILTER_VALIDATE_BOOLEAN) : null;
        
        if (!$idDocente || !$idBloque || !$dia || $disponible === null) {
            throw new Exception("Todos los parámetros son requeridos");
        }
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->horarioModel->updateDocenteDisponibilidad($idDocente, $idBloque, $dia, $disponible);
            
            if (!$success) {
                throw new Exception("Error al actualizar la disponibilidad");
            }
            
            // Registrar en el log
            $disponibilidadText = $disponible ? 'disponible' : 'no disponible';
            $this->logActivity("Marcó docente ID $idDocente como $disponibilidadText para $dia bloque $idBloque");
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Disponibilidad actualizada exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Obtiene todos los bloques horarios
     */
    private function getBloques() {
        $bloques = $this->horarioModel->getAllBloques();
        
        echo json_encode([
            'success' => true,
            'data' => $bloques
        ]);
    }
    
    /**
     * Obtiene todos los grupos
     */
    private function getGrupos() {
        $grupos = $this->horarioModel->getAllGrupos();
        
        echo json_encode([
            'success' => true,
            'data' => $grupos
        ]);
    }
    
    /**
     * Obtiene todas las materias
     */
    private function getMaterias() {
        $materias = $this->horarioModel->getAllMaterias();
        
        echo json_encode([
            'success' => true,
            'data' => $materias
        ]);
    }
    
    /**
     * Obtiene todos los docentes
     */
    private function getDocentes() {
        $docentes = $this->horarioModel->getAllDocentes();
        
        echo json_encode([
            'success' => true,
            'data' => $docentes
        ]);
    }
    
    /**
     * Valida los datos de un horario
     */
    private function validateHorarioData($data, $required = true) {
        $validated = [];
        $dias_validos = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];
        
        // ID Grupo (requerido)
        if ($required && empty($data['id_grupo'])) {
            throw new Exception("El grupo es requerido");
        }
        if (!empty($data['id_grupo'])) {
            $validated['id_grupo'] = intval($data['id_grupo']);
        }
        
        // ID Docente (requerido)
        if ($required && empty($data['id_docente'])) {
            throw new Exception("El docente es requerido");
        }
        if (!empty($data['id_docente'])) {
            $validated['id_docente'] = intval($data['id_docente']);
        }
        
        // ID Materia (requerido)
        if ($required && empty($data['id_materia'])) {
            throw new Exception("La materia es requerida");
        }
        if (!empty($data['id_materia'])) {
            $validated['id_materia'] = intval($data['id_materia']);
        }
        
        // ID Bloque (requerido)
        if ($required && empty($data['id_bloque'])) {
            throw new Exception("El bloque horario es requerido");
        }
        if (!empty($data['id_bloque'])) {
            $validated['id_bloque'] = intval($data['id_bloque']);
        }
        
        // Día (requerido)
        if ($required && empty($data['dia'])) {
            throw new Exception("El día es requerido");
        }
        if (!empty($data['dia'])) {
            $dia = strtoupper($data['dia']);
            if (!in_array($dia, $dias_validos)) {
                throw new Exception("Día no válido");
            }
            $validated['dia'] = $dia;
        }
        
        return $validated;
    }
    
    /**
     * Registra una actividad en el log del sistema
     */
    private function logActivity($accion) {
        try {
            require_once __DIR__ . '/../helpers/AuthHelper.php';
            $userId = AuthHelper::getCurrentUserId();
            
            if ($userId) {
                $stmt = $this->db->prepare("INSERT INTO log (id_usuario, accion, fecha) VALUES (?, ?, NOW())");
                $stmt->execute([$userId, $accion]);
            }
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
