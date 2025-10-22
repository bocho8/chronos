<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

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

            // Only check conflicts if force_override is not set or false
            $forceOverride = $data['force_override'] ?? false;
            
            if (!$forceOverride) {
                if ($this->hasScheduleConflict($data)) {
                    throw new Exception("Ya existe una asignación para este horario");
                }

                if (!$this->isDocenteAvailable($data['id_docente'], $data['id_bloque'], $data['dia'])) {
                    throw new Exception("El docente no está disponible en este horario");
                }
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

            // Only check conflicts if force_override is not set or false
            $forceOverride = $data['force_override'] ?? false;
            
            if (!$forceOverride) {
                if ($this->hasScheduleConflict($data, $id)) {
                    throw new Exception("Ya existe una asignación para este horario");
                }

                if (!empty($data['id_docente']) && !empty($data['id_bloque']) && !empty($data['dia'])) {
                    if (!$this->isDocenteAvailable($data['id_docente'], $data['id_bloque'], $data['dia'], $id)) {
                        throw new Exception("El docente no está disponible en este horario");
                    }
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
     * Obtiene un bloque horario por ID
     */
    public function getBloqueById($id_bloque) {
        try {
            $query = "SELECT * FROM bloque_horario WHERE id_bloque = :id_bloque";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_bloque', $id_bloque, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting bloque by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea un nuevo bloque horario
     */
    public function createBloque($hora_inicio, $hora_fin) {
        try {
            $query = "INSERT INTO bloque_horario (hora_inicio, hora_fin) VALUES (:hora_inicio, :hora_fin)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':hora_inicio', $hora_inicio);
            $stmt->bindParam(':hora_fin', $hora_fin);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating bloque: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza un bloque horario existente
     */
    public function updateBloque($id_bloque, $hora_inicio, $hora_fin) {
        try {
            $query = "UPDATE bloque_horario SET hora_inicio = :hora_inicio, hora_fin = :hora_fin WHERE id_bloque = :id_bloque";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_bloque', $id_bloque, PDO::PARAM_INT);
            $stmt->bindParam(':hora_inicio', $hora_inicio);
            $stmt->bindParam(':hora_fin', $hora_fin);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating bloque: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un bloque horario
     */
    public function deleteBloque($id_bloque) {
        try {
            $query = "DELETE FROM bloque_horario WHERE id_bloque = :id_bloque";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_bloque', $id_bloque, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting bloque: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si un bloque horario tiene dependencias (usado en horarios)
     */
    public function checkBloqueDependencies($id_bloque) {
        try {
            $query = "SELECT COUNT(*) as count FROM horario WHERE id_bloque = :id_bloque";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_bloque', $id_bloque, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error checking bloque dependencies: " . $e->getMessage());
            return 0;
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
            
            $publishedCheck = $this->db->prepare("SELECT COUNT(*) as count FROM horario_publicado WHERE activo = true");
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
                     WHERE hp.activo = true
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

    /**
     * Creates a publish request for all schedules
     */
    public function createPublishRequest($userId) {
        try {
            $this->db->beginTransaction();

            // Check if there's already a pending request
            $existingQuery = "SELECT id_solicitud FROM solicitud_publicacion WHERE estado = 'pendiente' LIMIT 1";
            $existingStmt = $this->db->prepare($existingQuery);
            $existingStmt->execute();
            
            if ($existingStmt->fetch()) {
                throw new Exception("Ya existe una solicitud de publicación pendiente");
            }

            // Generate hash of current schedules
            $scheduleHash = $this->getScheduleHash();
            
            // Create the request
            $query = "INSERT INTO solicitud_publicacion (solicitado_por, snapshot_hash) VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$userId, $scheduleHash]);
            
            if ($result) {
                $this->db->commit();
                return $this->db->lastInsertId();
            } else {
                $this->db->rollBack();
                return false;
            }
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating publish request: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets the current publish request status
     */
    public function getPublishRequestStatus() {
        try {
            $query = "SELECT estado, fecha_solicitud, snapshot_hash FROM solicitud_publicacion 
                     WHERE estado = 'pendiente' 
                     ORDER BY fecha_solicitud DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                return 'none';
            }
            
            // Check if schedules have changed since request
            $currentHash = $this->getScheduleHash();
            if ($request['snapshot_hash'] !== $currentHash) {
                // Schedules changed, invalidate the request
                $this->cancelPublishRequest($request['id_solicitud']);
                return 'none';
            }
            
            return $request['estado'];
        } catch (PDOException $e) {
            error_log("Error getting publish request status: " . $e->getMessage());
            return 'none';
        }
    }

    /**
     * Approves a publish request and publishes schedules
     */
    public function approvePublishRequest($requestId, $userId) {
        try {
            // First update the request status
            $updateQuery = "UPDATE solicitud_publicacion 
                           SET estado = 'aprobado', revisado_por = ?, fecha_revision = CURRENT_TIMESTAMP 
                           WHERE id_solicitud = ? AND estado = 'pendiente'";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateResult = $updateStmt->execute([$userId, $requestId]);
            
            if (!$updateResult) {
                throw new Exception("No se pudo actualizar la solicitud");
            }

            // Publish the schedules using existing method (it handles its own transaction)
            $publishResult = $this->publishSchedule(1); // Use dummy ID since we're publishing all
            
            return $publishResult;
            
        } catch (PDOException $e) {
            error_log("Error approving publish request: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rejects a publish request
     */
    public function rejectPublishRequest($requestId, $userId, $reason = '') {
        try {
            $query = "UPDATE solicitud_publicacion 
                     SET estado = 'rechazado', revisado_por = ?, fecha_revision = CURRENT_TIMESTAMP, notas = ?
                     WHERE id_solicitud = ? AND estado = 'pendiente'";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$userId, $reason, $requestId]);
        } catch (PDOException $e) {
            error_log("Error rejecting publish request: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancels a pending publish request
     */
    public function cancelPublishRequest($requestId) {
        try {
            $query = "DELETE FROM solicitud_publicacion WHERE id_solicitud = ? AND estado = 'pendiente'";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$requestId]);
        } catch (PDOException $e) {
            error_log("Error canceling publish request: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generates a hash of current schedules to detect changes
     */
    public function getScheduleHash() {
        try {
            $query = "SELECT h.*, g.nombre as grupo_nombre, m.nombre as materia_nombre, 
                            u.nombre as docente_nombre, u.apellido as docente_apellido,
                            b.hora_inicio, b.hora_fin
                     FROM horario h
                     JOIN grupo g ON h.id_grupo = g.id_grupo
                     JOIN materia m ON h.id_materia = m.id_materia
                     JOIN docente d ON h.id_docente = d.id_docente
                     JOIN usuario u ON d.id_usuario = u.id_usuario
                     JOIN bloque_horario b ON h.id_bloque = b.id_bloque
                     ORDER BY h.id_horario";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create a string representation of all schedules
            $scheduleString = '';
            foreach ($schedules as $schedule) {
                $scheduleString .= implode('|', $schedule);
            }
            
            return md5($scheduleString);
        } catch (PDOException $e) {
            error_log("Error generating schedule hash: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Gets pending publish requests for director review
     */
    public function getPendingPublishRequests() {
        try {
            $query = "SELECT sp.*, u.nombre as solicitante_nombre, u.apellido as solicitante_apellido
                     FROM solicitud_publicacion sp
                     JOIN usuario u ON sp.solicitado_por = u.id_usuario
                     WHERE sp.estado = 'pendiente'
                     ORDER BY sp.fecha_solicitud DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting pending publish requests: " . $e->getMessage());
            return [];
        }
    }
}
