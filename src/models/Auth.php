<?php
class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Authenticate user using cedula and password
     * 
     * @param string $cedula User's cedula (7-8 digits)
     * @param string $password User's password
     * @param string $role User's role
     * @return array|false User data if successful, false if failed
     */
    public function authenticate($cedula, $password, $role) {
        try {
            // Validate cedula format
            if (!$this->validateCedula($cedula)) {
                return false;
            }
            
            // Prepare query to get user by cedula and role
            $query = "SELECT u.cedula, u.nombre, u.apellido, u.email, u.telefono, 
                             u.contrasena_hash, u.nombre_rol, r.descripcion as rol_descripcion
                      FROM usuario u 
                      INNER JOIN rol r ON u.nombre_rol = r.nombre_rol 
                      WHERE u.cedula = :cedula AND u.nombre_rol = :role";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['contrasena_hash'])) {
                // Log successful login
                $this->logLogin($cedula, 'LOGIN_EXITOSO', 'Inicio de sesión exitoso');
                
                // Return user data without password hash
                unset($user['contrasena_hash']);
                return $user;
            }
            
            // Log failed login attempt
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
            // Validate required fields
            if (empty($userData['cedula']) || empty($userData['nombre']) || 
                empty($userData['apellido']) || empty($userData['contrasena']) || 
                empty($userData['nombre_rol'])) {
                return false;
            }
            
            // Validate cedula format
            if (!$this->validateCedula($userData['cedula'])) {
                return false;
            }
            
            // Check if cedula already exists
            if ($this->cedulaExists($userData['cedula'])) {
                return false;
            }
            
            // Hash password
            $passwordHash = password_hash($userData['contrasena'], PASSWORD_DEFAULT);
            
            $query = "INSERT INTO usuario (cedula, nombre, apellido, email, telefono, contrasena_hash, nombre_rol) 
                      VALUES (:cedula, :nombre, :apellido, :email, :telefono, :contrasena_hash, :nombre_rol)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':cedula', $userData['cedula'], PDO::PARAM_STR);
            $stmt->bindParam(':nombre', $userData['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':apellido', $userData['apellido'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $userData['email'], PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $userData['telefono'], PDO::PARAM_STR);
            $stmt->bindParam(':contrasena_hash', $passwordHash, PDO::PARAM_STR);
            $stmt->bindParam(':nombre_rol', $userData['nombre_rol'], PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if ($result) {
                // Log user creation
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
            $query = "SELECT u.cedula, u.nombre, u.apellido, u.email, u.telefono, 
                             u.nombre_rol, r.descripcion as rol_descripcion
                      FROM usuario u 
                      INNER JOIN rol r ON u.nombre_rol = r.nombre_rol 
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
            $query = "INSERT INTO log (cedula_usuario, accion, detalle) VALUES (:cedula, :accion, :detalle)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
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
}
