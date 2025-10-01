<?php
/**
 * Grupo Model
 * Modelo para gestionar grupos del sistema
 */

class Grupo {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Obtiene todos los grupos
     */
    public function getAllGrupos() {
        try {
            $query = "SELECT * FROM grupo ORDER BY nivel, nombre";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all grupos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene un grupo por ID
     */
    public function getGrupoById($idGrupo) {
        try {
            $query = "SELECT * FROM grupo WHERE id_grupo = :id_grupo";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting grupo by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea un nuevo grupo
     */
    public function createGrupo($nombre, $nivel) {
        try {
            // Verificar si ya existe un grupo con el mismo nombre y nivel
            $checkQuery = "SELECT COUNT(*) FROM grupo WHERE nombre = :nombre AND nivel = :nivel";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':nombre', $nombre);
            $checkStmt->bindParam(':nivel', $nivel);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Ya existe un grupo con este nombre y nivel'];
            }
            
            $query = "INSERT INTO grupo (nombre, nivel) VALUES (:nombre, :nivel)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':nivel', $nivel);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Grupo creado exitosamente', 'id' => $this->db->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Error al crear el grupo'];
            }
        } catch (PDOException $e) {
            error_log("Error creating grupo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }
    
    /**
     * Actualiza un grupo existente
     */
    public function updateGrupo($idGrupo, $nombre, $nivel) {
        try {
            // Verificar si ya existe otro grupo con el mismo nombre y nivel
            $checkQuery = "SELECT COUNT(*) FROM grupo WHERE nombre = :nombre AND nivel = :nivel AND id_grupo != :id_grupo";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':nombre', $nombre);
            $checkStmt->bindParam(':nivel', $nivel);
            $checkStmt->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Ya existe otro grupo con este nombre y nivel'];
            }
            
            $query = "UPDATE grupo SET nombre = :nombre, nivel = :nivel WHERE id_grupo = :id_grupo";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':nivel', $nivel);
            $stmt->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return ['success' => true, 'message' => 'Grupo actualizado exitosamente'];
                } else {
                    return ['success' => false, 'message' => 'No se encontró el grupo o no hubo cambios'];
                }
            } else {
                return ['success' => false, 'message' => 'Error al actualizar el grupo'];
            }
        } catch (PDOException $e) {
            error_log("Error updating grupo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }
    
    /**
     * Elimina un grupo
     */
    public function deleteGrupo($idGrupo) {
        try {
            // Verificar si el grupo tiene horarios asignados
            $checkQuery = "SELECT COUNT(*) FROM horario WHERE id_grupo = :id_grupo";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'No se puede eliminar el grupo porque tiene horarios asignados'];
            }
            
            // Verificar si el grupo tiene materias compartidas
            $checkQuery2 = "SELECT COUNT(*) FROM materia WHERE id_grupo_compartido = :id_grupo";
            $checkStmt2 = $this->db->prepare($checkQuery2);
            $checkStmt2->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            $checkStmt2->execute();
            
            if ($checkStmt2->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'No se puede eliminar el grupo porque tiene materias compartidas asignadas'];
            }
            
            $query = "DELETE FROM grupo WHERE id_grupo = :id_grupo";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return ['success' => true, 'message' => 'Grupo eliminado exitosamente'];
                } else {
                    return ['success' => false, 'message' => 'No se encontró el grupo'];
                }
            } else {
                return ['success' => false, 'message' => 'Error al eliminar el grupo'];
            }
        } catch (PDOException $e) {
            error_log("Error deleting grupo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }
    
    /**
     * Obtiene grupos por nivel
     */
    public function getGruposByNivel($nivel) {
        try {
            $query = "SELECT * FROM grupo WHERE nivel = :nivel ORDER BY nombre";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nivel', $nivel);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting grupos by nivel: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene estadísticas de grupos
     */
    public function getGruposStats() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_grupos,
                        COUNT(DISTINCT nivel) as total_niveles,
                        nivel,
                        COUNT(*) as grupos_por_nivel
                      FROM grupo 
                      GROUP BY nivel
                      ORDER BY nivel";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting grupos stats: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca grupos por nombre o nivel
     */
    public function searchGrupos($searchTerm) {
        try {
            $query = "SELECT * FROM grupo 
                      WHERE nombre ILIKE :search OR nivel ILIKE :search 
                      ORDER BY nivel, nombre";
            $stmt = $this->db->prepare($query);
            $searchPattern = '%' . $searchTerm . '%';
            $stmt->bindParam(':search', $searchPattern);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching grupos: " . $e->getMessage());
            return false;
        }
    }
}
