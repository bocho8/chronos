<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

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
    public function createCoordinator($coordinadorData) {
        try {
            $this->db->beginTransaction();

            if (empty($coordinadorData['cedula']) || empty($coordinadorData['nombre']) || 
                empty($coordinadorData['apellido']) || empty($coordinadorData['email']) || 
                empty($coordinadorData['contrasena'])) {
                throw new Exception("Todos los campos son requeridos");
            }

            if ($this->cedulaExists($coordinadorData['cedula'])) {
                throw new Exception("La cédula ya está en uso");
            }

            if ($this->emailExists($coordinadorData['email'])) {
                throw new Exception("El email ya está en uso");
            }

            $passwordHash = password_hash($coordinadorData['contrasena'], PASSWORD_DEFAULT);

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

            $this->assignRole($userId, 'COORDINADOR');

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
    public function updateCoordinator($id, $coordinadorData) {
        try {
            $this->db->beginTransaction();

            $currentCoordinador = $this->getCoordinadorById($id);
            if (!$currentCoordinador) {
                throw new Exception("Coordinador no encontrado");
            }

            if ($coordinadorData['cedula'] !== $currentCoordinador['cedula'] && $this->cedulaExists($coordinadorData['cedula'])) {
                throw new Exception("La cédula ya está en uso");
            }

            if ($coordinadorData['email'] !== $currentCoordinador['email'] && $this->emailExists($coordinadorData['email'])) {
                throw new Exception("El email ya está en uso");
            }

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

            if (!empty($coordinadorData['contrasena'])) {
                $passwordHash = password_hash($coordinadorData['contrasena'], PASSWORD_DEFAULT);
                $passwordQuery = "UPDATE usuario SET contrasena_hash = :contrasena_hash WHERE id_usuario = :id_usuario";
                $passwordStmt = $this->db->prepare($passwordQuery);
                $passwordStmt->bindValue(':contrasena_hash', $passwordHash, PDO::PARAM_STR);
                $passwordStmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
                $passwordStmt->execute();
            }

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
    public function deleteCoordinator($id) {
        try {
            $this->db->beginTransaction();

            $coordinador = $this->getCoordinadorById($id);
            if (!$coordinador) {
                throw new Exception("Coordinador no encontrado");
            }

            $this->logAction($id, 'delete', "Coordinador eliminado: {$coordinador['nombre']} {$coordinador['apellido']}");

            $deleteLogQuery = "DELETE FROM log WHERE id_usuario = :id_usuario";
            $deleteLogStmt = $this->db->prepare($deleteLogQuery);
            $deleteLogStmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
            $deleteLogStmt->execute();

            $deleteRolesQuery = "DELETE FROM usuario_rol WHERE id_usuario = :id_usuario";
            $deleteRolesStmt = $this->db->prepare($deleteRolesQuery);
            $deleteRolesStmt->bindValue(':id_usuario', $id, PDO::PARAM_INT);
            $deleteRolesStmt->execute();

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
