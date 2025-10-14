<?php

class Horario {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
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

            $requiredFields = ['id_grupo', 'id_docente', 'id_materia', 'id_bloque', 'dia'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }

            if ($this->hasScheduleConflict($data)) {
                throw new Exception("Ya existe una asignación para este horario");
            }

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

            if (!$this->getHorarioById($id)) {
                throw new Exception("El horario no existe");
            }

            if ($this->hasScheduleConflict($data, $id)) {
                throw new Exception("Ya existe una asignación para este horario");
            }

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
     * Obtiene un horario completo con todos los datos necesarios para edición
     */
    public function getHorarioCompletoById($id) {
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
                     WHERE h.id_horario = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting complete horario by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si hay conflicto de horario
     */
    private function hasScheduleConflict($data, $excludeId = null) {
        try {

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

            if (!$disponibilidad) {
                return true;
            }
            
            return $disponibilidad['disponible'];
        } catch (PDOException $e) {
            error_log("Error checking docente availability: " . $e->getMessage());
            return true;
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

            $query = "SELECT id_disponibilidad FROM disponibilidad 
                     WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_docente', $idDocente, PDO::PARAM_INT);
            $stmt->bindParam(':id_bloque', $idBloque, PDO::PARAM_INT);
            $stmt->bindParam(':dia', $dia);
            $stmt->execute();
            
            if ($stmt->fetch()) {

                $query = "UPDATE disponibilidad SET disponible = :disponible 
                         WHERE id_docente = :id_docente AND id_bloque = :id_bloque AND dia = :dia";
            } else {

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
            return null;
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
            
            $publishedCheck = $this->db->prepare("SELECT COUNT(*) as count FROM horario_publicado WHERE activo = 1");
            $publishedCheck->execute();
            $publishedCount = $publishedCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($publishedCount && $publishedCount['count'] > 0) {
                return [];
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
     * Obtiene docentes asignados a una materia específica
     */
    public function getTeachersBySubject($subjectId) {
        try {
            $query = "SELECT d.id_docente, u.nombre, u.apellido 
                     FROM docente_materia dm
                     JOIN docente d ON dm.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     WHERE dm.id_materia = :id_materia
                     ORDER BY u.apellido, u.nombre";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_materia', $subjectId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting teachers by subject: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene todas las materias con conteo de docentes asignados
     */
    public function getSubjectsWithTeacherCounts() {
        try {
            $query = "SELECT m.*, COUNT(dm.id_docente) as teacher_count
                     FROM materia m
                     LEFT JOIN docente_materia dm ON m.id_materia = dm.id_materia
                     GROUP BY m.id_materia, m.nombre, m.horas_semanales, m.id_pauta_anep, m.en_conjunto, m.id_grupo_compartido, m.es_programa_italiano
                     ORDER BY m.nombre";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting subjects with teacher counts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Publica un horario
     */
    public function publishSchedule($scheduleId) {
        try {
            $this->db->beginTransaction();

            $createTableQuery = "CREATE TABLE IF NOT EXISTS horario_publicado (
                id_publicacion SERIAL PRIMARY KEY,
                id_horario_referencia INTEGER,
                fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                publicado_por INTEGER,
                activo BOOLEAN DEFAULT TRUE,
                descripcion TEXT
            )";
            $this->db->exec($createTableQuery);

            $deactivateQuery = "UPDATE horario_publicado SET activo = FALSE WHERE activo = TRUE";
            $this->db->exec($deactivateQuery);

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
