<?php

/**
 * Modelo para gestión general de usuarios
 * Maneja operaciones CRUD para todos los tipos de usuarios
 */
class Usuario {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Obtener todos los usuarios con sus roles
     */
    public function getAllUsuarios() {
        try {
            $query = "SELECT u.*, 
                             STRING_AGG(r.nombre_rol, ', ') as roles,
                             STRING_AGG(r.nombre_rol, ', ') as role_names,
                             true as activo
                      FROM usuario u 
                      LEFT JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario 
                      LEFT JOIN rol r ON ur.nombre_rol = r.nombre_rol 
                      GROUP BY u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono, u.contrasena_hash
                      ORDER BY u.id_usuario DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo usuarios: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener usuario por ID
     */
    public function getUsuarioById($id) {
        try {
            $query = "SELECT u.*, 
                             STRING_AGG(r.nombre_rol, ', ') as roles,
                             STRING_AGG(r.nombre_rol, ', ') as role_names,
                             true as activo
                      FROM usuario u 
                      LEFT JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario 
                      LEFT JOIN rol r ON ur.nombre_rol = r.nombre_rol 
                      WHERE u.id_usuario = :id
                      GROUP BY u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono, u.contrasena_hash";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error obteniendo usuario por ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear nuevo usuario
     */
    public function createUsuario($usuarioData) {
        try {
            $this->db->beginTransaction();
            
            // Verificar si la cédula ya existe
            if ($this->cedulaExists($usuarioData['cedula'])) {
                throw new Exception("La cédula ya está en uso");
            }
            
            // Verificar si el email ya existe
            if ($this->emailExists($usuarioData['email'])) {
                throw new Exception("El email ya está en uso");
            }
            
            // Hash de la contraseña
            $passwordHash = password_hash($usuarioData['contrasena'], PASSWORD_DEFAULT);
            
            // Insertar usuario
            $userQuery = "INSERT INTO usuario (cedula, nombre, apellido, email, telefono, contrasena_hash) 
                         VALUES (:cedula, :nombre, :apellido, :email, :telefono, :contrasena_hash)";
            
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->bindValue(':cedula', $usuarioData['cedula'], PDO::PARAM_STR);
            $userStmt->bindValue(':nombre', $usuarioData['nombre'], PDO::PARAM_STR);
            $userStmt->bindValue(':apellido', $usuarioData['apellido'], PDO::PARAM_STR);
            $userStmt->bindValue(':email', $usuarioData['email'], PDO::PARAM_STR);
            $userStmt->bindValue(':telefono', $usuarioData['telefono'] ?? null, PDO::PARAM_STR);
            $userStmt->bindValue(':contrasena_hash', $passwordHash, PDO::PARAM_STR);
            
            $userStmt->execute();
            $userId = $this->db->lastInsertId();
            
            // Asignar roles
            if (!empty($usuarioData['roles'])) {
                $this->assignRoles($userId, $usuarioData['roles']);
            }
            
            // Log de la acción
            $this->logAction($userId, 'create', "Usuario creado: {$usuarioData['nombre']} {$usuarioData['apellido']}");
            
            $this->db->commit();
            return $userId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creando usuario: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Actualizar usuario
     */
    public function updateUsuario($id, $usuarioData) {
        try {
            $this->db->beginTransaction();
            
            // Obtener usuario actual
            $currentUsuario = $this->getUsuarioById($id);
            if (!$currentUsuario) {
                throw new Exception("Usuario no encontrado");
            }
            
            // Verificar si la cédula ya existe (excluyendo el usuario actual)
            if ($usuarioData['cedula'] !== $currentUsuario['cedula'] && $this->cedulaExists($usuarioData['cedula'])) {
                throw new Exception("La cédula ya está en uso");
            }
            
            // Verificar si el email ya existe (excluyendo el usuario actual)
            if ($usuarioData['email'] !== $currentUsuario['email'] && $this->emailExists($usuarioData['email'])) {
                throw new Exception("El email ya está en uso");
            }
            
            // Actualizar datos del usuario
            $updateQuery = "UPDATE usuario SET 
                           cedula = :cedula, 
                           nombre = :nombre, 
                           apellido = :apellido, 
                           email = :email, 
                           telefono = :telefono
                           WHERE id_usuario = :id_usuario";
            
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindValue(':cedula', $usuarioData['cedula'], PDO::PARAM_STR);
            $updateStmt->bindValue(':nombre', $usuarioData['nombre'], PDO::PARAM_STR);
            $updateStmt->bindValue(':apellido', $usuarioData['apellido'], PDO::PARAM_STR);
            $updateStmt->bindValue(':email', $usuarioData['email'], PDO::PARAM_STR);
            $updateStmt->bindValue(':telefono', $usuarioData['telefono'] ?? null, PDO::PARAM_STR);
            $updateStmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
            
            $updateStmt->execute();
            
            // Actualizar contraseña si se proporciona
            if (!empty($usuarioData['contrasena'])) {
                $passwordHash = password_hash($usuarioData['contrasena'], PASSWORD_DEFAULT);
                $passwordQuery = "UPDATE usuario SET contrasena_hash = :contrasena_hash WHERE id_usuario = :id_usuario";
                $passwordStmt = $this->db->prepare($passwordQuery);
                $passwordStmt->bindValue(':contrasena_hash', $passwordHash, PDO::PARAM_STR);
                $passwordStmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
                $passwordStmt->execute();
            }
            
            // Actualizar roles
            if (isset($usuarioData['roles'])) {
                $this->updateRoles($id, $usuarioData['roles']);
            }
            
            // Log de la acción
            $this->logAction($id, 'update', "Usuario actualizado: {$usuarioData['nombre']} {$usuarioData['apellido']}");
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error actualizando usuario: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Eliminar usuario
     */
    public function deleteUsuario($id) {
        try {
            $this->db->beginTransaction();
            
            // Obtener datos del usuario antes de eliminar
            $usuario = $this->getUsuarioById($id);
            if (!$usuario) {
                throw new Exception("Usuario no encontrado");
            }
            
            // Log de la acción (antes de eliminar registros)
            $this->logAction($id, 'delete', "Usuario eliminado: {$usuario['nombre']} {$usuario['apellido']}");
            
            // Eliminar registros de log del usuario
            $deleteLogQuery = "DELETE FROM log WHERE id_usuario = :id_usuario";
            $deleteLogStmt = $this->db->prepare($deleteLogQuery);
            $deleteLogStmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
            $deleteLogStmt->execute();
            
            // Eliminar roles del usuario
            $deleteRolesQuery = "DELETE FROM usuario_rol WHERE id_usuario = :id_usuario";
            $deleteRolesStmt = $this->db->prepare($deleteRolesQuery);
            $deleteRolesStmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
            $deleteRolesStmt->execute();
            
            // Eliminar usuario
            $deleteQuery = "DELETE FROM usuario WHERE id_usuario = :id_usuario";
            $deleteStmt = $this->db->prepare($deleteQuery);
            $deleteStmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
            $deleteStmt->execute();
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error eliminando usuario: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Buscar usuarios
     */
    public function searchUsuarios($searchTerm) {
        try {
            $searchPattern = '%' . $searchTerm . '%';
            $query = "SELECT u.*, 
                             STRING_AGG(r.nombre_rol, ', ') as roles,
                             STRING_AGG(r.nombre_rol, ', ') as role_names,
                             true as activo
                      FROM usuario u 
                      LEFT JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario 
                      LEFT JOIN rol r ON ur.nombre_rol = r.nombre_rol 
                      WHERE u.nombre ILIKE :search 
                         OR u.apellido ILIKE :search 
                         OR u.email ILIKE :search 
                         OR u.cedula ILIKE :search
                      GROUP BY u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono, u.contrasena_hash
                      ORDER BY u.id_usuario DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':search', $searchPattern, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error buscando usuarios: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todos los roles disponibles
     */
    public function getAllRoles() {
        try {
            $query = "SELECT * FROM rol ORDER BY nombre_rol";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo roles: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si la cédula existe
     */
    private function cedulaExists($cedula) {
        $query = "SELECT COUNT(*) FROM usuario WHERE cedula = :cedula";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Verificar si la cédula existe (método público)
     */
    public function checkCedulaExists($cedula) {
        return $this->cedulaExists($cedula);
    }
    
    /**
     * Verificar si el email existe
     */
    private function emailExists($email) {
        $query = "SELECT COUNT(*) FROM usuario WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Verificar si el email existe (método público)
     */
    public function checkEmailExists($email) {
        return $this->emailExists($email);
    }
    
    /**
     * Asignar roles a un usuario
     */
    private function assignRoles($userId, $roleNames) {
        foreach ($roleNames as $roleName) {
            $query = "INSERT INTO usuario_rol (id_usuario, nombre_rol) VALUES (:id_usuario, :nombre_rol)";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id_usuario', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':nombre_rol', $roleName, PDO::PARAM_STR);
            $stmt->execute();
        }
    }
    
    /**
     * Actualizar roles de un usuario
     */
    private function updateRoles($userId, $roleNames) {
        // Eliminar roles actuales
        $deleteQuery = "DELETE FROM usuario_rol WHERE id_usuario = :id_usuario";
        $deleteStmt = $this->db->prepare($deleteQuery);
        $deleteStmt->bindValue(':id_usuario', $userId, PDO::PARAM_INT);
        $deleteStmt->execute();
        
        // Asignar nuevos roles
        if (!empty($roleNames)) {
            $this->assignRoles($userId, $roleNames);
        }
    }
    
    /**
     * Registrar acción en el log
     */
    private function logAction($userId, $action, $details) {
        try {
            $query = "INSERT INTO log (id_usuario, accion, detalle, fecha) 
                     VALUES (:id_usuario, :accion, :detalle, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id_usuario', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':accion', $action, PDO::PARAM_STR);
            $stmt->bindValue(':detalle', $details, PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error registrando log: " . $e->getMessage());
        }
    }
    
    /**
     * Get user by cedula
     */
    public function getUserByCedula($cedula) {
        try {
            $query = "SELECT * FROM usuario WHERE cedula = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$cedula]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user by cedula: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        try {
            $query = "SELECT * FROM usuario WHERE email = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user by email: " . $e->getMessage());
            return false;
        }
    }
    
}
?>
