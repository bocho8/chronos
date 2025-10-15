<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

namespace App\Services;

require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../helpers/ValidationHelper.php';
require_once __DIR__ . '/../../app/Models/User.php';

class AuthService
{
    private $userModel;
    
    public function __construct($database)
    {
        $this->userModel = new \App\Models\User($database);
    }
    
    /**
     * Authenticate user with credentials
     */
    public function authenticate($cedula, $password)
    {
        try {
            if (empty($cedula) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Cédula y contraseña son requeridos'
                ];
            }
            
            if (!ValidationHelper::validateCedula($cedula)) {
                return [
                    'success' => false,
                    'message' => 'Formato de cédula inválido'
                ];
            }
            
            $user = $this->userModel->getUserByCedula($cedula);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ];
            }
            
            if (!password_verify($password, $user['contrasena_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ];
            }
            
            if (!$user['activo']) {
                return [
                    'success' => false,
                    'message' => 'Cuenta desactivada. Contacte al administrador.'
                ];
            }
            
            $roles = $this->userModel->getUserRoles($user['id_usuario']);
            
            if (empty($roles)) {
                return [
                    'success' => false,
                    'message' => 'Usuario sin roles asignados'
                ];
            }
            
            $this->createSession($user, $roles);

            $this->logActivity($user['id_usuario'], 'LOGIN', 'Usuario inició sesión');
            
            return [
                'success' => true,
                'message' => 'Login exitoso',
                'user' => $this->formatUserData($user, $roles)
            ];
            
        } catch (Exception $e) {
            error_log("Error in AuthService::authenticate: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout()
    {
        try {
            if (isset($_SESSION['user'])) {
                $userId = $_SESSION['user']['id_usuario'];

                $this->logActivity($userId, 'LOGOUT', 'Usuario cerró sesión');
            }

            session_destroy();
            
            return [
                'success' => true,
                'message' => 'Logout exitoso'
            ];
            
        } catch (Exception $e) {
            error_log("Error in AuthService::logout: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated()
    {
        return AuthHelper::isLoggedIn() && AuthHelper::checkSessionTimeout();
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        return AuthHelper::hasRole($role);
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        try {
            $passwordError = ValidationHelper::validatePassword($newPassword, true);
            if ($passwordError) {
                return [
                    'success' => false,
                    'message' => $passwordError
                ];
            }
            
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ];
            }
            
            if (!password_verify($currentPassword, $user['contrasena_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Contraseña actual incorrecta'
                ];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $this->userModel->updatePassword($userId, $hashedPassword);
            
            if ($result) {
                $this->logActivity($userId, 'PASSWORD_CHANGE', 'Usuario cambió contraseña');
                return [
                    'success' => true,
                    'message' => 'Contraseña actualizada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error actualizando contraseña'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error in AuthService::changePassword: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    /**
     * Create user session
     */
    private function createSession($user, $roles)
    {
        $_SESSION['user'] = [
            'id_usuario' => $user['id_usuario'],
            'cedula' => $user['cedula'],
            'nombre' => $user['nombre'],
            'apellido' => $user['apellido'],
            'email' => $user['email'],
            'telefono' => $user['telefono'],
            'roles' => $roles,
            'login_time' => time()
        ];
        
        $_SESSION['timeout'] = time() + (2 * 60 * 60);
    }
    
    /**
     * Format user data for response
     */
    private function formatUserData($user, $roles)
    {
        return [
            'id_usuario' => $user['id_usuario'],
            'cedula' => $user['cedula'],
            'nombre' => $user['nombre'],
            'apellido' => $user['apellido'],
            'email' => $user['email'],
            'telefono' => $user['telefono'],
            'roles' => $roles,
            'display_name' => $user['nombre'] . ' ' . $user['apellido']
        ];
    }
    
    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $details = '')
    {
        try {
            $this->userModel->logActivity($userId, $action, $details);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
