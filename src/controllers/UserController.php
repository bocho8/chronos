<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

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
                ResponseHelper::error('ID de usuario requerido');
                return;
            }
            
            $usuarioData = $this->validateUsuarioData($_POST, true);
            
            if ($usuarioData === false) {
                return;
            }
            
            $result = $this->usuarioModel->updateUsuario($id, $usuarioData);
            
            if ($result) {
                ResponseHelper::success('Usuario actualizado exitosamente');
            } else {
                ResponseHelper::error('Error actualizando usuario');
            }
            
        } catch (Exception $e) {
            error_log("Error actualizando usuario: " . $e->getMessage());
            ResponseHelper::error('Error actualizando usuario: ' . $e->getMessage());
        }
    }
    
    /**
     * Eliminar usuario
     */
    private function deleteUsuario() {
        try {
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                ResponseHelper::error('ID de usuario requerido');
                return;
            }
            
            $this->usuarioModel->deleteUsuario($id);
            
            ResponseHelper::success('Usuario eliminado exitosamente');
            
        } catch (Exception $e) {
            error_log("Error eliminando usuario: " . $e->getMessage());
            ResponseHelper::error('Error eliminando usuario: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener usuario por ID
     */
    private function getUsuario() {
        try {
            $id = $_GET['id'] ?? $_POST['id'] ?? '';
            
            if (empty($id)) {
                ResponseHelper::error('ID de usuario requerido');
                return;
            }
            
            $usuario = $this->usuarioModel->getUsuarioById($id);
            
            if (!$usuario) {
                ResponseHelper::notFound('Usuario');
                return;
            }

            $usuario['roles'] = !empty($usuario['roles']) ? explode(', ', $usuario['roles']) : [];
            $usuario['role_names'] = !empty($usuario['role_names']) ? explode(', ', $usuario['role_names']) : [];
            
            ResponseHelper::success('Usuario obtenido exitosamente', $usuario);
            
        } catch (Exception $e) {
            error_log("Error obteniendo usuario: " . $e->getMessage());
            ResponseHelper::error('Error obteniendo usuario: ' . $e->getMessage());
        }
    }
    
    /**
     * Buscar usuarios
     */
    private function searchUsuarios() {
        try {
            $searchTerm = $_GET['search'] ?? $_POST['search'] ?? '';
            
            if (empty($searchTerm)) {
                ResponseHelper::error('Término de búsqueda requerido');
                return;
            }
            
            $usuarios = $this->usuarioModel->searchUsuarios($searchTerm);
            
            ResponseHelper::success('Búsqueda completada', $usuarios ?: []);
            
        } catch (Exception $e) {
            error_log("Error buscando usuarios: " . $e->getMessage());
            ResponseHelper::error('Error buscando usuarios: ' . $e->getMessage());
        }
    }
    
    /**
     * Listar todos los usuarios
     */
    private function listUsuarios() {
        try {
            $usuarios = $this->usuarioModel->getAllUsuarios();
            
            ResponseHelper::success('Usuarios obtenidos exitosamente', $usuarios ?: []);
            
        } catch (Exception $e) {
            error_log("Error listando usuarios: " . $e->getMessage());
            ResponseHelper::error('Error listando usuarios: ' . $e->getMessage());
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
            ResponseHelper::validationError($errors);
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
