<?php
/**
 * Horario Model
 * Maneja operaciones CRUD para horarios y disponibilidad
 */
class Horario {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Obtiene todos los horarios con información relacionada
     */
    public function getAllHorarios() {
        try {
            $query = "SELECT h.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre, m.horas_semanales,
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN docente d ON h.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     ORDER BY h.dia, b.hora_inicio, g.nombre";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all horarios: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene horarios por grupo
     */
    public function getHorariosByGrupo($idGrupo) {
        try {
            $query = "SELECT h.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre,
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN docente d ON h.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     WHERE h.id_grupo = :id_grupo
                     ORDER BY h.dia, b.hora_inicio";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $idGrupo, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting horarios by grupo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene horarios por docente
     */
    public function getHorariosByDocente($idDocente) {
        try {
            $query = "SELECT h.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     WHERE h.id_docente = :id_docente
                     ORDER BY h.dia, b.hora_inicio";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting horarios by docente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea una nueva asignación de horario
     */
    public function createHorario($data) {
        try {
            // Validar datos requeridos
            $requiredFields = ['id_grupo', 'id_docente', 'id_materia', 'id_bloque', 'dia'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }
            
            // Verificar que no existe conflicto de horario
            if ($this->hasScheduleConflict($data)) {
                throw new Exception("Ya existe una asignación para este horario");
            }
            
            // Verificar disponibilidad del docente
            if (!$this->isDocenteAvailable($data['id_docente'], $data['id_bloque'], $data['dia'])) {
                throw new Exception("El docente no está disponible en este horario");
            }
            
            $query = "INSERT INTO horario (id_grupo, id_docente, id_materia, id_bloque, dia) 
                     VALUES (:id_grupo, :id_docente, :id_materia, :id_bloque, :dia)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $data['id_grupo'], PDO::PARAM_INT);
            $stmt->bindParam(':id_docente', $data['id_docente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_materia', $data['id_materia'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            
            $stmt->execute();
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating horario: " . $e->getMessage());
            throw new Exception("Error al crear el horario: " . $e->getMessage());
        }
    }
    
    /**
     * Actualiza una asignación de horario
     */
    public function updateHorario($id, $data) {
        try {
            // Verificar que el horario existe
            if (!$this->getHorarioById($id)) {
                throw new Exception("El horario no existe");
            }
            
            // Verificar conflictos (excluyendo el horario actual)
            if ($this->hasScheduleConflict($data, $id)) {
                throw new Exception("Ya existe una asignación para este horario");
            }
            
            // Verificar disponibilidad del docente
            if (!empty($data['id_docente']) && !empty($data['id_bloque']) && !empty($data['dia'])) {
                if (!$this->isDocenteAvailable($data['id_docente'], $data['id_bloque'], $data['dia'], $id)) {
                    throw new Exception("El docente no está disponible en este horario");
                }
            }
            
            $query = "UPDATE horario SET 
                     id_grupo = :id_grupo,
                     id_docente = :id_docente,
                     id_materia = :id_materia,
                     id_bloque = :id_bloque,
                     dia = :dia
                     WHERE id_horario = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':id_grupo', $data['id_grupo'], PDO::PARAM_INT);
            $stmt->bindParam(':id_docente', $data['id_docente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_materia', $data['id_materia'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating horario: " . $e->getMessage());
            throw new Exception("Error al actualizar el horario: " . $e->getMessage());
        }
    }
    
    /**
     * Elimina una asignación de horario
     */
    public function deleteHorario($id) {
        try {
            // Verificar que el horario existe
            if (!$this->getHorarioById($id)) {
                throw new Exception("El horario no existe");
            }
            
            $query = "DELETE FROM horario WHERE id_horario = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting horario: " . $e->getMessage());
            throw new Exception("Error al eliminar el horario: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene un horario por ID
     */
    public function getHorarioById($id) {
        try {
            $query = "SELECT h.*, 
                            g.nombre as grupo_nombre, g.nivel as grupo_nivel,
                            m.nombre as materia_nombre,
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN docente d ON h.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     WHERE h.id_horario = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting horario by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si hay conflicto de horario
     */
    private function hasScheduleConflict($data, $excludeId = null) {
        try {
            // Verificar conflicto de grupo
            $query = "SELECT id_horario FROM horario 
                     WHERE id_grupo = :id_grupo AND id_bloque = :id_bloque AND dia = :dia";
            if ($excludeId) {
                $query .= " AND id_horario != :exclude_id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_grupo', $data['id_grupo'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            
            if ($stmt->fetch()) {
                return true;
            }
            
            // Verificar conflicto de docente
            $query = "SELECT id_horario FROM horario 
                     WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            if ($excludeId) {
                $query .= " AND id_horario != :exclude_id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $data['id_docente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $data['id_bloque'], PDO::PARAM_INT);
            $stmt->bindParam(':dia', $data['dia']);
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error checking schedule conflict: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si un docente está disponible en un horario específico
     */
    private function isDocenteAvailable($idDocente, $idBloque, $dia, $excludeHorarioId = null) {
        try {
            $query = "SELECT disponible FROM disponibilidad 
                     WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
            $stmt->bindParam(':dia', $dia);
            $stmt->execute();
            
            $disponibilidad = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si no hay registro de disponibilidad, asumir que está disponible
            if (!$disponibilidad) {
                return true;
            }
            
            return $disponibilidad['disponible'];
        } catch (PDOException $e) {
            error_log("Error checking docente availability: " . $e->getMessage());
            return true; // Asumir disponible en caso de error
        }
    }
    
    /**
     * Obtiene la disponibilidad de un docente
     */
    public function getDocenteDisponibilidad($idDocente) {
        try {
            $query = "SELECT d.*, b.hora_inicio, b.hora_fin
                     FROM disponibilidad d
                     JOIN bloque_horario b ON d.id_bloque = b.id_bloque
                     WHERE d.id_docente = :id_docente
                     ORDER BY d.dia, b.hora_inicio";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting docente disponibilidad: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualiza la disponibilidad de un docente
     */
    public function updateDocenteDisponibilidad($idDocente, $idBloque, $dia, $disponible) {
        try {
            // Verificar si ya existe el registro
            $query = "SELECT id_disponibilidad FROM disponibilidad 
                     WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
            $stmt->bindParam(':dia', $dia);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                // Actualizar registro existente
                $query = "UPDATE disponibilidad SET disponible = :disponible 
                         WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            } else {
                // Crear nuevo registro
                $query = "INSERT INTO disponibilidad (id_docente, id_bloque, dia, disponible) 
                         VALUES (:id_docente, :id_bloque, :dia, :disponible)";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
            $stmt->bindParam(':dia', $dia);
            $stmt->bindParam(':disponible', $disponible, PDO::PARAM_BOOL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating docente disponibilidad: " . $e->getMessage());
            throw new Exception("Error al actualizar la disponibilidad: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene todos los bloques horarios
     */
    public function getAllBloques() {
        try {
            $query = "SELECT * FROM bloque_horario ORDER BY hora_inicio";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all bloques: " . $e->getMessage());
            return [];
        }
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
            return [];
        }
    }
    
    /**
     * Obtiene todas las materias
     */
    public function getAllMaterias() {
        try {
            $query = "SELECT * FROM materia ORDER BY nombre";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all materias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene todos los docentes
     */
    public function getAllDocentes() {
        try {
            $query = "SELECT d.id_docente, u.nombre, u.apellido, u.email
                     FROM docente d
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     ORDER BY u.apellido, u.nombre";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all docentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene horarios no publicados
     */
    public function getUnpublishedSchedules() {
        try {
            // For now, we'll simulate unpublished schedules based on horario table
            // In a real implementation, you might have a separate table for schedule generations
            $query = "SELECT DISTINCT 
                        1 as id_horario,
                        'Horario Generado' as nombre,
                        'Horario generado automáticamente para todos los grupos' as descripcion,
                        NOW() as fecha_creacion,
                        0 as publicado
                     FROM horario 
                     WHERE EXISTS (SELECT 1 FROM horario)
                     LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check if there are actually unpublished schedules
            $publishedCheck = $this->db->prepare("SELECT COUNT(*) as count FROM horario_publicado WHERE activo = 1");
            $publishedCheck->execute();
            $publishedCount = $publishedCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($publishedCount && $publishedCount['count'] > 0) {
                return []; // Already published
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting unpublished schedules: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene horarios publicados
     */
    public function getPublishedSchedules() {
        try {
            $query = "SELECT hp.*, 
                            hp.fecha_publicacion,
                            'Horario Oficial' as nombre,
                            'Horario publicado y visible para todos los usuarios' as descripcion
                     FROM horario_publicado hp
                     WHERE hp.activo = 1
                     ORDER BY hp.fecha_publicacion DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting published schedules: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Publica un horario
     */
    public function publishSchedule($scheduleId) {
        try {
            $this->db->beginTransaction();
            
            // First, check if table exists, if not create it
            $createTableQuery = "CREATE TABLE IF NOT EXISTS horario_publicado (
                id_publicacion SERIAL PRIMARY KEY,
                id_horario_referencia INTEGER,
                fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                publicado_por INTEGER,
                activo BOOLEAN DEFAULT TRUE,
                descripcion TEXT
            )";
            $this->db->exec($createTableQuery);
            
            // Deactivate any existing published schedules
            $deactivateQuery = "UPDATE horario_publicado SET activo = FALSE WHERE activo = TRUE";
            $this->db->exec($deactivateQuery);
            
            // Insert new published schedule
            $publishQuery = "INSERT INTO horario_publicado (id_horario_referencia, descripcion, activo) 
                           VALUES (?, 'Horario oficial publicado por la dirección', TRUE)";
            
            $stmt = $this->db->prepare($publishQuery);
            $result = $stmt->execute([$scheduleId]);
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error publishing schedule: " . $e->getMessage());
            return false;
        }
    }
}
