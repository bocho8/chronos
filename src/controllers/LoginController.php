<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

initSecureSession();

class LoginController {
    
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToLogin('invalid_method');
            return;
        }
        
        $cedula = $_POST['ci'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        
        $validation = $this->validateInput($cedula, $password, $role);
        if (!$validation['valid']) {
            $this->redirectToLogin('validation_error', $validation['errors']);
            return;
        }
        
        try {
            $dbConfig = require __DIR__ . '/../config/database.php';
            $database = new Database($dbConfig);
            $auth = new Auth($database->getConnection());

            $user = $auth->authenticate($cedula, $password, $role);
            
            if ($user) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user'] = $user;
                $_SESSION['last_activity'] = time();

                $redirectUrl = $this->getRoleRedirectUrl($role);
                header("Location: $redirectUrl");
                exit();
            } else {
                $this->redirectToLogin('invalid_credentials');
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $this->redirectToLogin('system_error');
        }
    }
    
    public function handleAjaxLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ResponseHelper::error('Método no permitido');
            return;
        }
        
        $cedula = $_POST['ci'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        
        $validation = $this->validateInput($cedula, $password, $role);
        if (!$validation['valid']) {
            ResponseHelper::error('Datos inválidos', $validation['errors']);
            return;
        }
        
        try {
            $dbConfig = require __DIR__ . '/../config/database.php';
            $database = new Database($dbConfig);
            $auth = new Auth($database->getConnection());

            $user = $auth->authenticate($cedula, $password, $role);
            
            if ($user) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user'] = $user;
                $_SESSION['last_activity'] = time();
                
                $redirectUrl = $this->getRoleRedirectUrl($role);
                ResponseHelper::success('Inicio de sesión exitoso', ['redirect' => $redirectUrl]);
            } else {
                ResponseHelper::error('Credenciales inválidas');
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            ResponseHelper::error('Error del sistema');
        }
    }
    
    private function validateInput($cedula, $password, $role) {
        $errors = [];
        
        if (empty($cedula)) {
            $errors['ci'] = 'El C.I es obligatorio';
        } elseif (!preg_match('/^\d{7,8}$/', $cedula)) {
            $errors['ci'] = 'El C.I debe tener 7 u 8 dígitos numéricos';
        }
        
        if (empty($password)) {
            $errors['password'] = 'La contraseña es obligatoria';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
        }
        
        if (empty($role)) {
            $errors['role'] = 'Debe seleccionar un rol';
        } elseif (!in_array($role, ['ADMIN', 'DIRECTOR', 'COORDINADOR', 'DOCENTE', 'PADRE'])) {
            $errors['role'] = 'Rol inválido';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function redirectToLogin($message = null, $errors = []) {
        $loginUrl = '/login';
        
        if ($message) {
            $loginUrl .= '?message=' . urlencode($message);
        }
        
        if (!empty($errors)) {
            $loginUrl .= '&errors=' . urlencode(json_encode($errors));
        }
        
        header("Location: $loginUrl");
        exit();
    }
    
    private function getRoleRedirectUrl($role) {
        return match ($role) {
            'ADMIN' => '/admin/dashboard',
            'DIRECTOR' => '/admin/dashboard', // Directors use admin dashboard
            'COORDINADOR' => '/coordinator/dashboard',
            'DOCENTE' => '/teacher/dashboard',
            'PADRE' => '/parent/dashboard',
            default => '/login'
        };
    }
}

$controller = new LoginController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
    $controller->handleAjaxLogin();
} else {
    $controller->handleLogin();
}
