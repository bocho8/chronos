<?php
/**
 * Logout Controller
 * Handles user logout functionality
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

// Initialize secure session
initSecureSession();

class LogoutController {
    
    /**
     * Handle logout request
     */
    public function handleLogout() {
        // Check if user is logged in
        if (!AuthHelper::isLoggedIn()) {
            $this->redirectToLogin();
            return;
        }
        
        $user = AuthHelper::getCurrentUser();
        $cedula = $user['cedula'] ?? null;
        
        // Load database configuration
        $dbConfig = require __DIR__ . '/../config/database.php';
        $database = new Database($dbConfig);
        $auth = new Auth($database->getConnection());
        
        // Perform logout
        $success = $auth->logout($cedula);
        
        if ($success) {
            // Redirect to login with success message
            $this->redirectToLogin('logout_success');
        } else {
            // Redirect to login with error message
            $this->redirectToLogin('logout_error');
        }
    }
    
    /**
     * Redirect to login page with optional message
     * 
     * @param string $message Optional message parameter
     */
    private function redirectToLogin($message = null) {
        $loginUrl = '/src/views/login.php';
        
        if ($message) {
            $loginUrl .= '?message=' . urlencode($message);
        }
        
        header("Location: $loginUrl");
        exit();
    }
    
    /**
     * Handle AJAX logout request
     */
    public function handleAjaxLogout() {
        // Set JSON header
        header('Content-Type: application/json');
        
        // Check if user is logged in
        if (!AuthHelper::isLoggedIn()) {
            echo json_encode([
                'success' => false,
                'message' => 'No hay sesión activa'
            ]);
            return;
        }
        
        $user = AuthHelper::getCurrentUser();
        $cedula = $user['cedula'] ?? null;
        
        try {
            // Load database configuration
            $dbConfig = require __DIR__ . '/../config/database.php';
            $database = new Database($dbConfig);
            $auth = new Auth($database->getConnection());
            
            // Perform logout
            $success = $auth->logout($cedula);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sesión cerrada correctamente',
                    'redirect' => '/src/views/login.php'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al cerrar sesión'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error del sistema'
            ]);
        }
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $controller = new LogoutController();
    $controller->handleLogout();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
    $controller = new LogoutController();
    $controller->handleAjaxLogout();
} else {
    // Default logout action
    $controller = new LogoutController();
    $controller->handleLogout();
}
