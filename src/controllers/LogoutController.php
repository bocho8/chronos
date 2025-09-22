<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

initSecureSession();

class LogoutController {
    
    public function handleLogout() {
        if (!AuthHelper::isLoggedIn()) {
            $this->redirectToLogin();
            return;
        }
        
        $user = AuthHelper::getCurrentUser();
        $cedula = $user['cedula'] ?? null;
        
        $dbConfig = require __DIR__ . '/../config/database.php';
        $database = new Database($dbConfig);
        $auth = new Auth($database->getConnection());
        
        $success = $auth->logout($cedula);
        
        if ($success) {
            $this->redirectToLogin('logout_success');
        } else {
            $this->redirectToLogin('logout_error');
        }
    }
    
    private function redirectToLogin($message = null) {
        $loginUrl = '/src/views/login.php';
        
        if ($message) {
            $loginUrl .= '?message=' . urlencode($message);
        }
        
        header("Location: $loginUrl");
        exit();
    }
    
    public function handleAjaxLogout() {
        if (!AuthHelper::isLoggedIn()) {
            ResponseHelper::error('No hay sesión activa');
        }
        
        $user = AuthHelper::getCurrentUser();
        $cedula = $user['cedula'] ?? null;
        
        try {
            $dbConfig = require __DIR__ . '/../config/database.php';
            $database = new Database($dbConfig);
            $auth = new Auth($database->getConnection());
            
            $success = $auth->logout($cedula);
            
            if ($success) {
                ResponseHelper::success('Sesión cerrada correctamente', ['redirect' => '/src/views/login.php']);
            } else {
                ResponseHelper::error('Error al cerrar sesión');
            }
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            ResponseHelper::error('Error del sistema');
        }
    }
}

$controller = new LogoutController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
    $controller->handleAjaxLogout();
} else {
    $controller->handleLogout();
}
