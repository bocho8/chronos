<?php
/**
 * Authentication Helper Class
 * Provides utility functions for session management and authentication checks
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Database.php';

class AuthHelper {
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if user is logged in, false otherwise
     */
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']);
    }
    
    /**
     * Get current user data
     * 
     * @return array|null User data if logged in, null otherwise
     */
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return $_SESSION['user'];
        }
        return null;
    }
    
    /**
     * Get current user's role
     * 
     * @return string|null User's role if logged in, null otherwise
     */
    public static function getCurrentUserRole() {
        $user = self::getCurrentUser();
        return $user ? $user['nombre_rol'] : null;
    }
    
    /**
     * Check if current user has specific role
     * 
     * @param string $role Role to check
     * @return bool True if user has the role, false otherwise
     */
    public static function hasRole($role) {
        return self::getCurrentUserRole() === $role;
    }
    
    /**
     * Check if current user has any of the specified roles
     * 
     * @param array $roles Array of roles to check
     * @return bool True if user has any of the roles, false otherwise
     */
    public static function hasAnyRole($roles) {
        $userRole = self::getCurrentUserRole();
        return in_array($userRole, $roles);
    }
    
    /**
     * Require user to be logged in, redirect to login if not
     * 
     * @param string $redirectUrl Optional redirect URL after login
     */
    public static function requireLogin($redirectUrl = null) {
        if (!self::isLoggedIn()) {
            $loginUrl = '/src/views/login.php';
            if ($redirectUrl) {
                $loginUrl .= '?redirect=' . urlencode($redirectUrl);
            }
            header("Location: $loginUrl");
            exit();
        }
    }
    
    /**
     * Require user to have specific role, redirect to login if not
     * 
     * @param string|array $requiredRole Role or array of roles required
     * @param string $redirectUrl Optional redirect URL after login
     */
    public static function requireRole($requiredRole, $redirectUrl = null) {
        self::requireLogin($redirectUrl);
        
        if (is_array($requiredRole)) {
            if (!self::hasAnyRole($requiredRole)) {
                self::redirectToLogin($redirectUrl);
            }
        } else {
            if (!self::hasRole($requiredRole)) {
                self::redirectToLogin($redirectUrl);
            }
        }
    }
    
    /**
     * Redirect to login page
     * 
     * @param string $redirectUrl Optional redirect URL after login
     */
    private static function redirectToLogin($redirectUrl = null) {
        $loginUrl = '/src/views/login.php';
        if ($redirectUrl) {
            $loginUrl .= '?redirect=' . urlencode($redirectUrl);
        }
        header("Location: $loginUrl");
        exit();
    }
    
    /**
     * Redirect already logged in users to their dashboard
     * Call this at the top of login page
     */
    public static function redirectIfLoggedIn() {
        if (self::isLoggedIn()) {
            $userRole = self::getCurrentUserRole();
            $redirectUrl = self::getRoleRedirectUrl($userRole);
            header("Location: $redirectUrl");
            exit();
        }
    }
    
    /**
     * Get redirect URL based on user role
     * 
     * @param string $role User's role
     * @return string Redirect URL
     */
    public static function getRoleRedirectUrl($role) {
        switch ($role) {
            case 'ADMIN':
                return '/src/views/admin/';
            case 'DIRECTOR':
                return '/src/views/director/';
            case 'COORDINADOR':
                return '/src/views/coordinador/';
            case 'DOCENTE':
                return '/src/views/docente/';
            case 'PADRE':
                return '/src/views/padre/';
            default:
                return '/src/views/login.php';
        }
    }
    
    /**
     * Logout user and clear session
     * 
     * @param bool $destroySession Whether to destroy the entire session
     * @return bool True if logout successful, false otherwise
     */
    public static function logout($destroySession = true) {
        try {
            // Log logout action if user is logged in
            if (self::isLoggedIn()) {
                $user = self::getCurrentUser();
                if ($user && isset($user['cedula'])) {
                    // Log logout action
                    self::logLogout($user['cedula']);
                }
            }
            
            // Clear session data
            $_SESSION = array();
            
            if ($destroySession) {
                // Destroy session cookie
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                
                // Destroy session
                session_destroy();
            } else {
                // Just unset the session variables
                unset($_SESSION['user']);
                unset($_SESSION['logged_in']);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log logout action
     * 
     * @param string $cedula User's cedula
     * @return bool True if logged successfully, false otherwise
     */
    private static function logLogout($cedula) {
        try {
            // Load database configuration
            $dbConfig = require __DIR__ . '/../config/database.php';
            $database = new Database($dbConfig);
            $db = $database->getConnection();
            
            // Get user ID from cedula
            $userQuery = "SELECT id_usuario FROM usuario WHERE cedula = :cedula";
            $userStmt = $db->prepare($userQuery);
            $userStmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            $userStmt->execute();
            $userId = $userStmt->fetchColumn();
            
            if (!$userId) {
                return false;
            }
            
            // Log logout action
            $query = "INSERT INTO log (id_usuario, accion, detalle) VALUES (:id_usuario, :accion, :detalle)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':accion', 'LOGOUT', PDO::PARAM_STR);
            $stmt->bindValue(':detalle', 'Cierre de sesión', PDO::PARAM_STR);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Error logging logout: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if session has expired
     * 
     * @param int $timeoutMinutes Session timeout in minutes (default: 30)
     * @return bool True if session has expired, false otherwise
     */
    public static function isSessionExpired($timeoutMinutes = SESSION_TIMEOUT) {
        if (!self::isLoggedIn()) {
            return true;
        }
        
        return !isSessionValid();
    }
    
    /**
     * Update last activity timestamp
     */
    public static function updateLastActivity() {
        updateLastActivity();
    }
    
    /**
     * Check and handle session timeout
     * 
     * @param int $timeoutMinutes Session timeout in minutes (default: 30)
     * @return bool True if session is valid, false if expired
     */
    public static function checkSessionTimeout($timeoutMinutes = SESSION_TIMEOUT) {
        if (!isSessionValid()) {
            self::logout();
            return false;
        }
        
        self::updateLastActivity();
        return true;
    }
    
    /**
     * Get user's display name
     * 
     * @return string User's display name or 'Usuario' if not available
     */
    public static function getUserDisplayName() {
        $user = self::getCurrentUser();
        if ($user && isset($user['nombre']) && isset($user['apellido'])) {
            return $user['nombre'] . ' ' . $user['apellido'];
        }
        return 'Usuario';
    }
    
    /**
     * Get user's initials for avatar
     * 
     * @return string User's initials or 'U' if not available
     */
    public static function getUserInitials() {
        $user = self::getCurrentUser();
        if ($user && isset($user['nombre']) && isset($user['apellido'])) {
            return strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1));
        }
        return 'U';
    }
}
