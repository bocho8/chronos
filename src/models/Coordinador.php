<?php

class Coordinador {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getAllCoordinadores() {
        try {
            $query = "SELECT u.*, 
                             STRING_AGG(r.nombre_rol, ', ') as roles,
                             STRING_AGG(r.nombre_rol, ', ') as role_names,
                             true as activo
                      FROM usuario u 
                      INNER JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario 
                      INNER JOIN rol r ON ur.nombre_rol = r.nombre_rol 
                      WHERE r.nombre_rol = 'COORDINADOR'
                      GROUP BY u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono, u.contrasena_hash
                      ORDER BY u.id_usuario DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo coordinadores: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCoordinadorById($id) {
        try {
            $query = "SELECT u.*, 
                             STRING_AGG(r.nombre_rol, ', ') as roles,
                             STRING_AGG(r.nombre_rol, ', ') as role_names,
                             true as activo
                      FROM usuario u 
                      INNER JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario 
                      INNER JOIN rol r ON ur.nombre_rol = r.nombre_rol 
                      WHERE u.id_usuario = :id AND r.nombre_rol = 'COORDINADOR'
                      GROUP BY u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono, u.contrasena_hash";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error obteniendo coordinador por ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear nuevo coordinador
     */
    public function createCoordinador($coordinadorData) {
        try {
            $this->db->beginTransaction();
            
            // Validar datos requeridos
            if (empty($coordinadorData['cedula']) || empty($coordinadorData['nombre']) || 
                empty($coordinadorData['apellido']) || empty($coordinadorData['email']) || 
                empty($coordinadorData['contrasena'])) {
                throw new Exception("Todos los campos son requeridos");
            }
            
            // Verificar si la cédula ya existe
            if ($this->cedulaExists($coordinadorData['cedula'])) {
                throw new Exception("La cédula ya está en uso");
            }
            
            // Verificar si el email ya existe
            if ($this->emailExists($coordinadorData['email'])) {
                throw new Exception("El email ya está en uso");
            }
            
            // Hash de la contraseña
            $passwordHash = password_hash($coordinadorData['contrasena'], PASSWORD_DEFAULT);
            
            // Insertar usuario
            $userQuery = "INSERT INTO usuario (cedula, nombre, apellido, email, telefono, contrasena_hash) 
                         VALUES (:cedula, :nombre, :apellido, :email, :telefono, :contrasena_hash)";
            
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->bindValue(':cedula', $coordinadorData['cedula'], PDO::PARAM_STR);
            $userStmt->bindValue(':nombre', $coordinadorData['nombre'], PDO::PARAM_STR);
            $userStmt->bindValue(':apellido', $coordinadorData['apellido'], PDO::PARAM_STR);
            $userStmt->bindValue(':email', $coordinadorData['email'], PDO::PARAM_STR);
            $userStmt->bindValue(':telefono', $coordinadorData['telefono'] ?? null, PDO::PARAM_STR);
            $userStmt->bindValue(':contrasena_hash', $passwordHash, PDO::PARAM_STR);
            
            $userStmt->execute();
            $userId = $this->db->lastInsertId();
            
            // Asignar rol de coordinador
            $this->assignRole($userId, 'COORDINADOR');
            
            // Log de la acción
            $this->logAction($userId, 'create', "Coordinador creado: {$coordinadorData['nombre']} {$coordinadorData['apellido']}");
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creando coordinador: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Actualizar coordinador
     */
    public function updateCoordinador($id, $coordinadorData) {
        try {
            $this->db->beginTransaction();
            
            // Obtener coordinador actual
            $currentCoordinador = $this->getCoordinadorById($id);
            if (!$currentCoordinador) {
                throw new Exception("Coordinador no encontrado");
            }
            
            // Verificar si la cédula ya existe (excluyendo el coordinador actual)
            if ($coordinadorData['cedula'] !== $currentCoordinador['cedula'] && $this->cedulaExists($coordinadorData['cedula'])) {
                throw new Exception("La cédula ya está en uso");
            }
            
            // Verificar si el email ya existe (excluyendo el coordinador actual)
            if ($coordinadorData['email'] !== $currentCoordinador['email'] && $this->emailExists($coordinadorData['email'])) {
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
            $updateStmt->bindValue(':cedula', $coordinadorData['cedula'], PDO::PARAM_STR);
            $updateStmt->bindValue(':nombre', $coordinadorData['nombre'], PDO::PARAM_STR);
            $updateStmt->bindValue(':apellido', $coordinadorData['apellido'], PDO::PARAM_STR);
            $updateStmt->bindValue(':email', $coordinadorData['email'], PDO::PARAM_STR);
            $updateStmt->bindValue(':telefono', $coordinadorData['telefono'] ?? null, PDO::PARAM_STR);
            $updateStmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
            
            $updateStmt->execute();
            
            // Actualizar contraseña si se proporciona
            if (!empty($coordinadorData['contrasena'])) {
                $passwordHash = password_hash($coordinadorData['contrasena'], PASSWORD_DEFAULT);
                $passwordQuery = "UPDATE usuario SET contrasena_hash = :contrasena_hash WHERE id_usuario = :id_usuario";
                $passwordStmt = $this->db->prepare($passwordQuery);
                $passwordStmt->bindValue(':contrasena_hash', $passwordHash, PDO::PARAM_STR);
                $passwordStmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
                $passwordStmt->execute();
            }
            
            // Log de la acción
            $this->logAction($id, 'update', "Coordinador actualizado: {$coordinadorData['nombre']} {$coordinadorData['apellido']}");
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error actualizando coordinador: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Eliminar coordinador
     */
    public function deleteCoordinador($id) {
        try {
            $this->db->beginTransaction();
            
            // Obtener datos del coordinador antes de eliminar
            $coordinador = $this->getCoordinadorById($id);
            if (!$coordinador) {
                throw new Exception("Coordinador no encontrado");
            }
            
            // Log de la acción (antes de eliminar registros)
            $this->logAction($id, 'delete', "Coordinador eliminado: {$coordinador['nombre']} {$coordinador['apellido']}");
            
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
            error_log("Error eliminando coordinador: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Buscar coordinadores
     */
    public function searchCoordinadores($searchTerm) {
        try {
            $searchPattern = '%' . $searchTerm . '%';
            $query = "SELECT u.*, 
                             STRING_AGG(r.nombre_rol, ', ') as roles,
                             STRING_AGG(r.nombre_rol, ', ') as role_names,
                             true as activo
                      FROM usuario u 
                      INNER JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario 
                      INNER JOIN rol r ON ur.nombre_rol = r.nombre_rol 
                      WHERE r.nombre_rol = 'COORDINADOR'
                        AND (u.nombre ILIKE :search 
                         OR u.apellido ILIKE :search 
                         OR u.email ILIKE :search 
                         OR u.cedula ILIKE :search)
                      GROUP BY u.id_usuario, u.cedula, u.nombre, u.apellido, u.email, u.telefono, u.contrasena_hash
                      ORDER BY u.id_usuario DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':search', $searchPattern, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error buscando coordinadores: " . $e->getMessage());
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
     * Asignar rol a un usuario
     */
    private function assignRole($userId, $roleName) {
        $query = "INSERT INTO usuario_rol (id_usuario, nombre_rol) VALUES (:id_usuario, :nombre_rol)";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id_usuario', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre_rol', $roleName, PDO::PARAM_STR);
        $stmt->execute();
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
}
?>
