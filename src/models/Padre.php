<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Padre Model
 * Modelo para gestionar padres y sus relaciones con grupos
 */

class Padre {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Asigna un grupo a un padre
     */
    public function assignGroupToParent($id_padre, $id_grupo) {
        try {
            $query = "INSERT INTO padre_grupo (id_padre, id_grupo) VALUES (:id_padre, :id_grupo)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_padre', $id_padre, PDO::PARAM_INT);
            $stmt->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error assigning group to parent: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remueve un grupo de un padre
     */
    public function removeGroupFromParent($id_padre, $id_grupo) {
        try {
            $query = "DELETE FROM padre_grupo WHERE id_padre = :id_padre AND id_grupo = :id_grupo";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_padre', $id_padre, PDO::PARAM_INT);
            $stmt->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error removing group from parent: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene los grupos asignados a un padre
     */
    public function getGroupsForParent($id_padre) {
        try {
            $query = "SELECT g.*, pg.fecha_asignacion 
                     FROM grupo g 
                     INNER JOIN padre_grupo pg ON g.id_grupo = pg.id_grupo 
                     WHERE pg.id_padre = :id_padre 
                     ORDER BY g.nivel, g.nombre";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_padre', $id_padre, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting groups for parent: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene los padres asignados a un grupo
     */
    public function getParentsForGroup($id_grupo) {
        try {
            $query = "SELECT u.*, p.id_padre, pg.fecha_asignacion 
                     FROM usuario u 
                     INNER JOIN padre p ON u.id_usuario = p.id_usuario 
                     INNER JOIN padre_grupo pg ON p.id_padre = pg.id_padre 
                     WHERE pg.id_grupo = :id_grupo 
                     ORDER BY u.apellido, u.nombre";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting parents for group: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene todos los padres con sus grupos asignados
     */
    public function getAllParentsWithGroups() {
        try {
            $query = "SELECT DISTINCT u.*, p.id_padre,
                            STRING_AGG(g.nombre, ', ' ORDER BY g.nivel, g.nombre) as grupos_asignados,
                            COUNT(pg.id_grupo) as total_grupos
                     FROM usuario u 
                     INNER JOIN padre p ON u.id_usuario = p.id_usuario 
                     LEFT JOIN padre_grupo pg ON p.id_padre = pg.id_padre 
                     LEFT JOIN grupo g ON pg.id_grupo = g.id_grupo 
                     GROUP BY u.id_usuario, p.id_padre, u.cedula, u.nombre, u.apellido, u.email, u.telefono
                     ORDER BY u.apellido, u.nombre";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all parents with groups: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si un padre tiene acceso a un grupo específico
     */
    public function hasAccessToGroup($id_padre, $id_grupo) {
        try {
            $query = "SELECT COUNT(*) FROM padre_grupo WHERE id_padre = :id_padre AND id_grupo = :id_grupo";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_padre', $id_padre, PDO::PARAM_INT);
            $stmt->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking parent access to group: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el ID del padre por ID de usuario
     */
    public function getPadreIdByUsuarioId($id_usuario) {
        try {
            $query = "SELECT id_padre FROM padre WHERE id_usuario = :id_usuario";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id_padre'] : false;
        } catch (PDOException $e) {
            error_log("Error getting padre ID by usuario ID: " . $e->getMessage());
            return false;
        }
    }
}
?>
