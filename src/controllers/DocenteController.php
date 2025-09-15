<?php

/**
 * Docente Controller
 * Handles HTTP requests for teacher management
 */
class DocenteController {
    private $docenteModel;
    private $translation;
    
    public function __construct($database) {
        $this->docenteModel = new Docente($database);
        $this->translation = Translation::getInstance();
    }
    
    /**
     * Handle incoming requests
     */
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'create':
                return $this->createDocente();
            case 'update':
                return $this->updateDocente();
            case 'delete':
                return $this->deleteDocente();
            case 'get':
                return $this->getDocente();
            case 'search':
                return $this->searchDocentes();
            case 'list':
            default:
                return $this->listDocentes();
        }
    }
    
    /**
     * List all teachers
     */
    private function listDocentes() {
        try {
            $docentes = $this->docenteModel->getAllDocentes();
            
            if ($docentes === false) {
                return $this->jsonResponse(false, 'Error obteniendo lista de docentes');
            }
            
            return $this->jsonResponse(true, 'Docentes obtenidos exitosamente', $docentes);
            
        } catch (Exception $e) {
            error_log("Error en listDocentes: " . $e->getMessage());
            return $this->jsonResponse(false, 'Error interno del servidor');
        }
    }
    
    /**
     * Get single teacher
     */
    private function getDocente() {
        try {
            $id_docente = $_GET['id'] ?? $_POST['id'] ?? null;
            
            if (!$id_docente) {
                return $this->jsonResponse(false, 'ID de docente requerido');
            }
            
            $docente = $this->docenteModel->getDocenteById($id_docente);
            
            if (!$docente) {
                return $this->jsonResponse(false, 'Docente no encontrado');
            }
            
            return $this->jsonResponse(true, 'Docente obtenido exitosamente', $docente);
            
        } catch (Exception $e) {
            error_log("Error en getDocente: " . $e->getMessage());
            return $this->jsonResponse(false, 'Error interno del servidor');
        }
    }
    
    /**
     * Create new teacher
     */
    private function createDocente() {
        try {
            error_log("Creating docente with data: " . json_encode($_POST));
            
            // Validate required fields
            $errors = $this->validateDocenteData($_POST);
            
            if (!empty($errors)) {
                error_log("Validation errors: " . json_encode($errors));
                return $this->jsonResponse(false, 'Datos inválidos', $errors);
            }
            
            // Prepare data
            $docenteData = [
                'cedula' => trim($_POST['cedula']),
                'nombre' => trim($_POST['nombre']),
                'apellido' => trim($_POST['apellido']),
                'email' => trim($_POST['email'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'contrasena' => $_POST['contrasena'],
                'trabaja_otro_liceo' => isset($_POST['trabaja_otro_liceo']) ? (bool)$_POST['trabaja_otro_liceo'] : false,
                'fecha_envio_disponibilidad' => $_POST['fecha_envio_disponibilidad'] ?? null,
                'horas_asignadas' => (int)($_POST['horas_asignadas'] ?? 0),
                'porcentaje_margen' => (float)($_POST['porcentaje_margen'] ?? 0.00)
            ];
            
            // Create teacher
            $docenteId = $this->docenteModel->createDocente($docenteData);
            
            if ($docenteId === false) {
                return $this->jsonResponse(false, 'Error creando docente. Verifique que la cédula no esté en uso.');
            }
            
            return $this->jsonResponse(true, 'Docente creado exitosamente', ['id_docente' => $docenteId]);
            
        } catch (Exception $e) {
            error_log("Error en createDocente: " . $e->getMessage());
            return $this->jsonResponse(false, 'Error interno del servidor');
        }
    }
    
    /**
     * Update teacher
     */
    private function updateDocente() {
        try {
            $id_docente = $_POST['id_docente'] ?? null;
            
            if (!$id_docente) {
                return $this->jsonResponse(false, 'ID de docente requerido');
            }
            
            // Validate required fields (excluding password)
            $errors = $this->validateDocenteData($_POST, false);
            
            if (!empty($errors)) {
                return $this->jsonResponse(false, 'Datos inválidos', $errors);
            }
            
            // Prepare data
            $docenteData = [
                'nombre' => trim($_POST['nombre']),
                'apellido' => trim($_POST['apellido']),
                'email' => trim($_POST['email'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'trabaja_otro_liceo' => isset($_POST['trabaja_otro_liceo']) ? (bool)$_POST['trabaja_otro_liceo'] : false,
                'fecha_envio_disponibilidad' => $_POST['fecha_envio_disponibilidad'] ?? null,
                'horas_asignadas' => (int)($_POST['horas_asignadas'] ?? 0),
                'porcentaje_margen' => (float)($_POST['porcentaje_margen'] ?? 0.00)
            ];
            
            // Update password if provided
            if (!empty($_POST['contrasena'])) {
                $docenteData['contrasena'] = $_POST['contrasena'];
            }
            
            // Update teacher
            $result = $this->docenteModel->updateDocente($id_docente, $docenteData);
            
            if ($result === false) {
                return $this->jsonResponse(false, 'Error actualizando docente');
            }
            
            return $this->jsonResponse(true, 'Docente actualizado exitosamente');
            
        } catch (Exception $e) {
            error_log("Error en updateDocente: " . $e->getMessage());
            return $this->jsonResponse(false, 'Error interno del servidor');
        }
    }
    
    /**
     * Delete teacher
     */
    private function deleteDocente() {
        try {
            $id_docente = $_POST['id_docente'] ?? $_GET['id'] ?? null;
            
            if (!$id_docente) {
                return $this->jsonResponse(false, 'ID de docente requerido');
            }
            
            // Delete teacher
            $result = $this->docenteModel->deleteDocente($id_docente);
            
            if ($result === false) {
                return $this->jsonResponse(false, 'Error eliminando docente');
            }
            
            return $this->jsonResponse(true, 'Docente eliminado exitosamente');
            
        } catch (Exception $e) {
            error_log("Error en deleteDocente: " . $e->getMessage());
            return $this->jsonResponse(false, 'Error interno del servidor');
        }
    }
    
    /**
     * Search teachers
     */
    private function searchDocentes() {
        try {
            $searchTerm = $_GET['q'] ?? $_POST['q'] ?? '';
            
            if (empty($searchTerm)) {
                return $this->listDocentes();
            }
            
            $docentes = $this->docenteModel->searchDocentes($searchTerm);
            
            if ($docentes === false) {
                return $this->jsonResponse(false, 'Error buscando docentes');
            }
            
            return $this->jsonResponse(true, 'Búsqueda completada', $docentes);
            
        } catch (Exception $e) {
            error_log("Error en searchDocentes: " . $e->getMessage());
            return $this->jsonResponse(false, 'Error interno del servidor');
        }
    }
    
    /**
     * Validate teacher data
     * 
     * @param array $data Data to validate
     * @param bool $requirePassword Whether password is required
     * @return array Array of validation errors
     */
    private function validateDocenteData($data, $requirePassword = true) {
        $errors = [];
        
        // Required fields
        if (empty($data['cedula'])) {
            $errors['cedula'] = 'Cédula es requerida';
        } elseif (!preg_match('/^\d{7,8}$/', $data['cedula'])) {
            $errors['cedula'] = 'Cédula debe tener 7 u 8 dígitos';
        }
        
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'Nombre es requerido';
        } elseif (strlen($data['nombre']) < 2) {
            $errors['nombre'] = 'Nombre debe tener al menos 2 caracteres';
        }
        
        if (empty($data['apellido'])) {
            $errors['apellido'] = 'Apellido es requerido';
        } elseif (strlen($data['apellido']) < 2) {
            $errors['apellido'] = 'Apellido debe tener al menos 2 caracteres';
        }
        
        if ($requirePassword && empty($data['contrasena'])) {
            $errors['contrasena'] = 'Contraseña es requerida';
        } elseif (!empty($data['contrasena']) && strlen($data['contrasena']) < 6) {
            $errors['contrasena'] = 'Contraseña debe tener al menos 6 caracteres';
        }
        
        // Optional fields validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }
        
        if (!empty($data['telefono']) && !preg_match('/^[\d\s\-\+\(\)]+$/', $data['telefono'])) {
            $errors['telefono'] = 'Teléfono inválido';
        }
        
        if (isset($data['horas_asignadas']) && (!is_numeric($data['horas_asignadas']) || $data['horas_asignadas'] < 0)) {
            $errors['horas_asignadas'] = 'Horas asignadas debe ser un número positivo';
        }
        
        if (isset($data['porcentaje_margen']) && (!is_numeric($data['porcentaje_margen']) || $data['porcentaje_margen'] < 0 || $data['porcentaje_margen'] > 100)) {
            $errors['porcentaje_margen'] = 'Porcentaje de margen debe ser entre 0 y 100';
        }
        
        return $errors;
    }
    
    /**
     * Send JSON response
     * 
     * @param bool $success Whether operation was successful
     * @param string $message Response message
     * @param mixed $data Additional data
     */
    private function jsonResponse($success, $message, $data = null) {
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
}
