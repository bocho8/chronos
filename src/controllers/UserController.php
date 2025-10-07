<?php

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../models/Usuario.php';

class UserController {
    private $usuarioModel;
    
    public function __construct($database) {
        $this->usuarioModel = new Usuario($database);
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            match ($action) {
                'create' => $this->createUsuario(),
                'update' => $this->updateUsuario(),
                'delete' => $this->deleteUsuario(),
                'get' => $this->getUsuario(),
                'search' => $this->searchUsuarios(),
                default => $this->listUsuarios()
            };
        } catch (Exception $e) {
            error_log("Error in UserController: " . $e->getMessage());
            ResponseHelper::error('Error interno del servidor', null, 500);
        }
    }
    
    private function createUsuario() {
        $usuarioData = $this->validateUsuarioData($_POST);
        if ($usuarioData === false) {
            return;
        }
        
        $userId = $this->usuarioModel->createUsuario($usuarioData);
        ResponseHelper::success('Usuario creado exitosamente', ['id' => $userId]);
    }
    
    /**
     * Actualizar usuario
     */
    private function updateUsuario() {
        try {
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de usuario requerido'
                ]);
                return;
            }
            
            $usuarioData = $this->validateUsuarioData($_POST, true);
            
            if ($usuarioData === false) {
                return;
            }
            
            $this->usuarioModel->updateUsuario($id, $usuarioData);
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente'
            ]);
            
        } catch (Exception $e) {
            error_log("Error actualizando usuario: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error actualizando usuario: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Eliminar usuario
     */
    private function deleteUsuario() {
        try {
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de usuario requerido'
                ]);
                return;
            }
            
            $this->usuarioModel->deleteUsuario($id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);
            
        } catch (Exception $e) {
            error_log("Error eliminando usuario: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error eliminando usuario: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener usuario por ID
     */
    private function getUsuario() {
        try {
            $id = $_GET['id'] ?? $_POST['id'] ?? '';
            
            if (empty($id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de usuario requerido'
                ]);
                return;
            }
            
            $usuario = $this->usuarioModel->getUsuarioById($id);
            
            if (!$usuario) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
                return;
            }

            $usuario['roles'] = !empty($usuario['roles']) ? explode(', ', $usuario['roles']) : [];
            $usuario['role_names'] = !empty($usuario['role_names']) ? explode(', ', $usuario['role_names']) : [];
            
            echo json_encode([
                'success' => true,
                'data' => $usuario
            ]);
            
        } catch (Exception $e) {
            error_log("Error obteniendo usuario: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error obteniendo usuario: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Buscar usuarios
     */
    private function searchUsuarios() {
        try {
            $searchTerm = $_GET['search'] ?? $_POST['search'] ?? '';
            
            if (empty($searchTerm)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Término de búsqueda requerido'
                ]);
                return;
            }
            
            $usuarios = $this->usuarioModel->searchUsuarios($searchTerm);
            
            echo json_encode([
                'success' => true,
                'data' => $usuarios ?: []
            ]);
            
        } catch (Exception $e) {
            error_log("Error buscando usuarios: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error buscando usuarios: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Listar todos los usuarios
     */
    private function listUsuarios() {
        try {
            $usuarios = $this->usuarioModel->getAllUsuarios();
            
            echo json_encode([
                'success' => true,
                'data' => $usuarios ?: []
            ]);
            
        } catch (Exception $e) {
            error_log("Error listando usuarios: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error listando usuarios: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Validar datos del usuario
     */
    private function validateUsuarioData($data, $isUpdate = false) {
        $errors = [];

        if (empty($data['cedula'])) {
            $errors['cedula'] = 'La cédula es requerida';
        }
        
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es requerido';
        }
        
        if (empty($data['apellido'])) {
            $errors['apellido'] = 'El apellido es requerido';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }

        if (!$isUpdate || !empty($data['contrasena'])) {
            if (empty($data['contrasena'])) {
                $errors['contrasena'] = 'La contraseña es requerida';
            } elseif (strlen($data['contrasena']) < 6) {
                $errors['contrasena'] = 'La contraseña debe tener al menos 6 caracteres';
            }
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => 'Datos inválidos',
                'data' => $errors
            ]);
            return false;
        }

        $usuarioData = [
            'cedula' => trim($data['cedula']),
            'nombre' => trim($data['nombre']),
            'apellido' => trim($data['apellido']),
            'email' => trim($data['email']),
            'telefono' => trim($data['telefono'] ?? '')
        ];

        if (!empty($data['contrasena'])) {
            $usuarioData['contrasena'] = $data['contrasena'];
        }

        if (!empty($data['roles'])) {
            $usuarioData['roles'] = is_array($data['roles']) ? $data['roles'] : explode(',', $data['roles']);
        }
        
        return $usuarioData;
    }
}
?>
