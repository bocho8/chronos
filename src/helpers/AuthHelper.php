<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Database.php';

class AuthHelper {
    
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']);
    }
    
    public static function getCurrentUser() {
        return self::isLoggedIn() ? $_SESSION['user'] : null;
    }
    
    public static function getCurrentUserRole() {
        $user = self::getCurrentUser();
        return $user ? $user['nombre_rol'] : null;
    }
    
    public static function hasRole($role) {
        return self::getCurrentUserRole() === $role;
    }
    
    public static function hasAnyRole($roles) {
        $userRole = self::getCurrentUserRole();
        return in_array($userRole, $roles);
    }
    
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
    
    public static function requireRole($requiredRole, $redirectUrl = null) {
        self::requireLogin($redirectUrl);
        
        $hasRole = is_array($requiredRole) 
            ? self::hasAnyRole($requiredRole)
            : self::hasRole($requiredRole);
            
        if (!$hasRole) {
            self::redirectToLogin($redirectUrl);
        }
    }
    
    private static function redirectToLogin($redirectUrl = null) {
        $loginUrl = '/src/views/login.php';
        if ($redirectUrl) {
            $loginUrl .= '?redirect=' . urlencode($redirectUrl);
        }
        header("Location: $loginUrl");
        exit();
    }
    
    public static function redirectIfLoggedIn() {
        if (self::isLoggedIn()) {
            $userRole = self::getCurrentUserRole();
            $redirectUrl = self::getRoleRedirectUrl($userRole);
            header("Location: $redirectUrl");
            exit();
        }
    }
    
    public static function getRoleRedirectUrl($role) {
        return match ($role) {
            'ADMIN' => '/src/views/admin/',
            'DIRECTOR' => '/src/views/director/',
            'COORDINADOR' => '/src/views/coordinador/',
            'DOCENTE' => '/src/views/docente/',
            'PADRE' => '/src/views/padre/',
            default => '/src/views/login.php'
        };
    }
    
    public static function logout($destroySession = true) {
        try {
            if (self::isLoggedIn()) {
                $user = self::getCurrentUser();
                if ($user && isset($user['cedula'])) {
                    self::logLogout($user['cedula']);
                }
            }
            
            $_SESSION = array();
            
            if ($destroySession) {
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();
            } else {
                unset($_SESSION['user'], $_SESSION['logged_in']);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }
    
    private static function logLogout($cedula) {
        try {
            $dbConfig = require __DIR__ . '/../config/database.php';
            $database = new Database($dbConfig);
            $db = $database->getConnection();
            
            $userQuery = "SELECT id_usuario FROM usuario WHERE cedula = :cedula";
            $userStmt = $db->prepare($userQuery);
            $userStmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            $userStmt->execute();
            $userId = $userStmt->fetchColumn();
            
            if (!$userId) {
                return false;
            }
            
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
    
    public static function isSessionExpired($timeoutMinutes = SESSION_TIMEOUT) {
        return !self::isLoggedIn() || !isSessionValid();
    }
    
    public static function updateLastActivity() {
        updateLastActivity();
    }
    
    public static function checkSessionTimeout($timeoutMinutes = SESSION_TIMEOUT) {
        if (!isSessionValid()) {
            self::logout();
            return false;
        }
        
        self::updateLastActivity();
        return true;
    }
    
    public static function getUserDisplayName() {
        $user = self::getCurrentUser();
        return ($user && isset($user['nombre'], $user['apellido'])) 
            ? $user['nombre'] . ' ' . $user['apellido'] 
            : 'Usuario';
    }
    
    public static function getUserInitials() {
        $user = self::getCurrentUser();
        return ($user && isset($user['nombre'], $user['apellido'])) 
            ? strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1))
            : 'U';
    }
}
