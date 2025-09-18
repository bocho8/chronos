<?php
/**
 * MateriaController
 * Controlador para manejar operaciones CRUD de materias
 */

require_once __DIR__ . '/../models/Materia.php';

class MateriaController {
    private $db;
    private $materiaModel;
    
    public function __construct($database) {
        $this->db = $database;
        $this->materiaModel = new Materia($database);
    }
    
    /**
     * Maneja las peticiones HTTP
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        error_log("MateriaController handling request - Method: $method, Action: $action");
        
        try {
            switch ($action) {
                case 'get':
                    $this->getMateria();
                    break;
                case 'list':
                    $this->listMaterias();
                    break;
                case 'create':
                    $this->createMateria();
                    break;
                case 'update':
                    $this->updateMateria();
                    break;
                case 'delete':
                    $this->deleteMateria();
                    break;
                case 'get_pautas':
                    $this->getPautasAnep();
                    break;
                case 'get_grupos':
                    $this->getGrupos();
                    break;
                default:
                    throw new Exception("Acción no válida: $action");
            }
        } catch (Exception $e) {
            error_log("Error in MateriaController: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtiene una materia específica
     */
    private function getMateria() {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) {
            throw new Exception("ID de materia requerido");
        }
        
        $materia = $this->materiaModel->getMateriaById($id);
        
        if (!$materia) {
            throw new Exception("Materia no encontrada");
        }
        
        echo json_encode([
            'success' => true,
            'data' => $materia
        ]);
    }
    
    /**
     * Lista todas las materias
     */
    private function listMaterias() {
        $materias = $this->materiaModel->getAllMaterias();
        
        if ($materias === false) {
            throw new Exception("Error al obtener las materias");
        }
        
        echo json_encode([
            'success' => true,
            'data' => $materias
        ]);
    }
    
    /**
     * Crea una nueva materia
     */
    private function createMateria() {
        $data = $this->validateMateriaData($_POST);
        
        try {
            $this->db->beginTransaction();
            
            $id = $this->materiaModel->createMateria($data);
            
            if (!$id) {
                throw new Exception("Error al crear la materia");
            }
            
            // Registrar en el log
            $this->logActivity("Creó la materia: " . $data['nombre']);
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Materia creada exitosamente',
                'data' => ['id' => $id]
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Actualiza una materia existente
     */
    private function updateMateria() {
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            throw new Exception("ID de materia requerido");
        }
        
        $data = $this->validateMateriaData($_POST, false);
        
        try {
            $this->db->beginTransaction();
            
            $success = $this->materiaModel->updateMateria($id, $data);
            
            if (!$success) {
                throw new Exception("Error al actualizar la materia");
            }
            
            // Registrar en el log
            $this->logActivity("Actualizó la materia ID: $id");
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Materia actualizada exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Elimina una materia
     */
    private function deleteMateria() {
        $id = $_POST['id'] ?? $_POST['id_materia'] ?? null;
        
        if (!$id) {
            throw new Exception("ID de materia requerido");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Obtener información de la materia antes de eliminarla
            $materia = $this->materiaModel->getMateriaById($id);
            if (!$materia) {
                throw new Exception("Materia no encontrada");
            }
            
            $success = $this->materiaModel->deleteMateria($id);
            
            if (!$success) {
                throw new Exception("Error al eliminar la materia");
            }
            
            // Registrar en el log
            $this->logActivity("Eliminó la materia: " . $materia['nombre']);
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Materia eliminada exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Obtiene todas las pautas ANEP
     */
    private function getPautasAnep() {
        $pautas = $this->materiaModel->getAllPautasAnep();
        
        echo json_encode([
            'success' => true,
            'data' => $pautas
        ]);
    }
    
    /**
     * Obtiene todos los grupos
     */
    private function getGrupos() {
        $grupos = $this->materiaModel->getAllGrupos();
        
        echo json_encode([
            'success' => true,
            'data' => $grupos
        ]);
    }
    
    /**
     * Valida los datos de una materia
     */
    private function validateMateriaData($data, $required = true) {
        $validated = [];
        
        // Nombre (requerido)
        if ($required && empty($data['nombre'])) {
            throw new Exception("El nombre de la materia es requerido");
        }
        if (!empty($data['nombre'])) {
            $validated['nombre'] = trim($data['nombre']);
            if (strlen($validated['nombre']) > 200) {
                throw new Exception("El nombre de la materia no puede exceder 200 caracteres");
            }
        }
        
        // Horas semanales
        if (isset($data['horas_semanales'])) {
            $horas = intval($data['horas_semanales']);
            if ($horas < 1 || $horas > 40) {
                throw new Exception("Las horas semanales deben estar entre 1 y 40");
            }
            $validated['horas_semanales'] = $horas;
        }
        
        // ID Pauta ANEP
        if (isset($data['id_pauta_anep'])) {
            $validated['id_pauta_anep'] = intval($data['id_pauta_anep']);
        }
        
        // En conjunto (boolean)
        if (isset($data['en_conjunto'])) {
            $validated['en_conjunto'] = filter_var($data['en_conjunto'], FILTER_VALIDATE_BOOLEAN);
        }
        
        // ID Grupo compartido
        if (isset($data['id_grupo_compartido']) && !empty($data['id_grupo_compartido'])) {
            $validated['id_grupo_compartido'] = intval($data['id_grupo_compartido']);
        }
        
        // Es programa italiano (boolean)
        if (isset($data['es_programa_italiano'])) {
            $validated['es_programa_italiano'] = filter_var($data['es_programa_italiano'], FILTER_VALIDATE_BOOLEAN);
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
