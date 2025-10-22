<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function authenticate($cedula, $password, $role) {
        try {
            if (!$this->validateCedula($cedula)) {
                return false;
            }
            
            $query = "SELECT u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono, 
                             u.contrasena_hash, ur.nombre_rol, r.descripcion as rol_descripcion
                      FROM usuario u 
                      INNER JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario
                      INNER JOIN rol r ON ur.nombre_rol = r.nombre_rol 
                      WHERE u.cedula = :cedula AND ur.nombre_rol = :role";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['contrasena_hash'])) {
                $this->logLogin($cedula, 'LOGIN_EXITOSO', 'Inicio de sesión exitoso');
                unset($user['contrasena_hash']);
                return $user;
            }
            
            $this->logLogin($cedula, 'LOGIN_FALLIDO', 'Intento de inicio de sesión fallido');
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error en autenticación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate cedula format (7-8 digits)
     * 
     * @param string $cedula Cedula to validate
     * @return bool True if valid, false otherwise
     */
    public function validateCedula($cedula) {
        return preg_match('/^\d{7,8}$/', $cedula);
    }
    
    /**
     * Create new user with cedula as primary key
     * 
     * @param array $userData User data including cedula, nombre, apellido, etc.
     * @return bool True if successful, false otherwise
     */
    public function createUser($userData) {
        try {
            if (empty($userData['cedula']) || empty($userData['nombre']) || 
                empty($userData['apellido']) || empty($userData['contrasena']) || 
                empty($userData['nombre_rol'])) {
                return false;
            }
            
            if (!$this->validateCedula($userData['cedula'])) {
                return false;
            }
            
            if ($this->cedulaExists($userData['cedula'])) {
                return false;
            }

            $passwordHash = password_hash($userData['contrasena'], PASSWORD_DEFAULT);
            
            $this->db->beginTransaction();
            
            try {

                $query = "INSERT INTO usuario (cedula, nombre, apellido, email, telefono, contrasena_hash) 
                          VALUES (:cedula, :nombre, :apellido, :email, :telefono, :contrasena_hash)";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':cedula', $userData['cedula'], PDO::PARAM_STR);
                $stmt->bindParam(':nombre', $userData['nombre'], PDO::PARAM_STR);
                $stmt->bindParam(':apellido', $userData['apellido'], PDO::PARAM_STR);
                $stmt->bindParam(':email', $userData['email'], PDO::PARAM_STR);
                $stmt->bindParam(':telefono', $userData['telefono'], PDO::PARAM_STR);
                $stmt->bindParam(':contrasena_hash', $passwordHash, PDO::PARAM_STR);
                
                $stmt->execute();
                
                $userId = $this->db->lastInsertId();

                $roleQuery = "INSERT INTO usuario_rol (id_usuario, nombre_rol) VALUES (:id_usuario, :nombre_rol)";
                $roleStmt = $this->db->prepare($roleQuery);
                $roleStmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
                $roleStmt->bindParam(':nombre_rol', $userData['nombre_rol'], PDO::PARAM_STR);
                
                $result = $roleStmt->execute();

                $this->db->commit();
            } catch (Exception $e) {

                $this->db->rollback();
                throw $e;
            }
            
            if ($result) {

                $this->logLogin($userData['cedula'], 'USUARIO_CREADO', 'Nuevo usuario creado');
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error creando usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if cedula already exists in the system
     * 
     * @param string $cedula Cedula to check
     * @return bool True if exists, false otherwise
     */
    public function cedulaExists($cedula) {
        try {
            $query = "SELECT COUNT(*) FROM usuario WHERE cedula = :cedula";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Error verificando cedula: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user password
     * 
     * @param string $cedula User's cedula
     * @param string $newPassword New password
     * @return bool True if successful, false otherwise
     */
    public function updatePassword($cedula, $newPassword) {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $query = "UPDATE usuario SET contrasena_hash = :contrasena_hash WHERE cedula = :cedula";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':contrasena_hash', $passwordHash, PDO::PARAM_STR);
            $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->logLogin($cedula, 'PASSWORD_ACTUALIZADA', 'Contraseña actualizada');
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error actualizando contraseña: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by cedula
     * 
     * @param string $cedula User's cedula
     * @return array|false User data if found, false otherwise
     */
    public function getUserByCedula($cedula) {
        try {
            $query = "SELECT u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono, 
                             ur.nombre_rol, r.descripcion as rol_descripcion
                      FROM usuario u 
                      INNER JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario
                      INNER JOIN rol r ON ur.nombre_rol = r.nombre_rol 
                      WHERE u.cedula = :cedula";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log user actions
     * 
     * @param string $cedula User's cedula
     * @param string $accion Action performed
     * @param string $detalle Action details
     * @return bool True if successful, false otherwise
     */
    private function logLogin($cedula, $accion, $detalle) {
        try {
            $userQuery = "SELECT id_usuario FROM usuario WHERE cedula = :cedula";
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            $userStmt->execute();
            $userId = $userStmt->fetchColumn();
            
            if (!$userId) {
                error_log("No se pudo encontrar usuario con cedula: " . $cedula);
                return false;
            }
            
            $query = "INSERT INTO log (id_usuario, accion, detalle) VALUES (:id_usuario, :accion, :detalle)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':accion', $accion, PDO::PARAM_STR);
            $stmt->bindParam(':detalle', $detalle, PDO::PARAM_STR);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error registrando log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all available roles
     * 
     * @return array|false Array of roles if successful, false otherwise
     */
    public function getRoles() {
        try {
            $query = "SELECT nombre_rol, descripcion FROM rol ORDER BY nombre_rol";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo roles: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logout user and log the action
     * 
     * @param string $cedula User's cedula
     * @return bool True if successful, false otherwise
     */
    public function logout($cedula) {
        try {

            $this->logLogin($cedula, 'LOGOUT', 'Cierre de sesión');

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = array();

                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }

                session_destroy();
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user session is valid
     * 
     * @param string $cedula User's cedula
     * @param int $timeoutMinutes Session timeout in minutes
     * @return bool True if session is valid, false otherwise
     */
    public function isSessionValid($cedula, $timeoutMinutes = 30) {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }
        
        if (!isset($_SESSION['user']) || $_SESSION['user']['cedula'] !== $cedula) {
            return false;
        }
        
        $lastActivity = $_SESSION['last_activity'] ?? time();
        $timeout = $timeoutMinutes * 60;
        
        if ((time() - $lastActivity) > $timeout) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Update user's last activity timestamp
     * 
     * @param string $cedula User's cedula
     * @return bool True if successful, false otherwise
     */
    public function updateLastActivity($cedula) {
        if (isset($_SESSION['user']) && $_SESSION['user']['cedula'] === $cedula) {
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }
}
